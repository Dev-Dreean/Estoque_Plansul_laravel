<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_uf_to_usuario_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            // Adiciona a coluna UF após a coluna LGATIVO, por exemplo.
            // É nullable() para não quebrar usuários existentes.
            $table->string('UF', 2)->nullable()->after('LGATIVO');
        });
    }

    public function down(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            $table->dropColumn('UF');
        });
    }
};
