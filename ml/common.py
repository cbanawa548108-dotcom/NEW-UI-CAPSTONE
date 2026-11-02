"""Shared helpers for the FreshTrack ML scripts: DB access, dates and weather features.

Connection settings come from environment variables (Laravel passes them when it
runs the scripts) and fall back to the project's .env file when run by hand.
"""
import json
import os
import sys
from datetime import date, datetime, timedelta
from pathlib import Path

import holidays
import mysql.connector
import numpy as np
import pandas as pd

PROJECT_ROOT = Path(__file__).resolve().parent.parent
MODEL_DIR = PROJECT_ROOT / "storage" / "app" / "ml"

WEATHER_COLS = ["temperature", "humidity", "rainfall"]
# Weather conditions from mildest to most severe; OpenWeather's other "main" values map onto these
CONDITION_ORDER = ["Clear", "Clouds", "Drizzle", "Rain", "Thunderstorm"]
CONDITION_ALIASES = {"Mist": "Clouds", "Fog": "Clouds", "Haze": "Clouds", "Smoke": "Clouds", "Dust": "Clouds",
                     "Sand": "Clouds", "Ash": "Clouds", "Snow": "Rain", "Squall": "Thunderstorm",
                     "Tornado": "Thunderstorm"}
LOCAL_HOLIDAYS = [(3, 16)]  # Araw ng Dabaw, on top of the national holidays


def _load_dotenv():
    env_file = PROJECT_ROOT / ".env"
    if not env_file.exists():
        return
    for line in env_file.read_text(encoding="utf-8").splitlines():
        line = line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, value = line.split("=", 1)
        os.environ.setdefault(key.strip(), value.strip().strip('"').strip("'"))


_load_dotenv()

APP_TZ = os.environ.get("APP_TIMEZONE", "UTC")
LOCAL_TZ = os.environ.get("OPENWEATHER_TIMEZONE", "Asia/Manila")


def connect():
    return mysql.connector.connect(
        host=os.environ.get("DB_HOST", "127.0.0.1"),
        port=int(os.environ.get("DB_PORT", 3306)),
        database=os.environ.get("DB_DATABASE", "capstone_db"),
        user=os.environ.get("DB_USERNAME", "root"),
        password=os.environ.get("DB_PASSWORD", ""),
    )


def query_df(conn, sql, params=None):
    cur = conn.cursor()
    cur.execute(sql, params or ())
    cols = [c[0] for c in cur.description]
    rows = cur.fetchall()
    cur.close()
    df = pd.DataFrame(rows, columns=cols)
    # DECIMAL columns come back as Decimal objects
    for col in df.columns:
        if len(df) and df[col].map(lambda v: v.__class__.__name__ == "Decimal").any():
            df[col] = df[col].astype(float)
    return df


def local_today():
    return pd.Timestamp.now(tz=LOCAL_TZ).normalize().tz_localize(None)


def to_local_date(series):
    """Convert DB timestamps (stored in the app timezone) to local calendar dates."""
    ts = pd.to_datetime(series)
    return ts.dt.tz_localize(APP_TZ).dt.tz_convert(LOCAL_TZ).dt.tz_localize(None).dt.normalize()


def load_weather(conn):
    df = query_df(conn, "SELECT date, temperature, humidity, rainfall, `condition`, is_forecast FROM weather_data ORDER BY date")
    if df.empty:
        return pd.DataFrame(columns=WEATHER_COLS + ["condition", "is_forecast"], index=pd.DatetimeIndex([], name="date"))
    df["date"] = pd.to_datetime(df["date"])
    return df.set_index("date")


def weather_for(weather, dates):
    """Daily weather for `dates`; gaps are interpolated, then filled with
    same-month averages, then the overall average."""
    idx = pd.DatetimeIndex(dates)
    if weather.empty:
        raise RuntimeError("No weather data. Run `php artisan weather:sync` and `php artisan weather:backfill` first.")

    full_idx = idx.union(weather.index)
    out = weather[WEATHER_COLS].reindex(full_idx).astype(float)
    out = out.interpolate(method="time", limit=3, limit_area="inside")

    monthly = weather[WEATHER_COLS].groupby(weather.index.month).mean()
    for col in WEATHER_COLS:
        missing = out[col].isna()
        if missing.any():
            out.loc[missing, col] = out.index[missing].month.map(monthly[col]).to_numpy(dtype=float)
        out[col] = out[col].fillna(weather[col].mean())

    return out.reindex(idx)


def conditions_for(weather, dates):
    """Daily weather condition for `dates`, one of CONDITION_ORDER; gaps get the most common condition."""
    idx = pd.DatetimeIndex(dates)
    cond = weather["condition"].map(lambda c: CONDITION_ALIASES.get(c, c))
    cond = cond.where(cond.isin(CONDITION_ORDER))
    fallback = cond.mode().iloc[0] if cond.notna().any() else "Clouds"
    return cond.reindex(idx).fillna(fallback)


def holiday_flags(dates):
    """1.0 on Philippine national holidays and Araw ng Dabaw, else 0.0."""
    idx = pd.DatetimeIndex(dates)
    years = sorted(set(idx.year))
    days = set(pd.to_datetime(list(holidays.PH(years=years).keys())))
    days |= {pd.Timestamp(y, m, d) for y in years for m, d in LOCAL_HOLIDAYS}
    return pd.Series(idx.isin(list(days)).astype(float), index=idx)


def save_run(conn, model, status, training_rows=0, metrics=None, message=None):
    cur = conn.cursor()
    now = datetime.now()
    cur.execute(
        "INSERT INTO ml_model_runs (model, status, training_rows, metrics, message, created_at, updated_at) "
        "VALUES (%s, %s, %s, %s, %s, %s, %s)",
        (model, status, int(training_rows), json.dumps(metrics) if metrics is not None else None, message, now, now),
    )
    conn.commit()
    cur.close()


def emit(payload):
    """Print the result as JSON for Laravel to read."""
    print(json.dumps(payload, default=_json_default))


def fail(message):
    emit({"success": False, "message": message})
    sys.exit(1)


def _json_default(value):
    if isinstance(value, (np.integer,)):
        return int(value)
    if isinstance(value, (np.floating,)):
        return float(value)
    if isinstance(value, (pd.Timestamp, date, datetime)):
        return value.isoformat()
    raise TypeError(f"Not JSON serializable: {type(value)}")
