<?php

namespace App\Console\Commands;

use App\Services\SearchCacheService;
use Illuminate\Console\Command;

class ClearSearchCache extends Command
{
    protected $signature = 'cache:clear-search {--all : Limpar todos os caches}';
    protected $description = 'Limpa o cache de buscas do sistema';

    public function handle()
    {
        if ($this->option('all')) {
            SearchCacheService::invalidateProjetos();
            SearchCacheService::invalidateCodigos();
            SearchCacheService::invalidatePatrimonio();
            $this->info('✓ Todos os caches de busca foram limpos!');
        } else {
            SearchCacheService::invalidateProjetos();
            SearchCacheService::invalidateCodigos();
            $this->info('✓ Cache de projetos e códigos limpo!');
        }
    }
}
