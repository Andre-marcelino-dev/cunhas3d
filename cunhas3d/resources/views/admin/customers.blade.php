@extends('layouts.admin')

@section('title', 'Clientes')

@section('content')
<form class="mb-6 flex gap-3">
    <input name="q" value="{{ request('q') }}" placeholder="Nome ou e-mail" class="input max-w-xs">
    <button class="btn-ghost">Buscar</button>
</form>

<div class="glass overflow-hidden !rounded-2xl">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-white/5 bg-white/[0.02] text-xs uppercase tracking-wider text-zinc-500">
                <tr><th class="px-6 py-3">Cliente</th><th class="px-6 py-3">Telefone</th><th class="px-6 py-3">Cidade</th><th class="px-6 py-3">Pedidos</th><th class="px-6 py-3">Total gasto</th><th class="px-6 py-3">Cadastro</th></tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($customers as $c)
                    <tr class="hover:bg-white/[0.03]">
                        <td class="px-6 py-4"><p class="text-white">{{ $c->name }}</p><p class="text-xs text-zinc-500">{{ $c->email }}</p></td>
                        <td class="px-6 py-4 text-zinc-400">{{ $c->phone ?? '—' }}</td>
                        <td class="px-6 py-4 text-zinc-400">{{ $c->city ? $c->city.'/'.$c->state : '—' }}</td>
                        <td class="px-6 py-4"><a href="{{ route('admin.orders.index', ['q' => $c->email]) }}" class="text-neon">{{ $c->orders_count }}</a></td>
                        <td class="px-6 py-4 text-white">{{ money($c->total_spent) }}</td>
                        <td class="px-6 py-4 text-zinc-500">{{ $c->created_at->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-10 text-center text-zinc-500">Nenhum cliente ainda.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $customers->links() }}
@endsection
