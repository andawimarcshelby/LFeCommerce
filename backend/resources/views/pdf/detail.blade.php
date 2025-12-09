@extends('pdf.layout')

@section('content')
    <div class="report-content">
        <h2>Detail Report</h2>

        @if(isset($data['data']) && count($data['data']) > 0)
            <table>
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Region</th>
                        <th>Status</th>
                        <th class="currency">Amount</th>
                        <th class="currency">Tax</th>
                        <th class="currency">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['data'] as $order)
                        <tr>
                            <td>{{ $order->order_number ?? $order->id }}</td>
                            <td>{{ \Carbon\Carbon::parse($order->order_date)->format('Y-m-d') }}</td>
                            <td>{{ $order->customer->name ?? 'N/A' }}</td>
                            <td>{{ $order->region->name ?? 'N/A' }}</td>
                            <td>
                                @if($order->status === 'completed')
                                    <span class="badge badge-success">{{ ucfirst($order->status) }}</span>
                                @elseif($order->status === 'failed' || $order->status === 'cancelled')
                                    <span class="badge badge-danger">{{ ucfirst($order->status) }}</span>
                                @else
                                    <span class="badge badge-warning">{{ ucfirst($order->status) }}</span>
                                @endif
                            </td>
                            <td class="currency">${{ number_format($order->total_amount - $order->tax, 2) }}</td>
                            <td class="currency">${{ number_format($order->tax, 2) }}</td>
                            <td class="currency">${{ number_format($order->total_amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if(isset($data['total']))
                <div class="summary-stats">
                    <div class="stat-card">
                        <div class="stat-label">Total Orders</div>
                        <div class="stat-value">{{ number_format($data['total']) }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Total Revenue</div>
                        <div class="stat-value">${{ number_format(collect($data['data'])->sum('total_amount'), 2) }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Average Order</div>
                        <div class="stat-value">${{ number_format(collect($data['data'])->avg('total_amount'), 2) }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Total Tax</div>
                        <div class="stat-value">${{ number_format(collect($data['data'])->sum('tax'), 2) }}</div>
                    </div>
                </div>
            @endif
        @else
            <p>No data available for the selected filters.</p>
        @endif
    </div>
@endsection