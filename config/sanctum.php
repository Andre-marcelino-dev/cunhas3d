<?php

/*
 * Só sobrescreve a validade do login do app; o resto vem do padrão do Sanctum.
 * Depois de 90 dias o app pede login de novo (antes o token valia para sempre).
 */
return [
    'expiration' => (int) env('SANCTUM_EXPIRATION_MINUTES', 60 * 24 * 90),
];
