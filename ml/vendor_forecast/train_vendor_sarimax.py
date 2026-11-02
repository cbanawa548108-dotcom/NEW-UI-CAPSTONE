"""Per-fruit SARIMAX sales forecast for the Davao vendor, with weather.

Uses the same method as ml/sales_forecast/train_sarimax.py (its fit, order
selection, rolling evaluation and metrics are imported, not copied):

    units_sold_t = SARIMA(p,d,q)(P,D,Q,7) + b * exog_t

Exogenous: is_holiday, temperature_c, humidity_pct, rainfall_mm and
weather_condition dummies. Conditions seen on fewer than MIN_CONDITION_DAYS
training days are merged into the next condition (e.g. 2 Clear days -> Cloudy)
so no coefficient rests on a handful of days; the mildest remaining condition
is the reference level.

Per fruit: date split (first 80% train, last 20% test), order by lowest AIC on
training data, rolling 7-day-ahead forecasts from weekly origins with the
fitted params (no re-fitting), compared with the seasonal naive baseline
(same weekday one week earlier).

Outputs (model_output/sales/): metrics.json, summary.csv, test_predictions.csv,
forecast_chart.png, models/<fruit>.pkl

Usage: python train_vendor_sarimax.py   (run prepare_vendor.py first)
"""
import contextlib
import importlib.util
import io
import json
import os
from concurrent.futures import ProcessPoolExecutor, as_completed
from pathlib import Path

# one BLAS thread per worker process; fruits already run in parallel
for _var in ("OPENBLAS_NUM_THREADS", "MKL_NUM_THREADS", "OMP_NUM_THREADS"):
    os.environ.setdefault(_var, "1")

import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
import pandas as pd

BASE_DIR = Path(__file__).resolve().parent
DAILY_CSV = BASE_DIR / "data" / "processed" / "vendor_daily.csv"
OUTPUT_DIR = BASE_DIR / "model_output" / "sales"

MIN_CONDITION_DAYS = 30
CHART_DAYS = 84  # last 12 weeks of the test period


def load_module(name, path):
    spec = importlib.util.spec_from_file_location(name, path)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


ts = load_module("guayaquil_train_sarimax", BASE_DIR.parent / "sales_forecast" / "train_sarimax.py")
WEATHER_NUMERIC = ts.WEATHER_NUMERIC
CONDITION_ORDER = ts.CONDITION_ORDER


def merge_rare_conditions(condition, train_index):
    """Fold conditions too rare in training into the next condition in CONDITION_ORDER."""
    condition = condition.copy()
    merged = {}
    for name in CONDITION_ORDER:
        n = int((condition.loc[train_index] == name).sum())
        if 0 < n < MIN_CONDITION_DAYS:
            idx = CONDITION_ORDER.index(name)
            neighbours = CONDITION_ORDER[idx + 1:] + CONDITION_ORDER[:idx][::-1]
            target = next(c for c in neighbours if (condition.loc[train_index] == c).sum() >= MIN_CONDITION_DAYS)
            condition[condition == name] = target
            merged[name] = target
    return condition, merged


def build_exog(df, train_index):
    exog = pd.DataFrame(index=df.index)
    exog["is_holiday"] = df["is_holiday"].astype(float)
    for col in WEATHER_NUMERIC:
        exog[col] = df[col].interpolate(method="time", limit_direction="both")

    condition, merged = merge_rare_conditions(df["weather_condition"].ffill().bfill(), train_index)
    present = [c for c in CONDITION_ORDER if (condition.loc[train_index] == c).any()]
    reference = present[0]
    dummies = pd.get_dummies(condition, prefix="weather", dtype=float).drop(columns=f"weather_{reference}")
    constant = [c for c in dummies if dummies.loc[train_index, c].nunique() <= 1]
    exog = exog.join(dummies.drop(columns=constant))
    return exog, reference, merged


