<?php
// one-off: Extrair telas da tabela acessotela para restaurar no KingHost

$pdo = new PDO(
    'mysql:host=127.0.0.1;dbname=cadastros_plansul;charset=utf8mb4',
    'root',
    '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$stmt = $pdo->query("SELECT NUSEQTELA, DETELA, FLACESSO, NMSISTEMA, NIVEL_VISIBILIDADE FROM acessotela ORDER BY NUSEQTELA");
$telas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Telas encontradas no banco local:\n";
echo "==================================\n\n";

foreach ($telas as $tela) {
    echo sprintf(
        "INSERT INTO acessotela (NUSEQTELA, DETELA, FLACESSO, NMSISTEMA, NIVEL_VISIBILIDADE) VALUES (%d, '%s', '%s', '%s', '%s');\n",
        $tela['NUSEQTELA'],
        addslashes($tela['DETELA'] ?? ''),
        $tela['FLACESSO'] ?? 'S',
        addslashes($tela['NMSISTEMA'] ?? ''),
        $tela['NIVEL_VISIBILIDADE'] ?? 'TODOS'
    );
}

echo "\n==================================\n";
echo "Total de telas: " . count($telas) . "\n";
?>
