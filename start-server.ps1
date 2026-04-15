# script para iniciar servidor Laravel com IP visível correto
$ip = (Get-NetIPAddress -AddressFamily IPv4 -AddressState Preferred -ErrorAction SilentlyContinue | Where-Object { $_.IPAddress -notmatch '^127\.' } | Select-Object -First 1).IPAddress
if (-not $ip) {
    $ip = "127.0.0.1"
}

Write-Host "🚀 Iniciando servidor Laravel..." -ForegroundColor Green
Write-Host "   Acesse em: http://$ip`:8000" -ForegroundColor Cyan
Write-Host ""

php artisan serve --host=$ip --port=8000
