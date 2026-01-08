<?php
use Illuminate\Support\Facades\DB;
// one-off: Marcar patrimônios como verificados e mover para Copa 1 - Projeto 8 SEDE

$patrimonios = [
    22506, 22486, 22487, 22488, 22489, 22490, 22491, 22492, 22493, 22494, 
    22495, 22496, 22497, 22498, 22499, 22500, 22501, 22502, 22503, 22504, 
    22505, 5555, 383, 384, 397, 22443, 14102, 14097, 14100, 36138
];

echo "=== Atualização de Patrimônios ===\n";
echo "Total de patrimônios: " . count($patrimonios) . "\n";
echo "Ações:\n";
echo "  - FLCONFERIDO = 'S' (verificado)\n";
echo "  - CDLOCAL = 1965 (Copa 1)\n";
echo "  - CDPROJETO = 8 (SEDE)\n";
echo "  - DTOPERACAO = " . date('Y-m-d H:i:s') . "\n\n";

// Atualizar em lotes
$updated = DB::table('patr')
    ->whereIn('NUPATRIMONIO', $patrimonios)
    ->update([
        'FLCONFERIDO' => 'S',
        'CDLOCAL' => 1965,
        'CDPROJETO' => 8,
        'DTOPERACAO' => now(),
    ]);

echo "✅ Atualizado no banco local: $updated registros\n";

// Listar os patrimônios atualizados
$atualizados = DB::table('patr')
    ->whereIn('NUPATRIMONIO', $patrimonios)
    ->select('NUPATRIMONIO', 'DEPATRIMONIO', 'FLCONFERIDO', 'CDLOCAL', 'CDPROJETO')
    ->get();

echo "\n=== Resultado da Atualização ===\n";
foreach ($atualizados as $p) {
    echo "#{$p->NUPATRIMONIO} - {$p->DEPATRIMONIO} | Conf: {$p->FLCONFERIDO} | Local: {$p->CDLOCAL} | Proj: {$p->CDPROJETO}\n";
}

echo "\n✅ Script concluído com sucesso!\n";
