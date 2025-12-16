<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Se existir uma criação parcial anterior, remover para garantir migração limpa
        Schema::dropIfExists('registros_removidos');

        Schema::create('registros_removidos', function (Blueprint $table) {
            $table->id();

            $table->string('entity', 50);
            // reduzir comprimento para evitar problemas de key-length em MySQL antigos
            $table->string('model_type', 150);
            $table->string('model_id', 50);
            $table->string('model_label', 255)->nullable();

            $table->string('deleted_by', 191)->nullable();
            $table->string('deleted_by_matricula', 50)->nullable();
            $table->timestamp('deleted_at')->useCurrent();

            $table->string('request_path', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->longText('payload');

            $table->timestamps();

            $table->index(['entity']);
            $table->index(['model_type', 'model_id']);
            $table->index(['deleted_at']);
            $table->index(['deleted_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_removidos');
    }
};

