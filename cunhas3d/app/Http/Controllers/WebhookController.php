<?php

namespace App\Http\Controllers;

use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class WebhookController extends Controller
{
    public function __invoke(Request $request, MercadoPagoService $mercadoPago)
    {
        if (! $mercadoPago->validSignature($request)) {
            Log::warning('Mercado Pago: webhook com assinatura inválida', ['ip' => $request->ip()]);

            return response()->json(['error' => 'invalid signature'], 401);
        }

        $type = $request->input('type') ?? $request->query('type') ?? $request->query('topic');
        $paymentId = $request->input('data.id') ?? $request->query('data_id') ?? $request->query('id');

        if ($type === 'payment' && $paymentId) {
            try {
                $mercadoPago->syncPayment((string) $paymentId);
            } catch (Throwable $e) {
                Log::error('Mercado Pago: erro ao processar webhook', ['payment' => $paymentId, 'error' => $e->getMessage()]);

                // 500 faz o Mercado Pago tentar de novo mais tarde
                return response()->json(['error' => 'retry'], 500);
            }
        }

        return response()->json(['ok' => true]);
    }
}
