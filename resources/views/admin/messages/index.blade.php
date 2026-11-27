@extends('layouts.admin')

@section('title', 'Mensagens')

@section('content')
<form class="mb-6 flex flex-wrap gap-3">
    <input name="q" value="{{ request('q') }}" placeholder="Nome, e-mail ou assunto" class="input max-w-xs">
    <select name="filtro" class="input max-w-[12rem]">
        <option value="">Todas</option>
        <option value="nao-lidas" @selected(request('filtro') === 'nao-lidas')>Não lidas</option>
    </select>
    <button class="btn-ghost">Filtrar</button>
</form>

<div class="glass overflow-hidden !rounded-2xl">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-white/5 bg-white/[0.02] text-xs uppercase tracking-wider text-zinc-500">
                <tr><th class="px-6 py-3">De</th><th class="px-6 py-3">Assunto</th><th class="px-6 py-3">Produto</th><th class="px-6 py-3">Recebida</th></tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($messages as $m)
                    <tr class="cursor-pointer hover:bg-white/[0.03] {{ $m->read_at ? 'text-zinc-400' : '' }}" onclick="location.href='{{ route('admin.messages.show', $m) }}'">
                        <td class="px-6 py-4">
                            <p class="flex items-center gap-2 {{ $m->read_at ? '' : 'font-semibold text-white' }}">
                                @unless($m->read_at)<span class="h-2 w-2 rounded-full bg-neon" title="Não lida"></span>@endunless
                                {{ $m->name }}
                            </p>
                            <p class="text-xs text-zinc-500">{{ $m->email }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.messages.show', $m) }}" class="{{ $m->read_at ? '' : 'font-semibold text-white' }}">{{ $m->subject }}</a>
                            <p class="max-w-md truncate text-xs text-zinc-500">{{ $m->message }}</p>
                        </td>
                        <td class="px-6 py-4">{{ $m->product?->name ?? '—' }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-zinc-500">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-10 text-center text-zinc-500">Nenhuma mensagem ainda.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $messages->links() }}
@endsection
