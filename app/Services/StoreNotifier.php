<?php

namespace App\Services;

use App\Models\ContactMessage;
use App\Models\Order;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Mime\Address;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

use function Illuminate\Support\defer;

/**
 * E-mails automáticos da loja: pedido pago (cliente e loja) e mensagem nova do "Fale conosco".
 *
 * Regras de segurança:
 *  - só envia DEPOIS que o banco gravou (afterCommit) e depois de responder ao navegador/Mercado Pago (defer);
 *  - se o e-mail falhar, só registra no log: o pedido e a mensagem nunca deixam de ser salvos por causa disso.
 */
class StoreNotifier
{
    /** E-mail que recebe os avisos da loja (padrão: o mesmo do "Responder para") */
    public static function storeEmail(): ?string
    {
        return config('services.loja.email_avisos') ?: config('mail.reply_to.address');
    }

    public function orderPaid(Order $order): void
    {
        $this->later('pedido pago '.$order->code, function () use ($order) {
            $order = $order->fresh(['items.product']);
            $days = (int) $order->items->max(fn ($i) => $i->product?->production_days ?? 0);
            $data = ['order' => $order, 'productionDays' => $days];

            Mail::send('emails.order-paid-customer', $data, fn ($m) => $m
                ->to($order->email, $order->name)
                ->subject("Pagamento aprovado! Pedido {$order->code}"));

            if ($to = self::storeEmail()) {
                Mail::send('emails.order-paid-store', $data, fn ($m) => $this->replyOnlyTo($m, $order->email, $order->name)
                    ->to($to)
                    ->subject("💰 Novo pedido pago: {$order->code} · ".money($order->total)));
            }
        });
    }

    public function newContactMessage(ContactMessage $message): void
    {
        if (! $to = self::storeEmail()) {
            return;
        }

        $this->later('mensagem de contato '.$message->id, function () use ($message, $to) {
            $message = $message->fresh('product');

            // "Responder" no Outlook vai direto para o cliente
            Mail::send('emails.contact-message', ['msg' => $message], fn ($m) => $this->replyOnlyTo($m, $message->email, $message->name)
                ->to($to)
                ->subject("✉️ {$message->subject} · {$message->name}"));
        });
    }

    /**
     * Nos avisos para a loja, "Responder" deve ir SÓ para o cliente
     * (substitui o MAIL_REPLY_TO_ADDRESS, que senão faria a resposta voltar para a própria loja).
     */
    private function replyOnlyTo(Message $m, string $email, ?string $name): Message
    {
        $m->getSymfonyMessage()->replyTo(new Address($email, (string) $name));

        return $m;
    }

    private function later(string $what, callable $send): void
    {
        DB::afterCommit(fn () => defer(function () use ($what, $send) {
            try {
                $send();
            } catch (Throwable $e) {
                Log::error("E-mail automático não enviado ({$what})", ['error' => $e->getMessage()]);
            }
        }));
    }
}
