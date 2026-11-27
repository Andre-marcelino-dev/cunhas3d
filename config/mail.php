<?php

/*
 * Só acrescenta o "Responder para"; o resto vem do padrão do Laravel (MAIL_HOST, MAIL_PORT...).
 * O site envia pela conta de e-mail da Locaweb, mas quando o cliente clica em "Responder"
 * a resposta vai para o e-mail da empresa (MAIL_REPLY_TO_ADDRESS, ex.: cunhas3d@outlook.com).
 */
return [
    'reply_to' => [
        'address' => env('MAIL_REPLY_TO_ADDRESS'),
        'name' => env('MAIL_REPLY_TO_NAME', env('MAIL_FROM_NAME', env('APP_NAME'))),
    ],
];
