#!/bin/bash

# Verificar dados atribuídos no servidor
echo "=== VERIFICANDO DADOS ATRIBUÍDOS ==="
mysql -h mysql07-farm10.kinghost.net -u plansul004_add2 -pA33673170a plansul04 <<EOF
SELECT COUNT(*) as 'Total de patrimônios' FROM patr;
SELECT COUNT(*) as 'Com NMPLANTA (Atribuídos)' FROM patr WHERE NMPLANTA IS NOT NULL;
SELECT COUNT(*) as 'Sem NMPLANTA (Disponíveis)' FROM patr WHERE NMPLANTA IS NULL;
EOF
