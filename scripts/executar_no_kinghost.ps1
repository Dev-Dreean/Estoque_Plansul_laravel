# one-off: Executar alteração em massa NO KINGHOST
# Data: 2026-01-12
# Ação: Alterar patrimônios da planilha para Projeto 8, Local 2059, Beatriz

Write-Host "🚀 [KINGHOST] Alteração em massa de patrimônios" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

# Configurações
$KINGHOST_USER = "plansul"
$KINGHOST_HOST = "ftp.plansul.info"
$APP_PATH = "~/www/estoque-laravel"
$ARQUIVO_PLAN = "Massa/Alterações em massa.xlsx"

Write-Host "📋 Dados da alteração:" -ForegroundColor Yellow
Write-Host "   • Projeto: 8 (SEDE)"
Write-Host "   • Local: 2059 (Sala Comercial)"
Write-Host "   • Situação: À DISPOSIÇÃO"
Write-Host "   • Usuário: BEATRIZ.SC"
Write-Host "   • Verificado: S"
Write-Host ""

# 1. Verificar se planilha existe
if (-not (Test-Path $ARQUIVO_PLAN)) {
    Write-Host "❌ Planilha não encontrada: $ARQUIVO_PLAN" -ForegroundColor Red
    Write-Host "💡 Certifique-se de ter preenchido a planilha com os números dos patrimônios" -ForegroundColor Yellow
    exit 1
}

Write-Host "✅ Planilha encontrada localmente" -ForegroundColor Green
Write-Host ""

# 2. Upload da planilha
Write-Host "📤 Fazendo upload da planilha para KingHost..." -ForegroundColor Yellow
try {
    scp $ARQUIVO_PLAN "${KINGHOST_USER}@${KINGHOST_HOST}:${APP_PATH}/Massa/"
    Write-Host "✅ Upload concluído" -ForegroundColor Green
} catch {
    Write-Host "❌ Erro no upload: $_" -ForegroundColor Red
    exit 1
}
Write-Host ""

# 3. Verificar conexão e ambiente
Write-Host "🔍 Verificando ambiente KingHost..." -ForegroundColor Yellow
ssh "${KINGHOST_USER}@${KINGHOST_HOST}" "cd $APP_PATH && php82 --version && ls -lh $ARQUIVO_PLAN"
Write-Host ""

# 4. DRY-RUN (simulação)
Write-Host "🔍 PASSO 1: DRY-RUN (simulação sem gravar)" -ForegroundColor Yellow
Write-Host "============================================================" -ForegroundColor Yellow
ssh "${KINGHOST_USER}@${KINGHOST_HOST}" "cd $APP_PATH && php82 artisan patrimonios:bulk-update '$ARQUIVO_PLAN' --dry-run"
Write-Host ""

# 5. Solicitar confirmação
$confirmacao = Read-Host "⚠️  Executar alteração REAL no KingHost? (digite 'sim' para confirmar)"

if ($confirmacao -ne "sim") {
    Write-Host ""
    Write-Host "❌ Operação cancelada pelo usuário" -ForegroundColor Red
    exit 0
}

Write-Host ""
Write-Host "🚀 PASSO 2: Executando alteração REAL no KingHost" -ForegroundColor Yellow
Write-Host "============================================================" -ForegroundColor Yellow

# 6. Executar alteração REAL
ssh "${KINGHOST_USER}@${KINGHOST_HOST}" "cd $APP_PATH && php82 artisan patrimonios:bulk-update '$ARQUIVO_PLAN'"

Write-Host ""
Write-Host "✅ Processo concluído!" -ForegroundColor Green
Write-Host ""

# 7. Verificar backup foi criado
Write-Host "📋 Verificando backup criado..." -ForegroundColor Yellow
ssh "${KINGHOST_USER}@${KINGHOST_HOST}" "ls -lah $APP_PATH/storage/backups/ | tail -3"

Write-Host ""
Write-Host "📊 Próximos passos:" -ForegroundColor Cyan
Write-Host "   1. Acessar sistema e verificar patrimônios alterados"
Write-Host "   2. Conferir backup em storage/backups/"
Write-Host "   3. Logs disponíveis em storage/logs/"
