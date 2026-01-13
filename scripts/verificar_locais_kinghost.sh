#!/bin/bash
# Script temporário para verificar dados do KingHost

echo "=== PRÉVIA DE SINCRONIZAÇÃO ==="
echo ""

# Conectar ao KingHost e buscar dados
mysql -h mysql07-farm10.kinghost.net -u plansul004_add2 -pA33673170a plansul04 << EOF
SELECT COUNT(*) as 'Total de Locais no KingHost' FROM locais_projeto;

SELECT 'Primeiros 5 registros:';
SELECT id, cdlocal, delocal, tabfant_id FROM locais_projeto LIMIT 5;

SELECT '' as 'Status';
SELECT tabfant_id as 'Projeto ID', COUNT(*) as 'Qtd Locais' FROM locais_projeto GROUP BY tabfant_id ORDER BY COUNT(*) DESC LIMIT 10;
EOF

echo ""
echo "✅ Dados do KingHost recuperados"
