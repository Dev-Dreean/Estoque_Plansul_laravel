<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            // role: 'admin' (padrão, editar/deletar), 'consulta' (read-only)
            $table->enum('role', ['admin', 'consulta'])->default('admin')->after('SENHA');
        });
    }

    public function down(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
