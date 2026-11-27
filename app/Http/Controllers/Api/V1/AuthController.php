<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Login do app por token (Laravel Sanctum). Usa a mesma tabela "users" do site,
 * então o cliente entra no app com a mesma conta do site.
 */
class AuthController extends Controller
{
    private const VALIDADE_CODIGO_MINUTOS = 30;

    private const MAX_TENTATIVAS_CODIGO = 5;

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

    /** Envia por e-mail um código de 6 números para trocar a senha. */
    public function esqueciSenha(Request $request)
    {
        $dados = $request->validate(['email' => 'required|email']);

        // Responde igual exista ou não a conta, para não revelar e-mails cadastrados
        $resposta = response()->json([
            'success' => true,
            'message' => 'Se o e-mail estiver cadastrado, você vai receber um código para criar uma nova senha.',
        ]);

        $user = User::where('email', $dados['email'])->first();
        if (! $user) {
            return $resposta;
        }

        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($codigo), 'created_at' => now()]
        );

        Mail::raw(
            "Olá, {$user->name}!\n\nSeu código para criar uma nova senha no app ".config('app.name')." é: {$codigo}\n\n"
            .'Ele vale por '.self::VALIDADE_CODIGO_MINUTOS." minutos. Se não foi você que pediu, ignore este e-mail.",
            fn ($m) => $m->to($user->email)->subject('Código para redefinir sua senha - '.config('app.name'))
        );

        return $resposta;
    }

    public function redefinirSenha(Request $request)
    {
        $dados = $request->validate([
            'email' => 'required|email',
            'codigo' => 'required|digits:6',
            'senha' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [], ['codigo' => 'código', 'senha' => 'nova senha']);

        $registro = DB::table('password_reset_tokens')->where('email', $dados['email'])->first();

        // Limite por conta (não só por IP): depois de 5 erros o código é descartado,
        // assim não dá para tentar os 1.000.000 de combinações trocando de internet
        $chaveTentativas = 'codigo-senha:'.strtolower($dados['email']);

        $valido = $registro
            && Hash::check($dados['codigo'], $registro->token)
            && now()->subMinutes(self::VALIDADE_CODIGO_MINUTOS)->lt($registro->created_at);

        if (! $valido) {
            RateLimiter::hit($chaveTentativas, self::VALIDADE_CODIGO_MINUTOS * 60);

            if (RateLimiter::attempts($chaveTentativas) >= self::MAX_TENTATIVAS_CODIGO) {
                DB::table('password_reset_tokens')->where('email', $dados['email'])->delete();
                RateLimiter::clear($chaveTentativas);

                throw ValidationException::withMessages(['codigo' => 'Muitas tentativas erradas. Peça um novo código.']);
            }

            throw ValidationException::withMessages(['codigo' => 'Código inválido ou expirado. Peça um novo código.']);
        }

        RateLimiter::clear($chaveTentativas);

        $user = User::where('email', $dados['email'])->firstOrFail();
        $user->update(['password' => $dados['senha']]);
        $user->tokens()->delete(); // desconecta os outros aparelhos

        DB::table('password_reset_tokens')->where('email', $dados['email'])->delete();

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
