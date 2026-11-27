<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeçalhos de segurança em todas as respostas (site e API).
 * Feito no Laravel para funcionar igual no Docker (nginx) e na Locaweb (Apache).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Não revela a versão do PHP (X-Powered-By: PHP/8.x)
        header_remove('X-Powered-By');
        $response->headers->remove('X-Powered-By');

        // X-Content-Type-Options, X-Frame-Options e Referrer-Policy já vêm do servidor
        // (.htaccess na Locaweb, docker/nginx no ambiente local); aqui só o que falta
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // HSTS: o navegador passa a usar só https neste site por 6 meses (só faz sentido em produção com SSL)
        if ($request->isSecure() && app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=15552000');
        }

        return $response;
    }
}
