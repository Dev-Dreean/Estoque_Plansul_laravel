<?php

return [
    'enabled' => env('SYSTEM_NEWS_ENABLED', true),

    'items' => [
        [
            'key' => '2026-04-15-fluxo-ti-automatico-e-numero-da-mesa',
            'title' => 'Fluxo da TI automático por local e número da mesa no patrimônio',
            'summary' => 'As solicitações agora identificam automaticamente o fluxo da TI pelo local classificado, e os patrimônios em uso passam a contar com o campo Número da mesa para facilitar a identificação no escritório.',
            'summary_html' => 'As <span class="system-news-inline-highlight system-news-inline-highlight--solicitacoes">solicitações</span> agora identificam automaticamente o <span class="system-news-inline-highlight">fluxo da TI pelo local classificado</span>, e os <span class="system-news-inline-highlight system-news-inline-highlight--patrimonio">patrimônios em uso</span> passam a contar com o campo <span class="system-news-inline-highlight">Número da mesa</span> para facilitar a identificação no escritório.',
            'highlight' => 'Ao selecionar um local classificado para TI, o pedido já entra no fluxo correto sem ajuste manual. No patrimônio, informe o número da mesa para localizar rapidamente os bens em uso e evitar duplicidades.',
            'details' => [
                'O local classificado em projetos passou a definir automaticamente se a solicitação segue pelo fluxo padrão ou pelo fluxo da TI.',
                'No fluxo da TI, a triagem inicial continua com Bruno, depois o pedido segue por medição, cotações, autorização e liberação final.',
                'O cadastro de patrimônio agora possui o campo Número da mesa, exibido na listagem e no detalhe para facilitar a identificação dos bens em uso.',
                'O sistema não permite repetir o mesmo número da mesa entre patrimônios com situação Em uso.',
            ],
            'tutorial_label' => '',
            'tutorial_target' => '',
            'cta_label' => '',
            'cta_url' => '',
            'released_at' => '2026-04-15 00:00:00',
            'active' => true,
        ],
        [
            'key' => '2026-04-07-fluxo-estoque-ti-e-acesso-solicitante',
            'title' => 'Pedidos do estoque da TI com controle próprio',
            'summary' => 'As solicitações do estoque da TI agora passam pela triagem inicial do Bruno, seguem para medição e cotação e, depois disso, precisam da autorização do Theo antes da liberação final do Bruno.',
            'summary_html' => 'As solicitações do <span class="system-news-inline-highlight system-news-inline-highlight--solicitacoes">estoque da TI</span> agora passam pela <span class="system-news-inline-highlight">triagem inicial do Bruno</span>, seguem para medição e cotação e, depois disso, precisam da <span class="system-news-inline-highlight">autorização do Theo</span> antes da liberação final do Bruno.',
            'highlight' => 'Ao abrir uma solicitação, use "Origem do pedido: Estoque da TI" para registrar saídas desse estoque. Depois das cotações, o sistema volta a solicitar autorização do Theo antes de liberar o envio.',
            'details' => [
                'Use a origem "Estoque da TI" quando o item sair do estoque controlado pela equipe de TI.',
                'Nesse fluxo, a validação inicial de disponibilidade fica com Bruno.',
                'Depois da triagem inicial, medição e cotação, o pedido passa por autorização do Theo e só então segue para a liberação final do Bruno.',
            ],
            'tutorial_label' => '',
            'tutorial_target' => '',
            'cta_label' => '',
            'cta_url' => '',
            'released_at' => '2026-04-07 00:00:00',
            'active' => true,
        ],
    ],
];
