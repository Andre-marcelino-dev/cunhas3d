<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Só deixa passar quem está logado como administrador (guard "admin", tabela "admins").
 * Clientes logados na loja NÃO têm acesso ao painel.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('admin')->check()) {
            return redirect()->guest(route('admin.login'));
        }

        // Dentro do painel, auth()->user() passa a ser o administrador
        Auth::shouldUse('admin');

        return $next($request);
    }
}
