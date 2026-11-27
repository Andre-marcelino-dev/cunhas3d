<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0;border-collapse:collapse;font-size:14px;">
    @foreach($order->items as $item)
        <tr>
            <td style="padding:8px 0;border-bottom:1px solid #e5e7eb;">{{ $item->quantity }}× {{ $item->name }}</td>
            <td style="padding:8px 0;border-bottom:1px solid #e5e7eb;text-align:right;white-space:nowrap;">{{ money($item->total) }}</td>
        </tr>
    @endforeach
    <tr>
        <td style="padding:8px 0;color:#6b7280;">Frete</td>
        <td style="padding:8px 0;text-align:right;color:#6b7280;">{{ (float) $order->shipping > 0 ? money($order->shipping) : 'Grátis' }}</td>
    </tr>
    <tr>
        <td style="padding:8px 0;font-weight:bold;">Total</td>
        <td style="padding:8px 0;text-align:right;font-weight:bold;">{{ money($order->total) }}</td>
    </tr>
</table>

<p style="margin:0 0 4px;"><strong>Entrega em</strong></p>
<p style="margin:0 0 20px;color:#4b5563;">
    {{ $order->address }}, {{ $order->number }}@if($order->complement) · {{ $order->complement }}@endif<br>
    {{ $order->district }} · {{ $order->city }}/{{ $order->state }} · CEP {{ $order->cep }}
</p>
