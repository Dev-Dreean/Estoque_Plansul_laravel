#!/bin/bash
# one-off: Adicionar funcionário 200288 - FELIPE DA COSTA BUENO LOCHE no KingHost

KINGHOST_USER="plansul"
KINGHOST_HOST="ftp.plansul.info"
KINGHOST_DB="plansul04"
KINGHOST_DB_HOST="mysql07-farm10.kinghost.net"
KINGHOST_DB_USER="plansul004_add2"
KINGHOST_DB_PASS="A33673170a"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] INI Adicionando funcionário 200288..."

# Inserir funcionário na tabela funcionarios do KingHost
ssh ${KINGHOST_USER}@${KINGHOST_HOST} "mysql -h ${KINGHOST_DB_HOST} -u ${KINGHOST_DB_USER} -p'${KINGHOST_DB_PASS}' ${KINGHOST_DB} -e \"INSERT INTO funcionarios (CDMATRFUNCIONARIO, NOMEFUNCIONARIO, DTCADASTRO) VALUES ('200288', 'FELIPE DA COSTA BUENO LOCHE', NOW()) ON DUPLICATE KEY UPDATE NOMEFUNCIONARIO='FELIPE DA COSTA BUENO LOCHE';\" 2>&1"

if [ $? -eq 0 ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ✅ Funcionário adicionado com sucesso!"
else
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ❌ Erro ao adicionar funcionário"
    exit 1
fi

echo "[$(date '+%Y-%m-%d %H:%M:%S')] FIM"
