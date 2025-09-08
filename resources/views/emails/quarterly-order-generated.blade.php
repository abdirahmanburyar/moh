<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quarterly Order Generated</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #2563eb;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f8fafc;
            padding: 30px;
            border: 1px solid #e2e8f0;
        }
        .order-info {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .info-label {
            font-weight: bold;
            color: #475569;
        }
        .info-value {
            color: #1e293b;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        .summary-card {
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .summary-number {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
        }
        .summary-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            margin-top: 5px;
        }
        .view-order-btn {
            display: inline-block;
            background-color: #10b981;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
        }
        .view-order-btn:hover {
            background-color: #059669;
        }
        .footer {
            background-color: #f1f5f9;
            padding: 20px;
            text-align: center;
            border-radius: 0 0 8px 8px;
            color: #64748b;
            font-size: 14px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pending {
            background-color: #fef3c7;
            color: #d97706;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📋 Quarterly Order Generated</h1>
        <p>A new quarterly order has been created for your facility</p>
    </div>

    <div class="content">
        <div class="order-info">
            <h2>Order Details</h2>
            
            <div class="info-row">
                <span class="info-label">Order Number:</span>
                <span class="info-value"><strong>{{ $orderNumber }}</strong></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Facility:</span>
                <span class="info-value">{{ $facilityName }} (ID: {{ $facilityId }})</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Order Type:</span>
                <span class="info-value">{{ ucwords(str_replace('-', ' ', $orderType)) }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Order Date:</span>
                <span class="info-value">{{ \Carbon\Carbon::parse($orderDate)->format('F j, Y') }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Expected Date:</span>
                <span class="info-value">{{ \Carbon\Carbon::parse($expectedDate)->format('F j, Y') }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="info-value">
                    <span class="status-badge status-{{ strtolower($status) }}">{{ ucfirst($status) }}</span>
                </span>
            </div>
        </div>

        <h3>📊 Order Summary</h3>
        <div class="summary-grid">
            <div class="summary-card">
                <div class="summary-number">{{ $totalItems }}</div>
                <div class="summary-label">Total Items</div>
            </div>
            <div class="summary-card">
                <div class="summary-number">{{ $processedItems }}</div>
                <div class="summary-label">Processed</div>
            </div>
            <div class="summary-card">
                <div class="summary-number">{{ $skippedItems }}</div>
                <div class="summary-label">Skipped</div>
            </div>
            <div class="summary-card">
                <div class="summary-number">{{ $errorItems }}</div>
                <div class="summary-label">Errors</div>
            </div>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $orderViewUrl }}" class="view-order-btn">
                🔍 View Order Details
            </a>
        </div>

        <div style="background-color: #eff6ff; padding: 15px; border-radius: 6px; border-left: 4px solid #2563eb;">
            <p><strong>📌 Next Steps:</strong></p>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>Review the order details by clicking the button above</li>
                <li>Verify inventory allocations are correct</li>
                <li>Process the order when ready</li>
                <li>Contact the warehouse team if you have any questions</li>
            </ul>
        </div>
    </div>

    <div class="footer">
        <p>This email was automatically generated on {{ $generatedAt }}</p>
        <p>Warehouse Management System</p>
    </div>
</body>
</html> 