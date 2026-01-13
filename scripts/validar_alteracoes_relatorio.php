<?php
// one-off: Validar alterações já feitas
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "📊 VALIDAÇÃO: Patrimônios alterados pela Beatriz\n\n";

$alterados = DB::table('patr')
    ->where('CDPROJETO', 8)
    ->where('CDLOCAL', 530)
    ->where('SITUACAO', 'À DISPOSIÇÃO')
    ->where('USUARIO', 'BEATRIZ.SC')
    ->where('FLCONFERIDO', 'S')
    ->select('NUPATRIMONIO')
    ->orderBy('NUPATRIMONIO')
    ->get();

echo "✅ Total de patrimônios alterados: " . count($alterados) . "\n";
echo "✅ Patrimônios: " . $alterados->pluck('NUPATRIMONIO')->implode(', ') . "\n";
