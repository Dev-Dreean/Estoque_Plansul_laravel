<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $temColuna = DB::selectOne("SHOW COLUMNS FROM patr LIKE 'CDMATRGERENTE'");

        if (!$temColuna) {
            DB::statement("
                ALTER TABLE patr
                ADD COLUMN CDMATRGERENTE BIGINT UNSIGNED NULL
                COMMENT 'Matricula do gerente responsavel pelo patrimonio'
                AFTER CDMATRFUNCIONARIO
            ");
        }
    }

    public function down(): void
    {
        $temColuna = DB::selectOne("SHOW COLUMNS FROM patr LIKE 'CDMATRGERENTE'");

        if ($temColuna) {
            DB::statement('ALTER TABLE patr DROP COLUMN CDMATRGERENTE');
        }
    }
};
