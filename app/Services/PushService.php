<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\PushSubscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

use function Illuminate\Support\defer;

/**
 * Notificações no app (Web Push): aparecem no celular com o som padrão, mesmo com o app fechado.
 *
 * - Novidades: o admin escreve e manda para todos.
 * - Pedido: avisa o dono do pedido quando o status muda (pago, em produção, enviado, entregue).
 * - Produto novo: avisa todos na primeira vez que um produto fica ativo.
 *
 * Avisos automáticos saem depois de gravar no banco e de responder (afterCommit + defer).
 * Se o envio falhar, só vai para o log: nunca atrapalha o pedido ou o cadastro do produto.
 */
class PushService
{
    /** Textos dos avisos de pedido, por status */
    private const STATUS = [
        Order::STATUS_PAID => ['Pagamento aprovado ✅', 'Recebemos o pagamento do pedido :code. Já vamos começar!'],
        Order::STATUS_PRODUCTION => ['Sua peça está sendo impressa 🖨️', 'O pedido :code entrou em produção.'],
        Order::STATUS_SHIPPED => ['Pedido enviado 📦', 'O pedido :code saiu para entrega.'],
        Order::STATUS_DELIVERED => ['Pedido entregue 🎉', 'O pedido :code foi entregue. Esperamos que goste!'],
    ];

    public function configured(): bool
    {
        return filled(config('services.webpush.public_key')) && filled(config('services.webpush.private_key'));
    }

    /** Envia para todos os aparelhos inscritos. Devolve [enviadas, falharam]. */
    public function toAll(array $payload): array
    {
        return $this->send(PushSubscription::query(), $payload);
    }

    public function toUser(int $userId, array $payload): array
    {
        return $this->send(PushSubscription::where('user_id', $userId), $payload);
    }

    /** Aviso automático de mudança de status do pedido */
    public function orderStatusChanged(Order $order): void
    {
        if (! $order->user_id || ! isset(self::STATUS[$order->status])) {
            return;
        }

        [$title, $body] = self::STATUS[$order->status];
        $body = str_replace(':code', $order->code, $body);
        if ($order->status === Order::STATUS_SHIPPED && $order->tracking_code) {
            $body .= " Rastreio: {$order->tracking_code}";
        }

        $userId = $order->user_id;
        $this->later("pedido {$order->code}", fn () => $this->toUser($userId, [
            'title' => $title,
            'body' => $body,
            'url' => '/pedido/'.$order->code,
            'tag' => 'pedido-'.$order->code, // um aviso novo do mesmo pedido substitui o anterior
        ]));
    }

    /** Aviso automático de produto novo (só na primeira vez que ele fica ativo) */
    public function announceProduct(Product $product): void
    {
        if (! $product->active || $product->push_announced_at) {
            return;
        }

        $product->forceFill(['push_announced_at' => now()])->saveQuietly();

        $this->later("produto {$product->id}", function () use ($product) {
            $payload = [
                'title' => 'Novidade na loja ✨',
                'body' => $product->name.($product->short_description ? ' · '.$product->short_description : ''),
                'url' => '/produto/'.$product->slug,
                'image' => $product->image_url,
                'tag' => 'produto-'.$product->id,
            ];
            [$sent, $failed] = $this->toAll($payload);

            \App\Models\PushMessage::create([
                'title' => $payload['title'], 'body' => mb_strimwidth($payload['body'], 0, 240, '…'),
                'url' => $payload['url'], 'kind' => 'produto', 'sent' => $sent, 'failed' => $failed,
            ]);
        });
    }

    private function send(Builder $query, array $payload): array
    {
        if (! $this->configured()) {
            return [0, 0];
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => config('services.webpush.subject'),
                'publicKey' => config('services.webpush.public_key'),
                'privateKey' => config('services.webpush.private_key'),
            ],
        ], ['TTL' => 60 * 60 * 24 * 3, 'urgency' => 'high'], 15);
        $webPush->setReuseVAPIDHeaders(true);

        $json = json_encode($payload + ['icon' => '/icon-192.png'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $sent = 0;
        $failed = 0;

        $query->select(['id', 'endpoint', 'public_key', 'auth_token'])->chunkById(200, function ($subs) use ($webPush, $json, &$sent, &$failed) {
            $byEndpoint = [];
            foreach ($subs as $sub) {
                $byEndpoint[$sub->endpoint] = $sub->id;
                $webPush->queueNotification(Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'keys' => ['p256dh' => $sub->public_key, 'auth' => $sub->auth_token],
                ]), $json);
            }

            $ok = [];
            $expired = [];
            foreach ($webPush->flush() as $report) {
                $id = $byEndpoint[$report->getEndpoint()] ?? null;
                if ($report->isSuccess()) {
                    $sent++;
                    $ok[] = $id;
                } else {
                    $failed++;
                    // O cliente desinstalou o app ou bloqueou as notificações: apaga para não tentar de novo
                    if ($report->isSubscriptionExpired()) {
                        $expired[] = $id;
                    }
                }
            }

            PushSubscription::whereIn('id', array_filter($ok))->update(['last_sent_at' => now()]);
            PushSubscription::whereIn('id', array_filter($expired))->delete();
        });

        return [$sent, $failed];
    }

    private function later(string $what, callable $send): void
    {
        if (! $this->configured()) {
            return;
        }

        DB::afterCommit(fn () => defer(function () use ($what, $send) {
            try {
                $send();
            } catch (Throwable $e) {
                Log::error("Notificação no app não enviada ({$what})", ['error' => $e->getMessage()]);
            }
        }));
    }
}
