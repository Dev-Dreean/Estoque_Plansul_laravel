<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adiciona campos PESO e TAMANHO à tabela patr para facilitar
     * cotações de transporte no almoxarifado.
     * 
     * Campos:
     * - PESO: decimal(10, 2) = peso em kg
     * - TAMANHO: varchar(100) = dimensões (ex: "100x50x30 cm" ou "M" para médio)
     */
    public function up(): void
    {
        Schema::table('patr', function (Blueprint $table) {
            // Adicionar após coluna NUSERIE, antes de CDLOCAL
            // PESO em kg (max 9999.99 kg)
            $table->decimal('PESO', 10, 2)->nullable()->comment('Peso em kg para cálculo de frete');
            
            // TAMANHO em texto livre (ex: "100x50x30 cm", "Grande", "M")
            $table->string('TAMANHO', 100)->nullable()->comment('Dimensões/Tamanho para cálculo de frete');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patr', function (Blueprint $table) {
            $table->dropColumn('PESO');
            $table->dropColumn('TAMANHO');
        });
    }
};
