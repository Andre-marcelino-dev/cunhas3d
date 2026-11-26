<?php

if (! function_exists('money')) {
    /** Formata valores em reais: 1234.5 => "R$ 1.234,50" */
    function money(float|int|string|null $value): string
    {
        return 'R$ '.number_format((float) $value, 2, ',', '.');
    }
}
