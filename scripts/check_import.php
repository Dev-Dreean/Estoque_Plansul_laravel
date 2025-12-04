<?php
$pdo = new PDO('mysql:host=mysql07-farm10.kinghost.net;dbname=plansul04', 'plansul004_add2', 'A33673170a');
$result = $pdo->query('SELECT COUNT(*) as cnt FROM patr')->fetch(PDO::FETCH_ASSOC);
echo 'Total: ' . $result['cnt'] . "\n";

$verificar = [5243, 33074, 16216, 5640];
foreach ($verificar as $num) {
    $r = $pdo->query("SELECT DEPATRIMONIO, SITUACAO, CDLOCAL FROM patr WHERE NUPATRIMONIO = $num LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($r) {
        echo "#$num: SIT=" . $r['SITUACAO'] . " LOCAL=" . $r['CDLOCAL'] . " DESC=" . substr($r['DEPATRIMONIO'], 0, 40) . "\n";
    }
}
