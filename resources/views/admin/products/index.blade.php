@extends('layouts.admin')

@section('title', 'Produtos')

@section('actions')
    <a href="{{ route('admin.products.create') }}" class="btn-primary !px-4 !py-2">+ Novo produto</a>
@endsection

@section('content')
<form class="mb-6 flex flex-wrap gap-3">
    <input name="q" value="{{ request('q') }}" placeholder="Nome ou SKU" class="input max-w-xs">
    <select name="categoria" class="input max-w-xs" onchange="this.form.submit()">
        <option value="">Todas as categorias</option>
        @foreach($categories as $c)
            <option value="{{ $c->id }}" @selected(request('categoria') == $c->id)>{{ $c->name }}</option>
        @endforeach
    </select>
    <button class="btn-ghost">Filtrar</button>
</form>

<div class="glass overflow-hidden !rounded-2xl">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-white/5 bg-white/[0.02] text-xs uppercase tracking-wider text-zinc-500">
                <tr><th class="px-6 py-3">Produto</th><th class="px-6 py-3">Categoria</th><th class="px-6 py-3">Preço</th><th class="px-6 py-3">Estoque</th><th class="px-6 py-3">Status</th><th class="px-6 py-3"></th></tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($products as $product)
                    <tr class="hover:bg-white/[0.03]">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                @if($product->image_url)
                                    <img src="{{ $product->image_url }}" alt="" class="h-11 w-11 rounded-lg object-cover">
                                @else
                                    <span class="h-11 w-11 rounded-lg" style="background: radial-gradient(circle, {{ $product->color }}, transparent 75%)"></span>
                                @endif
                                <div>
                                    <p class="font-medium text-white">{{ $product->name }} @if($product->featured)<span class="text-amber-300" title="Destaque">★</span>@endif</p>
                                    <p class="text-xs text-zinc-500">{{ $product->sku }} · {{ $product->material }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-zinc-400">{{ $product->category?->name }}</td>
                        <td class="px-6 py-4">
                            <p class="text-white">{{ money($product->final_price) }}</p>
                            @if($product->discount_percent)<p class="text-xs text-zinc-500 line-through">{{ money($product->price) }}</p>@endif
                        </td>
                        <td class="px-6 py-4"><span class="{{ $product->stock <= 3 ? 'text-amber-300' : '' }} {{ $product->stock == 0 ? '!text-rose-400' : '' }}">{{ $product->stock }}</span></td>
                        <td class="px-6 py-4"><span class="badge {{ $product->active ? 'border-emerald-400/40 text-emerald-300' : 'border-zinc-500/40 text-zinc-400' }}">{{ $product->active ? 'Ativo' : 'Inativo' }}</span></td>
                        <td class="px-6 py-4">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('products.show', $product) }}" target="_blank" class="btn-ghost !px-3 !py-1.5 text-xs">Ver</a>
                                <a href="{{ route('admin.products.edit', $product) }}" class="btn-ghost !px-3 !py-1.5 text-xs">Editar</a>
                                <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Excluir este produto?')">
                                    @csrf @method('DELETE')
                                    <button class="btn-danger !px-3 !py-1.5 text-xs">Excluir</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-10 text-center text-zinc-500">Nenhum produto encontrado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $products->links() }}
@endsection
