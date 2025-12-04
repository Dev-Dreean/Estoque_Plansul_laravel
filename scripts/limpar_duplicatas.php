<?php
$kinghost = new PDO('mysql:host=mysql07-farm10.kinghost.net;dbname=plansul04;charset=utf8mb4', 'plansul004_add2', 'A33673170a');

// Limpar duplicatas em locais_projeto
echo "Limpando duplicatas em locais_projeto...\n";

// Manter apenas primeira ocorrência de cada CDLOCAL
$kinghost->exec('
    DELETE FROM locais_projeto 
    WHERE CDLOCAL IN (
        SELECT x.CDLOCAL FROM (
            SELECT CDLOCAL FROM locais_projeto GROUP BY CDLOCAL HAVING COUNT(*) > 1
        ) x
    )
    AND CDLOCAL NOT IN (
        SELECT y.minid FROM (
            SELECT MIN(CDLOCAL) as minid FROM locais_projeto GROUP BY CDLOCAL
        ) y
    )
');

$final = $kinghost->query('SELECT COUNT(*) FROM locais_projeto')->fetch()[0];
echo "Locais após limpeza: $final\n";

// Fazer o mesmo para tabfant (projetos)
echo "\nLimpando duplicatas em tabfant...\n";

$kinghost->exec('
    DELETE FROM tabfant
    WHERE CDPROJETO IN (
        SELECT x.CDPROJETO FROM (
            SELECT CDPROJETO FROM tabfant GROUP BY CDPROJETO HAVING COUNT(*) > 1
        ) x
    )
    AND CDPROJETO NOT IN (
        SELECT y.minid FROM (
            SELECT MIN(CDPROJETO) as minid FROM tabfant GROUP BY CDPROJETO
        ) y
    )
');

$final_proj = $kinghost->query('SELECT COUNT(*) FROM tabfant')->fetch()[0];
echo "Projetos após limpeza: $final_proj\n";

echo "\n=== SINCRONIZAÇÃO CONCLUÍDA ===\n";
echo "tabfant:      $final_proj (LOCAL: 874)\n";
echo "locais_projeto: $final (LOCAL: 1936)\n";
