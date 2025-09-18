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
            $table->string('NUPATR');
            $table->string('CODPROJ')->nullable();
            $table->string('USUARIO');
            $table->timestamp('DTOPERACAO');
            $table->index(['NUPATR']);
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
