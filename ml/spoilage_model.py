"""XGBoost spoilage-risk regression.

Training data: one row per (batch, day) over the life of every finished batch.
The target is the share of the stock still on hand that day that was later
written off as spoilage/wastage, as a percentage (0-100).

Features:
  fruit type        one-hot fruit name + the fruit's typical shelf life
  stock age         days since the batch was received, days left to expiry
  inventory turnover  recent daily sales / stock on hand, share of batch remaining
  sales trend       7-day vs 28-day average daily sales of the fruit
  weather           3-day average temperature, humidity and total rainfall

The fitted regressor scores every open batch for today and the next 6 days
(using OpenWeather's forecast) and writes the results to `spoilage_predictions`.

Until MIN_TRAIN_BATCHES finished batches exist (e.g. at the start of alpha
testing; the vendor's logbook recorded no spoilage), open batches are scored
with a shelf-life rule instead (see shelf_life_rule_predictions), and the run is
logged as a skipped XGBoost run so the rule's scores are never shown as ML output.

Usage: python ml/spoilage_model.py [--horizon 7]
"""
import argparse
import json
from datetime import datetime

import numpy as np
import pandas as pd
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
from xgboost import XGBRegressor

from common import (MODEL_DIR, WEATHER_COLS, connect, emit, fail, load_weather, local_today, query_df,
                    save_run, to_local_date, weather_for)

MIN_TRAIN_BATCHES = 8
SPOILAGE_TYPES = ("spoilage", "wastage")
NUMERIC_FEATURES = [
    "shelf_life_days", "stock_age_days", "days_to_expiry", "remaining_fraction",
    "turnover_rate", "sales_trend", "temperature", "humidity", "rainfall",
]
# Shelf-life rule used until enough batches exist to train XGBoost
Q10 = 2.0                    # fresh produce decay rate roughly doubles per +10 °C (postharvest Q10 ~2-3)
REFERENCE_TEMP_C = 27.0      # used only if there is no observed weather
DEFAULT_SHELF_LIFE_DAYS = 7  # used only if the batch has no expiry date and the fruit no batch history
RISK_LEVELS = [(70, "Critical"), (50, "High Risk"), (25, "Moderate"), (10, "Low Risk"), (0, "Fresh")]


def load_data(conn):
    batches = query_df(conn, """
        SELECT b.id AS batch_id, b.inventory_item_id AS item_id, i.name AS fruit, b.batch_code,
               b.quantity, GREATEST(b.initial_quantity, b.quantity) AS initial_quantity,
               b.received_date, b.expiry_date, b.status
        FROM inventory_batches b JOIN inventory_items i ON i.id = b.inventory_item_id
        WHERE b.received_date IS NOT NULL
    """)
    moves = query_df(conn, """
        SELECT si.inventory_batch_id AS batch_id, COALESCE(si.inventory_item_id, b.inventory_item_id) AS item_id,
               st.type, st.created_at, si.quantity
        FROM sales_items si
        JOIN sales_transactions st ON st.id = si.sale_transaction_id
        LEFT JOIN inventory_batches b ON b.id = si.inventory_batch_id
        WHERE st.status = 'completed' AND si.quantity > 0 AND st.type IN ('sale', 'spoilage', 'wastage')
    """)
    for col in ("received_date", "expiry_date"):
        batches[col] = pd.to_datetime(batches[col])
    if not moves.empty:
        moves["date"] = to_local_date(moves["created_at"])
        moves["kind"] = np.where(moves["type"].isin(SPOILAGE_TYPES), "spoiled", "sold")
    return batches, moves


def item_sales_stats(moves, start, end):
    """Per fruit, per day: average daily sales over the trailing 7 and 28 days (excluding that day)."""
    sold = moves[moves["kind"] == "sold"] if not moves.empty else moves
    idx = pd.date_range(start, end, freq="D")
    stats = {}
    for item_id, grp in sold.groupby("item_id") if not sold.empty else []:
        daily = grp.groupby("date")["quantity"].sum().reindex(idx, fill_value=0.0)
        prev = daily.shift(1, fill_value=0.0)
        stats[int(item_id)] = pd.DataFrame({
            "avg7": prev.rolling(7, min_periods=1).mean(),
            "avg28": prev.rolling(28, min_periods=1).mean(),
        })
    return stats


