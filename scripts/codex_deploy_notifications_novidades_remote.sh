#!/bin/bash
set -euo pipefail

APP_DIR="$HOME/www/estoque-laravel"
ZIP_PATH="$HOME/deploy_notifications_novidades_20260325.zip"
TS="$(date +%Y%m%d_%H%M%S)"
BACKUP_DIR="$HOME/backups/codex_notifications_$TS"

FILES=(
  "app/Console/Commands/SendDailyImportantNotificationsSummary.php"
  "app/Contracts/ImportantNotificationProvider.php"
  "app/Http/Controllers/ImportantNotificationController.php"
  "app/Http/Controllers/SystemNewsController.php"
  "app/Http/Controllers/RemovidosController.php"
  "app/Providers/AppServiceProvider.php"
  "app/Services/ImportantNotifications/SolicitacoesImportantNotificationProvider.php"
  "app/Services/ImportantNotifications/RemovidosImportantNotificationProvider.php"
  "app/Services/ImportantNotificationsService.php"
  "app/Services/SolicitacaoBemPendenciaService.php"
  "app/Services/SystemNewsService.php"
  "config/notificacoes.php"
  "config/novidades.php"
  "database/migrations/2026_03_25_113000_create_solicitacoes_bens_notificacao_usuarios_table.php"
  "database/migrations/2026_03_25_140000_add_logistics_volume_count_to_solicitacoes_bens.php"
  "database/migrations/2026_03_25_170000_create_novidades_sistema_visualizacoes_table.php"
  "resources/views/emails/notificacoes/resumo-diario.blade.php"
  "resources/views/layouts/navigation.blade.php"
  "resources/views/layouts/app.blade.php"
  "resources/views/removidos/index.blade.php"
  "resources/views/solicitacoes/index.blade.php"
  "resources/views/solicitacoes/partials/show-content.blade.php"
  "resources/js/app.js"
  "resources/css/app.css"
  "resources/css/components/notifications.css"
  "resources/css/components/system-news.css"
  "routes/web.php"
  "routes/console.php"
  "public/build/manifest.json"
  "public/build/assets/app-BaBnB5WQ.js"
  "public/build/assets/app-Dq9qyc34.css"
)

mkdir -p "$BACKUP_DIR"
cd "$APP_DIR"

for f in "${FILES[@]}"; do
  if [ -f "$f" ]; then
    mkdir -p "$BACKUP_DIR/$(dirname "$f")"
    cp "$f" "$BACKUP_DIR/$f"
  fi
done

unzip -oq "$ZIP_PATH" -d "$APP_DIR"
rm -f "$ZIP_PATH"

php82 artisan migrate --force
php82 artisan config:clear
php82 artisan route:clear
php82 artisan view:clear
php82 artisan cache:clear

echo "BACKUP_DIR=$BACKUP_DIR"
echo "MANIFEST_JS=$(php -r '$m=json_decode(file_get_contents(\"public/build/manifest.json\"),true); echo $m[\"resources/js/app.js\"][\"file\"];')"
echo "MANIFEST_CSS=$(php -r '$m=json_decode(file_get_contents(\"public/build/manifest.json\"),true); echo $m[\"resources/css/app.css\"][\"file\"];')"
