#!/bin/bash
# Script para sincronizar correção de patrimonios no KingHost
# Projeto: 999915, Local: 1632 → 1642
# Uso: ./kinghost_sync_patrimonios_local_1632.sh [--dry-run]

TIMESTAMP=$(date +"%Y-%m-%d_%H%M%S")
LOG_FILE="logs/kinghost_sync_${TIMESTAMP}.log"
DRY_RUN=false

# Verificar argumentos
if [[ "$1" == "--dry-run" ]]; then
    DRY_RUN=true
fi

mkdir -p logs

{
    echo "═════════════════════════════════════════════════════════════════"
    echo "🔄 KingHost Sync: Corrigir Patrimonios Local 1632 → 1642 | Projeto 999915"
    echo "═════════════════════════════════════════════════════════════════"
    echo "Timestamp: $TIMESTAMP"
    echo "Modo: $([ "$DRY_RUN" = true ] && echo 'DRY-RUN' || echo 'EXECUTE')"
    echo ""

    # 1. Conectar ao KingHost e buscar informações
    echo "🔍 Verificando patrimonios no KingHost..."
    echo ""

    SSH_CMD="ssh plansul@ftp.plansul.info"

    # Verificação de backup no KingHost
    echo "📦 Criando backup no KingHost..."
    $SSH_CMD "cd ~/www/estoque-laravel && \
    BACKUP_FILE=\"storage/backups/pre_sync_local_1632_${TIMESTAMP}.json\" && \
    php82 artisan db:export-json --table=patr --where='CDPROJETO=999915' --output=\$BACKUP_FILE && \
    ls -lh \$BACKUP_FILE"

    if [ $? -ne 0 ]; then
        echo "❌ Falha ao criar backup no KingHost!"
        exit 1
    fi

    echo ""
    echo "✅ Backup criado com sucesso"
    echo ""

    # 2. Verificar quantidade de patrimonios a serem corrigidos
    echo "📊 Contando patrimonios com local 1632..."
    $SSH_CMD "cd ~/www/estoque-laravel && \
    php82 -r \"
    require 'vendor/autoload.php';
    \\\$app = require_once 'bootstrap/app.php';
    \\\$app->make(\\\Illuminate\\\Contracts\\\Console\\\Kernel::class)->bootstrap();
    
    use Illuminate\\\Support\\\Facades\\\DB;
    
    \\\$count = DB::table('patr')
        ->where('CDPROJETO', '999915')
        ->where('CDLOCAL', '1632')
        ->count();
    
    echo 'Patrimonios com local 1632: ' . \\\$count . '\\\n';
    \""

    echo ""

    # 3. Executar a correção
    if [ "$DRY_RUN" = true ]; then
        echo "ℹ️  DRY-RUN: Comando que seria executado:"
        echo ""
        echo "php82 artisan tinker --execute=\"\\\""
        echo "DB::table('patr')"
        echo "  ->where('CDPROJETO', '999915')"
        echo "  ->where('CDLOCAL', '1632')"
        echo "  ->update(['CDLOCAL' => '1642', 'DTOPERACAO' => now()]);"
        echo "\\\""
    else
        echo "🚀 Executando correção no KingHost..."
        echo ""
        $SSH_CMD "cd ~/www/estoque-laravel && \
        echo 'yes' | php82 artisan tinker --execute=\"\
        DB::table('patr')\
          ->where('CDPROJETO', '999915')\
          ->where('CDLOCAL', '1632')\
          ->update(['CDLOCAL' => '1642', 'DTOPERACAO' => now()]);\
        echo 'Atualização concluída!';\" 2>&1"

        if [ $? -ne 0 ]; then
            echo ""
            echo "❌ Falha ao executar correção!"
            exit 1
        fi

        echo ""
        echo "✅ Correção executada com sucesso"
    fi

    echo ""
    echo "═════════════════════════════════════════════════════════════════"
    echo "✅ Sincronização concluída"
    echo "═════════════════════════════════════════════════════════════════"

} | tee "$LOG_FILE"

echo ""
echo "📝 Log salvo em: $LOG_FILE"
