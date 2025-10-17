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
     * Adiciona campo para controlar visibilidade da tela por nível de usuário
     * Valores possíveis:
     * - 'TODOS': Visível para todos os usuários (USR, ADM, SUP)
     * - 'ADM': Visível apenas para ADM e SUP (oculto de USR)
     * - 'SUP': Visível apenas para SUP (oculto de USR e ADM)
     */
    public function up(): void
    {
        Schema::table('acessotela', function (Blueprint $table) {
            $table->string('NIVEL_VISIBILIDADE', 10)
                ->default('TODOS')
                ->after('FLACESSO')
                ->comment('Controla quem pode ver esta tela: TODOS, ADM (só ADM/SUP), SUP (só SUP)');
        });

        // Atualizar telas administrativas existentes
        DB::table('acessotela')->where('NUSEQTELA', 1003)->update(['NIVEL_VISIBILIDADE' => 'ADM']); // Usuários
        DB::table('acessotela')->where('NUSEQTELA', 1004)->update(['NIVEL_VISIBILIDADE' => 'SUP']); // Cadastro de Telas
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('acessotela', function (Blueprint $table) {
            $table->dropColumn('NIVEL_VISIBILIDADE');
        });
    }
};
