@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
    @foreach([
        ['Faturamento hoje', money($revenueToday), 'from-neon/20'],
        ['Faturamento do mês', money($revenueMonth), 'from-plasma/20'],
        ['Aguardando pagamento', $ordersPending, 'from-amber-400/20'],
        ['Para produzir', $ordersToProduce, 'from-flare/20'],
        ['Clientes', $customers, 'from-emerald-400/20'],
    ] as [$label, $value, $grad])
        <div class="rounded-2xl border border-white/10 bg-gradient-to-br {{ $grad }} to-transparent p-5">
            <p class="text-xs uppercase tracking-wider text-zinc-400">{{ $label }}</p>
            <p class="mt-2 font-display text-2xl font-bold text-white">{{ $value }}</p>
        </div>
    @endforeach
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-3">
    {{-- Gráfico de faturamento (SVG puro) --}}
    <div class="glass !rounded-2xl p-6 xl:col-span-2">
        <h2 class="text-base font-semibold">Faturamento · últimos 14 dias</h2>
        @php $max = max(1, $chart->max('value')); @endphp
        <div class="mt-6 flex h-56 gap-2">
            @foreach($chart as $bar)
                <div class="group flex flex-1 flex-col items-center gap-2">
                    <div class="relative flex w-full flex-1 items-end">
                        <div class="w-full rounded-t-lg bg-gradient-to-t from-neon/40 to-plasma transition group-hover:from-neon group-hover:to-flare"
                             style="height: {{ max(2, $bar['value'] / $max * 100) }}%"></div>
                        <span class="pointer-events-none absolute -top-8 left-1/2 hidden -translate-x-1/2 whitespace-nowrap rounded-lg bg-panel px-2 py-1 text-xs text-white shadow group-hover:block">{{ money($bar['value']) }}</span>
                    </div>
                    <span class="text-[10px] text-zinc-500">{{ $bar['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="glass !rounded-2xl p-6">
        <h2 class="text-base font-semibold">Mais vendidos</h2>
        <ul class="mt-4 space-y-3 text-sm">
            @forelse($topProducts as $p)
                <li class="flex justify-between gap-3"><span class="text-zinc-300">{{ $p->name }}</span><span class="shrink-0 text-zinc-500">{{ $p->qty }} un · {{ money($p->revenue) }}</span></li>
            @empty
                <li class="text-zinc-500">Ainda sem vendas aprovadas.</li>
            @endforelse
        </ul>
        <h2 class="mt-8 text-base font-semibold">Estoque baixo</h2>
        <ul class="mt-4 space-y-2 text-sm">
            @forelse($lowStock as $p)
                <li class="flex justify-between"><a href="{{ route('admin.products.edit', $p) }}" class="text-zinc-300 hover:text-neon">{{ $p->name }}</a><span class="{{ $p->stock ? 'text-amber-300' : 'text-rose-400' }}">{{ $p->stock }} un</span></li>
            @empty
                <li class="text-zinc-500">Tudo certo com o estoque. ✓</li>
            @endforelse
        </ul>
    </div>
</div>

<div class="glass mt-6 overflow-hidden !rounded-2xl">
    <div class="flex items-center justify-between p-6">
        <h2 class="text-base font-semibold">Últimos pedidos</h2>
        <a href="{{ route('admin.orders.index') }}" class="text-sm text-neon">Ver todos →</a>
    </div>
    @include('admin.orders._table', ['orders' => $latestOrders])
</div>
@endsection
