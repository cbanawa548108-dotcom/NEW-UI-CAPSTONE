"""Prepare the Davao fruit vendor's daily sales records for modelling.

Input  (data/raw/):       vendor_sales.xlsx   (vendor's handwritten daily records,
                                               digitized; sheet "Daily Sales")
Output (data/processed/): vendor_daily.csv    (one row per date x fruit)

The vendor's logbook records sales only. Spoilage was never written down, so
"Available" and "Net Profit" in the spreadsheet were filled in during
digitization and are not used as measurements; no spoilage figures are
derived from them.

This script adds:
  - Philippine holidays (national + Araw ng Dabaw, Mar 16)
  - day_of_week (0 = Monday), is_weekend, month
  - daily Davao City weather from the Open-Meteo Historical API (same source and
    WMO condition mapping as ml/sales_forecast/add_weather.py)

Days with no units sold are left blank (NaN) and flagged is_closed = 1.

Usage: python prepare_vendor.py
"""
import importlib.util
from pathlib import Path

import holidays
import numpy as np
import pandas as pd

BASE_DIR = Path(__file__).resolve().parent
RAW_XLSX = BASE_DIR / "data" / "raw" / "vendor_sales.xlsx"
OUTPUT_CSV = BASE_DIR / "data" / "processed" / "vendor_daily.csv"

LATITUDE = 7.07     # Davao City
LONGITUDE = 125.61
TIMEZONE = "Asia/Manila"
LOCAL_HOLIDAYS = {(3, 16): "Araw ng Dabaw"}

COLUMNS = {
    "Date": "date",
    "Product": "product",
    "Unit Price (₱)": "unit_price",
    "Unit Cost (₱)": "unit_cost",
    "Stock In": "stock_in",
    "Available": "available",
    "Units Sold": "units_sold",
    "Revenue (₱)": "revenue",
    "COGS (₱)": "cogs",
    "Net Profit (₱)": "net_profit",
}


def load_module(name, path):
    spec = importlib.util.spec_from_file_location(name, path)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


weather_source = load_module("guayaquil_add_weather", BASE_DIR.parent / "sales_forecast" / "add_weather.py")


def load_sales():
    df = pd.read_excel(RAW_XLSX, sheet_name="Daily Sales").rename(columns=COLUMNS)
    df["date"] = pd.to_datetime(df["date"]).dt.normalize()
    dupes = df.duplicated(["date", "product"]).sum()
    if dupes:
        raise ValueError(f"{dupes} duplicate date/product rows in {RAW_XLSX.name}")
    return df


def holiday_dates(years):
    ph = holidays.PH(years=years)
    dates = set(pd.to_datetime(list(ph.keys())))
    dates |= {pd.Timestamp(year, m, d) for year in years for (m, d) in LOCAL_HOLIDAYS}
    return dates


def main():
    df = load_sales()
    start, end = df["date"].min(), df["date"].max()
    products = sorted(df["product"].unique())
    print(f"Products ({len(products)}): {products}")
    print(f"Date range: {start.date()} to {end.date()}")

    # Every calendar day for every fruit, so gaps in the logbook show up as NaN
    calendar = pd.MultiIndex.from_product(
        [pd.date_range(start, end, freq="D"), products], names=["date", "product"])
    df = df.set_index(["date", "product"]).reindex(calendar).reset_index()
    missing_rows = int(df["units_sold"].isna().sum())

    closed = df["units_sold"].isna() | (df["units_sold"] <= 0)
    df.loc[closed, "units_sold"] = np.nan
    df["is_closed"] = closed.astype(int)

    hol = holiday_dates(range(start.year, end.year + 1))
    df["is_holiday"] = df["date"].isin(hol).astype(int)
    df["day_of_week"] = df["date"].dt.day_of_week
    df["is_weekend"] = (df["day_of_week"] >= 5).astype(int)
    df["month"] = df["date"].dt.month

    print(f"Fetching Open-Meteo weather for Davao City (lat {LATITUDE}, lon {LONGITUDE}, {TIMEZONE})")
    weather = weather_source.fetch_weather(start.strftime("%Y-%m-%d"), end.strftime("%Y-%m-%d"),
                                           latitude=LATITUDE, longitude=LONGITUDE, timezone=TIMEZONE)
    df = df.merge(weather.drop(columns="weather_code"), on="date", how="left")

    OUTPUT_CSV.parent.mkdir(parents=True, exist_ok=True)
    df.to_csv(OUTPUT_CSV, index=False, date_format="%Y-%m-%d")

    days = df["date"].nunique()
    weather_days = df.drop_duplicates("date")
    print(f"Rows: {len(df)} ({days} days x {len(products)} fruits)")
    print(f"Missing logbook rows: {missing_rows}, closed/no-sale fruit-days: {int(df['is_closed'].sum())}")
    print(f"Holiday days: {int(weather_days['is_holiday'].sum())}")
    print(f"Days with missing weather: {int(weather_days[weather_source.WEATHER_COLUMNS].isna().any(axis=1).sum())}")
    print("\nWeather summary (Davao City):")
    print(weather_days[["temperature_c", "humidity_pct", "rainfall_mm"]].describe().round(2).to_string())
    print("\nWeather condition counts (days):")
    print(weather_days["weather_condition"].value_counts(dropna=False).to_string())
    print("\nPer fruit: mean units sold/day")
    print(df.groupby("product")["units_sold"].mean().round(1).to_string())
    print(f"\nSaved {OUTPUT_CSV.relative_to(BASE_DIR)}")


if __name__ == "__main__":
    main()
