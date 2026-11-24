@extends('layouts.app')

@section('title', 'Impressão 3D sob medida')

@section('content')
{{-- ===================== HERO com cena 3D ===================== --}}
<section class="relative flex min-h-[100svh] items-center overflow-hidden pt-24">
    <div class="grid-bg absolute inset-0"></div>
    <div class="absolute inset-0 opacity-50 lg:left-[38%] lg:opacity-100">
        <canvas id="hero-canvas" class="h-full w-full"></canvas>
    </div>
    <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-ink via-ink/70 to-transparent lg:via-ink/30"></div>
    <div class="pointer-events-none absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-ink to-transparent"></div>

    <div class="container-x relative">
        <div class="max-w-2xl">
            <span class="badge border-neon/30 bg-neon/10 text-neon">
                <span class="relative flex h-2 w-2"><span class="absolute h-full w-full animate-ping rounded-full bg-neon opacity-75"></span><span class="h-2 w-2 rounded-full bg-neon"></span></span>
                Impressoras trabalhando agora
            </span>
            <h1 data-split class="mt-6 text-5xl font-bold leading-[1.02] sm:text-6xl lg:text-7xl">
                Ideias que ganham forma, camada por camada.
            </h1>
            <p data-reveal class="mt-6 max-w-lg text-lg leading-relaxed text-zinc-400">
                Decoração, miniaturas, peças técnicas e protótipos impressos em 3D com precisão de 0,05 mm. Escolha, personalize e receba em casa.
            </p>
            <div data-reveal class="mt-10 flex flex-wrap items-center gap-4">
                <a href="{{ route('products.index') }}" data-magnetic class="btn-primary !px-8 !py-4 text-base">
                    Explorar a loja
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                </a>
                <a href="#como-funciona" class="btn-ghost !px-8 !py-4 text-base">Como funciona</a>
            </div>

            <dl data-reveal data-stagger class="mt-16 grid max-w-md grid-cols-3 gap-6">
                <div><dt class="text-xs uppercase tracking-wider text-zinc-500">Peças entregues</dt><dd class="mt-1 font-display text-3xl font-bold text-white" data-count="12500" data-suffix="+">0</dd></div>
                <div><dt class="text-xs uppercase tracking-wider text-zinc-500">Materiais</dt><dd class="mt-1 font-display text-3xl font-bold text-white" data-count="6">0</dd></div>
                <div><dt class="text-xs uppercase tracking-wider text-zinc-500">Avaliação</dt><dd class="mt-1 font-display text-3xl font-bold text-white">4,9★</dd></div>
            </dl>
        </div>
    </div>

    <div class="absolute bottom-8 left-1/2 hidden -translate-x-1/2 flex-col items-center gap-2 text-xs text-zinc-500 md:flex">
        <span>role para descobrir</span>
        <span class="h-10 w-px animate-pulse bg-gradient-to-b from-neon to-transparent"></span>
    </div>
</section>

{{-- ===================== Categorias ===================== --}}
<section class="container-x mt-32">
    <div data-reveal class="flex flex-wrap items-end justify-between gap-6">
        <div>
            <p class="label !text-neon">Categorias</p>
            <h2 class="text-4xl font-bold sm:text-5xl">O que vamos imprimir hoje?</h2>
        </div>
        <a href="{{ route('products.index') }}" class="btn-ghost">Ver tudo</a>
    </div>

    <div data-reveal data-stagger class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        @foreach($categories as $cat)
            <a href="{{ route('categories.show', $cat) }}" class="tilt group relative flex min-h-56 flex-col justify-between overflow-hidden rounded-3xl border border-white/10 p-6 transition hover:border-white/30"
               style="background: linear-gradient(160deg, {{ $cat->color }}26, transparent 60%)">
                <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full blur-3xl transition duration-700 group-hover:scale-150" style="background: {{ $cat->color }}40"></div>
                <span class="relative font-display text-5xl font-bold text-white/10 transition group-hover:text-white/25">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                <div class="relative">
                    <h3 class="text-xl font-semibold">{{ $cat->name }}</h3>
                    <p class="mt-1 text-sm text-zinc-400">{{ $cat->products_count }} {{ Str::plural('produto', $cat->products_count) }}</p>
                </div>
                <div class="tilt-glare pointer-events-none absolute inset-0"></div>
            </a>
        @endforeach
    </div>
