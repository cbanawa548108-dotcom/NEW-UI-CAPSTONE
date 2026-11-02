"""SARIMAX sales forecast.

For each fruit, models daily kg sold as a seasonal ARIMA process (weekly
seasonality, s=7) with weather and holidays as exogenous regressors:

    sales_t = SARIMA(p,d,q)(P,D,Q,7) + b1*temperature_t + b2*humidity_t + b3*rainfall_t
              + b4*is_holiday_t + sum_c g_c*[condition_t = c]

Weather conditions seen on fewer than MIN_CONDITION_DAYS training days are merged
into the nearest more common one; the mildest remaining condition is the reference
level (no dummy).

Pre-train + live updates: each fruit's model is fitted on its full sales
history (including the vendor's imported 2020-2024 logbook) and saved under
storage/app/ml/sarimax/. Every run applies the saved parameters to the latest
sales without re-estimating them (a Kalman filter pass, takes seconds), so
sales logged in the app move the forecast straight away. Only the order,
estimated parameters and exog scaling are saved (JSON), not pickled objects. Parameters are
re-estimated only when a fruit has no saved model or with --refit (scheduled
weekly): the order is picked by AIC from a small candidate grid, accuracy is
measured on the last HOLDOUT_DAYS recorded days, then the model is refit on all
history.

Days with no sales of any fruit (store closed, or nothing logged, such as the
gap between the logbook and live use) are missing values, not zeros. A fruit
with no sales on a day other fruits sold counts as 0.

Usage: python ml/sales_forecast.py [--horizon 7] [--refit]
"""
import argparse
import json
import os
import warnings
from concurrent.futures import ProcessPoolExecutor
from concurrent.futures.process import BrokenProcessPool
from datetime import datetime

# one BLAS thread per worker process; fruits are refit in parallel
for _var in ("OPENBLAS_NUM_THREADS", "MKL_NUM_THREADS", "OMP_NUM_THREADS"):
    os.environ.setdefault(_var, "1")

import numpy as np
import pandas as pd
from statsmodels.tools.sm_exceptions import ConvergenceWarning
from statsmodels.tsa.statespace.sarimax import SARIMAX

from common import (CONDITION_ORDER, MODEL_DIR, WEATHER_COLS, conditions_for, connect, emit, fail,
                    holiday_flags, load_weather, local_today, query_df, save_run, to_local_date, weather_for)

warnings.simplefilter("ignore", ConvergenceWarning)
warnings.simplefilter("ignore", UserWarning)
warnings.simplefilter("ignore", RuntimeWarning)

MIN_HISTORY_DAYS = 28
MIN_SALES_DAYS = 10
HOLDOUT_DAYS = 14
STALE_AFTER_DAYS = 7  # note in the forecast when no sales were logged for this long
ORDERS = [(1, 0, 1), (2, 0, 1), (1, 1, 1)]
SEASONAL_ORDERS = [(1, 0, 1, 7), (0, 1, 1, 7)]
MIN_CONDITION_DAYS = 30
FEATURES_VERSION = 2  # bump when the exogenous inputs change; saved models from older versions are refit
# Each refit worker needs a few hundred MB; more workers ran out of memory on a 4 GB laptop
MAX_WORKERS = int(os.environ.get("ML_WORKERS", 2))
SARIMAX_DIR = MODEL_DIR / "sarimax"


def load_sales(conn):
    df = query_df(conn, """
        SELECT si.inventory_item_id, ii.name, ii.price_per_unit, st.created_at, si.quantity
        FROM sales_items si
        JOIN sales_transactions st ON st.id = si.sale_transaction_id
        JOIN inventory_items ii ON ii.id = si.inventory_item_id
        WHERE st.type = 'sale' AND st.status = 'completed' AND si.quantity > 0
    """)
    if df.empty:
        return df
    df["date"] = to_local_date(df["created_at"])
    return df


def daily_series(grp, open_days, history_end):
    """Daily quantity from the fruit's first sale to history_end; NaN on days nothing was sold at all."""
    daily = grp.groupby("date")["quantity"].sum()
    series = daily.reindex(pd.date_range(daily.index.min(), history_end, freq="D"))
    series[series.isna() & series.index.isin(open_days)] = 0.0
    return series.astype(float)


def has_enough_history(series):
    return series.notna().sum() >= MIN_HISTORY_DAYS + HOLDOUT_DAYS and (series > 0).sum() >= MIN_SALES_DAYS


