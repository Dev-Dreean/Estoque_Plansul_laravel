# PowerShell health check for Plansul Laravel project (Windows/Server PowerShell)
# Use: .\scripts\run_health_checks.ps1

Write-Host "== Plansul health checks (PowerShell) =="

Write-Host "-> PHP version"
try {
    & php -v
} catch {
    Write-Host "PHP not found"
}

Write-Host "-> PHP extensions check (best-effort)"
$required = @('openssl','pdo_mysql','mbstring','tokenizer','xml','ctype','json','fileinfo')
$missing = @()
foreach ($ext in $required) {
    $cmd = "php -r \"echo extension_loaded('$ext') ? '1' : '0';\""
    $res = Invoke-Expression $cmd
    if ($res -ne '1') { $missing += $ext }
}
if ($missing.Count -gt 0) { Write-Host "Missing PHP extensions: $($missing -join ', ')" } else { Write-Host "All required PHP extensions present" }

Write-Host "-> Composer availability"
if (Get-Command composer -ErrorAction SilentlyContinue) { composer --version } else { Write-Host "Composer not found (skip)" }

Write-Host "-> Artisan check"
try { php artisan --version } catch { Write-Host "artisan not runnable" }

Write-Host "-> .env check"
if (Test-Path .env) { Get-Content .env | Select-String -Pattern 'APP_KEY|APP_ENV|DB_CONNECTION' } else { Write-Host '.env not found' }

Write-Host "-> Storage permissions"
Get-ChildItem -Path storage, bootstrap/cache -Force -ErrorAction SilentlyContinue | Select-Object Mode,FullName

Write-Host "-> Tail laravel.log (last 50 lines)"
$log = 'storage/logs/laravel.log'
if (Test-Path $log) { Get-Content $log -Tail 50 } else { Write-Host 'No laravel.log found' }

Write-Host "== Health checks completed =="
