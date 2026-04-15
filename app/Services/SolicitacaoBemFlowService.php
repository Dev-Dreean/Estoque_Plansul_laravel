<?php

namespace App\Services;

use App\Models\LocalProjeto;
use App\Models\SolicitacaoBem;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class SolicitacaoBemFlowService
{
    private const FLOW_BRUNO_MATRICULAS = ['11829'];
    private const FLOW_BRUNO_LOGINS = ['BRUNO'];
    private const FLOW_BRUNO_NAMES = ['BRUNO DE AZEVEDO FELICIANO'];

    private const FLOW_TIAGO_MATRICULAS = ['185895'];
    private const FLOW_TIAGO_LOGINS = ['TIAGOP'];
    private const FLOW_TIAGO_NAMES = ['TIAGO PACHECO'];

    private const FLOW_BEATRIZ_MATRICULAS = ['182687'];
    private const FLOW_BEATRIZ_LOGINS = ['BEA.SC'];
    private const FLOW_BEATRIZ_NAMES = ['BEATRIZ PATRICIA VIRISSIMO DOS SANTOS'];

    private const FLOW_THEO_MATRICULAS = ['134616'];
    private const FLOW_THEO_LOGINS = ['THEO'];
    private const FLOW_THEO_NAMES = ['THEODORO BUZZI AVILA'];

    public function normalizeFlow(?string $flow): string
    {
        $flow = mb_strtoupper(trim((string) $flow), 'UTF-8');

        return array_key_exists($flow, LocalProjeto::fluxoResponsavelOptions())
            ? $flow
            : LocalProjeto::FLUXO_RESPONSAVEL_PADRAO;
    }

    public function resolveFlow(?SolicitacaoBem $solicitacao): string
    {
        if (!$solicitacao) {
            return LocalProjeto::FLUXO_RESPONSAVEL_PADRAO;
        }

        return $this->normalizeFlow($solicitacao->fluxo_responsavel_normalizado ?? $solicitacao->fluxo_responsavel ?? null);
    }

    public function originLabel(?SolicitacaoBem $solicitacao): string
    {
        return $this->resolveFlow($solicitacao) === LocalProjeto::FLUXO_RESPONSAVEL_TI
            ? 'Estoque da TI'
            : 'Padrão';
    }

    public function initialTriageLabel(?SolicitacaoBem $solicitacao): string
    {
        return $this->resolveFlow($solicitacao) === LocalProjeto::FLUXO_RESPONSAVEL_TI
            ? 'Bruno'
            : 'Tiago ou Beatriz';
    }

    public function canConfirmSolicitacao(?User $user, ?SolicitacaoBem $solicitacao = null): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (!$user->temAcessoTela(User::TELA_SOLICITACOES_TRIAGEM_INICIAL)) {
            return false;
        }

        if ($solicitacao === null) {
            return $this->isBrunoFlowOperator($user)
                || $this->isTiagoFlowOperator($user)
                || $this->isBeatrizFlowOperator($user);
        }

        return $this->canConfirmSolicitacaoForFlow($user, $this->resolveFlow($solicitacao));
    }

    public function canConfirmSolicitacaoForFlow(?User $user, string $flow): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (!$user->temAcessoTela(User::TELA_SOLICITACOES_TRIAGEM_INICIAL)) {
            return false;
        }

        return $this->normalizeFlow($flow) === LocalProjeto::FLUXO_RESPONSAVEL_TI
            ? $this->isBrunoFlowOperator($user)
            : ($this->isTiagoFlowOperator($user) || $this->isBeatrizFlowOperator($user));
    }

    public function applyPendingScopeForUser(Builder $query, User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $canHandleTi = $this->canConfirmSolicitacaoForFlow($user, LocalProjeto::FLUXO_RESPONSAVEL_TI);
        $canHandlePadrao = $this->canConfirmSolicitacaoForFlow($user, LocalProjeto::FLUXO_RESPONSAVEL_PADRAO);

        if ($canHandleTi && $canHandlePadrao) {
            return;
        }

        if ($canHandleTi) {
            $query->whereRaw($this->pendingFlowConditionSql(true));

            return;
        }

        if ($canHandlePadrao) {
            $query->whereRaw($this->pendingFlowConditionSql(false));

            return;
        }

        $query->whereRaw('1 = 0');
    }

    public function pendingFlowConditionSql(bool $ti): string
    {
        if ($ti) {
            return "UPPER(TRIM(COALESCE(fluxo_responsavel, ''))) = 'TI'";
        }

        return "(fluxo_responsavel IS NULL OR TRIM(fluxo_responsavel) = '' OR UPPER(TRIM(fluxo_responsavel)) <> 'TI')";
    }

    public function isBrunoFlowOperator(?User $user): bool
    {
        return $this->isFlowOperator($user, self::FLOW_BRUNO_MATRICULAS, self::FLOW_BRUNO_LOGINS, self::FLOW_BRUNO_NAMES);
    }

    public function isTiagoFlowOperator(?User $user): bool
    {
        return $this->isFlowOperator($user, self::FLOW_TIAGO_MATRICULAS, self::FLOW_TIAGO_LOGINS, self::FLOW_TIAGO_NAMES);
    }

    public function isBeatrizFlowOperator(?User $user): bool
    {
        return $this->isFlowOperator($user, self::FLOW_BEATRIZ_MATRICULAS, self::FLOW_BEATRIZ_LOGINS, self::FLOW_BEATRIZ_NAMES);
    }

    public function isTheoFlowOperator(?User $user): bool
    {
        return $this->isFlowOperator($user, self::FLOW_THEO_MATRICULAS, self::FLOW_THEO_LOGINS, self::FLOW_THEO_NAMES);
    }

    /**
     * @param  array<int, string>  $matriculas
     * @param  array<int, string>  $logins
     * @param  array<int, string>  $names
     */
    private function isFlowOperator(?User $user, array $matriculas, array $logins, array $names): bool
    {
        if (!$user) {
            return false;
        }

        $matricula = trim((string) ($user->CDMATRFUNCIONARIO ?? ''));
        if ($matricula !== '' && in_array($matricula, $matriculas, true)) {
            return true;
        }

        $login = mb_strtoupper(trim((string) ($user->NMLOGIN ?? '')), 'UTF-8');
        if ($login !== '' && in_array($login, $logins, true)) {
            return true;
        }

        $name = mb_strtoupper(trim(preg_replace('/\s+/u', ' ', (string) ($user->NOMEUSER ?? '')) ?: ''), 'UTF-8');

        return $name !== '' && in_array($name, $names, true);
    }
}
