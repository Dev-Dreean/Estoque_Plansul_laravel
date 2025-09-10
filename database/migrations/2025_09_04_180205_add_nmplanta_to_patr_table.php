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
        Schema::table('patr', function (Blueprint $table) {
            // Adiciona a nova coluna para o Código do Termo
            // Será do tipo INTEGER, pode ser nulo, e adicionado após a coluna CDPROJETO
            $table->integer('NMPLANTA')->nullable()->after('CDPROJETO');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patr', function (Blueprint $table) {
            // Remove a coluna caso precisemos reverter a migration
            $table->dropColumn('NMPLANTA');
        });
    }
};