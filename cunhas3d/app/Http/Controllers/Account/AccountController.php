<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function orders(Request $request)
    {
        return view('account.orders', [
            'orders' => $request->user()->orders()->withCount('items')->paginate(10),
        ]);
    }

    public function order(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        return view('account.order', ['order' => $order->load('items.product', 'histories')]);
    }

    public function profile(Request $request)
    {
        return view('account.profile', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => ['required', 'email', 'max:160', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'cpf' => 'nullable|string|max:14',
            'cep' => 'nullable|string|max:9',
            'address' => 'nullable|string|max:160',
            'number' => 'nullable|string|max:20',
            'complement' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|size:2',
        ]);

        $user->update($data);

        return back()->with('success', 'Dados atualizados!');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [], ['current_password' => 'senha atual', 'password' => 'nova senha']);

        $request->user()->update(['password' => $data['password']]);

        return back()->with('success', 'Senha alterada com sucesso!');
    }
}
