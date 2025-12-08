<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Helvetica', 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .cover-container {
            text-align: center;
            padding: 60px;
            max-width: 800px;
        }

        .university-seal {
            width: 150px;
            height: 150px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 40px;
            display: flex;
            align-items: center;
            justify-center;
            font-size: 48px;
            color: #667eea;
            font-weight: bold;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }

        h1 {
            font-size: 48px;
            margin: 0 0 20px 0;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .subtitle {
            font-size: 24px;
            margin: 0 0 40px 0;
            opacity: 0.9;
            font-weight: 300;
        }

        .meta-info {
            font-size: 16px;
            opacity: 0.8;
            margin-top: 60px;
        }

        .meta-row {
            margin: 10px 0;
        }

        .meta-label {
            font-weight: 600;
            display: inline-block;
            width: 150px;
            text-align: right;
            margin-right: 20px;
        }

        .footer {
            position: absolute;
            bottom: 40px;
            font-size: 14px;
            opacity: 0.7;
        }
    </style>
</head>

<body>
    <div class="cover-container">
        <div class="university-seal">
            PPK
        </div>

        <h1>{{ $title }}</h1>

        @if(isset($subtitle))
            <div class="subtitle">{{ $subtitle }}</div>
        @endif

        <div class="meta-info">
            <div class="meta-row">
                <span class="meta-label">Report Type:</span>
                <span>{{ $reportType }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Generated:</span>
                <span>{{ $generatedDate }}</span>
            </div>
            @if(isset($dateRange))
                <div class="meta-row">
                    <span class="meta-label">Date Range:</span>
                    <span>{{ $dateRange }}</span>
                </div>
            @endif
            @if(isset($totalRecords))
                <div class="meta-row">
                    <span class="meta-label">Total Records:</span>
                    <span>{{ number_format($totalRecords) }}</span>
                </div>
            @endif
        </div>
    </div>

    <div class="footer">
        PPK LMS Reporting System &copy; {{ date('Y') }}
    </div>
</body>

</html>