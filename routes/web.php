<?php
// DENTRO DE routes/web.php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PatrimonioController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjetoController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Rota principal redireciona para o login
Route::get('/', function () {
    return redirect()->route('login');
});

// Inclui as rotas de autenticação (login, logout, etc.)
// É importante que auth.php esteja aqui para que as rotas de login/logout funcionem
require __DIR__ . '/auth.php';


// --- ESTRUTURA CORRIGIDA ---

// GRUPO 1: Apenas para o formulário de completar o perfil.
// Protegido apenas por 'auth', para garantir que o usuário esteja logado.
Route::middleware('auth')->group(function () {
    Route::get('/completar-perfil', [ProfileController::class, 'showCompletionForm'])
        ->name('profile.completion.create');
    Route::post('/completar-perfil', [ProfileController::class, 'storeCompletionForm'])
        ->name('profile.completion.store');
});

// GRUPO 2: Rotas principais que EXIGEM perfil completo. NOTE A MUDANÇA AQUI!
// NOTE: Adicionamos 'profile.complete' a este grupo.
Route::middleware(['auth', \App\Http\Middleware\EnsureProfileIsComplete::class])->group(function () {

    // MOVI TODAS AS SUAS ROTAS PRINCIPAIS PARA DENTRO DESTE GRUPO

    // Rota do Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');

    // Rotas do CRUD de Patrimônios e suas APIs
    Route::resource('patrimonios', PatrimonioController::class);
    Route::get('/patrimonios/atribuir/termo', [PatrimonioController::class, 'atribuir'])->name('patrimonios.atribuir');
    Route::post('/patrimonios/atribuir/processar', [PatrimonioController::class, 'processarAtribuicao'])->name('patrimonios.atribuir.processar');
    Route::get('/api/patrimonios/disponiveis', [PatrimonioController::class, 'getPatrimoniosDisponiveis'])->name('api.patrimonios.disponiveis');
    Route::get('/api/patrimonios/buscar/{numero}', [PatrimonioController::class, 'buscarPorNumero'])->name('api.patrimonios.buscar');
    Route::get('/api/patrimonios/pesquisar', [PatrimonioController::class, 'pesquisar'])->name('api.patrimonios.pesquisar');

    // Rotas de Projetos e suas APIs
    Route::resource('projetos', ProjetoController::class)->middleware('admin');
    Route::get('/api/projetos/nome/{codigo}', function ($codigo) {
        $p = \App\Models\Tabfant::where('CDPROJETO', $codigo)->first();
        return $p ? response()->json(['exists' => true, 'nome' => $p->NOMEPROJETO]) : response()->json(['exists' => false]);
    });
    Route::get('/api/projetos/buscar/{cdprojeto}', [App\Http\Controllers\PatrimonioController::class, 'buscarProjeto'])->name('api.projetos.buscar');
    Route::get('/api/locais/{cdprojeto}', [App\Http\Controllers\PatrimonioController::class, 'getLocaisPorProjeto'])->name('api.locais');

    // Rotas de Códigos (API)
    Route::get('/api/codigos/buscar/{codigo}', [PatrimonioController::class, 'buscarCodigoObjeto'])->name('api.codigos.buscar');
    Route::get('/api/codigos/{tipo}', [PatrimonioController::class, 'getCodigosPorTipo'])->name('api.codigos');

    // Rotas de Perfil (editar, atualizar, deletar)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rotas de Usuários (Admin)
    Route::middleware('admin')->group(function () {
        Route::resource('usuarios', UserController::class);
    });

    // Rotas de Relatórios
    Route::prefix('relatorios')->name('relatorios.')->group(function () {
        Route::get('/patrimonios', [\App\Http\Controllers\RelatorioController::class, 'create'])->name('patrimonios.create');
        Route::post('/patrimonios', [\App\Http\Controllers\RelatorioController::class, 'gerar'])->name('patrimonios.gerar');
        Route::post('/patrimonios/exportar/excel', [\App\Http\Controllers\RelatorioController::class, 'exportarExcel'])->name('patrimonios.exportar.excel');
        Route::post('/patrimonios/exportar/csv', [\App\Http\Controllers\RelatorioController::class, 'exportarCsv'])->name('patrimonios.exportar.csv');
        Route::post('/patrimonios/exportar/ods', [\App\Http\Controllers\RelatorioController::class, 'exportarOds'])->name('patrimonios.exportar.ods');
        Route::post('/patrimonios/exportar/pdf', [\App\Http\Controllers\RelatorioController::class, 'exportarPdf'])->name('patrimonios.exportar.pdf');
    });

    // Rotas de Termos
    Route::prefix('termos')->name('termos.')->group(function () {
        Route::post('/atribuir', [\App\Http\Controllers\TermoController::class, 'store'])->name('atribuir.store');
        Route::post('/exportar/excel', [\App\Http\Controllers\TermoController::class, 'exportarExcel'])->name('exportar.excel');
        Route::post('/desatribuir', [\App\Http\Controllers\TermoController::class, 'desatribuir'])->name('desatribuir');
        Route::get('/codigos', [\App\Http\Controllers\TermoController::class, 'listarCodigos'])->name('codigos.index');
        Route::post('/codigos', [\App\Http\Controllers\TermoController::class, 'criarCodigo'])->name('codigos.store');
        Route::get('/codigos/sugestao', [\App\Http\Controllers\TermoController::class, 'sugestaoCodigo'])->name('codigos.sugestao');
    });

    // Rota de Histórico
    Route::get('/historico', [\App\Http\Controllers\HistoricoController::class, 'index'])->name('historico.index');
});
