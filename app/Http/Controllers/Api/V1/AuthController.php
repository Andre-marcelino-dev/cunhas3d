<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PasswordResetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Login do app por token (Laravel Sanctum). Usa a mesma tabela "users" do site,
 * então o cliente entra no app com a mesma conta do site.
 */
class AuthController extends Controller
{
    public function login(Request $request)
    {
        $dados = $request->validate([
            'email' => 'required|email',
            'senha' => 'required|string',
            'dispositivo' => 'nullable|string|max:60',
        ], [], ['senha' => 'senha']);

        $user = User::where('email', $dados['email'])->first();

        if (! $user || ! Hash::check($dados['senha'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'E-mail ou senha incorretos.']);
        }

        return $this->comToken($user, $dados['dispositivo'] ?? 'app', 'Bem-vindo(a) de volta!');
    }

    public function cadastro(Request $request)
    {
        $dados = $request->validate([
            'nome' => 'required|string|max:120',
            'email' => 'required|email|max:160|unique:users,email',
            'telefone' => 'nullable|string|max:20',
            'senha' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'termos' => 'accepted',
            'dispositivo' => 'nullable|string|max:60',
        ], ['termos.accepted' => 'Você precisa aceitar os termos de uso.'], [
            'nome' => 'nome', 'telefone' => 'telefone', 'senha' => 'senha',
        ]);

        $user = User::create([
            'name' => $dados['nome'],
            'email' => $dados['email'],
            'phone' => $dados['telefone'] ?? null,
            'password' => $dados['senha'],
        ]);

        return $this->comToken($user, $dados['dispositivo'] ?? 'app', 'Conta criada com sucesso!', 201);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['success' => true, 'message' => 'Você saiu da conta.']);
    }

    /** Envia por e-mail um código de 6 números para trocar a senha (o mesmo serve no site). */
    public function esqueciSenha(Request $request, PasswordResetService $reset)
    {
        $dados = $request->validate(['email' => 'required|email']);

        try {
            $reset->enviarCodigo($dados['email']);
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 503);
        }

        // Responde igual exista ou não a conta, para não revelar e-mails cadastrados
        return response()->json([
            'success' => true,
            'message' => 'Se o e-mail estiver cadastrado, você vai receber um código para criar uma nova senha.',
        ]);
    }

    public function redefinirSenha(Request $request, PasswordResetService $reset)
    {
        $dados = $request->validate([
            'email' => 'required|email',
            'codigo' => 'required|digits:6',
            'senha' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [], ['codigo' => 'código', 'senha' => 'nova senha']);

        // Confere o código (máx. 5 tentativas), troca a senha e desconecta os outros aparelhos
        $reset->redefinir($dados['email'], $dados['codigo'], $dados['senha'], 'codigo');

        return response()->json(['success' => true, 'message' => 'Senha alterada! Faça login com a nova senha.']);
    }

    private function comToken(User $user, string $dispositivo, string $mensagem, int $status = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $mensagem,
            'data' => [
                'token' => $user->createToken($dispositivo)->plainTextToken,
                'cliente' => Formata::cliente($user),
            ],
        ], $status);
    }
}
