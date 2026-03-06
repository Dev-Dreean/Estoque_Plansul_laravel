<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona o campo FLTERMORESPONSABILIDADE à tabela patr.
     * Indica se o Termo de Responsabilidade foi enviado para o responsável pelo bem.
     * Valores: 'S' = Sim (enviado), 'N' = Não (pendente)
     */
    public function up(): void
    {
        Schema::table('patr', function (Blueprint $table) {
            $table->char('FLTERMORESPONSABILIDADE', 1)
                ->nullable()
                ->default('N')
                ->comment('Indica se o Termo de Responsabilidade foi enviado: S=Sim, N=Não')
                ->after('TAMANHO');
        });
    }

    public function down(): void
    {
        Schema::table('patr', function (Blueprint $table) {
            $table->dropColumn('FLTERMORESPONSABILIDADE');
        });
    }
};
