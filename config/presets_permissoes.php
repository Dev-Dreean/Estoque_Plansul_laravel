<?php

/**
 * Presets de permissões para criação de logins via Gestão de Colaboradores.
 *
 * Referências reais:
 *  - criador_patrimonios → Beatriz / Tiago (USR)
 *  - consultor           → Theo (PERFIL='C')
 *  - coordenador         → Bruno (USR)
 *  - criador_solicitacoes → quem apenas faz pedidos de compra
 *
 * Cada preset define:
 *  perfil          → PERFIL do usuário (USR | C)
 *  telas           → array de IDs de tela a liberar
 *  nome            → rótulo exibido no modal
 *  descricao       → frase curta de propósito
 *  emoji           → ícone emoji visual no card
 *  nivel           → badge de nível (ex: Operacional)
 *  cor_nivel       → classes Tailwind do badge
 *  chips           → array de [label, cor_tailwind] para chips visuais
 */

return [

    'criador_solicitacoes' => [
        'perfil'       => 'USR',
        'nome'         => 'Solicitante de Bens',
        'descricao'    => 'Abre pedidos de compra e acompanha o andamento',
        'emoji'        => '🛒',
        'nivel'        => 'Básico',
        'cor_nivel'    => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
        'chips'        => [
            ['label' => 'Patrimônios (ver)', 'cor' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'],
            ['label' => 'Relatórios',        'cor' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'],
            ['label' => 'Criar pedidos',     'cor' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'],
        ],
        'telas' => [1000, 1006, 1008, 1010, 1013],
    ],

    'criador_patrimonios' => [
        'perfil'       => 'USR',
        'nome'         => 'Gestor de Patrimônios',
        'descricao'    => 'Cadastra e edita patrimônios; coordena pedidos de compra',
        'emoji'        => '🏷️',
        'nivel'        => 'Operacional',
        'cor_nivel'    => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
        'chips'        => [
            ['label' => 'Patrimônios (criar/editar)', 'cor' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'],
            ['label' => 'Dashboard',                  'cor' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300'],
            ['label' => 'Relatórios',                 'cor' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'],
            ['label' => 'Analisar pedidos',           'cor' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'],
        ],
        // Sem 1007 (histórico - só admin/coordenador) e sem 1013 (cria pedidos)
        'telas' => [1000, 1001, 1006, 1010, 1011, 1012, 1014, 1015, 1016, 1019],
    ],

    'consultor' => [
        'perfil'       => 'C',
        'nome'         => 'Consultor',
        'descricao'    => 'Consulta o sistema e abre e autoriza liberação de pedidos',
        'emoji'        => '🔍',
        'nivel'        => 'Consultor',
        'cor_nivel'    => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
        'chips'        => [
            ['label' => 'Patrimônios (ver)',    'cor' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'],
            ['label' => 'Relatórios',           'cor' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'],
            ['label' => 'Criar pedidos',        'cor' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'],
            ['label' => 'Autorizar liberação',  'cor' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300'],
        ],
        // Igual ao Theo: 1000, 1006, 1010, 1013, 1021
        'telas' => [1000, 1006, 1010, 1013, 1021],
    ],

    'coordenador' => [
        'perfil'       => 'USR',
        'nome'         => 'Coordenador',
        'descricao'    => 'Visão completa de patrimônios, pedidos e liberação de envio',
        'emoji'        => '🎯',
        'nivel'        => 'Coordenação',
        'cor_nivel'    => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
        'chips'        => [
            ['label' => 'Patrimônios',     'cor' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'],
            ['label' => 'Dashboard',       'cor' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300'],
            ['label' => 'Relatórios',      'cor' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'],
            ['label' => 'Itens removidos', 'cor' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'],
            ['label' => 'Gerir pedidos',   'cor' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'],
            ['label' => 'Liberar envio',   'cor' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300'],
        ],
        // Igual ao Bruno: 1000, 1001, 1006, 1009, 1010, 1011, 1015, 1016, 1020
        'telas' => [1000, 1001, 1006, 1009, 1010, 1011, 1015, 1016, 1020],
    ],

];