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
     * Remove duplicata do local 1642:
     * - Manter ID 1634 (original)
     * - Remover ID 1885 (criado por engano em migration anterior)
     * - Atualizar patrimônio 17466 para usar CDLOCAL 1634
     */
    public function up(): void
    {
        // Passo 1: Atualizar patrimônio 17466 para usar o local 1634
        DB::table('patr')
            ->where('NUPATRIMONIO', 17466)
            ->update(['CDLOCAL' => 1634]);

        // Passo 2: Remover local duplicado 1885
        DB::table('locais_projeto')
            ->where('id', 1885)
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverter é complexo, então não fazemos automático
    }
};
