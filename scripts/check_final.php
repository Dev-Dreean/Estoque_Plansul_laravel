<?php
$local = new PDO('mysql:host=127.0.0.1;dbname=cadastros_plansul;charset=utf8mb4', 'root', '');
$kinghost = new PDO('mysql:host=mysql07-farm10.kinghost.net;dbname=plansul04;charset=utf8mb4', 'plansul004_add2', 'A33673170a');

$local_count = $local->query('SELECT COUNT(*) FROM patr')->fetch()[0];
$kinghost_count = $kinghost->query('SELECT COUNT(*) FROM patr')->fetch()[0];

echo "LOCAL PATRIMÔNIOS: $local_count\n";
echo "KINGHOST PATRIMÔNIOS: $kinghost_count\n";
echo "DIFERENÇA: " . ($kinghost_count - $local_count) . "\n";
