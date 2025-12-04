<?php
/**
 * Script para verificar problema com CDLOCAL do patrimônio 17546
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Patrimonio;
use App\Models\LocalProjeto;
use Illuminate\Support\Facades\DB;

echo "🔍 VERIFICANDO PATRIMÔNIO 17546\n";
echo "════════════════════════════════════════════════════════════\n\n";

// Buscar no banco
$p = Patrimonio::where('NUPATRIMONIO', 17546)->first();

if ($p) {
    echo "📋 DADOS NO BANCO DE DADOS:\n";
    echo "NUPATRIMONIO: {$p->NUPATRIMONIO}\n";
    echo "CDLOCAL: {$p->CDLOCAL}\n";
    echo "CDPROJETO: {$p->CDPROJETO}\n";
    echo "DEPATRIMONIO: {$p->DEPATRIMONIO}\n";
    echo "SITUACAO: {$p->SITUACAO}\n";
    
    if ($p->CDLOCAL) {
        $local = LocalProjeto::find($p->CDLOCAL);
        if ($local) {
            echo "\n📍 LOCAL ASSOCIADO (ID {$p->CDLOCAL}):\n";
            echo "cdlocal: {$local->cdlocal}\n";
            echo "delocal: {$local->delocal}\n";
            echo "tabfant_id: {$local->tabfant_id}\n";
        } else {
            echo "\n⚠️ Local ID {$p->CDLOCAL} não encontrado!\n";
        }
    }
} else {
    echo "❌ Patrimônio 17546 não encontrado no banco\n";
}

echo "\n" . str_repeat("─", 60) . "\n\n";
echo "📄 DADOS NO ARQUIVO TXT:\n";
echo "Linha do arquivo: 17546	BAIXA		1	BASE DE METAL TAMPO DE MADEIRA		<null>		133838	100001	BRUNO	2025-12-02	<null>	<null>	S\n\n";
echo "Estrutura esperada:\n";
echo "NUPATRIMONIO | SITUACAO | MARCA | CDLOCAL | MODELO | COR | DTAQUISICAO | DEHISTORICO | CDMATRFUNCIONARIO | CDPROJETO | USUARIO | DTOPERACAO | NUDOCFISCAL | NUMOF | FLVISIVEL\n\n";

$parts = explode("\t", "17546	BAIXA		1	BASE DE METAL TAMPO DE MADEIRA		<null>		133838	100001	BRUNO	2025-12-02	<null>	<null>	S");
echo "Parsing da linha:\n";
echo "[0] NUPATRIMONIO: {$parts[0]}\n";
echo "[1] SITUACAO: {$parts[1]}\n";
echo "[2] MARCA: {$parts[2]}\n";
echo "[3] CDLOCAL: {$parts[3]}\n";
echo "[4] MODELO: {$parts[4]}\n";
echo "[5] COR: {$parts[5]}\n";
echo "[6] DTAQUISICAO: {$parts[6]}\n";
echo "[7] DEHISTORICO: {$parts[7]}\n";
echo "[8] CDMATRFUNCIONARIO: {$parts[8]}\n";
echo "[9] CDPROJETO: {$parts[9]}\n";
echo "[10] USUARIO: {$parts[10]}\n";
echo "[11] DTOPERACAO: {$parts[11]}\n";
echo "[12] NUDOCFISCAL: " . ($parts[12] ?? 'não existe') . "\n";
echo "[13] NUMOF: " . ($parts[13] ?? 'não existe') . "\n";
echo "[14] FLVISIVEL: " . ($parts[14] ?? 'não existe') . "\n";

echo "\n" . str_repeat("─", 60) . "\n\n";
echo "🔍 ANÁLISE:\n";
echo "• No arquivo TXT: CDLOCAL=1, CDPROJETO=100001\n";
echo "• Esperado: CDLOCAL=8, CDPROJETO=100001\n";
echo "• Problema: O CDLOCAL está vindo como 1 (incorreto) no arquivo\n\n";

// Verificar local correto
echo "🔎 VERIFICANDO SE EXISTE LOCAL COM cdlocal=8:\n";
$local8 = LocalProjeto::where('cdlocal', 8)->first();
if ($local8) {
    echo "✅ Local encontrado:\n";
    echo "   ID: {$local8->id}\n";
    echo "   cdlocal: {$local8->cdlocal}\n";
    echo "   delocal: {$local8->delocal}\n";
    echo "   tabfant_id: {$local8->tabfant_id}\n";
} else {
    echo "❌ Local com cdlocal=8 não encontrado!\n";
}

echo "\n" . str_repeat("═", 60) . "\n\n";
echo "💡 SOLUÇÃO:\n";
echo "1. Verificar o arquivo fonte original\n";
echo "2. Corrigir os dados no banco de dados com um script SQL\n";
echo "3. Criar regra de validação na importação futura\n";
