<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Perfil do administrador logado: nome, e-mail e troca de senha.
 */
class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('admin.profile', ['admin' => $request->user('admin')]);
    }

    public function update(Request $request)
    {
        $admin = $request->user('admin');

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => ['required', 'email', 'max:160', Rule::unique('admins')->ignore($admin->id)],
        ], [], ['name' => 'nome']);

        $admin->update($data);

        return back()->with('success', 'Perfil atualizado!');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|current_password:admin',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [], ['current_password' => 'senha atual', 'password' => 'nova senha']);

        $request->user('admin')->update(['password' => $data['password']]);

        return back()->with('success', 'Senha alterada com sucesso!');
    }
}
