#!/usr/bin/env bash
# Simple health check script for Plansul Laravel project (Linux)
# Use: bash scripts/run_health_checks.sh

set -euo pipefail
echo "== Plansul health checks (Linux) =="

echo "-> PHP version"
php -v || { echo "PHP not found"; exit 2; }

echo "-> PHP required extensions (checks)"
REQUIRED_EXT=(openssl pdo_mysql mbstring tokenizer xml ctype json fileinfo)
MISSING=()
for ext in "${REQUIRED_EXT[@]}"; do
  php -r "echo extension_loaded('$ext') ? '1' : '0';" | grep -q 1 || MISSING+=("$ext")
done
if [ ${#MISSING[@]} -ne 0 ]; then
  echo "Missing PHP extensions: ${MISSING[*]}" || true
else
  echo "All required PHP extensions present"
fi

echo "-> Composer availability"
if command -v composer >/dev/null 2>&1; then
  composer --version
else
  echo "Composer not found (skipping composer checks)"
fi

echo "-> Laravel artisan check"
if php artisan --version >/dev/null 2>&1; then
  php artisan --version
else
  echo "artisan not runnable (check PHP environment)"
fi

echo "-> .env check"
if [ -f .env ]; then
  grep -E "APP_KEY|APP_ENV|DB_CONNECTION" .env || true
else
  echo ".env file not found"
fi

echo "-> Storage permissions (storage and bootstrap/cache)"
ls -ld storage bootstrap/cache || true

echo "-> Tail last 50 lines of laravel.log"
if [ -f storage/logs/laravel.log ]; then
  tail -n 50 storage/logs/laravel.log || true
else
  echo "No laravel.log found"
fi

echo "== Health checks completed =="

exit 0
