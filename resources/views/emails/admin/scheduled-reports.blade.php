<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduled Reports</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #392a26;
            color: #ffffff;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
            margin: -30px -30px 20px -30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin: 20px 0;
        }
        .report-list {
            list-style: none;
            padding: 0;
        }
        .report-list li {
            padding: 10px;
            margin: 5px 0;
            background-color: #f9fafb;
            border-left: 4px solid #392a26;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #6b7280;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Scheduled Reports</h1>
        </div>
        <div class="content">
            <p>Hello,</p>
            <p>Your scheduled <strong>{{ ucfirst($frequency) }}</strong> reports for the period <strong>{{ \Carbon\Carbon::parse($dateFrom)->format('M d, Y') }}</strong> to <strong>{{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}</strong> have been generated and are attached to this email.</p>
            
            <h3>Generated Reports:</h3>
            <ul class="report-list">
                @foreach($files as $file)
                <li>
                    <strong>{{ ucfirst(str_replace('_', ' ', $file['report'])) }}</strong><br>
                    <small>{{ basename($file['filename']) }}</small>
                </li>
                @endforeach
            </ul>
            
            <p>Please find the reports attached to this email.</p>
        </div>
        <div class="footer">
            <p>This is an automated email from LegitBooks Admin Portal.</p>
            <p>Generated on {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>

