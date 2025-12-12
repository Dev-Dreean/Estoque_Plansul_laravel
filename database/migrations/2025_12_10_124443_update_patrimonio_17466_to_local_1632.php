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
     * Atualiza o patrimônio 17466 para usar o local correto:
     * - CDPROJETO: 999915 (ALMOXARIFADO CENTRAL)
     * - CDLOCAL: 1632 (ALMOXARIFADO CENTRAL no projeto 999915)
     * 
     * Antes estava usando CDLOCAL 1642 (que foi criado por engano).
     */
    public function up(): void
    {
        // Buscar o ID do local 1632 no projeto 999915
        $local = DB::table('locais_projeto')
            ->where('cdlocal', 1632)
            ->where('tabfant_id', 999915)
            ->first();

        if ($local) {
            DB::table('patr')
                ->where('NUPATRIMONIO', 17466)
                ->update(['CDLOCAL' => $local->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverter para 1642 (estado anterior)
        $local = DB::table('locais_projeto')
            ->where('cdlocal', 1642)
            ->where('tabfant_id', 999915)
            ->first();

        if ($local) {
            DB::table('patr')
                ->where('NUPATRIMONIO', 17466)
                ->update(['CDLOCAL' => $local->id]);
        }
    }
};
