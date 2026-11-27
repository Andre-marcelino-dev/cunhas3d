<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Inscrição do celular/navegador para receber notificações do app (Web Push).
 * Funciona com ou sem login: sem login recebe só novidades; logado recebe também os avisos dos pedidos.
 */
class NotificacaoController extends Controller
{
    /** Chave pública que o navegador precisa para se inscrever */
    public function chave()
    {
        $chave = config('services.webpush.public_key');

        return response()->json(['success' => (bool) $chave, 'data' => ['chave' => $chave]], $chave ? 200 : 503);
    }

    public function inscrever(Request $request)
    {
        $dados = $request->validate([
            'endpoint' => ['required', 'string', 'max:1000', 'url:https'],
            'keys.p256dh' => 'required|string|max:255',
            'keys.auth' => 'required|string|max:255',
        ]);

        // Login é opcional: se o app mandar o token, liga o aparelho à conta do cliente
        $user = Auth::guard('sanctum')->user();

        PushSubscription::updateOrCreate(
            ['endpoint_hash' => PushSubscription::hashOf($dados['endpoint'])],
            [
                'endpoint' => $dados['endpoint'],
                'public_key' => $dados['keys']['p256dh'],
                'auth_token' => $dados['keys']['auth'],
                'user_id' => $user?->id,
                'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            ],
        );

        return response()->json(['success' => true, 'message' => 'Notificações ativadas!'], 201);
    }

    public function cancelar(Request $request)
    {
        $dados = $request->validate(['endpoint' => 'required|string|max:1000']);

        PushSubscription::where('endpoint_hash', PushSubscription::hashOf($dados['endpoint']))->delete();

        return response()->json(['success' => true, 'message' => 'Notificações desativadas.']);
    }
}
