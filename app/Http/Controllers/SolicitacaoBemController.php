<?php

namespace App\Http\Controllers;

use App\Models\Tabfant;
use App\Models\SolicitacaoBem;
use App\Models\SolicitacaoBemStatusHistorico;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SolicitacaoBemController extends Controller
{
    private const TELA_SOLICITACOES_VER_TODAS = 1011;
    private const TELA_SOLICITACOES_ATUALIZAR = 1012;
    private const TELA_SOLICITACOES_CRIAR = 1013;

    public function index(Request $request): View
    {
        $perPage = max(10, min(200, $request->integer('per_page', 30)));
        $query = SolicitacaoBem::query();
        /** @var User|null $user */
        $user = Auth::user();

        if (!$this->canViewAllSolicitacoes($user)) {
            $this->applyOwnerScope($query, $user);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('uf')) {
            $query->where('uf', strtoupper(trim((string) $request->input('uf'))));
        }

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('solicitante_nome', 'like', '%' . $term . '%')
                    ->orWhere('solicitante_matricula', 'like', '%' . $term . '%')
                    ->orWhere('setor', 'like', '%' . $term . '%')
                    ->orWhere('local_destino', 'like', '%' . $term . '%');
            });
        }

        $solicitacoes = $query
            ->withCount('itens')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $statusOptions = SolicitacaoBem::statusOptions();
        $projetos = Tabfant::orderBy('NOMEPROJETO')->get();

        return view('solicitacoes.index', compact('solicitacoes', 'statusOptions', 'projetos'));
    }

    public function create(Request $request): View|JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canCreateSolicitacao($user)) {
            return $this->denyAccess($request, 'Você não tem permissão para criar solicitacao.');
        }

        $isModal = $request->input('modal') === '1';
        $projetos = Tabfant::orderBy('NOMEPROJETO')->get();
        return view('solicitacoes.create', [
            'user' => Auth::user(),
            'isModal' => $isModal,
            'projetos' => $projetos,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canCreateSolicitacao($user)) {
            return $this->denyAccess($request, 'Você não tem permissão para criar solicitacao.');
        }

        $rules = [
            'solicitante_nome' => ['required', 'string', 'max:120'],
            'solicitante_matricula' => ['nullable', 'string', 'max:20'],
            'projeto_id' => ['required', 'integer', 'exists:tabfant,id'],
            'uf' => ['nullable', 'string', 'max:2'],
            'local_destino' => ['required', 'string', 'max:150'],
            'observacao' => ['nullable', 'string', 'max:2000'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.descricao' => ['required', 'string', 'max:200'],
            'itens.*.quantidade' => ['required', 'integer', 'min:1'],
            'itens.*.unidade' => ['nullable', 'string', 'max:20'],
            'itens.*.observacao' => ['nullable', 'string', 'max:500'],
        ];

        $validated = $request->validate($rules);

        $user = Auth::user();
        $uf = strtoupper(trim((string) ($validated['uf'] ?? '')));
        $matricula = trim((string) ($validated['solicitante_matricula'] ?? ''));
        $observacao = trim((string) ($validated['observacao'] ?? ''));

        $solicitacao = null;

        DB::transaction(function () use ($validated, $user, $uf, $matricula, $observacao, &$solicitacao) {
            $solicitacao = SolicitacaoBem::create([
                'solicitante_id' => $user?->getAuthIdentifier(),
                'solicitante_nome' => trim((string) $validated['solicitante_nome']),
                'solicitante_matricula' => $matricula !== '' ? $matricula : null,
                'projeto_id' => $validated['projeto_id'],
                'uf' => $uf !== '' ? $uf : null,
                'local_destino' => trim((string) $validated['local_destino']),
                'observacao' => $observacao !== '' ? $observacao : null,
                'status' => SolicitacaoBem::STATUS_PENDENTE,
            ]);

            $itens = collect($validated['itens'] ?? [])
                ->map(function ($item) {
                    return [
                        'descricao' => trim((string) ($item['descricao'] ?? '')),
                        'quantidade' => (int) ($item['quantidade'] ?? 1),
                        'unidade' => trim((string) ($item['unidade'] ?? '')),
                        'observacao' => trim((string) ($item['observacao'] ?? '')),
                    ];
                })
                ->all();

            if (!empty($itens)) {
                $solicitacao->itens()->createMany($itens);
            }
        });

        if ($solicitacao) {
            $this->registrarHistoricoStatus(
                $solicitacao,
                null,
                SolicitacaoBem::STATUS_PENDENTE,
                'criado',
                null
            );
            $this->sendConfirmacaoEmail($solicitacao);
        }

        // Se foi aberto como modal, retornar JSON
        if ($request->input('modal') === '1') {
            return response()->json([
                'success' => true,
                'message' => 'Solicitacao registrada com sucesso.',
                'redirect' => route('solicitacoes-bens.index'),
            ]);
        }

        return redirect()
            ->route('solicitacoes-bens.index')
            ->with('success', 'Solicitacao registrada com sucesso.');
    }

    public function show(Request $request, SolicitacaoBem $solicitacao): View|JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canViewSolicitacao($user, $solicitacao)) {
            return $this->denyAccess($request, 'Você não tem permissão para visualizar esta solicitacao.');
        }

        $isModal = $request->input('modal') === '1';
        $solicitacao->load(['itens', 'projeto', 'historicoStatus.usuario']);
        $statusOptions = SolicitacaoBem::statusOptions();

        $canManage = $this->canUpdateSolicitacao($user)
            || $this->canConfirmSolicitacao($user)
            || $this->canApproveSolicitacao($user)
            || $this->canCancelSolicitacao($user)
            || $this->canReturnSolicitacao($user);

        if ($isModal) {
            return view('solicitacoes.partials.show-content', compact('solicitacao', 'statusOptions', 'isModal', 'canManage'));
        }

        return view('solicitacoes.show', compact('solicitacao', 'statusOptions', 'isModal', 'canManage'));
    }

    public function showModal(Request $request, SolicitacaoBem $solicitacao): View
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canViewSolicitacao($user, $solicitacao)) {
            abort(403, 'Você não tem permissão para visualizar esta solicitacao.');
        }

        $solicitacao->load(['itens', 'projeto', 'historicoStatus.usuario']);
        $statusOptions = SolicitacaoBem::statusOptions();
        $canManage = $this->canUpdateSolicitacao($user)
            || $this->canConfirmSolicitacao($user)
            || $this->canApproveSolicitacao($user)
            || $this->canCancelSolicitacao($user)
            || $this->canReturnSolicitacao($user);
        $isModal = true;

        return view('solicitacoes.partials.show-content', compact('solicitacao', 'statusOptions', 'isModal', 'canManage'));
    }

    public function update(Request $request, SolicitacaoBem $solicitacao): RedirectResponse|JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canViewSolicitacao($user, $solicitacao)) {
            return $this->denyAccess($request, 'Você não tem permissão para atualizar esta solicitacao.');
        }
        if (!$this->canUpdateSolicitacao($user)) {
            return $this->denyAccess($request, 'Você não tem permissão para atualizar esta solicitacao.');
        }

        $data = $request->validate([
            'local_destino' => ['nullable', 'string', 'max:150'],
            'observacao_controle' => ['nullable', 'string', 'max:2000'],
            'recebedor_matricula' => ['nullable', 'string', 'max:20', 'exists:funcionarios,CDMATRFUNCIONARIO'],
        ]);

        $data['local_destino'] = trim((string) ($data['local_destino'] ?? ''));
        if ($data['local_destino'] === '') {
            $data['local_destino'] = null;
        }
        $data['observacao_controle'] = trim((string) ($data['observacao_controle'] ?? ''));
        if ($data['observacao_controle'] === '') {
            $data['observacao_controle'] = null;
        }
        
        // Mapear recebedor_matricula → matricula_recebedor (coluna do banco)
        if (!empty($data['recebedor_matricula'])) {
            $funcionario = \App\Models\Funcionario::where('CDMATRFUNCIONARIO', $data['recebedor_matricula'])->first();
            if ($funcionario) {
                $data['matricula_recebedor'] = $funcionario->CDMATRFUNCIONARIO;
                $data['nome_recebedor'] = $funcionario->NMFUNCIONARIO;
            }
        }
        unset($data['recebedor_matricula']); // Remove campo temporário
        $solicitacao->fill($data);

        // Novo fluxo: não há campos separado_em, concluido_em, etc.
        // Os campos confirmado_em, cancelado_em são preenchidos pelos métodos confirm/approve/cancel

        $solicitacao->save();

        if ($request->input('modal') === '1') {
            return response()->json([
                'success' => true,
                'message' => 'Solicitacao atualizada com sucesso.',
            ]);
        }

        return redirect()
            ->route('solicitacoes-bens.index')
            ->with('success', 'Solicitacao atualizada com sucesso.');
    }

    public function destroy(Request $request, SolicitacaoBem $solicitacao): RedirectResponse|JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canDeleteSolicitacao($user, $solicitacao)) {
            return $this->denyAccess($request, 'Você não tem permissão para remover esta solicitacao.');
        }

        DB::transaction(function () use ($solicitacao) {
            $solicitacao->itens()->delete();
            $solicitacao->delete();
        });

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Solicitacao removida com sucesso.',
            ]);
        }

        return redirect()
            ->route('solicitacoes-bens.index')
            ->with('success', 'Solicitacao removida com sucesso.');
    }

    private function canViewAllSolicitacoes(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->PERFIL === User::PERFIL_CONSULTOR) {
            return false;
        }
        return $user->temAcessoTela((string) self::TELA_SOLICITACOES_VER_TODAS)
            || $user->temAcessoTela((string) self::TELA_SOLICITACOES_ATUALIZAR)
            || $user->temAcessoTela((string) User::TELA_SOLICITACOES_APROVAR)
            || $user->temAcessoTela((string) User::TELA_SOLICITACOES_CANCELAR);
    }

    private function isOwner(?User $user, SolicitacaoBem $solicitacao): bool
    {
        if (!$user) {
            return false;
        }

        $userId = $user->getAuthIdentifier();
        if ($userId && (string) $solicitacao->solicitante_id === (string) $userId) {
            return true;
        }

        $matricula = trim((string) ($user->CDMATRFUNCIONARIO ?? ''));
        if ($matricula === '') {
            return false;
        }

        return trim((string) ($solicitacao->solicitante_matricula ?? '')) === $matricula;
    }

    private function canViewSolicitacao(?User $user, SolicitacaoBem $solicitacao): bool
    {
        return $this->canViewAllSolicitacoes($user) || $this->isOwner($user, $solicitacao);
    }

    private function canDeleteSolicitacao(?User $user, SolicitacaoBem $solicitacao): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }
        return $this->isOwner($user, $solicitacao);
    }

    private function canUpdateSolicitacao(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->PERFIL === User::PERFIL_CONSULTOR) {
            return false;
        }
        return $user->temAcessoTela((string) self::TELA_SOLICITACOES_ATUALIZAR);
    }    private function canCreateSolicitacao(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        // ✅ ADM sempre tem acesso (nunca precisa de permissão explícita)
        if ($user->isAdmin()) {
            return true;
        }
        return $user->temAcessoTela((string) self::TELA_SOLICITACOES_CRIAR);
    }

    private function canConfirmSolicitacao(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        // ✅ ADM sempre tem acesso (nunca precisa de permissão explícita)
        if ($user->isAdmin()) {
            return true;
        }
        return $user->temAcessoTela((string) self::TELA_SOLICITACOES_VER_TODAS);
    }

    private function canApproveSolicitacao(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        // ✅ ADM sempre tem acesso (nunca precisa de permissão explícita)
        if ($user->isAdmin()) {
            return true;
        }
        return $user->temAcessoTela((string) User::TELA_SOLICITACOES_APROVAR);
    }

    private function canCancelSolicitacao(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        // ✅ ADM sempre tem acesso (nunca precisa de permissão explícita)
        if ($user->isAdmin()) {
            return true;
        }
        return $user->temAcessoTela((string) User::TELA_SOLICITACOES_CANCELAR);
    }

    private function canReturnSolicitacao(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }
        return $user->temAcessoTela((string) User::TELA_SOLICITACOES_APROVAR);
    }

    private function applyOwnerScope($query, ?User $user): void
    {
        if (!$user) {
            $query->whereRaw('1=0');
            return;
        }

        $userId = $user->getAuthIdentifier();
        $matricula = trim((string) ($user->CDMATRFUNCIONARIO ?? ''));

        $query->where(function ($builder) use ($userId, $matricula) {
            if ($userId) {
                $builder->where('solicitante_id', $userId);
            }
            if ($matricula !== '') {
                if ($userId) {
                    $builder->orWhere('solicitante_matricula', $matricula);
                } else {
                    $builder->where('solicitante_matricula', $matricula);
                }
            }
        });
    }

    private function denyAccess(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        abort(403, $message);
    }

    private function sendConfirmacaoEmail(SolicitacaoBem $solicitacao): void
    {
        $to = trim((string) config('solicitacoes_bens.email_to'));
        if ($to === '') {
            return;
        }

        $subject = 'Solicitacao de bens recebida #' . $solicitacao->id;
        $body = implode("\n", [
            'Uma nova solicitacao de bens foi registrada.',
            'Numero: ' . $solicitacao->id,
            'Solicitante: ' . ($solicitacao->solicitante_nome ?? '-'),
            'Matricula: ' . ($solicitacao->solicitante_matricula ?? '-'),
            'Setor: ' . ($solicitacao->setor ?? '-'),
            'UF: ' . ($solicitacao->uf ?? '-'),
            'Local destino: ' . ($solicitacao->local_destino ?? '-'),
            'Status: ' . ($solicitacao->status ?? '-'),
        ]);

        try {
            Mail::raw($body, function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });

            $solicitacao->email_confirmacao_enviado_em = now();
            $solicitacao->save();
        } catch (\Throwable $e) {
            Log::warning('Falha ao enviar email de solicitacao de bens', [
                'solicitacao_id' => $solicitacao->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function registrarHistoricoStatus(
        SolicitacaoBem $solicitacao,
        ?string $statusAnterior,
        string $statusNovo,
        ?string $acao = null,
        ?string $motivo = null
    ): void {
        if (!Schema::hasTable('solicitacoes_bens_status_historico')) {
            return;
        }

        $userId = Auth::user()?->getAuthIdentifier();

        SolicitacaoBemStatusHistorico::create([
            'solicitacao_id' => $solicitacao->id,
            'status_anterior' => $statusAnterior,
            'status_novo' => $statusNovo,
            'acao' => $acao,
            'motivo' => $motivo,
            'usuario_id' => $userId,
        ]);
    }

    /**
     * Confirmar solicitação (primeira confirmação)
     * Somente Tiago, Beatriz e Admin
     */
    public function confirm(Request $request, SolicitacaoBem $solicitacao): JsonResponse|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canConfirmSolicitacao($user)) {
            return $this->denyAccess($request, 'Voce nao tem permissao para confirmar solicitacoes.');
        }

        if ($solicitacao->status !== SolicitacaoBem::STATUS_PENDENTE) {
            return $this->denyAccess($request, 'Apenas solicitações pendentes podem ser confirmadas.');
        }

        $validated = $request->validate([
            'recebedor_matricula' => ['required', 'string', 'max:20', 'exists:funcionarios,CDMATRFUNCIONARIO'],
            'tracking_code' => ['required', 'string', 'max:100'],
            'destination_type' => ['nullable', 'in:FILIAL,PROJETO'],
        ]);

        $data = [
            'status' => SolicitacaoBem::STATUS_AGUARDANDO_CONFIRMACAO,
            'tracking_code' => $validated['tracking_code'],
        ];
        if (!empty($validated['destination_type'])) {
            $data['destination_type'] = $validated['destination_type'];
        }

        if (!empty($validated['recebedor_matricula'])) {
            $funcionario = \App\Models\Funcionario::where('CDMATRFUNCIONARIO', $validated['recebedor_matricula'])->first();
            if ($funcionario) {
                $data['matricula_recebedor'] = $funcionario->CDMATRFUNCIONARIO;
                $data['nome_recebedor'] = $funcionario->NMFUNCIONARIO;
            }
        }

        $statusAnterior = $solicitacao->status;
        $solicitacao->update($data);
        $this->registrarHistoricoStatus(
            $solicitacao,
            $statusAnterior,
            SolicitacaoBem::STATUS_AGUARDANDO_CONFIRMACAO,
            'confirmar',
            null
        );

        Log::info('✅ [SOLICITACOES] Solicitação confirmada (1º nível)', [
            'solicitacao_id' => $solicitacao->id,
            'confirmado_por' => $user->NMLOGIN,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Solicitação confirmada. Aguardando aprovação final.',
            ]);
        }

        return redirect()->back()->with('success', 'Solicitação confirmada com sucesso!');
    }

    /**
     * Aprovar solicitação (confirmação final)
     * Somente o solicitante original pode fazer
     */
    public function approve(Request $request, SolicitacaoBem $solicitacao): JsonResponse|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canApproveSolicitacao($user)) {
            return $this->denyAccess($request, 'Voce nao tem permissao para aprovar a solicitacao.');
        }
        if (empty($solicitacao->matricula_recebedor)) {
            return $this->denyAccess($request, 'Informe o responsavel recebedor antes de aprovar a solicitacao.');
        }

        if ($solicitacao->status !== SolicitacaoBem::STATUS_AGUARDANDO_CONFIRMACAO) {
            return $this->denyAccess($request, 'Apenas solicitações aguardando confirmação podem ser aprovadas.');
        }

        $statusAnterior = $solicitacao->status;
        $solicitacao->update([
            'status' => SolicitacaoBem::STATUS_CONFIRMADO,
            'confirmado_por_id' => $user->getAuthIdentifier(),
            'confirmado_em' => now(),
        ]);
        $this->registrarHistoricoStatus(
            $solicitacao,
            $statusAnterior,
            SolicitacaoBem::STATUS_CONFIRMADO,
            'aprovar',
            null
        );

        Log::info('✅ [SOLICITACOES] Solicitação aprovada (2º nível)', [
            'solicitacao_id' => $solicitacao->id,
            'aprovado_por' => $user->NMLOGIN,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Solicitação aprovada com sucesso!',
            ]);
        }

        return redirect()->back()->with('success', 'Solicitação aprovada com sucesso!');
    }

    /**
     * Cancelar solicitação
     * Somente o solicitante pode cancelar
     */
    public function cancel(Request $request, SolicitacaoBem $solicitacao): JsonResponse|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canCancelSolicitacao($user)) {
            return $this->denyAccess($request, 'Voce nao tem permissao para cancelar a solicitacao.');
        }

        if ($solicitacao->status === SolicitacaoBem::STATUS_CANCELADO) {
            return $this->denyAccess($request, 'Esta solicitação já foi cancelada.');
        }

        if ($solicitacao->status !== SolicitacaoBem::STATUS_PENDENTE) {
            return $this->denyAccess($request, 'Apenas solicitacoes pendentes podem ser canceladas.');
        }

        $validated = $request->validate([
            'justificativa_cancelamento' => ['required', 'string', 'max:1000'],
        ]);

        $statusAnterior = $solicitacao->status;
        $solicitacao->update([
            'status' => SolicitacaoBem::STATUS_CANCELADO,
            'justificativa_cancelamento' => $validated['justificativa_cancelamento'],
            'cancelado_por_id' => $user->getAuthIdentifier(),
            'cancelado_em' => now(),
        ]);
        $this->registrarHistoricoStatus(
            $solicitacao,
            $statusAnterior,
            SolicitacaoBem::STATUS_CANCELADO,
            'cancelar',
            $validated['justificativa_cancelamento']
        );

        Log::info('🚫 [SOLICITACOES] Solicitação cancelada', [
            'solicitacao_id' => $solicitacao->id,
            'cancelado_por' => $user->NMLOGIN,
            'justificativa' => $validated['justificativa_cancelamento'],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Solicitação cancelada com sucesso!',
            ]);
        }

        return redirect()->back()->with('success', 'Solicitação cancelada com sucesso!');
    }
    /**
     * Retornar solicitacao para analise (volta para PENDENTE)
     */
    public function returnToAnalysis(Request $request, SolicitacaoBem $solicitacao): JsonResponse|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canReturnSolicitacao($user)) {
            return $this->denyAccess($request, 'Voce nao tem permissao para retornar a solicitacao para analise.');
        }

        if ($solicitacao->status !== SolicitacaoBem::STATUS_AGUARDANDO_CONFIRMACAO) {
            return $this->denyAccess($request, 'Apenas solicitacoes aguardando confirmacao podem voltar para analise.');
        }

        $validated = $request->validate([
            'motivo_retorno' => ['required', 'string', 'max:1000'],
        ]);

        $motivo = trim((string) $validated['motivo_retorno']);
        $timestamp = now()->format('d/m/Y H:i');
        $nota = "[{$timestamp}] Retorno para analise: {$motivo}";
        $observacaoAtual = trim((string) ($solicitacao->observacao_controle ?? ''));
        $observacaoNova = $observacaoAtual !== '' ? ($observacaoAtual . "\n\n" . $nota) : $nota;
        $destinationType = $solicitacao->destination_type ?: SolicitacaoBem::DESTINATION_PROJETO;

        $statusAnterior = $solicitacao->status;
        $solicitacao->update([
            "status" => SolicitacaoBem::STATUS_PENDENTE,
            "observacao_controle" => $observacaoNova,
            "tracking_code" => null,
            "destination_type" => $destinationType,
            "matricula_recebedor" => null,
            "nome_recebedor" => null,
            "confirmado_por_id" => null,
            "confirmado_em" => null,
        ]);
        $this->registrarHistoricoStatus(
            $solicitacao,
            $statusAnterior,
            SolicitacaoBem::STATUS_PENDENTE,
            'retornar',
            $motivo
        );

        Log::info('[SOLICITACOES] Solicitacao retornada para analise', [
            'solicitacao_id' => $solicitacao->id,
            'retornado_por' => $user?->NMLOGIN,
            'motivo' => $motivo,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Solicitacao retornada para analise com sucesso!',
            ]);
        }

        return redirect()->back()->with('success', 'Solicitacao retornada para analise com sucesso!');
    }
}
