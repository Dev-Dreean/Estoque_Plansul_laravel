<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if ($this->tableExists('locais_projeto')) {
            Schema::table('locais_projeto', function (Blueprint $table) {
                if (!$this->columnExists('locais_projeto', 'tipo_local')) {
                    $table->string('tipo_local', 30)->default('PADRAO')->after('delocal');
                }

                if (!$this->columnExists('locais_projeto', 'fluxo_responsavel')) {
                    $table->string('fluxo_responsavel', 20)->default('PADRAO')->after('tipo_local');
                }
            });
        }

        if ($this->tableExists('solicitacoes_bens')) {
            Schema::table('solicitacoes_bens', function (Blueprint $table) {
                if (!$this->columnExists('solicitacoes_bens', 'local_projeto_id')) {
                    $table->unsignedBigInteger('local_projeto_id')->nullable()->after('projeto_id');
                }

                if (!$this->columnExists('solicitacoes_bens', 'fluxo_responsavel')) {
                    $table->string('fluxo_responsavel', 20)->nullable()->after('local_destino');
                }
            });
        }

        if ($this->tableExists('solicitacoes_bens') && $this->columnExists('solicitacoes_bens', 'fluxo_responsavel')) {
            DB::table('solicitacoes_bens')
                ->where(function ($query) {
                    $query->whereNull('fluxo_responsavel')
                        ->orWhereRaw("TRIM(fluxo_responsavel) = ''");
                })
                ->update([
                    'fluxo_responsavel' => 'PADRAO',
                ]);
        }

        if ($this->tableExists('locais_projeto')) {
            if ($this->columnExists('locais_projeto', 'tipo_local') && $this->columnExists('locais_projeto', 'fluxo_responsavel')) {
                DB::table('locais_projeto')
                    ->whereRaw("UPPER(TRIM(delocal)) = 'ESTOQUE TI'")
                    ->update([
                        'tipo_local' => 'ESTOQUE_TI',
                        'fluxo_responsavel' => 'TI',
                    ]);
            }

            if ($this->columnExists('locais_projeto', 'tipo_local')) {
                DB::table('locais_projeto')
                    ->whereRaw("UPPER(TRIM(delocal)) = 'TI'")
                    ->where(function ($query) {
                        $query->whereNull('tipo_local')
                            ->orWhereRaw("TRIM(tipo_local) = ''")
                            ->orWhere('tipo_local', 'PADRAO');
                    })
                    ->update([
                        'tipo_local' => 'TI_EM_USO',
                    ]);
            }
        }
    }

    public function down(): void
    {
        if ($this->tableExists('solicitacoes_bens')) {
            Schema::table('solicitacoes_bens', function (Blueprint $table) {
                if ($this->columnExists('solicitacoes_bens', 'fluxo_responsavel')) {
                    $table->dropColumn('fluxo_responsavel');
                }

                if ($this->columnExists('solicitacoes_bens', 'local_projeto_id')) {
                    $table->dropColumn('local_projeto_id');
                }
            });
        }

        if ($this->tableExists('locais_projeto')) {
            Schema::table('locais_projeto', function (Blueprint $table) {
                if ($this->columnExists('locais_projeto', 'fluxo_responsavel')) {
                    $table->dropColumn('fluxo_responsavel');
                }

                if ($this->columnExists('locais_projeto', 'tipo_local')) {
                    $table->dropColumn('tipo_local');
                }
            });
        }
    }

    private function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }

    private function columnExists(string $table, string $column): bool
    {
        $quotedColumn = DB::getPdo()->quote($column);
        $result = DB::select("SHOW COLUMNS FROM `{$table}` LIKE {$quotedColumn}");

        return $result !== [];
    }
};
