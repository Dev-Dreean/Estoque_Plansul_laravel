#!/bin/bash
set -euo pipefail

APP_DIR="$HOME/www/estoque-laravel"
TS="$(date +%Y%m%d_%H%M%S)"
BACKUP_DIR="$HOME/backups/codex_$TS"

mkdir -p "$BACKUP_DIR"
cd "$APP_DIR"

FILES=(
  "app/Http/Controllers/Auth/AuthenticatedSessionController.php"
  "app/Http/Controllers/ProfileController.php"
  "app/Http/Controllers/SolicitacaoBemController.php"
  "app/Http/Controllers/SolicitacaoEmailController.php"
  "app/Http/Controllers/UserController.php"
  "app/Http/Middleware/EnsureProfileIsComplete.php"
  "app/Models/User.php"
  "app/View/Components/AppLayout.php"
  "app/Jobs/SendSolicitacaoBemCriadaEmailJob.php"
  "app/Services/SolicitacaoBemEmailService.php"
  "config/solicitacoes_bens.php"
  "database/migrations/2026_03_24_000000_add_email_to_usuario_table.php"
  "public/build/manifest.json"
  "public/build/assets/app-D39jYA1c.css"
  "resources/views/layouts/app.blade.php"
  "resources/views/profile/complete.blade.php"
  "resources/views/profile/partials/update-profile-information-form.blade.php"
  "resources/views/solicitacoes/index.blade.php"
  "resources/views/solicitacoes/partials/show-content.blade.php"
  "resources/views/usuarios/partials/form.blade.php"
  "routes/web.php"
)

for f in "${FILES[@]}"; do
  if [ -f "$f" ]; then
    mkdir -p "$BACKUP_DIR/$(dirname "$f")"
    cp "$f" "$BACKUP_DIR/$f"
  fi
done

echo "$BACKUP_DIR"
