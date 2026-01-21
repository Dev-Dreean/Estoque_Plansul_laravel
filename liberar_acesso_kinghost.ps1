# one-off: Liberar acesso para THEO, TIAGOP, BEA.SC no KingHost
# Uso: .\liberar_acesso_kinghost.ps1

Write-Host "=== LIBERANDO ACESSO NO KINGHOST ===" -ForegroundColor Green

# Ler o arquivo SQL
$sqlFile = "scripts/liberar_acesso.sql"
if (-not (Test-Path $sqlFile)) {
    Write-Host "Erro: arquivo $sqlFile não encontrado" -ForegroundColor Red
    exit 1
}

$sqlContent = Get-Content $sqlFile -Raw

# Executar SSH
Write-Host "Conectando ao KingHost..." -ForegroundColor Yellow

$comando = @"
mysql -h mysql07-farm10.kinghost.net -u plansul004_add2 -p'A33673170a' plansul04 << 'EOF'
$sqlContent
EOF
"@

# Executar SSH
ssh plansul@ftp.plansul.info $comando

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Acesso liberado com sucesso!" -ForegroundColor Green
} else {
    Write-Host "❌ Erro ao liberar acesso" -ForegroundColor Red
    exit 1
}
