<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Services\SearchCacheService;

class WarmSearchCache
{
    public function handle(Request $request, Closure $next)
    {
        // Pré-carrega cache na primeira requisição se vazio
        if (!Cache::has(SearchCacheService::CACHE_PROJETOS)) {
            SearchCacheService::getProjetos(true);
        }

        if (!Cache::has(SearchCacheService::CACHE_CODIGOS)) {
            SearchCacheService::getCodigos(true);
        }

        return $next($request);
    }
}