def weather_3day(weather, dates):
    """3-day trailing mean temperature/humidity and total rainfall ending on each date."""
    dates = pd.DatetimeIndex(dates)
    span = pd.date_range(dates.min() - pd.Timedelta(days=2), dates.max(), freq="D")
    w = weather_for(weather, span)
    rolled = pd.DataFrame({
        "temperature": w["temperature"].rolling(3, min_periods=1).mean(),
        "humidity": w["humidity"].rolling(3, min_periods=1).mean(),
        "rainfall": w["rainfall"].rolling(3, min_periods=1).sum(),
    })
    return rolled.reindex(dates)


def feature_row(batch, day, remaining, shelf_life, stats):
    age = (day - batch["received_date"]).days
    expiry = batch["expiry_date"]
    days_to_expiry = (expiry - day).days if pd.notna(expiry) else shelf_life - age
    s = stats.get(int(batch["item_id"]))
    avg7 = float(s.at[day, "avg7"]) if s is not None and day in s.index else 0.0
    avg28 = float(s.at[day, "avg28"]) if s is not None and day in s.index else 0.0
    return {
        "fruit": batch["fruit"],
        "shelf_life_days": shelf_life,
        "stock_age_days": age,
        "days_to_expiry": days_to_expiry,
        "remaining_fraction": remaining / batch["initial_quantity"] if batch["initial_quantity"] > 0 else 0,
        "turnover_rate": min(avg7 / remaining, 5.0) if remaining > 0 else 5.0,
        "sales_trend": avg7 / avg28 if avg28 > 0 else 1.0,
    }


def build_training_set(batches, moves, weather, today):
    finished = batches[(batches["quantity"] <= 0) | (batches["status"] != "available")
                       | (batches["expiry_date"] < today)]
    if finished.empty or moves.empty:
        return pd.DataFrame()

    start = batches["received_date"].min() - pd.Timedelta(days=28)
    stats = item_sales_stats(moves, start, today)
    shelf_lives = typical_shelf_life(batches)

    rows = []
    for _, b in finished.iterrows():
        bm = moves[moves["batch_id"] == b["batch_id"]]
        if bm.empty or b["initial_quantity"] <= 0:
            continue
        daily = bm.pivot_table(index="date", columns="kind", values="quantity", aggfunc="sum").fillna(0.0)
        for k in ("sold", "spoiled"):
            if k not in daily:
                daily[k] = 0.0
        end = min(daily.index.max(), today - pd.Timedelta(days=1))
        days = pd.date_range(b["received_date"], end, freq="D")
        daily = daily.reindex(days, fill_value=0.0)
        total_spoiled = daily["spoiled"].sum()
        used_before = (daily["sold"] + daily["spoiled"]).cumsum().shift(1, fill_value=0.0)
        spoiled_before = daily["spoiled"].cumsum().shift(1, fill_value=0.0)

        for day in days:
            remaining = b["initial_quantity"] - used_before[day]
            if remaining <= 0.01:
                break
            row = feature_row(b, day, remaining, shelf_lives.get(b["fruit"], 7), stats)
            row["batch_id"] = b["batch_id"]
            row["received_date"] = b["received_date"]
            row["date"] = day
            row["target"] = float(np.clip((total_spoiled - spoiled_before[day]) / remaining * 100, 0, 100))
            rows.append(row)

    df = pd.DataFrame(rows)
    if df.empty:
        return df
    w = weather_3day(weather, df["date"])
    df[WEATHER_COLS] = w.to_numpy()
    return df


def typical_shelf_life(batches):
    life = (batches["expiry_date"] - batches["received_date"]).dt.days
    return life.groupby(batches["fruit"]).median().fillna(7).to_dict()


