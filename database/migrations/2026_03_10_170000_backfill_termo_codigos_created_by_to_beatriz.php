<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('termo_codigos') || !Schema::hasTable('patr')) {
            return;
        }

        DB::statement("
            INSERT INTO termo_codigos (codigo, created_by)
            SELECT DISTINCT p.NMPLANTA, 'beatriz.sc'
            FROM patr p
            LEFT JOIN termo_codigos t ON t.codigo = p.NMPLANTA
            WHERE p.NMPLANTA IS NOT NULL
              AND p.NMPLANTA <> ''
              AND t.codigo IS NULL
        ");

        DB::statement("
            UPDATE termo_codigos t
            INNER JOIN (
                SELECT DISTINCT NMPLANTA AS codigo
                FROM patr
                WHERE NMPLANTA IS NOT NULL
                  AND NMPLANTA <> ''
            ) p ON p.codigo = t.codigo
            SET t.created_by = 'beatriz.sc'
        ");
    }

    public function down(): void
    {
        // Migração de acerto histórico sem rollback automático.
    }
};
