<?php

namespace App\Services;

use App\Models\Patrimonio;
use App\Models\TermoCodigo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CodigoService
{
    public function gerarOuReaproveitar(): array
    {
        return DB::transaction(function () {
            $reusable = TermoCodigo::query()
                ->whereNotIn('codigo', function ($q) {
                    $q->select('NMPLANTA')->from('patr')->whereNotNull('NMPLANTA');
                })
                ->orderBy('codigo', 'asc')
                ->lockForUpdate()
                ->first();

            if ($reusable) {
                return [(int) $reusable->codigo, true];
            }

            $tentativas = 0;
            do {
                $tentativas++;
                $maxRegistrado = (int) TermoCodigo::max('codigo');
                $maxUsado = (int) Patrimonio::max('NMPLANTA');
                $proximo = max($maxRegistrado, $maxUsado) + 1;

                try {
                    $registro = TermoCodigo::create([
                        'codigo' => $proximo,
                        'created_by' => Auth::user()->NMLOGIN ?? 'SISTEMA',
                    ]);

                    return [(int) $registro->codigo, false];
                } catch (\Throwable $e) {
                    Log::warning('Colisão ao criar código termo, tentando outro', ['erro' => $e->getMessage()]);
                    if ($tentativas > 5) {
                        throw $e;
                    }
                }
            } while (true);
        });
    }

    /**
     * Atribui código a uma lista de IDs de patrimônio retornando IDs realmente atualizados.
     */
    public function atribuirCodigo(int $codigo, array $ids): array
    {
        $numero = $codigo;

        return DB::transaction(function () use ($numero, $ids) {
            $this->garantirMetadadosTermo($numero);

            $idsLimpos = collect($ids)->filter()->unique()->values();
            if ($idsLimpos->isEmpty()) {
                return ['updated' => [], 'already_used' => false, 'code' => $numero];
            }

            $queryAtualizacao = Patrimonio::whereIn('NUSEQPATR', $idsLimpos);
            $this->aplicarFiltroPatrimoniosAtivosParaTermo($queryAtualizacao);

            $queryAtualizacao->update(['NMPLANTA' => $numero]);

            $atualizados = Patrimonio::whereIn('NUSEQPATR', $idsLimpos)
                ->where('NMPLANTA', $numero)
                ->pluck('NUSEQPATR')
                ->all();

            return ['updated' => $atualizados, 'already_used' => false, 'code' => $numero];
        });
    }

    private function garantirMetadadosTermo(int $codigo): void
    {
        try {
            $registro = TermoCodigo::firstOrCreate(
                ['codigo' => $codigo],
                ['created_by' => Auth::user()->NMLOGIN ?? 'SISTEMA']
            );

            if (blank($registro->created_by)) {
                $registro->forceFill([
                    'created_by' => Auth::user()->NMLOGIN ?? 'SISTEMA',
                ])->save();
            }
        } catch (\Throwable $e) {
            Log::warning('Não foi possível atualizar os metadados do termo durante a atribuição.', [
                'codigo' => $codigo,
                'erro' => $e->getMessage(),
            ]);
        }
    }

    private function aplicarFiltroPatrimoniosAtivosParaTermo($query): void
    {
        try {
            if (Schema::hasColumn('patr', 'CDSITUACAO')) {
                $query->where(function ($q) {
                    $q->whereNull('CDSITUACAO')
                        ->orWhere('CDSITUACAO', '<>', 2);
                });
            }
        } catch (\Exception $e) {
        }

        try {
            if (Schema::hasColumn('patr', 'SITUACAO')) {
                $query->where(function ($q) {
                    $q->whereNull('SITUACAO')
                        ->orWhereRaw("UPPER(TRIM(SITUACAO)) NOT LIKE '%BAIXA%'");
                });
            }
        } catch (\Exception $e) {
        }

        try {
            if (Schema::hasColumn('patr', 'DTBAIXA')) {
                $query->whereNull('DTBAIXA');
            }
        } catch (\Exception $e) {
        }
    }
}
