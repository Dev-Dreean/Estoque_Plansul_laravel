<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locais_projeto', function (Blueprint $table) {
            $table->id();
            $table->integer('cdlocal')->nullable();
            $table->string('delocal')->nullable();
            $table->boolean('flativo')->nullable();

            // Coluna para a chave estrangeira que liga ao projeto
            $table->foreignId('tabfant_id')
                ->nullable()
                ->constrained('tabfant') // Nome da tabela de projetos
                ->onDelete('set null'); // Se um projeto for deletado, este campo fica nulo

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locais_projeto');
    }
};
