<?php

return [
    'email_to' => env('SOLICITACOES_BENS_EMAIL_TO', ''),
    'power_automate_token' => env('POWER_AUTOMATE_TOKEN', ''),
    'power_automate' => [
        'webhook_url' => env('SOLICITACOES_BENS_POWER_AUTOMATE_WEBHOOK_URL', ''),
        'webhook_token' => env('SOLICITACOES_BENS_POWER_AUTOMATE_WEBHOOK_TOKEN', ''),
        'timeout' => (int) env('SOLICITACOES_BENS_POWER_AUTOMATE_TIMEOUT', 15),
        'verify_ssl' => filter_var(env('SOLICITACOES_BENS_POWER_AUTOMATE_VERIFY_SSL', true), FILTER_VALIDATE_BOOL),
    ],
    'notificacoes' => [
        'enabled' => env('SOLICITACOES_BENS_NOTIFICACOES_ENABLED', true),
        'queue_connection' => env('SOLICITACOES_BENS_NOTIFICACOES_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database')),
        'queue_name' => env('SOLICITACOES_BENS_NOTIFICACOES_QUEUE', 'emails'),
        'subject_prefix' => env('SOLICITACOES_BENS_NOTIFICACOES_SUBJECT_PREFIX', 'Solicitação de bens'),
        'fallback_to' => env('SOLICITACOES_BENS_NOTIFICACOES_FALLBACK_TO', ''),
        'login_email_domain' => env('SOLICITACOES_BENS_LOGIN_EMAIL_DOMAIN', ''),
        'roles' => [
            'triagem' => [
                'logins' => explode(',', (string) env('SOLICITACOES_BENS_NOTIFICACOES_TRIAGEM_LOGINS', 'TIAGOP,BEA.SC')),
                'emails' => explode(',', (string) env('SOLICITACOES_BENS_NOTIFICACOES_TRIAGEM_EMAILS', '')),
            ],
            'medicao' => [
                'logins' => explode(',', (string) env('SOLICITACOES_BENS_NOTIFICACOES_MEDICAO_LOGINS', 'TIAGOP')),
                'emails' => explode(',', (string) env('SOLICITACOES_BENS_NOTIFICACOES_MEDICAO_EMAILS', '')),
            ],
            'cotacao' => [
                'logins' => explode(',', (string) env('SOLICITACOES_BENS_NOTIFICACOES_COTACAO_LOGINS', 'BEA.SC')),
                'emails' => explode(',', (string) env('SOLICITACOES_BENS_NOTIFICACOES_COTACAO_EMAILS', '')),
            ],
            'liberacao' => [
                'logins' => explode(',', (string) env('SOLICITACOES_BENS_NOTIFICACOES_LIBERACAO_LOGINS', 'BRUNO')),
                'emails' => explode(',', (string) env('SOLICITACOES_BENS_NOTIFICACOES_LIBERACAO_EMAILS', '')),
            ],
            'envio' => [
                'logins' => explode(',', (string) env('SOLICITACOES_BENS_NOTIFICACOES_ENVIO_LOGINS', 'TIAGOP,BEA.SC')),
                'emails' => explode(',', (string) env('SOLICITACOES_BENS_NOTIFICACOES_ENVIO_EMAILS', '')),
            ],
        ],
        'flows' => [
            'TI' => [
                'label' => 'TI',
                'roles' => [
                    'triagem' => [
                        'logins' => explode(',', (string) env('SOLICITACOES_BENS_NOTIFICACOES_TI_TRIAGEM_LOGINS', 'BRUNO')),
                        'emails' => explode(',', (string) env('SOLICITACOES_BENS_NOTIFICACOES_TI_TRIAGEM_EMAILS', '')),
                    ],
                    'medicao' => [
                        'logins' => explode(',', (string) env('SOLICITACOES_BENS_NOTIFICACOES_TI_MEDICAO_LOGINS', 'TIAGOP')),
                        'emails' => explode(',', (string) env('SOLICITACOES_BENS_NOTIFICACOES_TI_MEDICAO_EMAILS', '')),
                    ],
                    'cotacao' => [
                        'logins' => explode(',', (string) env('SOLICITACOES_BENS_NOTIFICACOES_TI_COTACAO_LOGINS', 'BEA.SC')),
                        'emails' => explode(',', (string) env('SOLICITACOES_BENS_NOTIFICACOES_TI_COTACAO_EMAILS', '')),
                    ],
                    'liberacao' => [
                        'logins' => explode(',', (string) env('SOLICITACOES_BENS_NOTIFICACOES_TI_LIBERACAO_LOGINS', 'BRUNO')),
                        'emails' => explode(',', (string) env('SOLICITACOES_BENS_NOTIFICACOES_TI_LIBERACAO_EMAILS', '')),
                    ],
                    'envio' => [
                        'logins' => explode(',', (string) env('SOLICITACOES_BENS_NOTIFICACOES_TI_ENVIO_LOGINS', 'TIAGOP,BEA.SC')),
                        'emails' => explode(',', (string) env('SOLICITACOES_BENS_NOTIFICACOES_TI_ENVIO_EMAILS', '')),
                    ],
                ],
            ],
        ],
    ],
];
