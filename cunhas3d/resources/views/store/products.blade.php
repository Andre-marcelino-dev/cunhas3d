@extends('layouts.app')

@section('title', $category?->name ?? 'Loja')

@section('content')
<section class="container-x pt-36">
    <div data-reveal>
        <nav class="text-xs text-zinc-500"><a href="{{ route('home') }}" class="hover:text-white">Início</a> / <a href="{{ route('products.index') }}" class="hover:text-white">Loja</a>@if($category) / <span class="text-zinc-300">{{ $category->name }}</span>@endif</nav>
        <h1 class="mt-4 text-5xl font-bold sm:text-6xl">{{ $category?->name ?? 'Todos os produtos' }}</h1>
        @if($category?->description)
            <p class="mt-3 max-w-xl text-zinc-400">{{ $category->description }}</p>
        @endif
        @if(request('q'))
            <p class="mt-3 text-zinc-400">Resultados para "<span class="text-white">{{ request('q') }}</span>"</p>
        @endif
    </div>

    {{-- Filtros --}}
    <div class="mt-10 flex flex-wrap gap-2">
        <a href="{{ route('products.index', request()->only('q')) }}" class="chip {{ ! $category ? 'chip-active' : '' }}">Todos</a>
        @foreach($categories as $cat)
            <a href="{{ route('categories.show', [$cat, ...request()->only('q')]) }}" class="chip {{ $category?->id === $cat->id ? 'chip-active' : '' }}">{{ $cat->name }}</a>
        @endforeach
    </div>

    <form class="glass mt-6 grid gap-3 !rounded-2xl p-3 sm:grid-cols-4" x-data @change="$el.submit()">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar..." class="input">
        <select name="material" class="input">
            <option value="">Todos os materiais</option>
            @foreach($materials as $m)
                <option value="{{ $m }}" @selected(request('material') === $m)>{{ $m }}</option>
            @endforeach
        </select>
        <select name="max" class="input">
            <option value="">Qualquer preço</option>
            @foreach([50, 100, 200] as $max)
                <option value="{{ $max }}" @selected(request('max') == $max)>Até {{ money($max) }}</option>
            @endforeach
        </select>
        <select name="ordem" class="input">
            <option value="">Relevância</option>
            <option value="menor-preco" @selected(request('ordem') === 'menor-preco')>Menor preço</option>
            <option value="maior-preco" @selected(request('ordem') === 'maior-preco')>Maior preço</option>
            <option value="nome" @selected(request('ordem') === 'nome')>Nome (A-Z)</option>
        </select>
    </form>

    @if($products->isEmpty())
        <div class="glass mt-12 p-16 text-center">
            <p class="text-xl font-semibold text-white">Nenhum produto encontrado</p>
            <p class="mt-2 text-zinc-400">Tente outros filtros ou <a class="text-neon" href="{{ route('products.index') }}">veja todos os produtos</a>.</p>
        </div>
    @else
        <p class="mt-8 text-sm text-zinc-500">{{ $products->total() }} {{ Str::plural('produto', $products->total()) }}</p>
        <div data-reveal data-stagger class="mt-4 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach($products as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>
        {{ $products->links() }}
    @endif
</section>
@endsection
