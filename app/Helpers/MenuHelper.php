<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class MenuHelper
{
    /**
     * Telas que são sempre obrigatórias e não aparecem no menu principal
     * mas devem estar sempre ativas
     */
    private const TELAS_OBRIGATORIAS = [
        '1006', // Relatórios - sempre ativo, não aparece no menu
        '1007', // Histórico de Movimentações - aparece no submenu de Patrimônio
    ];

    /**
     * Telas que aparecem no submenu de Patrimônio e são sempre obrigatórias
     */
    private const TELAS_SUBMENU_PATRIMONIO = [
        '1000', // Patrimônios (obrigatória)
        '1007', // Histórico de Movimentações (obrigatória)
        // Outras telas podem ser adicionadas aqui e controladas por permissão
    ];

    /**
     * Obtém todas as telas disponíveis do arquivo config/telas.php
     */
    public static function getTelasDisponiveis(): array
    {
        return config('telas', []);
    }

    /**
     * Verifica se uma tela é obrigatória (sempre ativa)
     */
    public static function isTelaObrigatoria(string $nuseqtela): bool
    {
        return in_array($nuseqtela, self::TELAS_OBRIGATORIAS);
    }

    /**
     * Verifica se uma tela deve aparecer no menu principal
     * Retorna false para telas obrigatórias que não devem aparecer no menu
     */
    public static function deveAparecerNoMenu(string $nuseqtela): bool
    {
        // Relatórios (1006) nunca aparece no menu principal
        if ($nuseqtela === '1006') {
            return false;
        }

        // Histórico (1007) só aparece no submenu de Patrimônio
        if ($nuseqtela === '1007') {
            return false;
        }

        return true;
    }

    /**
     * Obtém as telas que o usuário tem acesso baseado no perfil
     * - USR: acesso apenas a 1000 e 1001
     * - ADM: acesso a tudo
     */
    public static function getTelasComAcesso(): array
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        $telasConfig = self::getTelasDisponiveis();

        // Administrador tem acesso a todas as telas
        if ($user->isAdmin()) {
            return array_keys($telasConfig);
        }

        // Usuário comum (USR) só tem acesso a Patrimônio e Gráficos
        if ($user->PERFIL === 'USR') {
            return ['1000', '1001'];
        }

        return [];
    }

    /**
     * Obtém as telas que devem aparecer no menu principal
     * Filtra apenas telas com acesso e que devem aparecer no menu
     */
    public static function getTelasParaMenu(): array
    {
        $telasComAcesso = self::getTelasComAcesso();
        $telasConfig = self::getTelasDisponiveis();
        $telasMenu = [];

        foreach ($telasComAcesso as $nuseqtela) {
            // Verifica se deve aparecer no menu
            if (!self::deveAparecerNoMenu($nuseqtela)) {
                continue;
            }

            // Adiciona a tela ao menu com suas configurações
            if (isset($telasConfig[$nuseqtela])) {
                $telasMenu[$nuseqtela] = $telasConfig[$nuseqtela];
            }
        }

        // Ordena pelo campo 'ordem'
        uasort($telasMenu, function ($a, $b) {
            return ($a['ordem'] ?? 999) <=> ($b['ordem'] ?? 999);
        });

        return $telasMenu;
    }

    /**
     * Verifica se o usuário tem acesso a uma tela específica
     * Inclui verificação de telas obrigatórias
     */
    public static function temAcessoTela(string $nuseqtela): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Super Admin tem acesso total
        if ($user->isGod()) {
            return true;
        }

        // Telas obrigatórias são sempre acessíveis
        if (self::isTelaObrigatoria($nuseqtela)) {
            return true;
        }

        // Verifica permissão do usuário
        return $user->temAcessoTela($nuseqtela);
    }

    /**
     * Obtém as telas do submenu de Patrimônio
     * Inclui telas obrigatórias e telas com permissão
     */
    public static function getSubmenuPatrimonio(): array
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        $submenu = [];
        $telasConfig = self::getTelasDisponiveis();

        // Adiciona tela de Patrimônios (sempre obrigatória)
        if (isset($telasConfig['1000'])) {
            $submenu['1000'] = array_merge($telasConfig['1000'], ['obrigatoria' => true]);
        }

        // Adiciona Histórico de Movimentações (sempre obrigatória)
        if (isset($telasConfig['1007'])) {
            $submenu['1007'] = array_merge($telasConfig['1007'], ['obrigatoria' => true]);
        }

        // Outras telas podem ser adicionadas aqui conforme necessário

        return $submenu;
    }

    /**
     * Verifica se uma rota existe
     */
    public static function rotaExiste(?string $routeName): bool
    {
        if (!$routeName) {
            return false;
        }

        return RouteHelper::exists($routeName);
    }

    /**
     * Gera o HTML de um card de tela para o menu
     */
    public static function gerarCardTela(string $nuseqtela, array $config): string
    {
        $nome = $config['nome'] ?? 'Sem nome';
        $descricao = $config['descricao'] ?? '';
        $route = $config['route'] ?? null;
        $icone = $config['icone'] ?? 'fa-window';
        $cor = $config['cor'] ?? 'blue';

        // Se não houver rota ou a rota não existir, retorna card desabilitado
        if (!$route || !self::rotaExiste($route)) {
            return self::gerarCardDesabilitado($nome, $descricao, $icone, $cor);
        }

        $url = route($route);

        return <<<HTML
<a href="{$url}" class="service-card tela-{$cor}" style="text-decoration: none; display: block;">
    <div class="service-icon"><i class="fas {$icone}"></i></div>
    <div class="service-title">{$nome}</div>
    <div class="service-description">{$descricao}</div>
</a>
HTML;
    }

    /**
     * Gera o HTML de um card desabilitado
     */
    private static function gerarCardDesabilitado(string $nome, string $descricao, string $icone, string $cor): string
    {
        return <<<HTML
<div class="service-card tela-{$cor}" style="opacity: 0.5; cursor: not-allowed;">
    <div class="service-icon"><i class="fas {$icone}"></i></div>
    <div class="service-title">{$nome}</div>
    <div class="service-description">{$descricao}</div>
</div>
HTML;
    }
}
