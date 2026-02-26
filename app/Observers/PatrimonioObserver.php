<?php

/**
 * Observer para invalidar caches quando patrimônios são criados, atualizados ou excluídos.
 *
 * Caches invalidados:
 * - SearchCacheService (patrimônios, projetos, códigos)
 * - Dashboard (gráficos, top cadastradores, UF)
 *
 * Registrado em: AppServiceProvider::boot()
 */

namespace App\Observers;

use App\Models\Patrimonio;
use App\Services\SearchCacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PatrimonioObserver
{
    /**
     * Após criar um patrimônio, invalida caches relacionados.
     */
    public function created(Patrimonio $patrimonio): void
    {
        $this->invalidarCaches('created', $patrimonio);
    }

    /**
     * Após atualizar um patrimônio, invalida caches relacionados.
     */
    public function updated(Patrimonio $patrimonio): void
    {
        $this->invalidarCaches('updated', $patrimonio);
    }

    /**
     * Após excluir um patrimônio, invalida caches relacionados.
     */
    public function deleted(Patrimonio $patrimonio): void
    {
        $this->invalidarCaches('deleted', $patrimonio);
    }

    /**
     * Limpa todos os caches relacionados a patrimônios.
     */
    private function invalidarCaches(string $evento, Patrimonio $patrimonio): void
    {
        try {
            // Invalidar cache de busca
            SearchCacheService::invalidatePatrimonio();

            // Invalidar caches do dashboard (todos os períodos e modos de status)
            // Modos conforme DashboardController::resolveStatusMode()
            $statusModes = ['ativos', 'all', 'baixa', 'em_uso', 'a_disposicao', 'conserto'];
            foreach ($statusModes as $mode) {
                foreach (['day', 'week', 'month', 'year'] as $period) {
                    Cache::forget("dashboard_data_{$period}_{$mode}");
                }
                Cache::forget("dashboard_uf_data_{$mode}");
                Cache::forget("dashboard_top_cadastradores_{$mode}");
            }

            Log::debug("🔄 [CACHE] Caches invalidados após {$evento} do patrimônio #{$patrimonio->NUSEQPATR}");
        } catch (\Throwable $e) {
            Log::warning("⚠️ [CACHE] Falha ao invalidar caches: {$e->getMessage()}");
        }
    }
}
