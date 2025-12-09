<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>{{ ucfirst($reportType) }} Report</title>
    <style>
        @page {
            margin: 2cm;

            @top-center {
                content: "{{ ucfirst($reportType) }} Report";
            }

            @bottom-right {
                content: "Page " counter(page) " of " counter(pages);
            }
        }

        body {
            font-family: 'Inter', Arial, sans-serif;
            font-size: 10pt;
            color: #191919;
        }

        .header {
            background: #E3651D;
            color: white;
            padding: 20px;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 24pt;
        }

        .filters {
            background: #f5f5f5;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #750E21;
        }

        .filters h3 {
            margin-top: 0;
            color: #750E21;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        thead {
            background: #191919;
            color: white;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        tbody tr:nth-child(even) {
            background: #f9f9f9;
        }

        .page-break {
            page-break-after: always;
        }

        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 2px solid #BED754;
            font-size: 8pt;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>{{ ucfirst($reportType) }} Report</h1>
        <p>Generated: {{ $generatedAt->format('F d, Y H:i:s') }}</p>
    </div>

    @if(!empty($filters))
        <div class="filters">
            <h3>Applied Filters</h3>
            @foreach($filters as $key => $value)
                <p><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ $value }}</p>
            @endforeach
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Order #</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
                <tr>
                    <td>{{ $row->order_number ?? 'N/A' }}</td>
                    <td>{{ isset($row->order_date) ? \Carbon\Carbon::parse($row->order_date)->format('Y-m-d') : 'N/A' }}
                    </td>
                    <td>{{ $row->customer->name ?? 'N/A' }}</td>
                    <td>{{ ucfirst($row->status ?? 'N/A') }}</td>
                    <td>${{ number_format($row->total_amount ?? 0, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>LF E-commerce Reporting Module | Confidential</p>
    </div>
</body>

</html>