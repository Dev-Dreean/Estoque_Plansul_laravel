#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR=$(cd "$(dirname "$0")/.." && pwd)
cd "$PROJECT_DIR"

echo "=== DEPLOY IMPORT - AUTOMATION ==="
echo "Project dir: $PROJECT_DIR"

FILE_PATH="$PROJECT_DIR/patrimonio.TXT"

if [ ! -f "$FILE_PATH" ]; then
  echo "\n❌ Arquivo não encontrado em: $FILE_PATH"
  echo "Coloque 'patrimonio.TXT' na raiz do projeto ou passe como argumento:\n  ./scripts/deploy_import.sh /caminho/para/patrimonio.TXT"
  exit 1
fi

echo "\n📄 Arquivo encontrado: $FILE_PATH"
ls -lah "$FILE_PATH"

read -p $'\n⚠️  Fazer backup do banco antes de importar? [ENTER para continuar | Ctrl+C para cancelar]\n'

echo "\n1) Rodando checagem rápida do ambiente..."
php scripts/check_server_environment.php || true

echo "\n2) Criando backup do banco..."
php scripts/backup_database.php

echo "\n3) Executando importação (com argumento explicito)..."
php scripts/import_patrimonio_completo.php --arquivo="$FILE_PATH"

echo "\n4) Limpando caches do Laravel..."
php artisan cache:clear || true
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan optimize:clear || true

echo "\n✅ IMPORT PROCESS COMPLETED"
echo "Verifique: php scripts/test_atribuir.php e logs em storage/logs/imports/"

exit 0
