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
        // Adiciona coluna UF em tabfant de forma compatível com versões antigas do MySQL
        // Evita usar Schema::hasColumn() porque em alguns servidores a consulta em
        // information_schema pode falhar (coluna generation_expression não disponível).
        if (Schema::hasTable('tabfant')) {
            try {
                // Tenta adicionar a coluna diretamente. Se já existir, a exceção será ignorada.
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE `tabfant` ADD COLUMN `UF` VARCHAR(2) NULL AFTER `LOCAL`");
            } catch (\Illuminate\Database\QueryException $e) {
                // Código 1060 = Duplicate column name
                $msg = $e->getMessage();
                if (stripos($msg, 'Duplicate column name') !== false || stripos($msg, 'duplicate column') !== false || strpos($msg, '1060') !== false) {
                    // coluna já existe, ignorar
                } else {
                    throw $e;
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove coluna UF
        if (Schema::hasTable('tabfant') && Schema::hasColumn('tabfant', 'UF')) {
            Schema::table('tabfant', function (Blueprint $table) {
                $table->dropColumn('UF');
            });
        }
    }
};