</section>

{{-- ===================== Destaques ===================== --}}
@if($featured->isNotEmpty())
<section class="container-x mt-32">
    <div data-reveal>
        <p class="label !text-plasma">Mais desejados</p>
        <h2 class="text-4xl font-bold sm:text-5xl">Destaques do estúdio</h2>
    </div>
    <div data-reveal data-stagger class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($featured as $product)
            <x-product-card :product="$product" />
        @endforeach
    </div>
</section>
@endif

{{-- ===================== Como funciona ===================== --}}
<section id="como-funciona" class="container-x mt-32">
    <div class="glass relative overflow-hidden p-8 sm:p-14">
        <div class="absolute -right-20 -top-20 h-72 w-72 rounded-full bg-plasma/20 blur-[100px]" data-parallax="-30"></div>
        <div data-reveal class="relative max-w-xl">
            <p class="label !text-flare">Do arquivo à sua porta</p>
            <h2 class="text-4xl font-bold sm:text-5xl">Como funciona</h2>
        </div>
        <ol data-reveal data-stagger class="relative mt-14 grid gap-10 md:grid-cols-4">
            @foreach([
                ['Escolha', 'Navegue pelo catálogo e gire cada peça em 3D antes de comprar.'],
                ['Pague com segurança', 'Pix, cartão em até 12x ou boleto pelo Mercado Pago.'],
                ['Imprimimos', 'Sua peça entra na fila de produção e é revisada à mão.'],
                ['Receba', 'Embalagem protegida e rastreio enviado por e-mail.'],
            ] as [$t, $d])
                <li class="relative">
                    <span class="font-display text-sm font-bold text-gradient">0{{ $loop->iteration }}</span>
                    <div class="my-4 h-px bg-gradient-to-r from-white/20 to-transparent"></div>
                    <h3 class="text-lg font-semibold">{{ $t }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-zinc-400">{{ $d }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>

{{-- ===================== Novidades ===================== --}}
@if($latest->isNotEmpty())
<section class="container-x mt-32">
    <div data-reveal class="flex items-end justify-between">
        <div>
            <p class="label !text-neon">Acabaram de sair da impressora</p>
            <h2 class="text-4xl font-bold sm:text-5xl">Novidades</h2>
        </div>
    </div>
    <div data-reveal data-stagger class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($latest as $product)
            <x-product-card :product="$product" />
        @endforeach
    </div>
</section>
@endif

{{-- ===================== CTA ===================== --}}
<section class="container-x mt-32">
    <div data-reveal class="relative overflow-hidden rounded-[2.5rem] bg-gradient-to-br from-neon via-plasma to-flare p-[1px]">
        <div class="relative rounded-[2.5rem] bg-ink/90 px-8 py-16 text-center sm:px-16">
            <div class="grid-bg absolute inset-0"></div>
            <h2 class="relative mx-auto max-w-2xl text-4xl font-bold sm:text-5xl">Frete grátis acima de <span class="text-gradient">{{ money(config('services.loja.frete_gratis_acima')) }}</span></h2>
            <p class="relative mx-auto mt-4 max-w-lg text-zinc-400">Crie sua conta, acompanhe a produção dos seus pedidos em tempo real e receba novidades em primeira mão.</p>
            <a href="{{ route('register') }}" data-magnetic class="btn-primary relative mt-8 !px-8 !py-4">Criar minha conta</a>
        </div>
    </div>
</section>
@endsection