def condition_map(conditions):
    """Map each condition to the nearest one seen on at least MIN_CONDITION_DAYS days (ties go to the milder)."""
    counts = conditions.value_counts()
    kept = [c for c in CONDITION_ORDER if counts.get(c, 0) >= MIN_CONDITION_DAYS] or [counts.idxmax()]
    rank = {c: i for i, c in enumerate(CONDITION_ORDER)}
    return {c: min(kept, key=lambda k: (abs(rank[k] - rank[c]), rank[k])) for c in CONDITION_ORDER}


def build_exog(weather, dates, cmap):
    """Weather measurements, holiday flag and one dummy per kept condition except the mildest (the reference)."""
    x = weather_for(weather, dates)
    x["is_holiday"] = holiday_flags(dates).to_numpy()
    cond = conditions_for(weather, dates).map(cmap)
    for c in sorted(set(cmap.values()), key=CONDITION_ORDER.index)[1:]:
        x[f"cond_{c.lower()}"] = (cond == c).astype(float).to_numpy()
    return x


def scaler(exog):
    return exog.mean(), exog.std().replace(0, 1).fillna(1)


def build_model(y, exog, order, seasonal_order):
    return SARIMAX(
        y, exog=exog, order=tuple(order), seasonal_order=tuple(seasonal_order),
        trend="c" if order[1] == 0 and seasonal_order[1] == 0 else "n",
        enforce_stationarity=False, enforce_invertibility=False,
    )


def fit(y, exog, order, seasonal_order):
    return build_model(y, exog, order, seasonal_order).fit(disp=False, maxiter=200)


def select_order(y, exog):
    """Lowest-AIC candidate; returns (order, seasonal_order, fitted results)."""
    best = None
    for order in ORDERS:
        for seasonal in SEASONAL_ORDERS:
            try:
                res = fit(y, exog, order, seasonal)
            except Exception:
                continue
            if np.isfinite(res.aic) and (best is None or res.aic < best[2].aic):
                best = (order, seasonal, res)
    if best is None:
        raise RuntimeError("no SARIMAX order converged")
    return best


def evaluate(res, y, x, test_days):
    """Score a fit (trained before test_days) on the holdout days; gap days in between are skipped."""
    horizon = y.index[(y.index >= test_days[0]) & (y.index <= test_days[-1])]
    fc = res.get_forecast(len(horizon), exog=x.loc[horizon])
    pred = np.clip(pd.Series(fc.predicted_mean.to_numpy(), index=horizon).loc[test_days].to_numpy(), 0, None)
    actual = y.loc[test_days].to_numpy()

    err = actual - pred
    mae = float(np.mean(np.abs(err)))
    rmse = float(np.sqrt(np.mean(err ** 2)))
    # Weighted APE: robust when some days have zero sales (plain MAPE divides by zero)
    wape = float(np.sum(np.abs(err)) / max(np.sum(actual), 1e-9))
    nonzero = actual > 0
    mape = float(np.mean(np.abs(err[nonzero] / actual[nonzero])) * 100) if nonzero.any() else None
    accuracy = max(0.0, min(100.0, (1 - wape) * 100))
    return {"mae": round(mae, 3), "rmse": round(rmse, 3), "mape": None if mape is None else round(mape, 2),
            "wape": round(wape * 100, 2), "accuracy": round(accuracy, 2)}


def model_path(item_id):
    return SARIMAX_DIR / f"item_{item_id}.json"


def refit_item(item_id, y, weather):
    """Re-estimate one fruit's model on its full history and save it. Runs in a worker process."""
    test_days = y.dropna().index[-HOLDOUT_DAYS:]
    train = y.index < test_days[0]
    cmap = condition_map(conditions_for(weather, y[train].dropna().index))
    exog = build_exog(weather, y.index, cmap)
    mean, std = scaler(exog[train])
    x = (exog - mean) / std
    order, seasonal, res_train = select_order(y[train], x[train])
    metrics = evaluate(res_train, y, x, test_days)

    mean, std = scaler(exog)
    res = fit(y, (exog - mean) / std, order, seasonal)

    meta = {
        "order": list(order), "seasonal_order": list(seasonal), "aic": round(float(res.aic), 2),
        "params": {name: float(v) for name, v in res.params.items()},
        "exog_mean": mean.to_dict(), "exog_std": std.to_dict(), "metrics": metrics,
        "features_version": FEATURES_VERSION, "condition_map": cmap,
        "condition_reference": min(set(cmap.values()), key=CONDITION_ORDER.index),
        "exog_coefficients": {c: round(float(res.params.get(c, 0.0)), 4) for c in exog.columns},
        "exog_pvalues": {c: round(float(res.pvalues.get(c, 1.0)), 4) for c in exog.columns},
        "trained_through": str(y.dropna().index[-1].date()),
        "fitted_at": datetime.now().isoformat(timespec="seconds"),
    }
    SARIMAX_DIR.mkdir(parents=True, exist_ok=True)
    model_path(item_id).write_text(json.dumps(meta, indent=2), encoding="utf-8")
    return meta


