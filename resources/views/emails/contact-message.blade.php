@extends('emails.layout')

@section('content')
    <h1 style="margin:0 0 8px;font-size:22px;color:#111827;">Nova mensagem ✉️</h1>
    <p style="margin:0 0 16px;color:#4b5563;">
        <strong style="color:#111827;">{{ $msg->name }}</strong> · {{ $msg->email }}@if($msg->phone) · {{ $msg->phone }}@endif
        @if($msg->product)<br>Sobre: <a href="{{ route('products.show', $msg->product) }}" style="color:#0891b2;">{{ $msg->product->name }}</a>@endif
    </p>

    <p style="margin:0 0 4px;"><strong>{{ $msg->subject }}</strong></p>
    <div style="margin:0 0 24px;padding:14px;background:#f9fafb;border-radius:8px;white-space:pre-line;">{{ $msg->message }}</div>

    <p style="margin:0;"><a href="{{ route('admin.messages.show', $msg) }}" style="display:inline-block;background:#0a0a12;color:#ffffff;text-decoration:none;font-weight:bold;padding:12px 22px;border-radius:999px;">Abrir no painel</a></p>
@endsection

@section('footer')
    "Responder" vai direto para o cliente. · Aviso automático do site
@endsection
