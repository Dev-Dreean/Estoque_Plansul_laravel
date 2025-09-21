<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            if (!Schema::hasColumn('usuario', 'theme')) {
                $table->string('theme', 20)->nullable()->after('UF');
                $table->index('theme');
            }
        });
    }

    public function down(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            if (Schema::hasColumn('usuario', 'theme')) {
                $table->dropIndex(['theme']);
                $table->dropColumn('theme');
            }
        });
    }
};
