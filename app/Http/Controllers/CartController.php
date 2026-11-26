<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function index()
    {
        $subtotal = $this->cart->subtotal();

        return view('store.cart', [
            'items' => $this->cart->items(),
            'subtotal' => $subtotal,
            'shipping' => $this->cart->shipping($subtotal),
            'total' => $this->cart->total(),
            'freeShippingFrom' => config('services.loja.frete_gratis_acima'),
        ]);
    }

    public function add(Request $request, Product $product)
    {
        abort_unless($product->active, 404);

        // Produto com variações: o cliente precisa escolher uma (ex.: com ou sem vela LED)
        $variant = null;
        if ($product->hasVariants()) {
            $variant = $product->variants->firstWhere('id', (int) $request->input('variant_id'));

            if (! $variant) {
                return $this->fail($request, 'Escolha uma opção antes de adicionar ao carrinho.');
            }
        }

        if (! ($variant ? $variant->inStock() : $product->inStock())) {
            return $this->fail($request, 'Produto sem estoque no momento.');
        }

        // "Comprar agora" com o item já no carrinho: só leva ao carrinho, sem somar de novo
        if ($request->boolean('buy_now') && $this->cart->has(CartService::key($product, $variant))) {
            return redirect()->route('cart.index');
        }

        $this->cart->add($product, (int) $request->input('quantity', 1), $variant);

        $name = $variant ? "{$product->name} — {$variant->name}" : $product->name;

        if ($request->wantsJson()) {
            return response()->json(['count' => $this->cart->count(), 'message' => 'Produto adicionado ao carrinho!']);
        }

        return $request->boolean('buy_now')
            ? redirect()->route('cart.index')
            : back()->with('success', "\"{$name}\" foi adicionado ao carrinho!");
    }

    public function update(Request $request, string $key)
    {
        $this->cart->update($key, (int) $request->validate(['quantity' => 'required|integer|min:0|max:999'])['quantity']);

        return redirect()->route('cart.index');
    }

    public function remove(string $key)
    {
        $this->cart->remove($key);

        return redirect()->route('cart.index')->with('success', 'Item removido do carrinho.');
    }

    private function fail(Request $request, string $message)
    {
        return $request->wantsJson()
            ? response()->json(['message' => $message], 422)
            : back()->with('error', $message);
    }
}
