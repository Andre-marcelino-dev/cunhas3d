<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/**
 * "Esqueci minha senha" por código de 6 números enviado por e-mail.
 * Usado pelo site e pelo app, com as mesmas regras:
 *  - o código vale 30 minutos e só o último pedido vale;
 *  - depois de 5 erros o código é descartado (limite por conta, não só por IP).
 */
class PasswordResetService
{
    public const VALIDADE_MINUTOS = 30;

    public const MAX_TENTATIVAS = 5;

    /**
     * Envia o código se o e-mail tiver conta. Não informa se a conta existe.
     *
     * @throws RuntimeException se o e-mail não puder ser enviado
     */
    public function enviarCodigo(string $email): void
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            return;
        }

        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($codigo), 'created_at' => now()]
        );
        RateLimiter::clear($this->chave($user->email));

        try {
            Mail::raw(
                "Olá, {$user->name}!\n\n"
                ."Seu código para criar uma nova senha na ".config('app.name')." é: {$codigo}\n\n"
                .'Ele vale por '.self::VALIDADE_MINUTOS." minutos e funciona no site e no app.\n"
                .'Se não foi você que pediu, ignore este e-mail: sua senha continua a mesma.',
                fn ($m) => $m->to($user->email)->subject('Código para redefinir sua senha - '.config('app.name'))
            );
        } catch (Throwable $e) {
            Log::error('Esqueci a senha: falha ao enviar o e-mail', ['error' => $e->getMessage()]);

            throw new RuntimeException('Não conseguimos enviar o e-mail agora. Tente de novo em alguns minutos.');
        }
    }

    /**
     * Confere o código e troca a senha. Desconecta o app em todos os aparelhos.
     *
     * @param  string  $campoErro  nome do campo onde o erro aparece ("code" no site, "codigo" no app)
     */
    public function redefinir(string $email, string $codigo, string $novaSenha, string $campoErro): User
    {
        $registro = DB::table('password_reset_tokens')->where('email', $email)->first();
        $chave = $this->chave($email);

        $valido = $registro
            && Hash::check($codigo, $registro->token)
            && now()->subMinutes(self::VALIDADE_MINUTOS)->lt($registro->created_at);

        if (! $valido) {
            RateLimiter::hit($chave, self::VALIDADE_MINUTOS * 60);

            if (RateLimiter::attempts($chave) >= self::MAX_TENTATIVAS) {
                DB::table('password_reset_tokens')->where('email', $email)->delete();
                RateLimiter::clear($chave);

                throw ValidationException::withMessages([$campoErro => 'Muitas tentativas erradas. Peça um novo código.']);
            }

            throw ValidationException::withMessages([$campoErro => 'Código inválido ou expirado. Confira o e-mail ou peça um novo código.']);
        }

        RateLimiter::clear($chave);

        $user = User::where('email', $email)->firstOrFail();
        $user->forceFill(['password' => $novaSenha, 'remember_token' => null])->save();

        // Quem estava logado com a senha antiga (no app ou em outro navegador) sai
        $user->tokens()->delete();
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return $user;
    }

    private function chave(string $email): string
    {
        return 'codigo-senha:'.strtolower($email);
    }
}
