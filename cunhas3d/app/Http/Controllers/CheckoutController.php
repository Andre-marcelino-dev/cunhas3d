<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CartService;
use App\Services\MercadoPagoService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use RuntimeException;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cart,
        private OrderService $orders,
        private MercadoPagoService $mercadoPago,
    ) {}

    public function index(Request $request)
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Seu carrinho está vazio.');
        }

        $subtotal = $this->cart->subtotal();

        return view('store.checkout', [
            'user' => $request->user(),
            'items' => $this->cart->items(),
            'subtotal' => $subtotal,
            'shipping' => $this->cart->shipping($subtotal),
            'total' => $this->cart->total(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:160',
            'phone' => 'required|string|max:20',
            'cpf' => 'nullable|string|max:14',
            'cep' => ['required', 'regex:/^\d{5}-?\d{3}$/'],
            'address' => 'required|string|max:160',
            'number' => 'required|string|max:20',
            'complement' => 'nullable|string|max:100',
            'district' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'state' => 'required|string|size:2',
            'notes' => 'nullable|string|max:500',
        ], [], [
            'name' => 'nome', 'phone' => 'telefone', 'address' => 'endereço', 'number' => 'número',
            'district' => 'bairro', 'city' => 'cidade', 'state' => 'estado',
        ]);

        $data['state'] = strtoupper($data['state']);

        $order = $this->orders->createFromCart($request->user(), $data);

        return redirect()->route('checkout.pay', $order);
    }

    /** Envia o cliente para o Mercado Pago (também usado para "pagar novamente") */
    public function pay(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        if (! $order->isPayable()) {
            return redirect()->route('account.order', $order);
        }

        try {
            return redirect()->away($this->mercadoPago->createPreference($order->load('items')));
        } catch (RuntimeException $e) {
            return redirect()->route('account.order', $order)->with('error', $e->getMessage());
        }
    }

    /** O Mercado Pago devolve o cliente para cá depois do pagamento */
    public function return(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        // Confirma o status direto na API (útil em localhost, onde o webhook não chega)
        if ($paymentId = $request->query('payment_id')) {
            try {
                $order = $this->mercadoPago->syncPayment($paymentId) ?? $order;
            } catch (RuntimeException) {
                // segue com o status atual; o webhook atualizará depois
            }
        }

        return view('store.checkout-result', [
            'order' => $order->fresh('items'),
            'mpStatus' => $request->query('status') ?? $request->query('collection_status'),
        ]);
    }
}
