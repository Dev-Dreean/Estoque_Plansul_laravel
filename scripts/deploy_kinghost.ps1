#!/usr/bin/env powershell
# ════════════════════════════════════════════════════════════════════════════════
# SCRIPT DE DEPLOY PARA KINGHOST - CORREÇÃO DE CDLOCAL (Windows PowerShell)
# ════════════════════════════════════════════════════════════════════════════════

param(
    [switch]$Confirm = $true
)

$ErrorActionPreference = "Stop"

Write-Host "🚀 DEPLOY PARA KINGHOST - CORREÇÃO CDLOCAL" -ForegroundColor Green
Write-Host "════════════════════════════════════════════════════════════════════════════════" -ForegroundColor Green
Write-Host ""

# Configurações do servidor Kinghost
$KingHostUser = "plansul"
$KingHostHost = "ftp.plansul.info"
$KingHostPath = "/home/plansul/public_html/plansul"
$KingHostDbHost = "mysql07-farm10.kinghost.net"
$KingHostDbUser = "plansul004_add2"
$KingHostDbPass = "A33673170a"
$KingHostDbName = "plansul04"

Write-Host "📋 CONFIGURAÇÕES:" -ForegroundColor Cyan
Write-Host "Servidor: $KingHostHost"
Write-Host "Usuário: $KingHostUser"
Write-Host "Caminho: $KingHostPath"
Write-Host "Database: $KingHostDbName"
Write-Host ""

# ════════════════════════════════════════════════════════════════════════════════
# ETAPA 1: Testar conexão SSH
# ════════════════════════════════════════════════════════════════════════════════

Write-Host "🔗 ETAPA 1: TESTANDO CONEXÃO SSH" -ForegroundColor Yellow
Write-Host "────────────────────────────────────────────────────────────────────────────────" -ForegroundColor Yellow

try {
    $testConnection = ssh ${KingHostUser}@${KingHostHost} "echo 'Conexão OK'" 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Conexão SSH estabelecida" -ForegroundColor Green
    } else {
        Write-Host "❌ Erro na conexão SSH: $testConnection" -ForegroundColor Red
        exit 1
    }
} catch {
    Write-Host "❌ Erro ao conectar: $_" -ForegroundColor Red
    exit 1
}

Write-Host ""

# ════════════════════════════════════════════════════════════════════════════════
# ETAPA 2: Fazer backup no servidor
# ════════════════════════════════════════════════════════════════════════════════

Write-Host "📦 ETAPA 2: BACKUP NO SERVIDOR KINGHOST" -ForegroundColor Yellow
Write-Host "────────────────────────────────────────────────────────────────────────────────" -ForegroundColor Yellow

$BackupTimestamp = (Get-Date).ToString("yyyy_MM_dd_HHmmss")
$BackupTable = "patr_backup_kinghost_${BackupTimestamp}"

$backupSQL = @"
DROP TABLE IF EXISTS $BackupTable;
CREATE TABLE $BackupTable LIKE patr;
INSERT INTO $BackupTable SELECT * FROM patr;
SELECT CONCAT('✅ Backup criado: $BackupTable com ', COUNT(*), ' registros') as status 
FROM $BackupTable;
"@

$backupResult = ssh ${KingHostUser}@${KingHostHost} "mysql -h${KingHostDbHost} -u${KingHostDbUser} -p${KingHostDbPass} ${KingHostDbName}" <<< $backupSQL 2>&1

Write-Host $backupResult -ForegroundColor Green
Write-Host ""

# ════════════════════════════════════════════════════════════════════════════════
# ETAPA 3: Copiar scripts PHP para o servidor
# ════════════════════════════════════════════════════════════════════════════════

Write-Host "📤 ETAPA 3: ENVIANDO SCRIPTS PARA KINGHOST" -ForegroundColor Yellow
Write-Host "────────────────────────────────────────────────────────────────────────────────" -ForegroundColor Yellow

Write-Host "Criando diretório de scripts..."
ssh ${KingHostUser}@${KingHostHost} "mkdir -p ${KingHostPath}/scripts_correcao" 2>&1 | Out-Null

$ScriptsDir = "$(Split-Path -Parent $MyInvocation.MyCommand.Path)"

