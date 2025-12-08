<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Charts' }}</title>
    <style>
        @page {
            margin: 40px 60px;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
        }

        h2 {
            font-size: 24px;
            color: #667eea;
            margin: 30px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .chart-container {
            margin: 30px 0;
            page-break-inside: avoid;
        }

        .chart-title {
            font-size: 18px;
            font-weight: 600;
            color: #764ba2;
            margin-bottom: 15px;
        }

        .chart {
            width: 100%;
            max-width: 700px;
            margin: 0 auto;
            display: block;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .chart-description {
            font-size: 14px;
            color: #666;
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            border-radius: 4px;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin: 30px 0;
        }

        .chart-grid-item {
            page-break-inside: avoid;
        }

        @media print {
            .chart-container {
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>
    @if(isset($pageTitle))
        <h2>{{ $pageTitle }}</h2>
    @endif

    @if(isset($charts) && count($charts) > 0)
        @if(isset($useGrid) && $useGrid)
            <div class="charts-grid">
                @foreach($charts as $chart)
                    <div class="chart-grid-item">
                        <div class="chart-title">{{ $chart['title'] }}</div>
                        <img src="{{ $chart['url'] }}" alt="{{ $chart['title'] }}" class="chart">
                        @if(isset($chart['description']))
                            <div class="chart-description">{{ $chart['description'] }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            @foreach($charts as $chart)
                <div class="chart-container">
                    <div class="chart-title">{{ $chart['title'] }}</div>
                    <img src="{{ $chart['url'] }}" alt="{{ $chart['title'] }}" class="chart">
                    @if(isset($chart['description']))
                        <div class="chart-description">{{ $chart['description'] }}</div>
                    @endif
                </div>
            @endforeach
        @endif
    @else
        <p style="color: #999; font-style: italic;">No charts available for this report.</p>
    @endif
</body>

</html>