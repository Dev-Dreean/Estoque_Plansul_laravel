<?php
// Teste da API diretamente
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Patrimonio;
use App\Services\FilterService;
use Illuminate\Http\Request;

// Criar requisição corretamente
$request = Request::create('http://localhost:8000/api/patrimonios/pesquisar?q=100', 'GET');

echo "Termo buscado: '" . $request->input('q') . "'\n";
echo "Tipo: " . gettype($request->input('q')) . "\n";

// Carregar todos os patrimônios
$patrimonios = Patrimonio::select(['NUSEQPATR', 'NUPATRIMONIO', 'DEPATRIMONIO', 'SITUACAO'])
    ->get()
    ->toArray();

echo "Total de patrimônios carregados: " . count($patrimonios) . "\n";

// Filtrar
$termo = trim((string) $request->input('q', ''));
echo "Termo após trim e cast: '" . $termo . "'\n";

$filtrados = FilterService::filtrar(
    $patrimonios,
    $termo,
    ['NUPATRIMONIO', 'DEPATRIMONIO'],
    ['NUPATRIMONIO' => 'número', 'DEPATRIMONIO' => 'texto'],
    10
);

echo "Resultados da busca: " . count($filtrados) . "\n";
if (count($filtrados) > 0) {
    echo json_encode(array_slice($filtrados, 0, 3), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}

// Agora testar via controller
echo "\n=== Testando via Controller ===\n";

use App\Http\Controllers\PatrimonioController;

$controller = new PatrimonioController();
$response = $controller->pesquisar($request);

echo "Response: " . $response->getContent() . "\n";
