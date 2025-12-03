#!/bin/bash

# ============================================================================
# SCRIPT DE IMPORTAÇÃO KINGHOST - COPIAR E COLAR NO SSH
# ============================================================================
# Executar: bash COMANDO_SSH_KINGHOST.sh
# Ou colcar os comandos abaixo diretamente no terminal SSH do KingHost
# ============================================================================

set -e  # Parar em caso de erro

echo "========================================="
echo "IMPORTAÇÃO PLANSUL NO KINGHOST"
echo "========================================="
echo ""

# 1. Ir para o diretório do projeto
cd /home/$(whoami)/public_html

echo "✓ Diretório: $(pwd)"
echo ""

# 2. Pull do repositório
echo "📥 Atualizando código do repositório..."
git pull origin main

echo ""
echo "✓ Código atualizado"
echo ""

# 3. Listar scripts importados
echo "📋 Verificando arquivos de importação..."
ls -lh scripts/import*.php scripts/validate*.php scripts/run_importacao*.php

echo ""

# 4. Validação pré-importação
echo "🔍 ETAPA 1: VALIDAÇÃO PRÉ-IMPORTAÇÃO"
echo "========================================="
php scripts/validate_pre_import.php

echo ""

# 5. Executar importação completa com aumento de timeout e memória
echo ""
echo "🚀 ETAPA 2: INICIANDO IMPORTAÇÃO COMPLETA"
echo "========================================="
echo "Tempo estimado: 10-15 minutos"
echo ""

php -d max_execution_time=600 -d memory_limit=512M scripts/run_importacao_completa.php

echo ""
echo "========================================="
echo "✅ IMPORTAÇÃO CONCLUÍDA!"
echo "========================================="
echo ""

# 6. Verificar resultados
echo "📊 VERIFICANDO RESULTADOS:"
echo ""

php artisan tinker --execute="
echo '═════════════════════════════════════════' . PHP_EOL;
echo 'RESUMO FINAL DA IMPORTAÇÃO' . PHP_EOL;
echo '═════════════════════════════════════════' . PHP_EOL;
echo 'Patrimônios: ' . \App\Models\Patrimonio::count() . PHP_EOL;
echo 'Locais de Projeto: ' . \App\Models\LocalProjeto::count() . PHP_EOL;
echo 'Histórico Movimentações: ' . \App\Models\HistoricoMovimentacao::count() . PHP_EOL;
echo PHP_EOL;
echo 'Patrimônios com usuário: ' . \App\Models\Patrimonio::whereNotNull('USUARIO')->count() . PHP_EOL;
echo '═════════════════════════════════════════' . PHP_EOL;
"

echo ""
echo "✅ SUCESSO TOTAL!"
echo ""
echo "Próximos passos:"
echo "1. Acessar o sistema via navegador"
echo "2. Verificar patrimônios importados"
echo "3. Testar buscas e filtros"
echo ""
echo "Logs disponíveis em: storage/logs/laravel.log"
echo ""
