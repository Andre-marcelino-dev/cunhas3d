<?php

return [

    'mercadopago' => [
        'public_key' => env('MP_PUBLIC_KEY'),
        'access_token' => env('MP_ACCESS_TOKEN'),
        'webhook_secret' => env('MP_WEBHOOK_SECRET'),
        // true = usa o link de testes (sandbox) do Checkout Pro
        'sandbox' => env('MP_SANDBOX', true),
    ],

    // Notificações no app (Web Push). Chaves geradas uma vez com: php artisan push:chaves
    'webpush' => [
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
        'subject' => env('VAPID_SUBJECT', 'mailto:cunhas3d@outlook.com'),
    ],

    // Endereço do app (para links das notificações e permissão de acesso à API)
    'app' => [
        'url' => rtrim(env('APP_CLIENTE_URL', 'https://app-cunhas3d.expo.app'), '/'),
    ],

    'loja' => [
        'frete_fixo' => (float) env('LOJA_FRETE_FIXO', 24.90),
        'frete_gratis_acima' => (float) env('LOJA_FRETE_GRATIS_ACIMA', 299),
        // Quem recebe os avisos de pedido pago e de mensagem nova (padrão: MAIL_REPLY_TO_ADDRESS)
        'email_avisos' => env('LOJA_EMAIL_AVISOS'),
    ],

];
