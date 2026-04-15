<?php
// one-off: Find Tiago employee
require __DIR__ . '/../bootstrap/app.php';
use Illuminate\Support\Facades\DB;

$results = DB::table('funcionarios')
    ->select('CDMATRFUNCIONARIO', 'NMFUNCIONARIO')
    ->whereRaw("UPPER(NMFUNCIONARIO) LIKE '%TIAGO%'")
    ->limit(5)
    ->get();

echo "Possíveis Tiagos:\n";
foreach ($results as $row) {
    echo "- {$row->CDMATRFUNCIONARIO} | {$row->NMFUNCIONARIO}\n";
}
