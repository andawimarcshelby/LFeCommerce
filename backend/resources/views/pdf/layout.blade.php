<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Report' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: A4;
            margin: 15mm 15mm 20mm 15mm;

            @top-center {
                content: element(header);
            }

            @bottom-center {
                content: element(footer);
            }
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #191919;
        }

        .page-header {
            position: running(header);
            padding: 10px 0;
            border-bottom: 2px solid #750E21;
            margin-bottom: 10px;
        }

        .page-header h1 {
            font-size: 14pt;
            color: #750E21;
            margin-bottom: 5px;
        }

        .page-header .filters {
            font-size: 8pt;
            color: #666;
        }

        .page-footer {
            position: running(footer);
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 8pt;
            color: #666;
            display: flex;
            justify-content: space-between;
        }

        .page-footer .page-number::after {
            content: "Page " counter(page);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            page-break-inside: auto;
        }

        thead {
            display: table-header-group;
        }

        tbody {
            display: table-row-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        th {
            background-color: #750E21;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: 600;
            font-size: 9pt;
        }

        td {
            padding: 6px 8px;
            border-bottom: 1px solid #eee;
            font-size: 9pt;
        }

        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .section-break {
            page-break-before: always;
            margin-top: 20px;
        }

        .section-header {
            background-color: #750E21;
            color: white;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 12pt;
            font-weight: 600;
        }

        .summary-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .stat-card {
            background-color: #f5f5f5;
            padding: 10px;
            border-left: 3px solid #E3651D;
        }

        .stat-label {
            font-size: 8pt;
            color: #666;
            margin-bottom: 3px;
        }

        .stat-value {
            font-size: 14pt;
            font-weight: 600;
            color: #191919;
        }

        .table-of-contents {
            page-break-after: always;
        }

        .table-of-contents h1 {
            font-size: 18pt;
            color: #750E21;
            margin-bottom: 20px;
            border-bottom: 2px solid #750E21;
            padding-bottom: 10px;
        }

        .toc-entry {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px dotted #ddd;
        }

        .toc-title {
            flex: 1;
        }

        .toc-page {
            margin-left: 10px;
            font-weight: 600;
        }

        .currency {
            text-align: right;
        }

        .number {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: 600;
        }

        .badge-success {
            background-color: #BED754;
            color: #191919;
        }

        .badge-danger {
            background-color: #750E21;
            color: white;
        }

        .badge-warning {
            background-color: #E3651D;
            color: white;
        }
    </style>
</head>

<body>
    <div class="page-header">
        <h1>{{ $title ?? 'Report' }}</h1>
        <div class="filters">
            @if(isset($filters) && !empty($filters))
                @foreach($filters as $key => $value)
                    <span>{{ ucfirst(str_replace('_', ' ', $key)) }}: {{ $value }}</span>
                    @if(!$loop->last) | @endif
                @endforeach
            @endif
        </div>
    </div>

    <div class="page-footer">
        <div class="generated-at">Generated: {{ now()->format('Y-m-d H:i:s') }}</div>
        <div class="page-number"></div>
    </div>

    <div class="content">
        @yield('content')
    </div>
</body>

</html>