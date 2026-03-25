<?php

namespace App\Services;

use App\Models\SolicitacaoBem;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class SolicitacaoBemPendenciaService
{
    private const FLOW_BRUNO_MATRICULAS = ['11829'];
    private const FLOW_BRUNO_LOGINS = ['BRUNO'];
    private const FLOW_TIAGO_MATRICULAS = ['185895'];
    private const FLOW_TIAGO_LOGINS = ['TIAGOP'];
    private const FLOW_BEATRIZ_MATRICULAS = ['182687'];
    private const FLOW_BEATRIZ_LOGINS = ['BEA.SC'];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function notificationsFor(User $user): array
    {
        if (!$user->temAcessoTela(User::TELA_SOLICITACOES_BENS) || !Schema::hasTable('solicitacoes_bens')) {
            return [];
        }

        $query = SolicitacaoBem::query()
            ->where('status', '!=', SolicitacaoBem::STATUS_ARQUIVADO);

        $this->applyRelevantScope($query, $user);

        return $query
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (SolicitacaoBem $solicitacao) => $this->describeForUser($user, $solicitacao))
            ->filter(fn ($item) => is_array($item))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function describeForUser(User $user, SolicitacaoBem $solicitacao): ?array
    {
        if ($solicitacao->status === SolicitacaoBem::STATUS_PENDENTE && $this->canConfirmSolicitacao($user)) {
            return $this->makeNotification(
                $solicitacao,
                'confirmar_solicitacao',
                'Solicitação #' . $solicitacao->id . ' aguardando sua ação',
                'Triagem inicial pendente para liberar o fluxo da solicitação.',
                'Abrir triagem',
                100
            );
        }

        if ($solicitacao->status === SolicitacaoBem::STATUS_AGUARDANDO_CONFIRMACAO && !$solicitacao->hasLogisticsData() && $this->canRegisterMeasures($user)) {
            return $this->makeNotification(
                $solicitacao,
                'registrar_medidas',
                'Solicitação #' . $solicitacao->id . ' aguardando sua ação',
                'As medidas, o peso e a separação ainda precisam ser registrados.',
                'Registrar medidas',
                95
            );
        }

        if ($solicitacao->status === SolicitacaoBem::STATUS_AGUARDANDO_CONFIRMACAO && $solicitacao->hasLogisticsData() && $this->canRegisterQuote($user)) {
            return $this->makeNotification(
                $solicitacao,
                'registrar_cotacoes',
                'Solicitação #' . $solicitacao->id . ' aguardando sua ação',
                'A solicitação já tem medidas e peso, mas ainda precisa das cotações.',
                'Registrar cotações',
                92
            );
        }

        if ($solicitacao->status === SolicitacaoBem::STATUS_LIBERACAO
            && $solicitacao->isAwaitingRequesterDecision()
            && $this->canAuthorizeRelease($user)) {
            return $this->makeNotification(
                $solicitacao,
                'liberar_envio',
                'Solicitação #' . $solicitacao->id . ' aguardando sua ação',
                'A cotação já foi registrada e está aguardando sua liberação final.',
                'Liberar envio',
                90
            );
        }

        if ($solicitacao->status === SolicitacaoBem::STATUS_CONFIRMADO
            && !$solicitacao->hasShipmentData()
            && $this->canSendSolicitacao($user)
            && $this->isReadyToSend($solicitacao)) {
            return $this->makeNotification(
                $solicitacao,
                'registrar_envio',
                'Solicitação #' . $solicitacao->id . ' aguardando sua ação',
                'A solicitação já está pronta e precisa do registro de envio.',
                'Registrar envio',
                88
            );
        }

        if ($solicitacao->status === SolicitacaoBem::STATUS_CONFIRMADO
            && $solicitacao->hasShipmentData()
            && $this->canReceiveSolicitacao($user, $solicitacao)) {
            return $this->makeNotification(
                $solicitacao,
                'confirmar_recebimento',
                'Solicitação #' . $solicitacao->id . ' aguardando sua ação',
                'O envio foi registrado e falta confirmar o recebimento.',
                'Confirmar recebimento',
                84
            );
        }

        return null;
    }

    private function applyRelevantScope(Builder $query, User $user): void
    {
        $query->where(function (Builder $builder) use ($user) {
            if ($this->canConfirmSolicitacao($user)) {
                $builder->orWhere('status', SolicitacaoBem::STATUS_PENDENTE);
            }

            if ($this->canRegisterMeasures($user)) {
                $builder->orWhere(function (Builder $stage) {
                    $stage->where('status', SolicitacaoBem::STATUS_AGUARDANDO_CONFIRMACAO);
                    $this->applyNoLogistics($stage);
                });
            }

            if ($this->canRegisterQuote($user)) {
                $builder->orWhere(function (Builder $stage) {
                    $stage->where('status', SolicitacaoBem::STATUS_AGUARDANDO_CONFIRMACAO);
                    $this->applyHasLogistics($stage);
                });
            }

            if ($this->canAuthorizeRelease($user)) {
                $builder->orWhere(function (Builder $stage) {
                    $stage->where('status', SolicitacaoBem::STATUS_LIBERACAO)
                        ->whereNotNull('quote_options_payload')
                        ->whereNull('quote_approved_at');
                });
            }

            if ($this->canSendSolicitacao($user)) {
                $builder->orWhere(function (Builder $stage) {
                    $stage->where('status', SolicitacaoBem::STATUS_CONFIRMADO);
                    $this->applyNoShipment($stage);
                });
            }

            if ($this->canReceiveAny($user)) {
                $builder->orWhere(function (Builder $stage) use ($user) {
                    $stage->where('status', SolicitacaoBem::STATUS_CONFIRMADO);
                    $this->applyHasShipment($stage);
                    $this->applyOwnerFilter($stage, $user);
                });
            }
        });
    }

    private function applyHasLogistics(Builder $builder): void
    {
        $builder->where(function (Builder $logistics) {
            $logistics->whereNotNull('logistics_height_cm')
                ->orWhereNotNull('logistics_width_cm')
                ->orWhereNotNull('logistics_length_cm')
                ->orWhereNotNull('logistics_weight_kg')
                ->orWhereNotNull('logistics_registered_at')
                ->orWhere(function (Builder $notes) {
                    $notes->whereNotNull('logistics_notes')
                        ->where('logistics_notes', '!=', '');
                });
        });
    }

    private function applyNoLogistics(Builder $builder): void
    {
        $builder->whereNull('logistics_height_cm')
            ->whereNull('logistics_width_cm')
            ->whereNull('logistics_length_cm')
            ->whereNull('logistics_weight_kg')
            ->whereNull('logistics_registered_at')
            ->where(function (Builder $notes) {
                $notes->whereNull('logistics_notes')
                    ->orWhere('logistics_notes', '');
            });
    }

    private function applyHasShipment(Builder $builder): void
    {
        $builder->where(function (Builder $shipment) {
            $shipment->where(function (Builder $tracking) {
                $tracking->whereNotNull('tracking_code')
                    ->where('tracking_code', '!=', '');
            })->orWhere(function (Builder $invoice) {
                $invoice->whereNotNull('invoice_number')
                    ->where('invoice_number', '!=', '');
            })->orWhereNotNull('shipped_at');
        });
    }

    private function applyNoShipment(Builder $builder): void
    {
        $builder->where(function (Builder $shipment) {
            $shipment->whereNull('tracking_code')
                ->orWhere('tracking_code', '');
        })->where(function (Builder $invoice) {
            $invoice->whereNull('invoice_number')
                ->orWhere('invoice_number', '');
        })->whereNull('shipped_at');
    }

    private function applyOwnerFilter(Builder $builder, User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $userId = $user->getAuthIdentifier();
        $matricula = trim((string) ($user->CDMATRFUNCIONARIO ?? ''));

        $builder->where(function (Builder $owner) use ($userId, $matricula) {
            if ($userId) {
                $owner->where('solicitante_id', $userId);
            }

            if ($matricula !== '') {
                if ($userId) {
                    $owner->orWhere('solicitante_matricula', $matricula);
                } else {
                    $owner->where('solicitante_matricula', $matricula);
                }
            }
        });
    }

    private function canReceiveAny(User $user): bool
    {
        $matricula = trim((string) ($user->CDMATRFUNCIONARIO ?? ''));

        return $user->isAdmin() || $user->getAuthIdentifier() !== null || $matricula !== '';
    }

    private function canReceiveSolicitacao(User $user, SolicitacaoBem $solicitacao): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $userId = $user->getAuthIdentifier();
        if ($userId !== null && (string) $solicitacao->solicitante_id === (string) $userId) {
            return true;
        }

        $matricula = trim((string) ($user->CDMATRFUNCIONARIO ?? ''));

        return $matricula !== '' && $matricula === trim((string) ($solicitacao->solicitante_matricula ?? ''));
    }

    private function canConfirmSolicitacao(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->temAcessoTela(User::TELA_SOLICITACOES_TRIAGEM_INICIAL)
            && ($this->isTiagoFlowOperator($user) || $this->isBeatrizFlowOperator($user));
    }

    private function canRegisterMeasures(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->temAcessoTela(User::TELA_SOLICITACOES_ATUALIZAR)
            && $this->isTiagoFlowOperator($user);
    }

    private function canRegisterQuote(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->temAcessoTela(User::TELA_SOLICITACOES_ATUALIZAR)
            && $this->isBeatrizFlowOperator($user);
    }

    private function canAuthorizeRelease(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->temAcessoTela(User::TELA_SOLICITACOES_LIBERACAO_ENVIO)
            && $this->isBrunoFlowOperator($user);
    }

    private function canSendSolicitacao(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->temAcessoTela(User::TELA_SOLICITACOES_APROVAR)
            && ($this->isTiagoFlowOperator($user) || $this->isBeatrizFlowOperator($user));
    }

    private function isTiagoFlowOperator(User $user): bool
    {
        return $this->isFlowOperator($user, self::FLOW_TIAGO_MATRICULAS, self::FLOW_TIAGO_LOGINS);
    }

    private function isBeatrizFlowOperator(User $user): bool
    {
        return $this->isFlowOperator($user, self::FLOW_BEATRIZ_MATRICULAS, self::FLOW_BEATRIZ_LOGINS);
    }

    private function isBrunoFlowOperator(User $user): bool
    {
        return $this->isFlowOperator($user, self::FLOW_BRUNO_MATRICULAS, self::FLOW_BRUNO_LOGINS);
    }

    /**
     * @param  array<int, string>  $matriculas
     * @param  array<int, string>  $logins
     */
    private function isFlowOperator(User $user, array $matriculas, array $logins): bool
    {
        $matricula = trim((string) ($user->CDMATRFUNCIONARIO ?? ''));
        if ($matricula !== '' && in_array($matricula, $matriculas, true)) {
            return true;
        }

        $login = mb_strtoupper(trim((string) ($user->NMLOGIN ?? '')), 'UTF-8');

        return $login !== '' && in_array($login, $logins, true);
    }

    private function isReadyToSend(SolicitacaoBem $solicitacao): bool
    {
        if (!$solicitacao->hasQuoteData()) {
            return true;
        }

        return $solicitacao->quote_approved_at !== null || $solicitacao->isReadyToShip();
    }

    /**
     * @return array<string, mixed>
     */
    private function makeNotification(
        SolicitacaoBem $solicitacao,
        string $action,
        string $title,
        string $description,
        string $actionLabel,
        int $importance
    ): array {
        $occurredAt = $solicitacao->updated_at instanceof Carbon
            ? $solicitacao->updated_at
            : ($solicitacao->created_at instanceof Carbon ? $solicitacao->created_at : now());

        return [
            'provider' => 'solicitacoes',
            'item_key' => 'solicitacao:' . $solicitacao->id . ':' . $action,
            'modulo' => 'Solicitações',
            'titulo' => $title,
            'descricao' => $description,
            'acao_label' => $actionLabel,
            'url' => route('solicitacoes-bens.index', [
                'open_modal' => $solicitacao->id,
                'notification_action' => $action,
            ]),
            'importance' => $importance,
            'occurred_at' => $occurredAt->toIso8601String(),
            'occurred_at_label' => $occurredAt->format('d/m/Y H:i'),
            'countable' => true,
            'count_value' => 1,
        ];
    }
}
