<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Services\PushService;
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

    /**
     * Manda uma notificação de teste só para este aparelho (botão "Testar pelo servidor" no app).
     * Diz se o serviço de push aceitou, para saber se o problema é no servidor ou no celular.
     */
    public function testar(Request $request, PushService $push)
    {
        $dados = $request->validate(['endpoint' => 'required|string|max:1000']);

        $inscricao = PushSubscription::where('endpoint_hash', PushSubscription::hashOf($dados['endpoint']))->first();
        if (! $inscricao) {
            return response()->json([
                'success' => false,
                'message' => 'Este aparelho não está inscrito no servidor. Desative e ative as notificações de novo.',
            ], 404);
        }

        [$enviadas] = $push->toSubscription($inscricao, [
            'title' => 'Teste do servidor ✅',
            'body' => 'Se você viu e ouviu isto, as notificações da Cunhas 3D estão funcionando.',
            'url' => '/configuracoes',
            'tag' => 'teste',
        ]);

        return $enviadas
            ? response()->json(['success' => true, 'message' => 'O servidor enviou. Deve aparecer em alguns segundos.'])
            : response()->json(['success' => false, 'message' => 'O serviço de notificações recusou este aparelho. Desative e ative de novo.'], 422);
    }

    public function cancelar(Request $request)
    {
        $dados = $request->validate(['endpoint' => 'required|string|max:1000']);

        PushSubscription::where('endpoint_hash', PushSubscription::hashOf($dados['endpoint']))->delete();

        return response()->json(['success' => true, 'message' => 'Notificações desativadas.']);
    }
}
