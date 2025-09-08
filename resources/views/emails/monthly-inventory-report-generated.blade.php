<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Monthly Inventory Report Notification</title>
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
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .success {
            color: #28a745;
        }
        .error {
            color: #dc3545;
        }
        .info-box {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .details {
            margin: 20px 0;
        }
        .details table {
            width: 100%;
            border-collapse: collapse;
        }
        .details th, .details td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .details th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        @if($errorMessage)
            <h1 class="error">❌ Monthly Inventory Report Generation Failed</h1>
        @else
            <h1 class="success">✅ Monthly Inventory Report Generated Successfully</h1>
        @endif
    </div>

    @if($errorMessage)
        <div class="info-box">
            <h3>Error Details:</h3>
            <p><strong>Error Message:</strong> {{ $errorMessage }}</p>
            <p>Please check the application logs for more detailed information and contact the system administrator if needed.</p>
        </div>
    @else
        <div class="details">
            <h3>Report Summary:</h3>
            <table>
                <tr>
                    <th>Report Month:</th>
                    <td>{{ \Carbon\Carbon::createFromFormat('Y-m', $report->month_year)->format('F Y') }}</td>
                </tr>
                <tr>
                    <th>Generated At:</th>
                    <td>{{ $report->generated_at->format('Y-m-d H:i:s') }}</td>
                </tr>
                <tr>
                    <th>Items Generated:</th>
                    <td>{{ number_format($itemsGenerated) }} products</td>
                </tr>
                <tr>
                    <th>Status:</th>
                    <td><span class="success">{{ ucfirst($report->status) }}</span></td>
                </tr>
            </table>
        </div>

        <div class="info-box">
            <h4>Next Steps:</h4>
            <ul>
                <li>The monthly inventory report is now available in the warehouse management system</li>
                <li>You can view the detailed report in the Reports section</li>
                <li>The report includes beginning balance, received quantities, issued quantities, adjustments, and closing balances for all active products</li>
            </ul>
        </div>

        <div class="info-box">
            <h4>Report Access:</h4>
            <p>To view this report, log in to the warehouse system and navigate to:</p>
            <p><strong>Reports → Warehouse Monthly Report → {{ \Carbon\Carbon::createFromFormat('Y-m', $report->month_year)->format('F Y') }}</strong></p>
        </div>
    @endif

    <div class="footer">
        <p>This is an automated notification from the Warehouse Management System.</p>
        <p>Generated at {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>
