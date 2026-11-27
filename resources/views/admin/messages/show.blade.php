@extends('layouts.admin')

@section('title', 'Mensagem')

@section('actions')
    <a href="{{ route('admin.messages.index') }}" class="btn-ghost !px-4 !py-2">← Voltar</a>
@endsection

@section('content')
@php
    $whats = $message->phone ? preg_replace('/\D/', '', $message->phone) : null;
    if ($whats && strlen($whats) <= 11) $whats = '55'.$whats;
@endphp
<div class="grid gap-6 xl:grid-cols-3">
    <div class="glass !rounded-2xl p-6 xl:col-span-2">
        <p class="text-xs text-zinc-500">{{ $message->created_at->format('d/m/Y \à\s H:i') }}</p>
        <h2 class="mt-1 text-xl font-semibold text-white">{{ $message->subject }}</h2>
        <div class="mt-6 whitespace-pre-line leading-relaxed text-zinc-300">{{ $message->message }}</div>

        <div class="mt-8 flex flex-wrap gap-3 border-t border-white/5 pt-6">
            <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: '.$message->subject) }}" class="btn-primary !px-5 !py-2.5">Responder por e-mail</a>
            @if($whats)
                <a href="https://wa.me/{{ $whats }}" target="_blank" rel="noopener" class="btn-ghost !px-5 !py-2.5">WhatsApp</a>
            @endif
            <form method="POST" action="{{ route('admin.messages.unread', $message) }}">@csrf
                <button class="btn-ghost !px-5 !py-2.5">Marcar como não lida</button>
            </form>
            <form method="POST" action="{{ route('admin.messages.destroy', $message) }}" onsubmit="return confirm('Excluir esta mensagem?')">@csrf @method('DELETE')
                <button class="rounded-full px-5 py-2.5 text-sm text-rose-300 hover:bg-rose-500/10">Excluir</button>
            </form>
        </div>
    </div>

    <div class="space-y-6">
        <div class="glass !rounded-2xl p-6 text-sm">
            <h3 class="font-semibold text-white">Remetente</h3>
            <dl class="mt-4 space-y-3">
                <div><dt class="text-zinc-500">Nome</dt><dd class="text-white">{{ $message->name }}</dd></div>
                <div><dt class="text-zinc-500">E-mail</dt><dd class="break-all text-white">{{ $message->email }}</dd></div>
                <div><dt class="text-zinc-500">Telefone</dt><dd class="text-white">{{ $message->phone ?? '—' }}</dd></div>
                <div><dt class="text-zinc-500">Conta</dt><dd class="text-white">{{ $message->user ? 'Cliente cadastrado' : 'Visitante' }}</dd></div>
            </dl>
        </div>
        @if($message->product)
            <div class="glass !rounded-2xl p-6 text-sm">
                <h3 class="font-semibold text-white">Produto</h3>
                <div class="mt-4 flex items-center gap-3">
                    @if($message->product->image_url)<img src="{{ $message->product->image_url }}" alt="" class="h-14 w-14 rounded-xl object-cover">@endif
                    <div>
                        <p class="text-white">{{ $message->product->name }}</p>
                        <p class="text-xs text-zinc-500">{{ $message->product->sku }}</p>
                    </div>
                </div>
                <div class="mt-4 flex gap-4 text-xs">
                    <a href="{{ route('products.show', $message->product) }}" target="_blank" class="text-neon">Ver na loja ↗</a>
                    <a href="{{ route('admin.products.edit', $message->product) }}" class="text-neon">Editar</a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
