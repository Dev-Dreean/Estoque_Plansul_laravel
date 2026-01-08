#!/bin/bash
# one-off: Atualizar patrimônios no KingHost

mysql -h mysql07-farm10.kinghost.net -u plansul004_add2 -p'A33673170a' plansul04 << EOF
SELECT id, cdlocal, delocal, tabfant_id FROM locais_projeto WHERE delocal LIKE 'COPA%' AND tabfant_id = 8 LIMIT 1;
EOF
