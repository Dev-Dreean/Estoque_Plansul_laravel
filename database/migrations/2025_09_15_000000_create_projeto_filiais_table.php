<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('projeto_filiais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tabfant_id')->constrained('tabfant')->cascadeOnDelete();
            $table->string('NOMEFILIAL');
            $table->timestamps();
            $table->index('tabfant_id');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('projeto_filiais');
    }
};
