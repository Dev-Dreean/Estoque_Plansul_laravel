<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('solicitacoes_bens')) {
            return;
        }

        Schema::table('solicitacoes_bens', function (Blueprint $table) {
            $indexes = $this->listIndexes();

            if (!in_array('sb_status_updated_at_index', $indexes, true)) {
                $table->index(['status', 'updated_at'], 'sb_status_updated_at_index');
            }

            if (!in_array('sb_solicitante_id_status_index', $indexes, true)) {
                $table->index(['solicitante_id', 'status'], 'sb_solicitante_id_status_index');
            }

            if (!in_array('sb_solicitante_matricula_status_index', $indexes, true)) {
                $table->index(['solicitante_matricula', 'status'], 'sb_solicitante_matricula_status_index');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('solicitacoes_bens')) {
            return;
        }

        Schema::table('solicitacoes_bens', function (Blueprint $table) {
            $indexes = $this->listIndexes();

            if (in_array('sb_status_updated_at_index', $indexes, true)) {
                $table->dropIndex('sb_status_updated_at_index');
            }

            if (in_array('sb_solicitante_id_status_index', $indexes, true)) {
                $table->dropIndex('sb_solicitante_id_status_index');
            }

            if (in_array('sb_solicitante_matricula_status_index', $indexes, true)) {
                $table->dropIndex('sb_solicitante_matricula_status_index');
            }
        });
    }

    private function listIndexes(): array
    {
        return collect(DB::select('SHOW INDEX FROM `solicitacoes_bens`'))
            ->pluck('Key_name')
            ->map(static fn ($name) => strtolower((string) $name))
            ->unique()
            ->values()
            ->all();
    }
};
