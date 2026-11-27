@extends('layouts.admin')

@section('title', 'Pedido '.$order->code)

@section('actions')
    <a href="{{ route('admin.orders.index') }}" class="btn-ghost !px-4 !py-2">← Voltar</a>
@endsection

@section('content')
<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <div class="glass !rounded-2xl p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-semibold">Itens do pedido</h2>
                <x-status-badge :order="$order" />
            </div>
            <div class="mt-4 overflow-x-auto"><table class="w-full min-w-[480px] text-sm">
                <thead class="text-left text-xs uppercase tracking-wider text-zinc-500"><tr><th class="py-2">Produto</th><th>Qtd</th><th>Unit.</th><th class="text-right">Total</th></tr></thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($order->items as $item)
                        <tr>
                            <td class="py-3"><p class="text-white">{{ $item->name }}</p><p class="text-xs text-zinc-500">{{ $item->sku }}</p></td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ money($item->price) }}</td>
                            <td class="text-right text-white">{{ money($item->total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
            <dl class="ml-auto mt-4 max-w-xs space-y-1.5 text-sm">
                <div class="flex justify-between"><dt class="text-zinc-400">Subtotal</dt><dd>{{ money($order->subtotal) }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-400">Frete</dt><dd>{{ money($order->shipping) }}</dd></div>
                <div class="flex justify-between text-base font-bold text-white"><dt>Total</dt><dd>{{ money($order->total) }}</dd></div>
            </dl>
            @if($order->notes)
                <div class="mt-6 rounded-xl border border-amber-400/30 bg-amber-400/10 p-4 text-sm"><strong class="text-amber-200">Observações do cliente:</strong><br>{{ $order->notes }}</div>
            @endif
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div class="glass !rounded-2xl p-6 text-sm">
                <h2 class="text-lg font-semibold">Cliente e entrega</h2>
                <p class="mt-3 leading-relaxed text-zinc-300">
                    <strong class="text-white">{{ $order->name }}</strong><br>
                    {{ $order->email }} · {{ $order->phone }}<br>
                    @if($order->cpf) CPF {{ $order->cpf }}<br>@endif
                    {{ $order->address }}, {{ $order->number }} {{ $order->complement }}<br>
                    {{ $order->district }} · {{ $order->city }}/{{ $order->state }} · CEP {{ $order->cep }}
                </p>
            </div>
            <div class="glass !rounded-2xl p-6 text-sm">
                <h2 class="text-lg font-semibold">Pagamento (Mercado Pago)</h2>
                <dl class="mt-3 space-y-1.5 text-zinc-300">
                    <div class="flex justify-between"><dt class="text-zinc-500">Status MP</dt><dd>{{ $order->payment_status ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-500">Método</dt><dd>{{ $order->payment_method ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-500">ID pagamento</dt><dd class="font-mono">{{ $order->mp_payment_id ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-500">Pago em</dt><dd>{{ $order->paid_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
                </dl>
                @if($order->mp_payment_id)
                    <form method="POST" action="{{ route('admin.orders.sync', $order) }}" class="mt-4">@csrf
                        <button class="btn-ghost !px-4 !py-2 text-xs">↻ Sincronizar com Mercado Pago</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="glass space-y-4 !rounded-2xl p-6">
            @csrf @method('PUT')
            <h2 class="text-lg font-semibold">Atualizar pedido</h2>
            <div>
                <label class="label">Status</label>
                <select name="status" class="input">
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" @selected($order->status === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Código de rastreio</label>
                <input name="tracking_code" value="{{ old('tracking_code', $order->tracking_code) }}" placeholder="AA123456789BR" class="input font-mono">
            </div>
            <div>
                <label class="label">Observação (aparece para o cliente)</label>
                <input name="note" placeholder="Ex.: Peça em pós-processamento" class="input">
            </div>
            <button class="btn-primary w-full">Salvar</button>
            <p class="text-xs text-zinc-500">Cancelar um pedido devolve os itens ao estoque.</p>
        </form>

        <div class="glass !rounded-2xl p-6">
            <h2 class="text-lg font-semibold">Histórico</h2>
            <ul class="mt-4 space-y-3 text-sm">
                @foreach($order->histories as $h)
                    <li class="flex gap-3"><span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-neon"></span><span><span class="text-white">{{ $h->status_label }}</span> <span class="text-zinc-500">· {{ $h->created_at->format('d/m H:i') }}</span>@if($h->note)<br><span class="text-zinc-400">{{ $h->note }}</span>@endif</span></li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
