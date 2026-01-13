<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$p = DB::table('patr')->where('NUPATRIMONIO', 1)->first();
echo json_encode($p, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
