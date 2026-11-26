<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        return $this->listing($request);
    }

    public function category(Request $request, Category $category)
    {
        abort_unless($category->active, 404);

        return $this->listing($request, $category);
    }

    private function listing(Request $request, ?Category $category = null)
    {
        $query = Product::active()->with('category', 'variants');

        if ($category) {
            $ids = $category->children()->pluck('id')->push($category->id);
            $query->whereIn('category_id', $ids);
        }

        if ($search = trim((string) $request->query('q'))) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('short_description', 'like', "%{$search}%"));
        }

        if ($material = $request->query('material')) {
            $query->where('material', $material);
        }

        if ($request->filled('max')) {
            $query->whereRaw('COALESCE(sale_price, price) <= ?', [(float) $request->query('max')]);
        }

        match ($request->query('ordem')) {
            'menor-preco' => $query->orderByRaw('COALESCE(sale_price, price) asc'),
            'maior-preco' => $query->orderByRaw('COALESCE(sale_price, price) desc'),
            'nome' => $query->orderBy('name'),
            default => $query->orderByDesc('featured')->latest(),
        };

        return view('store.products', [
            'products' => $query->paginate(12)->withQueryString(),
            'category' => $category,
            'categories' => Category::active()->whereNull('parent_id')->orderBy('position')->get(),
            'materials' => Product::MATERIALS,
        ]);
    }

    public function show(Product $product)
    {
        abort_unless($product->active, 404);

        $product->load('category', 'images', 'variants');

        return view('store.product', [
            'product' => $product,
            'related' => Product::active()
                ->where('category_id', $product->category_id)
                ->with('variants')
                ->whereKeyNot($product->id)
                ->inRandomOrder()->take(4)->get(),
        ]);
    }
}
