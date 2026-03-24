<?php

namespace App\Http\Controllers;

use App\Models\Tabfant;
use App\Models\Funcionario;
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
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SolicitacaoBemController extends Controller
{
    private const TELA_SOLICITACOES_BENS = 1010;
    private const TELA_SOLICITACOES_VER_TODAS = 1011;
    private const TELA_SOLICITACOES_ATUALIZAR = 1012;
    private const TELA_SOLICITACOES_CRIAR = 1013;
    private const TELA_SOLICITACOES_HISTORICO = 1016;
    private const TELA_SOLICITACOES_GERENCIAR_VISIBILIDADE = 1017;
    private const TELA_SOLICITACOES_VISUALIZACAO_RESTRITA = 1018;
    private const TELA_SOLICITACOES_TRIAGEM_INICIAL = 1019;
    private const TELA_SOLICITACOES_LIBERACAO_ENVIO = 1020;
    private const FLOW_BRUNO_MATRICULAS = ['11829'];
    private const FLOW_BRUNO_LOGINS = ['BRUNO'];
    private const FLOW_BRUNO_NAMES = ['BRUNO DE AZEVEDO FELICIANO'];
    private const FLOW_TIAGO_MATRICULAS = ['185895'];
    private const FLOW_TIAGO_LOGINS = ['TIAGOP'];
    private const FLOW_TIAGO_NAMES = ['TIAGO PACHECO'];
    private const FLOW_BEATRIZ_MATRICULAS = ['182687'];
    private const FLOW_BEATRIZ_LOGINS = ['BEA.SC'];
    private const FLOW_BEATRIZ_NAMES = ['BEATRIZ PATRICIA VIRISSIMO DOS SANTOS'];

    public function index(Request $request): View
    {
        $perPage = max(10, min(200, $request->integer('per_page', 30)));
        $query = SolicitacaoBem::query();
        $statusVisualFilters = $this->extractStatusVisualFilters($request);
        /** @var User|null $user */
        $user = Auth::user();

        if (!$this->canViewAllSolicitacoes($user)) {
            $this->applyOwnerScope($query, $user);
        }

        if (!empty($statusVisualFilters)) {
            $this->applyStatusVisualFilters($query, $statusVisualFilters);
        } elseif ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('uf')) {
            $query->where('uf', strtoupper(trim((string) $request->input('uf'))));
        }

        $searchInput = $request->input('search', '');
        $searchTerms = [];
        if (is_array($searchInput)) {
            $searchTerms = array_values(array_filter(array_map(static fn ($value) => trim((string) $value), $searchInput)));
        } else {
            $single = trim((string) $searchInput);
            if ($single !== '') {
                $searchTerms = preg_split('/[\s,|]+/', $single, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            }
        }

        if (!empty($searchTerms)) {
            foreach ($searchTerms as $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('solicitante_nome', 'like', '%' . $term . '%')
                        ->orWhere('solicitante_matricula', 'like', '%' . $term . '%')
                        ->orWhere('setor', 'like', '%' . $term . '%')
                        ->orWhere('local_destino', 'like', '%' . $term . '%')
                        ->orWhere('status', 'like', '%' . $term . '%')
                        ->orWhere('uf', 'like', '%' . $term . '%')
                        ->orWhere('id', 'like', '%' . $term . '%');
                });
            }
        }

        $sortableColumns = [
            'id' => 'id',
            'solicitante' => 'solicitante_nome',
            'setor' => 'setor',
            'local_destino' => 'local_destino',
            'uf' => 'uf',
            'status' => 'status',
            'itens' => 'itens_count',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
        $sort = (string) $request->input('sort', 'updated_at');
        if (!array_key_exists($sort, $sortableColumns)) {
            $sort = 'updated_at';
        }
        $direction = strtolower((string) $request->input('direction', 'desc'));
        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        $query->where('status', '!=', SolicitacaoBem::STATUS_ARQUIVADO);
        $query->withCount('itens');

        if (Schema::hasTable('solicitacoes_bens_status_historico')) {
            $query->with(['ultimoHistoricoStatus.usuario']);
        }

        $this->applyCurrentUserPendingPriority($query, $user);

        $solicitacoes = $query
            ->orderBy($sortableColumns[$sort], $direction)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $statusOptions = SolicitacaoBem::statusOptions();
        $projetos = $this->loadProjetosUnicos();

        if ($request->header('X-Solicitacoes-Grid') === '1') {
            return view('solicitacoes.partials.index-grid', compact('solicitacoes', 'sort', 'direction'));
        }

        return view('solicitacoes.index', compact('solicitacoes', 'statusOptions', 'projetos', 'sort', 'direction'));
    }

    private function applyCurrentUserPendingPriority($query, ?User $user): void
    {
        if (!$user) {
            return;
        }

        $isAdmin = $user->isAdmin();
        $matricula = trim((string) ($user->CDMATRFUNCIONARIO ?? ''));
        $userId = $user->getAuthIdentifier();

        $canConfirmAction = $this->canConfirmSolicitacao($user);
        $canForwardAction = $this->canForwardToLiberacao($user);
        $canQuoteAction = $this->canRegisterQuote($user);
        $canReleaseAction = $this->canAuthorizeRelease($user);
        $canSendAction = $this->canSendSolicitacao($user);
        $canReceiveAny = $isAdmin || $userId || $matricula !== '';

        $hasLogisticsSql = '('
            . 'logistics_height_cm is not null'
            . ' or logistics_width_cm is not null'
            . ' or logistics_length_cm is not null'
            . ' or logistics_weight_kg is not null'
            . ' or logistics_registered_at is not null'
            . ' or (logistics_notes is not null and logistics_notes != \'\')'
            . ')';

        $noLogisticsSql = '('
            . 'logistics_height_cm is null'
            . ' and logistics_width_cm is null'
            . ' and logistics_length_cm is null'
            . ' and logistics_weight_kg is null'
            . ' and logistics_registered_at is null'
            . ' and (logistics_notes is null or logistics_notes = \'\')'
            . ')';

        $hasShipmentSql = '('
            . '(tracking_code is not null and tracking_code != \'\')'
            . ' or (invoice_number is not null and invoice_number != \'\')'
            . ' or shipped_at is not null'
            . ')';

        $noShipmentSql = '('
            . '(tracking_code is null or tracking_code = \'\')'
            . ' and (invoice_number is null or invoice_number = \'\')'
            . ' and shipped_at is null'
            . ')';

        $cases = [];
        $bindings = [];

        if ($canConfirmAction) {
            $cases[] = 'when status = ? then 0';
            $bindings[] = SolicitacaoBem::STATUS_PENDENTE;
        }

        if ($canForwardAction) {
            $cases[] = 'when status = ? and ' . $noLogisticsSql . ' then 0';
            $bindings[] = SolicitacaoBem::STATUS_AGUARDANDO_CONFIRMACAO;
        }

        if ($canQuoteAction) {
            $cases[] = 'when status = ? and ' . $hasLogisticsSql . ' then 0';
            $bindings[] = SolicitacaoBem::STATUS_AGUARDANDO_CONFIRMACAO;
        }

        if ($canReleaseAction) {
            $cases[] = 'when status = ? and quote_approved_at is null and ' . $noShipmentSql . ' then 0';
            $bindings[] = SolicitacaoBem::STATUS_LIBERACAO;
        }

        if ($canSendAction) {
            $cases[] = 'when status = ? and quote_approved_at is not null and ' . $noShipmentSql . ' then 0';
            $bindings[] = SolicitacaoBem::STATUS_CONFIRMADO;
        }

        if ($canReceiveAny) {
            $ownerSql = '';
            $ownerBindings = [];

            if ($isAdmin) {
                $ownerSql = '1 = 1';
            } elseif ($userId && $matricula !== '') {
                $ownerSql = '(solicitante_id = ? or solicitante_matricula = ?)';
                $ownerBindings = [$userId, $matricula];
            } elseif ($userId) {
                $ownerSql = 'solicitante_id = ?';
                $ownerBindings = [$userId];
            } elseif ($matricula !== '') {
                $ownerSql = 'solicitante_matricula = ?';
                $ownerBindings = [$matricula];
            }

            if ($ownerSql !== '') {
                $cases[] = 'when status = ? and ' . $hasShipmentSql . ' and ' . $ownerSql . ' then 0';
                $bindings[] = SolicitacaoBem::STATUS_CONFIRMADO;
                array_push($bindings, ...$ownerBindings);
            }
        }

        if (empty($cases)) {
            return;
        }

        $query->orderByRaw(
            'case ' . implode(' ', $cases) . ' else 1 end asc',
            $bindings
        );
    }

    public function create(Request $request): View|JsonResponse|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canCreateSolicitacao($user)) {
            return $this->denyAccess($request, 'Você não tem permissão para criar solicitação.');
        }
        if (!$this->solicitanteMatriculaValida($user)) {
            return $this->responderMatriculaInvalida($request);
        }

        $isModal = $request->input('modal') === '1';
        $projetos = $this->loadProjetosUnicos();
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
            return $this->denyAccess($request, 'Você não tem permissão para criar solicitação.');
        }
        if (!$this->solicitanteMatriculaValida($user)) {
            return $this->responderMatriculaInvalida($request);
        }

        $rules = [
            'solicitante_nome' => ['required', 'string', 'max:120'],
            'solicitante_matricula' => ['nullable', 'string', 'max:20'],
            'recebedor_matricula' => ['required', 'string', 'max:20', 'exists:funcionarios,CDMATRFUNCIONARIO'],
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

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            if ($request->input('modal') === '1' || $request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        $user = Auth::user();
        $uf = strtoupper(trim((string) ($validated['uf'] ?? '')));
        $matricula = trim((string) ($validated['solicitante_matricula'] ?? ''));
        $recebedorMatricula = trim((string) ($validated['recebedor_matricula'] ?? ''));
        $observacao = trim((string) ($validated['observacao'] ?? ''));
        $recebedor = null;
        if ($recebedorMatricula !== '') {
            $recebedor = Funcionario::query()
                ->select(['CDMATRFUNCIONARIO', 'NMFUNCIONARIO'])
                ->where('CDMATRFUNCIONARIO', $recebedorMatricula)
                ->first();
        }

        $solicitacao = null;

        DB::transaction(function () use ($validated, $user, $uf, $matricula, $recebedorMatricula, $recebedor, $observacao, &$solicitacao) {
            $solicitacao = SolicitacaoBem::create([
                'solicitante_id' => $user?->getAuthIdentifier(),
                'solicitante_nome' => trim((string) $validated['solicitante_nome']),
                'solicitante_matricula' => $matricula !== '' ? $matricula : null,
                'matricula_recebedor' => $recebedor?->CDMATRFUNCIONARIO ?? ($recebedorMatricula !== '' ? $recebedorMatricula : null),
                'nome_recebedor' => $recebedor?->NMFUNCIONARIO ?? trim((string) $validated['solicitante_nome']),
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
                'message' => 'Solicitação registrada com sucesso.',
                'redirect' => route('solicitacoes-bens.index'),
            ]);
        }

        return redirect()
            ->route('solicitacoes-bens.index')
            ->with('success', 'Solicitação registrada com sucesso.');
    }

    public function show(Request $request, SolicitacaoBem $solicitacao): View|JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canViewSolicitacao($user, $solicitacao)) {
            return $this->denyAccess($request, 'Você não tem permissão para visualizar esta Solicitação.');
        }

        $isModal = $request->input('modal') === '1';
        $relations = ['itens', 'projeto'];
        if (Schema::hasTable('solicitacoes_bens_status_historico')) {
            $relations[] = 'historicoStatus.usuario';
        }
        $solicitacao->load($relations);
        $statusOptions = SolicitacaoBem::statusOptions();
        $projetos = $this->loadProjetosUnicos();
        $canManagePermissoes = $this->canManagePermissoes($user) && $this->canViewSolicitacao($user, $solicitacao);
        $usuariosDisponiveisPermissao = $this->listarUsuariosDisponiveisParaPermissao($solicitacao);
        $usuariosComPermissao = $this->listarUsuariosComPermissao($solicitacao);
        $canOwnerEditPending = $this->canEditAsOwnerPending($user, $solicitacao);
        $canRecriarCancelada = $this->canRecreateCancelled($user, $solicitacao);

        $canConfirmAction = $this->canConfirmSolicitacao($user);
        $canForwardAction = $this->canForwardToLiberacao($user);
        $canQuoteAction = $this->canRegisterQuote($user);
        $canReleaseAction = $this->canAuthorizeRelease($user);
        $canSendAction = $this->canSendSolicitacao($user);
        $canCancelAction = $this->canCancelSolicitacao($user, $solicitacao);
        $canReturnAction = $this->canReturnSolicitacao($user, $solicitacao);
        $canManage = $this->canUpdateSolicitacao($user)
            || $canConfirmAction
            || $canForwardAction
            || $canQuoteAction
            || $canReleaseAction
            || $canSendAction
            || $canCancelAction
            || $canReturnAction;
        $canContestNotReceived = $this->canContestNotReceived($user);

        if ($isModal) {
            return view('solicitacoes.partials.show-content', compact(
                'solicitacao',
                'statusOptions',
                'isModal',
                'canManage',
                'canConfirmAction',
                'canForwardAction',
                'canQuoteAction',
                'canReleaseAction',
                'canSendAction',
                'canCancelAction',
                'canReturnAction',
                'canContestNotReceived',
                'canOwnerEditPending',
                'canRecriarCancelada',
                'projetos',
                'canManagePermissoes',
                'usuariosDisponiveisPermissao',
                'usuariosComPermissao'
            ));
        }

        return view('solicitacoes.show', compact(
            'solicitacao',
            'statusOptions',
            'isModal',
            'canManage',
            'canConfirmAction',
            'canForwardAction',
            'canQuoteAction',
            'canReleaseAction',
            'canSendAction',
            'canCancelAction',
            'canReturnAction',
            'canContestNotReceived',
            'canOwnerEditPending',
            'canRecriarCancelada',
            'projetos',
            'canManagePermissoes',
            'usuariosDisponiveisPermissao',
            'usuariosComPermissao'
        ));
    }

    public function historico(Request $request): View
    {
        $perPage = max(10, min(200, $request->integer('per_page', 30)));
        $statusOptions = SolicitacaoBem::statusOptions();
        $historicoDisponivel = Schema::hasTable('solicitacoes_bens_status_historico');
        if (!$historicoDisponivel) {
            $solicitacoes = new \Illuminate\Pagination\LengthAwarePaginator(
                collect(),
                0,
                $perPage,
                1,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );

            return view('solicitacoes.historico', compact(
                'solicitacoes',
                'historicoDisponivel',
                'statusOptions'
            ));
        }

        $query = SolicitacaoBem::query()
            ->with([
                'projeto:id,CDPROJETO,NOMEPROJETO',
                'historicoStatus' => function ($q) {
                    $q->with('usuario:NUSEQUSUARIO,NOMEUSER,NMLOGIN')
                        ->orderByDesc('created_at');
                },
            ])
            ->withMax('historicoStatus as ultima_movimentacao_em', 'created_at')
            ->whereHas('historicoStatus');

        if ($request->filled('solicitacao_id')) {
            $query->where('id', (int) $request->input('solicitacao_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', trim((string) $request->input('status')));
        }

        if ($request->filled('data_inicio')) {
            $dataInicio = (string) $request->input('data_inicio');
            $query->whereHas('historicoStatus', function ($q) use ($dataInicio) {
                $q->whereDate('created_at', '>=', $dataInicio);
            });
        }

        if ($request->filled('data_fim')) {
            $dataFim = (string) $request->input('data_fim');
            $query->whereHas('historicoStatus', function ($q) use ($dataFim) {
                $q->whereDate('created_at', '<=', $dataFim);
            });
        }

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('solicitante_nome', 'like', '%' . $term . '%')
                    ->orWhere('local_destino', 'like', '%' . $term . '%')
                    ->orWhereHas('historicoStatus', function ($qh) use ($term) {
                        $qh->where('motivo', 'like', '%' . $term . '%')
                            ->orWhere('status_novo', 'like', '%' . $term . '%')
                            ->orWhereHas('usuario', function ($qu) use ($term) {
                                $qu->where('NOMEUSER', 'like', '%' . $term . '%')
                                    ->orWhere('NMLOGIN', 'like', '%' . $term . '%');
                            });
                    });
            });
        }

        $solicitacoes = $query
            ->orderByDesc('ultima_movimentacao_em')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('solicitacoes.historico', compact(
            'solicitacoes',
            'historicoDisponivel',
            'statusOptions'
        ));
    }

    public function showModal(Request $request, SolicitacaoBem $solicitacao)
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canViewSolicitacao($user, $solicitacao)) {
            abort(403, 'Você não tem permissão para visualizar esta Solicitação.');
        }

        try {
            $relations = ['itens', 'projeto'];
            if (Schema::hasTable('solicitacoes_bens_status_historico')) {
                $relations[] = 'historicoStatus.usuario';
            }
            $solicitacao->load($relations);
            $statusOptions = SolicitacaoBem::statusOptions();
            $projetos = $this->loadProjetosUnicos();
            $canManagePermissoes = $this->canManagePermissoes($user) && $this->canViewSolicitacao($user, $solicitacao);
            $usuariosDisponiveisPermissao = $this->listarUsuariosDisponiveisParaPermissao($solicitacao);
            $usuariosComPermissao = $this->listarUsuariosComPermissao($solicitacao);
            $canOwnerEditPending = $this->canEditAsOwnerPending($user, $solicitacao);
            $canRecriarCancelada = $this->canRecreateCancelled($user, $solicitacao);
            $canConfirmAction = $this->canConfirmSolicitacao($user);
            $canForwardAction = $this->canForwardToLiberacao($user);
            $canQuoteAction = $this->canRegisterQuote($user);
            $canReleaseAction = $this->canAuthorizeRelease($user);
            $canSendAction = $this->canSendSolicitacao($user);
            $canCancelAction = $this->canCancelSolicitacao($user, $solicitacao);
            $canReturnAction = $this->canReturnSolicitacao($user, $solicitacao);
            $canManage = $this->canUpdateSolicitacao($user)
                || $canConfirmAction
                || $canForwardAction
                || $canQuoteAction
                || $canReleaseAction
                || $canSendAction
                || $canCancelAction
                || $canReturnAction;
            $canContestNotReceived = $this->canContestNotReceived($user);
            $isModal = true;

            // Renderiza aqui para capturar qualquer falha de Blade e evitar HTTP 500 na modal.
            $html = view('solicitacoes.partials.show-content', compact(
                'solicitacao',
                'statusOptions',
                'isModal',
                'canManage',
                'canConfirmAction',
                'canForwardAction',
                'canQuoteAction',
                'canReleaseAction',
                'canSendAction',
                'canCancelAction',
                'canReturnAction',
                'canContestNotReceived',
                'canOwnerEditPending',
                'canRecriarCancelada',
                'projetos',
                'canManagePermissoes',
                'usuariosDisponiveisPermissao',
                'usuariosComPermissao'
            ))->render();
            return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
        } catch (\Throwable $e) {
            Log::error('[SOLICITACAO] Falha ao renderizar modal de visualizacao', [
                'solicitacao_id' => $solicitacao->id,
                'user_id' => $user?->getAuthIdentifier(),
                'error' => $e->getMessage(),
            ]);

            $id = (int) $solicitacao->id;
            $status = e((string) ($solicitacao->status ?? '-'));
            $solicitante = e((string) ($solicitacao->solicitante_nome ?? '-'));

            $fallbackHtml = <<<HTML
<div class="p-4">
    <div class="rounded-lg border border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/30 text-red-900 dark:text-red-200 px-4 py-3">
        <h3 class="text-sm font-semibold mb-1">Erro ao carregar Solicitação #{$id}</h3>
        <p class="text-xs">Não foi possível carregar os detalhes desta Solicitação.</p>
        <p class="text-xs mt-1">Solicitante: {$solicitante} | Status: {$status}</p>
        <p class="text-xs mt-2 text-red-700 dark:text-red-300">Tente novamente. Se o problema persistir, contate o administrador.</p>
    </div>
</div>
HTML;

            return response($fallbackHtml, 500)
                ->header('Content-Type', 'text/html; charset=UTF-8')
                ->header('X-Solicitacao-Modal-Error', '1');
        }
    }

    public function update(Request $request, SolicitacaoBem $solicitacao): RedirectResponse|JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canViewSolicitacao($user, $solicitacao)) {
            return $this->denyAccess($request, 'Você não tem permissão para atualizar esta solicitação.');
        }

        $canOwnerEditPending = $this->canEditAsOwnerPending($user, $solicitacao);
        if (!$this->canUpdateSolicitacao($user) && !$canOwnerEditPending) {
            return $this->denyAccess($request, 'Você não tem permissão para atualizar esta solicitação.');
        }

        if ($request->boolean('owner_edit') && $canOwnerEditPending) {
            $validated = $request->validate([
                'solicitante_nome' => ['required', 'string', 'max:120'],
                'recebedor_matricula' => ['required', 'string', 'max:20', 'exists:funcionarios,CDMATRFUNCIONARIO'],
                'projeto_id' => ['required', 'integer', 'exists:tabfant,id'],
                'local_destino' => ['required', 'string', 'max:150'],
                'observacao' => ['nullable', 'string', 'max:2000'],
                'itens' => ['required', 'array', 'min:1'],
                'itens.*.descricao' => ['required', 'string', 'max:200'],
                'itens.*.quantidade' => ['required', 'integer', 'min:1'],
                'itens.*.unidade' => ['nullable', 'string', 'max:20'],
                'itens.*.observacao' => ['nullable', 'string', 'max:500'],
            ]);

            $recebedorMatricula = trim((string) $validated['recebedor_matricula']);
            $recebedor = Funcionario::query()
                ->select(['CDMATRFUNCIONARIO', 'NMFUNCIONARIO'])
                ->where('CDMATRFUNCIONARIO', $recebedorMatricula)
                ->first();

            DB::transaction(function () use ($solicitacao, $validated, $recebedor, $recebedorMatricula) {
                $solicitacao->fill([
                    'solicitante_nome' => trim((string) $validated['solicitante_nome']),
                    'matricula_recebedor' => $recebedor?->CDMATRFUNCIONARIO ?? $recebedorMatricula,
                    'nome_recebedor' => $recebedor?->NMFUNCIONARIO ?? trim((string) $validated['solicitante_nome']),
                    'projeto_id' => (int) $validated['projeto_id'],
                    'local_destino' => trim((string) $validated['local_destino']),
                    'observacao' => trim((string) ($validated['observacao'] ?? '')) ?: null,
                ]);
                $solicitacao->save();

                $itens = collect($validated['itens'] ?? [])->map(function (array $item): array {
                    return [
                        'descricao' => trim((string) ($item['descricao'] ?? '')),
                        'quantidade' => (int) ($item['quantidade'] ?? 1),
                        'unidade' => trim((string) ($item['unidade'] ?? '')) ?: null,
                        'observacao' => trim((string) ($item['observacao'] ?? '')) ?: null,
                    ];
                })->values();

                $solicitacao->itens()->delete();
                if ($itens->isNotEmpty()) {
                    $solicitacao->itens()->createMany($itens->all());
                }
            });

            if ($request->input('modal') === '1' || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Solicitação atualizada com sucesso.',
                ]);
            }

            return redirect()
                ->route('solicitacoes-bens.index')
                ->with('success', 'Solicitação atualizada com sucesso.');
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

        if (!empty($data['recebedor_matricula'])) {
            $funcionario = Funcionario::where('CDMATRFUNCIONARIO', $data['recebedor_matricula'])->first();
            if ($funcionario) {
                $data['matricula_recebedor'] = $funcionario->CDMATRFUNCIONARIO;
                $data['nome_recebedor'] = $funcionario->NMFUNCIONARIO;
            }
        }
        unset($data['recebedor_matricula']);

        $solicitacao->fill($data);
        $solicitacao->save();

        if ($request->input('modal') === '1') {
            return response()->json([
                'success' => true,
                'message' => 'Solicitação atualizada com sucesso.',
            ]);
        }

        return redirect()
            ->route('solicitacoes-bens.index')
            ->with('success', 'Solicitação atualizada com sucesso.');
    }

    public function destroy(Request $request, SolicitacaoBem $solicitacao): RedirectResponse|JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canDeleteSolicitacao($user, $solicitacao)) {
            return $this->denyAccess($request, 'Você não tem permissão para remover esta Solicitação.');
        }

        DB::transaction(function () use ($solicitacao) {
            $solicitacao->itens()->delete();
            $solicitacao->delete();
        });

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Solicitação removida com sucesso.',
            ]);
        }

        return redirect()
            ->route('solicitacoes-bens.index')
            ->with('success', 'Solicitação removida com sucesso.');
    }

    public function grantViewer(Request $request, SolicitacaoBem $solicitacao): RedirectResponse|JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canManagePermissoes($user) || !$this->canViewSolicitacao($user, $solicitacao)) {
            return $this->denyAccess($request, 'Você não tem permissão para liberar visualização desta Solicitação.');
        }
        if (!Schema::hasTable('solicitacoes_bens_permissoes')) {
            return $this->permissionResponse($request, false, 'A tabela de permissões ainda não foi criada.', 422);
        }

        $validator = Validator::make($request->all(), [
            'usuario_ids' => ['nullable', 'array', 'min:1'],
            'usuario_ids.*' => ['integer', 'distinct', 'exists:usuario,NUSEQUSUARIO'],
            'usuario_id' => ['nullable', 'integer', 'exists:usuario,NUSEQUSUARIO'],
        ]);

        if ($validator->fails()) {
            return $this->permissionResponse($request, false, $validator->errors()->first(), 422);
        }

        $validated = $validator->validated();
        $usuarioIds = collect($validated['usuario_ids'] ?? [])
            ->push($validated['usuario_id'] ?? null)
            ->filter(static fn ($id) => $id !== null && $id !== '')
            ->map(static fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($usuarioIds->isEmpty()) {
            return $this->permissionResponse($request, false, 'Selecione ao menos um usuário para liberar acesso.', 422);
        }

        $usuariosValidos = User::query()
            ->whereIn('NUSEQUSUARIO', $usuarioIds->all())
            ->pluck('NUSEQUSUARIO')
            ->map(static fn ($id) => (int) $id)
            ->all();

        if (empty($usuariosValidos)) {
            return $this->permissionResponse($request, false, 'Nenhum usuário válido foi encontrado para liberação.', 404);
        }

        $timestamp = now();
        foreach ($usuariosValidos as $usuarioId) {
            DB::table('solicitacoes_bens_permissoes')->updateOrInsert(
                [
                    'solicitacao_id' => $solicitacao->id,
                    'usuario_id' => $usuarioId,
                ],
                [
                    'liberado_por_id' => $user?->getAuthIdentifier(),
                    'updated_at' => $timestamp,
                    'created_at' => $timestamp,
                ]
            );
        }

        $total = count($usuariosValidos);
        $mensagem = $total === 1
            ? 'Visualização liberada com sucesso.'
            : "Visualização liberada com sucesso para {$total} usuários.";

        return $this->permissionResponse($request, true, $mensagem);
    }

    public function revokeViewer(Request $request, SolicitacaoBem $solicitacao, User $usuario): RedirectResponse|JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canManagePermissoes($user) || !$this->canViewSolicitacao($user, $solicitacao)) {
            return $this->denyAccess($request, 'Você não tem permissão para remover visualização desta Solicitação.');
        }
        if (!Schema::hasTable('solicitacoes_bens_permissoes')) {
            return $this->permissionResponse($request, false, 'A tabela de permissões ainda não foi criada.', 422);
        }

        $deleted = DB::table('solicitacoes_bens_permissoes')
            ->where('solicitacao_id', $solicitacao->id)
            ->where('usuario_id', $usuario->getAuthIdentifier())
            ->delete();

        if ($deleted < 1) {
            return $this->permissionResponse($request, false, 'O usuário não possui permissão ativa nesta Solicitação.', 422);
        }

        return $this->permissionResponse($request, true, 'Visualização removida com sucesso.');
    }

    public function revokeViewerModal(Request $request, SolicitacaoBem $solicitacao): RedirectResponse|JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canManagePermissoes($user) || !$this->canViewSolicitacao($user, $solicitacao)) {
            return $this->denyAccess($request, 'Você não tem permissão para remover visualização desta Solicitação.');
        }
        if (!Schema::hasTable('solicitacoes_bens_permissoes')) {
            return $this->permissionResponse($request, false, 'A tabela de permissões ainda não foi criada.', 422);
        }

        $validator = Validator::make($request->all(), [
            'usuario_id' => ['required', 'integer', 'exists:usuario,NUSEQUSUARIO'],
        ]);

        if ($validator->fails()) {
            return $this->permissionResponse($request, false, $validator->errors()->first(), 422);
        }

        $usuarioId = (int) $validator->validated()['usuario_id'];

        $deleted = DB::table('solicitacoes_bens_permissoes')
            ->where('solicitacao_id', $solicitacao->id)
            ->where('usuario_id', $usuarioId)
            ->delete();

        if ($deleted < 1) {
            return $this->permissionResponse($request, false, 'O usuário não possui permissão ativa nesta Solicitação.', 422);
        }

        return $this->permissionResponse($request, true, 'Visualização removida com sucesso.');
    }

    private function canViewAllSolicitacoes(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }

        return $this->canTriagemInicial($user)
            || $user->temAcessoTela((string) self::TELA_SOLICITACOES_VER_TODAS)
            || $user->temAcessoTela((string) self::TELA_SOLICITACOES_ATUALIZAR)
            || $user->temAcessoTela((string) User::TELA_SOLICITACOES_APROVAR)
            || $user->temAcessoTela((string) User::TELA_SOLICITACOES_CANCELAR)
            || $user->temAcessoTela((string) self::TELA_SOLICITACOES_LIBERACAO_ENVIO);
    }

    private function isVisualizacaoRestrita(?User $user): bool
    {
        if (!$user || $user->isAdmin()) {
            return false;
        }

        return $user->temAcessoTela((string) self::TELA_SOLICITACOES_VISUALIZACAO_RESTRITA);
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
            $nomeUsuario = $this->normalizarNome((string) ($user->NOMEUSER ?? ''));
            $nomeSolicitante = $this->normalizarNome((string) ($solicitacao->solicitante_nome ?? ''));

            return $nomeUsuario !== '' && $nomeUsuario === $nomeSolicitante;
        }

        if (trim((string) ($solicitacao->solicitante_matricula ?? '')) === $matricula) {
            return true;
        }

        $nomeUsuario = $this->normalizarNome((string) ($user->NOMEUSER ?? ''));
        $nomeSolicitante = $this->normalizarNome((string) ($solicitacao->solicitante_nome ?? ''));

        return $nomeUsuario !== '' && $nomeUsuario === $nomeSolicitante;
    }

    private function canViewSolicitacao(?User $user, SolicitacaoBem $solicitacao): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($this->canViewAllSolicitacoes($user)) {
            return true;
        }

        if ($this->isOwner($user, $solicitacao)) {
            return true;
        }

        return $this->temPermissaoVisualizacao($user, $solicitacao);
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

    private function canReceiveSolicitacao(?User $user, SolicitacaoBem $solicitacao): bool
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

    private function canEditAsOwnerPending(?User $user, SolicitacaoBem $solicitacao): bool
    {
        if (!$user) {
            return false;
        }

        return $solicitacao->status === SolicitacaoBem::STATUS_PENDENTE
            && $this->isOwner($user, $solicitacao);
    }

    private function canCreateSolicitacao(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        // ADM sempre tem acesso (nunca precisa de permissão explícita)
        if ($user->isAdmin()) {
            return true;
        }
        return $user->temAcessoTela((string) self::TELA_SOLICITACOES_CRIAR);
    }

    private function canRecreateCancelled(?User $user, SolicitacaoBem $solicitacao): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }

        return $solicitacao->status === SolicitacaoBem::STATUS_CANCELADO
            && $this->isOwner($user, $solicitacao)
            && $this->canCreateSolicitacao($user);
    }

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

        $nome = $this->normalizarNome((string) ($user->NOMEUSER ?? ''));
        return $nome !== '' && in_array($nome, $names, true);
    }

    private function isBrunoFlowOperator(?User $user): bool
    {
        return $this->isFlowOperator($user, self::FLOW_BRUNO_MATRICULAS, self::FLOW_BRUNO_LOGINS, self::FLOW_BRUNO_NAMES);
    }

    private function isTiagoFlowOperator(?User $user): bool
    {
        return $this->isFlowOperator($user, self::FLOW_TIAGO_MATRICULAS, self::FLOW_TIAGO_LOGINS, self::FLOW_TIAGO_NAMES);
    }

    private function isBeatrizFlowOperator(?User $user): bool
    {
        return $this->isFlowOperator($user, self::FLOW_BEATRIZ_MATRICULAS, self::FLOW_BEATRIZ_LOGINS, self::FLOW_BEATRIZ_NAMES);
    }

    private function canConfirmSolicitacao(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        // ADM sempre tem acesso (nunca precisa de permissão explícita)
        if ($user->isAdmin()) {
            return true;
        }
        return $this->canTriagemInicial($user)
            && ($this->isTiagoFlowOperator($user) || $this->isBeatrizFlowOperator($user));
    }

    private function canForwardToLiberacao(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        // ADM sempre tem acesso (nunca precisa de permissão explícita)
        if ($user->isAdmin()) {
            return true;
        }
        return $user->temAcessoTela((string) User::TELA_SOLICITACOES_ATUALIZAR)
            && $this->isTiagoFlowOperator($user);
    }

    private function canRegisterQuote(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }

        return $user->temAcessoTela((string) User::TELA_SOLICITACOES_ATUALIZAR)
            && $this->isBeatrizFlowOperator($user);
    }

    private function canAuthorizeRelease(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }

        return $user->temAcessoTela((string) self::TELA_SOLICITACOES_LIBERACAO_ENVIO)
            && $this->isBrunoFlowOperator($user);
    }

    private function canSendSolicitacao(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }

        return $user->temAcessoTela((string) User::TELA_SOLICITACOES_APROVAR)
            && ($this->isTiagoFlowOperator($user) || $this->isBeatrizFlowOperator($user));
    }

    private function canDecideQuote(?User $user, SolicitacaoBem $solicitacao): bool
    {
        return $this->canAuthorizeRelease($user);
    }

    private function canManageCurrentFlowStage(?User $user, SolicitacaoBem $solicitacao): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($solicitacao->status === SolicitacaoBem::STATUS_PENDENTE) {
            return $this->canConfirmSolicitacao($user);
        }

        if ($solicitacao->status === SolicitacaoBem::STATUS_AGUARDANDO_CONFIRMACAO) {
            return $solicitacao->hasLogisticsData()
                ? $this->canRegisterQuote($user)
                : $this->canForwardToLiberacao($user);
        }

        if ($solicitacao->status === SolicitacaoBem::STATUS_LIBERACAO) {
            return $this->canAuthorizeRelease($user);
        }

        if ($solicitacao->status === SolicitacaoBem::STATUS_CONFIRMADO) {
            if ($solicitacao->hasShipmentData()) {
                return $this->canReceiveSolicitacao($user, $solicitacao);
            }

            return $this->canSendSolicitacao($user);
        }

        if ($solicitacao->status === SolicitacaoBem::STATUS_NAO_RECEBIDO) {
            return $this->canReceiveSolicitacao($user, $solicitacao);
        }

        if ($solicitacao->status === SolicitacaoBem::STATUS_NAO_ENVIADO) {
            return $this->canConfirmSolicitacao($user);
        }

        return false;
    }

    private function normalizeQuoteOptions(array $quotes): array
    {
        return array_values(array_filter(array_map(function ($item) {
            if (!is_array($item)) {
                return null;
            }

            $transporter = trim((string) ($item['transporter'] ?? ''));
            $deadline = trim((string) ($item['deadline'] ?? ''));
            $trackingType = trim((string) ($item['tracking_type'] ?? ''));
            $notes = trim((string) ($item['notes'] ?? ''));
            $amountRaw = $item['amount'] ?? null;
            $amount = $amountRaw !== null && $amountRaw !== '' ? (float) $amountRaw : null;

            if ($transporter === '' && $deadline === '' && $trackingType === '' && $notes === '' && $amount === null) {
                return null;
            }

            if ($transporter === '' || $deadline === '' || $trackingType === '' || $amount === null) {
                return [
                    '_invalid' => true,
                ];
            }

            return [
                'transporter' => $transporter,
                'amount' => $amount,
                'deadline' => $deadline,
                'tracking_type' => $trackingType,
                'notes' => $notes !== '' ? $notes : null,
            ];
        }, $quotes)));
    }

    private function canCancelSolicitacao(?User $user, ?SolicitacaoBem $solicitacao = null): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }

        if (!$solicitacao) {
            return false;
        }

        if ($solicitacao->hasShipmentData()) {
            return false;
        }

        return $this->canManageCurrentFlowStage($user, $solicitacao);
    }

    private function canReturnSolicitacao(?User $user, ?SolicitacaoBem $solicitacao = null): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }

        if (!$solicitacao) {
            return false;
        }

        if ($solicitacao->hasShipmentData()) {
            return false;
        }

        return $this->canManageCurrentFlowStage($user, $solicitacao);
    }

    private function applyOwnerScope($query, ?User $user): void
    {
        if (!$user) {
            $query->whereRaw('1=0');
            return;
        }

        $userId = $user->getAuthIdentifier();
        $matricula = trim((string) ($user->CDMATRFUNCIONARIO ?? ''));
        $nomeUsuario = $this->normalizarNome((string) ($user->NOMEUSER ?? ''));
        $hasOwnerIdentifier = (bool) $userId || $matricula !== '' || $nomeUsuario !== '';
        $canUseExplicitPermission = (bool) $userId && Schema::hasTable('solicitacoes_bens_permissoes');
        $statusVisiveis = $this->statusVisiveisParaSolicitante();

        if (!$hasOwnerIdentifier && !$canUseExplicitPermission) {
            $query->whereRaw('1=0');
            return;
        }

        $query->where(function ($builder) use (
            $hasOwnerIdentifier,
            $userId,
            $matricula,
            $nomeUsuario,
            $statusVisiveis,
            $canUseExplicitPermission
        ) {
            if ($hasOwnerIdentifier) {
                $builder->where(function ($ownerScope) use ($userId, $matricula, $nomeUsuario, $statusVisiveis) {
                    $ownerScope->where(function ($ownerMatch) use ($userId, $matricula, $nomeUsuario) {
                        if ($userId) {
                            $ownerMatch->where('solicitante_id', $userId);
                        }
                        if ($matricula !== '') {
                            if ($userId) {
                                $ownerMatch->orWhere('solicitante_matricula', $matricula);
                            } else {
                                $ownerMatch->where('solicitante_matricula', $matricula);
                            }
                        }
                        if ($nomeUsuario !== '') {
                            if ($userId || $matricula !== '') {
                                $ownerMatch->orWhereRaw('TRIM(UPPER(solicitante_nome)) = ?', [$nomeUsuario]);
                            } else {
                                $ownerMatch->whereRaw('TRIM(UPPER(solicitante_nome)) = ?', [$nomeUsuario]);
                            }
                        }
                    });

                    // Solicitante comum enxerga apenas solicitações próprias em andamento
                    // e as canceladas (para poder reenviar corrigindo motivo).
                    $ownerScope->whereIn('status', $statusVisiveis);
                });
            }

            if ($canUseExplicitPermission) {
                $builder->orWhereExists(function ($sub) use ($userId) {
                    $sub->selectRaw('1')
                        ->from('solicitacoes_bens_permissoes as sbp')
                        ->whereColumn('sbp.solicitacao_id', 'solicitacoes_bens.id')
                        ->where('sbp.usuario_id', $userId);
                });
            }
        });
    }

    private function statusVisiveisParaSolicitante(): array
    {
        return [
            SolicitacaoBem::STATUS_PENDENTE,
            SolicitacaoBem::STATUS_AGUARDANDO_CONFIRMACAO,
            SolicitacaoBem::STATUS_LIBERACAO,
            SolicitacaoBem::STATUS_CONFIRMADO,
            SolicitacaoBem::STATUS_NAO_ENVIADO,
            SolicitacaoBem::STATUS_NAO_RECEBIDO,
            SolicitacaoBem::STATUS_RECEBIDO,
            SolicitacaoBem::STATUS_CANCELADO,
        ];
    }

    private function canManagePermissoes(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }

        return $this->canTriagemInicial($user);
    }

    private function canContestNotReceived(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $this->canTriagemInicial($user);
    }

    private function canTriagemInicial(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->temAcessoTela((string) self::TELA_SOLICITACOES_TRIAGEM_INICIAL);
    }

    private function isLiberacaoOnlyOperator(?User $user): bool
    {
        if (!$user || $user->isAdmin()) {
            return false;
        }

        if (!$user->temAcessoTela((string) self::TELA_SOLICITACOES_LIBERACAO_ENVIO)) {
            return false;
        }

        return !$this->canTriagemInicial($user)
            && !$user->temAcessoTela((string) self::TELA_SOLICITACOES_ATUALIZAR)
            && !$user->temAcessoTela((string) User::TELA_SOLICITACOES_APROVAR);
    }

    private function temPermissaoVisualizacao(?User $user, SolicitacaoBem $solicitacao): bool
    {
        if (!$user || !Schema::hasTable('solicitacoes_bens_permissoes')) {
            return false;
        }

        $userId = $user->getAuthIdentifier();
        if (!$userId) {
            return false;
        }

        return DB::table('solicitacoes_bens_permissoes')
            ->where('solicitacao_id', $solicitacao->id)
            ->where('usuario_id', $userId)
            ->exists();
    }

    private function listarUsuariosDisponiveisParaPermissao(SolicitacaoBem $solicitacao)
    {
        if (!Schema::hasTable('solicitacoes_bens_permissoes')) {
            return collect();
        }

        $idsLiberados = DB::table('solicitacoes_bens_permissoes')
            ->where('solicitacao_id', $solicitacao->id)
            ->pluck('usuario_id')
            ->map(static fn ($value) => (int) $value)
            ->all();

        $telasSolicitacoes = [
            self::TELA_SOLICITACOES_BENS,
            self::TELA_SOLICITACOES_VER_TODAS,
            self::TELA_SOLICITACOES_ATUALIZAR,
            self::TELA_SOLICITACOES_CRIAR,
            User::TELA_SOLICITACOES_APROVAR,
            User::TELA_SOLICITACOES_CANCELAR,
            self::TELA_SOLICITACOES_HISTORICO,
            self::TELA_SOLICITACOES_GERENCIAR_VISIBILIDADE,
            self::TELA_SOLICITACOES_VISUALIZACAO_RESTRITA,
            self::TELA_SOLICITACOES_TRIAGEM_INICIAL,
        ];

        return User::query()
            ->select(['NUSEQUSUARIO', 'NOMEUSER', 'NMLOGIN', 'PERFIL'])
            ->whereIn('LGATIVO', ['S', '1'])
            ->whereNotNull('NOMEUSER')
            ->whereNotIn('NUSEQUSUARIO', $idsLiberados)
            ->where(function ($query) use ($telasSolicitacoes) {
                $query->where('PERFIL', User::PERFIL_ADMIN)
                    ->orWhereExists(function ($sub) use ($telasSolicitacoes) {
                        $sub->selectRaw('1')
                            ->from('acessousuario as au')
                            ->whereColumn('au.CDMATRFUNCIONARIO', 'usuario.CDMATRFUNCIONARIO')
                            ->whereIn('au.NUSEQTELA', $telasSolicitacoes)
                            ->whereRaw("TRIM(UPPER(au.INACESSO)) = 'S'");
                    });
            })
            ->orderBy('NOMEUSER')
            ->get();
    }

    private function listarUsuariosComPermissao(SolicitacaoBem $solicitacao)
    {
        if (!Schema::hasTable('solicitacoes_bens_permissoes')) {
            return collect();
        }

        return DB::table('solicitacoes_bens_permissoes as sbp')
            ->join('usuario as u', 'u.NUSEQUSUARIO', '=', 'sbp.usuario_id')
            ->leftJoin('usuario as liberador', 'liberador.NUSEQUSUARIO', '=', 'sbp.liberado_por_id')
            ->where('sbp.solicitacao_id', $solicitacao->id)
            ->orderBy('u.NOMEUSER')
            ->get([
                'u.NUSEQUSUARIO as id',
                'u.NOMEUSER as nome',
                'u.NMLOGIN as login',
                'u.PERFIL as perfil',
                'liberador.NOMEUSER as liberado_por_nome',
                'sbp.created_at',
            ]);
    }

    private function permissionResponse(Request $request, bool $success, string $message, int $status = 200): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson() || $request->input('modal') === '1') {
            return response()->json([
                'success' => $success,
                'message' => $message,
            ], $status);
        }

        if ($success) {
            return redirect()->back()->with('success', $message);
        }

        return redirect()->back()->with('error', $message);
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

        $subject = 'Solicitação de bens recebida #' . $solicitacao->id;
        $body = implode("\n", [
            'Uma nova Solicitação de bens foi registrada.',
            'Número: ' . $solicitacao->id,
            'Solicitante: ' . ($solicitacao->solicitante_nome ?? '-'),
            'Matrícula: ' . ($solicitacao->solicitante_matricula ?? '-'),
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
            Log::warning('Falha ao enviar e-mail de Solicitação de bens', [
                'solicitacao_id' => $solicitacao->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Evita projetos duplicados no dropdown (mesmo código/nome com IDs diferentes).
     */
    private function loadProjetosUnicos()
    {
        return Tabfant::query()
            ->selectRaw('MIN(id) as id, CDPROJETO, NOMEPROJETO')
            ->whereNotNull('CDPROJETO')
            ->whereNotNull('NOMEPROJETO')
            ->groupBy('CDPROJETO', 'NOMEPROJETO')
            ->orderBy('NOMEPROJETO')
            ->get();
    }

    private function normalizarNome(string $nome): string
    {
        $nome = trim($nome);
        if ($nome === '') {
            return '';
        }

        return mb_strtoupper(preg_replace('/\s+/u', ' ', $nome) ?: $nome, 'UTF-8');
    }

    private function extractStatusVisualFilters(Request $request): array
    {
        $statusVisual = $request->input('status_visual', []);

        if (!is_array($statusVisual)) {
            $statusVisual = [$statusVisual];
        }

        return array_values(array_unique(array_filter(array_map(function ($status) {
            $status = trim((string) $status);
            if ($status === '') {
                return null;
            }

            return str_replace(['-', ' '], '_', mb_strtoupper($status, 'UTF-8'));
        }, $statusVisual))));
    }

    private function applyStatusVisualFilters($query, array $statusVisualFilters): void
    {
        $query->where(function ($builder) use ($statusVisualFilters) {
            foreach ($statusVisualFilters as $statusVisual) {
                switch ($statusVisual) {
                    case SolicitacaoBem::STATUS_PENDENTE:
                    case SolicitacaoBem::STATUS_AGUARDANDO_CONFIRMACAO:
                    case SolicitacaoBem::STATUS_LIBERACAO:
                    case SolicitacaoBem::STATUS_RECEBIDO:
                    case SolicitacaoBem::STATUS_NAO_RECEBIDO:
                        $builder->orWhere('status', $statusVisual);
                        break;

                    case SolicitacaoBem::STATUS_CONFIRMADO:
                        $builder->orWhere('status', SolicitacaoBem::STATUS_CONFIRMADO);
                        break;

                    case 'ENVIADO':
                        $builder->orWhere(function ($enviadoQuery) {
                            $enviadoQuery
                                ->where('status', SolicitacaoBem::STATUS_CONFIRMADO)
                                ->where(function ($shipmentQuery) {
                                    $shipmentQuery
                                        ->whereNotNull('tracking_code')
                                        ->where('tracking_code', '!=', '')
                                        ->orWhere(function ($invoiceQuery) {
                                            $invoiceQuery
                                                ->whereNotNull('invoice_number')
                                                ->where('invoice_number', '!=', '');
                                        })
                                        ->orWhereNotNull('shipped_at');
                                });
                        });
                        break;

                    case SolicitacaoBem::STATUS_CANCELADO:
                        $builder->orWhereIn('status', [
                            SolicitacaoBem::STATUS_CANCELADO,
                            SolicitacaoBem::STATUS_NAO_ENVIADO,
                        ]);
                        break;
                }
            }
        });
    }

    private function jaPassouPorStatusFinal(SolicitacaoBem $solicitacao): bool
    {
        if (in_array($solicitacao->status, [SolicitacaoBem::STATUS_CONFIRMADO, SolicitacaoBem::STATUS_RECEBIDO], true)) {
            return true;
        }

        if (!Schema::hasTable('solicitacoes_bens_status_historico')) {
            return false;
        }

        return SolicitacaoBemStatusHistorico::query()
            ->where('solicitacao_id', $solicitacao->id)
            ->whereIn('status_novo', [SolicitacaoBem::STATUS_CONFIRMADO, SolicitacaoBem::STATUS_RECEBIDO])
            ->exists();
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

        // Cada Solicitação deve ter apenas um evento inicial "criado".
        if ($acao === 'criado') {
            $jaExisteCriado = SolicitacaoBemStatusHistorico::query()
                ->where('solicitacao_id', $solicitacao->id)
                ->where('acao', 'criado')
                ->exists();

            if ($jaExisteCriado) {
                return;
            }

            // Deixa explícito no histórico o início de fluxo.
            if ($statusAnterior === null || trim((string) $statusAnterior) === '') {
                $statusAnterior = 'CRIADO';
            }
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
     * Confirmar Solicitação (primeira confirmação)
     * Somente Tiago, Beatriz e Admin
     */
    public function confirm(Request $request, SolicitacaoBem $solicitacao): JsonResponse|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canConfirmSolicitacao($user)) {
            return $this->denyAccess($request, 'Você não tem permissão para confirmar solicitações.');
        }

        if ($solicitacao->status !== SolicitacaoBem::STATUS_PENDENTE) {
            return $this->denyAccess($request, 'Apenas solicitações pendentes podem ser confirmadas.');
        }

        // Regra de integridade: um mesmo pedido não pode voltar ao ciclo inicial
        // após já ter passado por etapa final (enviado/recebido).
        if ($this->jaPassouPorStatusFinal($solicitacao)) {
            return $this->denyAccess($request, 'Esta solicitação já passou por etapa final e não pode voltar para confirmação inicial.');
        }

        $validator = Validator::make($request->all(), [
            'destination_type' => ['nullable', 'in:FILIAL,PROJETO'],
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        $data = [
            'status' => SolicitacaoBem::STATUS_AGUARDANDO_CONFIRMACAO,
        ];
        if (!empty($validated['destination_type'])) {
            $data['destination_type'] = $validated['destination_type'];
        }
        if (empty($solicitacao->matricula_recebedor) && !empty($solicitacao->solicitante_matricula)) {
            $data['matricula_recebedor'] = $solicitacao->solicitante_matricula;
            $data['nome_recebedor'] = $solicitacao->nome_recebedor ?: $solicitacao->solicitante_nome;
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

        Log::info('[SOLICITACOES] Solicitação confirmada (1º nível)', [
            'solicitacao_id' => $solicitacao->id,
            'confirmado_por' => $user->NMLOGIN,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Solicitação aprovada. Etapa de separação iniciada.',
            ]);
        }

        return redirect()->back()->with('success', 'Solicitação aprovada. Etapa de separação iniciada.');
    }

    /**
     * Aprovar Solicitação (confirmação final)
     * Somente o solicitante original pode fazer
     */
    public function forwardToLiberacao(Request $request, SolicitacaoBem $solicitacao): JsonResponse|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canForwardToLiberacao($user)) {
            return $this->denyAccess($request, 'Você não tem permissão para encaminhar para liberação.');
        }

        if ($solicitacao->status !== SolicitacaoBem::STATUS_AGUARDANDO_CONFIRMACAO) {
            return $this->denyAccess($request, 'Apenas solicitações em análise podem ser encaminhadas para liberação.');
        }

        $validated = $request->validate([
            'logistics_height_cm' => ['required', 'numeric', 'min:0.01'],
            'logistics_width_cm' => ['required', 'numeric', 'min:0.01'],
            'logistics_length_cm' => ['required', 'numeric', 'min:0.01'],
            'logistics_weight_kg' => ['required', 'numeric', 'min:0.001'],
            'logistics_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $statusAnterior = $solicitacao->status;
        $solicitacao->update([
            'status' => SolicitacaoBem::STATUS_AGUARDANDO_CONFIRMACAO,
            'tracking_code' => null,
            'invoice_number' => null,
            'confirmado_por_id' => null,
            'confirmado_em' => null,
            'logistics_height_cm' => $validated['logistics_height_cm'],
            'logistics_width_cm' => $validated['logistics_width_cm'],
            'logistics_length_cm' => $validated['logistics_length_cm'],
            'logistics_weight_kg' => $validated['logistics_weight_kg'],
            'logistics_notes' => trim((string) ($validated['logistics_notes'] ?? '')) ?: null,
            'logistics_registered_by_id' => $user?->getAuthIdentifier(),
            'logistics_registered_at' => now(),
            'quote_options_payload' => null,
            'quote_selected_index' => null,
            'quote_tracking_type' => null,
            'quote_transporter' => null,
            'quote_amount' => null,
            'quote_deadline' => null,
            'quote_notes' => null,
            'quote_registered_by_id' => null,
            'quote_registered_at' => null,
            'quote_approved_by_id' => null,
            'quote_approved_at' => null,
            'shipped_by_id' => null,
            'shipped_at' => null,
        ]);

        $this->registrarHistoricoStatus(
            $solicitacao,
            $statusAnterior,
            SolicitacaoBem::STATUS_AGUARDANDO_CONFIRMACAO,
            'registrar_medidas',
            sprintf(
                'Medidas registradas: A %.2f x L %.2f x C %.2f cm | Peso %.3f kg',
                (float) $validated['logistics_height_cm'],
                (float) $validated['logistics_width_cm'],
                (float) $validated['logistics_length_cm'],
                (float) $validated['logistics_weight_kg']
            )
        );

        Log::info('[SOLICITACOES] Medidas e peso registrados', [
            'solicitacao_id' => $solicitacao->id,
            'encaminhado_por' => $user?->NMLOGIN,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Medidas e peso registrados. Separação concluída para a etapa da Beatriz.',
            ]);
        }

        return redirect()->back()->with('success', 'Medidas e peso registrados. Separação concluída para a etapa da Beatriz.');
    }

    public function release(Request $request, SolicitacaoBem $solicitacao): JsonResponse|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canRegisterQuote($user)) {
            return $this->denyAccess($request, 'Você não tem permissão para registrar cotações.');
        }

        if ($solicitacao->status !== SolicitacaoBem::STATUS_AGUARDANDO_CONFIRMACAO) {
            return $this->denyAccess($request, 'Apenas solicitações em separação podem receber cotações.');
        }

        if (!$solicitacao->hasLogisticsData()) {
            return $this->denyAccess($request, 'Registre as medidas e o peso antes de informar as cotações.');
        }

        if (empty($solicitacao->matricula_recebedor) && !empty($solicitacao->solicitante_matricula)) {
            $solicitacao->forceFill([
                'matricula_recebedor' => $solicitacao->solicitante_matricula,
                'nome_recebedor' => $solicitacao->nome_recebedor ?: $solicitacao->solicitante_nome,
            ])->save();
        }

        if (empty($solicitacao->matricula_recebedor)) {
            return $this->denyAccess($request, 'Informe o recebedor antes de registrar as cotações.');
        }

        $validator = Validator::make($request->all(), [
            'quote_slots' => ['nullable', 'integer', 'min:1', 'max:3'],
            'quotes' => ['required', 'array', 'min:1'],
            'quotes.*.transporter' => ['nullable', 'string', 'max:120'],
            'quotes.*.amount' => ['nullable', 'numeric', 'min:0'],
            'quotes.*.deadline' => ['nullable', 'string', 'max:80'],
            'quotes.*.tracking_type' => ['nullable', Rule::in(array_keys(SolicitacaoBem::trackingTypeOptions()))],
            'quotes.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $quotes = $this->normalizeQuoteOptions((array) $request->input('quotes', []));

            if ($quotes === []) {
                $validator->errors()->add('quotes', 'Informe ao menos uma cotação completa.');
                return;
            }

            foreach ($quotes as $index => $quote) {
                if (($quote['_invalid'] ?? false) === true) {
                    $validator->errors()->add("quotes.{$index}", 'Preencha todos os campos obrigatórios de cada cotação informada.');
                }
            }
        });

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();
        $quoteOptions = $this->normalizeQuoteOptions((array) ($validated['quotes'] ?? []));
        $quoteOptions = array_values(array_filter($quoteOptions, static fn ($quote) => ($quote['_invalid'] ?? false) !== true));

        $statusAnterior = $solicitacao->status;
        $solicitacao->update([
            'status' => SolicitacaoBem::STATUS_LIBERACAO,
            'tracking_code' => null,
            'invoice_number' => null,
            'confirmado_por_id' => null,
            'confirmado_em' => null,
            'quote_options_payload' => $quoteOptions,
            'quote_selected_index' => null,
            'quote_tracking_type' => null,
            'quote_transporter' => null,
            'quote_amount' => null,
            'quote_deadline' => null,
            'quote_notes' => null,
            'quote_registered_by_id' => $user?->getAuthIdentifier(),
            'quote_registered_at' => now(),
            'quote_approved_by_id' => null,
            'quote_approved_at' => null,
            'shipped_by_id' => null,
            'shipped_at' => null,
        ]);

        $this->registrarHistoricoStatus(
            $solicitacao,
            $statusAnterior,
            SolicitacaoBem::STATUS_LIBERACAO,
            'registrar_cotacoes',
            count($quoteOptions) . ' cotação(ões) registradas. Aguardando liberação do Bruno.'
        );

        Log::info('[SOLICITACOES] Cotações registradas', [
            'solicitacao_id' => $solicitacao->id,
            'registrado_por' => $user?->NMLOGIN,
            'quantidade_cotacoes' => count($quoteOptions),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cotações registradas. Solicitação em liberação do Bruno.',
            ]);
        }

        return redirect()->back()->with('success', 'Cotações registradas. Solicitação em liberação do Bruno.');
    }

    public function approve(Request $request, SolicitacaoBem $solicitacao): JsonResponse|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canAuthorizeRelease($user)) {
            return $this->denyAccess($request, 'Você não tem permissão para registrar pedido enviado.');
        }

        if ($solicitacao->status !== SolicitacaoBem::STATUS_LIBERACAO) {
            return $this->denyAccess($request, 'Apenas solicitações em análise podem ser marcadas como pedido enviado.');
        }

        if (empty($solicitacao->matricula_recebedor) && !empty($solicitacao->solicitante_matricula)) {
            $solicitacao->forceFill([
                'matricula_recebedor' => $solicitacao->solicitante_matricula,
                'nome_recebedor' => $solicitacao->nome_recebedor ?: $solicitacao->solicitante_nome,
            ])->save();
        }

        if (empty($solicitacao->matricula_recebedor)) {
            return $this->denyAccess($request, 'Informe o recebedor antes de liberar e enviar o pedido.');
        }

        $validated = $request->validate([
            'tracking_code' => ['required', 'string', 'max:100'],
        ]);

        $trackingCode = trim((string) $validated['tracking_code']);
        $statusAnterior = $solicitacao->status;
        $solicitacao->update([
            'status' => SolicitacaoBem::STATUS_CONFIRMADO,
            'tracking_code' => $trackingCode,
            'confirmado_por_id' => $user->getAuthIdentifier(),
            'confirmado_em' => now(),
        ]);

        $this->registrarHistoricoStatus(
            $solicitacao,
            $statusAnterior,
            SolicitacaoBem::STATUS_CONFIRMADO,
            'liberar_enviar',
            'Rastreio: ' . $trackingCode
        );

        Log::info('[SOLICITACOES] Envio registrado', [
            'solicitacao_id' => $solicitacao->id,
            'enviado_por' => $user->NMLOGIN,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Envio registrado com sucesso.',
            ]);
        }

        return redirect()->back()->with('success', 'Envio registrado com sucesso.');
    }

    /**
     * Marcar pedido como não enviado (fecha fluxo com justificativa)
     */
    public function send(Request $request, SolicitacaoBem $solicitacao): JsonResponse|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canSendSolicitacao($user)) {
            return $this->denyAccess($request, 'Você não tem permissão para enviar o pedido.');
        }

        if ($solicitacao->status !== SolicitacaoBem::STATUS_CONFIRMADO) {
            return $this->denyAccess($request, 'Apenas solicitações na etapa de envio podem ser enviadas.');
        }

        if ($solicitacao->hasShipmentData()) {
            return $this->denyAccess($request, 'Este pedido já possui envio registrado.');
        }

        if ($solicitacao->hasQuoteData() && $solicitacao->quote_approved_at === null) {
            return $this->denyAccess($request, 'A cotação precisa ser liberada pelo Bruno antes do envio.');
        }

        $requiresTrackingCode = $solicitacao->requiresTrackingCode();
        $requiresInvoiceNumber = $solicitacao->requiresInvoiceNumber();

        $validator = Validator::make($request->all(), [
            'tracking_code' => [$requiresTrackingCode ? 'required' : 'nullable', 'string', 'max:100'],
            'invoice_number' => [$requiresInvoiceNumber ? 'required' : 'nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        $trackingCode = trim((string) ($validated['tracking_code'] ?? ''));
        $invoiceNumber = trim((string) ($validated['invoice_number'] ?? ''));
        $statusAnterior = $solicitacao->status;
        $solicitacao->update([
            'tracking_code' => $trackingCode,
            'invoice_number' => $invoiceNumber,
            'shipped_by_id' => $user?->getAuthIdentifier(),
            'shipped_at' => now(),
        ]);

        $this->registrarHistoricoStatus(
            $solicitacao,
            $statusAnterior,
            SolicitacaoBem::STATUS_CONFIRMADO,
            'enviar_pedido',
            collect([
                $trackingCode !== '' ? 'Rastreio: ' . $trackingCode : null,
                $invoiceNumber !== '' ? 'NF: ' . $invoiceNumber : null,
            ])->filter()->implode(' | ')
        );

        Log::info('[SOLICITACOES] Pedido enviado', [
            'solicitacao_id' => $solicitacao->id,
            'enviado_por' => $user?->NMLOGIN,
            'tracking_code' => $trackingCode,
            'invoice_number' => $invoiceNumber,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pedido enviado com sucesso.',
            ]);
        }

        return redirect()->back()->with('success', 'Pedido enviado com sucesso.');
    }

    public function approveQuote(Request $request, SolicitacaoBem $solicitacao): JsonResponse|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canAuthorizeRelease($user)) {
            return $this->denyAccess($request, 'Você não tem permissão para liberar este envio.');
        }

        if ($solicitacao->status !== SolicitacaoBem::STATUS_LIBERACAO || !$solicitacao->hasQuoteOptions()) {
            return $this->denyAccess($request, 'Esta solicitação não está aguardando liberação do Bruno.');
        }

        $validated = $request->validate([
            'selected_quote_index' => ['required', 'integer', 'min:0', 'max:2'],
            'quote_approval_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $quoteOptions = $solicitacao->quoteOptions();
        $selectedIndex = (int) $validated['selected_quote_index'];

        if (!array_key_exists($selectedIndex, $quoteOptions)) {
            return $this->denyAccess($request, 'Selecione uma cotação válida para a liberação.');
        }

        $selectedQuote = $quoteOptions[$selectedIndex];
        $motivo = trim((string) ($validated['quote_approval_notes'] ?? ''));
        $statusAnterior = $solicitacao->status;
        $solicitacao->update([
            'status' => SolicitacaoBem::STATUS_CONFIRMADO,
            'quote_selected_index' => $selectedIndex,
            'quote_tracking_type' => $selectedQuote['tracking_type'] ?? null,
            'quote_transporter' => $selectedQuote['transporter'] ?? null,
            'quote_amount' => $selectedQuote['amount'] ?? null,
            'quote_deadline' => $selectedQuote['deadline'] ?? null,
            'quote_notes' => $selectedQuote['notes'] ?? null,
            'quote_approved_by_id' => $user?->getAuthIdentifier(),
            'quote_approved_at' => now(),
            'tracking_code' => null,
            'invoice_number' => null,
            'shipped_by_id' => null,
            'shipped_at' => null,
        ]);

        $this->registrarHistoricoStatus(
            $solicitacao,
            $statusAnterior,
            SolicitacaoBem::STATUS_CONFIRMADO,
            'liberar_envio',
            $motivo !== ''
                ? $motivo
                : 'Bruno liberou a cotação da transportadora ' . ($selectedQuote['transporter'] ?? '-')
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Liberação registrada. Pedido pronto para envio.',
            ]);
        }

        return redirect()->back()->with('success', 'Liberação registrada. Pedido pronto para envio.');
    }

    public function rejectQuote(Request $request, SolicitacaoBem $solicitacao): JsonResponse|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canDecideQuote($user, $solicitacao)) {
            return $this->denyAccess($request, 'Você não tem permissão para recusar esta cotação.');
        }

        if (!$solicitacao->isAwaitingRequesterDecision()) {
            return $this->denyAccess($request, 'Esta solicitação não está aguardando liberação do Bruno.');
        }

        $validated = $request->validate([
            'quote_rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $motivo = trim((string) $validated['quote_rejection_reason']);
        $statusAnterior = $solicitacao->status;
        $solicitacao->update([
            'status' => SolicitacaoBem::STATUS_NAO_ENVIADO,
            'justificativa_cancelamento' => $motivo,
            'cancelado_por_id' => $user?->getAuthIdentifier(),
            'cancelado_em' => now(),
            'tracking_code' => null,
            'invoice_number' => null,
            'shipped_by_id' => null,
            'shipped_at' => null,
        ]);

        $this->registrarHistoricoStatus(
            $solicitacao,
            $statusAnterior,
            SolicitacaoBem::STATUS_NAO_ENVIADO,
            'recusar_cotacao',
            $motivo
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cotação recusada. Solicitação encerrada como não enviada.',
            ]);
        }

        return redirect()->back()->with('success', 'Cotação recusada. Solicitação encerrada como não enviada.');
    }

    public function notSent(Request $request, SolicitacaoBem $solicitacao): JsonResponse|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canForwardToLiberacao($user)) {
            return $this->denyAccess($request, 'Você não tem permissão para registrar pedido não enviado.');
        }

        if ($solicitacao->status !== SolicitacaoBem::STATUS_AGUARDANDO_CONFIRMACAO) {
            return $this->denyAccess($request, 'Apenas solicitações em análise podem ser marcadas como pedido não enviado.');
        }

        $validated = $request->validate([
            'justificativa_nao_enviado' => ['required', 'string', 'max:1000'],
        ]);

        $motivo = trim((string) $validated['justificativa_nao_enviado']);
        $statusAnterior = $solicitacao->status;

        $solicitacao->update([
            'status' => SolicitacaoBem::STATUS_NAO_ENVIADO,
            'justificativa_cancelamento' => $motivo,
            'cancelado_por_id' => $user?->getAuthIdentifier(),
            'cancelado_em' => now(),
            'tracking_code' => null,
            'invoice_number' => null,
            'shipped_by_id' => null,
            'shipped_at' => null,
        ]);

        $this->registrarHistoricoStatus(
            $solicitacao,
            $statusAnterior,
            SolicitacaoBem::STATUS_NAO_ENVIADO,
            'nao_enviado',
            $motivo
        );

        Log::info('[SOLICITACOES] Pedido marcado como não enviado', [
            'solicitacao_id' => $solicitacao->id,
            'registrado_por' => $user?->NMLOGIN,
            'motivo' => $motivo,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pedido não enviado registrado com sucesso.',
            ]);
        }

        return redirect()->back()->with('success', 'Pedido não enviado registrado com sucesso.');
    }

    /**
     * Solicitação recebida pelo solicitante (encerramento)
     */
    public function receive(Request $request, SolicitacaoBem $solicitacao): JsonResponse|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canReceiveSolicitacao($user, $solicitacao)) {
            return $this->denyAccess($request, 'Você não tem permissão para informar o recebimento desta solicitação.');
        }

        if ($solicitacao->status !== SolicitacaoBem::STATUS_CONFIRMADO || trim((string) ($solicitacao->tracking_code ?? '')) === '') {
            return $this->denyAccess($request, 'Apenas solicitações enviadas podem ser encerradas como recebidas.');
        }

        $statusAnterior = $solicitacao->status;
        $solicitacao->update([
            'status' => SolicitacaoBem::STATUS_RECEBIDO,
        ]);

        $this->registrarHistoricoStatus(
            $solicitacao,
            $statusAnterior,
            SolicitacaoBem::STATUS_RECEBIDO,
            'receber',
            'Recebimento confirmado pelo solicitante.'
        );

        Log::info('[SOLICITACOES] Solicitação encerrada como recebida', [
            'solicitacao_id' => $solicitacao->id,
            'recebido_por' => $user?->NMLOGIN,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Recebimento informado com sucesso.',
            ]);
        }

        return redirect()->back()->with('success', 'Recebimento informado com sucesso.');
    }

    /**
     * Pedido não recebido pelo solicitante (encerramento com justificativa).
     */
    public function notReceived(Request $request, SolicitacaoBem $solicitacao): JsonResponse|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canReceiveSolicitacao($user, $solicitacao)) {
            return $this->denyAccess($request, 'Você não tem permissão para informar que o pedido não foi recebido.');
        }

        if ($solicitacao->status !== SolicitacaoBem::STATUS_CONFIRMADO) {
            return $this->denyAccess($request, 'Apenas solicitações enviadas podem ser marcadas como não recebidas.');
        }

        $validated = $request->validate([
            'justificativa_nao_recebido' => ['required', 'string', 'max:1000'],
        ]);

        $motivo = trim((string) $validated['justificativa_nao_recebido']);
        $statusAnterior = $solicitacao->status;

        $solicitacao->update([
            'status' => SolicitacaoBem::STATUS_NAO_RECEBIDO,
            'justificativa_cancelamento' => $motivo,
        ]);

        $this->registrarHistoricoStatus(
            $solicitacao,
            $statusAnterior,
            SolicitacaoBem::STATUS_NAO_RECEBIDO,
            'nao_recebido',
            $motivo
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Não recebimento registrado com sucesso.',
            ]);
        }

        return redirect()->back()->with('success', 'Não recebimento registrado com sucesso.');
    }

    /**
     * Contestação de não recebimento (Bruno/Admin).
     * Permite reabrir para em análise ou enviado.
     */
    public function contestNotReceived(Request $request, SolicitacaoBem $solicitacao): JsonResponse|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canContestNotReceived($user)) {
            return $this->denyAccess($request, 'Você não tem permissão para contestar não recebimento.');
        }

        if ($solicitacao->status !== SolicitacaoBem::STATUS_NAO_RECEBIDO) {
            return $this->denyAccess($request, 'Apenas solicitações não recebidas podem ser contestadas.');
        }

        $validated = $request->validate([
            'status_destino' => ['required', Rule::in([
                SolicitacaoBem::STATUS_AGUARDANDO_CONFIRMACAO,
                SolicitacaoBem::STATUS_CONFIRMADO,
            ])],
            'motivo_contestacao' => ['required', 'string', 'max:1000'],
        ]);

        $statusDestino = (string) $validated['status_destino'];
        $motivo = trim((string) $validated['motivo_contestacao']);
        $statusAnterior = $solicitacao->status;
        $timestamp = now()->format('d/m/Y H:i');
        $nota = "[{$timestamp}] Contestação de não recebido: {$motivo}";
        $observacaoAtual = trim((string) ($solicitacao->observacao_controle ?? ''));
        $observacaoNova = $observacaoAtual !== '' ? ($observacaoAtual . "\n\n" . $nota) : $nota;

        $payload = [
            'status' => $statusDestino,
            'observacao_controle' => $observacaoNova,
        ];

        $solicitacao->update($payload);
        $this->registrarHistoricoStatus(
            $solicitacao,
            $statusAnterior,
            $statusDestino,
            'contestar_nao_recebido',
            $motivo
        );

        Log::info('[SOLICITACOES] Não recebimento contestado', [
            'solicitacao_id' => $solicitacao->id,
            'status_destino' => $statusDestino,
            'contestado_por' => $user?->NMLOGIN,
            'motivo' => $motivo,
        ]);

        $mensagem = match ($statusDestino) {
            SolicitacaoBem::STATUS_AGUARDANDO_CONFIRMACAO => 'Solicitação contestada e retornada para em análise.',
            SolicitacaoBem::STATUS_CONFIRMADO => 'Solicitação contestada e retornada para enviado.',
            default => 'Contestação registrada com sucesso.',
        };

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $mensagem,
            ]);
        }

        return redirect()->back()->with('success', $mensagem);
    }

    public function cancel(Request $request, SolicitacaoBem $solicitacao): JsonResponse|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canCancelSolicitacao($user, $solicitacao)) {
            return $this->denyAccess($request, 'Você não tem permissão para cancelar a Solicitação.');
        }

        if (in_array($solicitacao->status, [SolicitacaoBem::STATUS_CANCELADO, SolicitacaoBem::STATUS_NAO_ENVIADO, SolicitacaoBem::STATUS_RECEBIDO], true)) {
            return $this->denyAccess($request, 'Esta solicitação não pode mais ser cancelada.');
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
            'tracking_code' => null,
            'confirmado_por_id' => null,
            'confirmado_em' => null,
        ]);
        $this->registrarHistoricoStatus(
            $solicitacao,
            $statusAnterior,
            SolicitacaoBem::STATUS_CANCELADO,
            'cancelar',
            $validated['justificativa_cancelamento']
        );

        Log::info('[SOLICITACOES] Solicitação cancelada', [
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
     * Retornar Solicitação para análise (volta para PENDENTE)
     */
    public function returnToAnalysis(Request $request, SolicitacaoBem $solicitacao): JsonResponse|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canReturnSolicitacao($user, $solicitacao)) {
            return $this->denyAccess($request, 'Você não tem permissão para retornar a Solicitação para análise.');
        }

        if (!in_array($solicitacao->status, [
            SolicitacaoBem::STATUS_AGUARDANDO_CONFIRMACAO,
            SolicitacaoBem::STATUS_NAO_ENVIADO,
            SolicitacaoBem::STATUS_LIBERACAO,
            SolicitacaoBem::STATUS_CONFIRMADO,
            SolicitacaoBem::STATUS_NAO_RECEBIDO,
        ], true)) {
            return $this->denyAccess($request, 'Esta solicitação não pode voltar para a etapa anterior.');
        }

        $validated = $request->validate([
            'motivo_retorno' => ['required', 'string', 'max:1000'],
        ]);

        $motivo = trim((string) $validated['motivo_retorno']);
        $timestamp = now()->format('d/m/Y H:i');
        $nota = "[{$timestamp}] Retorno para análise: {$motivo}";
        $observacaoAtual = trim((string) ($solicitacao->observacao_controle ?? ''));
        $observacaoNova = $observacaoAtual !== '' ? ($observacaoAtual . "\n\n" . $nota) : $nota;
        $destinationType = $solicitacao->destination_type ?: SolicitacaoBem::DESTINATION_PROJETO;

        $statusAnterior = $solicitacao->status;
        $statusDestino = match ($solicitacao->status) {
            SolicitacaoBem::STATUS_AGUARDANDO_CONFIRMACAO,
            SolicitacaoBem::STATUS_NAO_ENVIADO => SolicitacaoBem::STATUS_PENDENTE,
            SolicitacaoBem::STATUS_LIBERACAO => SolicitacaoBem::STATUS_AGUARDANDO_CONFIRMACAO,
            SolicitacaoBem::STATUS_CONFIRMADO => SolicitacaoBem::STATUS_LIBERACAO,
            SolicitacaoBem::STATUS_NAO_RECEBIDO => SolicitacaoBem::STATUS_CONFIRMADO,
            default => $solicitacao->status,
        };

        $payload = [
            'status' => $statusDestino,
            'observacao_controle' => $observacaoNova,
            'destination_type' => $destinationType,
        ];

        if ($statusDestino === SolicitacaoBem::STATUS_PENDENTE) {
            $payload = array_merge($payload, [
                'tracking_code' => null,
                'invoice_number' => null,
                'confirmado_por_id' => null,
                'confirmado_em' => null,
                'logistics_height_cm' => null,
                'logistics_width_cm' => null,
                'logistics_length_cm' => null,
                'logistics_weight_kg' => null,
                'logistics_notes' => null,
                'logistics_registered_by_id' => null,
                'logistics_registered_at' => null,
                'quote_options_payload' => null,
                'quote_selected_index' => null,
                'quote_tracking_type' => null,
                'quote_transporter' => null,
                'quote_amount' => null,
                'quote_deadline' => null,
                'quote_notes' => null,
                'quote_registered_by_id' => null,
                'quote_registered_at' => null,
                'quote_approved_by_id' => null,
                'quote_approved_at' => null,
                'shipped_by_id' => null,
                'shipped_at' => null,
            ]);
        } elseif ($statusDestino === SolicitacaoBem::STATUS_AGUARDANDO_CONFIRMACAO) {
            $payload = array_merge($payload, [
                'tracking_code' => null,
                'invoice_number' => null,
                'confirmado_por_id' => null,
                'confirmado_em' => null,
                'quote_selected_index' => null,
                'quote_tracking_type' => null,
                'quote_transporter' => null,
                'quote_amount' => null,
                'quote_deadline' => null,
                'quote_notes' => null,
                'quote_registered_by_id' => null,
                'quote_registered_at' => null,
                'quote_approved_by_id' => null,
                'quote_approved_at' => null,
                'quote_options_payload' => null,
                'shipped_by_id' => null,
                'shipped_at' => null,
            ]);
        } elseif ($statusDestino === SolicitacaoBem::STATUS_LIBERACAO) {
            $payload = array_merge($payload, [
                'tracking_code' => null,
                'invoice_number' => null,
                'confirmado_por_id' => null,
                'confirmado_em' => null,
                'quote_selected_index' => null,
                'quote_tracking_type' => null,
                'quote_transporter' => null,
                'quote_amount' => null,
                'quote_deadline' => null,
                'quote_notes' => null,
                'quote_approved_by_id' => null,
                'quote_approved_at' => null,
                'shipped_by_id' => null,
                'shipped_at' => null,
            ]);
        } elseif ($statusDestino !== SolicitacaoBem::STATUS_CONFIRMADO) {
            $payload['tracking_code'] = null;
            $payload['invoice_number'] = null;
            $payload['confirmado_por_id'] = null;
            $payload['confirmado_em'] = null;
            $payload['shipped_by_id'] = null;
            $payload['shipped_at'] = null;
        }

        $solicitacao->update($payload);
        $this->registrarHistoricoStatus(
            $solicitacao,
            $statusAnterior,
            $statusDestino,
            'retornar',
            $motivo
        );

        Log::info('[SOLICITACOES] Solicitação retornada para análise', [
            'solicitacao_id' => $solicitacao->id,
            'retornado_por' => $user?->NMLOGIN,
            'motivo' => $motivo,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Solicitação retornada para a etapa anterior com sucesso!',
            ]);
        }

        return redirect()->back()->with('success', 'Solicitação retornada para a etapa anterior com sucesso!');
    }

    /**
     * Reenvia uma solicitação cancelada com os mesmos dados e itens.
     */
    public function recreateCancelled(Request $request, SolicitacaoBem $solicitacao): JsonResponse|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canRecreateCancelled($user, $solicitacao)) {
            return $this->denyAccess($request, 'Você não tem permissão para reenviar esta solicitação cancelada.');
        }

        if ($solicitacao->status !== SolicitacaoBem::STATUS_CANCELADO) {
            return $this->denyAccess($request, 'Apenas solicitações canceladas podem ser reenviadas.');
        }

        $validated = $request->validate([
            'motivo_reenvio' => ['required', 'string', 'max:1000'],
        ]);

        $motivoReenvio = trim((string) $validated['motivo_reenvio']);
        $novaSolicitacao = null;

        DB::transaction(function () use ($solicitacao, $motivoReenvio, &$novaSolicitacao) {
            $observacaoAtual = trim((string) ($solicitacao->observacao ?? ''));
            $observacaoNova = $observacaoAtual !== ''
                ? $observacaoAtual . "\n\nCorreção após cancelamento: {$motivoReenvio}"
                : "Correção após cancelamento: {$motivoReenvio}";

            $novaSolicitacao = SolicitacaoBem::create([
                'solicitante_id' => $solicitacao->solicitante_id,
                'solicitante_nome' => $solicitacao->solicitante_nome,
                'solicitante_matricula' => $solicitacao->solicitante_matricula,
                'projeto_id' => $solicitacao->projeto_id,
                'uf' => $solicitacao->uf,
                'setor' => $solicitacao->setor,
                'local_destino' => $solicitacao->local_destino,
                'status' => SolicitacaoBem::STATUS_PENDENTE,
                'observacao' => $observacaoNova,
                'matricula_recebedor' => $solicitacao->matricula_recebedor,
                'nome_recebedor' => $solicitacao->nome_recebedor,
                'destination_type' => $solicitacao->destination_type ?: SolicitacaoBem::DESTINATION_PROJETO,
            ]);

            $itens = $solicitacao->itens()
                ->get(['descricao', 'quantidade', 'unidade', 'observacao'])
                ->map(static fn ($item) => [
                    'descricao' => (string) $item->descricao,
                    'quantidade' => (int) $item->quantidade,
                    'unidade' => $item->unidade ? (string) $item->unidade : null,
                    'observacao' => $item->observacao ? (string) $item->observacao : null,
                ])
                ->all();

            if (!empty($itens)) {
                $novaSolicitacao->itens()->createMany($itens);
            }

        });

        if ($novaSolicitacao) {
            $this->registrarHistoricoStatus(
                $novaSolicitacao,
                null,
                SolicitacaoBem::STATUS_PENDENTE,
                'criado',
                'Reenvio da solicitação cancelada #' . $solicitacao->id . '. Motivo da correção: ' . $motivoReenvio
            );
        }

        $mensagem = 'Solicitação reenviada com sucesso a partir da cancelada #' . $solicitacao->id . '.';
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $mensagem,
                'nova_solicitacao_id' => $novaSolicitacao?->id,
            ]);
        }

        return redirect()->route('solicitacoes-bens.index')->with('success', $mensagem);
    }


    public function archiveCancelled(Request $request, SolicitacaoBem $solicitacao): JsonResponse|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canRecreateCancelled($user, $solicitacao)) {
            return $this->denyAccess($request, 'Voce nao tem permissao para arquivar esta solicitacao.');
        }

        if ($solicitacao->status !== SolicitacaoBem::STATUS_CANCELADO) {
            return $this->denyAccess($request, 'Apenas solicitacoes canceladas podem ser arquivadas.');
        }

        $statusAnterior = $solicitacao->status;
        $solicitacao->update([
            'status' => SolicitacaoBem::STATUS_ARQUIVADO,
        ]);

        $this->registrarHistoricoStatus(
            $solicitacao,
            $statusAnterior,
            SolicitacaoBem::STATUS_ARQUIVADO,
            'arquivar',
            'Solicitacao arquivada manualmente.'
        );

        $mensagem = 'Solicitacao arquivada com sucesso.';
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $mensagem,
            ]);
        }

        return redirect()->route('solicitacoes-bens.index')->with('success', $mensagem);
    }
    private function solicitanteMatriculaValida(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $matricula = trim((string) ($user->CDMATRFUNCIONARIO ?? ''));
        if ($matricula === '') {
            return false;
        }

        return Funcionario::where('CDMATRFUNCIONARIO', $matricula)->exists();
    }

    private function responderMatriculaInvalida(Request $request): RedirectResponse|JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user && !($user->needs_identity_update ?? false)) {
            $user->needs_identity_update = true;
            $user->save();
        }

        $message = 'Sua matrícula de usuário não está sincronizada com Funcionários. Atualize o cadastro e tente novamente.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'redirect' => route('profile.completion.create'),
                'code' => 'MATRICULA_INVALIDA',
            ], 409);
        }

        return redirect()->route('profile.completion.create')->with('error', $message);
    }
}






