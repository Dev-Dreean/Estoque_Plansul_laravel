<?php

namespace App\Http\Controllers;

use App\Models\Tabfant;
use App\Models\SolicitacaoBem;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
        $solicitacao->load(['itens', 'projeto']);
        $statusOptions = SolicitacaoBem::statusOptions();

        $canUpdate = $this->canUpdateSolicitacao($user);

        if ($isModal) {
            return view('solicitacoes.partials.show-content', compact('solicitacao', 'statusOptions', 'isModal', 'canUpdate'));
        }

        return view('solicitacoes.show', compact('solicitacao', 'statusOptions', 'isModal', 'canUpdate'));
    }

    public function showModal(Request $request, SolicitacaoBem $solicitacao): View
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canViewSolicitacao($user, $solicitacao)) {
            abort(403, 'Você não tem permissão para visualizar esta solicitacao.');
        }

        $solicitacao->load(['itens', 'projeto']);
        $statusOptions = SolicitacaoBem::statusOptions();
        $canUpdate = $this->canUpdateSolicitacao($user);
        $isModal = true;

        return view('solicitacoes.partials.show-content', compact('solicitacao', 'statusOptions', 'isModal', 'canUpdate'));
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
            || $user->temAcessoTela((string) self::TELA_SOLICITACOES_ATUALIZAR);
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
    }

    private function canCreateSolicitacao(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }
        // Consultores sempre podem criar
        if ($user->PERFIL === User::PERFIL_CONSULTOR) {
            return true;
        }
        // USRs precisam ter permissão específica
        if ($user->PERFIL === User::PERFIL_USUARIO) {
            return $user->temAcessoTela((string) self::TELA_SOLICITACOES_CRIAR);
        }
        return false;
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

    /**
     * Confirmar solicitação (primeira confirmação)
     * Somente Tiago, Beatriz e Admin
     */
    public function confirm(Request $request, SolicitacaoBem $solicitacao): JsonResponse|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        
        // Verificar se pode confirmar (Tiago, Beatriz, Admin)
        $confirmadores = ['TIAGOP', 'BEATRIZ.SC'];
        $canConfirm = $user?->isAdmin() || in_array(strtoupper($user->NMLOGIN ?? ''), $confirmadores, true);
        
        if (!$canConfirm) {
            return $this->denyAccess($request, 'Apenas Tiago e Beatriz podem confirmar solicitações.');
        }

        if ($solicitacao->status !== SolicitacaoBem::STATUS_PENDENTE) {
            return $this->denyAccess($request, 'Apenas solicitações pendentes podem ser confirmadas.');
        }

        $validated = $request->validate([
            'tracking_code' => ['required', 'string', 'max:100'],
            'destination_type' => ['required', 'in:FILIAL,PROJETO'],
        ]);

        $solicitacao->update([
            'status' => SolicitacaoBem::STATUS_AGUARDANDO_CONFIRMACAO,
            'tracking_code' => $validated['tracking_code'],
            'destination_type' => $validated['destination_type'],
        ]);

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

        // Verificar se é o solicitante
        if (!$this->isOwner($user, $solicitacao)) {
            return $this->denyAccess($request, 'Apenas o solicitante pode aprovar a solicitação.');
        }

        if ($solicitacao->status !== SolicitacaoBem::STATUS_AGUARDANDO_CONFIRMACAO) {
            return $this->denyAccess($request, 'Apenas solicitações aguardando confirmação podem ser aprovadas.');
        }

        $solicitacao->update([
            'status' => SolicitacaoBem::STATUS_CONFIRMADO,
            'confirmado_por_id' => $user->getAuthIdentifier(),
            'confirmado_em' => now(),
        ]);

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

        // Verificar se é o solicitante
        if (!$this->isOwner($user, $solicitacao)) {
            return $this->denyAccess($request, 'Apenas o solicitante pode cancelar a solicitação.');
        }

        if ($solicitacao->status === SolicitacaoBem::STATUS_CANCELADO) {
            return $this->denyAccess($request, 'Esta solicitação já foi cancelada.');
        }

        $validated = $request->validate([
            'justificativa_cancelamento' => ['required', 'string', 'max:1000'],
        ]);

        $solicitacao->update([
            'status' => SolicitacaoBem::STATUS_CANCELADO,
            'justificativa_cancelamento' => $validated['justificativa_cancelamento'],
            'cancelado_por_id' => $user->getAuthIdentifier(),
            'cancelado_em' => now(),
        ]);

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
}