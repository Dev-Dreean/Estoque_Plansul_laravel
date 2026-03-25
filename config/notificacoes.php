<?php

return [
    'enabled' => filter_var(env('NOTIFICACOES_IMPORTANTES_ENABLED', true), FILTER_VALIDATE_BOOL),
    'daily_summary' => [
        'enabled' => filter_var(env('NOTIFICACOES_IMPORTANTES_DAILY_SUMMARY_ENABLED', true), FILTER_VALIDATE_BOOL),
        'time' => env('NOTIFICACOES_IMPORTANTES_DAILY_SUMMARY_TIME', '08:00'),
        'timezone' => env('NOTIFICACOES_IMPORTANTES_DAILY_SUMMARY_TIMEZONE', 'America/Sao_Paulo'),
        'subject' => env('NOTIFICACOES_IMPORTANTES_DAILY_SUMMARY_SUBJECT', 'Resumo diário de pendências importantes'),
    ],
];