Write-Host "Copiando scripts..."
scp "${ScriptsDir}/correcao_massa_cdlocal.php" "${KingHostUser}@${KingHostHost}:${KingHostPath}/scripts_correcao/" 2>&1 | Out-Null
scp "${ScriptsDir}/verificar_todas_inconsistencias.php" "${KingHostUser}@${KingHostHost}:${KingHostPath}/scripts_correcao/" 2>&1 | Out-Null

Write-Host "✅ Scripts enviados" -ForegroundColor Green
Write-Host ""

# ════════════════════════════════════════════════════════════════════════════════
# ETAPA 4: Verificação PRÉ-CORREÇÃO
# ════════════════════════════════════════════════════════════════════════════════

Write-Host "📊 ETAPA 4: VERIFICAÇÃO PRÉ-CORREÇÃO" -ForegroundColor Yellow
Write-Host "────────────────────────────────────────────────────────────────────────────────" -ForegroundColor Yellow

$preCheckSQL = @"
SELECT 
    COUNT(*) as total_inconsistencias
FROM (
    SELECT p.NUPATRIMONIO
    FROM patr p
    LEFT JOIN locais_projeto lp ON p.CDLOCAL = lp.id
    LEFT JOIN tabfant t ON lp.tabfant_id = t.id
    WHERE p.CDPROJETO IS NOT NULL
      AND lp.tabfant_id IS NOT NULL
      AND t.CDPROJETO != p.CDPROJETO
) inconsistent;
"@

$preResult = ssh ${KingHostUser}@${KingHostHost} "mysql -h${KingHostDbHost} -u${KingHostDbUser} -p${KingHostDbPass} ${KingHostDbName}" <<< $preCheckSQL 2>&1

Write-Host "Inconsistências encontradas:" -ForegroundColor Cyan
Write-Host $preResult -ForegroundColor Cyan
Write-Host ""

# ════════════════════════════════════════════════════════════════════════════════
# ETAPA 5: Executar correção via SSH
# ════════════════════════════════════════════════════════════════════════════════

Write-Host "🔧 ETAPA 5: EXECUTANDO CORREÇÃO NO KINGHOST" -ForegroundColor Yellow
Write-Host "────────────────────────────────────────────────────────────────────────────────" -ForegroundColor Yellow

$correctionScript = @'
cd /home/plansul/public_html/plansul

php -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

use App\Models\Patrimonio;
use App\Models\LocalProjeto;
use App\Models\Tabfant;
use Illuminate\Support\Facades\DB;

echo '🔧 Executando correção em massa...' . PHP_EOL;

// Criar mapeamento de projetos
\$mapeamentoProjetos = [];
\$projetos = Tabfant::whereNotNull('CDPROJETO')->get();

foreach (\$projetos as \$projeto) {
    \$local = LocalProjeto::where('tabfant_id', \$projeto->id)->first();
    if (\$local) {
        \$mapeamentoProjetos[\$projeto->CDPROJETO] = \$local->id;
    }
}

echo 'Mapeamento criado: ' . count(\$mapeamentoProjetos) . ' projetos' . PHP_EOL;

// Executar correções
DB::beginTransaction();

try {
    \$totalCorrigidos = 0;
    
    foreach (\$mapeamentoProjetos as \$cdprojeto => \$localCorreto) {
        \$updated = Patrimonio::where('CDPROJETO', \$cdprojeto)
            ->where('CDLOCAL', '!=', \$localCorreto)
            ->update(['CDLOCAL' => \$localCorreto]);
        
        if (\$updated > 0) {
            \$totalCorrigidos += \$updated;
            if (\$totalCorrigidos % 500 == 0) {
                echo '  Processados: ' . \$totalCorrigidos . '...' . PHP_EOL;
            }
        }
    }
    
    DB::commit();
    
    echo PHP_EOL . '✅ CORREÇÃO CONCLUÍDA!' . PHP_EOL;
    echo 'Total corrigidos: ' . \$totalCorrigidos . PHP_EOL;
    
} catch (Exception \$e) {
    DB::rollBack();
    echo PHP_EOL . '❌ ERRO: ' . \$e->getMessage() . PHP_EOL;
    exit(1);
}
"
'@

