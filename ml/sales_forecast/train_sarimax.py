"""Train and evaluate a SARIMAX model for daily Guayaquil PRODUCE sales.

    sales_t = SARIMA(p,d,q)(P,D,Q,7) + b * exog_t

Exogenous: is_holiday, onpromotion / 100, temperature_c, humidity_pct,
rainfall_mm and weather_condition dummies ("Clear" is the reference level).
If no weather has been added yet, only holiday + promotion are used.

Closed days (Jan 1, Dec 25) stay NaN in the target; the Kalman filter in
statsmodels treats them as missing observations.

The split is by date (first 80% train, last 20% test). The order is chosen by
lowest AIC on the training data. Evaluation is rolling 7-day-ahead: at each
weekly origin in the test period the fitted parameters are applied to all
data up to the origin (no re-fitting) and the next 7 days are forecast with
their known exogenous values. Baseline: seasonal naive (same weekday one
week earlier).

Outputs (model_output/): sarimax_model.pkl, metrics.json,
test_predictions.csv, forecast_chart.png

Usage: python train_sarimax.py   (run prepare_sales.py and add_weather.py first)
"""
import itertools
import json
import warnings
from pathlib import Path

import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
import numpy as np
import pandas as pd
from statsmodels.tools.sm_exceptions import ConvergenceWarning
from statsmodels.tsa.statespace.sarimax import SARIMAX

BASE_DIR = Path(__file__).resolve().parent
SALES_CSV = BASE_DIR / "data" / "processed" / "guayaquil_produce_daily.csv"
OUTPUT_DIR = BASE_DIR / "model_output"

TRAIN_FRACTION = 0.8
SEASON = 7
HORIZON = 7
ORDERS = [(1, 0, 1), (1, 1, 1), (2, 1, 1), (0, 1, 1)]
SEASONAL_ORDERS = [(1, 0, 1, SEASON), (0, 1, 1, SEASON), (1, 1, 1, SEASON)]
WEATHER_NUMERIC = ["temperature_c", "humidity_pct", "rainfall_mm"]
REFERENCE_CONDITION = "Clear"
CONDITION_ORDER = ["Clear", "Cloudy", "Drizzle", "Rain", "Thunderstorm"]  # mildest first
SIGNIFICANCE = 0.05


def load_data():
    df = pd.read_csv(SALES_CSV, parse_dates=["date"]).set_index("date").asfreq("D")
    return df["sales"], df


def build_exog(df, train_index):
    """Exogenous matrix and whether weather was included."""
    exog = pd.DataFrame(index=df.index)
    exog["is_holiday"] = df["is_holiday"].astype(float)
    # promotions are NaN on closed days; nothing is sold or promoted then
    exog["onpromotion"] = df["onpromotion"].fillna(0) / 100

    has_weather = df["temperature_c"].notna().any()
    if not has_weather:
        print("NOTICE: no weather data found in the processed CSV "
              "(run add_weather.py). Using is_holiday + onpromotion only.")
        return exog, False

    missing = int(df[WEATHER_NUMERIC + ["weather_condition"]].isna().any(axis=1).sum())
    if missing:
        print(f"Interpolating weather for {missing} day(s) with missing values")
    for col in WEATHER_NUMERIC:
        exog[col] = df[col].interpolate(method="time", limit_direction="both")

    condition = df["weather_condition"].ffill().bfill()
    dummies = pd.get_dummies(condition, prefix="weather", dtype=float)
    # Without a reference level the dummies sum to 1 and are collinear with the
    # level, so fall back to the mildest condition present if "Clear" never occurs
    present = [c for c in CONDITION_ORDER if f"weather_{c}" in dummies]
    reference = REFERENCE_CONDITION if REFERENCE_CONDITION in present else present[0]
    if reference != REFERENCE_CONDITION:
        print(f"NOTICE: no '{REFERENCE_CONDITION}' days in the data; using '{reference}' "
              "as the weather_condition reference level")
    dummies = dummies.drop(columns=f"weather_{reference}")
    # a condition that never occurs (or always occurs) in training can't be estimated
    constant = [c for c in dummies if dummies.loc[train_index, c].nunique() <= 1]
    if constant:
        print(f"Dropping weather dummies constant in training: {constant}")
    exog = exog.join(dummies.drop(columns=constant))
    return exog, True


