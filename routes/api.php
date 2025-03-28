<?php

use App\Http\Api\ApiAuthController;
use App\Http\Api\ApiFotoPessoaController;
use App\Http\Api\ApiLotacaoController;
use App\Http\Api\ApiServidorEfetivoController;
use App\Http\Api\ApiServidorTemporarioController;
use App\Http\Api\ApiUnidadeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Rotas públicas para autenticação
Route::post('/login', [ApiAuthController::class, 'login']);
Route::post('/refresh', [ApiAuthController::class, 'refresh']);

// Rotas protegidas por autenticação e domínio
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [ApiAuthController::class, 'logout']);

    // Servidores Efetivos
    Route::get('/servidores-efetivos', [ApiServidorEfetivoController::class, 'index']);
    Route::post('/servidores-efetivos', [ApiServidorEfetivoController::class, 'store']);
    Route::get('/servidores-efetivos/{id}', [ApiServidorEfetivoController::class, 'show']);
    Route::put('/servidores-efetivos/{id}', [ApiServidorEfetivoController::class, 'update']);
    Route::delete('/servidores-efetivos/{id}', [ApiServidorEfetivoController::class, 'destroy']);

    // Consultas específicas
    Route::get('/servidores-efetivos/unidade/{unidadeId}', [ApiServidorEfetivoController::class, 'porUnidade']);
    Route::post('/servidores-efetivos/buscar-por-nome', [ApiServidorEfetivoController::class, 'buscarPorNome']);

    // Servidores Temporários
    Route::get('/servidores-temporarios', [ApiServidorTemporarioController::class, 'index']);
    Route::post('/servidores-temporarios', [ApiServidorTemporarioController::class, 'store']);
    Route::get('/servidores-temporarios/{id}', [ApiServidorTemporarioController::class, 'show']);
    Route::put('/servidores-temporarios/{id}', [ApiServidorTemporarioController::class, 'update']);
    Route::delete('/servidores-temporarios/{id}', [ApiServidorTemporarioController::class, 'destroy']);

    // Unidades
    Route::get('/unidades', [ApiUnidadeController::class, 'index']);
    Route::post('/unidades', [ApiUnidadeController::class, 'store']);
    Route::get('/unidades/{id}', [ApiUnidadeController::class, 'show']);
    Route::put('/unidades/{id}', [ApiUnidadeController::class, 'update']);
    Route::delete('/unidades/{id}', [ApiUnidadeController::class, 'destroy']);

    // Lotações
    Route::get('/lotacoes', [ApiLotacaoController::class, 'index']);
    Route::post('/lotacoes', [ApiLotacaoController::class, 'store']);
    Route::get('/lotacoes/{id}', [ApiLotacaoController::class, 'show']);
    Route::put('/lotacoes/{id}', [ApiLotacaoController::class, 'update']);
    Route::delete('/lotacoes/{id}', [ApiLotacaoController::class, 'destroy']);

    // Fotos
    Route::post('/pessoas/{pessoaId}/fotos', [ApiFotoPessoaController::class, 'store']);
    Route::get('/fotos/{id}', [ApiFotoPessoaController::class, 'show']);
    Route::delete('/fotos/{id}', [ApiFotoPessoaController::class, 'destroy']);
    Route::get('/pessoas/{pessoaId}/fotos', [ApiFotoPessoaController::class, 'listarPorPessoa']);
});
