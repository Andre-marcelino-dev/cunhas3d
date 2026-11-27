<?php

/*
 * Só sobrescreve o disco "public" (fotos de produtos e modelos 3D).
 * Os demais discos vêm do padrão do Laravel.
 *
 * Em hospedagem compartilhada (ex.: Locaweb) não dá para criar o link
 * public/storage -> storage/app/public. Nesse caso defina no .env:
 *   PUBLIC_DISK_ROOT=../public_html/storage
 * (caminho relativo à pasta do projeto) e os uploads vão direto para a pasta pública.
 */
return [
    'disks' => [
        'public' => [
            'driver' => 'local',
            'root' => env('PUBLIC_DISK_ROOT')
                ? base_path(env('PUBLIC_DISK_ROOT'))
                : storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],
    ],
];
