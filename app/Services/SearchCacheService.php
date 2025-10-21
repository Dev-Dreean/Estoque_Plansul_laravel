<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SearchCacheService
{
    // Cache keys
    const CACHE_PROJETOS = 'search:projetos:all';
    const CACHE_CODIGOS = 'search:codigos:all';
    const CACHE_LOCAIS_PREFIX = 'search:locais:projeto:';
    const CACHE_PATRIMONIO = 'search:patrimonio:all';
    
    // Cache TTL em minutos
    const CACHE_TTL = 60;

    /**
     * Busca e cacheia todos os projetos
     */
    public static function getProjetos($force = false): array
    {
        if (!$force && Cache::has(self::CACHE_PROJETOS)) {
            return Cache::get(self::CACHE_PROJETOS);
        }

        $projetos = DB::table('tabfant')
            ->select('CDPROJETO', 'NOMEPROJETO')
            ->where('CDPROJETO', '!=', 0)
            ->orderByRaw('CAST(CDPROJETO AS UNSIGNED) ASC')
            ->get()
            ->toArray();

        Cache::put(self::CACHE_PROJETOS, $projetos, now()->addMinutes(self::CACHE_TTL));
        return $projetos;
    }

    /**
     * Busca e cacheia todos os códigos
     */
    public static function getCodigos($force = false): array
    {
        if (!$force && Cache::has(self::CACHE_CODIGOS)) {
            return Cache::get(self::CACHE_CODIGOS);
        }

        $codigos = DB::table('objeto_patr')
            ->select('NUSEQOBJETO as CODOBJETO', 'DEOBJETO as DESCRICAO')
            ->orderBy('NUSEQOBJETO')
            ->get()
            ->toArray();

        Cache::put(self::CACHE_CODIGOS, $codigos, now()->addMinutes(self::CACHE_TTL));
        return $codigos;
    }

    /**
     * Busca e cacheia locais de um projeto
     */
    public static function getLocaisPorProjeto($tabfant_id, $force = false): array
    {
        $cacheKey = self::CACHE_LOCAIS_PREFIX . $tabfant_id;
        
        if (!$force && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $locais = DB::table('locais_projeto')
            ->where('tabfant_id', $tabfant_id)
            ->where('flativo', true)
            ->select('id', 'cdlocal', 'delocal')
            ->orderBy('delocal')
            ->get()
            ->toArray();

        Cache::put($cacheKey, $locais, now()->addMinutes(self::CACHE_TTL));
        return $locais;
    }

    /**
     * Busca e cacheia patrimônios
     */
    public static function getPatrimonios($force = false): array
    {
        if (!$force && Cache::has(self::CACHE_PATRIMONIO)) {
            return Cache::get(self::CACHE_PATRIMONIO);
        }

        $patrimonios = DB::table('patrimonio')
            ->select('NUSEQPATR', 'NUPATRIMONIO', 'DEPATRIMONIO', 'SITUACAO')
            ->get()
            ->toArray();

        Cache::put(self::CACHE_PATRIMONIO, $patrimonios, now()->addMinutes(self::CACHE_TTL));
        return $patrimonios;
    }

    /**
     * Invalida cache de projetos
     */
    public static function invalidateProjetos(): void
    {
        Cache::forget(self::CACHE_PROJETOS);
    }

    /**
     * Invalida cache de códigos
     */
    public static function invalidateCodigos(): void
    {
        Cache::forget(self::CACHE_CODIGOS);
    }

    /**
     * Invalida cache de um projeto específico
     */
    public static function invalidateLocaisProjeto($tabfant_id): void
    {
        Cache::forget(self::CACHE_LOCAIS_PREFIX . $tabfant_id);
    }

    /**
     * Invalida cache de patrimônios
     */
    public static function invalidatePatrimonio(): void
    {
        Cache::forget(self::CACHE_PATRIMONIO);
    }

    /**
     * Busca rápida em array usando índice
     */
    public static function filtrarRapido(array $dados, string $termo, array $campos): array
    {
        if (empty($termo)) {
            return array_slice($dados, 0, 30);
        }

        $termo_lower = strtolower($termo);
        $resultados = [];

        foreach ($dados as $item) {
            foreach ($campos as $campo) {
                if (isset($item->{$campo}) && stripos((string)$item->{$campo}, $termo_lower) === 0) {
                    $resultados[] = $item;
                    break;
                }
            }
        }

        return array_slice($resultados, 0, 30);
    }

    /**
     * Busca por magnitude em números
     */
    public static function filtrarPorMagnitude(array $dados, string $termo, string $campoNumero): array
    {
        if (!is_numeric($termo)) {
            return [];
        }

        $termo_len = strlen($termo);
        $termo_num = (int)$termo;
        $resultados = [];

        foreach ($dados as $item) {
            $codigo = (int)($item->{$campoNumero} ?? 0);
            $codigo_str = (string)$codigo;

            // Prefixo exato
            if (strpos($codigo_str, $termo) === 0) {
                $resultados[] = $item;
                continue;
            }

            // Magnitude checks
            if ($termo_len === 1) {
                $min = $termo_num * 10;
                $max = $min + 9;
                if ($codigo >= $min && $codigo <= $max) {
                    $resultados[] = $item;
                    continue;
                }

                $min = $termo_num * 100;
                $max = $min + 99;
                if ($codigo >= $min && $codigo <= $max) {
                    $resultados[] = $item;
                    continue;
                }

                $min = $termo_num * 1000;
                $max = $min + 999;
                if ($codigo >= $min && $codigo <= $max) {
                    $resultados[] = $item;
                }
            } else if ($termo_len === 2) {
                $min = $termo_num * 10;
                $max = $min + 9;
                if ($codigo >= $min && $codigo <= $max) {
                    $resultados[] = $item;
                    continue;
                }

                $min = $termo_num * 100;
                $max = $min + 99;
                if ($codigo >= $min && $codigo <= $max) {
                    $resultados[] = $item;
                }
            } else if ($termo_len === 3) {
                $min = $termo_num * 10;
                $max = $min + 9;
                if ($codigo >= $min && $codigo <= $max) {
                    $resultados[] = $item;
                }
            }
        }

        return array_slice($resultados, 0, 30);
    }
}
