<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private CartService $cart) {}

    /**
     * Cria o pedido a partir do carrinho, recalculando tudo no servidor
     * e reservando o estoque (devolvido se o pedido for cancelado).
     */
    public function createFromCart(User $user, array $address): Order
    {
        $items = $this->cart->items();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Seu carrinho está vazio.']);
        }

        return DB::transaction(function () use ($user, $address, $items) {
            $this->reserveStock($items, 'cart');

            $subtotal = round($items->sum('total'), 2);
            $shipping = $this->cart->shipping($subtotal);

            $order = Order::create([
                ...$address,
                'code' => Order::generateCode(),
                'user_id' => $user->id,
                'status' => Order::STATUS_PENDING,
                'subtotal' => $subtotal,
                'shipping' => $shipping,
                'total' => round($subtotal + $shipping, 2),
            ]);

            $this->createItems($order, $items);

            $order->histories()->create(['status' => Order::STATUS_PENDING, 'note' => 'Pedido criado']);

            // Salva o endereço no perfil para a próxima compra
            $user->fill(collect($address)->only(['phone', 'cpf', 'cep', 'address', 'number', 'complement', 'district', 'city', 'state'])->all())->save();

            $this->cart->clear();

            return $order;
        });
    }

    /**
     * Trava produtos e variações, confere o estoque e já o desconta.
     * Deve ser chamado dentro de uma transação.
     *
     * @param  Collection  $items  itens de CartService::resolve()
     */
    public function reserveStock(Collection $items, string $errorKey): void
    {
        // Trava as linhas para evitar vender o mesmo item duas vezes
        $products = Product::whereIn('id', $items->pluck('product.id')->unique())->lockForUpdate()->get()->keyBy('id');
        $variantIds = $items->pluck('variant.id')->filter()->unique();
        $variants = $variantIds->isEmpty()
            ? collect()
            : ProductVariant::whereIn('id', $variantIds)->lockForUpdate()->get()->keyBy('id');

        foreach ($items as $item) {
            $available = $item->variant
                ? $variants[$item->variant->id]->stock
                : $products[$item->product->id]->stock;

            if ($available < $item->quantity) {
                throw ValidationException::withMessages([
                    $errorKey => "Estoque insuficiente para \"{$item->name}\". Disponível: {$available}.",
                ]);
            }
        }

        foreach ($items as $item) {
            $products[$item->product->id]->decrement('stock', $item->quantity);
            if ($item->variant) {
                $variants[$item->variant->id]->decrement('stock', $item->quantity);
            }
        }
    }

    /** Grava os itens no pedido. O nome inclui a variação, ex.: "Anjo Luminária — Com vela LED". */
    public function createItems(Order $order, Collection $items): void
    {
        foreach ($items as $item) {
            $order->items()->create([
                'product_id' => $item->product->id,
                'product_variant_id' => $item->variant?->id,
                'name' => $item->name,
                'sku' => $item->product->sku,
                'price' => $item->unit_price,
                'quantity' => $item->quantity,
                'total' => $item->total,
            ]);
        }
    }
}
