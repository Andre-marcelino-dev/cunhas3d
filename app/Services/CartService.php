<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Carrinho guardado na sessão: [product_id => quantidade].
 * Funciona para visitantes e continua após o login (a sessão é mantida).
 * Preços SEMPRE vêm do banco, nunca do navegador.
 */
class CartService
{
    private const KEY = 'cart';

    public function raw(): array
    {
        return session(self::KEY, []);
    }

    public function add(Product $product, int $quantity = 1): void
    {
        $cart = $this->raw();
        $current = $cart[$product->id] ?? 0;
        $cart[$product->id] = min($current + max(1, $quantity), max(1, $product->stock));
        session([self::KEY => $cart]);
    }

    public function update(Product $product, int $quantity): void
    {
        $cart = $this->raw();

        if ($quantity <= 0) {
            unset($cart[$product->id]);
        } else {
            $cart[$product->id] = min($quantity, max(1, $product->stock));
        }

        session([self::KEY => $cart]);
    }

    public function remove(Product $product): void
    {
        $this->update($product, 0);
    }

    public function clear(): void
    {
        session()->forget(self::KEY);
    }

    public function count(): int
    {
        return (int) array_sum($this->raw());
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * Itens com os produtos carregados do banco.
     *
     * @return Collection<int, object{product: Product, quantity: int, total: float}>
     */
    public function items(): Collection
    {
        $cart = $this->raw();

        if (empty($cart)) {
            return collect();
        }

        return Product::active()
            ->whereIn('id', array_keys($cart))
            ->with('category')
            ->get()
            ->map(fn (Product $product) => (object) [
                'product' => $product,
                'quantity' => (int) $cart[$product->id],
                'total' => round($product->final_price * $cart[$product->id], 2),
            ])
            ->values();
    }

    public function subtotal(): float
    {
        return round($this->items()->sum('total'), 2);
    }

    public function shipping(?float $subtotal = null): float
    {
        $subtotal ??= $this->subtotal();

        if ($subtotal <= 0) {
            return 0;
        }

        return $subtotal >= config('services.loja.frete_gratis_acima')
            ? 0.0
            : (float) config('services.loja.frete_fixo');
    }

    public function total(): float
    {
        $subtotal = $this->subtotal();

        return round($subtotal + $this->shipping($subtotal), 2);
    }
}
