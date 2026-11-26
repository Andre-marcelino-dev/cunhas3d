<div class="overflow-x-auto">
    <table class="w-full text-left text-sm">
        <thead class="border-y border-white/5 bg-white/[0.02] text-xs uppercase tracking-wider text-zinc-500">
            <tr><th class="px-6 py-3">Pedido</th><th class="px-6 py-3">Cliente</th><th class="px-6 py-3">Data</th><th class="px-6 py-3">Status</th><th class="px-6 py-3 text-right">Total</th></tr>
        </thead>
        <tbody class="divide-y divide-white/5">
            @forelse($orders as $order)
                <tr class="transition hover:bg-white/[0.03]">
                    <td class="px-6 py-4"><a href="{{ route('admin.orders.show', $order) }}" class="font-mono font-semibold text-neon hover:underline">{{ $order->code }}</a></td>
                    <td class="px-6 py-4"><p class="text-white">{{ $order->name }}</p><p class="text-xs text-zinc-500">{{ $order->email }}</p></td>
                    <td class="px-6 py-4 text-zinc-400">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-6 py-4"><x-status-badge :order="$order" /></td>
                    <td class="px-6 py-4 text-right font-semibold text-white">{{ money($order->total) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-6 py-10 text-center text-zinc-500">Nenhum pedido encontrado.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
