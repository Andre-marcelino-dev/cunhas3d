<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // O carrinho fica na sessão; guardamos antes de regenerar
        $cart = session('cart', []);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'E-mail ou senha incorretos.']);
        }

        $request->session()->regenerate();
        session(['cart' => $cart]);

        return redirect()->intended(route('account.orders'));
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:160|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [], ['name' => 'nome', 'password' => 'senha', 'phone' => 'telefone']);

        $cart = session('cart', []);

        $user = User::create($data);
        Auth::login($user);

        $request->session()->regenerate();
        session(['cart' => $cart]);

        return redirect()->intended(route('account.orders'))->with('success', 'Conta criada com sucesso! Bem-vindo(a).');
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        // Se o administrador também estiver logado neste navegador, não derruba a sessão dele
        if (! Auth::guard('admin')->check()) {
            $request->session()->invalidate();
        }
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
