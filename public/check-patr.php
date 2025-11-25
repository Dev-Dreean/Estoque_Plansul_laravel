<?php
$mysqli = new mysqli("localhost", "root", "", "plansul04");

$result = $mysqli->query("SELECT COUNT(*) as total FROM patr WHERE NMPLANTA IS NULL");
$row = $result->fetch_assoc();
echo "Patrimônios com NMPLANTA IS NULL: " . $row["total"] . "\n";

$result2 = $mysqli->query("SELECT COUNT(*) as total FROM patr WHERE NMPLANTA IS NOT NULL");
$row2 = $result2->fetch_assoc();
echo "Patrimônios com NMPLANTA NOT NULL: " . $row2["total"] . "\n";

$result3 = $mysqli->query("SELECT COUNT(*) as total FROM patr");
$row3 = $result3->fetch_assoc();
echo "Total de patrimônios: " . $row3["total"] . "\n";

$result4 = $mysqli->query("SELECT COUNT(*) as total FROM patr WHERE DEPATRIMONIO IS NULL");
$row4 = $result4->fetch_assoc();
echo "Patrimônios com DEPATRIMONIO IS NULL: " . $row4["total"] . "\n";
