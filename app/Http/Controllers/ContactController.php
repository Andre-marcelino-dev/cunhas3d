<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Product;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function show(Request $request)
    {
        $product = $request->filled('produto')
            ? Product::active()->where('slug', $request->query('produto'))->first()
            : null;

        return view('store.contact', ['product' => $product]);
    }

    public function store(Request $request)
    {
        // Campo escondido "website": pessoas não veem, robôs de spam preenchem
        if ($request->filled('website')) {
            return back()->with('success', 'Mensagem enviada! Responderemos em breve.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'subject' => 'nullable|string|max:120',
            'message' => 'required|string|min:10|max:2000',
            'product_id' => 'nullable|integer|exists:products,id',
        ], [], [
            'name' => 'nome',
            'email' => 'e-mail',
            'phone' => 'telefone',
            'subject' => 'assunto',
            'message' => 'mensagem',
        ]);

        $product = isset($data['product_id']) ? Product::find($data['product_id']) : null;

        ContactMessage::create([
            ...$data,
            'user_id' => $request->user()?->id,
            'subject' => ($data['subject'] ?? null) ?: ($product ? 'Dúvida sobre: '.$product->name : 'Contato pelo site'),
        ]);

        return back()->with('success', 'Mensagem enviada! Responderemos no seu e-mail em breve.');
    }
}
