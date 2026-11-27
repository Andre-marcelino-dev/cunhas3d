@extends('layouts.admin')

@section('title', 'Notificações do app')

@section('content')
@unless($configured)
    <div class="mb-6 rounded-2xl border border-amber-400/30 bg-amber-400/10 p-4 text-sm text-amber-200">
        As notificações ainda não estão configuradas. Coloque as linhas <code>VAPID_PUBLIC_KEY</code> e <code>VAPID_PRIVATE_KEY</code> no <code>.env</code> do servidor.
    </div>
@endunless

<div class="grid gap-6 xl:grid-cols-3">
    <div class="glass !rounded-2xl p-6 xl:col-span-2"
         x-data="{ title: @js(old('title', '')), body: @js(old('body', '')) }">
        <h2 class="text-lg font-semibold">Mandar novidade</h2>
        <p class="mt-1 text-sm text-zinc-400">Aparece no celular de todos que ativaram as notificações no app, com o som do aparelho.</p>

        <form method="POST" action="{{ route('admin.push.store') }}" class="mt-6 space-y-5"
              onsubmit="return confirm(@js('Mandar esta notificação para '.$devices.' '.($devices === 1 ? 'aparelho' : 'aparelhos').'?'))">
            @csrf
            <div>
                <label class="label" for="title">Título <span class="text-zinc-500" x-text="'(' + title.length + '/60)'"></span></label>
                <input id="title" name="title" x-model="title" maxlength="60" required class="input" placeholder="Ex.: Promoção de fim de semana 🔥">
                @error('title')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="body">Mensagem <span class="text-zinc-500" x-text="'(' + body.length + '/200)'"></span></label>
                <textarea id="body" name="body" x-model="body" maxlength="200" rows="3" required class="input" placeholder="Ex.: 15% de desconto em todas as luminárias até domingo."></textarea>
                @error('body')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="product_id">Ao tocar, abrir (opcional)</label>
                <select id="product_id" name="product_id" class="input">
                    <option value="">Tela inicial do app</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" @selected(old('product_id') == $p->id)>Produto: {{ $p->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Prévia de como aparece no celular --}}
            <div>
                <p class="label">Prévia</p>
                <div class="flex items-start gap-3 rounded-2xl bg-zinc-200 p-3 text-zinc-900 sm:max-w-sm">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-ink font-display text-xs font-bold text-neon">3D</span>
                    <div class="min-w-0">
                        <p class="text-[11px] text-zinc-500">Cunhas 3D · agora</p>
                        <p class="truncate text-sm font-semibold" x-text="title || 'Título da notificação'"></p>
                        <p class="line-clamp-2 text-sm text-zinc-700" x-text="body || 'Mensagem que o cliente vai ler.'"></p>
                    </div>
                </div>
            </div>

            <button class="btn-primary" @disabled(! $configured || ! $devices)>Enviar para {{ $devices }} {{ $devices === 1 ? 'aparelho' : 'aparelhos' }}</button>
        </form>
    </div>

    <div class="space-y-6">
        <div class="glass !rounded-2xl p-6">
            <h2 class="text-lg font-semibold">Quem recebe</h2>
            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-xl bg-white/[0.03] p-3"><dt class="text-zinc-500">Aparelhos</dt><dd class="font-display text-2xl font-bold text-white">{{ $devices }}</dd></div>
                <div class="rounded-xl bg-white/[0.03] p-3"><dt class="text-zinc-500">Clientes logados</dt><dd class="font-display text-2xl font-bold text-white">{{ $customers }}</dd></div>
            </dl>
            @if($subscriptions->isNotEmpty())
                <ul class="mt-4 divide-y divide-white/5 text-sm">
                    @foreach($subscriptions as $s)
                        @php
                            $ua = (string) $s->user_agent;
                            $device = match (true) {
                                str_contains($ua, 'Android') => '📱 Android',
                                (bool) preg_match('/iPhone|iPad/', $ua) => '📱 iPhone',
                                str_contains($ua, 'Windows') => '💻 Windows',
                                str_contains($ua, 'Mac OS') => '💻 Mac',
                                default => '❔ Outro',
                            };
                            $browser = match (true) {
                                str_contains($ua, 'Edg/') => 'Edge',
                                str_contains($ua, 'SamsungBrowser') => 'Samsung',
                                str_contains($ua, 'Firefox') => 'Firefox',
                                str_contains($ua, 'Chrome') => 'Chrome',
                                str_contains($ua, 'Safari') => 'Safari',
                                default => '',
                            };
                        @endphp
                        <li class="py-2.5">
                            <p class="text-white">{{ $device }} <span class="text-zinc-500">{{ $browser }}</span></p>
                            <p class="text-xs text-zinc-500">
                                {{ $s->user?->name ?? 'Sem login' }} · ativou {{ $s->created_at->format('d/m H:i') }}
                                · último envio {{ $s->last_sent_at?->format('d/m H:i') ?? 'nenhum' }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif
            <p class="mt-4 text-xs leading-relaxed text-zinc-500">O cliente ativa em <em>Configurações → Notificações</em> no app. Avisos automáticos: pedido pago, em produção, enviado e entregue (só para o dono do pedido) e produto novo (para todos, na primeira vez que o produto fica ativo).</p>
        </div>

        <div class="glass !rounded-2xl p-6">
            <h2 class="text-lg font-semibold">Últimos envios</h2>
            <ul class="mt-4 space-y-3 text-sm">
                @forelse($history as $h)
                    <li class="rounded-xl bg-white/[0.03] p-3">
                        <p class="font-semibold text-white">{{ $h->title }}</p>
                        <p class="line-clamp-2 text-zinc-400">{{ $h->body }}</p>
                        <p class="mt-1 text-xs text-zinc-500">{{ $h->created_at->format('d/m H:i') }} · {{ $h->kind === 'produto' ? 'automático (produto novo)' : 'novidade' }} · {{ $h->sent }} {{ $h->sent === 1 ? 'entregue' : 'entregues' }}@if($h->failed) · {{ $h->failed }} falharam @endif</p>
                    </li>
                @empty
                    <li class="text-zinc-500">Nenhuma notificação enviada ainda.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
