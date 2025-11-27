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
        Schema::table('usuario', function (Blueprint $table) {
            // Use longText for better compatibility (some MariaDB/MySQL versions lack JSON type)
            $table->longText('supervisor_de')->nullable()->after('CDMATRFUNCIONARIO')
                ->comment('Lista de logins (NMLOGIN) que este usuário pode supervisionar (armazenado como JSON)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            $table->dropColumn('supervisor_de');
        });
    }
};
