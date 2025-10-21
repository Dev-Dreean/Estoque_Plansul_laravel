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
}