def batch_shelf_life(batch, shelf_lives):
    """Days from receipt to expiry; else the fruit's typical batch life; else DEFAULT_SHELF_LIFE_DAYS."""
    life = ((batch["expiry_date"] - batch["received_date"]).days if pd.notna(batch["expiry_date"])
            else shelf_lives.get(batch["fruit"], DEFAULT_SHELF_LIFE_DAYS))
    return max(life, 1)


def temperature_reference(weather, today):
    """Past year's mean observed temperature: a day at this temperature ages fruit by exactly one day."""
    observed = weather[~weather["is_forecast"].astype(bool)] if not weather.empty else weather
    recent = observed[observed.index >= today - pd.Timedelta(days=365)]
    return float(recent["temperature"].astype(float).mean()) if len(recent) else REFERENCE_TEMP_C


def effective_age(weather, received, end, t_ref):
    """Temperature-adjusted days elapsed before each day starts (0 on the day received), up to `end`.
    Each day counts as Q10 ** ((T - t_ref) / 10) days, so hot days age fruit faster."""
    temps = weather_for(weather, pd.date_range(received, end, freq="D"))["temperature"].astype(float)
    return (Q10 ** ((temps - t_ref) / 10)).cumsum().shift(1, fill_value=0.0)


def encode(df, fruit_columns=None):
    dummies = pd.get_dummies(df["fruit"], prefix="fruit", dtype=float)
    if fruit_columns is not None:
        dummies = dummies.reindex(columns=fruit_columns, fill_value=0.0)
    return pd.concat([df[NUMERIC_FEATURES].astype(float), dummies], axis=1), list(dummies.columns)


def new_model():
    return XGBRegressor(
        n_estimators=400, max_depth=4, learning_rate=0.05, subsample=0.8, colsample_bytree=0.8,
        min_child_weight=3, reg_lambda=1.0, objective="reg:squarederror", random_state=42,
    )


def train(df):
    """Time-based split: batches received last (most recent 20%) form the test set."""
    batch_dates = df.groupby("batch_id")["received_date"].first().sort_values()
    cutoff = batch_dates.iloc[int(len(batch_dates) * 0.8)]
    train_mask = df["received_date"] < cutoff

    X, fruit_cols = encode(df)
    y = df["target"]
    model = new_model()
    model.fit(X[train_mask], y[train_mask])
    pred = np.clip(model.predict(X[~train_mask]), 0, 100)
    metrics = {
        "mae": round(float(mean_absolute_error(y[~train_mask], pred)), 3),
        "rmse": round(float(np.sqrt(mean_squared_error(y[~train_mask], pred))), 3),
        "r2": round(float(r2_score(y[~train_mask], pred)), 4) if (~train_mask).sum() > 1 else None,
        "train_rows": int(train_mask.sum()),
        "test_rows": int((~train_mask).sum()),
        "batches": int(len(batch_dates)),
    }

    final = new_model()
    final.fit(X, y)
    importance = dict(sorted(
        ((k, round(float(v), 4)) for k, v in zip(X.columns, final.feature_importances_)),
        key=lambda kv: kv[1], reverse=True,
    ))
    metrics["feature_importance"] = importance
    return final, fruit_cols, metrics


def risk_level(score):
    return next(label for threshold, label in RISK_LEVELS if score >= threshold)


def recommend(level, days_to_expiry, turnover):
    if days_to_expiry < 0:
        return "Expired: remove from shelf"
    if days_to_expiry == 0:
        return "Expires today: sell first, discount by afternoon"
    if level == "Critical":
        return "Sell today at a discount or pull from shelf"
    if level == "High Risk":
        return "Discount 15-20% and move to cool storage"
    if level == "Moderate":
        return "Display up front; sell within 2 days" if turnover >= 0.2 else "Apply 10% discount to speed up sales"
    if level == "Low Risk":
        return "Monitor temperature and humidity"
    return "Optimal condition"


