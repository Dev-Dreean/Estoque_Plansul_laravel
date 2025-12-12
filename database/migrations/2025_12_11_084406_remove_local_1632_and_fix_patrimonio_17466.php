<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * 1. Remove o local 1632 do projeto 999915 (foi criado por engano)
     * 2. Garante que o local 1642 está vinculado ao projeto 999915
     * 3. Atualiza o patrimônio 17466 para usar CDLOCAL 1642
     */
    public function up(): void
    {
        // Passo 1: Remover local 1632 do projeto 999915
        DB::table('locais_projeto')
            ->where('cdlocal', 1632)
            ->where('tabfant_id', 999915)
            ->delete();

        // Passo 2: Garantir que local 1642 está vinculado ao projeto 999915
        DB::table('locais_projeto')
            ->where('cdlocal', 1642)
            ->where('tabfant_id', 999915)
            ->update([
                'delocal' => 'ALMOXARIFADO CENTRAL',
                'flativo' => 1,
                'updated_at' => now(),
            ]);

        // Passo 3: Atualizar patrimônio 17466 para usar o local 1642 correto
        $local1642 = DB::table('locais_projeto')
            ->where('cdlocal', 1642)
            ->where('tabfant_id', 999915)
            ->first();

        if ($local1642) {
            DB::table('patr')
                ->where('NUPATRIMONIO', 17466)
                ->update([
                    'CDLOCAL' => $local1642->id,
                    'CDPROJETO' => 999915,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverter é complexo, então não fazemos automático
        // Deixar como está
    }
};
