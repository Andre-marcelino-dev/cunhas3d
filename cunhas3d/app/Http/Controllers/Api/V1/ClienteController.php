<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Perfil do cliente logado no app */
class ClienteController extends Controller
{
    public function show(Request $request)
    {
        return response()->json(['success' => true, 'data' => Formata::cliente($request->user())]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $dados = $request->validate([
            'nome' => 'required|string|max:120',
            'email' => ['required', 'email', 'max:160', Rule::unique('users')->ignore($user->id)],
            'telefone' => 'nullable|string|max:20',
            'cpf' => 'nullable|string|max:14',
        ], [], ['nome' => 'nome', 'telefone' => 'telefone']);

        $user->update([
            'name' => $dados['nome'],
            'email' => $dados['email'],
            'phone' => $dados['telefone'] ?? null,
            'cpf' => $dados['cpf'] ?? null,
        ]);

        return response()->json(['success' => true, 'message' => 'Dados atualizados!', 'data' => Formata::cliente($user)]);
    }

    public function senha(Request $request)
    {
        $dados = $request->validate([
            'senha_atual' => 'required|current_password:sanctum',
            'nova_senha' => ['required', 'confirmed', 'different:senha_atual', Password::min(8)->letters()->numbers()],
        ], ['nova_senha.different' => 'A nova senha precisa ser diferente da atual.'], [
            'senha_atual' => 'senha atual', 'nova_senha' => 'nova senha',
        ]);

        $user = $request->user();
        $user->update(['password' => $dados['nova_senha']]);

        // Desconecta os outros aparelhos, mantendo este
        $user->tokens()->whereKeyNot($user->currentAccessToken()->id)->delete();

        return response()->json(['success' => true, 'message' => 'Senha alterada com sucesso!']);
    }
}