def predict_open_batches(model, fruit_cols, batches, moves, weather, today, horizon):
    open_b = batches[(batches["quantity"] > 0) & (batches["status"] == "available")]
    if open_b.empty:
        return []

    dates = pd.date_range(today, periods=horizon, freq="D")
    stats = item_sales_stats(moves, batches["received_date"].min() - pd.Timedelta(days=28), today)
    w = weather_3day(weather, dates)
    shelf_lives = typical_shelf_life(batches)
    t_ref = temperature_reference(weather, today)

    rows, meta = [], []
    for _, b in open_b.iterrows():
        current = feature_row(b, today, b["quantity"], shelf_lives.get(b["fruit"], 7), stats)
        # Shown to the user only, not a model feature
        remaining_life = batch_shelf_life(b, shelf_lives) - effective_age(weather, b["received_date"], dates[-1], t_ref)
        for h, day in enumerate(dates):
            row = dict(current)
            row["stock_age_days"] = current["stock_age_days"] + h
            row["days_to_expiry"] = current["days_to_expiry"] - h
            row.update(w.loc[day, WEATHER_COLS].to_dict())
            rows.append(row)
            meta.append((b, h, day, row, round(float(remaining_life[day]), 1)))

    X, _ = encode(pd.DataFrame(rows), fruit_cols)
    scores = np.clip(model.predict(X), 0, 100)

    out = []
    for (b, h, day, row, remaining_life), score in zip(meta, scores):
        # Past expiry the stock is written off; training data has almost no such
        # rows, so this is a business rule rather than a model prediction.
        if row["days_to_expiry"] < 0:
            score = 100.0
        level = risk_level(score)
        out.append({
            "inventory_batch_id": int(b["batch_id"]), "inventory_item_id": int(b["item_id"]),
            "prediction_date": today.date(), "horizon_day": h, "risk_score": round(float(score), 2),
            "risk_level": level, "stock_age_days": row["stock_age_days"], "remaining_shelf_life_days": remaining_life,
            "turnover_rate": round(row["turnover_rate"], 4), "sales_trend": round(row["sales_trend"], 4),
            "temperature": round(float(row["temperature"]), 2), "humidity": round(float(row["humidity"]), 2),
            "rainfall": round(float(row["rainfall"]), 2),
            "recommendation": recommend(level, row["days_to_expiry"], row["turnover_rate"]),
        })
    return out


def shelf_life_rule_predictions(batches, moves, weather, today, horizon):
    """Score open batches without a trained model (too few finished batches yet).

    Each batch ages from its received date toward its shelf life (expiry date, else
    the fruit's typical batch life, else DEFAULT_SHELF_LIFE_DAYS). Hot days count as
    more than one day: decay rate x Q10 ** ((T - T_ref) / 10), with T_ref the past
    year's mean temperature, so an average day counts as exactly one day.
    Risk = 100 * (effective age / shelf life) ** 2, and 100 once past expiry.
    """
    open_b = batches[(batches["quantity"] > 0) & (batches["status"] == "available")]
    if open_b.empty:
        return []

    dates = pd.date_range(today, periods=horizon, freq="D")
    stats = item_sales_stats(moves, batches["received_date"].min() - pd.Timedelta(days=28), today)
    w3 = weather_3day(weather, dates)
    shelf_lives = typical_shelf_life(batches)
    t_ref = temperature_reference(weather, today)

    out = []
    for _, b in open_b.iterrows():
        shelf_life = batch_shelf_life(b, shelf_lives)
        age = effective_age(weather, b["received_date"], dates[-1], t_ref)
        current = feature_row(b, today, b["quantity"], shelf_life, stats)

        for h, day in enumerate(dates):
            days_to_expiry = current["days_to_expiry"] - h
            score = 100.0 if days_to_expiry < 0 else min(100.0, 100 * (age[day] / shelf_life) ** 2)
            level = risk_level(score)
            weather_day = w3.loc[day]
            out.append({
                "inventory_batch_id": int(b["batch_id"]), "inventory_item_id": int(b["item_id"]),
                "prediction_date": today.date(), "horizon_day": h, "risk_score": round(float(score), 2),
                "risk_level": level, "stock_age_days": current["stock_age_days"] + h,
                "remaining_shelf_life_days": round(float(shelf_life - age[day]), 1),
                "turnover_rate": round(current["turnover_rate"], 4), "sales_trend": round(current["sales_trend"], 4),
                "temperature": round(float(weather_day["temperature"]), 2),
                "humidity": round(float(weather_day["humidity"]), 2),
                "rainfall": round(float(weather_day["rainfall"]), 2),
                "recommendation": recommend(level, days_to_expiry, current["turnover_rate"]),
            })
    return out


