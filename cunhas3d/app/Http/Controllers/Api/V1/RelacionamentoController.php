<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Testimonial;
use Illuminate\Http\Request;

/** Depoimentos e "Fale conosco" do app */
class RelacionamentoController extends Controller
{
    /** Depoimentos aprovados (públicos) */
    public function depoimentos()
    {
        $lista = Testimonial::with('user')->where('approved', true)->latest()->limit(30)->get();

        return response()->json(['success' => true, 'data' => $lista->map(fn ($t) => Formata::depoimento($t))]);
    }

    /** Depoimentos do cliente logado */
    public function meusDepoimentos(Request $request)
    {
        $lista = Testimonial::with('user')->where('user_id', $request->user()->id)->latest()->get();

        return response()->json(['success' => true, 'data' => $lista->map(fn ($t) => Formata::depoimento($t))]);
    }

    public function enviarDepoimento(Request $request)
    {
        $dados = $request->validate([
            'nota' => 'required|integer|min:1|max:5',
            'mensagem' => 'required|string|min:10|max:1000',
        ], ['nota.required' => 'Escolha de 1 a 5 estrelas.'], ['mensagem' => 'depoimento']);

        $depoimento = Testimonial::create([
            'user_id' => $request->user()->id,
            'rating' => $dados['nota'],
            'message' => $dados['mensagem'],
        ])->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Obrigado pelo seu depoimento!',
            'data' => Formata::depoimento($depoimento),
        ], 201);
    }

    public function contato(Request $request)
    {
        $dados = $request->validate([
            'assunto' => 'required|string|max:120',
            'mensagem' => 'required|string|min:10|max:2000',
        ], [], ['assunto' => 'assunto', 'mensagem' => 'mensagem']);

        $user = $request->user();

        ContactMessage::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'subject' => $dados['assunto'],
            'message' => $dados['mensagem'],
        ]);

        return response()->json(['success' => true, 'message' => 'Mensagem enviada! Responderemos no seu e-mail.'], 201);
    }
}
