#!/bin/bash
# Setup: Criar usuário consultor no banco real do KingHost

HOST="mysql07-farm10.kinghost.net"
USER="plansul004_add2"
PASS="A33673170a"
DB="plansul04"

echo "1. Verificando estrutura da tabela usuario..."
mysql -h $HOST -u $USER -p$PASS $DB -e "DESCRIBE usuario;" | head -20

echo ""
echo "2. Verificando usuários existentes..."
mysql -h $HOST -u $USER -p$PASS $DB -e "SELECT NMLOGIN, NOMEUSER, PERFIL FROM usuario LIMIT 5;"

echo ""
echo "3. Inserindo usuário consultor..."
mysql -h $HOST -u $USER -p$PASS $DB << EOF
INSERT IGNORE INTO usuario (NMLOGIN, NOMEUSER, PERFIL, CDMATRFUNCIONARIO, ATIVO)
VALUES ('consultor', 'Consultor', 'C', 9999, 'S');
EOF

echo ""
echo "4. Verificando criação..."
mysql -h $HOST -u $USER -p$PASS $DB -e "SELECT NMLOGIN, NOMEUSER, PERFIL, ATIVO FROM usuario WHERE NMLOGIN = 'consultor';"
