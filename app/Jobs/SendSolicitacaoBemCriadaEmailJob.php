<?php

namespace App\Jobs;

use App\Services\SolicitacaoBemEmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSolicitacaoBemCriadaEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $solicitacaoId, public readonly string $evento = 'criada')
    {
    }

    public function handle(SolicitacaoBemEmailService $emailService): void
    {
        $emailService->enviarNotificacaoFluxo($this->solicitacaoId, $this->evento);
    }
}