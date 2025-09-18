<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adiciona as colunas 'created_at' e 'updated_at' na tabela.
     */
    public function up(): void
    {
        Schema::table('tabfant', function (Blueprint $table) {
            $table->timestamps(); // Este comando adiciona as duas colunas
        });
    }

    /**
     * Reverse the migrations.
     * Remove as colunas, caso precise reverter.
     */
    public function down(): void
    {
        Schema::table('tabfant', function (Blueprint $table) {
            $table->dropTimestamps(); // Este comando remove as duas colunas
        });
    }
};