def load_model(item_id):
    path = model_path(item_id)
    return json.loads(path.read_text(encoding="utf-8")) if path.exists() else None


def forecast_item(meta, y, weather, future_dates):
    """Filter the latest history with the saved parameters (no re-estimation) and forecast."""
    mean, std = pd.Series(meta["exog_mean"]), pd.Series(meta["exog_std"])
    exog = build_exog(weather, y.index, meta["condition_map"])[mean.index]
    future_exog = build_exog(weather, future_dates, meta["condition_map"])[mean.index]
    model = build_model(y, (exog - mean) / std, meta["order"], meta["seasonal_order"])
    current = model.filter(pd.Series(meta["params"])[model.param_names].to_numpy())
    fc = current.get_forecast(len(future_dates), exog=(future_exog - mean) / std)
    pred = np.clip(fc.predicted_mean.to_numpy(), 0, None)
    ci = np.clip(fc.conf_int(alpha=0.05).to_numpy(), 0, None)
    return pred, ci[:, 0], ci[:, 1]


def baseline_item(series, weather, future_dates):
    """Too little history for SARIMAX: same-weekday average of the last 28 recorded days."""
    recent = series.dropna().iloc[-28:]
    by_dow = recent.groupby(recent.index.dayofweek).mean()
    overall = recent.mean() if len(recent) else 0.0
    mean = np.array([by_dow.get(d.dayofweek, overall) for d in future_dates], dtype=float)
    spread = recent.std() if len(recent) > 1 else overall * 0.5
    spread = 0.0 if np.isnan(spread) else spread
    future_exog = weather_for(weather, future_dates) if not weather.empty else pd.DataFrame(index=future_dates, columns=WEATHER_COLS)
    return mean, np.clip(mean - 1.96 * spread, 0, None), mean + 1.96 * spread, future_exog


