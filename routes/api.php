<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ClienteController;
use App\Http\Controllers\Api\V1\EnderecoController;
use App\Http\Controllers\Api\V1\LojaController;
use App\Http\Controllers\Api\V1\NotificacaoController;
use App\Http\Controllers\Api\V1\PedidoController;
use App\Http\Controllers\Api\V1\RelacionamentoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API do app (app-cunhas3d) - endereço base: /api/v1
|--------------------------------------------------------------------------
| Login por token (Laravel Sanctum): o app manda "Authorization: Bearer <token>".
*/

Route::prefix('v1')->group(function () {
    // ---------------------------------------------------------- Públicas
    Route::get('loja', [LojaController::class, 'loja']);
    Route::get('categorias', [LojaController::class, 'categorias']);
    Route::get('produtos', [LojaController::class, 'produtos']);
    Route::get('produtos/{slug}', [LojaController::class, 'produto']);
    Route::post('sacola/calcular', [LojaController::class, 'calcularSacola']);
    Route::get('depoimentos', [RelacionamentoController::class, 'depoimentos']);

    // Notificações no app (com ou sem login)
    Route::get('notificacoes/chave', [NotificacaoController::class, 'chave']);
    Route::middleware('throttle:20,1')->group(function () {
        Route::post('notificacoes', [NotificacaoController::class, 'inscrever']);
        Route::delete('notificacoes', [NotificacaoController::class, 'cancelar']);
    });
    Route::post('notificacoes/teste', [NotificacaoController::class, 'testar'])->middleware('throttle:5,1');

    Route::middleware('throttle:10,1')->group(function () {
        Route::post('auth/login', [AuthController::class, 'login']);
        Route::post('auth/cadastro', [AuthController::class, 'cadastro']);
        Route::post('auth/esqueci-senha', [AuthController::class, 'esqueciSenha']);
        Route::post('auth/redefinir-senha', [AuthController::class, 'redefinirSenha']);
    });

    // ---------------------------------------------------------- Cliente logado
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::get('cliente', [ClienteController::class, 'show']);
        Route::put('cliente', [ClienteController::class, 'update']);
        Route::put('cliente/senha', [ClienteController::class, 'senha']);

        Route::get('enderecos', [EnderecoController::class, 'index']);
        Route::post('enderecos', [EnderecoController::class, 'store']);
        Route::put('enderecos/{endereco}', [EnderecoController::class, 'update']);
        Route::delete('enderecos/{endereco}', [EnderecoController::class, 'destroy']);
        Route::post('enderecos/{endereco}/principal', [EnderecoController::class, 'principal']);

        Route::get('pedidos', [PedidoController::class, 'index']);
        Route::post('pedidos', [PedidoController::class, 'store']);
        Route::get('pedidos/{codigo}', [PedidoController::class, 'show']);
        Route::post('pedidos/{codigo}/pagar', [PedidoController::class, 'pagar']);

        Route::get('depoimentos/meus', [RelacionamentoController::class, 'meusDepoimentos']);
        Route::post('depoimentos', [RelacionamentoController::class, 'enviarDepoimento']);
        Route::post('contato', [RelacionamentoController::class, 'contato']);
    });
});
