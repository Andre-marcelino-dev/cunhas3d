<?php

use App\Models\Order;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Cancela pedidos não pagos depois de 3 dias (Pix/boleto vencidos) e devolve o estoque
Schedule::call(function () {
    Order::where('status', Order::STATUS_PENDING)
        ->where('created_at', '<', now()->subDays(3))
        ->get()
        ->each(fn (Order $order) => $order->cancel('Cancelado automaticamente: pagamento não identificado em 3 dias'));
})->hourly()->name('cancelar-pedidos-expirados');

// Apaga do banco os logins do app que já venceram (config/sanctum.php)
Schedule::command('sanctum:prune-expired --hours=24')->daily()->name('limpar-tokens-vencidos');

// Gera as chaves das notificações do app (rodar uma vez e colar no .env)
Artisan::command('push:chaves', function () {
    $chaves = \Minishlink\WebPush\VAPID::createVapidKeys();
    $this->info('Cole estas linhas no .env (a chave privada é secreta, não compartilhe):');
    $this->line('VAPID_PUBLIC_KEY='.$chaves['publicKey']);
    $this->line('VAPID_PRIVATE_KEY='.$chaves['privateKey']);
})->purpose('Gera as chaves VAPID das notificações do app');
