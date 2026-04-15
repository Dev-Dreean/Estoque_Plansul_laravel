<?php
/**
 * one-off: Configure collaboration management access for Tiago, Beatriz, Bruno
 * Adds tela 1011 (Colaboradores) access to the 3 managers
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$gestores = ['TIAGO', 'BEATRIZ', 'BRUNO'];

echo "Procurando gestores no banco...\n";

foreach ($gestores as $nome) {
    $funcionario = DB::table('funcionarios')
        ->select('CDMATRFUNCIONARIO', 'NMFUNCIONARIO')
        ->whereRaw("UPPER(NMFUNCIONARIO) LIKE ?", ['%' . strtoupper($nome) . '%'])
        ->first();
    
    if ($funcionario) {
        echo "✓ {$nome}: matricula {$funcionario->CDMATRFUNCIONARIO} ({$funcionario->NMFUNCIONARIO})\n";
        
        // Verify if user exists
        $usuario = DB::table('usuario')
            ->where('CDMATRFUNCIONARIO', $funcionario->CDMATRFUNCIONARIO)
            ->first();
        
        if ($usuario) {
            // Grant access to tela 1011 (Colaboradores)
            DB::table('acessousuario')->updateOrInsert(
                [
                    'CDMATRFUNCIONARIO' => $funcionario->CDMATRFUNCIONARIO,
                    'NUSEQTELA' => 1011,
                ],
                [
                    'INACESSO' => 'S',
                ]
            );
            echo "  → Acesso à tela 1011 garantido\n";
        } else {
            echo "  ⚠ Usuário não existe no sistema\n";
        }
    } else {
        echo "✗ {$nome}: não encontrado\n";
    }
}

echo "\nConcluído!\n";
