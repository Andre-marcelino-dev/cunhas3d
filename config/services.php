<?php

return [

    'mercadopago' => [
        'public_key' => env('MP_PUBLIC_KEY'),
        'access_token' => env('MP_ACCESS_TOKEN'),
        'webhook_secret' => env('MP_WEBHOOK_SECRET'),
        // true = usa o link de testes (sandbox) do Checkout Pro
        'sandbox' => env('MP_SANDBOX', true),
    ],

    'loja' => [
        'frete_fixo' => (float) env('LOJA_FRETE_FIXO', 24.90),
        'frete_gratis_acima' => (float) env('LOJA_FRETE_GRATIS_ACIMA', 299),
        // Quem recebe os avisos de pedido pago e de mensagem nova (padrão: MAIL_REPLY_TO_ADDRESS)
        'email_avisos' => env('LOJA_EMAIL_AVISOS'),
    ],

];
