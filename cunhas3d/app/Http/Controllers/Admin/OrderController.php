<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('user')->withCount('items')->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('q')) {
            $query->where(fn ($q) => $q->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        return view('admin.orders.index', [
            'orders' => $query->paginate(20)->withQueryString(),
            'statuses' => Order::STATUSES,
        ]);
    }

    public function show(Order $order)
    {
        return view('admin.orders.show', [
            'order' => $order->load('items.product', 'histories', 'user'),
            'statuses' => Order::STATUSES,
        ]);
    }

    public function update(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Order::STATUSES))],
            'tracking_code' => 'nullable|string|max:60',
            'note' => 'nullable|string|max:255',
        ]);

        $order->update(['tracking_code' => $data['tracking_code']]);

        if ($data['status'] === Order::STATUS_CANCELED) {
            $order->cancel($data['note'] ?: 'Cancelado pelo administrador');
        } else {
            $order->changeStatus($data['status'], $data['note'] ?: 'Atualizado pelo administrador');
        }

        return back()->with('success', 'Pedido atualizado!');
    }

    /** Busca o status mais recente do pagamento no Mercado Pago */
    public function sync(Order $order, MercadoPagoService $mercadoPago)
    {
        if (! $order->mp_payment_id) {
            return back()->with('error', 'Este pedido ainda não tem pagamento registrado no Mercado Pago.');
        }

        try {
            $mercadoPago->syncPayment($order->mp_payment_id);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Status do pagamento sincronizado.');
    }
}
