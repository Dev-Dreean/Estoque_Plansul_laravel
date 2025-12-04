#!/bin/bash
# ════════════════════════════════════════════════════════════════════════════════
# SCRIPT DE DEPLOY PARA KINGHOST - CORREÇÃO DE CDLOCAL
# ════════════════════════════════════════════════════════════════════════════════

set -e

echo "🚀 DEPLOY PARA KINGHOST - CORREÇÃO CDLOCAL"
echo "════════════════════════════════════════════════════════════════════════════════"
echo ""

# Configurações do servidor Kinghost
KINGHOST_USER="plansul"
KINGHOST_HOST="ftp.plansul.info"
KINGHOST_PATH="/home/plansul/public_html/plansul"
KINGHOST_DB_HOST="mysql07-farm10.kinghost.net"
KINGHOST_DB_USER="plansul004_add2"
KINGHOST_DB_PASS="A33673170a"
KINGHOST_DB_NAME="plansul04"

# Caminhos locais
LOCAL_SCRIPTS_DIR="$(cd "$(dirname "$0")" && pwd)"
LOCAL_PROJECT_DIR="$(cd "$LOCAL_SCRIPTS_DIR/.." && pwd)"

echo "📋 CONFIGURAÇÕES:"
echo "Servidor: $KINGHOST_HOST"
echo "Usuário: $KINGHOST_USER"
echo "Caminho: $KINGHOST_PATH"
echo "Database: $KINGHOST_DB_NAME"
echo ""

# ════════════════════════════════════════════════════════════════════════════════
# ETAPA 1: Fazer backup no servidor
# ════════════════════════════════════════════════════════════════════════════════

echo "📦 ETAPA 1: BACKUP NO SERVIDOR KINGHOST"
echo "────────────────────────────────────────────────────────────────────────────────"

BACKUP_TIMESTAMP=$(date +%Y_%m_%d_%H%M%S)
BACKUP_FILE="patr_backup_kinghost_${BACKUP_TIMESTAMP}.sql"

ssh ${KINGHOST_USER}@${KINGHOST_HOST} << EOF
echo "Conectando ao banco de dados..."
mysql -h${KINGHOST_DB_HOST} -u${KINGHOST_DB_USER} -p${KINGHOST_DB_PASS} ${KINGHOST_DB_NAME} -e "
  -- Criar backup da tabela patr
  DROP TABLE IF EXISTS patr_backup_kinghost_${BACKUP_TIMESTAMP};
  CREATE TABLE patr_backup_kinghost_${BACKUP_TIMESTAMP} LIKE patr;
  INSERT INTO patr_backup_kinghost_${BACKUP_TIMESTAMP} SELECT * FROM patr;
  SELECT CONCAT('✅ Backup criado: patr_backup_kinghost_${BACKUP_TIMESTAMP} com ', COUNT(*), ' registros') as status 
  FROM patr_backup_kinghost_${BACKUP_TIMESTAMP};
"
EOF

echo "✅ Backup criado no servidor"
echo ""

# ════════════════════════════════════════════════════════════════════════════════
# ETAPA 2: Copiar scripts PHP para o servidor
# ════════════════════════════════════════════════════════════════════════════════

echo "📤 ETAPA 2: ENVIANDO SCRIPTS PARA KINGHOST"
echo "────────────────────────────────────────────────────────────────────────────────"

# Criar diretório de scripts se não existir
ssh ${KINGHOST_USER}@${KINGHOST_HOST} "mkdir -p ${KINGHOST_PATH}/scripts_correcao"

# Copiar scripts de correção
echo "Copiando scripts de correção..."
scp "${LOCAL_SCRIPTS_DIR}/correcao_massa_cdlocal.php" \
    ${KINGHOST_USER}@${KINGHOST_HOST}:${KINGHOST_PATH}/scripts_correcao/

scp "${LOCAL_SCRIPTS_DIR}/verificar_todas_inconsistencias.php" \
    ${KINGHOST_USER}@${KINGHOST_HOST}:${KINGHOST_PATH}/scripts_correcao/

