#!/usr/bin/env bash
# Script de deploy para ser executado no servidor (rodar a partir da raiz do projeto)
# Uso exemplo (no servidor):
#   SKIP_NPM=1 SKIP_COMPOSER=0 RUN_MIGRATIONS=0 bash scripts/deploy_server.sh

set -euo pipefail

SKIP_COMPOSER="${SKIP_COMPOSER:-0}"
SKIP_NPM="${SKIP_NPM:-0}"
RUN_MIGRATIONS="${RUN_MIGRATIONS:-0}"

echo "[deploy] Atualizando repositório..."
git fetch origin
git reset --hard origin/main

if [ "${SKIP_COMPOSER}" -ne 1 ]; then
  if command -v composer >/dev/null 2>&1; then
    echo "[deploy] Instalando dependências PHP (composer)..."
    composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
  else
    echo "[deploy] Composer não encontrado — pulando step composer."
  fi
else
  echo "[deploy] SKIP_COMPOSER=1 definido — pulando composer."
fi

if [ "${SKIP_NPM}" -ne 1 ]; then
  if command -v npm >/dev/null 2>&1; then
    echo "[deploy] Instalando/compilando assets (npm)..."
    npm ci --silent
    npm run build --silent
  else
    echo "[deploy] npm não encontrado — pulando build frontend."
  fi
else
  echo "[deploy] SKIP_NPM=1 definido — pulando npm."
fi

echo "[deploy] Rodando comandos Artisan de cache e links..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true
php artisan storage:link || true

if [ "${RUN_MIGRATIONS}" -eq 1 ]; then
  echo "[deploy] Rodando migrations (forçado)..."
  php artisan migrate --force
else
  echo "[deploy] RUN_MIGRATIONS=0 — migrations não serão executadas."
fi

echo "[deploy] Concluído."
