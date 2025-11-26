<?php
// Arquivo de teste temporário para debug do sistema de acessos
// Para usar: acesse /debug-acessos no navegador quando logado

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Helpers\MenuHelper;

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
            'is_super_admin' => $user->isSuperAdmin(),
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
            '1006' => MenuHelper::isTelaObrigatoria('1006'),
            '1007' => MenuHelper::isTelaObrigatoria('1007'),
        ],
        'verificacao_acesso' => [
            '1000_patrimonio' => [
                'tem_acesso' => $user->temAcessoTela('1000'),
                'deve_aparecer_menu' => MenuHelper::deveAparecerNoMenu('1000'),
            ],
            '1001_dashboard' => [
                'tem_acesso' => $user->temAcessoTela('1001'),
                'deve_aparecer_menu' => MenuHelper::deveAparecerNoMenu('1001'),
            ],
            '1002_locais' => [
                'tem_acesso' => $user->temAcessoTela('1002'),
                'deve_aparecer_menu' => MenuHelper::deveAparecerNoMenu('1002'),
            ],
            '1003_usuarios' => [
                'tem_acesso' => $user->temAcessoTela('1003'),
                'deve_aparecer_menu' => MenuHelper::deveAparecerNoMenu('1003'),
            ],
            '1006_relatorios' => [
                'tem_acesso' => MenuHelper::temAcessoTela('1006'),
                'deve_aparecer_menu' => MenuHelper::deveAparecerNoMenu('1006'),
                'is_obrigatoria' => MenuHelper::isTelaObrigatoria('1006'),
            ],
            '1007_historico' => [
                'tem_acesso' => MenuHelper::temAcessoTela('1007'),
                'deve_aparecer_menu' => MenuHelper::deveAparecerNoMenu('1007'),
                'is_obrigatoria' => MenuHelper::isTelaObrigatoria('1007'),
            ],
        ],
        'telas_com_acesso' => MenuHelper::getTelasComAcesso(),
        'telas_para_menu' => array_keys(MenuHelper::getTelasParaMenu()),
    ];

    return response()->json($debug, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
})->middleware('auth');
