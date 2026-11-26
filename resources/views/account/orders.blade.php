@extends('account.layout')

@section('title', 'Meus pedidos')

@section('account')
@if($orders->isEmpty())
    <div class="glass p-12 text-center">
        <p class="text-xl font-semibold text-white">Você ainda não fez nenhum pedido</p>
        <a href="{{ route('products.index') }}" class="btn-primary mt-6">Começar a comprar</a>
    </div>
@else
    <div class="space-y-3">
        @foreach($orders as $order)
            <a href="{{ route('account.order', $order) }}" class="glass flex flex-wrap items-center justify-between gap-4 !rounded-2xl p-5 transition hover:border-white/25">
                <div>
                    <p class="font-display font-semibold text-white">{{ $order->code }}</p>
                    <p class="text-sm text-zinc-500">{{ $order->created_at->format('d/m/Y H:i') }} · {{ $order->items_count }} {{ Str::plural('item', $order->items_count) }}</p>
                </div>
                <div class="flex items-center gap-5">
                    <x-status-badge :order="$order" />
                    <span class="font-display text-lg font-bold text-white">{{ money($order->total) }}</span>
                </div>
            </a>
        @endforeach
    </div>
    {{ $orders->links() }}
@endif
@endsection
