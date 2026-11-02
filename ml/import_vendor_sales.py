"""Import the vendor's digitized sales logbook into the app, replacing the demo data.

Reads the "Daily Sales" sheet of vendor_sales.xlsx (Date, Product, Unit Price,
Units Sold in kg) and writes one completed sale per logbook day, with one
sales_items row per fruit. Only the sales columns are imported: the logbook has
no spoilage, so "Stock In", "Available" and "Net Profit" (filled in during
digitization) are ignored and no batches are created.

Before importing, everything MLDemoDataSeeder created is removed: transactions
noted "Demo ...", "Demo Supplier" batches (their spoilage predictions cascade),
demo-only fruits, and the forecasts / model runs computed from demo data. The
script refuses to run if any other sales exist, so real records are never
touched. Back up the database first.

Usage: python ml/import_vendor_sales.py <path/to/vendor_sales.xlsx> [--dry-run]
"""
import argparse
from datetime import datetime
from pathlib import Path

import pandas as pd

from common import APP_TZ, LOCAL_TZ, connect, emit, fail, query_df

SALE_HOUR = 12  # logbook has daily totals only; record each day's sale at local noon
CODE_PREFIX = "LOG"
CHUNK = 500  # rows per INSERT; XAMPP's default max_allowed_packet is 1 MB
NOTE = "Vendor logbook import"
COLUMNS = {"Date": "date", "Product": "product", "Unit Price (₱)": "price", "Units Sold": "kg"}


def load_logbook(path):
    df = pd.read_excel(path, sheet_name="Daily Sales").rename(columns=COLUMNS)[list(COLUMNS.values())]
    df["date"] = pd.to_datetime(df["date"]).dt.normalize()
    df = df.dropna(subset=["kg"])
    df = df[df["kg"] > 0]
    if df.duplicated(["date", "product"]).any():
        raise ValueError("duplicate date/product rows in the logbook")
    return df


def demo_counts(conn):
    counts = query_df(conn, """
        SELECT SUM(notes LIKE 'Demo %%') AS demo, SUM(notes IS NULL OR notes NOT LIKE 'Demo %%') AS other
        FROM sales_transactions
    """).iloc[0]
    return int(counts["demo"] or 0), int(counts["other"] or 0)


def delete_demo(cur, keep_fruits):
    cur.execute("DELETE FROM sales_transactions WHERE notes LIKE 'Demo %%'")  # sales_items cascade
    tx = cur.rowcount
    cur.execute("DELETE FROM inventory_batches WHERE supplier = 'Demo Supplier'")  # spoilage_predictions cascade
    batches = cur.rowcount
    placeholders = ", ".join(["%s"] * len(keep_fruits))
    cur.execute(f"""
        DELETE FROM inventory_items
        WHERE name NOT IN ({placeholders})
          AND id NOT IN (SELECT inventory_item_id FROM inventory_batches)
          AND id NOT IN (SELECT inventory_item_id FROM sales_items WHERE inventory_item_id IS NOT NULL)
    """, tuple(keep_fruits))
    items = cur.rowcount
    # Outputs computed from the demo data
    cur.execute("DELETE FROM ai_forecasts")
    cur.execute("DELETE FROM ml_model_runs")
    return tx, batches, items


def upsert_items(cur, logbook, now):
    """Reuse existing fruit rows by name; create the rest. Returns {name: id}."""
    ids = {}
    for name, grp in logbook.groupby("product"):
        price = float(grp.sort_values("date")["price"].iloc[-1])
        reorder = round(float(grp["kg"].mean()) * 2)  # ~2 days of average sales
        cur.execute("SELECT id FROM inventory_items WHERE name = %s", (name,))
        row = cur.fetchone()
        if row:
            ids[name] = row[0]
            cur.execute(
                "UPDATE inventory_items SET category = 'Fruit', unit = 'kg', price_per_unit = %s, stock_quantity = 0, "
                "reorder_level = %s, status = 'active', updated_at = %s WHERE id = %s",
                (price, reorder, now, row[0]))
        else:
            cur.execute(
                "INSERT INTO inventory_items (name, category, unit, price_per_unit, stock_quantity, reorder_level, "
                "freshness_score, spoilage_risk, status, created_at, updated_at) "
                "VALUES (%s, 'Fruit', 'kg', %s, 0, %s, 100, 'Low', 'active', %s, %s)",
                (name, price, reorder, now, now))
            ids[name] = cur.lastrowid
    return ids


