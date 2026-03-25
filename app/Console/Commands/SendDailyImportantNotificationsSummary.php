<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ImportantNotificationsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDailyImportantNotificationsSummary extends Command
{
    protected $signature = 'notificacoes:resumo-diario';

    protected $description = 'Envia o resumo diário de pendências importantes por e-mail.';

    public function __construct(
        private readonly ImportantNotificationsService $importantNotificationsService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (!(bool) config('notificacoes.enabled', true) || !(bool) config('notificacoes.daily_summary.enabled', true)) {
            $this->info('Resumo diário de notificações desabilitado.');

            return self::SUCCESS;
        }

        $usuarios = User::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('NOMEUSER')
            ->get();

        $enviados = 0;

        foreach ($usuarios as $usuario) {
            $payload = $this->importantNotificationsService->payloadForUser($usuario);
            if (($payload['total_count'] ?? 0) <= 0) {
                continue;
            }

            Mail::send('emails.notificacoes.resumo-diario', [
                'usuario' => $usuario,
                'items' => $payload['items'],
                'grouped' => $payload['grouped'],
                'totalCount' => $payload['total_count'],
            ], function ($message) use ($usuario) {
                $message->to($usuario->email)
                    ->subject((string) config('notificacoes.daily_summary.subject', 'Resumo diário de pendências importantes'));
            });

            $enviados++;
        }

        $this->info('Resumo diário enviado para ' . $enviados . ' usuário(s).');

        return self::SUCCESS;
    }
}