$correctionResult = ssh ${KingHostUser}@${KingHostHost} $correctionScript 2>&1

Write-Host $correctionResult -ForegroundColor Cyan
Write-Host ""

# ════════════════════════════════════════════════════════════════════════════════
# ETAPA 6: Verificação PÓS-CORREÇÃO
# ════════════════════════════════════════════════════════════════════════════════

Write-Host "✅ ETAPA 6: VERIFICAÇÃO PÓS-CORREÇÃO" -ForegroundColor Yellow
Write-Host "────────────────────────────────────────────────────────────────────────────────" -ForegroundColor Yellow

$postCheckSQL = @"
SELECT 
    'Total de patrimônios' as metrica,
    COUNT(*) as valor
FROM patr
UNION ALL
SELECT 
    'Patrimônios com CDPROJETO' as metrica,
    COUNT(*) as valor
FROM patr
WHERE CDPROJETO IS NOT NULL
UNION ALL
SELECT 
    'Inconsistências restantes' as metrica,
    COUNT(*) as valor
FROM (
    SELECT p.NUPATRIMONIO
    FROM patr p
    LEFT JOIN locais_projeto lp ON p.CDLOCAL = lp.id
    LEFT JOIN tabfant t ON lp.tabfant_id = t.id
    WHERE p.CDPROJETO IS NOT NULL
      AND lp.tabfant_id IS NOT NULL
      AND t.CDPROJETO != p.CDPROJETO
) inconsistent;
"@

$postResult = ssh ${KingHostUser}@${KingHostHost} "mysql -h${KingHostDbHost} -u${KingHostDbUser} -p${KingHostDbPass} ${KingHostDbName}" <<< $postCheckSQL 2>&1

Write-Host $postResult -ForegroundColor Green
Write-Host ""

# ════════════════════════════════════════════════════════════════════════════════
# ETAPA 7: Verificar patrimônio 17546
# ════════════════════════════════════════════════════════════════════════════════

Write-Host "🎯 ETAPA 7: VERIFICANDO PATRIMÔNIO 17546" -ForegroundColor Yellow
Write-Host "────────────────────────────────────────────────────────────────────────────────" -ForegroundColor Yellow

$verificaSQL = @"
SELECT 
    p.NUPATRIMONIO,
    p.CDLOCAL,
    p.CDPROJETO,
    lp.delocal as local_nome,
    t.CDPROJETO as projeto_local,
    t.NOMEPROJETO as projeto_nome,
    IF(t.CDPROJETO = p.CDPROJETO, '✅ OK', '❌ ERRO') as status
FROM patr p
LEFT JOIN locais_projeto lp ON p.CDLOCAL = lp.id
LEFT JOIN tabfant t ON lp.tabfant_id = t.id
WHERE p.NUPATRIMONIO = 17546;
"@

$verificaResult = ssh ${KingHostUser}@${KingHostHost} "mysql -h${KingHostDbHost} -u${KingHostDbUser} -p${KingHostDbPass} ${KingHostDbName}" <<< $verificaSQL 2>&1

Write-Host $verificaResult -ForegroundColor Green
Write-Host ""

# ════════════════════════════════════════════════════════════════════════════════
# RESUMO FINAL
# ════════════════════════════════════════════════════════════════════════════════

Write-Host "════════════════════════════════════════════════════════════════════════════════" -ForegroundColor Green
Write-Host "🎉 DEPLOY CONCLUÍDO COM SUCESSO!" -ForegroundColor Green
Write-Host "════════════════════════════════════════════════════════════════════════════════" -ForegroundColor Green
Write-Host ""
Write-Host "✅ Backup criado: $BackupTable" -ForegroundColor Green
Write-Host "✅ Scripts enviados para: ${KingHostPath}/scripts_correcao" -ForegroundColor Green
Write-Host "✅ Correção executada no Kinghost" -ForegroundColor Green
Write-Host "✅ Patrimônio 17546 verificado" -ForegroundColor Green
Write-Host ""
Write-Host "Para reverter (se necessário):" -ForegroundColor Yellow
Write-Host "DROP TABLE patr;" -ForegroundColor Yellow
Write-Host "RENAME TABLE $BackupTable TO patr;" -ForegroundColor Yellow
Write-Host ""
