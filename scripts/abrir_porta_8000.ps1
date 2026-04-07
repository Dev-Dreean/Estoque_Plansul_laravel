# Script para abrir porta 8000 no Firewall do Windows
# Execute como ADMINISTRADOR

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Abrindo porta 8000 no Firewall..." -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Cyan

$ipv4 = (Get-NetIPAddress -AddressFamily IPv4 |
    Where-Object {
        $_.IPAddress -notlike '127.*' -and
        $_.IPAddress -notlike '169.254.*' -and
        $_.PrefixOrigin -ne 'WellKnown'
    } |
    Sort-Object InterfaceMetric |
    Select-Object -First 1 -ExpandProperty IPAddress)

# Verificar se está rodando como admin
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")

if (-not $isAdmin) {
    Write-Host "`n❌ ERRO: Este script precisa ser executado como ADMINISTRADOR!" -ForegroundColor Red
    Write-Host "Clique com botão direito em PowerShell e escolha 'Executar como administrador'" -ForegroundColor Yellow
    exit 1
}

# Remover regra anterior se existir (para evitar duplicatas)
Write-Host "`n🗑️  Removendo regra anterior (se existir)..." -ForegroundColor Gray
Remove-NetFirewallRule -DisplayName "Laravel Port 8000" -ErrorAction SilentlyContinue
Remove-NetFirewallRule -DisplayName "Laravel PHP 8000" -ErrorAction SilentlyContinue

# Criar nova regra permitindo a porta 8000
Write-Host "📝 Criando nova regra de firewall..." -ForegroundColor Yellow
New-NetFirewallRule `
    -DisplayName "Laravel Port 8000" `
    -Direction Inbound `
    -Action Allow `
    -Protocol TCP `
    -LocalPort 8000 `
    -Profile Any `
    -Enabled True

if (Test-Path 'C:\PHP\php.exe') {
    New-NetFirewallRule `
        -DisplayName "Laravel PHP 8000" `
        -Direction Inbound `
        -Action Allow `
        -Program 'C:\PHP\php.exe' `
        -Profile Any `
        -Enabled True | Out-Null
}

Write-Host "`n✅ Porta 8000 aberta com sucesso!" -ForegroundColor Green

# Verificar se o servidor está rodando
Write-Host "`n🔍 Verificando se o servidor está ativo..." -ForegroundColor Cyan
$processo = netstat -ano | Select-String ":8000.*LISTENING"

if ($processo) {
    Write-Host "✅ Servidor está rodando em porta 8000!" -ForegroundColor Green
    if ($ipv4) {
        Write-Host "📡 IP atual da máquina: $ipv4" -ForegroundColor Cyan
        Write-Host "`n🌐 Acesse do outro PC em: http://$ipv4`:8000" -ForegroundColor Cyan
    } else {
        Write-Host "⚠️ Não foi possível detectar o IP automaticamente." -ForegroundColor Yellow
    }
} else {
    Write-Host "`n⚠️  ATENÇÃO: Nenhum servidor detectado na porta 8000!" -ForegroundColor Yellow
    Write-Host "Execute primeiro: php artisan serve" -ForegroundColor Yellow
}

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "Pressione uma tecla para fechar..." -ForegroundColor Gray
[void][console]::ReadKey($true)
