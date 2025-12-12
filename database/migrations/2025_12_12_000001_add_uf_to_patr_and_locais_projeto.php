<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Adicionar coluna UF na tabela patr (patrimonios) - SQL RAW para compatibilidade
        try {
            DB::statement('ALTER TABLE patr ADD COLUMN UF VARCHAR(2) NULL COMMENT "Unidade Federativa (Estado)" AFTER CDPROJETO');
        } catch (\Exception $e) {
            // Coluna já existe, ignorar
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                throw $e;
            }
        }

        // Adicionar coluna UF na tabela locais_projeto - SQL RAW para compatibilidade
        try {
            DB::statement('ALTER TABLE locais_projeto ADD COLUMN UF VARCHAR(2) NULL COMMENT "Unidade Federativa (Estado)" AFTER tabfant_id');
        } catch (\Exception $e) {
            // Coluna já existe, ignorar
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                throw $e;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE patr DROP COLUMN IF EXISTS UF');
        } catch (\Exception $e) {
            // Coluna não existe, ignorar
        }

        try {
            DB::statement('ALTER TABLE locais_projeto DROP COLUMN IF EXISTS UF');
        } catch (\Exception $e) {
            // Coluna não existe, ignorar
        }
    }
};
