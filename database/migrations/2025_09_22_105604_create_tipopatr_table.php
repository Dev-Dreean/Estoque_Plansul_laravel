<?php

declare(strict_types=1);

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
        // Cria a tabela TIPOPATR conforme seu arquivo
        Schema::create('TIPOPATR', function (Blueprint $table) {
            $table->integer('NUSEQTIPOPATR')->primary();
            $table->string('DETIPOPATR', 150)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('TIPOPATR');
    }
};