def refit_all(fruits, weather, force):
    """Re-estimate models in parallel for fruits that need it. Returns ({item_id: meta}, {item_id: error})."""
    def outdated(item_id):
        meta = load_model(item_id)
        return meta is None or meta.get("features_version") != FEATURES_VERSION

    todo = [f for f in fruits if has_enough_history(f["series"]) and (force or outdated(f["item_id"]))]
    refitted, errors = {}, {}
    workers = min(len(todo), os.cpu_count() or 1, MAX_WORKERS)
    if workers > 1:
        try:
            with ProcessPoolExecutor(max_workers=workers) as pool:
                futures = {f["item_id"]: pool.submit(refit_item, f["item_id"], f["series"], weather) for f in todo}
                for item_id, future in futures.items():
                    try:
                        refitted[item_id] = future.result()
                    except BrokenProcessPool:
                        pass  # a worker ran out of memory; refit below in this process
                    except Exception as exc:
                        errors[item_id] = f"refit failed: {exc}"
        except BrokenProcessPool:
            pass
    # Sequential (ML_WORKERS=1, or what the pool could not finish): slower but needs memory for one fit only
    for f in todo:
        if f["item_id"] in refitted or f["item_id"] in errors:
            continue
        try:
            refitted[f["item_id"]] = refit_item(f["item_id"], f["series"], weather)
        except Exception as exc:
            errors[f["item_id"]] = f"refit failed: {exc}"
    return refitted, errors


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--horizon", type=int, default=7)
    parser.add_argument("--refit", action="store_true", help="re-estimate every fruit's parameters")
    args = parser.parse_args()

    conn = connect()
    sales = load_sales(conn)
    if sales.empty:
        save_run(conn, "SARIMAX", "skipped", message="No completed sales to train on.")
        fail("No completed sales to train on.")

    weather = load_weather(conn)
    today = local_today()
    # Today is still in progress, so history ends yesterday and the forecast starts today
    history_end = today - pd.Timedelta(days=1)
    future_dates = pd.date_range(today, periods=args.horizon, freq="D")
    future_exog = weather_for(weather, future_dates)

    open_days = pd.DatetimeIndex(sales["date"].unique())
    fruits = [
        {"item_id": int(item_id), "name": name, "price": float(grp["price_per_unit"].iloc[0]),
         "series": daily_series(grp, open_days, history_end)}
        for (item_id, name), grp in sales.groupby(["inventory_item_id", "name"])
    ]
    refitted, refit_errors = refit_all(fruits, weather, args.refit)

    rows, per_fruit, skipped = [], {}, []
    for f in fruits:
        item_id, name, price, series = f["item_id"], f["name"], f["price"], f["series"]
        model_name = "SARIMAX"
        fut_exog = future_exog
        try:
            if not has_enough_history(series):
                raise ValueError(f"only {series.notna().sum()} recorded days / {(series > 0).sum()} sales days of history")
            if item_id in refit_errors:
                raise RuntimeError(refit_errors[item_id])
            meta = load_model(item_id)
            if meta is None:
                raise RuntimeError("no saved model")
            mean, lo, hi = forecast_item(meta, series, weather, future_dates)
            accuracy = meta["metrics"]["accuracy"]
            last_sale = series.dropna().index[-1]
            per_fruit[name] = {**meta["metrics"], "order": meta["order"], "seasonal_order": meta["seasonal_order"],
                               "aic": meta["aic"], "exog_coefficients": meta["exog_coefficients"],
                               "exog_pvalues": meta["exog_pvalues"],
                               "condition_reference": meta["condition_reference"],
                               "trained_through": meta["trained_through"],
                               "fitted_at": meta["fitted_at"], "refitted": item_id in refitted,
                               "last_recorded_day": str(last_sale.date())}
            note = (f"SARIMAX{tuple(meta['order'])}x{tuple(meta['seasonal_order'])} AIC={meta['aic']}, "
                    f"trained through {meta['trained_through']}")
            gap = (history_end - last_sale).days
            if gap >= STALE_AFTER_DAYS:
                note += f"; no sales recorded since {last_sale.date()} ({gap} days), so intervals stay wide until new sales are logged"
        except Exception as exc:
            model_name = "Baseline"
            mean, lo, hi, fut_exog = baseline_item(series, weather, future_dates)
            accuracy = 0
            note = f"Weekday average (SARIMAX not used: {exc})"
            skipped.append({"fruit": name, "reason": str(exc)})

        for i, d in enumerate(future_dates):
            w = fut_exog.iloc[i] if len(fut_exog) else {}
            rows.append((
                item_id, name, d.date(), round(float(mean[i]), 2), round(float(lo[i]), 2), round(float(hi[i]), 2),
                round(float(mean[i]) * price, 2), accuracy,
                None if pd.isna(w.get("temperature")) else round(float(w["temperature"]), 2),
                None if pd.isna(w.get("humidity")) else round(float(w["humidity"]), 2),
                None if pd.isna(w.get("rainfall")) else round(float(w["rainfall"]), 2),
                model_name, note,
            ))

    now = datetime.now()
    cur = conn.cursor()
    cur.execute("DELETE FROM ai_forecasts WHERE forecast_date >= %s", (today.date(),))
    cur.executemany(
        "INSERT INTO ai_forecasts (inventory_item_id, fruit_name, forecast_date, predicted_sales_qty, lower_bound, "
        "upper_bound, predicted_revenue, forecast_accuracy, temperature, humidity, rainfall, model, notes, created_at, updated_at) "
        "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
        [r + (now, now) for r in rows],
    )
    conn.commit()
    cur.close()

    accuracies = [m["accuracy"] for m in per_fruit.values()]
    summary = {
        "horizon_days": args.horizon,
        "fruits_modeled": len(per_fruit),
        "fruits_baseline": len(skipped),
        "fruits_refitted": len(refitted),
        "overall_accuracy": round(float(np.mean(accuracies)), 2) if accuracies else None,
        "mean_mae": round(float(np.mean([m["mae"] for m in per_fruit.values()])), 3) if per_fruit else None,
        "mean_rmse": round(float(np.mean([m["rmse"] for m in per_fruit.values()])), 3) if per_fruit else None,
        "per_fruit": per_fruit,
        "baseline": skipped,
    }
    status = "success" if per_fruit else "skipped"
    save_run(conn, "SARIMAX", status, training_rows=len(sales), metrics=summary,
             message=None if per_fruit else "Not enough history for SARIMAX; used weekday averages.")
    conn.close()
    emit({"success": True, "model": "SARIMAX", **summary})


if __name__ == "__main__":
    try:
        main()
    except SystemExit:
        raise
    except Exception as exc:
        fail(f"{type(exc).__name__}: {exc}")
