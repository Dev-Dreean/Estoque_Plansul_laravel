<?php
// Migration: add must_change_password to usuario table

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            if (!Schema::hasColumn('usuario', 'must_change_password')) {
                $table->boolean('must_change_password')->default(true)->after('UF');
            }
        });
    }

    public function down(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            if (Schema::hasColumn('usuario', 'must_change_password')) {
                $table->dropColumn('must_change_password');
            }
        });
    }
};
