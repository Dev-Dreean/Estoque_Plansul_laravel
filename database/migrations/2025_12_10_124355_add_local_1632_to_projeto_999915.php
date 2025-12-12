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
     * Adiciona o local 1632 (ALMOXARIFADO CENTRAL) ao projeto 999915.
     * 
     * Contexto:
     * - O local 1632 já existe no projeto 736 (SERRO)
     * - Agora também precisa existir no projeto 999915 (ALMOXARIFADO CENTRAL)
     * - Ambos devem coexistir: mesmo cdlocal, projetos diferentes
     */
    public function up(): void
    {
        // Verificar se já existe o local 1632 no projeto 999915
        $existe = DB::table('locais_projeto')
            ->where('cdlocal', 1632)
            ->where('tabfant_id', 999915)
            ->exists();

        if (!$existe) {
            DB::table('locais_projeto')->insert([
                'cdlocal' => 1632,
                'codigo_projeto' => null,
                'delocal' => 'ALMOXARIFADO CENTRAL',
                'flativo' => 1,
                'tabfant_id' => 999915,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover apenas o local 1632 do projeto 999915
        DB::table('locais_projeto')
            ->where('cdlocal', 1632)
            ->where('tabfant_id', 999915)
            ->delete();
    }
};
