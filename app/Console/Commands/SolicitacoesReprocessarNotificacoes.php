<?php

namespace App\Console\Commands;

use App\Models\SolicitacaoBem;
use App\Services\SolicitacaoBemEmailService;
use Illuminate\Console\Command;

class SolicitacoesReprocessarNotificacoes extends Command
{
    protected $signature = 'solicitacoes-bens:reprocessar-notificacoes
                            {--from=2026-04-07 11:25:00 : Data/hora inicial para filtrar por atualização}
                            {--ids= : IDs específicos separados por vírgula}
                            {--dry-run : Simular sem enviar}
                            {--queue : Agendar na fila em vez de enviar imediatamente}';

    protected $description = 'Reprocessa o último status de solicitações de bens pelo fluxo real de notificação.';

    public function handle(SolicitacaoBemEmailService $emailService): int
    {
        $from = (string) $this->option('from');
        $dryRun = (bool) $this->option('dry-run');
        $useQueue = (bool) $this->option('queue');
        $ids = $this->parseIds((string) ($this->option('ids') ?? ''));

        $this->info('Iniciando reprocessamento de notificações.');
        $this->line('Data de corte: ' . $from);
        $this->line('Modo de envio: ' . ($useQueue ? 'fila' : 'imediato'));
        $this->line('Execução: ' . ($dryRun ? 'simulação' : 'produção'));
        if ($ids !== []) {
            $this->line('Filtro por IDs: ' . implode(', ', $ids));
        }
        $this->newLine();

        $solicitacoes = SolicitacaoBem::query()
            ->when($ids !== [], fn ($query) => $query->whereIn('id', $ids))
            ->when($ids === [], fn ($query) => $query->where('updated_at', '>=', $from))
            ->orderBy('id')
            ->get();

        if ($solicitacoes->isEmpty()) {
            $this->warn('Nenhuma solicitação encontrada para o filtro informado.');

            return self::SUCCESS;
        }

        $this->info('Total de solicitações encontradas: ' . $solicitacoes->count());
        $this->newLine();

        $okCount = 0;
        $skipCount = 0;
        $failCount = 0;

        foreach ($solicitacoes as $solicitacao) {
            $evento = $this->resolverEvento($solicitacao);

            if ($evento === null) {
                $this->warn("Solicitação {$solicitacao->id}: status sem evento mapeado ({$solicitacao->status}).");
                $skipCount++;
                continue;
            }

            if ($dryRun) {
                $this->line("SIMULAÇÃO {$solicitacao->id}: {$solicitacao->status} -> {$evento}");
                $okCount++;
                continue;
            }

            try {
                if ($useQueue) {
                    $emailService->agendarNotificacaoFluxo($solicitacao, $evento);
                    $this->info("Solicitação {$solicitacao->id}: {$evento} agendado na fila.");
                } else {
                    $emailService->enviarNotificacaoFluxo((int) $solicitacao->id, $evento);
                    $this->info("Solicitação {$solicitacao->id}: {$evento} enviado imediatamente.");
                }

                $okCount++;
            } catch (\Throwable $e) {
                $this->error("Solicitação {$solicitacao->id}: erro ao processar {$evento}. {$e->getMessage()}");
                $failCount++;
            }
        }

        $this->newLine();
        $this->line('Resumo:');
        $this->line('Sucesso: ' . $okCount);
        $this->line('Ignorados: ' . $skipCount);
        $this->line('Falhas: ' . $failCount);

        return $failCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return list<int>
     */
    private function parseIds(string $ids): array
    {
        $parsed = array_values(array_filter(array_map(
            static fn (string $value): int => (int) trim($value),
            explode(',', $ids)
        )));

        return array_values(array_unique(array_filter($parsed, static fn (int $id): bool => $id > 0)));
    }

    private function resolverEvento(SolicitacaoBem $solicitacao): ?string
    {
        return match ($solicitacao->status) {
            SolicitacaoBem::STATUS_PENDENTE => 'criada',
            SolicitacaoBem::STATUS_AGUARDANDO_CONFIRMACAO => $solicitacao->hasLogisticsData()
                ? 'medidas_registradas'
                : 'triagem_concluida',
            SolicitacaoBem::STATUS_LIBERACAO => $solicitacao->isAwaitingTheoAuthorization()
                ? 'cotacoes_registradas'
                : 'autorizacao_liberacao',
            SolicitacaoBem::STATUS_CONFIRMADO => $solicitacao->hasShipmentData()
                ? 'pedido_enviado'
                : 'liberacao_aprovada',
            SolicitacaoBem::STATUS_NAO_ENVIADO => 'pedido_nao_enviado',
            SolicitacaoBem::STATUS_NAO_RECEBIDO => 'pedido_nao_recebido',
            SolicitacaoBem::STATUS_RECEBIDO => 'pedido_recebido',
            SolicitacaoBem::STATUS_CANCELADO => 'solicitacao_cancelada',
            default => null,
        };
    }
}
