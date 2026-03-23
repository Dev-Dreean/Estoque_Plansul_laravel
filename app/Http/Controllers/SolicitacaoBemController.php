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
        $canReleaseAction = $this->canReleaseAndSend($user);
        $canSendAction = $this->canSendSolicitacao($user);
        $canCancelAction = $this->canCancelSolicitacao($user);
        $canReturnAction = $this->canReturnSolicitacao($user);
        $canManage = $this->canUpdateSolicitacao($user)
            || $canConfirmAction
            || $canForwardAction
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
            $canReleaseAction = $this->canReleaseAndSend($user);
            $canSendAction = $this->canSendSolicitacao($user);
            $canCancelAction = $this->canCancelSolicitacao($user);
            $canReturnAction = $this->canReturnSolicitacao($user);
            $canManage = $this->canUpdateSolicitacao($user)
                || $canConfirmAction
                || $canForwardAction
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

    private function canConfirmSolicitacao(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        // ADM sempre tem acesso (nunca precisa de permissão explícita)
        if ($user->isAdmin()) {
            return true;
        }
        return $this->canTriagemInicial($user);
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
        return $user->temAcessoTela((string) User::TELA_SOLICITACOES_ATUALIZAR);
    }

    private function canReleaseAndSend(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }

        return $user->temAcessoTela((string) self::TELA_SOLICITACOES_LIBERACAO_ENVIO);
    }

    private function canSendSolicitacao(?User $user): bool
    {
        if (!$user) {
            return false;
        }
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
        // ADM sempre tem acesso (nunca precisa de permissão explícita)
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
        return $this->canForwardToLiberacao($user)
            || $this->canReleaseAndSend($user);
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
                        $builder->orWhere(function ($confirmadoQuery) {
                            $confirmadoQuery
                                ->where('status', SolicitacaoBem::STATUS_CONFIRMADO)
                                ->where(function ($trackingQuery) {
                                    $trackingQuery
                                        ->whereNull('tracking_code')
                                        ->orWhere('tracking_code', '');
                                });
                        });
                        break;

                    case 'ENVIADO':
                        $builder->orWhere(function ($enviadoQuery) {
                            $enviadoQuery
                                ->where('status', SolicitacaoBem::STATUS_CONFIRMADO)
                                ->whereNotNull('tracking_code')
                                ->where('tracking_code', '!=', '');
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
                'message' => 'Solicitação confirmada. Aguardando decisão de envio.',
            ]);
        }

        return redirect()->back()->with('success', 'Solicitação confirmada. Aguardando decisão de envio.');
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

        $statusAnterior = $solicitacao->status;
        $solicitacao->update([
            'status' => SolicitacaoBem::STATUS_LIBERACAO,
            'tracking_code' => null,
            'confirmado_por_id' => null,
            'confirmado_em' => null,
        ]);

        $this->registrarHistoricoStatus(
            $solicitacao,
            $statusAnterior,
            SolicitacaoBem::STATUS_LIBERACAO,
            'encaminhar_liberacao',
            'Solicitação encaminhada para liberação final.'
        );

        Log::info('[SOLICITACOES] Solicitação encaminhada para liberação', [
            'solicitacao_id' => $solicitacao->id,
            'encaminhado_por' => $user?->NMLOGIN,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Solicitação encaminhada para liberação com sucesso.',
            ]);
        }

        return redirect()->back()->with('success', 'Solicitação encaminhada para liberação com sucesso.');
    }

    public function release(Request $request, SolicitacaoBem $solicitacao): JsonResponse|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canReleaseAndSend($user)) {
            return $this->denyAccess($request, 'Você não tem permissão para liberar o pedido.');
        }

        if ($solicitacao->status !== SolicitacaoBem::STATUS_LIBERACAO) {
            return $this->denyAccess($request, 'Apenas solicitações em liberação podem ser liberadas.');
        }

        if (empty($solicitacao->matricula_recebedor) && !empty($solicitacao->solicitante_matricula)) {
            $solicitacao->forceFill([
                'matricula_recebedor' => $solicitacao->solicitante_matricula,
                'nome_recebedor' => $solicitacao->nome_recebedor ?: $solicitacao->solicitante_nome,
            ])->save();
        }

        if (empty($solicitacao->matricula_recebedor)) {
            return $this->denyAccess($request, 'Informe o recebedor antes de liberar o pedido.');
        }

        $statusAnterior = $solicitacao->status;
        $solicitacao->update([
            'status' => SolicitacaoBem::STATUS_CONFIRMADO,
            'tracking_code' => null,
            'confirmado_por_id' => null,
            'confirmado_em' => null,
        ]);

        $this->registrarHistoricoStatus(
            $solicitacao,
            $statusAnterior,
            SolicitacaoBem::STATUS_CONFIRMADO,
            'liberar_pedido',
            'Pedido liberado para envio.'
        );

        Log::info('[SOLICITACOES] Pedido liberado', [
            'solicitacao_id' => $solicitacao->id,
            'liberado_por' => $user?->NMLOGIN,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pedido liberado com sucesso.',
            ]);
        }

        return redirect()->back()->with('success', 'Pedido liberado com sucesso.');
    }

    public function approve(Request $request, SolicitacaoBem $solicitacao): JsonResponse|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$this->canReleaseAndSend($user)) {
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

        if (trim((string) ($solicitacao->tracking_code ?? '')) !== '') {
            return $this->denyAccess($request, 'Este pedido já possui envio registrado.');
        }

        $validated = $request->validate([
            'tracking_code' => ['required', 'string', 'max:100'],
        ]);

        $trackingCode = trim((string) $validated['tracking_code']);
        $statusAnterior = $solicitacao->status;
        $solicitacao->update([
            'tracking_code' => $trackingCode,
            'confirmado_por_id' => $user?->getAuthIdentifier(),
            'confirmado_em' => now(),
        ]);

        $this->registrarHistoricoStatus(
            $solicitacao,
            $statusAnterior,
            SolicitacaoBem::STATUS_CONFIRMADO,
            'enviar_pedido',
            'Rastreio: ' . $trackingCode
        );

        Log::info('[SOLICITACOES] Pedido enviado', [
            'solicitacao_id' => $solicitacao->id,
            'enviado_por' => $user?->NMLOGIN,
            'tracking_code' => $trackingCode,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pedido enviado com sucesso.',
            ]);
        }

        return redirect()->back()->with('success', 'Pedido enviado com sucesso.');
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
        if (!$this->canCancelSolicitacao($user)) {
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
        if (!$this->canReturnSolicitacao($user)) {
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

        if ($statusDestino !== SolicitacaoBem::STATUS_CONFIRMADO) {
            $payload['tracking_code'] = null;
            $payload['confirmado_por_id'] = null;
            $payload['confirmado_em'] = null;
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






