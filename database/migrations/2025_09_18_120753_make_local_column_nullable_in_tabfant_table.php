<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Altera a coluna 'LOCAL' para permitir valores nulos.
     */
    public function up(): void
    {
        Schema::table('tabfant', function (Blueprint $table) {
            // O método change() modifica uma coluna existente
            $table->string('LOCAL')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     * Reverte a coluna para não permitir mais valores nulos.
     */
    public function down(): void
    {
        Schema::table('tabfant', function (Blueprint $table) {
            $table->string('LOCAL')->nullable(false)->change();
        });
    }
};
