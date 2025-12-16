<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('usuario', 'supervisor_de')) {
            Schema::table('usuario', function (Blueprint $table) {
                $table->dropColumn('supervisor_de');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('usuario', 'supervisor_de')) {
            Schema::table('usuario', function (Blueprint $table) {
                $table->longText('supervisor_de')->nullable()->after('CDMATRFUNCIONARIO');
            });
        }
    }
};
