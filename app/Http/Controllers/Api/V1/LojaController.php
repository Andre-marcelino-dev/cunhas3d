<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\Api\SacolaApi;
use Illuminate\Http\Request;

/**
 * Rotas públicas do app: dados da loja, categorias, produtos e cálculo da sacola.
 */
class LojaController extends Controller
{
    public function loja()
    {
        return response()->json(['success' => true, 'data' => [
            'nome' => config('app.name'),
            'frete_fixo' => (float) config('services.loja.frete_fixo'),
            'frete_gratis_acima' => (float) config('services.loja.frete_gratis_acima'),
            'email' => config('mail.from.address'),
            'horario' => 'Seg a Sex, das 9h às 18h',
            'pagamentos' => ['Pix', 'Cartão em até 12x', 'Boleto'],
        ]]);
    }

    public function categorias()
    {
        $categorias = Category::active()
            ->withCount(['products' => fn ($q) => $q->active()])
            ->orderBy('position')->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'data' => $categorias->map(fn ($c) => Formata::categoria($c))]);
    }

    public function produtos(Request $request)
    {
        $produtos = Product::active()->with('category')
            ->whereHas('category', fn ($q) => $q->active())
            ->when($request->query('categoria'), fn ($q, $slug) => $q->whereHas('category', fn ($c) => $c->where('slug', $slug)))
            ->when($request->query('busca'), fn ($q, $busca) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$busca}%")
                ->orWhere('short_description', 'like', "%{$busca}%")
                ->orWhere('material', 'like', "%{$busca}%")))
            ->when($request->boolean('destaque'), fn ($q) => $q->where('featured', true))
            ->orderByDesc('featured')->latest()
            ->limit(200)
            ->get();

        return response()->json(['success' => true, 'data' => $produtos->map(fn ($p) => Formata::produto($p))]);
    }

    public function produto(string $slug)
    {
        $produto = Product::active()->with(['category', 'images', 'variants'])->where('slug', $slug)->firstOrFail();

        return response()->json(['success' => true, 'data' => Formata::produto($produto, true)]);
    }

    /** Recebe os itens guardados no app e devolve preços, frete e total calculados no servidor. */
    public function calcularSacola(Request $request, SacolaApi $sacola)
    {
        $dados = $request->validate([
            'itens' => 'present|array|max:50',
            'itens.*.produto_id' => 'required|integer',
            'itens.*.variacao_id' => 'nullable|integer',
            'itens.*.quantidade' => 'required|integer|min:1|max:99',
        ]);

        [$linhas, $subtotal, $frete, $total] = $sacola->resumo($dados['itens']);

        return response()->json(['success' => true, 'data' => [
            'itens' => $linhas->map(fn ($l) => [
                'produto' => Formata::produto($l->product),
                'variacao' => $l->variant ? ['id' => $l->variant->id, 'nome' => $l->variant->name] : null,
                'nome' => $l->name,
                'preco_unitario' => $l->unit_price,
                'quantidade' => $l->quantity,
                'total' => $l->total,
            ]),
            'subtotal' => $subtotal,
            'frete' => $frete,
            'total' => $total,
            'frete_gratis_acima' => (float) config('services.loja.frete_gratis_acima'),
        ]]);
    }
}
