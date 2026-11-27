@extends('emails.layout')

@section('content')
    <h1 style="margin:0 0 8px;font-size:22px;color:#111827;">Novo pedido pago 💰</h1>
    <p style="margin:0 0 16px;"><strong>{{ $order->code }}</strong> · {{ $order->name }} · {{ money($order->total) }}</p>

    @if($order->notes)
        <p style="margin:0 0 16px;padding:12px;background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;"><strong>Observações do cliente:</strong><br>{{ $order->notes }}</p>
    @endif

    @include('emails.partials.items')

    <p style="margin:0 0 4px;"><strong>Cliente</strong></p>
    <p style="margin:0 0 16px;color:#4b5563;">{{ $order->name }} · {{ $order->email }}@if($order->phone) · {{ $order->phone }}@endif</p>

    @if($productionDays > 0)
        <p style="margin:0 0 24px;color:#4b5563;">Prazo de produção informado ao cliente: <strong>{{ $productionDays }} {{ $productionDays == 1 ? 'dia útil' : 'dias úteis' }}</strong></p>
    @endif

    <p style="margin:0;"><a href="{{ route('admin.orders.show', $order) }}" style="display:inline-block;background:#0a0a12;color:#ffffff;text-decoration:none;font-weight:bold;padding:12px 22px;border-radius:999px;">Abrir no painel</a></p>
@endsection

@section('footer')
    "Responder" vai direto para o cliente. · Aviso automático do site
@endsection
