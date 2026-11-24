<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentEvent;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Integração com o Mercado Pago (Checkout Pro).
 * O cliente é levado para a página segura do Mercado Pago, onde paga com
 * Pix, cartão de crédito (parcelado), cartão de débito ou boleto.
 *
 * Documentação: https://www.mercadopago.com.br/developers/pt/docs/checkout-pro
 */
class MercadoPagoService
{
    private const API = 'https://api.mercadopago.com';

    private function http()
    {
        $token = config('services.mercadopago.access_token');

        if (blank($token)) {
            throw new RuntimeException('Configure MP_ACCESS_TOKEN no arquivo .env para receber pagamentos.');
        }

        return Http::withToken($token)->acceptJson()->timeout(20)->baseUrl(self::API);
    }

    /**
     * Cria a "preferência" de pagamento e devolve o link para onde o cliente deve ir.
     */
    public function createPreference(Order $order): string
    {
        $items = $order->items->map(fn ($item) => [
            'id' => $item->sku,
            'title' => $item->name,
            'quantity' => $item->quantity,
            'unit_price' => (float) $item->price,
            'currency_id' => 'BRL',
        ])->values()->all();

        $nameParts = explode(' ', trim($order->name), 2);

        $payload = [
            'items' => $items,
            'shipments' => ['cost' => (float) $order->shipping, 'mode' => 'not_specified'],
            'payer' => [
                'name' => $nameParts[0],
                'surname' => $nameParts[1] ?? '',
                'email' => $order->email,
            ],
            'external_reference' => $order->code,
            'statement_descriptor' => 'CUNHAS3D',
            'back_urls' => [
                'success' => route('checkout.return', $order),
                'pending' => route('checkout.return', $order),
                'failure' => route('checkout.return', $order),
            ],
            'payment_methods' => ['installments' => 12],
            'expires' => true,
            'date_of_expiration' => now()->addDays(3)->format('Y-m-d\TH:i:s.vP'),
        ];

        // O Mercado Pago só aceita auto_return e notification_url com URLs públicas (não localhost)
        if (! str_contains(config('app.url'), 'localhost') && ! str_contains(config('app.url'), '127.0.0.1')) {
            $payload['auto_return'] = 'approved';
            $payload['notification_url'] = route('webhooks.mercadopago');
        }

        $response = $this->http()
            ->withHeaders(['X-Idempotency-Key' => 'pref-'.$order->code.'-'.$order->updated_at?->timestamp])
            ->post('/checkout/preferences', $payload);

        if ($response->failed()) {
            Log::error('Mercado Pago: erro ao criar preferência', ['order' => $order->code, 'body' => $response->json()]);
            throw new RuntimeException('Não foi possível iniciar o pagamento. Tente novamente em instantes.');
        }

        $order->update(['mp_preference_id' => $response->json('id')]);

        return config('services.mercadopago.sandbox')
            ? ($response->json('sandbox_init_point') ?? $response->json('init_point'))
            : $response->json('init_point');
    }

    public function getPayment(string $paymentId): array
    {
        $response = $this->http()->get("/v1/payments/{$paymentId}");

        if ($response->failed()) {
            throw new RuntimeException("Pagamento {$paymentId} não encontrado no Mercado Pago.");
        }

        return $response->json();
    }

    /**
     * Valida a assinatura (header x-signature) enviada pelo Mercado Pago.
     * https://www.mercadopago.com.br/developers/pt/docs/your-integrations/notifications/webhooks
     */
    public function validSignature(Request $request): bool
    {
        $secret = config('services.mercadopago.webhook_secret');

        if (blank($secret)) {
            // Sem segredo configurado: aceita, mas sempre confirma o status direto na API
            return true;
        }

        $signature = (string) $request->header('x-signature');
        $requestId = (string) $request->header('x-request-id');
        $dataId = (string) ($request->query('data_id') ?? $request->query('data.id') ?? $request->input('data.id'));

        $parts = [];
        foreach (explode(',', $signature) as $piece) {
            [$key, $value] = array_pad(explode('=', trim($piece), 2), 2, null);
            $parts[$key] = $value;
        }

        if (empty($parts['ts']) || empty($parts['v1'])) {
            return false;
        }

        $id = ctype_alnum($dataId) ? strtolower($dataId) : $dataId;
        $manifest = "id:{$id};request-id:{$requestId};ts:{$parts['ts']};";
        $expected = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expected, $parts['v1']);
    }

    /**
     * Consulta o pagamento na API e atualiza o pedido. Seguro para ser chamado várias vezes.
     */
    public function syncPayment(string $paymentId): ?Order
    {
        $payment = $this->getPayment($paymentId);
        $status = $payment['status'] ?? 'unknown';

        $order = Order::where('code', $payment['external_reference'] ?? null)->first();

        if (! $order) {
            Log::warning('Mercado Pago: pagamento sem pedido correspondente', ['payment' => $paymentId]);

            return null;
        }

        // Confere se o valor pago bate com o valor do pedido
        if ($status === 'approved' && round((float) ($payment['transaction_amount'] ?? 0), 2) < round((float) $order->total, 2)) {
            Log::critical('Mercado Pago: valor pago menor que o total do pedido', ['order' => $order->code]);

            return $order;
        }

        try {
            DB::transaction(fn () => $this->applyPayment($order, $payment, $paymentId, $status));
        } catch (UniqueConstraintViolationException) {
            // Idempotência: esta combinação pagamento + status já foi processada
            return $order;
        }

        return $order->fresh();
    }

    private function applyPayment(Order $order, array $payment, string $paymentId, string $status): void
    {
        PaymentEvent::create(['mp_payment_id' => $paymentId, 'status' => $status, 'payload' => $payment]);

        $order->update([
            'mp_payment_id' => $paymentId,
            'payment_status' => $status,
            'payment_method' => $payment['payment_method_id'] ?? null,
        ]);

        match ($status) {
            'approved' => $this->markPaid($order),
            'rejected', 'cancelled' => $order->histories()->create([
                'status' => $order->status,
                'note' => 'Pagamento recusado/cancelado no Mercado Pago. O cliente pode tentar novamente.',
            ]),
            'refunded', 'charged_back' => $order->cancel('Pagamento estornado no Mercado Pago'),
            default => null, // pending / in_process: aguarda
        };
    }

    private function markPaid(Order $order): void
    {
        if ($order->status !== Order::STATUS_PENDING) {
            return;
        }

        $order->update(['paid_at' => now()]);
        $order->changeStatus(Order::STATUS_PAID, 'Pagamento aprovado pelo Mercado Pago');
    }
}