def run_fruit(product, df):
    """Fit, select and evaluate one fruit. Returns (log text, result dict, predictions)."""
    log = io.StringIO()
    with contextlib.redirect_stdout(log):
        df = df.set_index("date").asfreq("D")
        y = df["units_sold"]
        n_train = int(len(y) * ts.TRAIN_FRACTION)
        train_index, test_index = y.index[:n_train], y.index[n_train:]
        exog, reference, merged = build_exog(df, train_index)

        best, grid = ts.select_order(y[train_index], exog.loc[train_index])
        order, seasonal_order = best["order"], best["seasonal_order"]
        results = ts.fit(y[train_index], exog.loc[train_index], order, seasonal_order)
        preds = ts.rolling_forecast(results, y, exog, test_index)

    baseline = y.shift(ts.SEASON).loc[test_index]
    pred_df = pd.DataFrame({"actual": y[test_index], "sarimax": preds, "baseline": baseline})
    scored = pred_df.dropna()
    sarimax_m = ts.metrics(scored["actual"], scored["sarimax"])
    baseline_m = ts.metrics(scored["actual"], scored["baseline"])

    models_dir = OUTPUT_DIR / "models"
    models_dir.mkdir(parents=True, exist_ok=True)
    results.save(models_dir / f"{product.lower()}.pkl", remove_data=True)

    result = {
        "model": f"SARIMAX{order}x{seasonal_order}",
        "order": list(order),
        "seasonal_order": list(seasonal_order),
        "aic": best["aic"],
        "aic_grid": [{**g, "order": list(g["order"]), "seasonal_order": list(g["seasonal_order"])} for g in grid],
        "exog": list(exog.columns),
        "weather_reference": reference,
        "weather_merged": merged,
        "train_period": [str(train_index[0].date()), str(train_index[-1].date())],
        "test_period": [str(test_index[0].date()), str(test_index[-1].date())],
        "days_scored": len(scored),
        "sarimax": sarimax_m,
        "baseline_seasonal_naive": baseline_m,
        "mae_improvement_pct": (baseline_m["mae"] - sarimax_m["mae"]) / baseline_m["mae"] * 100,
        "coefficients": {name: {"coef": float(results.params[name]), "p_value": float(results.pvalues[name])}
                         for name in results.params.index},
    }
    weather_terms = [c for c in exog.columns if c in WEATHER_NUMERIC or c.startswith("weather_")]
    result["significant_weather_terms"] = [c for c in weather_terms
                                           if result["coefficients"][c]["p_value"] < ts.SIGNIFICANCE]
    return log.getvalue(), result, pred_df.assign(product=product)


def plot(predictions, results, path):
    surface, ink, muted, grid = "#fcfcfb", "#0b0b0b", "#52514e", "#e4e3df"
    products = list(results)
    fig, axes = plt.subplots(5, 2, figsize=(13, 15), facecolor=surface, sharex=True)
    for ax, product in zip(axes.flat, products):
        p = predictions[predictions["product"] == product].tail(CHART_DAYS)
        ax.set_facecolor(surface)
        ax.plot(p.index, p["actual"], color=ink, lw=1.4, label="Actual")
        ax.plot(p.index, p["sarimax"], color="#2a78d6", lw=1.4, label="SARIMAX")
        ax.plot(p.index, p["baseline"], color="#eb6834", lw=0.9, ls="--", label="Seasonal naive")
        r = results[product]
        ax.set_title(f"{product}   MAPE {r['sarimax']['mape']:.1f}% vs {r['baseline_seasonal_naive']['mape']:.1f}%",
                     loc="left", color=ink, fontsize=10)
        ax.grid(axis="y", color=grid, lw=0.8)
        ax.tick_params(colors=muted, labelsize=8)
        for side in ("top", "right", "left"):
            ax.spines[side].set_visible(False)
        ax.spines["bottom"].set_color(grid)
    for ax in axes[:, 0]:
        ax.set_ylabel("Units sold", color=muted, fontsize=9)
    handles, labels = axes.flat[0].get_legend_handles_labels()
    fig.legend(handles, labels, loc="upper left", bbox_to_anchor=(0.01, 0.975), ncol=3, frameon=False,
               labelcolor=muted)
    fig.suptitle(f"Davao vendor daily sales per fruit: last {CHART_DAYS // 7} weeks of test period, "
                 "rolling 7-day-ahead forecasts", x=0.01, ha="left", color=ink, fontsize=12)
    fig.tight_layout(rect=(0, 0, 1, 0.96))
    fig.savefig(path, dpi=130, facecolor=surface)
    plt.close(fig)