def save_predictions(conn, preds, today):
    now = datetime.now()
    cols = ["inventory_batch_id", "inventory_item_id", "prediction_date", "horizon_day", "risk_score", "risk_level",
            "stock_age_days", "remaining_shelf_life_days", "turnover_rate", "sales_trend", "temperature", "humidity", "rainfall", "recommendation"]
    cur = conn.cursor()
    cur.execute("DELETE FROM spoilage_predictions WHERE prediction_date = %s", (today.date(),))
    cur.executemany(
        f"INSERT INTO spoilage_predictions ({', '.join(cols)}, created_at, updated_at) "
        f"VALUES ({', '.join(['%s'] * (len(cols) + 2))})",
        [tuple(p[c] for c in cols) + (now, now) for p in preds],
    )

    # Keep the inventory module's risk/freshness fields in step with the model
    worst = {}
    for p in preds:
        if p["horizon_day"] == 0:
            worst[p["inventory_item_id"]] = max(worst.get(p["inventory_item_id"], 0), p["risk_score"])
    for item_id, score in worst.items():
        label = "High" if score >= 50 else "Medium" if score >= 25 else "Low"
        cur.execute("UPDATE inventory_items SET spoilage_risk = %s, freshness_score = %s WHERE id = %s",
                    (label, int(round(100 - score)), item_id))
    conn.commit()
    cur.close()


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--horizon", type=int, default=7)
    args = parser.parse_args()

    conn = connect()
    today = local_today()
    batches, moves = load_data(conn)
    weather = load_weather(conn)

    df = build_training_set(batches, moves, weather, today)
    n_batches = df["batch_id"].nunique() if not df.empty else 0
    if n_batches < MIN_TRAIN_BATCHES:
        preds = shelf_life_rule_predictions(batches, moves, weather, today, args.horizon)
        save_predictions(conn, preds, today)
        today_preds = [p for p in preds if p["horizon_day"] == 0]
        msg = (f"XGBoost needs at least {MIN_TRAIN_BATCHES} finished batches with sales/spoilage history "
               f"(have {n_batches}); risk scored with the shelf-life rule (Q10 = {Q10}) until then.")
        save_run(conn, "XGBoost", "skipped", training_rows=len(df), message=msg)
        conn.close()
        emit({"success": True, "model": "ShelfLifeRule", "message": msg, "finished_batches": n_batches,
              "batches_scored": len(today_preds),
              "avg_risk": round(float(np.mean([p["risk_score"] for p in today_preds])), 2) if today_preds else 0})
        return

    model, fruit_cols, metrics = train(df)
    MODEL_DIR.mkdir(parents=True, exist_ok=True)
    model.save_model(MODEL_DIR / "spoilage_xgb.json")
    (MODEL_DIR / "spoilage_xgb_meta.json").write_text(json.dumps({
        "trained_at": datetime.now().isoformat(), "numeric_features": NUMERIC_FEATURES,
        "fruit_columns": fruit_cols, "metrics": metrics,
    }, indent=2))

    preds = predict_open_batches(model, fruit_cols, batches, moves, weather, today, args.horizon)
    save_predictions(conn, preds, today)

    today_preds = [p for p in preds if p["horizon_day"] == 0]
    summary = {
        **metrics,
        "batches_scored": len(today_preds),
        "avg_risk": round(float(np.mean([p["risk_score"] for p in today_preds])), 2) if today_preds else 0,
    }
    save_run(conn, "XGBoost", "success", training_rows=len(df), metrics=summary)
    conn.close()
    emit({"success": True, "model": "XGBoost", **summary})


if __name__ == "__main__":
    try:
        main()
    except SystemExit:
        raise
    except Exception as exc:
        fail(f"{type(exc).__name__}: {exc}")
