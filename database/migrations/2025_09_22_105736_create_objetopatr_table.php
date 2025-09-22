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
        // Cria a tabela OBJETOPATR conforme seu arquivo
        Schema::create('OBJETOPATR', function (Blueprint $table) {
            $table->integer('NUSEQOBJETO'); // Renomeado de NUSEOBJETO para consistência
            $table->integer('NUSEQTIPOPATR');
            $table->string('DEOBJETO', 150)->nullable();

            // Chave primária composta
            $table->primary(['NUSEQOBJETO', 'NUSEQTIPOPATR']);

            // Chave estrangeira (opcional, mas boa prática)
            // $table->foreign('NUSEQTIPOPATR')->references('NUSEQTIPOPATR')->on('TIPOPATR');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('OBJETOPATR');
    }
};
