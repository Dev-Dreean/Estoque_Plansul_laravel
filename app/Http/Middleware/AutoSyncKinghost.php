<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * AutoSyncKinghost — "Poor man's cron" para hospedagem compartilhada.
 *
 * Executa sync:kinghost-data em background na primeira requisição
 * de cada intervalo de horas configurado (padrão: 8h).
 *
 * Por que aqui: KingHost bloqueia crontab via SSH, então usamos
 * o tráfego web como gatilho. O processo roda em background com
 * nohup, sem bloquear a requisição do usuário.
 */
class AutoSyncKinghost
{
    // Intervalo mínimo entre syncs (em segundos). 8 horas = 28800
    private const INTERVAL = 28800;

    // Arquivo de lock com o timestamp da última execução
    private const LOCK_FILE = 'sync-kinghost.lock';

    public function handle(Request $request, Closure $next): Response
    {
        $this->dispatchIfNeeded();
        return $next($request);
    }

    private function dispatchIfNeeded(): void
    {
        try {
            $lockPath = storage_path('app/' . self::LOCK_FILE);

            // Verificar quando foi a última execução
            if (file_exists($lockPath)) {
                $lastRun = (int) file_get_contents($lockPath);
                if (time() - $lastRun < self::INTERVAL) {
                    return; // Ainda não chegou a hora
                }
            }

            // Atualizar o timestamp ANTES de disparar (evita concorrência)
            file_put_contents($lockPath, time());

            $phpBin = $this->getPhpBinary();
            $artisan = base_path('artisan');
            $logPath = storage_path('logs/sync-kinghost.log');

            // Lança o processo em background sem bloquear a requisição
            exec("nohup {$phpBin} {$artisan} sync:kinghost-data >> {$logPath} 2>&1 &");

            Log::info('🔄 [AUTO_SYNC] Sincronização do KingHost disparada em background', [
                'php' => $phpBin,
                'next_run' => date('Y-m-d H:i:s', time() + self::INTERVAL),
            ]);
        } catch (\Throwable $e) {
            // Nunca deve quebrar a requisição em curso
            Log::warning('⚠️  [AUTO_SYNC] Falha ao disparar sync: ' . $e->getMessage());
        }
    }

    private function getPhpBinary(): string
    {
        // KingHost produção: php82 em /usr/local/php/8.2/bin/php
        if (file_exists('/usr/local/php/8.2/bin/php')) {
            return '/usr/local/php/8.2/bin/php';
        }
        // Descoberto via diagnóstico: path real do binário php82 no KingHost
        if (file_exists('/opt/remi/php82/root/usr/bin/php')) {
            return '/opt/remi/php82/root/usr/bin/php';
        }
        // Fallback: binário que está executando o processo atual
        return PHP_BINARY;
    }
}
