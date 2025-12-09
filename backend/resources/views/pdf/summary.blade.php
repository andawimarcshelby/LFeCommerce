@extends('pdf.layout')

@section('content')
    <div class="report-content">
        <h2>Summary Report</h2>

        @if(isset($data) && count($data) > 0)
            <table>
                <thead>
                    <tr>
                        <th>{{ ucfirst($groupBy ?? 'Period') }}</th>
                        <th class="number">Total Orders</th>
                        <th class="currency">Total Revenue</th>
                        <th class="currency">Avg Order Value</th>
                        <th class="currency">Total Tax</th>
                        <th class="currency">Total Shipping</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $row)
                        <tr>
                            <td>{{ $row->group_key }}</td>
                            <td class="number">{{ number_format($row->total_orders) }}</td>
                            <td class="currency">${{ number_format($row->total_revenue, 2) }}</td>
                            <td class="currency">${{ number_format($row->average_order_value, 2) }}</td>
                            <td class="currency">${{ number_format($row->total_tax, 2) }}</td>
                            <td class="currency">${{ number_format($row->total_shipping, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="font-weight: 600; background-color: #f0f0f0;">
                        <td>TOTAL</td>
                        <td class="number">{{ number_format(collect($data)->sum('total_orders')) }}</td>
                        <td class="currency">${{ number_format(collect($data)->sum('total_revenue'), 2) }}</td>
                        <td class="currency">${{ number_format(collect($data)->avg('average_order_value'), 2) }}</td>
                        <td class="currency">${{ number_format(collect($data)->sum('total_tax'), 2) }}</td>
                        <td class="currency">${{ number_format(collect($data)->sum('total_shipping'), 2) }}</td>
                    </tr>
                </tfoot>
            </table>

            <div class="summary-stats">
                <div class="stat-card">
                    <div class="stat-label">Total Periods</div>
                    <div class="stat-value">{{ count($data) }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Orders</div>
                    <div class="stat-value">{{ number_format(collect($data)->sum('total_orders')) }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Revenue</div>
                    <div class="stat-value">${{ number_format(collect($data)->sum('total_revenue'), 2) }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Avg Order Value</div>
                    <div class="stat-value">${{ number_format(collect($data)->avg('average_order_value'), 2) }}</div>
                </div>
            </div>
        @else
            <p>No data available for the selected filters.</p>
        @endif
    </div>
@endsection