<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class SearchCacheService
{
    public const CACHE_PROJETOS = 'search_projetos';
    public const CACHE_CODIGOS = 'search_codigos';
    public const CACHE_PATRIMONIO = 'search_patrimonio';
    public const CACHE_TTL = 3600; // 1 hora

    /**
     * Obter todos os projetos com cache
     */
    public static function getProjetos(bool $refresh = false): array
    {
        if ($refresh) {
            Cache::forget(self::CACHE_PROJETOS);
        }

        return Cache::remember(self::CACHE_PROJETOS, self::CACHE_TTL, function () {
            return \App\Models\Tabfant::select(['CDPROJETO', 'NOMEPROJETO'])
                ->where('CDPROJETO', '!=', 0)
                ->orderByRaw('CAST(CDPROJETO AS UNSIGNED) ASC')
                ->get()
                ->toArray();
        });
    }

    /**
     * Obter todos os códigos com cache
     */
    public static function getCodigos(bool $refresh = false): array
    {
        if ($refresh) {
            Cache::forget(self::CACHE_CODIGOS);
        }

        return Cache::remember(self::CACHE_CODIGOS, self::CACHE_TTL, function () {
            return \App\Models\ObjetoPatr::select(['NUSEQOBJETO as CODOBJETO', 'DEOBJETO as DESCRICAO'])
                ->get()
                ->toArray();
        });
    }

    /**
     * Obter todos os patrimônios com cache
     */
    public static function getPatrimonios(bool $refresh = false): array
    {
        if ($refresh) {
            Cache::forget(self::CACHE_PATRIMONIO);
        }

        return Cache::remember(self::CACHE_PATRIMONIO, self::CACHE_TTL, function () {
            return \App\Models\Patrimonio::select(['NUSEQPATR', 'NUPATRIMONIO', 'DEPATRIMONIO', 'SITUACAO'])
                ->get()
                ->toArray();
        });
    }

    /**
     * Gerar chave de cache para códigos por termo
     */
    public static function codigosKey(string $termo): string
    {
        return 'search_codigos_' . md5($termo);
    }

    /**
     * Armazenar resultado em cache com callback
     */
    public static function remember(string $key, callable $callback): mixed
    {
        return Cache::remember($key, self::CACHE_TTL, $callback);
    }

    /**
     * Invalidar cache de projetos
     */
    public static function invalidateProjetos(): void
    {
        Cache::forget(self::CACHE_PROJETOS);
    }

    /**
     * Invalidar cache de códigos
     */
    public static function invalidateCodigos(): void
    {
        Cache::forget(self::CACHE_CODIGOS);
    }

    /**
     * Invalidar cache de patrimônio
     */
    public static function invalidatePatrimonio(): void
    {
        Cache::forget(self::CACHE_PATRIMONIO);
    }

    /**
     * Invalidar todos os caches
     */
    public static function invalidateAll(): void
    {
        self::invalidateProjetos();
        self::invalidateCodigos();
        self::invalidatePatrimonio();
    }

    /**
     * Filtra projetos por magnitude numérica
     * Se digitar 8: retorna 8, 80-89, 800-899, 8000-8999
     * Se digitar 80: retorna 80-89, 800-899, 8000-8999
     * Se digitar 800: retorna 800-899, 8000-8999
     */
    public static function filtrarPorMagnitude(array $projetos, string $termo, string $campoNumerico = 'CDPROJETO'): array
    {
        if (empty($termo) || !is_numeric($termo)) {
            return $projetos;
        }

        $termo_len = strlen($termo);
        $termo_num = (int)$termo;
        $resultados = [];

        foreach ($projetos as $projeto) {
            $codigo = (int)$projeto[$campoNumerico];
            $codigo_str = (string)$codigo;

            // Verificar se começa com o termo
            if (strpos($codigo_str, $termo) === 0) {
                $resultados[] = $projeto;
                continue;
            }

            // Verificar magnitudes (décimos, centenas, milhares)
            // Décimos: 8 -> 80-89
            if ($termo_len === 1) {
                $min = $termo_num * 10;
                $max = $min + 9;
                if ($codigo >= $min && $codigo <= $max) {
                    $resultados[] = $projeto;
                    continue;
                }

                // Centenas: 8 -> 800-899
                $min = $termo_num * 100;
                $max = $min + 99;
                if ($codigo >= $min && $codigo <= $max) {
                    $resultados[] = $projeto;
                    continue;
                }

                // Milhares: 8 -> 8000-8999
                $min = $termo_num * 1000;
                $max = $min + 999;
                if ($codigo >= $min && $codigo <= $max) {
                    $resultados[] = $projeto;
                }
            }
            // Dezenas: 80 -> 800-899, 8000-8999
            else if ($termo_len === 2) {
                // Centenas: 80 -> 800-899
                $min = $termo_num * 10;
                $max = $min + 9;
                if ($codigo >= $min && $codigo <= $max) {
                    $resultados[] = $projeto;
                    continue;
                }

                // Milhares: 80 -> 8000-8999
                $min = $termo_num * 100;
                $max = $min + 99;
                if ($codigo >= $min && $codigo <= $max) {
                    $resultados[] = $projeto;
                }
            }
            // Centenas: 800 -> 8000-8999
            else if ($termo_len === 3) {
                $min = $termo_num * 10;
                $max = $min + 9;
                if ($codigo >= $min && $codigo <= $max) {
                    $resultados[] = $projeto;
                }
            }
        }

        return $resultados;
    }
}

