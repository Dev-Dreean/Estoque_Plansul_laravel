<?php

/**
 * Application web routes
 *
 * This file defines HTTP routes for the application, organized into logical
 * groups. Routes are loaded by the RouteServiceProvider within a group
 * that contains the "web" middleware group.
 */

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PatrimonioController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjetoController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\RelatorioBensController;
use App\Http\Controllers\DuplicatePatrimonioController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Rota principal redireciona para /menu (página principal agora)
Route::get('/', function () {
    return redirect()->route('menu.index');
});

// Inclui as rotas de autenticação (login, logout, etc.)
// É importante que auth.php esteja aqui para que as rotas de login/logout funcionem
require __DIR__ . '/auth.php';

// Menu Principal - PÚBLICA (acessível para autenticados e não autenticados)
Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');

// API para obter clima - PÚBLICA (para usuários não autenticados)
Route::get('/api/weather', [MenuController::class, 'getWeather'])->name('api.weather');

// Debug de acessos (apenas em desenvolvimento)
Route::get('/debug-acessos', function () {
    if (!Auth::check()) {
        return 'Faça login primeiro!';
    }

    /** @var \App\Models\User $user */
    $user = Auth::user();
    
    $debug = [
        'usuario' => [
            'nome' => $user->NOMEUSER,
            'login' => $user->NMLOGIN,
            'perfil' => $user->PERFIL,
            'matricula' => $user->CDMATRFUNCIONARIO,
            'is_god' => $user->isGod(),
            'is_admin' => $user->isAdmin(),
        ],
        'permissoes_banco' => $user->acessos()
            ->select('NUSEQTELA', 'INACESSO')
            ->get()
            ->map(function($acesso) {
                return [
                    'tela' => $acesso->NUSEQTELA,
                    'acesso' => $acesso->INACESSO,
                    'acesso_bool' => $acesso->INACESSO === 'S',
                ];
            })
            ->toArray(),
        'telas_obrigatorias' => [
            '1006' => App\Helpers\MenuHelper::isTelaObrigatoria('1006'),
            '1007' => App\Helpers\MenuHelper::isTelaObrigatoria('1007'),
        ],
        'verificacao_acesso' => [
            '1000_patrimonio' => [
                'tem_acesso' => $user->temAcessoTela('1000'),
                'deve_aparecer_menu' => App\Helpers\MenuHelper::deveAparecerNoMenu('1000'),
            ],
            '1001_dashboard' => [
                'tem_acesso' => $user->temAcessoTela('1001'),
                'deve_aparecer_menu' => App\Helpers\MenuHelper::deveAparecerNoMenu('1001'),
            ],
            '1002_locais' => [
                'tem_acesso' => $user->temAcessoTela('1002'),
                'deve_aparecer_menu' => App\Helpers\MenuHelper::deveAparecerNoMenu('1002'),
            ],
            '1003_usuarios' => [
                'tem_acesso' => $user->temAcessoTela('1003'),
                'deve_aparecer_menu' => App\Helpers\MenuHelper::deveAparecerNoMenu('1003'),
            ],
            '1006_relatorios' => [
                'tem_acesso' => App\Helpers\MenuHelper::temAcessoTela('1006'),
                'deve_aparecer_menu' => App\Helpers\MenuHelper::deveAparecerNoMenu('1006'),
                'is_obrigatoria' => App\Helpers\MenuHelper::isTelaObrigatoria('1006'),
            ],
            '1007_historico' => [
                'tem_acesso' => App\Helpers\MenuHelper::temAcessoTela('1007'),
                'deve_aparecer_menu' => App\Helpers\MenuHelper::deveAparecerNoMenu('1007'),
                'is_obrigatoria' => App\Helpers\MenuHelper::isTelaObrigatoria('1007'),
            ],
        ],
        'telas_com_acesso' => App\Helpers\MenuHelper::getTelasComAcesso(),
        'telas_para_menu' => array_keys(App\Helpers\MenuHelper::getTelasParaMenu()),
    ];

    return response()->json($debug, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
})->middleware('auth')->name('debug.acessos');


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
    Route::middleware(['admin', 'tela.access:1008'])->group(function () {
        Route::get('/settings/theme', [\App\Http\Controllers\ThemeController::class, 'index'])->name('settings.theme');
        Route::post('/settings/theme', [\App\Http\Controllers\ThemeController::class, 'update'])->name('settings.theme.update');
    });

    // MOVI TODAS AS SUAS ROTAS PRINCIPAIS PARA DENTRO DESTE GRUPO

    // Rota do Dashboard/Gráficos (NUSEQTELA: 1008)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('tela.access:1001');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data')->middleware('tela.access:1001');

    // Rotas do CRUD de Patrimônios e suas APIs (NUSEQTELA: 1000)
    Route::resource('patrimonios', PatrimonioController::class)->middleware(['tela.access:1000']);
    
    // Rota de teste para deleção
    Route::delete('/patrimonio/delete/{id}', [PatrimonioController::class, 'deletePatrimonio'])->name('patrimonio.delete.test');
    Route::get('/patrimonios/lookup-codigo', [App\Http\Controllers\PatrimonioController::class, 'lookupCodigo'])->name('patrimonios.lookupCodigo');
    Route::get('/patrimonios/atribuir/termo', [PatrimonioController::class, 'atribuir'])->name('patrimonios.atribuir');
    // Alias / nova rota para listagem/atribuição via filtros (referenciada em views e redirects)
    // Mantemos a rota original acima para retrocompatibilidade; esta atende chamadas a route('patrimonios.atribuir.codigos')
    Route::get('/patrimonios/atribuir/codigos', [PatrimonioController::class, 'atribuir'])->name('patrimonios.atribuir.codigos');
    Route::post('/patrimonios/atribuir/processar', [PatrimonioController::class, 'processarAtribuicao'])->name('patrimonios.atribuir.processar');
    Route::post('/patrimonios/gerar-codigo', [PatrimonioController::class, 'gerarCodigo'])->name('patrimonios.gerarCodigo');
    Route::post('/patrimonios/atribuir-codigo', [PatrimonioController::class, 'atribuirCodigo'])->name('patrimonios.atribuirCodigo');
    Route::post('/patrimonios/desatribuir-codigo', [PatrimonioController::class, 'desatribuirCodigo'])->name('patrimonios.desatribuirCodigo');
    Route::post('/patrimonios/bulk-situacao', [PatrimonioController::class, 'bulkSituacao'])->name('patrimonios.bulk-situacao');
    Route::post('/patrimonios/bulk-delete', [PatrimonioController::class, 'bulkDelete'])->name('patrimonios.bulk-delete');
    Route::get('/api/patrimonios/disponiveis', [PatrimonioController::class, 'getPatrimoniosDisponiveis'])->name('api.patrimonios.disponiveis');
    Route::get('/api/patrimonios/buscar/{numero}', [PatrimonioController::class, 'buscarPorNumero'])->name('api.patrimonios.buscar');
    Route::get('/api/patrimonios/id/{id}', [PatrimonioController::class, 'buscarPorId'])->name('api.patrimonios.buscarId');
    Route::get('/api/patrimonios/pesquisar', [PatrimonioController::class, 'pesquisar'])->name('api.patrimonios.pesquisar');
    Route::get('/api/patrimonios/listar-cadastradores', [PatrimonioController::class, 'listarCadradores'])->name('api.patrimonios.listar-cadastradores');
    // Autocomplete Usuários
    // Rota antiga /api/usuarios/pesquisar removida após migração para funcionários
    // Nova rota: pesquisa de funcionários
    Route::get('/api/funcionarios/pesquisar', [\App\Http\Controllers\FuncionarioController::class, 'pesquisar'])->name('api.funcionarios.pesquisar');

    // API de cadastradores (usuários que cadastraram patrimônios)
    Route::get('/api/cadastradores/pesquisar', [PatrimonioController::class, 'pesquisarCadastradores'])->name('api.cadastradores.pesquisar');
    Route::get('/api/cadastradores/nomes', [PatrimonioController::class, 'buscarNomesCadastradores'])->name('api.cadastradores.nomes');

    // Rotas de Projetos/Locais e suas APIs (T:1002 - Cadastro de Locais)
    Route::resource('projetos', ProjetoController::class)->middleware(['tela.access:1002', 'can.delete']);
    Route::get('projetos/{projeto}/duplicar', [ProjetoController::class, 'duplicate'])->name('projetos.duplicate')->middleware('tela.access:1002');
    Route::post('projetos/delete-multiple', [ProjetoController::class, 'deleteMultiple'])->name('projetos.delete-multiple')->middleware(['tela.access:1002', 'can.delete']);
    Route::get('/api/locais/lookup', [ProjetoController::class, 'lookup'])->name('projetos.lookup')->middleware('tela.access:1002');
    Route::get('/api/projetos/nome/{codigo}', function ($codigo) {
        $p = \App\Models\Tabfant::where('CDPROJETO', $codigo)->first();
        return $p ? response()->json(['exists' => true, 'nome' => $p->NOMEPROJETO]) : response()->json(['exists' => false]);
    });
    Route::get('/api/projetos/buscar/{cdprojeto}', [App\Http\Controllers\PatrimonioController::class, 'buscarProjeto'])->name('api.projetos.buscar');
    Route::get('/api/projetos/pesquisar', [App\Http\Controllers\PatrimonioController::class, 'pesquisarProjetos'])->name('api.projetos.pesquisar');
    Route::get('/api/projetos/por-local/{cdlocal}', [App\Http\Controllers\PatrimonioController::class, 'buscarProjetosPorLocal'])->name('api.projetos.por-local');
    Route::post('/api/projetos/criar', [App\Http\Controllers\PatrimonioController::class, 'criarProjeto'])->name('api.projetos.criar')->middleware('tela.access:1002');
    Route::post('/api/projetos/criar-associado', [App\Http\Controllers\PatrimonioController::class, 'criarProjetoAssociado'])->name('api.projetos.criar-associado')->middleware('tela.access:1002');
    Route::get('/api/locais/buscar', [App\Http\Controllers\PatrimonioController::class, 'buscarLocais'])->name('api.locais.buscar');
    Route::get('/api/locais/{id}', [App\Http\Controllers\PatrimonioController::class, 'buscarLocalPorId'])->name('api.locais.por-id');
    Route::get('/api/locais/debug', [App\Http\Controllers\PatrimonioController::class, 'debugLocaisPorCodigo'])->name('api.locais.debug');
    Route::post('/api/locais/criar', [App\Http\Controllers\PatrimonioController::class, 'criarLocalVinculadoProjeto'])->name('api.locais.criar')->middleware('auth');
    Route::post('/api/locais/criar-novo', [App\Http\Controllers\PatrimonioController::class, 'criarNovoLocal'])->name('api.locais.criar-novo')->middleware('auth');
    Route::post('/api/locais-projetos/criar', [App\Http\Controllers\PatrimonioController::class, 'criarLocalProjeto'])->name('api.locais-projetos.criar')->middleware('auth');
    Route::post('/api/locais-projetos/criar-simples', [ProjetoController::class, 'criarSimples'])->name('api.locais-projetos.criar-simples')->middleware('auth');
    Route::post('/api/locais/criar-com-projeto', [App\Http\Controllers\PatrimonioController::class, 'criarLocalComProjeto'])->name('api.locais.criar-com-projeto')->middleware('auth');
    // ⚠️ Evitar rotas ambíguas como /api/locais/{cdprojeto} (colide com /api/locais/{id}).
    // Padronizar sempre em: /api/locais/buscar?cdprojeto=<CDPROJETO>&termo=

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

    // Rotas de Usuários (T:1003)
    Route::resource('usuarios', UserController::class)->middleware(['tela.access:1003', 'can.delete']);
    Route::get('usuarios/confirmacao', [UserController::class, 'confirmacao'])->name('usuarios.confirmacao')->middleware('tela.access:1003');
    Route::get('/usuarios/{usuario}/supervisao', [UserController::class, 'gerenciarSupervisao'])->name('usuarios.supervisao')->middleware('tela.access:1003');
    Route::put('/usuarios/{usuario}/supervisao', [UserController::class, 'atualizarSupervisao'])->name('usuarios.supervisao.update')->middleware('tela.access:1003');
    // Impersonation / developer helpers (restritos a admin ou ambiente local dentro do controller)
    Route::post('/usuarios/{usuario}/impersonate', [UserController::class, 'impersonate'])->name('usuarios.impersonate')->middleware('auth');
    Route::post('/impersonate/stop', [UserController::class, 'stopImpersonate'])->name('impersonate.stop')->middleware('auth');
    Route::post('/usuarios/{usuario}/reset-senha', [UserController::class, 'resetSenha'])->name('usuarios.resetSenha')->middleware('auth');
    // APIs auxiliares do formulário de usuário
    Route::get('/api/usuarios/por-matricula', [UserController::class, 'porMatricula'])->name('api.usuarios.porMatricula')->middleware('tela.access:1003');
    Route::get('/api/usuarios/sugerir-login', [UserController::class, 'sugerirLogin'])->name('api.usuarios.sugerirLogin')->middleware('tela.access:1003');
    Route::get('/api/usuarios/login-disponivel', [UserController::class, 'loginDisponivel'])->name('api.usuarios.loginDisponivel')->middleware('tela.access:1003');

    // Rotas de Relatórios
    Route::prefix('relatorios')->name('relatorios.')->middleware('tela.access:1006')->group(function () {
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
        // Rotas DOCX usando PhpWord TemplateProcessor
        Route::post('/docx/zip', [\App\Http\Controllers\TermoDocxController::class, 'downloadZip'])->name('docx.zip');
        Route::get('/docx/{id}', [\App\Http\Controllers\TermoDocxController::class, 'downloadSingle'])->name('docx.single');
    });

    // Rota de Histórico
    Route::get('/historico', [\App\Http\Controllers\HistoricoController::class, 'index'])->name('historico.index')->middleware('tela.access:1007');

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

    // Rotas protegidas para Cadastro de Tela (NUSEQTELA: 1006)
    Route::middleware(['auth', 'tela.access:1004'])->group(function () {
        Route::get('/cadastro-tela', [\App\Http\Controllers\CadastroTelaController::class, 'index'])->name('cadastro-tela.index');
        Route::post('/cadastro-tela', [\App\Http\Controllers\CadastroTelaController::class, 'store'])->name('cadastro-tela.store');
        Route::post('/cadastro-tela/show-form/{nome}', [\App\Http\Controllers\CadastroTelaController::class, 'showForm'])->name('cadastro-tela.showForm');
        Route::post('/cadastro-tela/gerar-vincular/{nome}', [\App\Http\Controllers\CadastroTelaController::class, 'gerarVincular'])->name('cadastro-tela.gerarVincular');
        Route::post('/cadastro-tela/vincular-todas', [\App\Http\Controllers\CadastroTelaController::class, 'vincularTodas'])->name('cadastro-tela.vincularTodas');
    });

    // Navegador lateral beta (projeto paralelo)
    Route::get('/navigator-beta', [PatrimonioController::class, 'navigatorBeta'])->name('navigator.beta')->middleware('auth');
});
