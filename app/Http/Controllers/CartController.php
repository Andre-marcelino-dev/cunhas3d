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

        if (! $product->inStock()) {
            return back()->with('error', 'Produto sem estoque no momento.');
        }

        // "Comprar agora" com o produto já no carrinho: só leva ao carrinho, sem somar de novo
        if ($request->boolean('buy_now') && isset($this->cart->raw()[$product->id])) {
            return redirect()->route('cart.index');
        }

        $this->cart->add($product, (int) $request->input('quantity', 1));

        if ($request->wantsJson()) {
            return response()->json(['count' => $this->cart->count(), 'message' => 'Produto adicionado ao carrinho!']);
        }

        return $request->boolean('buy_now')
            ? redirect()->route('cart.index')
            : back()->with('success', "\"{$product->name}\" foi adicionado ao carrinho!");
    }

    public function update(Request $request, Product $product)
    {
        $this->cart->update($product, (int) $request->validate(['quantity' => 'required|integer|min:0|max:999'])['quantity']);

        return redirect()->route('cart.index');
    }

    public function remove(Product $product)
    {
        $this->cart->remove($product);

        return redirect()->route('cart.index')->with('success', 'Item removido do carrinho.');
    }
}