def main():
    data = pd.read_csv(DAILY_CSV, parse_dates=["date"])
    products = sorted(data["product"].unique())
    first = data[data["product"] == products[0]]
    n_train = int(first["date"].nunique() * ts.TRAIN_FRACTION)
    dates = sorted(first["date"])
    print(f"Fruits: {len(products)}, days per fruit: {len(dates)}")
    print(f"Train: {dates[0].date()} to {dates[n_train - 1].date()} ({n_train} days)")
    print(f"Test:  {dates[n_train].date()} to {dates[-1].date()} ({len(dates) - n_train} days)")
    print(f"Fitting {len(ts.ORDERS) * len(ts.SEASONAL_ORDERS)} candidate orders per fruit in parallel...")

    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    results, predictions = {}, []
    workers = min(len(products), os.cpu_count() or 1)
    with ProcessPoolExecutor(max_workers=workers) as pool:
        futures = {pool.submit(run_fruit, p, data[data["product"] == p].drop(columns="product")): p
                   for p in products}
        for future in as_completed(futures):
            product = futures[future]
            log, result, pred_df = future.result()
            results[product] = result
            predictions.append(pred_df)
            print(f"\n=== {product} ===")
            if result["weather_merged"]:
                print(f"Rare weather conditions merged: {result['weather_merged']}; "
                      f"reference level: {result['weather_reference']}")
            print(log, end="")
            print(f"Selected {result['model']} (AIC {result['aic']:,.1f})")
            s, b = result["sarimax"], result["baseline_seasonal_naive"]
            print(f"  SARIMAX  MAE {s['mae']:7.2f}  RMSE {s['rmse']:7.2f}  MAPE {s['mape']:6.2f}%")
            print(f"  Naive    MAE {b['mae']:7.2f}  RMSE {b['rmse']:7.2f}  MAPE {b['mape']:6.2f}%")
            print(f"  MAE improvement vs baseline: {result['mae_improvement_pct']:.1f}%")
            for term in result["exog"]:
                c = result["coefficients"][term]
                flag = " *" if c["p_value"] < ts.SIGNIFICANCE else ""
                print(f"  {term:<18}{c['coef']:>10.3f}  p={c['p_value']:.4f}{flag}", flush=True)

    results = {p: results[p] for p in products}
    summary = pd.DataFrame([{
        "product": p,
        "model": r["model"],
        "sarimax_mae": r["sarimax"]["mae"],
        "sarimax_rmse": r["sarimax"]["rmse"],
        "sarimax_mape": r["sarimax"]["mape"],
        "baseline_mae": r["baseline_seasonal_naive"]["mae"],
        "baseline_mape": r["baseline_seasonal_naive"]["mape"],
        "mae_improvement_pct": r["mae_improvement_pct"],
        "significant_weather": ", ".join(r["significant_weather_terms"]) or "none",
    } for p, r in results.items()])

    print("\n=== Summary (test period, rolling 7-day-ahead) ===")
    print(summary.drop(columns=["sarimax_rmse"]).round(2).to_string(index=False))
    print(f"\nMean MAPE: SARIMAX {summary['sarimax_mape'].mean():.2f}% vs "
          f"baseline {summary['baseline_mape'].mean():.2f}%; "
          f"mean MAE improvement {summary['mae_improvement_pct'].mean():.1f}%")
    beat = int((summary["mae_improvement_pct"] > 0).sum())
    print(f"SARIMAX beats the baseline for {beat} of {len(summary)} fruits")
    print(f"(* p < {ts.SIGNIFICANCE})")

    pred_all = pd.concat(predictions)
    pred_all.round(2).reset_index().rename(columns={"index": "date"})[
        ["date", "product", "actual", "sarimax", "baseline"]].to_csv(
        OUTPUT_DIR / "test_predictions.csv", index=False, date_format="%Y-%m-%d")
    summary.round(4).to_csv(OUTPUT_DIR / "summary.csv", index=False)
    (OUTPUT_DIR / "metrics.json").write_text(json.dumps(results, indent=2), encoding="utf-8")
    plot(pred_all, results, OUTPUT_DIR / "forecast_chart.png")
    print(f"\nSaved metrics.json, summary.csv, test_predictions.csv, forecast_chart.png, models/ "
          f"to {OUTPUT_DIR.relative_to(BASE_DIR)}/")


if __name__ == "__main__":
    main()
