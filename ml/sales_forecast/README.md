# FreshTrack – SARIMAX sales forecast (Python)

Standalone Python pipeline that develops and evaluates the SARIMAX sales
forecasting model for FreshTrack. It runs separately from the Laravel app.

## Data source

Kaggle **Store Sales – Time Series Forecasting** (Corporación Favorita, Ecuador):
<https://www.kaggle.com/competitions/store-sales-time-series-forecasting>

The pipeline uses only the **PRODUCE** family in the 8 stores in **Guayaquil**
(stores 24, 26, 28, 29, 30, 32, 34, 51). Guayaquil is a tropical coastal city,
which makes it a reasonable stand-in for Davao. Daily sales and `onpromotion` are summed across the 8 stores.

Weather comes from the **Open-Meteo Historical Weather API**, which is free and needs no key
(<https://archive-api.open-meteo.com/v1/archive>). The pipeline requests lat −2.19, lon −79.89,
timezone America/Guayaquil.

## Date window: 2015-06-01 to 2017-08-15 (807 days)

Data before **June 2015 is excluded on purpose.** Before that date, PRODUCE sales in
`train.csv` are mostly zero because the category was not yet being recorded. The real
demand was not zero. If those rows were used, the model would learn a false structural
break, and the zero days would pull the fitted level and seasonality toward zero.

Four days in the window have no sales because the stores were closed:
2015-12-25, 2016-01-01, 2016-12-25 and 2017-01-01. They are kept as blank (NaN) and
flagged `is_closed = 1`. They are **not** filled with 0. The SARIMAX Kalman filter treats
them as missing observations, and they are left out of the metrics.

## Setup

Requires Python 3.12. From the project root:

```bash
py -3.12 -m venv ml/.venv
ml/.venv/Scripts/python -m pip install -r ml/sales_forecast/requirements.txt
```

Place `train.csv`, `stores.csv` and `holidays_events.csv` from Kaggle in
`ml/sales_forecast/data/raw/`. This folder is git-ignored.

## Run

From `ml/sales_forecast/`, run the three scripts in order:

```bash
../.venv/Scripts/python prepare_sales.py   # -> data/processed/guayaquil_produce_daily.csv
../.venv/Scripts/python add_weather.py     # adds weather columns to that CSV
../.venv/Scripts/python train_sarimax.py   # -> model_output/
```

`train_sarimax.py` takes about 3 minutes. If `add_weather.py` has not been run, it uses
`is_holiday` and `onpromotion` only and prints a notice.

## What each script does

**prepare_sales.py**
- Builds one row per calendar day with these columns:
  - `sales`, `onpromotion`, `is_closed`
  - `is_holiday`: National holidays, plus Guayaquil/Guayas local and regional ones; excludes transferred holidays and "Work Day" rows
  - `day_of_week` (0 = Monday), `is_weekend`, `month`
  - empty weather columns, filled in by the next script

**add_weather.py**
- Fetches these fields from Open-Meteo:
  - daily mean temperature (`temperature_c`)
  - precipitation (`rainfall_mm`)
  - WMO weather code
  - hourly relative humidity, averaged per day (`humidity_pct`)
- Maps the WMO code to `weather_condition`:
  - 0–1 → Clear
  - 2–48 → Cloudy
  - 51–57 → Drizzle
  - 61–82 → Rain
  - 95–99 → Thunderstorm

**train_sarimax.py**
- **Exogenous variables:**
  - `is_holiday`
  - `onpromotion / 100`
  - `temperature_c`, `humidity_pct`, `rainfall_mm`
  - `weather_condition` dummies
- **Weather gaps** are interpolated.
- **Reference level for the dummies:** "Clear" is the intended reference. Open-Meteo's
  daily code is the most severe condition of the day, and no day in 2015–2017 Guayaquil
  comes out as Clear. The script therefore falls back to the mildest condition present,
  **Cloudy**, and prints a notice. Without a reference level the dummies would be
  perfectly collinear.
- **Split:** by date, never random. The first 80% is training data
  (2015-06-01 to 2017-03-06, 645 days). The last 20% is test data
  (2017-03-07 to 2017-08-15, 162 days).
- **Order selection:** lowest AIC on the training data, from
  (p,d,q) ∈ {(1,0,1),(1,1,1),(2,1,1),(0,1,1)} × (P,D,Q,7) ∈ {(1,0,1),(0,1,1),(1,1,1)}.
  - Undifferenced models (d = D = 0) get a constant.
  - Candidates whose optimizer did not converge are excluded.
- **Evaluation:** rolling 7-day-ahead forecasts.
  - At each weekly origin, the fitted parameters are applied to all data up to that day
    with `results.apply`, without re-fitting.
  - The next 7 days are then forecast using their known exogenous values.
- **Baseline:** seasonal naive, meaning the same weekday one week earlier.
- **Metrics:** MAE, RMSE and MAPE for both models, plus the % MAE improvement over the baseline.

## Outputs (`model_output/`)

| File | Contents |
|---|---|
| `sarimax_model.pkl` | fitted statsmodels results; load with `SARIMAXResults.load()` (git-ignored, ~30 MB) |
| `metrics.json` | order, AIC grid, exog list, coefficients + p-values, metrics, improvement |
| `test_predictions.csv` | date, actual, sarimax, baseline for the test period |
| `forecast_chart.png` | actual vs SARIMAX vs baseline over the test period |

## Results (run on 2026-10-27)

| Model | MAE | RMSE | MAPE |
|---|---|---|---|
| SARIMAX(2,1,1)x(0,1,1,7) + holiday, promo, weather | **928.9** | **1,338.2** | **6.25%** |
| SARIMAX(2,1,1)x(1,1,1,7) + holiday, promo only | 967.9 | 1,349.9 | 6.60% |
| Seasonal naive baseline | 1,384.8 | 1,887.5 | 9.34% |

The model with weather has 32.9% lower MAE than the baseline. The model without weather is 30.1% lower.

Weather coefficients (with weather; p < 0.05 marked *):

| Term | Coef | p-value |
|---|---|---|
| temperature_c | +407.5 per °C | 0.0000 * |
| humidity_pct | +46.0 per % | 0.0048 * |
| rainfall_mm | +1.6 per mm | 0.852 |
| weather_Drizzle (vs Cloudy) | −108.3 | 0.576 |
| weather_Rain (vs Cloudy) | −210.7 | 0.390 |
