<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Report - FreshTrack</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
            margin: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #7C3AED;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #7C3AED;
            font-size: 28px;
            margin: 0 0 5px 0;
        }
        .header h2 {
            color: #666;
            font-size: 18px;
            margin: 0;
            font-weight: normal;
        }
        .meta {
            text-align: center;
            margin-bottom: 30px;
            color: #666;
        }
        .summary {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .summary-item {
            display: table-cell;
            width: 33.33%;
            padding: 15px;
            background: #F5F3FF;
            border: 2px solid #7C3AED;
            text-align: center;
            margin: 5px;
        }
        .summary-value {
            font-size: 24px;
            font-weight: bold;
            color: #7C3AED;
        }
        .summary-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background: #7C3AED;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #999;
            font-size: 10px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #7C3AED;
            margin: 30px 0 15px 0;
            border-bottom: 2px solid #7C3AED;
            padding-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🍎 FreshTrack</h1>
        <h2>Sales Performance Report</h2>
    </div>

    <div class="meta">
        <strong>Report Period:</strong> {{ $startDate->format('F j, Y') }} - {{ $endDate->format('F j, Y') }}<br>
        <strong>Generated:</strong> {{ now()->format('F j, Y g:i A') }}
    </div>

    <div class="summary">
        <div class="summary-item">
            <div class="summary-value">₱{{ number_format($totalSales, 2) }}</div>
            <div class="summary-label">Total Sales Revenue</div>
        </div>
        <div class="summary-item">
            <div class="summary-value">{{ number_format($totalTransactions) }}</div>
            <div class="summary-label">Total Transactions</div>
        </div>
        <div class="summary-item">
            <div class="summary-value">₱{{ number_format($totalTransactions > 0 ? $totalSales / $totalTransactions : 0, 2) }}</div>
            <div class="summary-label">Average Transaction</div>
        </div>
    </div>

    <div class="section-title">Top Selling Products</div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Product Name</th>
                <th>Quantity Sold</th>
                <th>Total Revenue</th>
                <th>Avg Price</th>
            </tr>
        </thead>
        <tbody>
            @forelse($topProducts as $index => $product)
            @if($product->inventoryItem)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $product->inventoryItem->name }}</td>
                <td>{{ number_format($product->total_quantity, 2) }} {{ $product->inventoryItem->unit }}</td>
                <td>₱{{ number_format($product->total_sales, 2) }}</td>
                <td>₱{{ number_format($product->total_quantity > 0 ? $product->total_sales / $product->total_quantity : 0, 2) }}</td>
            </tr>
            @endif
            @empty
            <tr>
                <td colspan="5" style="text-align:center; color:#999; padding:30px;">
                    No sales data available for this period
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>FreshTrack Inventory Management System &copy; {{ date('Y') }}</p>
        <p>This report is system-generated and contains confidential business information.</p>
    </div>
</body>
</html>
