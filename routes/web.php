<?php

/**
 * Application web routes
 *
 * This file defines HTTP routes for the application, organized into logical
 * groups. Routes are loaded by the RouteServiceProvider within a group
 * that contains the "web" middleware group.
 */

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PatrimonioController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjetoController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RelatorioBensController;
use App\Http\Controllers\DuplicatePatrimonioController;

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
    Route::get('/completar-perfil',  [ProfileController::class, 'showCompletionForm'])->name('profile.completion.create');
    Route::post('/completar-perfil', [ProfileController::class, 'storeCompletionForm'])->name('profile.completion.store');

    // Relatório / lista
    Route::get('/relatorios/bens',  [RelatorioBensController::class, 'index'])->name('relatorios.bens.index');

    // Cadastrar BEM (form do modal – aba "Bem")
    Route::post('/relatorios/bens', [RelatorioBensController::class, 'store'])->name('relatorios.bens.store');

    // Cadastrar TIPO (form do modal – aba "Tipo")
    Route::post('/tipopatr', [RelatorioBensController::class, 'storeTipo'])->name('tipopatr.store');
});


// GRUPO 2: Rotas principais que EXIGEM perfil completo. NOTE A MUDANÇA AQUI!
// NOTE: Adicionamos 'profile.complete' a este grupo.
Route::middleware(['auth', \App\Http\Middleware\EnsureProfileIsComplete::class])->group(function () {
    // Configuração de Tema (apenas administradores)
    Route::middleware('admin')->group(function () {
        Route::get('/settings/theme', [\App\Http\Controllers\ThemeController::class, 'index'])->name('settings.theme');
        Route::post('/settings/theme', [\App\Http\Controllers\ThemeController::class, 'update'])->name('settings.theme.update');
    });

    // MOVI TODAS AS SUAS ROTAS PRINCIPAIS PARA DENTRO DESTE GRUPO

    // Rota do Dashboard (NUSEQTELA: 1001)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('tela.access:1001');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data')->middleware('tela.access:1001');

    // Rotas do CRUD de Patrimônios e suas APIs (NUSEQTELA: 1000)
    Route::resource('patrimonios', PatrimonioController::class)->middleware(['tela.access:1000', 'can.delete']);
    Route::get('/patrimonios/lookup-codigo', [App\Http\Controllers\PatrimonioController::class, 'lookupCodigo'])->name('patrimonios.lookupCodigo');
    Route::resource('patrimonios', App\Http\Controllers\PatrimonioController::class);
    Route::get('/patrimonios/atribuir/termo', [PatrimonioController::class, 'atribuir'])->name('patrimonios.atribuir');
    // Alias / nova rota para listagem/atribuição via filtros (referenciada em views e redirects)
    // Mantemos a rota original acima para retrocompatibilidade; esta atende chamadas a route('patrimonios.atribuir.codigos')
    Route::get('/patrimonios/atribuir/codigos', [PatrimonioController::class, 'atribuir'])->name('patrimonios.atribuir.codigos');
    Route::post('/patrimonios/atribuir/processar', [PatrimonioController::class, 'processarAtribuicao'])->name('patrimonios.atribuir.processar');
    Route::post('/patrimonios/gerar-codigo', [PatrimonioController::class, 'gerarCodigo'])->name('patrimonios.gerarCodigo');
    Route::post('/patrimonios/atribuir-codigo', [PatrimonioController::class, 'atribuirCodigo'])->name('patrimonios.atribuirCodigo');
    Route::post('/patrimonios/desatribuir-codigo', [PatrimonioController::class, 'desatribuirCodigo'])->name('patrimonios.desatribuirCodigo');
    Route::get('/api/patrimonios/disponiveis', [PatrimonioController::class, 'getPatrimoniosDisponiveis'])->name('api.patrimonios.disponiveis');
    Route::get('/api/patrimonios/buscar/{numero}', [PatrimonioController::class, 'buscarPorNumero'])->name('api.patrimonios.buscar');
    Route::get('/api/patrimonios/pesquisar', [PatrimonioController::class, 'pesquisar'])->name('api.patrimonios.pesquisar');
    // Autocomplete Usuários
    // Rota antiga /api/usuarios/pesquisar removida após migração para funcionários
    // Nova rota: pesquisa de funcionários
    Route::get('/api/funcionarios/pesquisar', [\App\Http\Controllers\FuncionarioController::class, 'pesquisar'])->name('api.funcionarios.pesquisar');

    // Rotas de Projetos e suas APIs (NUSEQTELA: 1003 - Cadastro de Projetos)
    Route::resource('projetos', ProjetoController::class)->middleware(['admin', 'tela.access:1003', 'can.delete']);
    Route::get('projetos/{projeto}/duplicar', [ProjetoController::class, 'duplicate'])->name('projetos.duplicate')->middleware('admin');
    Route::post('projetos/delete-multiple', [ProjetoController::class, 'deleteMultiple'])->name('projetos.delete-multiple')->middleware(['admin', 'can.delete']);
    Route::get('/api/locais/lookup', [ProjetoController::class, 'lookup'])->name('projetos.lookup')->middleware('admin');
    Route::get('/api/projetos/nome/{codigo}', function ($codigo) {
        $p = \App\Models\Tabfant::where('CDPROJETO', $codigo)->first();
        return $p ? response()->json(['exists' => true, 'nome' => $p->NOMEPROJETO]) : response()->json(['exists' => false]);
    });
    Route::get('/api/projetos/buscar/{cdprojeto}', [App\Http\Controllers\PatrimonioController::class, 'buscarProjeto'])->name('api.projetos.buscar');
    Route::get('/api/projetos/pesquisar', [App\Http\Controllers\PatrimonioController::class, 'pesquisarProjetos'])->name('api.projetos.pesquisar');
    Route::get('/api/projetos/por-local/{cdlocal}', [App\Http\Controllers\PatrimonioController::class, 'buscarProjetosPorLocal'])->name('api.projetos.por-local');
    Route::post('/api/projetos/criar', [App\Http\Controllers\PatrimonioController::class, 'criarProjeto'])->name('api.projetos.criar');
    Route::post('/api/projetos/criar-associado', [App\Http\Controllers\PatrimonioController::class, 'criarProjetoAssociado'])->name('api.projetos.criar-associado');
    Route::get('/api/locais/buscar', [App\Http\Controllers\PatrimonioController::class, 'buscarLocais'])->name('api.locais.buscar');
    Route::get('/api/locais/{id}', [App\Http\Controllers\PatrimonioController::class, 'buscarLocalPorId'])->name('api.locais.por-id');
    Route::get('/api/locais/debug', [App\Http\Controllers\PatrimonioController::class, 'debugLocaisPorCodigo'])->name('api.locais.debug');
    Route::post('/api/locais/criar', [App\Http\Controllers\PatrimonioController::class, 'criarLocalVinculadoProjeto'])->name('api.locais.criar');
    Route::post('/api/locais/criar-novo', [App\Http\Controllers\PatrimonioController::class, 'criarNovoLocal'])->name('api.locais.criar-novo');
    Route::post('/api/locais-projetos/criar', [App\Http\Controllers\PatrimonioController::class, 'criarLocalProjeto'])->name('api.locais-projetos.criar');
    Route::post('/api/locais-projetos/criar-simples', [ProjetoController::class, 'criarSimples'])->name('api.locais-projetos.criar-simples');
    Route::post('/api/locais/criar-com-projeto', [App\Http\Controllers\PatrimonioController::class, 'criarLocalComProjeto'])->name('api.locais.criar-com-projeto');
    Route::get('/api/locais/{cdprojeto}', [App\Http\Controllers\PatrimonioController::class, 'getLocaisPorProjeto'])->name('api.locais');
    Route::post('/api/locais/{cdprojeto}', [App\Http\Controllers\PatrimonioController::class, 'criarLocalPorProjeto'])->name('api.locais.criar-por-projeto');

    // Rotas de Códigos (API)
    Route::get('/api/codigos/buscar/{codigo}', [PatrimonioController::class, 'buscarCodigoObjeto'])->name('api.codigos.buscar');
    Route::get('/api/codigos/pesquisar', [PatrimonioController::class, 'pesquisarCodigos'])->name('api.codigos.pesquisar');
    Route::get('/api/codigos/{tipo}', [PatrimonioController::class, 'getCodigosPorTipo'])->name('api.codigos');

    // API para gerar próximo número de patrimônio
    Route::get('/api/patrimonios/proximo-numero', [PatrimonioController::class, 'proximoNumeroPatrimonio'])->name('api.patrimonios.proximo-numero');

    // Rotas de Perfil (editar, atualizar, deletar)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rotas de Usuários (Admin - NUSEQTELA: 1002 - Cadastro de Usuário)
    Route::middleware('admin')->group(function () {
        Route::resource('usuarios', UserController::class)->middleware(['tela.access:1002', 'can.delete']);
        Route::get('usuarios/confirmacao', [UserController::class, 'confirmacao'])->name('usuarios.confirmacao');
        // APIs auxiliares do formulário de usuário
        Route::get('/api/usuarios/por-matricula', [UserController::class, 'porMatricula'])->name('api.usuarios.porMatricula');
        Route::get('/api/usuarios/sugerir-login', [UserController::class, 'sugerirLogin'])->name('api.usuarios.sugerirLogin');
        Route::get('/api/usuarios/login-disponivel', [UserController::class, 'loginDisponivel'])->name('api.usuarios.loginDisponivel');
    });

    // Rotas de Relatórios
    Route::prefix('relatorios')->name('relatorios.')->group(function () {
        // Fluxo original: gerar => retorna JSON para modal de pré-visualização
        Route::post('/patrimonios/gerar', [\App\Http\Controllers\RelatorioController::class, 'gerar'])->name('patrimonios.gerar');
        // Download direto (novo método unificado permanece disponível)
        Route::post('/patrimonios/download', [\App\Http\Controllers\RelatorioController::class, 'download'])->name('patrimonios.download');
        // Rotas legacy usadas pelo modal/JS (mantidas para não quebrar fluxo existente)
        Route::post('/patrimonios/exportar/excel', [\App\Http\Controllers\RelatorioController::class, 'exportarExcel'])->name('patrimonios.exportar.excel');
        Route::post('/patrimonios/exportar/csv', [\App\Http\Controllers\RelatorioController::class, 'exportarCsv'])->name('patrimonios.exportar.csv');
        Route::post('/patrimonios/exportar/pdf', [\App\Http\Controllers\RelatorioController::class, 'exportarPdf'])->name('patrimonios.exportar.pdf');
        Route::post('/patrimonios/exportar/ods', [\App\Http\Controllers\RelatorioController::class, 'exportarOds'])->name('patrimonios.exportar.ods');

        // Relatório de Funcionários (Excel)
        Route::get('/funcionarios/exportar/excel', [\App\Http\Controllers\RelatorioController::class, 'exportarFuncionariosExcel'])->name('funcionarios.exportar.excel');
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

    // Debug do tema (apenas em ambiente local ou se user for admin)
    Route::get('/debug/theme', function (\Illuminate\Http\Request $request) {
        /** @var \App\Models\User|null $user */
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!app()->environment('local') && optional($user)->PERFIL !== 'ADM') {
            abort(403);
        }
        $shared = \Illuminate\Support\Facades\View::getShared();
        return response()->json([
            'activeThemeShared' => $shared['activeTheme'] ?? null,
            'session' => session('theme'),
            'user' => $user?->theme,
            'cookie' => $request->cookie('theme'),
            'html_data_theme' => null,
        ]);
    })->name('debug.theme');

    // Rotas protegidas para Cadastro de Tela (apenas administradores)
    Route::middleware(['auth'])->group(function () {
        Route::get('/cadastro-tela', [\App\Http\Controllers\CadastroTelaController::class, 'index'])->name('cadastro-tela.index');
        Route::post('/cadastro-tela', [\App\Http\Controllers\CadastroTelaController::class, 'store'])->name('cadastro-tela.store');
        Route::post('/cadastro-tela/show-form/{nome}', [\App\Http\Controllers\CadastroTelaController::class, 'showForm'])->name('cadastro-tela.showForm');
        Route::post('/cadastro-tela/gerar-vincular/{nome}', [\App\Http\Controllers\CadastroTelaController::class, 'gerarVincular'])->name('cadastro-tela.gerarVincular');
        Route::post('/cadastro-tela/vincular-todas', [\App\Http\Controllers\CadastroTelaController::class, 'vincularTodas'])->name('cadastro-tela.vincularTodas');
    });
});
