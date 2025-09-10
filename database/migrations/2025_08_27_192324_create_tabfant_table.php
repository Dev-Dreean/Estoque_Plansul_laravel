<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tabfant', function (Blueprint $table) {
            $table->id();
            $table->integer('CDPROJETO');
            $table->string('NOMEPROJETO');
            $table->string('LOCAL');
            // Adicionamos um índice para otimizar as buscas por projeto
            $table->index('CDPROJETO');
        });
    }
    public function down(): void {
        Schema::dropIfExists('tabfant');
    }
};