def fit(y, exog, order, seasonal_order):
    # with no differencing the series needs an intercept, otherwise it is
    # modelled as fluctuating around 0 and the AR terms run to the unit root
    trend = "c" if order[1] == 0 and seasonal_order[1] == 0 else None
    model = SARIMAX(y, exog=exog, order=order, seasonal_order=seasonal_order, trend=trend)
    with warnings.catch_warnings():
        warnings.simplefilter("ignore", ConvergenceWarning)
        warnings.simplefilter("ignore", UserWarning)
        return model.fit(disp=False, maxiter=200)


def select_order(y_train, x_train):
    print("\nOrder selection (AIC on training data):")
    grid = []
    for order, seasonal_order in itertools.product(ORDERS, SEASONAL_ORDERS):
        label = f"SARIMAX{order}x{seasonal_order}"
        try:
            res = fit(y_train, x_train, order, seasonal_order)
        except Exception as exc:  # noqa: BLE001 - one failing candidate shouldn't stop the search
            print(f"  {label:<32} failed: {exc}")
            continue
        converged = res.mle_retvals.get("converged", True) and np.isfinite(res.llf)
        grid.append({"order": order, "seasonal_order": seasonal_order,
                     "aic": float(res.aic), "converged": bool(converged)})
        note = "" if converged else "  (did not converge, excluded)"
        print(f"  {label:<32} AIC = {res.aic:,.1f}{note}")
    candidates = [g for g in grid if g["converged"]]
    if not candidates:
        raise RuntimeError("No SARIMAX candidate converged")
    best = min(candidates, key=lambda g: g["aic"])
    return best, grid


def rolling_forecast(results, y, exog, test_index):
    """7-day-ahead forecasts from each weekly origin, using the fitted params."""
    preds = pd.Series(np.nan, index=test_index)
    for start in range(0, len(test_index), HORIZON):
        window = test_index[start:start + HORIZON]
        origin = window[0]
        history = y.index < origin
        res = results.apply(y[history], exog=exog[history])
        fc = res.forecast(steps=len(window), exog=exog.loc[window])
        preds.loc[window] = fc.to_numpy()
    return preds


def metrics(actual, predicted):
    err = actual - predicted
    return {
        "mae": float(np.mean(np.abs(err))),
        "rmse": float(np.sqrt(np.mean(err ** 2))),
        "mape": float(np.mean(np.abs(err) / actual) * 100),
    }


def plot(pred_df, label, path):
    surface, ink, muted, grid = "#fcfcfb", "#0b0b0b", "#52514e", "#e4e3df"
    fig, ax = plt.subplots(figsize=(12, 5), facecolor=surface)
    ax.set_facecolor(surface)
    ax.plot(pred_df.index, pred_df["actual"], color=ink, lw=1.6, label="Actual")
    ax.plot(pred_df.index, pred_df["sarimax"], color="#2a78d6", lw=1.6, label=label)
    ax.plot(pred_df.index, pred_df["baseline"], color="#eb6834", lw=1.0, ls="--",
            label="Seasonal naive baseline")
    ax.set_title("Guayaquil PRODUCE daily sales: test period, rolling 7-day-ahead forecasts",
                 loc="left", color=ink, fontsize=12, pad=28)
    ax.set_ylabel("Sales (units)", color=muted)
    ax.yaxis.set_major_formatter(matplotlib.ticker.FuncFormatter(lambda v, _: f"{v:,.0f}"))
    ax.grid(axis="y", color=grid, lw=0.8)
    ax.tick_params(colors=muted)
    for side in ("top", "right", "left"):
        ax.spines[side].set_visible(False)
    ax.spines["bottom"].set_color(grid)
    ax.legend(frameon=False, loc="lower left", bbox_to_anchor=(0, 1.0), ncol=3, labelcolor=muted)
    fig.tight_layout()
    fig.savefig(path, dpi=150, facecolor=surface)
    plt.close(fig)