scp "${LOCAL_SCRIPTS_DIR}/corrigir_cdlocal.sql" \
    ${KINGHOST_USER}@${KINGHOST_HOST}:${KINGHOST_PATH}/scripts_correcao/

echo "✅ Scripts enviados"
echo ""

# ════════════════════════════════════════════════════════════════════════════════
# ETAPA 3: Executar correção via SSH
# ════════════════════════════════════════════════════════════════════════════════

echo "🔧 ETAPA 3: EXECUTANDO CORREÇÃO NO KINGHOST"
echo "────────────────────────────────────────────────────────────────────────────────"

ssh ${KINGHOST_USER}@${KINGHOST_HOST} << 'REMOTE_EOF'
echo "Conectando ao diretório do projeto..."
cd /home/plansul/public_html/plansul

echo "Executando verificação PRÉ-CORREÇÃO..."
echo ""

# Verificação PRÉ-correção
mysql -hmysql07-farm10.kinghost.net -uplansul004_add2 -pA33673170a plansul04 << SQL_PRE
SELECT 
    CONCAT('📊 PRÉ-CORREÇÃO - INCONSISTÊNCIAS:') as status;
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
SQL_PRE

echo ""
echo "Executando script de correção..."
echo ""

# Executar script PHP de correção (automático)
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
    
    // Verificar patrimônio 17546
    \$p = Patrimonio::where('NUPATRIMONIO', 17546)->first();
    if (\$p) {
        \$local = LocalProjeto::find(\$p->CDLOCAL);
        echo PHP_EOL . '✅ Patrimônio 17546 - Verificação:' . PHP_EOL;
        echo '   CDLOCAL: ' . \$p->CDLOCAL . PHP_EOL;
        echo '   CDPROJETO: ' . \$p->CDPROJETO . PHP_EOL;
        if (\$local) {
            echo '   Local: ' . \$local->delocal . PHP_EOL;
        }
    }
    
} catch (Exception \$e) {
    DB::rollBack();
    echo PHP_EOL . '❌ ERRO: ' . \$e->getMessage() . PHP_EOL;
    exit(1);
}
"

REMOTE_EOF

echo ""
echo "✅ Script executado no Kinghost"
echo ""

# ════════════════════════════════════════════════════════════════════════════════
# ETAPA 4: Verificação PÓS-CORREÇÃO
# ════════════════════════════════════════════════════════════════════════════════

echo "✅ ETAPA 4: VERIFICAÇÃO PÓS-CORREÇÃO"
echo "────────────────────────────────────────────────────────────────────────────────"

ssh ${KINGHOST_USER}@${KINGHOST_HOST} << 'REMOTE_EOF'
cd /home/plansul/public_html/plansul

mysql -hmysql07-farm10.kinghost.net -uplansul004_add2 -pA33673170a plansul04 << SQL_POST
SELECT 
    '📊 PÓS-CORREÇÃO - ESTATÍSTICAS:' as status;

-- Total de patrimônios
SELECT 
    'Total de patrimônios' as metrica,
    COUNT(*) as valor
FROM patr;

-- Patrimônios com CDPROJETO válido
SELECT 
    'Patrimônios com CDPROJETO' as metrica,
    COUNT(*) as valor
FROM patr
WHERE CDPROJETO IS NOT NULL;

-- Inconsistências restantes
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

-- Verificação específica do patrimônio 17546
SELECT 
    '---' as separator;
SELECT 
    'Patrimônio 17546' as verificacao,
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

SQL_POST

REMOTE_EOF

echo ""
echo "════════════════════════════════════════════════════════════════════════════════"
echo "🎉 DEPLOY CONCLUÍDO COM SUCESSO!"
echo "════════════════════════════════════════════════════════════════════════════════"
echo ""
echo "✅ Backup criado: patr_backup_kinghost_${BACKUP_TIMESTAMP}"
echo "✅ Scripts enviados para: ${KINGHOST_PATH}/scripts_correcao"
echo "✅ Correção executada"
echo "✅ Patrimônio 17546 verificado"
echo ""
echo "Para reverter (se necessário):"
echo "DROP TABLE patr;"
echo "RENAME TABLE patr_backup_kinghost_${BACKUP_TIMESTAMP} TO patr;"
echo ""
