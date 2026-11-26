<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

/**
 * Carrinho guardado na sessão: [chave => quantidade].
 * A chave é o id do produto ("12") ou produto + variação ("12-5"),
 * assim "com vela LED" e "sem vela" do mesmo produto ficam em linhas separadas.
 * Funciona para visitantes e continua após o login (a sessão é mantida).
 * Preços SEMPRE vêm do banco, nunca do navegador.
 */
class CartService
{
    private const KEY = 'cart';

    /** Formato aceito nas rotas do carrinho */
    public const KEY_PATTERN = '[0-9]+(-[0-9]+)?';

    public static function key(Product $product, ?ProductVariant $variant = null): string
    {
        return $variant ? "{$product->id}-{$variant->id}" : (string) $product->id;
    }

    public function raw(): array
    {
        return session(self::KEY, []);
    }

    public function has(string $key): bool
    {
        return isset($this->raw()[$key]);
    }

    public function add(Product $product, int $quantity = 1, ?ProductVariant $variant = null): void
    {
        $key = self::key($product, $variant);
        $stock = $variant ? $variant->stock : $product->stock;

        $cart = $this->raw();
        $current = $cart[$key] ?? 0;
        $cart[$key] = min($current + max(1, $quantity), max(1, $stock));
        session([self::KEY => $cart]);
    }

    public function update(string $key, int $quantity): void
    {
        $cart = $this->raw();

        if ($quantity <= 0) {
            unset($cart[$key]);
        } elseif (isset($cart[$key])) {
            $item = $this->items()->firstWhere('key', $key);
            $cart[$key] = min($quantity, max(1, $item?->stock ?? 1));
        }

        session([self::KEY => $cart]);
    }

    public function remove(string $key): void
    {
        $this->update($key, 0);
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
     * Itens com os produtos (e variações) carregados do banco.
     *
     * @return Collection<int, object{key: string, product: Product, variant: ?ProductVariant, name: string, variant_name: ?string, unit_price: float, quantity: int, total: float, stock: int, image_url: ?string}>
     */
    public function items(): Collection
    {
        return self::resolve($this->raw());
    }

    /**
     * Transforma [chave => quantidade] em itens com preços do banco.
     * Linhas inválidas são descartadas: produto inativo, variação de outro produto
     * ou produto com variações sem a variação escolhida.
     * Também usado pela sacola do app (SacolaApi).
     */
    public static function resolve(array $quantities): Collection
    {
        $lines = collect($quantities)
            ->map(fn ($quantity, $key) => [...array_pad(array_map('intval', explode('-', (string) $key, 2)), 2, null), (int) $quantity])
            ->filter(fn ($line) => $line[2] > 0);

        if ($lines->isEmpty()) {
            return collect();
        }

        $products = Product::active()
            ->whereIn('id', $lines->pluck(0)->unique())
            ->with(['category', 'variants'])
            ->get()
            ->keyBy('id');

        return $lines->map(function ($line) use ($products) {
            [$productId, $variantId, $quantity] = $line;
            $product = $products[$productId] ?? null;

            if (! $product) {
                return null;
            }

            $variant = $variantId ? $product->variants->firstWhere('id', $variantId) : null;

            if ($variantId && ! $variant) {
                return null;
            }
            if (! $variant && $product->hasVariants()) {
                return null;
            }
            $variant?->setRelation('product', $product);

            $unitPrice = $variant ? $variant->final_price : $product->final_price;

            return (object) [
                'key' => self::key($product, $variant),
                'product' => $product,
                'variant' => $variant,
                'name' => $variant ? "{$product->name} — {$variant->name}" : $product->name,
                'variant_name' => $variant?->name,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'total' => round($unitPrice * $quantity, 2),
                'stock' => (int) ($variant ? $variant->stock : $product->stock),
                'image_url' => $variant ? $variant->image_url : $product->image_url,
            ];
        })->filter()->values();
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
