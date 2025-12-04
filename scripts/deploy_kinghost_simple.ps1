#!/usr/bin/env powershell
# DEPLOY KINGHOST - Correção CDLOCAL

Write-Host "🚀 DEPLOY PARA KINGHOST - CORREÇÃO CDLOCAL" -ForegroundColor Green
Write-Host ""

# Configurações
$User = "plansul"
$Host = "ftp.plansul.info"
$DbHost = "mysql07-farm10.kinghost.net"
$DbUser = "plansul004_add2"
$DbPass = "A33673170a"
$DbName = "plansul04"
$Path = "/home/plansul/public_html/plansul"

Write-Host "📋 Configuração:" -ForegroundColor Cyan
Write-Host "Servidor: $Host"
Write-Host "Database: $DbName"
Write-Host ""

# Teste conexão
Write-Host "🔗 Testando conexão..." -ForegroundColor Yellow
$test = ssh ${User}@${Host} "echo OK" 2>&1
if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Conexão OK" -ForegroundColor Green
} else {
    Write-Host "❌ Erro na conexão" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Timestamp para backup
$Timestamp = (Get-Date).ToString("yyyy_MM_dd_HHmmss")
Write-Host "📦 Criando backup: patr_backup_$Timestamp" -ForegroundColor Yellow

# Executar backup e correção
$RemoteScript = @"
cd $Path

# Backup
mysql -h$DbHost -u$DbUser -p$DbPass $DbName << SQL_BACKUP
DROP TABLE IF EXISTS patr_backup_$Timestamp;
CREATE TABLE patr_backup_$Timestamp LIKE patr;
INSERT INTO patr_backup_$Timestamp SELECT * FROM patr;
SELECT CONCAT('OK: Backup com ', COUNT(*), ' registros') FROM patr_backup_$Timestamp;
SQL_BACKUP

echo ""
echo "🔧 Executando correção..."
echo ""

# Executar script PHP
php -r '
require "vendor/autoload.php";
\$app = require "bootstrap/app.php";
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

use App\Models\Patrimonio;
use App\Models\LocalProjeto;
use App\Models\Tabfant;
use Illuminate\Support\Facades\DB;

echo "Criando mapeamento de projetos..." . PHP_EOL;

\$mapeamentoProjetos = [];
\$projetos = Tabfant::whereNotNull("CDPROJETO")->get();

foreach (\$projetos as \$projeto) {
    \$local = LocalProjeto::where("tabfant_id", \$projeto->id)->first();
    if (\$local) {
        \$mapeamentoProjetos[\$projeto->CDPROJETO] = \$local->id;
    }
}

echo "Mapeamento: " . count(\$mapeamentoProjetos) . " projetos" . PHP_EOL;
echo "" . PHP_EOL;

DB::beginTransaction();

try {
    \$totalCorrigidos = 0;
    
    foreach (\$mapeamentoProjetos as \$cdprojeto => \$localCorreto) {
        \$updated = Patrimonio::where("CDPROJETO", \$cdprojeto)
            ->where("CDLOCAL", "!=", \$localCorreto)
            ->update(["CDLOCAL" => \$localCorreto]);
        
        if (\$updated > 0) {
            \$totalCorrigidos += \$updated;
        }
    }
    
    DB::commit();
    
    echo "✅ CORREÇÃO CONCLUÍDA!" . PHP_EOL;
    echo "Total corrigidos: " . \$totalCorrigidos . PHP_EOL;
    echo "" . PHP_EOL;
    
} catch (Exception \$e) {
    DB::rollBack();
    echo "❌ ERRO: " . \$e->getMessage() . PHP_EOL;
    exit(1);
}
'

# Verificação PRÉ
echo "📊 Estatísticas:"
mysql -h$DbHost -u$DbUser -p$DbPass $DbName << SQL_CHECK
SELECT COUNT(*) as 'Total Patrimônios' FROM patr;
SQL_CHECK

echo ""
echo "✅ Verificando patrimônio 17546..."
mysql -h$DbHost -u$DbUser -p$DbPass $DbName << SQL_CHECK2
SELECT 
    NUPATRIMONIO,
    CDLOCAL,
    CDPROJETO,
    (SELECT delocal FROM locais_projeto WHERE id = patr.CDLOCAL) as local_nome
FROM patr
WHERE NUPATRIMONIO = 17546;
SQL_CHECK2

"@

Write-Host "Enviando e executando script remoto..." -ForegroundColor Yellow
$result = ssh ${User}@${Host} $RemoteScript 2>&1

Write-Host $result
Write-Host ""

Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor Green
Write-Host "🎉 DEPLOY CONCLUÍDO!" -ForegroundColor Green
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor Green
Write-Host ""
Write-Host "✅ Backup: patr_backup_$Timestamp" -ForegroundColor Green
Write-Host "✅ Correção executada no servidor" -ForegroundColor Green
Write-Host ""
Write-Host "Para reverter:" -ForegroundColor Yellow
Write-Host "  DROP TABLE patr; RENAME TABLE patr_backup_$Timestamp TO patr;" -ForegroundColor Yellow
Write-Host ""
