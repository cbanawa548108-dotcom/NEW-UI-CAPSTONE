"""Add daily Guayaquil weather to the processed sales CSV.

Source: Open-Meteo Historical Weather API (free, no API key)
  https://archive-api.open-meteo.com/v1/archive

  temperature_c      daily temperature_2m_mean (deg C)
  rainfall_mm        daily precipitation_sum (mm)
  humidity_pct       mean of hourly relative_humidity_2m (%)
  weather_condition  daily WMO weather_code grouped into
                     Clear / Cloudy / Drizzle / Rain / Thunderstorm

Usage: python add_weather.py   (run prepare_sales.py first)
"""
from pathlib import Path

import pandas as pd
import requests

BASE_DIR = Path(__file__).resolve().parent
SALES_CSV = BASE_DIR / "data" / "processed" / "guayaquil_produce_daily.csv"

API_URL = "https://archive-api.open-meteo.com/v1/archive"
LATITUDE = -2.19
LONGITUDE = -79.89
TIMEZONE = "America/Guayaquil"
WEATHER_COLUMNS = ["temperature_c", "humidity_pct", "rainfall_mm", "weather_condition"]

# WMO weather interpretation codes -> condition (inclusive ranges)
WMO_CONDITIONS = [
    (0, 1, "Clear"),
    (2, 48, "Cloudy"),
    (51, 57, "Drizzle"),
    (61, 82, "Rain"),
    (95, 99, "Thunderstorm"),
]


def wmo_to_condition(code):
    if pd.isna(code):
        return None
    for low, high, name in WMO_CONDITIONS:
        if low <= code <= high:
            return name
    return None


def fetch_weather(start, end, latitude=LATITUDE, longitude=LONGITUDE, timezone=TIMEZONE):
    params = {
        "latitude": latitude,
        "longitude": longitude,
        "start_date": start,
        "end_date": end,
        "timezone": timezone,
        "daily": "temperature_2m_mean,precipitation_sum,weather_code",
        "hourly": "relative_humidity_2m",
    }
    resp = requests.get(API_URL, params=params, timeout=60)
    resp.raise_for_status()
    data = resp.json()

    daily = pd.DataFrame({
        "date": pd.to_datetime(data["daily"]["time"]),
        "temperature_c": data["daily"]["temperature_2m_mean"],
        "rainfall_mm": data["daily"]["precipitation_sum"],
        "weather_code": data["daily"]["weather_code"],
    })
    daily["weather_condition"] = daily["weather_code"].map(wmo_to_condition)

    hourly = pd.DataFrame({
        "time": pd.to_datetime(data["hourly"]["time"]),
        "humidity": data["hourly"]["relative_humidity_2m"],
    })
    humidity = hourly.groupby(hourly["time"].dt.normalize())["humidity"].mean()
    daily["humidity_pct"] = daily["date"].map(humidity).round(1)

    return daily[["date"] + WEATHER_COLUMNS + ["weather_code"]]


def main():
    sales = pd.read_csv(SALES_CSV, parse_dates=["date"])
    start = sales["date"].min().strftime("%Y-%m-%d")
    end = sales["date"].max().strftime("%Y-%m-%d")
    print(f"Fetching Open-Meteo weather for {start} to {end} "
          f"(lat {LATITUDE}, lon {LONGITUDE}, {TIMEZONE})")

    weather = fetch_weather(start, end)

    unmapped = weather.loc[weather["weather_condition"].isna() & weather["weather_code"].notna(), "weather_code"]
    if len(unmapped):
        print(f"Warning: {len(unmapped)} days with unmapped WMO codes: {sorted(unmapped.unique())}")

    merged = sales.drop(columns=WEATHER_COLUMNS, errors="ignore").merge(
        weather.drop(columns="weather_code"), on="date", how="left")
    merged.to_csv(SALES_CSV, index=False, date_format="%Y-%m-%d")

    missing = merged[WEATHER_COLUMNS].isna().any(axis=1)
    print(f"Rows: {len(merged)}, days with missing weather: {int(missing.sum())}")
    for col in WEATHER_COLUMNS:
        n = int(merged[col].isna().sum())
        if n:
            print(f"  {col}: {n} missing")
    print("\nWeather summary:")
    print(merged[["temperature_c", "humidity_pct", "rainfall_mm"]].describe().round(2).to_string())
    print("\nWeather condition counts:")
    print(merged["weather_condition"].value_counts(dropna=False).to_string())
    print(f"\nSaved {SALES_CSV.relative_to(BASE_DIR)}")


if __name__ == "__main__":
    main()
