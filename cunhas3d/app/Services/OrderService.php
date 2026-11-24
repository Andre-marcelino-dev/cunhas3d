<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
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
            // Trava os produtos para evitar vender o mesmo item duas vezes
            $products = Product::whereIn('id', $items->pluck('product.id'))->lockForUpdate()->get()->keyBy('id');

            foreach ($items as $item) {
                $product = $products[$item->product->id];
                if ($product->stock < $item->quantity) {
                    throw ValidationException::withMessages([
                        'cart' => "Estoque insuficiente para \"{$product->name}\". Disponível: {$product->stock}.",
                    ]);
                }
            }

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

            foreach ($items as $item) {
                $order->items()->create([
                    'product_id' => $item->product->id,
                    'name' => $item->product->name,
                    'sku' => $item->product->sku,
                    'price' => $item->product->final_price,
                    'quantity' => $item->quantity,
                    'total' => $item->total,
                ]);

                $products[$item->product->id]->decrement('stock', $item->quantity);
            }

            $order->histories()->create(['status' => Order::STATUS_PENDING, 'note' => 'Pedido criado']);

            // Salva o endereço no perfil para a próxima compra
            $user->fill(collect($address)->only(['phone', 'cpf', 'cep', 'address', 'number', 'complement', 'district', 'city', 'state'])->all())->save();

            $this->cart->clear();

            return $order;
        });
    }
}
