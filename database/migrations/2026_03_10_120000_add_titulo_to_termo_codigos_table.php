<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('termo_codigos', 'titulo')) {
            Schema::table('termo_codigos', function (Blueprint $table) {
                $table->string('titulo', 120)->nullable()->after('codigo');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('termo_codigos', 'titulo')) {
            Schema::table('termo_codigos', function (Blueprint $table) {
                $table->dropColumn('titulo');
            });
        }
    }
};
