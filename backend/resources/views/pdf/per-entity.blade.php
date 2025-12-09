@extends('pdf.layout')

@section('content')
    @if(isset($toc))
        {!! $toc !!}
    @endif

    @if(isset($entities) && count($entities) > 0)
        @foreach($entities as $entity)
            <div class="section-break">
                <div class="section-header">
                    {{ $entity['name'] }}
                    @if(isset($entity['subtitle']))
                        <span style="font-size: 10pt; font-weight: normal;"> - {{ $entity['subtitle'] }}</span>
                    @endif
                </div>

                @if(isset($entity['details']))
                    <div class="summary-stats" style="margin-bottom: 15px;">
                        @foreach($entity['details'] as $label => $value)
                            <div class="stat-card">
                                <div class="stat-label">{{ $label }}</div>
                                <div class="stat-value" style="font-size: 12pt;">{{ $value }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if(isset($entity['orders']) && count($entity['orders']) > 0)
                    <h3 style="margin-bottom: 10px; color: #750E21;">Orders</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th class="currency">Subtotal</th>
                                <th class="currency">Tax</th>
                                <th class="currency">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($entity['orders'] as $order)
                                <tr>
                                    <td>{{ $order->order_number ?? $order->id }}</td>
                                    <td>{{ \Carbon\Carbon::parse($order->order_date)->format('Y-m-d') }}</td>
                                    <td>
                                        @if($order->status === 'completed')
                                            <span class="badge badge-success">{{ ucfirst($order->status) }}</span>
                                        @elseif($order->status === 'failed')
                                            <span class="badge badge-danger">{{ ucfirst($order->status) }}</span>
                                        @else
                                            <span class="badge badge-warning">{{ ucfirst($order->status) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ ucfirst($order->payment_method) }}</td>
                                    <td class="currency">${{ number_format($order->total_amount - $order->tax, 2) }}</td>
                                    <td class="currency">${{ number_format($order->tax, 2) }}</td>
                                    <td class="currency">${{ number_format($order->total_amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="font-weight: 600; background-color: #f0f0f0;">
                                <td colspan="4">SUBTOTAL</td>
                                <td class="currency">
                                    ${{ number_format(collect($entity['orders'])->sum(fn($o) => $o->total_amount - $o->tax), 2) }}</td>
                                <td class="currency">${{ number_format(collect($entity['orders'])->sum('tax'), 2) }}</td>
                                <td class="currency">${{ number_format(collect($entity['orders'])->sum('total_amount'), 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                @endif
            </div>
        @endforeach
    @else
        <p>No entities found for the selected filters.</p>
    @endif
@endsection