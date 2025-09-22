<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funcionarios', function (Blueprint $table) {
            // A matrícula será a chave primária
            $table->string('CDMATRFUNCIONARIO', 25)->primary();
            $table->string('NMFUNCIONARIO', 100);
            $table->date('DTADMISSAO')->nullable();
            $table->string('CDCARGO', 50)->nullable();
            $table->string('CODFIL', 10)->nullable();
            $table->string('UFPROJ', 2)->nullable();
            // Não usamos timestamps do Laravel aqui para manter simples
            // public $timestamps = false; será definido no Model
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('funcionarios');
    }
};
