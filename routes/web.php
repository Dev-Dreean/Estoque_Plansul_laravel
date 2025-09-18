<?php

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

// Rota do Dashboard, acessível apenas para usuários logados
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

// Endpoint que retorna dados agregados para o gráfico do dashboard
Route::get('/dashboard/data', [DashboardController::class, 'data'])
    ->middleware(['auth', 'verified'])->name('dashboard.data');

Route::get('/api/codigos/buscar/{codigo}', [PatrimonioController::class, 'buscarCodigoObjeto'])
    ->name('api.codigos.buscar');

// Grupo de rotas que exigem autenticação
Route::middleware('auth')->group(function () {

    Route::resource('projetos', ProjetoController::class)->middleware('admin');
    Route::get('/api/projetos/nome/{codigo}', function ($codigo) {
        $p = \App\Models\Tabfant::where('CDPROJETO', $codigo)->first();
        return $p ? response()->json(['exists' => true, 'nome' => $p->NOMEPROJETO]) : response()->json(['exists' => false]);
    })->middleware('auth');

    Route::get('/api/projetos/buscar/{cdprojeto}', [App\Http\Controllers\PatrimonioController::class, 'buscarProjeto'])->name('api.projetos.buscar');
    Route::get('/api/locais/{cdprojeto}', [App\Http\Controllers\PatrimonioController::class, 'getLocaisPorProjeto'])->name('api.locais');

    // Rotas de Perfil (ocultas, mas funcionais)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rotas do CRUD de Patrimônios (index, create, store, etc.)
    Route::resource('patrimonios', PatrimonioController::class);

    // Rotas para página dedicada de atribuição de códigos de termo
    Route::get('/patrimonios/atribuir/termo', [PatrimonioController::class, 'atribuir'])
        ->name('patrimonios.atribuir');
    Route::post('/patrimonios/atribuir/processar', [PatrimonioController::class, 'processarAtribuicao'])
        ->name('patrimonios.atribuir.processar');

    // API específica para modal de atribuir termo - não afeta URL principal
    Route::get('/api/patrimonios/disponiveis', [PatrimonioController::class, 'getPatrimoniosDisponiveis'])
        ->name('api.patrimonios.disponiveis');

    // Rotas de API para funcionalidades dinâmicas do formulário de patrimônio
    Route::get('/api/patrimonios/buscar/{numero}', [PatrimonioController::class, 'buscarPorNumero'])->name('api.patrimonios.buscar');
    Route::get('/api/patrimonios/pesquisar', [PatrimonioController::class, 'pesquisar'])->name('api.patrimonios.pesquisar');
    Route::get('/api/codigos/buscar/{codigo}', [PatrimonioController::class, 'buscarCodigoObjeto'])->name('api.codigos.buscar');
    Route::get('/api/codigos/{tipo}', [PatrimonioController::class, 'getCodigosPorTipo'])->name('api.codigos');

    // Grupo de rotas acessíveis apenas por administradores
    Route::middleware('admin')->group(function () {
        Route::resource('usuarios', UserController::class);
    });

    // Grupo para todas as funcionalidades de relatório
    Route::prefix('relatorios')->name('relatorios.')->group(function () {
        Route::get('/patrimonios', [\App\Http\Controllers\RelatorioController::class, 'create'])->name('patrimonios.create');
        Route::post('/patrimonios', [\App\Http\Controllers\RelatorioController::class, 'gerar'])->name('patrimonios.gerar');

        // Rotas de exportação
        Route::post('/patrimonios/exportar/excel', [\App\Http\Controllers\RelatorioController::class, 'exportarExcel'])->name('patrimonios.exportar.excel');
        Route::post('/patrimonios/exportar/csv', [\App\Http\Controllers\RelatorioController::class, 'exportarCsv'])->name('patrimonios.exportar.csv');
        Route::post('/patrimonios/exportar/ods', [\App\Http\Controllers\RelatorioController::class, 'exportarOds'])->name('patrimonios.exportar.ods');
        Route::post('/patrimonios/exportar/pdf', [\App\Http\Controllers\RelatorioController::class, 'exportarPdf'])->name('patrimonios.exportar.pdf');
    });

    Route::prefix('termos')->name('termos.')->group(function () {
        Route::post('/atribuir', [\App\Http\Controllers\TermoController::class, 'store'])->name('atribuir.store');
        Route::post('/exportar/excel', [\App\Http\Controllers\TermoController::class, 'exportarExcel'])->name('exportar.excel');
        Route::post('/desatribuir', [\App\Http\Controllers\TermoController::class, 'desatribuir'])->name('desatribuir');
        // Gerenciamento de códigos de termo (JSON)
        Route::get('/codigos', [\App\Http\Controllers\TermoController::class, 'listarCodigos'])->name('codigos.index');
        Route::post('/codigos', [\App\Http\Controllers\TermoController::class, 'criarCodigo'])->name('codigos.store');
        Route::get('/codigos/sugestao', [\App\Http\Controllers\TermoController::class, 'sugestaoCodigo'])->name('codigos.sugestao');
    });

    // Histórico de movimentações
    Route::get('/historico', [\App\Http\Controllers\HistoricoController::class, 'index'])->name('historico.index');
});

// Inclui as rotas de autenticação (login, logout, etc.)
require __DIR__ . '/auth.php';
