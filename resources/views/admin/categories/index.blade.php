@extends('layouts.admin')

@section('title', 'Categorias')

@section('actions')
    <a href="{{ route('admin.categories.create') }}" class="btn-primary !px-4 !py-2">+ Nova categoria</a>
@endsection

@section('content')
<div class="glass overflow-x-auto !rounded-2xl">
    <table class="w-full min-w-[560px] text-left text-sm">
        <thead class="border-b border-white/5 bg-white/[0.02] text-xs uppercase tracking-wider text-zinc-500">
            <tr><th class="px-6 py-3">Categoria</th><th class="px-6 py-3">Categoria pai</th><th class="px-6 py-3">Produtos</th><th class="px-6 py-3">Ordem</th><th class="px-6 py-3">Status</th><th class="px-6 py-3"></th></tr>
        </thead>
        <tbody class="divide-y divide-white/5">
            @forelse($categories as $category)
                <tr class="hover:bg-white/[0.03]">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <span class="h-8 w-8 rounded-lg" style="background: linear-gradient(135deg, {{ $category->color }}, transparent)"></span>
                            <div><p class="font-medium text-white">{{ $category->name }}</p><p class="text-xs text-zinc-500">/{{ $category->slug }}</p></div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-zinc-400">{{ $category->parent?->name ?? '—' }}</td>
                    <td class="px-6 py-4">{{ $category->products_count }}</td>
                    <td class="px-6 py-4 text-zinc-400">{{ $category->position }}</td>
                    <td class="px-6 py-4">
                        <span class="badge {{ $category->active ? 'border-emerald-400/40 text-emerald-300' : 'border-zinc-500/40 text-zinc-400' }}">{{ $category->active ? 'Ativa' : 'Inativa' }}</span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('admin.categories.edit', $category) }}" class="btn-ghost !px-3 !py-1.5 text-xs">Editar</a>
                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Excluir esta categoria?')">
                                @csrf @method('DELETE')
                                <button class="btn-danger !px-3 !py-1.5 text-xs">Excluir</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-6 py-10 text-center text-zinc-500">Nenhuma categoria cadastrada.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
