#!/bin/bash
# one-off: Teste simples da rota no KingHost

cd ~/www/estoque-laravel

# Teste 1: Verificar se a app está funcionando
echo "1. Testando se App está rodando..."
php82 artisan tinker << EOF
exit
EOF

# Teste 2: Testar com curl via artisan (mais confiável)
echo ""
echo "2. Testando rota via Artisan HTTP Client..."

php82 << 'PHPEOF'
<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';

$payload = [
    "from" => "teste@test.com",
    "subject" => "Solicitacao de Bem",
    "body" => "Solicitante: João Silva\nMatricula: 99999\nProjeto: 1234 - Sistema Plansul\nUF: SP\nSetor: TI\nLocal destino: Sala\nObservacao: Teste\n\nItens:\n- Monitor 24; 1; UN; Teste\n- Mouse; 1; UN; Teste"
];

$response = \Illuminate\Support\Facades\Http::withHeaders([
    'X-API-KEY' => 'f8a2c5e9d3b7f1a6e8c2f5b9d3e7a1c4f6b9d2e5f7a0c3e6f9b2d5e8a1c4f7'
])->post('https://plansul.info/api/solicitacoes/email', $payload);

echo "Status: " . $response->status() . "\n";
echo "Response: " . substr($response->body(), 0, 300) . "\n";
?>
PHPEOF

echo ""
echo "✅ Teste concluído"
