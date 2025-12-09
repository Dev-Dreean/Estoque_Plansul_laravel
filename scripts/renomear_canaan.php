<?php
require 'vendor/autoload.php';
use Illuminate\Support\Facades\DB;

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Renomeando CANAAN → ESCRITORIO SC\n";

DB::table('locais_projeto')
    ->where('cdlocal', 530)
    ->where('delocal', 'CANAAN')
    ->update(['delocal' => 'ESCRITORIO SC']);

echo "✓ Atualizado!\n";

$verificar = DB::table('locais_projeto')->where('cdlocal', 530)->first();
echo "✓ Resultado: ID {$verificar->id} - {$verificar->delocal}\n";
