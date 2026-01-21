<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Models\User;

class MenuHelper
{
    /**
     * Telas obrigatórias (mantido para compatibilidade; atualmente nenhuma).
     */
    private const TELAS_OBRIGATORIAS = [];

    /**
     * Telas que aparecem no submenu de Patrimônio e são sempre obrigatórias
     */
    private const TELAS_SUBMENU_PATRIMONIO = [
        '1000', // Patrimônios (obrigatória)
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

        return true;
    }

    /**
     * Obtém as telas que o usuário tem acesso baseado no banco (acessotela + acessousuario).
     * - ADM: acesso a tudo
     * - Demais perfis: somente telas com vínculo ativo
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

        $telasComAcesso = [];
        foreach (array_keys($telasConfig) as $codigo) {
            if ($user->temAcessoTela((string) $codigo)) {
                $telasComAcesso[] = (string) $codigo;
            }
        }

        return $telasComAcesso;
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
     * Verifica se o usuario tem acesso a uma tela especifica.
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

        // Verifica permissão do usuário
        return $user->temAcessoTela($nuseqtela);
    }

    /**
     * Obtem as telas do submenu de Patrimonio conforme acesso.
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

        // Adiciona tela de Patrimonios se estiver liberada
        if (isset($telasConfig['1000']) && $user->temAcessoTela('1000')) {
            $submenu['1000'] = array_merge($telasConfig['1000'], ['obrigatoria' => true]);
        }

        // Adiciona Historico de Movimentacoes se estiver liberada
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

