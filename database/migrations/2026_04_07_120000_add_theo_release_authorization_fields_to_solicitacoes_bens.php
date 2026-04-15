<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!$this->columnExists('solicitacoes_bens', 'release_authorized_by_id')) {
            DB::statement('ALTER TABLE solicitacoes_bens ADD COLUMN release_authorized_by_id BIGINT UNSIGNED NULL AFTER quote_registered_at');
        }

        if (!$this->columnExists('solicitacoes_bens', 'release_authorized_at')) {
            DB::statement('ALTER TABLE solicitacoes_bens ADD COLUMN release_authorized_at TIMESTAMP NULL AFTER release_authorized_by_id');
        }
    }

    public function down(): void
    {
        if ($this->columnExists('solicitacoes_bens', 'release_authorized_at')) {
            DB::statement('ALTER TABLE solicitacoes_bens DROP COLUMN release_authorized_at');
        }

        if ($this->columnExists('solicitacoes_bens', 'release_authorized_by_id')) {
            DB::statement('ALTER TABLE solicitacoes_bens DROP COLUMN release_authorized_by_id');
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.columns')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->exists();
    }
};