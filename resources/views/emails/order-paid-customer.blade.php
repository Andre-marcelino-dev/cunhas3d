@extends('emails.layout')

@section('content')
    <h1 style="margin:0 0 8px;font-size:22px;color:#111827;">Pagamento aprovado! 🎉</h1>
    <p style="margin:0 0 16px;">Olá, {{ \Illuminate\Support\Str::before($order->name, ' ') }}! Recebemos o pagamento do seu pedido <strong>{{ $order->code }}</strong>. Agora é com a gente.</p>

    @include('emails.partials.items')

    <p style="margin:0 0 4px;"><strong>Próximos passos</strong></p>
    <p style="margin:0 0 24px;">
        @if($productionDays > 0)
            Sua peça entra em produção e fica pronta em até <strong>{{ $productionDays }} {{ $productionDays == 1 ? 'dia útil' : 'dias úteis' }}</strong>.
        @else
            Seu pedido já está sendo preparado.
        @endif
        Quando enviarmos, o código de rastreio aparece na sua conta.
    </p>

    <p style="margin:0;"><a href="{{ route('account.order', $order) }}" style="display:inline-block;background:#22d3ee;color:#0a0a12;text-decoration:none;font-weight:bold;padding:12px 22px;border-radius:999px;">Acompanhar meu pedido</a></p>
@endsection

@section('footer')
    Dúvidas? É só responder este e-mail. · Cunhas 3D
@endsection
