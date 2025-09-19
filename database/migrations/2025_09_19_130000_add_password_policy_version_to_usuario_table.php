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
        // Adiciona a coluna diretamente, sem a verificação que causa o erro.
        Schema::table('usuario', function (Blueprint $table) {
            $table->unsignedInteger('password_policy_version')->default(0)->after('must_change_password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            $table->dropColumn('password_policy_version');
        });
    }
};
