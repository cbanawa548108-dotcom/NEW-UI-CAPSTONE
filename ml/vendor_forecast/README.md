# FreshTrack – Davao vendor per-fruit sales forecast (Python)

This pipeline applies the SARIMAX method from `ml/sales_forecast/` to a Davao City
fruit vendor's own sales history. It fits one model per fruit, with weather as
exogenous variables. It runs separately from the Laravel app.

## Data

**Sales:** `vendor_sales.xlsx`, sheet "Daily Sales". This is the vendor's handwritten
daily sales logbook, typed into Excel.
- 10 fruits: Banana, Durian, Jackfruit, Lanzones, Mango, Mangosteen, Papaya,
  Pomelo, Rambutan, Watermelon
- Every day from 2020-01-01 to 2024-12-31: 1,827 days, 18,270 rows
- Modelling target: `Units Sold`

**Spoilage is not used.** The vendor never wrote spoilage in the logbook. The
spreadsheet's "Available" and "Net Profit" columns were filled in when the logbook
was typed up. They are not measurements, and no spoilage figures are derived from
them. Spoilage data will come from batch logging in the app during alpha testing.

**Weather:** Open-Meteo Historical Weather API for Davao City
(lat 7.07, lon 125.61, Asia/Manila). The fields and WMO condition mapping are the
same as in `ml/sales_forecast/add_weather.py`. The vendor's logbook has no weather;
it is attached by date.

**Holidays:** Philippine national and special days from the `holidays` package,
plus Araw ng Dabaw (March 16).

## Setup and run

The pipeline uses the same venv as `ml/sales_forecast/`. From the project root:

```bash
ml/.venv/Scripts/python -m pip install -r ml/vendor_forecast/requirements.txt
```

Put `vendor_sales.xlsx` in `ml/vendor_forecast/data/raw/` (git-ignored). Then, from
`ml/vendor_forecast/`:

```bash
../.venv/Scripts/python prepare_vendor.py        # -> data/processed/vendor_daily.csv
../.venv/Scripts/python train_vendor_sarimax.py  # -> model_output/sales/  (~11 min, 4 cores)
```

## Method

The method is the same as `ml/sales_forecast/train_sarimax.py`. Its fitting, order
selection, rolling evaluation and metrics functions are imported, not copied.

- **Target:** daily `units_sold` per fruit
- **Exogenous variables:**
  - `is_holiday`
  - `temperature_c`, `humidity_pct`, `rainfall_mm`
  - `weather_condition` dummies
- **Weather reference level:** Davao had only 2 "Clear" days in 5 years. Conditions
  with fewer than 30 training days are merged into the next condition (Clear →
  Cloudy), so **Cloudy** is the reference level.
- **Split:** by date.
  - Train: 2020-01-01 to 2023-12-31 (1,461 days)
  - Test: 2024-01-01 to 2024-12-31 (366 days)
- **Order selection:** lowest AIC on the training data, from the same 12-candidate
  grid. Candidates that don't converge are excluded.
- **Evaluation:** rolling 7-day-ahead forecasts from weekly origins, using the
  fitted parameters without re-fitting.
- **Baseline:** seasonal naive, meaning the same weekday one week earlier.

## Outputs (`model_output/sales/`)

| File | Contents |
|---|---|
| `summary.csv` | one row per fruit: model, MAE/RMSE/MAPE vs baseline, improvement, significant weather terms |
| `metrics.json` | per fruit: order, AIC grid, exog, coefficients + p-values, metrics |
| `test_predictions.csv` | date, product, actual, sarimax, baseline for 2024 (git-ignored) |
| `forecast_chart.png` | actual vs SARIMAX vs baseline, last 12 weeks of 2024, per fruit (git-ignored) |
| `models/<fruit>.pkl` | fitted results without data arrays (git-ignored) |

The repository is public, so files containing the vendor's daily sales (raw
logbook, processed CSV, test predictions and the per-fruit chart) are kept out
of git. They are regenerated locally by the two scripts above. Only aggregate
results (`summary.csv`, `metrics.json`) are committed.

## Results (run on 2026-10-27)

| Fruit | Model | SARIMAX MAE | SARIMAX MAPE | Baseline MAPE | MAE improvement |
|---|---|---|---|---|---|
| Banana | (1,0,1)x(1,1,1,7) | 23.92 | 14.00% | 19.33% | 28.0% |
| Durian | (0,1,1)x(1,1,1,7) | 10.02 | 16.51% | 20.99% | 21.8% |
| Jackfruit | (1,0,1)x(0,1,1,7) | 8.36 | 14.01% | 19.12% | 27.6% |
| Lanzones | (1,0,1)x(0,1,1,7) | 18.51 | 16.07% | 21.11% | 24.5% |
| Mango | (1,0,1)x(0,1,1,7) | 17.61 | 13.24% | 18.68% | 29.3% |
| Mangosteen | (1,0,1)x(0,1,1,7) | 12.41 | 16.44% | 22.32% | 26.3% |
| Papaya | (1,0,1)x(0,1,1,7) | 15.62 | 14.92% | 20.37% | 27.3% |
| Pomelo | (1,0,1)x(0,1,1,7) | 13.24 | 15.31% | 19.59% | 22.1% |
| Rambutan | (1,0,1)x(0,1,1,7) | 16.74 | 14.26% | 19.42% | 28.1% |
| Watermelon | (1,0,1)x(0,1,1,7) | 10.05 | 13.58% | 17.51% | 23.8% |
| **Mean** | | | **14.83%** | **19.84%** | **25.9%** |

SARIMAX beats the seasonal naive baseline for **all 10 fruits**.

**Weather and holiday effects:**
- Of 50 weather coefficients (5 per fruit), only 2 are significant at p < 0.05:
  Papaya's Drizzle and Rain dummies, each about +4 units/day compared with Cloudy.
  With 50 tests at 5%, about 2.5 would come out significant by chance, so the vendor
  data shows **no reliable weather effect on daily sales**.
- `is_holiday` is not significant for any fruit.

This contrasts with the Guayaquil supermarket data, where temperature and humidity
were significant. Report both results as they are.
