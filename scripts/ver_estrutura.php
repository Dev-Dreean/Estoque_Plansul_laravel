<?php
$pdo = new PDO('mysql:host=mysql07-farm10.kinghost.net;dbname=plansul04', 'plansul004_add2', 'A33673170a');
$stmt = $pdo->query('SHOW CREATE TABLE patr');
$result = $stmt->fetch();
echo $result['Create Table'];
