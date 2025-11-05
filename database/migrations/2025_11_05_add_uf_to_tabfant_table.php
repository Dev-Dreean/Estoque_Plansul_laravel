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
        Schema::table('tabfant', function (Blueprint $table) {
            // Adiciona coluna UF (máximo 2 caracteres para siglas de estados)
            $table->string('UF', 2)->nullable()->after('LOCAL')->comment('Unidade Federativa (Estado)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tabfant', function (Blueprint $table) {
            $table->dropColumn('UF');
        });
    }
};
