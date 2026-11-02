"""Build the daily PRODUCE sales series for Guayaquil from the Kaggle
"Store Sales - Time Series Forecasting" (Corporacion Favorita) dataset.

Inputs  (data/raw/):       train.csv, stores.csv, holidays_events.csv
Output  (data/processed/): guayaquil_produce_daily.csv

Only 2015-06-01 .. 2017-08-15 is kept: before June 2015 PRODUCE sales are
mostly recorded as zero (the category was not being tracked), so those rows
are missing data, not real demand.

Days with no sales (stores closed on Jan 1 and Dec 25) are left blank (NaN)
and flagged is_closed = 1, never filled with 0.

Usage: python prepare_sales.py
"""
from pathlib import Path

import numpy as np
import pandas as pd

BASE_DIR = Path(__file__).resolve().parent
RAW_DIR = BASE_DIR / "data" / "raw"
PROCESSED_DIR = BASE_DIR / "data" / "processed"
OUTPUT_CSV = PROCESSED_DIR / "guayaquil_produce_daily.csv"

CITY = "Guayaquil"
FAMILY = "PRODUCE"
START_DATE = "2015-06-01"
END_DATE = "2017-08-15"
LOCAL_HOLIDAY_NAMES = ("Guayaquil", "Guayas")
WEATHER_COLUMNS = ["temperature_c", "humidity_pct", "rainfall_mm", "weather_condition"]


def load_stores():
    stores = pd.read_csv(RAW_DIR / "stores.csv")
    store_ids = sorted(stores.loc[stores["city"] == CITY, "store_nbr"].tolist())
    print(f"{CITY} stores ({len(store_ids)}): {store_ids}")
    return store_ids


def load_daily_sales(store_ids):
    train = pd.read_csv(
        RAW_DIR / "train.csv",
        usecols=["date", "store_nbr", "family", "sales", "onpromotion"],
        parse_dates=["date"],
    )
    produce = train[(train["family"] == FAMILY) & (train["store_nbr"].isin(store_ids))]
    produce = produce[(produce["date"] >= START_DATE) & (produce["date"] <= END_DATE)]
    return produce.groupby("date")[["sales", "onpromotion"]].sum()


def load_holiday_dates():
    hol = pd.read_csv(RAW_DIR / "holidays_events.csv", parse_dates=["date"])
    # transferred is read as bool by pandas; compare as text in case it isn't
    not_transferred = hol["transferred"].astype(str).str.lower() == "false"
    relevant = (hol["locale"] == "National") | (hol["locale_name"].isin(LOCAL_HOLIDAY_NAMES))
    keep = not_transferred & (hol["type"] != "Work Day") & relevant
    return set(hol.loc[keep, "date"])


def main():
    store_ids = load_stores()
    daily = load_daily_sales(store_ids)

    # Every calendar day in the window; days missing from train.csv become NaN
    calendar = pd.date_range(START_DATE, END_DATE, freq="D", name="date")
    daily = daily.reindex(calendar)

    closed = daily["sales"].isna() | (daily["sales"] <= 0)
    daily.loc[closed, ["sales", "onpromotion"]] = np.nan
    daily["is_closed"] = closed.astype(int)

    holidays = load_holiday_dates()
    daily["is_holiday"] = daily.index.isin(holidays).astype(int)
    daily["day_of_week"] = daily.index.day_of_week
    daily["is_weekend"] = (daily["day_of_week"] >= 5).astype(int)
    daily["month"] = daily.index.month
    for col in WEATHER_COLUMNS:
        daily[col] = np.nan

    PROCESSED_DIR.mkdir(parents=True, exist_ok=True)
    daily.reset_index().to_csv(OUTPUT_CSV, index=False, date_format="%Y-%m-%d")

    closed_days = daily.index[daily["is_closed"] == 1].strftime("%Y-%m-%d").tolist()
    print(f"Date range: {START_DATE} to {END_DATE}")
    print(f"Rows: {len(daily)}")
    print(f"Closed days ({len(closed_days)}): {closed_days}")
    print(f"Holiday days: {int(daily['is_holiday'].sum())}")
    print(f"Daily sales: mean {daily['sales'].mean():,.1f}, "
          f"min {daily['sales'].min():,.1f}, max {daily['sales'].max():,.1f}")
    print(f"Saved {OUTPUT_CSV.relative_to(BASE_DIR)}")


if __name__ == "__main__":
    main()
