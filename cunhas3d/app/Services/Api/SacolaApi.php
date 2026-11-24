<?php

namespace App\Services\Api;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Sacola do app. No app os itens ficam guardados no aparelho e chegam aqui como
 * [{produto_id, quantidade}]. Preços e frete SEMPRE são calculados no servidor,
 * com as mesmas regras do carrinho do site (CartService / OrderService).
 */
class SacolaApi
{
    /** @return Collection<int, object{product: Product, quantity: int, total: float}> */
    public function itens(array $itens): Collection
    {
        $quantidades = collect($itens)
            ->groupBy('produto_id')
            ->map(fn ($linhas) => (int) $linhas->sum('quantidade'))
            ->filter(fn ($q) => $q > 0);

        if ($quantidades->isEmpty()) {
            return collect();
        }

        return Product::active()->with('category')
            ->whereIn('id', $quantidades->keys())
            ->get()
            ->map(fn (Product $p) => (object) [
                'product' => $p,
                'quantity' => $quantidades[$p->id],
                'total' => round($p->final_price * $quantidades[$p->id], 2),
            ])->values();
    }

    public function frete(float $subtotal): float
    {
        if ($subtotal <= 0) {
            return 0;
        }

        return $subtotal >= config('services.loja.frete_gratis_acima') ? 0.0 : (float) config('services.loja.frete_fixo');
    }

    /** @return array{0: Collection, 1: float, 2: float, 3: float} [linhas, subtotal, frete, total] */
    public function resumo(array $itens): array
    {
        $linhas = $this->itens($itens);
        $subtotal = round($linhas->sum('total'), 2);
        $frete = $this->frete($subtotal);

        return [$linhas, $subtotal, $frete, round($subtotal + $frete, 2)];
    }

    /** Cria o pedido reservando o estoque (mesma regra do site). */
    public function criarPedido(User $user, array $itens, array $entrega): Order
    {
        $linhas = $this->itens($itens);

        if ($linhas->isEmpty()) {
            throw ValidationException::withMessages(['itens' => 'Sua sacola está vazia.']);
        }

        return DB::transaction(function () use ($user, $linhas, $entrega) {
            $produtos = Product::whereIn('id', $linhas->pluck('product.id'))->lockForUpdate()->get()->keyBy('id');

            foreach ($linhas as $linha) {
                $produto = $produtos[$linha->product->id];
                if ($produto->stock < $linha->quantity) {
                    throw ValidationException::withMessages([
                        'itens' => "Estoque insuficiente para \"{$produto->name}\". Disponível: {$produto->stock}.",
                    ]);
                }
            }

            $subtotal = round($linhas->sum('total'), 2);
            $frete = $this->frete($subtotal);

            $pedido = Order::create([
                ...$entrega,
                'code' => Order::generateCode(),
                'user_id' => $user->id,
                'status' => Order::STATUS_PENDING,
                'subtotal' => $subtotal,
                'shipping' => $frete,
                'total' => round($subtotal + $frete, 2),
            ]);

            foreach ($linhas as $linha) {
                $pedido->items()->create([
                    'product_id' => $linha->product->id,
                    'name' => $linha->product->name,
                    'sku' => $linha->product->sku,
                    'price' => $linha->product->final_price,
                    'quantity' => $linha->quantity,
                    'total' => $linha->total,
                ]);

                $produtos[$linha->product->id]->decrement('stock', $linha->quantity);
            }

            $pedido->histories()->create(['status' => Order::STATUS_PENDING, 'note' => 'Pedido criado pelo app']);

            return $pedido;
        });
    }
}
