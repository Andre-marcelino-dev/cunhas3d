<?php

namespace App\Providers;

use App\Models\Category;
use App\Services\CartService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CartService::class);
    }

    public function boot(): void
    {
        Paginator::defaultView('components.pagination');

        // Dados globais usados no layout da loja (menu de categorias e contador do carrinho)
        View::composer('layouts.app', function ($view) {
            $categories = Schema::hasTable('categories')
                ? Category::active()->whereNull('parent_id')->orderBy('position')->get()
                : collect();

            $view->with('menuCategories', $categories)
                ->with('cartCount', app(CartService::class)->count());
        });
    }
}
