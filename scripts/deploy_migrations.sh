#!/bin/bash
# Script simples para atualizar o repositório e rodar somente as migrations em produção.
# Uso recomendado: ./deploy_migrations.sh /home/plansul/www/estoque-laravel

PROJECT_DIR="${1:-$(pwd)}"
cd "$PROJECT_DIR" || { echo "Diretório $PROJECT_DIR não encontrado"; exit 1; }

echo "Pull do repositório..."
git pull origin main

echo "Rodando migrations (forçando)..."
php artisan migrate --force

echo "Atualizando caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Deploy de migrations concluído."
