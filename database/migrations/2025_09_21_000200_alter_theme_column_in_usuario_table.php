<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Converter coluna JSON existente para VARCHAR(20) mantendo valor simples
        // Estratégia: adicionar coluna temporária, copiar, dropar antiga, renomear.
        if (Schema::hasColumn('usuario', 'theme')) {
            $col = DB::select("SHOW COLUMNS FROM usuario LIKE 'theme'");
            if ($col && str_contains(strtolower($col[0]->Type), 'json')) {
                Schema::table('usuario', function (Blueprint $table) {
                    $table->string('theme_tmp', 20)->nullable()->after('UF');
                });
                DB::statement("UPDATE usuario SET theme_tmp = JSON_UNQUOTE(theme) WHERE theme IS NOT NULL");
                Schema::table('usuario', function (Blueprint $table) {
                    $table->dropColumn('theme');
                });
                Schema::table('usuario', function (Blueprint $table) {
                    $table->string('theme', 20)->nullable()->after('UF');
                    $table->index('theme');
                });
                DB::statement("UPDATE usuario SET theme = theme_tmp WHERE theme_tmp IS NOT NULL");
                Schema::table('usuario', function (Blueprint $table) {
                    $table->dropColumn('theme_tmp');
                });
            } else {
                // Se já não for JSON, garantir tamanho e índice
                Schema::table('usuario', function (Blueprint $table) {
                    $table->string('theme', 20)->nullable()->change();
                });
            }
        } else {
            Schema::table('usuario', function (Blueprint $table) {
                $table->string('theme', 20)->nullable()->after('UF');
                $table->index('theme');
            });
        }
    }

    public function down(): void
    {
        // Reverter para JSON (não estritamente necessário, mas para simetria)
        if (Schema::hasColumn('usuario', 'theme')) {
            Schema::table('usuario', function (Blueprint $table) {
                $table->dropIndex(['theme']);
                $table->json('theme')->nullable()->change();
            });
        }
    }
};
