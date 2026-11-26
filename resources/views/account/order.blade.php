@extends('account.layout')

@section('title', 'Pedido '.$order->code)

@section('account')
@php
    $steps = [\App\Models\Order::STATUS_PENDING, \App\Models\Order::STATUS_PAID, \App\Models\Order::STATUS_PRODUCTION, \App\Models\Order::STATUS_SHIPPED, \App\Models\Order::STATUS_DELIVERED];
    $current = array_search($order->status, $steps, true);
@endphp
<div class="glass p-6 sm:p-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-sm text-zinc-500">Pedido</p>
            <h2 class="text-2xl font-bold">{{ $order->code }}</h2>
            <p class="text-sm text-zinc-500">Feito em {{ $order->created_at->format('d/m/Y H:i') }}</p>
        </div>
        <div class="flex flex-col items-end gap-3">
            <x-status-badge :order="$order" class="!text-sm" />
            @if($order->isPayable())
                <a href="{{ route('checkout.pay', $order) }}" class="btn-primary !py-2.5">Pagar agora</a>
            @endif
        </div>
    </div>

    {{-- Linha do tempo --}}
    @if($order->status !== \App\Models\Order::STATUS_CANCELED)
        <ol class="mt-10 grid grid-cols-5 gap-2">
            @foreach($steps as $i => $step)
                <li class="text-center">
                    <div class="h-1.5 rounded-full {{ $current !== false && $i <= $current ? 'bg-gradient-to-r from-neon to-plasma' : 'bg-white/10' }}"></div>
                    <p class="mt-2 text-[11px] leading-tight sm:text-xs {{ $current !== false && $i <= $current ? 'text-white' : 'text-zinc-600' }}">{{ \App\Models\Order::STATUSES[$step] }}</p>
                </li>
            @endforeach
        </ol>
    @endif

    @if($order->tracking_code)
        <div class="mt-8 rounded-2xl border border-sky-400/30 bg-sky-400/10 p-4 text-sm">
            📦 Código de rastreio: <strong class="font-mono text-white">{{ $order->tracking_code }}</strong>
            <a href="https://rastreamento.correios.com.br/app/index.php?objeto={{ urlencode($order->tracking_code) }}" target="_blank" rel="noopener" class="ml-2 text-sky-300 underline">Rastrear</a>
        </div>
    @endif

    <div class="mt-10 grid gap-8 md:grid-cols-2">
        <div>
            <h3 class="label">Itens</h3>
            <ul class="space-y-2 text-sm">
                @foreach($order->items as $item)
                    <li class="flex justify-between gap-3 rounded-xl bg-white/[0.03] p-3">
                        <span>{{ $item->quantity }}× {{ $item->name }}</span>
                        <span class="text-white">{{ money($item->total) }}</span>
                    </li>
                @endforeach
            </ul>
            <dl class="mt-4 space-y-1.5 text-sm">
                <div class="flex justify-between"><dt class="text-zinc-400">Subtotal</dt><dd>{{ money($order->subtotal) }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-400">Frete</dt><dd>{{ $order->shipping > 0 ? money($order->shipping) : 'Grátis' }}</dd></div>
                <div class="flex justify-between text-base font-semibold text-white"><dt>Total</dt><dd>{{ money($order->total) }}</dd></div>
            </dl>
        </div>
        <div class="space-y-6">
            <div>
                <h3 class="label">Entrega</h3>
                <p class="text-sm leading-relaxed text-zinc-300">{{ $order->name }}<br>{{ $order->address }}, {{ $order->number }} {{ $order->complement }}<br>{{ $order->district }} · {{ $order->city }}/{{ $order->state }}<br>CEP {{ $order->cep }}</p>
            </div>
            <div>
                <h3 class="label">Histórico</h3>
                <ul class="space-y-2 text-sm">
                    @foreach($order->histories as $h)
                        <li class="flex gap-3"><span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-neon"></span><span><span class="text-white">{{ $h->status_label }}</span> <span class="text-zinc-500">· {{ $h->created_at->format('d/m H:i') }}</span>@if($h->note)<br><span class="text-zinc-400">{{ $h->note }}</span>@endif</span></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
