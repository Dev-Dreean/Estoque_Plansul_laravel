<?php

return [
    'enabled' => env('SYSTEM_NEWS_ENABLED', true),

    'items' => [
        [
            'key' => '2026-03-25-sino-de-pendencias-prioritarias',
            'title' => 'Novo sino de pendências prioritárias',
            'summary' => 'As pendências mais importantes do sistema agora ficam reunidas no topo da tela para acelerar a sua ação.',
            'highlight' => 'O sino passa a ser o ponto oficial para acompanhar o que exige atenção imediata.',
            'details' => [
                'Solicitações que aguardam sua ação aparecem no sino e levam direto para a etapa correta.',
                'Itens removidos entram como pendência até alguém restaurar ou remover definitivamente.',
                'Quando surgir uma nova pendência importante, o contador aumenta automaticamente.',
                'Usuários com pendências também recebem um resumo diário por e-mail às 08:00.',
            ],
            'cta_label' => 'Abrir solicitações',
            'cta_url' => '/solicitacoes-bens',
            'released_at' => '2026-03-25 17:00:00',
            'active' => true,
        ],
    ],
];
