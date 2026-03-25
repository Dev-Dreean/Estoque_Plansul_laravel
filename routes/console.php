<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Sincroniza automaticamente funcionários e projetos do KingHost.
 * Executa às 06:00 e às 18:00 todos os dias, garantindo que novos
 * colaboradores e projetos apareçam no sistema sem intervenção manual.
 */
Schedule::command('sync:kinghost-data')
    ->twiceDaily(6, 18)
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/sync-kinghost.log'))
    ->name('sync-kinghost-funcionarios-projetos');

Schedule::command('notificacoes:resumo-diario')
    ->dailyAt((string) config('notificacoes.daily_summary.time', '08:00'))
    ->timezone((string) config('notificacoes.daily_summary.timezone', 'America/Sao_Paulo'))
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/notificacoes-resumo-diario.log'))
    ->name('notificacoes-resumo-diario');
