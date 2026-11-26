<?php

namespace App\Services\Api;

use App\Models\Order;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
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
    public function __construct(private OrderService $orders) {}

    /**
     * Itens vêm como [{produto_id, variacao_id?, quantidade}].
     * Produtos com variações exigem variacao_id (sem ela a linha é ignorada).
     *
     * @return Collection itens no mesmo formato de CartService::resolve()
     */
    public function itens(array $itens): Collection
    {
        $quantidades = collect($itens)
            ->groupBy(fn ($i) => empty($i['variacao_id']) ? (string) $i['produto_id'] : "{$i['produto_id']}-{$i['variacao_id']}")
            ->map(fn ($linhas) => (int) $linhas->sum('quantidade'));

        return CartService::resolve($quantidades->all());
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
            $this->orders->reserveStock($linhas, 'itens');

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

            $this->orders->createItems($pedido, $linhas);

            $pedido->histories()->create(['status' => Order::STATUS_PENDING, 'note' => 'Pedido criado pelo app']);

            return $pedido;
        });
    }
}