def executemany_chunked(cur, sql, rows):
    for i in range(0, len(rows), CHUNK):
        cur.executemany(sql, rows[i:i + CHUNK])


def insert_sales(cur, logbook, item_ids, user_id):
    tx_rows, item_rows = [], []
    for date, day in logbook.groupby("date"):
        # local noon, stored in the app timezone like every other timestamp
        at = (pd.Timestamp(date).replace(hour=SALE_HOUR).tz_localize(LOCAL_TZ)
              .tz_convert(APP_TZ).tz_localize(None).to_pydatetime())
        total = round(float((day["price"] * day["kg"]).sum()), 2)
        tx_rows.append((user_id, f"{CODE_PREFIX}-{date:%Y%m%d}", "sale", total, 0, 0, total, "cash", "completed",
                        NOTE, at, at))
        for r in day.itertuples():
            item_rows.append((f"{CODE_PREFIX}-{date:%Y%m%d}", item_ids[r.product], float(r.kg), float(r.price),
                              round(float(r.kg * r.price), 2), at, at))

    executemany_chunked(
        cur,
        "INSERT INTO sales_transactions (user_id, transaction_code, type, subtotal, tax_amount, discount_amount, "
        "total_amount, payment_method, status, notes, created_at, updated_at) "
        "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)", tx_rows)
    cur.execute("SELECT transaction_code, id FROM sales_transactions WHERE transaction_code LIKE %s",
                (f"{CODE_PREFIX}-%",))
    tx_ids = dict(cur.fetchall())
    executemany_chunked(
        cur,
        "INSERT INTO sales_items (sale_transaction_id, inventory_item_id, quantity, unit_price, total_amount, "
        "created_at, updated_at) VALUES (%s, %s, %s, %s, %s, %s, %s)",
        [(tx_ids[code], *rest) for code, *rest in item_rows])
    return len(tx_rows), len(item_rows)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("xlsx", type=Path)
    parser.add_argument("--dry-run", action="store_true", help="report what would change, then roll back")
    args = parser.parse_args()

    logbook = load_logbook(args.xlsx)
    fruits = sorted(logbook["product"].unique())

    conn = connect()
    demo, other = demo_counts(conn)
    if other:
        fail(f"{other} non-demo sales transaction(s) exist; refusing to import over real records.")
    user_id = query_df(conn, "SELECT id FROM users WHERE role = 'owner' ORDER BY id LIMIT 1")
    user_id = int(user_id["id"].iloc[0]) if len(user_id) else None

    now = datetime.now()
    cur = conn.cursor()
    try:  # autocommit is off: everything below is one transaction
        tx_deleted, batches_deleted, items_deleted = delete_demo(cur, fruits)
        item_ids = upsert_items(cur, logbook, now)
        tx_added, items_added = insert_sales(cur, logbook, item_ids, user_id)
        if args.dry_run:
            conn.rollback()
        else:
            conn.commit()
    except Exception:
        conn.rollback()
        raise
    finally:
        cur.close()
        conn.close()

    emit({
        "success": True,
        "dry_run": args.dry_run,
        "deleted": {"demo_transactions": tx_deleted, "demo_batches": batches_deleted,
                    "demo_only_fruits": items_deleted},
        "imported": {"fruits": fruits, "days": int(logbook["date"].nunique()),
                     "from": str(logbook["date"].min().date()), "to": str(logbook["date"].max().date()),
                     "transactions": tx_added, "sales_items": items_added,
                     "total_kg": round(float(logbook["kg"].sum()), 2)},
    })


if __name__ == "__main__":
    try:
        main()
    except SystemExit:
        raise
    except Exception as exc:
        fail(f"{type(exc).__name__}: {exc}")
