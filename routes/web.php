<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// ----------------------------------------------------------------- Loja
Route::get('/', HomeController::class)->name('home');
Route::get('/produtos', [ProductController::class, 'index'])->name('products.index');
Route::get('/categoria/{category}', [ProductController::class, 'category'])->name('categories.show');
Route::get('/produto/{product}', [ProductController::class, 'show'])->name('products.show');

// ----------------------------------------------------------------- Carrinho
Route::prefix('carrinho')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/adicionar/{product}', [CartController::class, 'add'])->name('add');
    Route::patch('/{product}', [CartController::class, 'update'])->name('update');
    Route::delete('/{product}', [CartController::class, 'remove'])->name('remove');
});

// ----------------------------------------------------------------- Autenticação
Route::middleware('guest')->group(function () {
    Route::get('/entrar', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/entrar', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/cadastro', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/cadastro', [AuthController::class, 'register'])->middleware('throttle:10,1');
});
Route::post('/sair', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ----------------------------------------------------------------- Checkout e área do cliente
Route::middleware('auth')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
    Route::get('/checkout/pagar/{order}', [CheckoutController::class, 'pay'])->name('checkout.pay');
    Route::get('/checkout/retorno/{order}', [CheckoutController::class, 'return'])->name('checkout.return');

    Route::prefix('minha-conta')->name('account.')->group(function () {
        Route::get('/', [AccountController::class, 'orders'])->name('orders');
        Route::get('/pedido/{order}', [AccountController::class, 'order'])->name('order');
        Route::get('/perfil', [AccountController::class, 'profile'])->name('profile');
        Route::put('/perfil', [AccountController::class, 'updateProfile'])->name('profile.update');
        Route::put('/senha', [AccountController::class, 'updatePassword'])->name('password.update');
    });
});

// ----------------------------------------------------------------- Webhook Mercado Pago
Route::post('/webhooks/mercadopago', WebhookController::class)
    ->middleware('throttle:120,1')
    ->name('webhooks.mercadopago');

// ----------------------------------------------------------------- Login do administrador (separado dos clientes)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/entrar', [Admin\AuthController::class, 'showLogin'])->middleware('guest:admin')->name('login');
    Route::post('/entrar', [Admin\AuthController::class, 'login'])->middleware(['guest:admin', 'throttle:5,1']);
    Route::post('/sair', [Admin\AuthController::class, 'logout'])->name('logout');
});

// ----------------------------------------------------------------- Painel administrativo
Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');
    Route::resource('categorias', Admin\CategoryController::class)
        ->parameters(['categorias' => 'category'])->except('show')->names('categories');
    Route::resource('produtos', Admin\ProductController::class)
        ->parameters(['produtos' => 'product'])->except('show')->names('products');
    Route::delete('produtos/imagem/{image}', [Admin\ProductController::class, 'destroyImage'])->name('products.image.destroy');
    Route::get('pedidos', [Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('pedidos/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
    Route::put('pedidos/{order}', [Admin\OrderController::class, 'update'])->name('orders.update');
    Route::post('pedidos/{order}/sincronizar', [Admin\OrderController::class, 'sync'])->name('orders.sync');
    Route::get('clientes', [Admin\CustomerController::class, 'index'])->name('customers.index');
    Route::get('perfil', [Admin\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('perfil', [Admin\ProfileController::class, 'update'])->name('profile.update');
    Route::put('perfil/senha', [Admin\ProfileController::class, 'updatePassword'])->name('profile.password');
});
