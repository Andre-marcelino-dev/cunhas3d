@extends('layouts.app')

@section('title', 'Carrinho')

@section('content')
<section class="container-x pt-36">
    <h1 data-reveal class="text-5xl font-bold">Seu carrinho</h1>

    @if($items->isEmpty())
        <div data-reveal class="glass mt-10 p-16 text-center">
            <p class="text-6xl">🛒</p>
            <p class="mt-4 text-xl font-semibold text-white">Seu carrinho está vazio</p>
            <p class="mt-2 text-zinc-400">Que tal dar uma olhada nas novidades que acabaram de sair da impressora?</p>
            <a href="{{ route('products.index') }}" class="btn-primary mt-8">Ir para a loja</a>
        </div>
    @else
        <div class="mt-10 grid gap-8 lg:grid-cols-[1fr_380px]">
            <div data-reveal data-stagger class="space-y-4">
                @foreach($items as $item)
                    <div class="glass flex gap-4 !rounded-2xl p-4 sm:gap-6">
                        <a href="{{ route('products.show', $item->product) }}" class="h-24 w-24 shrink-0 overflow-hidden rounded-xl sm:h-28 sm:w-28" style="background: radial-gradient(circle, {{ $item->product->color }}33, transparent 70%)">
                            @if($item->product->image_url)
                                <img src="{{ $item->product->image_url }}" alt="" class="h-full w-full object-cover">
                            @else
                                <img data-thumb data-shape="{{ $item->product->shape }}" data-color="{{ $item->product->color }}" alt="" class="h-full w-full object-contain opacity-0 transition">
                            @endif
                        </a>
                        <div class="flex flex-1 flex-col justify-between gap-3 sm:flex-row sm:items-center">
                            <div>
                                <p class="text-xs uppercase tracking-wider text-zinc-500">{{ $item->product->category?->name }} · {{ $item->product->material }}</p>
                                <a href="{{ route('products.show', $item->product) }}" class="font-semibold text-white hover:text-neon">{{ $item->product->name }}</a>
                                <p class="text-sm text-zinc-400">{{ money($item->product->final_price) }} cada</p>
                            </div>
                            <div class="flex items-center gap-4">
                                <form method="POST" action="{{ route('cart.update', $item->product) }}" class="flex items-center rounded-full border border-white/15">
                                    @csrf @method('PATCH')
                                    <button name="quantity" value="{{ $item->quantity - 1 }}" class="h-9 w-9 text-zinc-400 hover:text-white">−</button>
                                    <span class="w-8 text-center text-sm text-white">{{ $item->quantity }}</span>
                                    <button name="quantity" value="{{ $item->quantity + 1 }}" class="h-9 w-9 text-zinc-400 hover:text-white" @disabled($item->quantity >= $item->product->stock)>+</button>
                                </form>
                                <p class="w-24 text-right font-display font-bold text-white">{{ money($item->total) }}</p>
                                <form method="POST" action="{{ route('cart.remove', $item->product) }}">
                                    @csrf @method('DELETE')
                                    <button class="grid h-9 w-9 place-items-center rounded-full text-zinc-500 hover:bg-rose-500/10 hover:text-rose-300" aria-label="Remover">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 7h16M10 11v6m4-6v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <aside data-reveal class="glass h-fit p-6 lg:sticky lg:top-28">
                <h2 class="text-xl font-semibold">Resumo</h2>
                <dl class="mt-6 space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-zinc-400">Subtotal</dt><dd class="text-white">{{ money($subtotal) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-400">Frete</dt><dd class="{{ $shipping == 0 ? 'text-emerald-300' : 'text-white' }}">{{ $shipping == 0 ? 'Grátis' : money($shipping) }}</dd></div>
                </dl>
                @if($shipping > 0)
                    @php $pct = min(100, $subtotal / $freeShippingFrom * 100); @endphp
                    <div class="mt-4 rounded-xl bg-white/[0.03] p-3 text-xs text-zinc-400">
                        Faltam <span class="text-neon">{{ money($freeShippingFrom - $subtotal) }}</span> para frete grátis
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white/10"><div class="h-full rounded-full bg-gradient-to-r from-neon to-plasma" style="width: {{ $pct }}%"></div></div>
                    </div>
                @endif
                <div class="my-6 h-px bg-white/10"></div>
                <div class="flex items-end justify-between">
                    <span class="text-zinc-400">Total</span>
                    <span class="font-display text-3xl font-bold text-white">{{ money($total) }}</span>
                </div>
                <a href="{{ route('checkout.index') }}" data-magnetic class="btn-primary mt-6 w-full !py-4">Finalizar compra</a>
                <a href="{{ route('products.index') }}" class="mt-3 block text-center text-sm text-zinc-400 hover:text-white">Continuar comprando</a>
                <p class="mt-6 flex items-center justify-center gap-2 text-xs text-zinc-500">🔒 Pagamento processado pelo Mercado Pago</p>
            </aside>
        </div>
    @endif
</section>
@endsection
