<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('novidades_sistema_visualizacoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('usuario_id');
            $table->string('novidade_key', 120);
            $table->timestamp('visualizado_em');
            $table->timestamps();

            $table->unique(['usuario_id', 'novidade_key'], 'novidades_usuario_key_unique');
            $table->index(['novidade_key'], 'novidades_key_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('novidades_sistema_visualizacoes');
    }
};