def main():
    y, df = load_data()
    n_train = int(len(y) * TRAIN_FRACTION)
    train_index, test_index = y.index[:n_train], y.index[n_train:]
    print(f"Rows: {len(y)} ({int(y.isna().sum())} closed days kept as NaN)")
    print(f"Train: {train_index[0].date()} to {train_index[-1].date()} ({len(train_index)} days)")
    print(f"Test:  {test_index[0].date()} to {test_index[-1].date()} ({len(test_index)} days)")

    exog, has_weather = build_exog(df, train_index)
    exog_cols = list(exog.columns)
    print(f"Exogenous variables: {exog_cols}")

    best, grid = select_order(y[train_index], exog.loc[train_index])
    order, seasonal_order = best["order"], best["seasonal_order"]
    label = f"SARIMAX{order}x{seasonal_order}"
    print(f"\nSelected {label} (AIC {best['aic']:,.1f})")

    results = fit(y[train_index], exog.loc[train_index], order, seasonal_order)

    preds = rolling_forecast(results, y, exog, test_index)
    baseline = y.shift(SEASON).loc[test_index]
    pred_df = pd.DataFrame({"actual": y[test_index], "sarimax": preds, "baseline": baseline})

    scored = pred_df.dropna()
    sarimax_m = metrics(scored["actual"], scored["sarimax"])
    baseline_m = metrics(scored["actual"], scored["baseline"])
    improvement = (baseline_m["mae"] - sarimax_m["mae"]) / baseline_m["mae"] * 100

    coefs = {
        name: {"coef": float(results.params[name]), "p_value": float(results.pvalues[name])}
        for name in results.params.index
    }

    print(f"\nTest evaluation ({len(scored)} days scored, closed days excluded):")
    print(f"  {'':<32}{'MAE':>10}{'RMSE':>11}{'MAPE':>9}")
    for name, m in ((label, sarimax_m), ("Seasonal naive", baseline_m)):
        print(f"  {name:<32}{m['mae']:>10,.1f}{m['rmse']:>11,.1f}{m['mape']:>8.2f}%")
    print(f"  MAE improvement vs baseline: {improvement:.1f}%")

    print("\nCoefficients:")
    print(f"  {'':<22}{'coef':>12}{'p-value':>10}")
    for name, c in coefs.items():
        flag = " *" if c["p_value"] < SIGNIFICANCE else ""
        print(f"  {name:<22}{c['coef']:>12.4f}{c['p_value']:>10.4f}{flag}")
    print(f"  (* p < {SIGNIFICANCE})")

    weather_terms = [c for c in exog_cols if c in WEATHER_NUMERIC or c.startswith("weather_")]
    significant_weather = [c for c in weather_terms if coefs[c]["p_value"] < SIGNIFICANCE]
    if has_weather:
        print(f"Significant weather coefficients: {significant_weather or 'none'}")

    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    results.save(OUTPUT_DIR / "sarimax_model.pkl")
    pred_df.round(2).to_csv(OUTPUT_DIR / "test_predictions.csv", index_label="date",
                            date_format="%Y-%m-%d")
    report = {
        "model": label,
        "order": list(order),
        "seasonal_order": list(seasonal_order),
        "aic": best["aic"],
        "aic_grid": [{**g, "order": list(g["order"]), "seasonal_order": list(g["seasonal_order"])}
                     for g in grid],
        "weather_used": bool(has_weather),
        "exog": exog_cols,
        "train_period": [str(train_index[0].date()), str(train_index[-1].date())],
        "test_period": [str(test_index[0].date()), str(test_index[-1].date())],
        "evaluation": f"rolling {HORIZON}-day-ahead from weekly origins, fitted params applied without re-fitting",
        "days_scored": len(scored),
        "sarimax": sarimax_m,
        "baseline_seasonal_naive": baseline_m,
        "mae_improvement_pct": improvement,
        "coefficients": coefs,
        "significant_weather_terms": significant_weather,
    }
    (OUTPUT_DIR / "metrics.json").write_text(json.dumps(report, indent=2), encoding="utf-8")
    plot(pred_df, label, OUTPUT_DIR / "forecast_chart.png")
    print(f"\nSaved sarimax_model.pkl, metrics.json, test_predictions.csv, forecast_chart.png "
          f"to {OUTPUT_DIR.relative_to(BASE_DIR)}/")


if __name__ == "__main__":
    main()
