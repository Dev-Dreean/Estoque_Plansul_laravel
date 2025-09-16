<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historico_movimentacoes', function (Blueprint $table) {
            $table->id();
            // Campos solicitados
            $table->integer('NUPATR'); // Número do patrimônio (NUPATRIMONIO)
            $table->integer('CODPROJ')->nullable(); // Código do projeto (CDPROJETO)
            $table->string('USUARIO', 100)->nullable(); // Login do usuário
            $table->timestamp('DTOPERACAO'); // Data/hora da operação

            // Índices úteis para busca
            $table->index(['NUPATR']);
            $table->index(['CODPROJ']);
            $table->index(['DTOPERACAO']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historico_movimentacoes');
    }
};
