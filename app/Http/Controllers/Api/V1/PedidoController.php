<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Order;
use App\Services\Api\SacolaApi;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use RuntimeException;

/** Pedidos do cliente: listar, ver, criar e pagar (Mercado Pago, igual ao site) */
class PedidoController extends Controller
{
    public function index(Request $request)
    {
        $pedidos = $request->user()->orders()->with('items.product')->limit(50)->get();

        return response()->json(['success' => true, 'data' => $pedidos->map(fn ($p) => Formata::pedido($p))]);
    }

    public function show(Request $request, string $codigo)
    {
        $pedido = $this->doCliente($request, $codigo)->load('items.product', 'histories');

        return response()->json(['success' => true, 'data' => Formata::pedido($pedido, true)]);
    }

    public function store(Request $request, SacolaApi $sacola)
    {
        $dados = $request->validate([
            'itens' => 'required|array|min:1|max:50',
            'itens.*.produto_id' => 'required|integer',
            'itens.*.variacao_id' => 'nullable|integer',
            'itens.*.quantidade' => 'required|integer|min:1|max:99',
            'endereco_id' => 'required|integer',
            'observacao' => 'nullable|string|max:500',
        ], ['endereco_id.required' => 'Escolha um endereço de entrega.']);

        $user = $request->user();

        if (blank($user->phone)) {
            return response()->json([
                'success' => false,
                'message' => 'Cadastre seu telefone no perfil antes de finalizar o pedido.',
            ], 422);
        }

        $endereco = Address::where('user_id', $user->id)->findOrFail($dados['endereco_id']);

        $pedido = $sacola->criarPedido($user, $dados['itens'], [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'cpf' => $user->cpf,
            'cep' => $endereco->cep,
            'address' => $endereco->address,
            'number' => $endereco->number,
            'complement' => $endereco->complement,
            'district' => $endereco->district,
            'city' => $endereco->city,
            'state' => $endereco->state,
            'notes' => $dados['observacao'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pedido criado! Agora é só pagar.',
            'data' => Formata::pedido($pedido->load('items.product', 'histories'), true),
        ], 201);
    }

    /** Devolve o link do Mercado Pago (Pix, cartão ou boleto) para o app abrir */
    public function pagar(Request $request, string $codigo, MercadoPagoService $mercadoPago)
    {
        $pedido = $this->doCliente($request, $codigo);

        if (! $pedido->isPayable()) {
            return response()->json(['success' => false, 'message' => 'Este pedido não está aguardando pagamento.'], 422);
        }

        try {
            $url = $mercadoPago->createPreference($pedido->load('items'));
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'data' => ['url' => $url]]);
    }

    private function doCliente(Request $request, string $codigo): Order
    {
        return Order::where('user_id', $request->user()->id)->where('code', $codigo)->firstOrFail();
    }
}
