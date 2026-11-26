@extends('layouts.app')

@section('title', 'Pedido '.$order->code)

@section('content')
@php
    $approved = $order->status !== \App\Models\Order::STATUS_PENDING && $order->status !== \App\Models\Order::STATUS_CANCELED;
    $failed = in_array($mpStatus, ['rejected', 'failure', 'null'], true) && ! $approved;
@endphp
<section class="container-x pt-40">
    <div data-reveal class="glass relative mx-auto max-w-2xl overflow-hidden p-10 text-center sm:p-14">
        <div class="absolute -top-24 left-1/2 h-60 w-60 -translate-x-1/2 rounded-full blur-[90px] {{ $approved ? 'bg-emerald-400/30' : ($failed ? 'bg-rose-500/30' : 'bg-amber-400/25') }}"></div>

        <div class="relative mx-auto grid h-20 w-20 place-items-center rounded-full text-4xl {{ $approved ? 'bg-emerald-400/15' : ($failed ? 'bg-rose-500/15' : 'bg-amber-400/15') }}">
            {{ $approved ? '🎉' : ($failed ? '😕' : '⏳') }}
        </div>

        <h1 class="relative mt-6 text-4xl font-bold">
            @if($approved) Pagamento aprovado!
            @elseif($failed) Pagamento não concluído
            @else Pedido recebido!
            @endif
        </h1>
        <p class="relative mt-3 text-zinc-400">
            @if($approved)
                Seu pedido <strong class="text-white">{{ $order->code }}</strong> já entrou na fila de produção. Avisaremos por e-mail a cada etapa.
            @elseif($failed)
                O pagamento do pedido <strong class="text-white">{{ $order->code }}</strong> foi recusado ou cancelado. Você pode tentar novamente com outro método.
            @else
                Estamos aguardando a confirmação do pagamento do pedido <strong class="text-white">{{ $order->code }}</strong>. Pix costuma ser aprovado em segundos; boleto em até 3 dias úteis.
            @endif
        </p>

        <div class="relative mt-8 flex flex-wrap justify-center gap-3">
            @if($order->isPayable())
                <a href="{{ route('checkout.pay', $order) }}" class="btn-primary">{{ $failed ? 'Tentar novamente' : 'Abrir pagamento' }}</a>
            @endif
            <a href="{{ route('account.order', $order) }}" class="btn-ghost">Acompanhar pedido</a>
        </div>
    </div>
</section>
@endsection
