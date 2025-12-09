@extends('pdf.layout')

@section('content')
    <div class="report-content">
        <h2>Exceptions Report - {{ ucfirst($exceptionType ?? 'All') }}</h2>

        @if(isset($data) && count($data) > 0)
            <table>
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Customer</th>
                        @if(($exceptionType ?? 'failed_orders') === 'refunds')
                            <th>Refund Date</th>
                            <th>Reason</th>
                            <th class="currency">Refund Amount</th>
                        @else
                            <th>Status</th>
                            <th>Payment Method</th>
                            <th class="currency">Amount</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $row)
                        <tr>
                            @if(($exceptionType ?? 'failed_orders') === 'refunds')
                                <td>{{ $row->order_number }}</td>
                                <td>{{ \Carbon\Carbon::parse($row->created_at)->format('Y-m-d') }}</td>
                                <td>{{ $row->customer_name }}</td>
                                <td>{{ \Carbon\Carbon::parse($row->refund_date)->format('Y-m-d') }}</td>
                                <td>{{ $row->reason ?? 'N/A' }}</td>
                                <td class="currency">${{ number_format($row->amount, 2) }}</td>
                            @else
                                <td>{{ $row->order_number ?? $row->id }}</td>
                                <td>{{ \Carbon\Carbon::parse($row->order_date)->format('Y-m-d') }}</td>
                                <td>{{ $row->customer->name ?? 'N/A' }}</td>
                                <td><span class="badge badge-danger">{{ ucfirst($row->status) }}</span></td>
                                <td>{{ ucfirst($row->payment_method) }}</td>
                                <td class="currency">${{ number_format($row->total_amount, 2) }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="summary-stats">
                <div class="stat-card">
                    <div class="stat-label">Total Exceptions</div>
                    <div class="stat-value">{{ count($data) }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Amount</div>
                    <div class="stat-value">
                        @if(($exceptionType ?? 'failed_orders') === 'refunds')
                            ${{ number_format(collect($data)->sum('amount'), 2) }}
                        @else
                            ${{ number_format(collect($data)->sum('total_amount'), 2) }}
                        @endif
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Average Amount</div>
                    <div class="stat-value">
                        @if(($exceptionType ?? 'failed_orders') === 'refunds')
                            ${{ number_format(collect($data)->avg('amount'), 2) }}
                        @else
                            ${{ number_format(collect($data)->avg('total_amount'), 2) }}
                        @endif
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Date Range</div>
                    <div class="stat-value" style="font-size: 10pt;">
                        {{ \Carbon\Carbon::parse(collect($data)->min('order_date'))->format('Y-m-d') }}
                    </div>
                </div>
            </div>
        @else
            <p>No exceptions found for the selected filters.</p>
        @endif
    </div>
@endsection