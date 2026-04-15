<?php

namespace App\Console\Commands;

use App\Models\SolicitacaoBem;
use App\Services\SolicitacaoBemEmailService;
use Illuminate\Console\Command;

class SolicitacoesEnviarTestEmail extends Command
{
    protected $signature = 'solicitacoes-bens:enviar-test-email
                            {--to=suporte.dev@plansul.com.br : E-mail que receberá os testes}
                            {--ids= : IDs das solicitações separados por vírgula}
                            {--dry-run : Simular sem enviar}';

    protected $description = 'Reenvia notificações de solicitações para um e-mail de teste usando o mesmo fluxo real da integração.';

    public function handle(SolicitacaoBemEmailService $emailService): int
    {
        $toEmail = trim((string) $this->option('to'));
        $dryRun = (bool) $this->option('dry-run');
        $ids = $this->parseIds((string) ($this->option('ids') ?? ''));

        if ($toEmail === '') {
            $this->error('Informe um e-mail válido em --to.');

            return self::FAILURE;
        }

        if ($ids === []) {
            $this->error('Informe pelo menos um ID em --ids.');

            return self::FAILURE;
        }

        config([
            'solicitacoes_bens.email_to' => $toEmail,
            'solicitacoes_bens.notificacoes.enabled' => true,
        ]);

        $this->info('Preparando envio de teste.');
        $this->line('Destinatário de teste: ' . $toEmail);
        $this->line('IDs informados: ' . implode(', ', $ids));
        $this->line('Execução: ' . ($dryRun ? 'simulação' : 'produção'));
        $this->newLine();

        $solicitacoes = SolicitacaoBem::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get();

        if ($solicitacoes->isEmpty()) {
            $this->warn('Nenhuma solicitação encontrada para os IDs informados.');

            return self::FAILURE;
        }

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
                $this->line("SIMULAÇÃO {$solicitacao->id}: {$solicitacao->status} -> {$evento} para {$toEmail}");
                $okCount++;
                continue;
            }

            try {
                $emailService->enviarNotificacaoFluxo((int) $solicitacao->id, $evento);
                $this->info("Solicitação {$solicitacao->id}: {$evento} enviada para {$toEmail}.");
                $okCount++;
            } catch (\Throwable $e) {
                $this->error("Solicitação {$solicitacao->id}: erro ao enviar {$evento}. {$e->getMessage()}");
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
