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
        // Apenas executa a adição da coluna, sem a verificação que causa o erro.
        Schema::table('usuario', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false)->after('UF');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            $table->dropColumn('must_change_password');
        });
    }
};
