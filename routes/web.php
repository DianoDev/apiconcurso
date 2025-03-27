<?php

use App\Http\Controllers\FotoPessoaController;
use App\Http\Controllers\LotacaoController;
use App\Http\Controllers\ServidorEfetivoController;
use App\Http\Controllers\ServidorTemporarioController;
use App\Http\Controllers\UnidadeController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Servidores Efetivos
    //Route::resource('servidor-efetivo', ServidorEfetivoController::class);

    // Servidores Temporários
    Route::resource('servidor-temporario', ServidorTemporarioController::class);

    // Unidades
    Route::resource('unidade', UnidadeController::class);

    // Lotações
    Route::resource('lotacao', LotacaoController::class);

    // Fotos
    Route::get('/pessoas/{pessoaId}/fotos/create', [FotoPessoaController::class, 'create'])->name('fotos.create');
    Route::post('/pessoas/{pessoaId}/fotos', [FotoPessoaController::class, 'store'])->name('fotos.store');
    Route::delete('/fotos/{id}', [FotoPessoaController::class, 'destroy'])->name('fotos.destroy');
});
