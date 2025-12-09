@extends('pdf.layout')

@section('content')
    <div class="report-content">
        <h2>Top {{ ucfirst($topType ?? 'Items') }} Report</h2>
        <p style="margin-bottom: 20px; color: #666;">Showing top {{ $limit ?? 100 }} by revenue</p>

        @if(isset($data) && count($data) > 0)
            <table>
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">Rank</th>
                        @if(($topType ?? 'customers') === 'customers')
                            <th>Customer Name</th>
                            <th>Email</th>
                            <th>Account Type</th>
                        @elseif($topType === 'products')
                            <th>Product Name</th>
                            <th>SKU</th>
                            <th>Category</th>
                        @else
                            <th>Region Name</th>
                            <th>Country</th>
                            <th></th>
                        @endif
                        <th class="number">Total Orders</th>
                        <th class="currency">Total Revenue</th>
                        <th class="currency">Avg Order Value</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $index => $row)
                        <tr>
                            <td class="text-center">
                                @if($index < 3)
                                    <span class="badge badge-warning">{{ $index + 1 }}</span>
                                @else
                                    {{ $index + 1 }}
                                @endif
                            </td>
                            @if(($topType ?? 'customers') === 'customers')
                                <td>{{ $row->name }}</td>
                                <td>{{ $row->email }}</td>
                                <td><span class="badge badge-success">{{ ucfirst($row->account_type) }}</span></td>
                            @elseif($topType === 'products')
                                <td>{{ $row->name }}</td>
                                <td>{{ $row->sku }}</td>
                                <td>{{ $row->category }}</td>
                            @else
                                <td>{{ $row->name }}</td>
                                <td>{{ $row->country }}</td>
                                <td></td>
                            @endif
                            <td class="number">{{ number_format($row->total_orders) }}</td>
                            <td class="currency">${{ number_format($row->total_revenue, 2) }}</td>
                            <td class="currency">${{ number_format($row->average_order_value, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="summary-stats">
                <div class="stat-card">
                    <div class="stat-label">Total {{ ucfirst($topType ?? 'Items') }}</div>
                    <div class="stat-value">{{ count($data) }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Combined Revenue</div>
                    <div class="stat-value">${{ number_format(collect($data)->sum('total_revenue'), 2) }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Combined Orders</div>
                    <div class="stat-value">{{ number_format(collect($data)->sum('total_orders')) }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Avg Revenue per Item</div>
                    <div class="stat-value">${{ number_format(collect($data)->avg('total_revenue'), 2) }}</div>
                </div>
            </div>
        @else
            <p>No data available for the selected filters.</p>
        @endif
    </div>
@endsection