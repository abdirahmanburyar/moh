<!DOCTYPE html>
<html>
<head>
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
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .content {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Physical Count Report Submitted</h2>
    </div>

    <div class="content">
        <p>Hello,</p>

        <p>A new physical count report has been submitted and is awaiting your review.</p>

        <p><strong>Details:</strong></p>
        <ul>
            <li>Submitted by: {{ $submittedBy->name }}</li>
            <li>Submission Date: {{ $adjustment->updated_at->format('F j, Y, g:i a') }}</li>
            <li>Month/Year: {{ \Carbon\Carbon::parse($adjustment->month_year)->format('F Y') }}</li>
        </ul>

        <p>Please review this report as soon as possible. You can access the report by clicking the button below:</p>

        <a href="{{ $approvalLink }}" class="button">Review Report</a>

        <p style="margin-top: 20px;">If you're unable to click the button, you can copy and paste this URL into your browser:</p>
        <p style="word-break: break-all;">{{ $approvalLink }}</p>
    </div>

    <div class="footer">
        <p>This is an automated message. Please do not reply to this email.</p>
    </div>
</body>
</html>
