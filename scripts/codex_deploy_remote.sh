#!/bin/bash
set -euo pipefail

APP_DIR="$HOME/www/estoque-laravel"
ZIP_PATH="$HOME/deploy_solicitacoes_email_20260325.zip"
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

unzip -oq "$ZIP_PATH" -d "$APP_DIR"
rm -f "$ZIP_PATH"

python3 - <<'PY'
from pathlib import Path
env_path = Path.home() / "www" / "estoque-laravel" / ".env"
text = env_path.read_text(encoding="utf-8")

updates = {
    "SOLICITACOES_BENS_POWER_AUTOMATE_WEBHOOK_URL": "https://default2ece9e6005c6403cbf2423da115415.76.environment.api.powerplatform.com:443/powerautomate/automations/direct/workflows/46cdb6d67ae54d5f91d027fb6eeddd4f/triggers/manual/paths/invoke?api-version=1&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=obwvTdwkzO3FD3_FxWb5PkWMi_WyA_Tcs2yPwFZhYMs",
    "SOLICITACOES_BENS_POWER_AUTOMATE_WEBHOOK_TOKEN": "",
    "SOLICITACOES_BENS_POWER_AUTOMATE_TIMEOUT": "15",
    "SOLICITACOES_BENS_POWER_AUTOMATE_VERIFY_SSL": "true",
    "SOLICITACOES_BENS_EMAIL_TO": "",
}

lines = text.splitlines()
keys_done = set()
for i, line in enumerate(lines):
    for key, value in updates.items():
        if line.startswith(key + "="):
            lines[i] = f"{key}={value}"
            keys_done.add(key)

for key, value in updates.items():
    if key not in keys_done:
        lines.append(f"{key}={value}")

env_path.write_text("\n".join(lines) + "\n", encoding="utf-8")
PY

php82 artisan migrate --force
php82 artisan config:clear
php82 artisan route:clear
php82 artisan view:clear

echo "BACKUP_DIR=$BACKUP_DIR"
echo "MANIFEST_CSS=$(php -r '$m=json_decode(file_get_contents(\"public/build/manifest.json\"),true); echo $m[\"resources/css/app.css\"][\"file\"];')"
echo "PROFILE_ROUTES=$(php82 artisan route:list --name=profile.completion --compact | wc -l)"
