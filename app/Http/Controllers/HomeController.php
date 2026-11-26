<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    public function __invoke()
    {
        return view('store.home', [
            'featured' => Product::active()->where('featured', true)->with('category')->latest()->take(8)->get(),
            'latest' => Product::active()->with('category')->latest()->take(4)->get(),
            'categories' => Category::active()->whereNull('parent_id')->withCount(['products' => fn ($q) => $q->active()])
                ->orderBy('position')->get(),
        ]);
    }
}
