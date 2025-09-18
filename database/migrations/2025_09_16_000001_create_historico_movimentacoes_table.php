<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('historico_movimentacoes', function (Blueprint $table) {
            $table->id();

            // Colunas principais
            $table->string('NUPATR');
            $table->string('CODPROJ')->nullable();
            $table->timestamp('DTOPERACAO');

            // Colunas de detalhe da movimentação (consolidadas)
            $table->string('TIPO')->comment('Tipo de evento: projeto, situacao, termo');
            $table->string('CAMPO')->comment('Campo que foi alterado');
            $table->string('VALOR_ANTIGO')->nullable();
            $table->string('VALOR_NOVO')->nullable();

            // Colunas de autoria (consolidadas)
            $table->string('USUARIO')->collation('utf8mb4_unicode_ci');
            $table->string('CO_AUTOR')->nullable()->collation('utf8mb4_unicode_ci');

            // Índices
            $table->index('NUPATR');
            $table->index('TIPO');
            $table->index('DTOPERACAO');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historico_movimentacoes');
    }
};
