#!/bin/bash
# one-off: Teste end-to-end da integração Power Automate

echo "🧪 Teste End-to-End: Power Automate → API → Banco de Dados"
echo "=========================================================="

TOKEN="f8a2c5e9d3b7f1a6e8c2f5b9d3e7a1c4f6b9d2e5f7a0c3e6f9b2d5e8a1c4f7"
API_URL="https://plansul.info/api/solicitacoes/email"

# Criar payload de teste com HTML (igual ao que Power Automate envia)
PAYLOAD=$(cat <<'EOF'
{
  "from": "andrelucasbueno@gmail.com",
  "subject": "Solicitacao de Bem",
  "body": "<html><head> <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"></head><body><div dir=\"ltr\"><div class=\"gmail_default\" style=\"font-family:comic sans ms,sans-serif\">Solicitante: João Silva Teste<br>Matricula: 99999<br>Projeto: 1234 - Sistema Plansul<br>UF: SP<br>Setor: TI<br>Local destino: Sala de Testes<br>Observacao: Email de teste do sistema<br><br>Itens:<br>- Monitor 24\"; 1; UN; Teste monitor<br>- Mouse; 1; UN; Teste mouse</div></div></body></html>"
}
EOF
)

echo ""
echo "📤 Enviando requisição POST para $API_URL"
echo ""

# Fazer a requisição
RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$API_URL" \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: $TOKEN" \
  -d "$PAYLOAD")

# Separar resposta do status code
HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | head -n-1)

echo "📊 Status HTTP: $HTTP_CODE"
echo "📝 Resposta:"
echo "$BODY" | head -c 500
echo ""
echo ""

if [ "$HTTP_CODE" = "200" ]; then
  echo "✅ SUCESSO! A API retornou 200"
  SOLICITACAO_ID=$(echo "$BODY" | grep -o '"solicitacao_id":[0-9]*' | grep -o '[0-9]*')
  if [ -n "$SOLICITACAO_ID" ]; then
    echo "✅ Solicitação criada com ID: $SOLICITACAO_ID"
  fi
else
  echo "❌ ERRO: HTTP $HTTP_CODE"
fi

echo ""
echo "🔍 Verificando logs do servidor..."
ssh plansul@ftp.plansul.info "cd ~/www/estoque-laravel && tail -10 storage/logs/laravel.log | grep -i 'solicitacao\|email' || echo 'Nenhum log encontrado'"

echo ""
echo "✅ Teste concluído!"
