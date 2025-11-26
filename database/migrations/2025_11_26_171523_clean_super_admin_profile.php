<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Converter qualquer usuário com PERFIL='SUP' para 'ADM'
        // Pois o perfil SUPER foi removido completamente do sistema
        DB::table('usuario')
            ->where('PERFIL', 'SUP')
            ->update(['PERFIL' => 'ADM']);

        // Remover registros de acesso que referenciem 'SUP' em NIVEL_VISIBILIDADE
        DB::table('acessotela')
            ->where('NIVEL_VISIBILIDADE', 'SUP')
            ->update(['NIVEL_VISIBILIDADE' => 'TODOS']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Não reverter - conversão de dados é permanente
    }
};
