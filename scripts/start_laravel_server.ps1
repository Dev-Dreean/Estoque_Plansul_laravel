# Script para iniciar WampServer (se necessário) e Laravel artisan serve
# Uso: .\scripts\start-laravel-server.ps1

Write-Host "🚀 Iniciando servidor Laravel com suporte a WampServer..." -ForegroundColor Cyan

# 1. Verificar se WampServer está rodando
$wampProcesses = @(
    'wampmanager',
    'wampmanager64',
    'apache',
    'mysqld'
)

$isWampRunning = $false
foreach ($process in $wampProcesses) {
    if (Get-Process -Name $process -ErrorAction SilentlyContinue) {
        $isWampRunning = $true
        break
    }
}

if (-not $isWampRunning) {
    Write-Host "⚠️  WampServer não está rodando. Iniciando..." -ForegroundColor Yellow
    
    # Tentar encontrar e iniciar WampServer
    $wampPaths = @(
        "C:\wamp\wampmanager.exe",
        "C:\wamp64\wampmanager.exe",
        "C:\Program Files\wamp\wampmanager.exe",
        "C:\Program Files (x86)\wamp\wampmanager.exe",
        "C:\Program Files\wamp64\wampmanager.exe"
    )
    
    $wampFound = $false
    foreach ($path in $wampPaths) {
        if (Test-Path $path) {
            Write-Host "✅ Encontrado WampServer em: $path" -ForegroundColor Green
            Start-Process -FilePath $path -WindowStyle Minimized
            $wampFound = $true
            
            # Aguardar o WampServer iniciar (até 15 segundos)
            Write-Host "⏳ Aguardando WampServer iniciar..." -ForegroundColor Cyan
            $attempts = 0
            while (-not (Get-Process -Name 'wampmanager*' -ErrorAction SilentlyContinue) -and $attempts -lt 15) {
                Start-Sleep -Seconds 1
                $attempts++
            }
            
            if ($attempts -lt 15) {
                Write-Host "✅ WampServer iniciado com sucesso!" -ForegroundColor Green
                Start-Sleep -Seconds 3 # Esperar mais um pouco para MySQL estar pronto
            }
            else {
                Write-Host "⚠️  WampServer demorou para iniciar, continuando mesmo assim..." -ForegroundColor Yellow
            }
            break
        }
    }
    
    if (-not $wampFound) {
        Write-Host "❌ WampServer não foi encontrado em nenhum caminho padrão." -ForegroundColor Red
        Write-Host "Por favor, instale WampServer ou ajuste os caminhos no script." -ForegroundColor Red
        Read-Host "Pressione Enter para continuar..."
    }
}
else {
    Write-Host "✅ WampServer já está rodando" -ForegroundColor Green
}

# 2. Executar artisan serve
Write-Host "🎯 Iniciando Laravel serve..." -ForegroundColor Cyan
Write-Host "Local: http://localhost:8000" -ForegroundColor Yellow
Write-Host "Padrão configurado via .env: SERVER_HOST=0.0.0.0 / SERVER_PORT=8000" -ForegroundColor Cyan

$ipv4 = (Get-NetIPAddress -AddressFamily IPv4 |
    Where-Object {
        $_.IPAddress -notlike '127.*' -and
        $_.IPAddress -notlike '169.254.*' -and
        $_.PrefixOrigin -ne 'WellKnown'
    } |
    Sort-Object InterfaceMetric |
    Select-Object -First 1 -ExpandProperty IPAddress)

if ($ipv4) {
    Write-Host "Rede: http://$ipv4`:8000" -ForegroundColor Green
}

php artisan serve
