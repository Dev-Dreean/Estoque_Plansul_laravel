<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\TermoCodigo;
use App\Models\Patrimonio;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

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
                return [(int)$reusable->codigo, true];
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
                        'created_by' => Auth::user()->NMLOGIN ?? 'SISTEMA'
                    ]);
                    return [(int)$registro->codigo, false];
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
            $jaUsado = Patrimonio::where('NMPLANTA', $numero)->exists();
            if ($jaUsado) {
                return ['updated' => [], 'already_used' => true, 'code' => $numero];
            }

            TermoCodigo::firstOrCreate([
                'codigo' => $numero
            ], [
                'created_by' => Auth::user()->NMLOGIN ?? 'SISTEMA'
            ]);

            $idsLimpos = collect($ids)->filter()->unique()->values();
            if ($idsLimpos->isEmpty()) {
                return ['updated' => [], 'already_used' => false, 'code' => $numero];
            }

            Patrimonio::whereIn('NUSEQPATR', $idsLimpos)
                ->whereNull('NMPLANTA')
                ->update(['NMPLANTA' => $numero]);

            $atualizados = Patrimonio::whereIn('NUSEQPATR', $idsLimpos)
                ->where('NMPLANTA', $numero)
                ->pluck('NUSEQPATR')
                ->all();

            return ['updated' => $atualizados, 'already_used' => false, 'code' => $numero];
        });
    }
}
