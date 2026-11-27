<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PasswordResetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

/** "Esqueci minha senha" do site: pede o e-mail, envia um código e troca a senha */
class PasswordResetController extends Controller
{
    public function __construct(private PasswordResetService $reset) {}

    public function showRequest()
    {
        return view('auth.forgot-password');
    }

    public function sendCode(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:160'], [], ['email' => 'e-mail']);

        try {
            $this->reset->enviarCodigo($data['email']);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        // Mesma resposta exista ou não a conta, para não revelar quem é cliente
        return redirect()->route('password.reset', ['email' => $data['email']])
            ->with('success', 'Se o e-mail estiver cadastrado, você vai receber um código em instantes.');
    }

    public function showReset(Request $request)
    {
        return view('auth.reset-password', ['email' => (string) $request->query('email')]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'code' => 'required|digits:6',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [], ['code' => 'código', 'password' => 'nova senha']);

        $user = $this->reset->redefinir($data['email'], $data['code'], $data['password'], 'code');

        $cart = session('cart', []);
        Auth::login($user);
        $request->session()->regenerate();
        session(['cart' => $cart]);

        return redirect()->route('account.orders')->with('success', 'Senha alterada! Você já está conectado.');
    }
}
