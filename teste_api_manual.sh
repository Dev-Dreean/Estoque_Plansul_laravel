#!/bin/bash
# TESTE MANUAL - Simular requisição do Power Automate

echo "🧪 TESTANDO API MANUALMENTE"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Criar payload de teste
cat > /tmp/test_api.json << 'EOF'
{
  "from": "teste@empresa.com",
  "subject": "Solicitacao de Bem",
  "body": "Solicitante: João Silva Teste\nMatricula: 99999\nProjeto: 100 - Teste\nUF: SP\nSetor: TI\nLocal destino: Almoxarifado\nObservacao: Teste\n\nItens:\n- Monitor 24\"; 1; UN; Teste\n- Mouse; 1; UN; Teste"
}
EOF

echo ""
echo "📤 Enviando requisição para KingHost..."
echo ""

# Executar curl no servidor
ssh plansul@ftp.plansul.info bash << 'REMOTE'
cd ~/www/estoque-laravel

# Testar API
curl -s -X POST https://plansul.info/api/solicitacoes/email \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: 3a7f9e2c5b8d1a4f7c9e2b5a8d1f4a7c9e2b5a8d1f4a7c9e2b5a8d1f4a7c9e" \
  -d '{"from":"teste@empresa.com","subject":"Solicitacao de Bem","body":"Solicitante: João Silva Teste\nMatricula: 99999\nProjeto: 100 - Teste\nUF: SP\nSetor: TI\nLocal destino: Almoxarifado\nObservacao: Teste\n\nItens:\n- Monitor 24\"; 1; UN; Teste\n- Mouse; 1; UN; Teste"}' \
  | python3 -m json.tool 2>/dev/null || cat

echo ""
echo ""
echo "📋 LOGS DO SERVIDOR:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
tail -30 storage/logs/laravel.log | grep -A 5 "POWER_AUTOMATE\|📧\|🚀"

REMOTE

echo ""
echo "✅ Teste concluído!"
