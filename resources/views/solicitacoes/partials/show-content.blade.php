@php
    $isModal = $isModal ?? false;
    $containerClass = $isModal ? 'p-4 sm:p-5' : 'py-12';
    $wrapperClass = $isModal ? 'w-full' : 'max-w-6xl mx-auto sm:px-6 lg:px-8';
    $statusColors = [
        'PENDENTE' => 'bg-yellow-400 text-black border border-yellow-500',
        'AGUARDANDO_CONFIRMACAO' => 'bg-blue-400 text-black border border-blue-500',
        'LIBERACAO' => 'bg-violet-300 text-black border border-violet-500',
        'CONFIRMADO' => 'bg-purple-400 text-black border border-purple-600',
        'RECEBIDO' => 'bg-green-400 text-black border border-green-600',
        'NAO_ENVIADO' => 'bg-amber-100 text-amber-800 border border-amber-200 dark:bg-amber-500/35 dark:text-amber-100 dark:border-amber-300/55',
        'NAO_RECEBIDO' => 'bg-rose-100 text-rose-800 border border-rose-200 dark:bg-rose-500/35 dark:text-rose-100 dark:border-rose-300/55',
        'CANCELADO' => 'bg-red-400 text-black border border-red-600',
    ];
    $recebedorMatriculaOld = old(
        'recebedor_matricula',
        $solicitacao->solicitante_matricula ?? $solicitacao->matricula_recebedor ?? ''
    );
    $recebedorNomeOld = old(
        'nome_recebedor',
        $solicitacao->solicitante_nome ?? $solicitacao->nome_recebedor ?? ''
    );
    $matriculaTrim = trim((string) $recebedorMatriculaOld);
    $nomeTrim = trim((string) $recebedorNomeOld);
    $lookupOnInit = $nomeTrim === '' && $matriculaTrim !== '';
    if ($matriculaTrim !== '' && $nomeTrim !== '') {
        $recebedorDisplay = $matriculaTrim . ' - ' . $nomeTrim;
    } elseif ($nomeTrim !== '') {
        $recebedorDisplay = $nomeTrim;
    } else {
        $recebedorDisplay = $matriculaTrim;
    }
    $canManage = $canManage ?? false;
    $canContestNotReceived = $canContestNotReceived ?? false;
    $authUser = auth()->user();
    $authUserMatricula = trim((string) ($authUser?->CDMATRFUNCIONARIO ?? ''));
    $solicitanteMatricula = trim((string) ($solicitacao->solicitante_matricula ?? ''));
    $canMarkReceived = (bool) ($authUser?->isAdmin())
        || ((string) ($solicitacao->solicitante_id ?? '') !== '' && (string) ($solicitacao->solicitante_id ?? '') === (string) ($authUser?->getAuthIdentifier() ?? ''))
        || ($authUserMatricula !== '' && $authUserMatricula === $solicitanteMatricula);
    $canMarkNotReceived = $canMarkReceived;
    $canConfirmAction = (bool) ($canConfirmAction ?? false);
    $canForwardAction = (bool) ($canForwardAction ?? false);
    $canReleaseAction = (bool) ($canReleaseAction ?? false);
    $canSendAction = (bool) ($canSendAction ?? false);
    $canCancelAction = (bool) ($canCancelAction ?? false);
    $canReturnAction = (bool) ($canReturnAction ?? false);
    $canRecriarCancelada = (bool) ($canRecriarCancelada ?? false);
    $canManagePanel = $canManage
        || $canMarkReceived
        || $canMarkNotReceived
        || $canContestNotReceived
        || $canRecriarCancelada;
    $statusBadgeAtual = $solicitacao->status === 'CONFIRMADO' && trim((string) ($solicitacao->tracking_code ?? '')) !== ''
        ? 'ENVIADO'
        : $solicitacao->status;

    $gridClass = 'grid-cols-1';
    $leftColClass = '';
@endphp

<div data-solicitacao-modal-content>
    <div class="{{ $containerClass }}">
        <div class="{{ $wrapperClass }}" x-data="{
                showUpdate: true,
                showConfirmModal: false,
                showForwardModal: false,
                showApproveModal: false,
                showSendModal: false,
                showNotSentModal: false,
                showReceiveModal: false,
                showNotReceivedModal: false,
                showContestNotReceivedModal: false,
                showReturnModal: false,
                showCancelModal: false,
                showRecreateCancelledModal: false,
                showDetails: false,
                openSection: 'history',
                toggleSection(section) {
                    this.openSection = this.openSection === section ? '' : section;
                }
            }">
            <!-- Alerts -->
            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 text-green-700 dark:bg-green-900/20 dark:border-green-800 dark:text-green-300 px-4 py-3 rounded-lg flex items-center gap-2" role="alert">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span class="font-semibold">Sucesso:</span> {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-6 bg-red-50 border border-red-200 text-red-700 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300 px-4 py-3 rounded-lg flex items-center gap-2" role="alert">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="font-semibold">Erro:</span> {{ session('error') }}
                </div>
            @endif
            @if($errors->any())
                <div class="mb-6 bg-red-50 border border-red-200 text-red-700 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300 px-4 py-3 rounded-lg" role="alert">
                    <span class="font-semibold">Erro:</span> {{ $errors->first() }}
                </div>
            @endif

            @if($canManagePanel)
                <div class="mb-3 bg-[color:var(--solicitacao-modal-bg,#fcfdff)] dark:bg-slate-900/90 shadow-sm rounded-xl border border-[color:var(--solicitacao-modal-border,#d6dde6)] dark:border-slate-700 overflow-hidden">
                    <div class="flex flex-wrap md:flex-nowrap items-stretch justify-center gap-2 p-2">
                        @if($solicitacao->status === 'PENDENTE' && $canConfirmAction)
                            <button type="button" @click="showConfirmModal = true" class="flex-1 min-w-[220px] inline-flex items-center justify-center gap-2 h-11 px-3 rounded-lg text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition shadow-sm">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                Confirmar Solicitação
                            </button>
                        @endif
                        @if($solicitacao->status === 'AGUARDANDO_CONFIRMACAO' && $canForwardAction)
                            <button type="button" @click="showForwardModal = true" class="flex-1 min-w-[220px] inline-flex items-center justify-center gap-2 h-11 px-3 rounded-lg text-xs font-semibold transition shadow-sm"
                                style="background:#7c3aed;border:1px solid #8b5cf6;color:#ffffff;">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                                Encaminhar para Liberação
                            </button>
                        @endif
                        @if($solicitacao->status === 'AGUARDANDO_CONFIRMACAO' && $canForwardAction)
                            <button type="button" @click="showNotSentModal = true" class="flex-1 min-w-[220px] inline-flex items-center justify-center gap-2 h-11 px-3 rounded-lg text-xs font-semibold transition shadow-sm"
                                style="background:#dc2626;border:1px solid #f87171;color:#ffffff;">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 11-12.728 0 9 9 0 0112.728 0zM9 9l6 6m0-6l-6 6" /></svg>
                                Cancelar Solicita&ccedil;&atilde;o
                            </button>
                        @endif
                        @if(in_array($solicitacao->status, ['AGUARDANDO_CONFIRMACAO', 'LIBERACAO'], true) && $canReturnAction)
                            <button type="button" @click="showReturnModal = true" class="flex-1 min-w-[220px] inline-flex items-center justify-center gap-2 h-11 px-3 rounded-lg text-xs font-semibold transition shadow-sm"
                                style="background:#d97706;border:1px solid #f59e0b;color:#ffffff;">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12a9 9 0 1015.364-6.364M3 12H9m-6 0l3-3m-3 3l3 3" /></svg>
                                Voltar para Em Análise
                            </button>
                        @endif
                        @if($solicitacao->status === 'LIBERACAO' && $canReleaseAction)
                            <button type="button" @click="showApproveModal = true" class="flex-1 min-w-[220px] inline-flex items-center justify-center gap-2 h-11 px-3 rounded-lg text-xs font-semibold transition shadow-sm"
                                style="background:#2563eb;border:1px solid #60a5fa;color:#ffffff;">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                Liberar Pedido
                            </button>
                        @endif
                        @if($solicitacao->status === 'PENDENTE' && $canCancelAction)
                            <button type="button" @click="showCancelModal = true" class="flex-1 min-w-[220px] inline-flex items-center justify-center gap-2 h-11 px-3 rounded-lg text-xs font-semibold text-white bg-red-600 hover:bg-red-700 transition shadow-sm">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                Cancelar Solicitação
                            </button>
                        @endif
                        @if($solicitacao->status === 'CANCELADO' && $canRecriarCancelada)
                            <button type="button" @click="showRecreateCancelledModal = true" class="flex-1 min-w-[220px] inline-flex items-center justify-center gap-2 h-11 px-3 rounded-lg text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-sm">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8 8 0 106.582 9m0 0H9m-4 0V4" /></svg>
                                Solicitar Novamente
                            </button>
                        @endif
                        @if($solicitacao->status === 'CONFIRMADO' && trim((string) ($solicitacao->tracking_code ?? '')) === '' && $canSendAction)
                            <button type="button" @click="showSendModal = true" class="flex-1 min-w-[220px] inline-flex items-center justify-center gap-2 h-11 px-3 rounded-lg text-xs font-semibold transition shadow-sm"
                                style="background:#4f46e5;border:1px solid #818cf8;color:#ffffff;">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                                Enviar Pedido
                            </button>
                        @endif
                        @if($solicitacao->status === 'CONFIRMADO' && trim((string) ($solicitacao->tracking_code ?? '')) !== '' && $canMarkReceived)
                            <button type="button" @click="showReceiveModal = true" class="flex-1 min-w-[220px] inline-flex items-center justify-center gap-2 h-11 px-3 rounded-lg text-xs font-bold transition shadow-sm"
                                style="background:#22d3ee;border:1px solid #67e8f9;color:#082f49;">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                Confirmar Recebimento
                            </button>
                        @endif
                        @if($solicitacao->status === 'CONFIRMADO' && trim((string) ($solicitacao->tracking_code ?? '')) !== '' && $canMarkNotReceived)
                            <button type="button" @click="showNotReceivedModal = true" class="flex-1 min-w-[220px] inline-flex items-center justify-center gap-2 h-11 px-3 rounded-lg text-xs font-semibold transition shadow-sm"
                                style="background:#e11d48;border:1px solid #fb7185;color:#fff;">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 11-12.728 0 9 9 0 0112.728 0zM9 9l6 6m0-6l-6 6" /></svg>
                                Pedido Não Recebido
                            </button>
                        @endif
                        @if($solicitacao->status === 'NAO_RECEBIDO' && $canContestNotReceived)
                            <button type="button" @click="showContestNotReceivedModal = true" class="flex-1 min-w-[220px] inline-flex items-center justify-center gap-2 h-11 px-3 rounded-lg text-xs font-semibold text-white bg-violet-600 hover:bg-violet-700 transition shadow-sm">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h6m-9 8h16a1 1 0 001-1V7a1 1 0 00-1-1h-3l-2-2H9L7 6H4a1 1 0 00-1 1v12a1 1 0 001 1z" /></svg>
                                Contestar Não Recebido
                            </button>
                        @endif
                    </div>
                </div>
            @endif

            @php
                $statusAtualTopo = $solicitacao->status;
                $statusFluxo = (string) ($solicitacao->status ?? '');
                $historicoStatusTopo = collect();
                if (\Illuminate\Support\Facades\Schema::hasTable('solicitacoes_bens_status_historico')) {
                    $historicoStatusTopo = $solicitacao->historicoStatus
                        ? $solicitacao->historicoStatus->sortBy('created_at')->values()
                        : collect();
                }
                $statusHistorico = $historicoStatusTopo
                    ->pluck('status_novo')
                    ->filter()
                    ->map(fn ($status) => strtoupper((string) $status))
                    ->values();
                $etapaAtual = match ($statusAtualTopo) {
                    'PENDENTE', 'CANCELADO' => 1,
                    'AGUARDANDO_CONFIRMACAO', 'NAO_ENVIADO' => 2,
                    'LIBERACAO' => 3,
                    'CONFIRMADO', 'NAO_RECEBIDO' => 4,
                    'RECEBIDO' => 5,
                    default => 1,
                };
                $nomeCurto = function (?string $nome): string {
                    $nome = trim((string) $nome);
                    if ($nome === '') {
                        return '-';
                    }
                    $partes = array_values(array_filter(explode(' ', preg_replace('/\s+/u', ' ', $nome))));
                    if (count($partes) <= 1) {
                        return $partes[0] ?? $nome;
                    }
                    return ($partes[0] ?? '') . ' ' . ($partes[count($partes) - 1] ?? '');
                };
                $solicitanteNomeCurto = $nomeCurto($solicitacao->solicitante_nome ?? null);
                $recebedorNomeBase = trim((string) ($solicitacao->nome_recebedor ?: $solicitacao->solicitante_nome));
                $recebedorNomeCurto = $nomeCurto($recebedorNomeBase);
                $mesmoSolicitanteRecebedor = mb_strtoupper($solicitanteNomeCurto, 'UTF-8') === mb_strtoupper($recebedorNomeCurto, 'UTF-8');
                $primeiroItemDescricao = trim((string) data_get($solicitacao->itens->first(), 'descricao', ''));
                $projetoCodigo = (string) ($solicitacao->projeto?->CDPROJETO ?? '-');
                $projetoNome = (string) ($solicitacao->projeto?->NOMEPROJETO ?? '');
                $projetoLabel = trim($projetoCodigo . ($projetoNome !== '' ? ' - ' . $projetoNome : ''));
                $analiseLiberada = in_array($statusFluxo, ['AGUARDANDO_CONFIRMACAO', 'LIBERACAO', 'CONFIRMADO', 'RECEBIDO', 'NAO_ENVIADO', 'NAO_RECEBIDO'], true)
                    || $statusHistorico->contains('AGUARDANDO_CONFIRMACAO')
                    || $statusHistorico->contains('LIBERACAO')
                    || $statusHistorico->contains('CONFIRMADO')
                    || $statusHistorico->contains('RECEBIDO')
                    || $statusHistorico->contains('NAO_ENVIADO')
                    || $statusHistorico->contains('NAO_RECEBIDO');
                $liberacaoLiberada = in_array($statusFluxo, ['LIBERACAO', 'CONFIRMADO', 'RECEBIDO', 'NAO_RECEBIDO'], true)
                    || $statusHistorico->contains('LIBERACAO')
                    || $statusHistorico->contains('CONFIRMADO')
                    || $statusHistorico->contains('RECEBIDO')
                    || $statusHistorico->contains('NAO_RECEBIDO');
                $envioLiberado = in_array($statusFluxo, ['CONFIRMADO', 'RECEBIDO', 'NAO_RECEBIDO'], true)
                    || $statusHistorico->contains('CONFIRMADO')
                    || $statusHistorico->contains('RECEBIDO')
                    || $statusHistorico->contains('NAO_RECEBIDO');
                $statusEnvioTexto = match ($statusFluxo) {
                    'RECEBIDO' => 'Entregue',
                    'CONFIRMADO' => trim((string) ($solicitacao->tracking_code ?? '')) !== '' ? 'Enviado' : 'Aguardando envio',
                    'LIBERACAO' => 'Aguardando liberação final',
                    'NAO_RECEBIDO' => 'Não recebido',
                    'NAO_ENVIADO' => 'Cancelado',
                    'AGUARDANDO_CONFIRMACAO' => 'Em análise',
                    'CANCELADO' => 'Cancelado',
                    default => 'Pendente',
                };
                $statusEnvioClass = match ($statusFluxo) {
                    'RECEBIDO' => 'bg-cyan-100 text-cyan-800 border border-cyan-200 dark:bg-cyan-500/20 dark:text-cyan-200 dark:border-cyan-400/40',
                    'CONFIRMADO' => 'bg-blue-100 text-blue-800 border border-blue-200 dark:bg-blue-500/20 dark:text-blue-200 dark:border-blue-400/40',
                    'LIBERACAO' => 'bg-violet-100 text-violet-800 border border-violet-200 dark:bg-violet-500/20 dark:text-violet-200 dark:border-violet-400/40',
                    'NAO_RECEBIDO' => 'bg-rose-100 text-rose-800 border border-rose-200 dark:bg-rose-500/20 dark:text-rose-200 dark:border-rose-400/40',
                    'NAO_ENVIADO' => 'bg-amber-100 text-amber-800 border border-amber-200 dark:bg-amber-500/20 dark:text-amber-200 dark:border-amber-400/40',
                    'CANCELADO' => 'bg-red-100 text-red-800 border border-red-200 dark:bg-red-500/20 dark:text-red-200 dark:border-red-400/40',
                    default => 'bg-yellow-100 text-yellow-800 border border-yellow-200 dark:bg-yellow-500/20 dark:text-yellow-200 dark:border-yellow-400/40',
                };
                $ultimoHistoricoNaoPendente = $historicoStatusTopo->reverse()->first(
                    fn ($h) => strtoupper((string) ($h->status_novo ?? '')) !== 'PENDENTE'
                );
                $ultimoStatusNaoPendente = strtoupper((string) data_get($ultimoHistoricoNaoPendente, 'status_novo', ''));
                $ultimoHistoricoComMotivo = $historicoStatusTopo->reverse()->first(
                    fn ($h) => trim((string) ($h->motivo ?? '')) !== ''
                );
                $motivoFluxo = trim((string) data_get($ultimoHistoricoComMotivo, 'motivo', $solicitacao->justificativa_cancelamento ?? ''));
                if ($statusFluxo === 'PENDENTE' && $ultimoStatusNaoPendente !== '') {
                    $statusEnvioTexto = 'Retornou para solicitado';
                    $statusEnvioClass = 'bg-indigo-100 text-indigo-800 border border-indigo-200 dark:bg-indigo-500/25 dark:text-indigo-100 dark:border-indigo-300/40';
                }
                $interrupcao = match ($statusAtualTopo) {
                    'CANCELADO' => ['label' => 'Cancelado', 'class' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300 border border-red-200 dark:border-red-800'],
                    'NAO_ENVIADO' => ['label' => 'Cancelado', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 border border-amber-200 dark:border-amber-800'],
                    'NAO_RECEBIDO' => ['label' => 'Pedido não recebido', 'class' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300 border border-rose-200 dark:border-rose-800'],
                    default => null,
                };
                $stepsTopo = [
                    ['n' => 1, 'label' => 'Solicitado'],
                    ['n' => 2, 'label' => 'Em Análise'],
                    ['n' => 3, 'label' => 'Liberação'],
                    ['n' => 4, 'label' => 'Envio'],
                    ['n' => 5, 'label' => 'Recebido'],
                ];
                $historicoCards = $historicoStatusTopo
                    ->sortBy('created_at')
                    ->values()
                    ->filter(function ($hist) {
                        $status = strtoupper((string) ($hist->status_novo ?? ''));
                        $acao = strtolower((string) ($hist->acao ?? ''));
                        return $status !== '' || $acao !== '';
                    })
                    ->values();

                if ($historicoCards->isEmpty()) {
                    $historicoCards = collect([
                        (object) [
                            'status_novo' => \App\Models\SolicitacaoBem::STATUS_PENDENTE,
                            'status_anterior' => null,
                            'acao' => 'criado',
                            'motivo' => null,
                            'usuario' => null,
                            'created_at' => $solicitacao->created_at,
                        ],
                    ]);
                }
            @endphp

            <div class="mb-3 bg-[color:var(--solicitacao-modal-bg,#fcfdff)] dark:bg-slate-900/90 shadow-sm rounded-xl border border-[color:var(--solicitacao-modal-border,#d6dde6)] dark:border-slate-700 overflow-hidden">
                <div class="px-4 py-3 border-b border-[color:var(--solicitacao-modal-border,#d6dde6)] dark:border-slate-700 bg-[color:var(--solicitacao-modal-input-bg,#f7f9fc)] dark:bg-slate-800/80 flex items-center justify-between gap-2">
                    <h3 class="text-[13px] font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2" /></svg>
                        Acompanhamento do Pedido
                    </h3>
                    <button type="button" @click="toggleSection('history')" class="ml-auto inline-flex items-center gap-1 text-[10px] uppercase tracking-wider text-gray-400 hover:text-gray-600 dark:text-slate-500 dark:hover:text-slate-300 transition-colors">
                        <span x-text="openSection === 'history' ? 'Contrair' : 'Expandir'"></span>
                        <svg class="w-3 h-3 transition-transform" :class="openSection === 'history' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                </div>
                <div class="p-4">
                    <div class="relative flex items-center justify-between">
                        <div class="absolute top-4 left-0 w-full h-0.5 bg-gray-200 dark:bg-gray-700"></div>
                        <div class="absolute top-4 left-0 h-0.5 bg-indigo-500 transition-all duration-500" style="width: {{ (($etapaAtual - 1) / 4) * 100 }}%"></div>
                        @foreach($stepsTopo as $stepTopo)
                            @php
                                $ativo = $stepTopo['n'] <= $etapaAtual;
                            @endphp
                            <div class="relative z-10 flex flex-col items-center">
                                <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center text-xs font-bold {{ $ativo ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white dark:bg-slate-900 border-gray-300 dark:border-gray-600 text-gray-400' }}">
                                    {{ $stepTopo['n'] }}
                                </div>
                                <span class="mt-2 text-[10px] {{ $ativo ? 'text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-400' }}">{{ $stepTopo['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    @if($interrupcao)
                        <div class="mt-3 text-center">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $interrupcao['class'] }}">{{ $interrupcao['label'] }}</span>
                        </div>
                    @endif

                    <div x-show="openSection === 'history'" x-transition.duration.300ms class="mt-4 border-t border-[color:var(--solicitacao-modal-border,#d6dde6)] pt-4">
                        <div class="overflow-x-auto pb-1">
                            <div class="flex flex-nowrap gap-3 min-w-max">
                                @foreach($historicoCards as $histCard)
                                    @php
                                        $statusCard = strtoupper((string) ($histCard->status_novo ?? ''));
                                        $acaoCard = strtolower((string) ($histCard->acao ?? ''));
                                        $motivoCard = trim((string) ($histCard->motivo ?? ''));
                                        $statusAnteriorCard = strtoupper((string) ($histCard->status_anterior ?? ''));
                                        $usuarioCard = trim((string) ($histCard->usuario->NOMEUSER ?? $histCard->usuario->NMLOGIN ?? '-'));
                                        $usuarioCardPartes = array_values(array_filter(explode(' ', preg_replace('/\s+/u', ' ', $usuarioCard))));
                                        $usuarioNomeCurtoCard = count($usuarioCardPartes) > 1
                                            ? (($usuarioCardPartes[0] ?? '') . ' ' . ($usuarioCardPartes[count($usuarioCardPartes) - 1] ?? ''))
                                            : $usuarioCard;
                                        $usuarioMatriculaCard = trim((string) ($histCard->usuario->CDMATRFUNCIONARIO ?? $solicitacao->solicitante_matricula ?? '-'));
                                        $dataCard = optional($histCard->created_at)->format('d/m/Y');
                                        $horaCard = optional($histCard->created_at)->format('H:i');

                                        $secaoCard = match (true) {
                                            $acaoCard === 'contestar_nao_recebido' => 'Contestação',
                                            $acaoCard === 'retornar' => 'Retorno',
                                            $statusCard === 'AGUARDANDO_CONFIRMACAO' => 'Em análise',
                                            $statusCard === 'LIBERACAO' => 'Liberação',
                                            $acaoCard === 'liberar_pedido', $statusCard === 'CONFIRMADO' => 'Liberação',
                                            $statusCard === 'CONFIRMADO' => 'Envio',
                                            $statusCard === 'NAO_ENVIADO' => 'Cancelamento',
                                            $statusCard === 'RECEBIDO', $statusCard === 'NAO_RECEBIDO' => 'Recebimento',
                                            $statusCard === 'CANCELADO' => 'Cancelamento',
                                            default => 'Solicitação',
                                        };

                                        $tituloCard = match (true) {
                                            $acaoCard === 'contestar_nao_recebido' => 'Não recebido contestado',
                                            $acaoCard === 'retornar' => 'Retornou para solicitado',
                                            $acaoCard === 'confirmar', $statusCard === 'AGUARDANDO_CONFIRMACAO' => 'Solicitação em análise',
                                            $acaoCard === 'encaminhar_liberacao', $statusCard === 'LIBERACAO' => 'Encaminhado para liberação',
                                            $acaoCard === 'liberar_pedido', $statusCard === 'CONFIRMADO' => 'Pedido liberado',
                                            $acaoCard === 'enviar_pedido', $statusCard === 'CONFIRMADO' => 'Pedido enviado',
                                            $acaoCard === 'liberar_enviar', $statusCard === 'CONFIRMADO' => 'Pedido enviado',
                                            $statusCard === 'NAO_ENVIADO' => 'Solicitação cancelada',
                                            $statusCard === 'NAO_RECEBIDO' => 'Pedido não recebido',
                                            $statusCard === 'RECEBIDO' => 'Recebimento confirmado',
                                            $statusCard === 'CANCELADO' => 'Solicitação cancelada',
                                            default => 'Solicitação realizada',
                                        };

                                        $paletteCard = match ($secaoCard) {
                                            'Solicitação' => ['border' => 'border-yellow-400 dark:border-yellow-400', 'bar' => 'bg-yellow-400', 'border_style' => 'border-color:#facc15;', 'bar_style' => 'background-color:#facc15;', 'tag_style' => 'background-color:#facc15;border-color:#eab308;color:#000;'],
                                            'Em análise' => ['border' => 'border-blue-400 dark:border-blue-400', 'bar' => 'bg-blue-400', 'border_style' => 'border-color:#60a5fa;', 'bar_style' => 'background-color:#60a5fa;', 'tag_style' => 'background-color:#60a5fa;border-color:#3b82f6;color:#000;'],
                                            'Liberação' => ['border' => 'border-violet-500 dark:border-violet-400', 'bar' => 'bg-violet-500', 'border_style' => 'border-color:#8b5cf6;', 'bar_style' => 'background-color:#8b5cf6;', 'tag_style' => 'background-color:#c4b5fd;border-color:#8b5cf6;color:#000;'],
                                            'Envio' => ['border' => 'border-purple-600 dark:border-purple-500', 'bar' => 'bg-purple-600', 'border_style' => 'border-color:#7c3aed;', 'bar_style' => 'background-color:#7c3aed;', 'tag_style' => 'background-color:#a78bfa;border-color:#7c3aed;color:#000;'],
                                            'Recebimento' => ['border' => 'border-green-400 dark:border-green-400', 'bar' => 'bg-green-400', 'border_style' => 'border-color:#22c55e;', 'bar_style' => 'background-color:#22c55e;', 'tag_style' => 'background-color:#4ade80;border-color:#22c55e;color:#000;'],
                                            'Contestação' => ['border' => 'border-violet-400 dark:border-violet-400', 'bar' => 'bg-violet-400', 'border_style' => 'border-color:#a78bfa;', 'bar_style' => 'background-color:#a78bfa;', 'tag_style' => 'background-color:#c4b5fd;border-color:#a78bfa;color:#000;'],
                                            'Retorno' => ['border' => 'border-amber-400 dark:border-amber-400', 'bar' => 'bg-amber-400', 'border_style' => 'border-color:#f59e0b;', 'bar_style' => 'background-color:#f59e0b;', 'tag_style' => 'background-color:#fbbf24;border-color:#f59e0b;color:#000;'],
                                            'Cancelamento' => ['border' => 'border-red-400 dark:border-red-400', 'bar' => 'bg-red-400', 'border_style' => 'border-color:#ef4444;', 'bar_style' => 'background-color:#ef4444;', 'tag_style' => 'background-color:#f87171;border-color:#ef4444;color:#000;'],
                                            default => ['border' => 'border-slate-300 dark:border-slate-600', 'bar' => 'bg-slate-500', 'border_style' => 'border-color:#94a3b8;', 'bar_style' => 'background-color:#94a3b8;', 'tag_style' => 'background-color:#cbd5e1;border-color:#94a3b8;color:#000;'],
                                        };
                                        $bordaCard = $paletteCard['border'];

                                        $statusCardLabel = match ($statusCard) {
                                            'PENDENTE' => 'Solicitado',
                                            'AGUARDANDO_CONFIRMACAO' => 'Aguardando confirmação',
                                            'LIBERACAO' => 'Liberação',
                                            'CONFIRMADO' => 'Envio',
                                            'RECEBIDO' => 'Recebido',
                                            'NAO_ENVIADO' => 'Cancelado',
                                            'NAO_RECEBIDO' => 'Não recebido',
                                            'CANCELADO' => 'Cancelado',
                                            default => 'Pendente',
                                        };
                                        $statusCardStyle = $paletteCard['tag_style'];
                                        $faixaCard = $paletteCard['bar'];
                                        $bordaCardStyle = $paletteCard['border_style'];
                                        $faixaCardStyle = $paletteCard['bar_style'];
                                        $isSolicitacaoCard = $secaoCard === 'Solicitação';
                                        $isSeparacaoCard = $secaoCard === 'Em análise';
                                        $isLiberacaoCard = $secaoCard === 'Liberação';
                                        $isEnvioCard = $secaoCard === 'Envio';
                                        $isRecebimentoCard = $secaoCard === 'Recebimento';
                                        $isExcecaoCard = in_array($secaoCard, ['Contestação', 'Retorno', 'Cancelamento'], true);
                                        $rastreioCard = trim((string) ($solicitacao->tracking_code ?? ''));
                                        $recebedorMatriculaCard = trim((string) ($solicitacao->matricula_recebedor ?: $solicitacao->solicitante_matricula ?: '-'));
                                        $responsavelCard = $usuarioNomeCurtoCard !== '' ? $usuarioNomeCurtoCard : '-';
                                        $responsavelCard .= $usuarioMatriculaCard !== '' ? ' (' . $usuarioMatriculaCard . ')' : '';
                                    @endphp
                                    <div class="w-[300px] shrink-0 rounded-xl border-2 {{ $bordaCard }} bg-[color:var(--solicitacao-modal-bg,#fcfdff)] dark:bg-slate-900/85 overflow-hidden shadow-sm" style="{{ $bordaCardStyle }}">
                                        <div class="h-1 {{ $faixaCard }}" style="{{ $faixaCardStyle }}"></div>
                                        <div class="px-3 py-2.5 border-b border-[color:var(--solicitacao-modal-border,#d6dde6)] dark:border-slate-700/90 flex items-start justify-between gap-2">
                                            <div class="min-w-0">
                                                <h4 class="text-[11px] font-bold tracking-[0.16em] text-slate-700 dark:text-slate-200 uppercase">{{ $secaoCard }}</h4>
                                                <div class="text-[10px] text-slate-500 dark:text-slate-400">{{ $dataCard ?: '-' }} {{ $horaCard ?: '-' }}</div>
                                            </div>
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold whitespace-nowrap border" style="{{ $statusCardStyle }}">{{ $statusCardLabel }}</span>
                                        </div>

                                        <div class="p-3 space-y-2.5">
                                            <div class="text-[15px] leading-tight font-semibold text-gray-900 dark:text-gray-100">{{ $tituloCard }}</div>

                                            <dl class="space-y-1.5 text-[12px] leading-snug">
                                                <div class="grid grid-cols-[84px,1fr] gap-x-2">
                                                    <dt class="uppercase tracking-wider text-[10px] text-slate-500 dark:text-slate-400">Item</dt>
                                                    <dd class="font-semibold text-gray-900 dark:text-gray-100 break-words">{{ $primeiroItemDescricao ?: '-' }}</dd>
                                                </div>

                                                @if($isSolicitacaoCard || $isSeparacaoCard || $isLiberacaoCard || $isEnvioCard || $isRecebimentoCard)
                                                    <div class="grid grid-cols-[84px,1fr] gap-x-2">
                                                        <dt class="uppercase tracking-wider text-[10px] text-slate-500 dark:text-slate-400">Projeto</dt>
                                                        <dd class="font-semibold text-gray-900 dark:text-gray-100 truncate" title="{{ $projetoLabel !== '' ? $projetoLabel : '-' }}">{{ $projetoLabel !== '' ? $projetoLabel : '-' }}</dd>
                                                    </div>
                                                    <div class="grid grid-cols-[84px,1fr] gap-x-2">
                                                        <dt class="uppercase tracking-wider text-[10px] text-slate-500 dark:text-slate-400">Local</dt>
                                                        <dd class="font-semibold text-gray-900 dark:text-gray-100 truncate" title="{{ $solicitacao->local_destino ?: '-' }}">{{ $solicitacao->local_destino ?: '-' }}</dd>
                                                    </div>
                                                @endif

                                                @if($isSolicitacaoCard || $isSeparacaoCard)
                                                    @if($mesmoSolicitanteRecebedor)
                                                        <div class="grid grid-cols-[84px,1fr] gap-x-2">
                                                            <dt class="uppercase tracking-wider text-[10px] text-slate-500 dark:text-slate-400">Pessoa</dt>
                                                            <dd class="font-semibold text-gray-900 dark:text-gray-100 truncate" title="{{ $solicitanteNomeCurto }}">{{ $solicitanteNomeCurto }}</dd>
                                                        </div>
                                                    @else
                                                        <div class="grid grid-cols-[84px,1fr] gap-x-2">
                                                            <dt class="uppercase tracking-wider text-[10px] text-slate-500 dark:text-slate-400">Solicitante</dt>
                                                            <dd class="font-semibold text-gray-900 dark:text-gray-100 truncate" title="{{ $solicitanteNomeCurto }}">{{ $solicitanteNomeCurto }}</dd>
                                                        </div>
                                                        <div class="grid grid-cols-[84px,1fr] gap-x-2">
                                                            <dt class="uppercase tracking-wider text-[10px] text-slate-500 dark:text-slate-400">Recebedor</dt>
                                                            <dd class="font-semibold text-gray-900 dark:text-gray-100 truncate" title="{{ $recebedorNomeCurto }}">{{ $recebedorNomeCurto }}</dd>
                                                        </div>
                                                    @endif
                                                @endif

                                                @if($isSeparacaoCard)
                                                    <div class="grid grid-cols-[84px,1fr] gap-x-2">
                                                        <dt class="uppercase tracking-wider text-[10px] text-slate-500 dark:text-slate-400">Matrícula</dt>
                                                        <dd class="font-semibold text-gray-900 dark:text-gray-100">{{ $recebedorMatriculaCard !== '' ? $recebedorMatriculaCard : '-' }}</dd>
                                                    </div>
                                                @endif

                                                @if(($isEnvioCard || $isRecebimentoCard || $isExcecaoCard) && $rastreioCard !== '')
                                                    <div class="grid grid-cols-[84px,1fr] gap-x-2">
                                                        <dt class="uppercase tracking-wider text-[10px] text-slate-500 dark:text-slate-400">Rastreio</dt>
                                                        <dd class="font-semibold text-gray-900 dark:text-gray-100 break-all">{{ $rastreioCard }}</dd>
                                                    </div>
                                                @endif

                                                @if($acaoCard === 'retornar' && $statusAnteriorCard !== '')
                                                    <div class="grid grid-cols-[84px,1fr] gap-x-2">
                                                        <dt class="uppercase tracking-wider text-[10px] text-slate-500 dark:text-slate-400">Retorno</dt>
                                                        <dd class="font-semibold text-amber-700 dark:text-amber-300">{{ str_replace('_', ' ', $statusAnteriorCard) }} -> SOLICITADO</dd>
                                                    </div>
                                                @endif

                                                @if($motivoCard !== '')
                                                    <div class="grid grid-cols-[84px,1fr] gap-x-2">
                                                        <dt class="uppercase tracking-wider text-[10px] text-slate-500 dark:text-slate-400">Motivo</dt>
                                                        <dd class="font-semibold text-rose-700 dark:text-rose-300 break-words">{{ $motivoCard }}</dd>
                                                    </div>
                                                @endif
                                            </dl>

                                            <div class="pt-2 border-t border-[color:var(--solicitacao-modal-border,#d6dde6)] dark:border-slate-700/90">
                                                <div class="text-[10px] uppercase tracking-wider text-slate-500 dark:text-slate-400">Responsável</div>
                                                <div class="text-[12px] font-semibold text-gray-900 dark:text-gray-100">{{ $responsavelCard }}</div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-3 {{ $gridClass }}">

                <!-- Coluna Principal (Detalhes + Itens) -->
                <div class="{{ $leftColClass }} space-y-3">

                    <!-- Card de Detalhes -->
                    <div class="bg-[color:var(--solicitacao-modal-bg,#fcfdff)] dark:bg-slate-900/90 shadow-sm rounded-xl border border-[color:var(--solicitacao-modal-border,#d6dde6)] dark:border-slate-700 overflow-hidden">

                        <!-- Header do Card -->
                        <div class="px-4 py-2.5 border-b border-[color:var(--solicitacao-modal-border,#d6dde6)] dark:border-slate-700 flex items-center justify-between gap-2 bg-[color:var(--solicitacao-modal-input-bg,#f7f9fc)] dark:bg-slate-800/80">
                            <div class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400">
                                    <svg class="h-4 w-4" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-[13px] font-semibold text-gray-900 dark:text-white">Detalhes da Solicitação</h3>
                                    <p class="text-[10px] text-gray-500 dark:text-slate-400 mt-0.5">Resumo das informações principais</p>
                                </div>
                            </div>
                            <button type="button" @click="showDetails = !showDetails" class="ml-auto inline-flex items-center gap-1 text-[10px] uppercase tracking-wider text-gray-400 hover:text-gray-600 dark:text-slate-500 dark:hover:text-slate-300 transition-colors">
                                <span x-text="showDetails ? 'Contrair' : 'Expandir'"></span>
                                <svg class="w-3 h-3 transition-transform" :class="showDetails ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </div>

                        <!-- Grid de Informacoes -->
                        <div class="p-4" x-show="showDetails" x-transition.duration.250ms>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div class="rounded-lg border border-[color:var(--solicitacao-modal-border,#d6dde6)] dark:border-slate-700 bg-[color:var(--solicitacao-modal-input-bg,#f7f9fc)] dark:bg-slate-900/60 p-3">
                                    <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Solicitante</div>
                                    <div class="text-base font-bold text-gray-900 dark:text-gray-100">{{ $solicitacao->solicitante_nome ?? '-' }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Matrícula: <span class="font-mono font-semibold text-gray-900 dark:text-gray-100">{{ $solicitacao->solicitante_matricula ?? '-' }}</span></div>
                                </div>

                                <div class="rounded-lg border border-[color:var(--solicitacao-modal-border,#d6dde6)] dark:border-slate-700 bg-[color:var(--solicitacao-modal-input-bg,#f7f9fc)] dark:bg-slate-900/60 p-3">
                                    <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Recebedor</div>
                                    <div class="text-base font-bold text-gray-900 dark:text-gray-100">{{ $solicitacao->nome_recebedor ?: ($solicitacao->solicitante_nome ?? '-') }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Matrícula: <span class="font-mono font-semibold text-gray-900 dark:text-gray-100">{{ $solicitacao->matricula_recebedor ?: ($solicitacao->solicitante_matricula ?? '-') }}</span></div>
                                    @if(trim((string) ($solicitacao->nome_recebedor ?? '')) !== '' && mb_strtoupper(trim((string) $solicitacao->nome_recebedor), 'UTF-8') !== mb_strtoupper(trim((string) ($solicitacao->solicitante_nome ?? '')), 'UTF-8'))
                                        <div class="text-xs text-cyan-700 dark:text-cyan-300 mt-1 font-medium">Recebedor diferente do solicitante</div>
                                    @endif
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                                <div class="rounded-lg border border-[color:var(--solicitacao-modal-border,#d6dde6)] dark:border-slate-700 bg-[color:var(--solicitacao-modal-input-bg,#f7f9fc)] dark:bg-slate-900/60 p-3">
                                    <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Local / UF</div>
                                    <div class="text-base font-bold text-gray-900 dark:text-gray-100">{{ $solicitacao->local_destino ?? '-' }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">UF: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $solicitacao->uf ?? '-' }}</span></div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Setor: <span class="text-gray-900 dark:text-gray-100">{{ $solicitacao->setor ?? 'Setor não informado' }}</span></div>
                                </div>

                                <div class="rounded-lg border border-[color:var(--solicitacao-modal-border,#d6dde6)] dark:border-slate-700 bg-[color:var(--solicitacao-modal-input-bg,#f7f9fc)] dark:bg-slate-900/60 p-3">
                                    <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Projeto / Destinação</div>
                                    <div class="text-base font-bold text-gray-900 dark:text-gray-100">{{ $solicitacao->projeto?->NOMEPROJETO ?? '-' }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Cód: <span class="font-mono font-semibold text-gray-900 dark:text-gray-100">{{ $solicitacao->projeto?->CDPROJETO ?? '-' }}</span></div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Destinação: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $solicitacao->destination_type ? 'Filial/Projeto' : '-' }}</span></div>
                                    <div class="flex items-center gap-2 mt-2">
                                        <span class="text-xs text-slate-500 dark:text-slate-400">Status:</span>
                                        <x-status-badge :status="$statusBadgeAtual" :color-map="$statusColors" class="px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider rounded-full shadow-sm" />
                                    </div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Criado em: <span class="font-semibold text-gray-900 dark:text-gray-200">{{ optional($solicitacao->created_at)->format('d/m/Y H:i') }}</span></div>
                                    @if($solicitacao->tracking_code)
                                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Rastreio: <span class="font-mono font-semibold text-gray-900 dark:text-gray-100">{{ $solicitacao->tracking_code }}</span></div>
                                    @endif
                                </div>
                            </div>

                            <!-- Observação -->
                            @if($solicitacao->observacao)
                                <div class="mx-4 mt-2 mb-4 pt-3 border-t border-[color:var(--solicitacao-modal-border,#d6dde6)]">
                                    <h4 class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Observação do Solicitante</h4>
                                    <div class="bg-[color:var(--solicitacao-modal-input-bg,#f7f9fc)] dark:bg-slate-900/75 rounded-lg p-3 text-xs text-gray-700 dark:text-slate-200 italic whitespace-pre-line border border-[color:var(--solicitacao-modal-border,#d6dde6)] dark:border-slate-700">
                                        {{ $solicitacao->observacao }}
                                    </div>
                                </div>
                            @endif

                            <!-- Itens Solicitados (dentro de Detalhes) -->
                            <div class="mx-4 mt-2 mb-4 pt-3 border-t border-[color:var(--solicitacao-modal-border,#d6dde6)]">
                                <h4 class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2 flex items-center gap-2">
                                    <svg class="w-3.5 h-3.5 text-indigo-500 flex-shrink-0" style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                    Itens Solicitados
                                </h4>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-xs text-left">
                                        <thead class="text-[10px] text-gray-700 uppercase bg-[color:var(--solicitacao-modal-input-bg,#f7f9fc)] dark:bg-slate-800/80 dark:text-slate-300 border-b border-[color:var(--solicitacao-modal-border,#d6dde6)] dark:border-slate-700">
                                            <tr>
                                                <th class="px-3 py-2 font-semibold tracking-wide">Descrição / Patrimônio</th>
                                                <th class="px-3 py-2 font-semibold tracking-wide text-center">Qtd</th>
                                                <th class="px-3 py-2 font-semibold tracking-wide text-center">Unidade</th>
                                                <th class="px-3 py-2 font-semibold tracking-wide">Observação</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-[color:var(--solicitacao-modal-border,#d6dde6)]">
                                            @forelse($solicitacao->itens as $item)
                                                <tr class="hover:bg-[color:var(--solicitacao-modal-input-bg,#f7f9fc)] dark:hover:bg-slate-800/70 transition-colors">
                                                    <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">{{ trim((string) ($item->descricao ?? '')) !== '' ? $item->descricao : 'Item sem descrição' }}</td>
                                                    <td class="px-3 py-2 text-center text-gray-600 dark:text-slate-300">{{ $item->quantidade }}</td>
                                                    <td class="px-3 py-2 text-center text-gray-500 dark:text-slate-400">{{ $item->unidade ?: '-' }}</td>
                                                    <td class="px-3 py-2 text-gray-500 dark:text-slate-400 italic">{{ $item->observacao ?: '-' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-slate-400">
                                                        Nenhum item registrado para esta solicitação.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>

                @if($canManagePanel)
                    <!-- MODAL: Confirmar Solicitação -->
                    <div x-show="showConfirmModal" x-transition class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50" style="display:none;">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-md w-full mx-4 overflow-hidden">
                            <div class="bg-emerald-600 text-white px-6 py-4 flex items-center justify-between">
                                <h3 class="text-sm font-bold">Confirmar Solicitação</h3>
                                <button @click="showConfirmModal = false" class="text-white/70 hover:text-white">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <form method="POST" action="{{ route('solicitacoes-bens.confirm', $solicitacao->id) }}" class="p-6 space-y-4" data-modal-form>
                                @csrf
                                @method('POST')
                                <p class="text-sm text-gray-600 dark:text-gray-400">Confirme para mover a solicitação para <strong>Em Análise</strong>.</p>
                                <div class="flex gap-2 pt-4">
                                    <button type="button" @click="showConfirmModal = false" class="flex-1 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition">
                                        Cancelar
                                    </button>
                                    <button type="submit" class="flex-1 px-4 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition">
                                        Confirmar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- MODAL: Encaminhar para Liberação -->
                    <div x-show="showForwardModal" x-transition class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50" style="display:none;">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-md w-full mx-4 overflow-hidden">
                            <div class="bg-violet-600 text-white px-6 py-4 flex items-center justify-between">
                                <h3 class="text-sm font-bold">Encaminhar para Liberação</h3>
                                <button @click="showForwardModal = false" class="text-white/70 hover:text-white">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <form method="POST" action="{{ route('solicitacoes-bens.forward-to-liberacao', $solicitacao->id) }}" class="p-6 space-y-4" data-modal-form>
                                @csrf
                                @method('POST')

                                <p class="text-sm text-gray-600 dark:text-gray-400">Confirme para encaminhar a solicitação para a etapa final de liberação.</p>

                                <div class="flex gap-2 pt-4">
                                    <button type="button" @click="showForwardModal = false" class="flex-1 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition">
                                        Cancelar
                                    </button>
                                    <button type="submit" class="flex-1 px-4 py-2 text-xs font-semibold text-white bg-violet-600 hover:bg-violet-700 rounded-lg transition">
                                        Encaminhar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- MODAL: Liberar Pedido -->
                    <div x-show="showApproveModal" x-transition class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50" style="display:none;">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-md w-full mx-4 overflow-hidden">
                            <div class="bg-blue-600 text-white px-6 py-4 flex items-center justify-between">
                                <h3 class="text-sm font-bold">Liberar Pedido</h3>
                                <button @click="showApproveModal = false" class="text-white/70 hover:text-white">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <form method="POST" action="{{ route('solicitacoes-bens.release', $solicitacao->id) }}" class="p-6 space-y-4" data-modal-form>
                                @csrf
                                @method('POST')

                                <p class="text-sm text-gray-600 dark:text-gray-400">Confirme para concluir a etapa de <strong>Liberação</strong> e mover a solicitação para <strong>Envio</strong>.</p>

                                <div class="flex gap-2 pt-4">
                                    <button type="button" @click="showApproveModal = false" class="flex-1 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition">
                                        Cancelar
                                    </button>
                                    <button type="submit" class="flex-1 px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">
                                        Liberar Pedido
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- MODAL: Enviar Pedido -->
                    <div x-show="showSendModal" x-transition class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50" style="display:none;">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-md w-full mx-4 overflow-hidden">
                            <div class="bg-indigo-600 text-white px-6 py-4 flex items-center justify-between">
                                <h3 class="text-sm font-bold">Enviar Pedido</h3>
                                <button @click="showSendModal = false" class="text-white/70 hover:text-white">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <form method="POST" action="{{ route('solicitacoes-bens.send', $solicitacao->id) }}" class="p-6 space-y-4" data-modal-form>
                                @csrf
                                @method('POST')

                                <p class="text-sm text-gray-600 dark:text-gray-400">Informe o código de rastreio para registrar o envio do pedido.</p>
                                <div>
                                    <label for="tracking_code_enviado" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Código de Rastreio *</label>
                                    <input type="text" id="tracking_code_enviado" name="tracking_code" required
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 text-xs h-8 px-3"
                                        placeholder="Ex: RAS-2026-001" />
                                </div>

                                <div class="flex gap-2 pt-4">
                                    <button type="button" @click="showSendModal = false" class="flex-1 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition">
                                        Cancelar
                                    </button>
                                    <button type="submit" class="flex-1 px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition">
                                        Enviar Pedido
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- MODAL: Cancelar Solicitação -->
                    <div x-show="showNotSentModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4 py-6 dark:bg-black/70" style="display:none;">
                        <div class="w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl" x-data="{ motivoPadrao: '', outroMotivo: '' }">
                            <div class="flex items-center justify-between bg-rose-600 px-6 py-4 text-white">
                                <h3 class="text-sm font-bold">Cancelar Solicitação</h3>
                                <button @click="showNotSentModal = false" class="text-white/70 hover:text-white">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <form method="POST" action="{{ route('solicitacoes-bens.not-sent', $solicitacao->id) }}" class="space-y-4 p-6" data-modal-form>
                                @csrf
                                @method('POST')

                                <p class="text-sm leading-6 text-slate-600">Use esta opção quando a solicitação não puder seguir no fluxo. Selecione um motivo comum ou descreva manualmente o cancelamento.</p>
                                <div class="space-y-1">
                                    <label for="motivo_padrao_cancelamento" class="mb-1 block text-xs font-medium text-slate-700">Motivo comum</label>
                                    <select id="motivo_padrao_cancelamento"
                                        x-model="motivoPadrao"
                                        @change="
                                            if (motivoPadrao && motivoPadrao !== 'OUTRO') {
                                                $refs.justificativaNaoEnviado.value = motivoPadrao;
                                            } else if (motivoPadrao === 'OUTRO') {
                                                $refs.justificativaNaoEnviado.value = outroMotivo;
                                            } else {
                                                $refs.justificativaNaoEnviado.value = '';
                                            }
                                        "
                                        class="block h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-xs text-slate-800 shadow-sm focus:border-rose-400 focus:ring-rose-400">
                                        <option value="">Selecione...</option>
                                        <option value="Sem estoque">Sem estoque</option>
                                        <option value="OUTRO">Outro motivo</option>
                                    </select>
                                </div>
                                <div x-show="motivoPadrao === 'OUTRO'" x-transition style="display: none;">
                                    <label for="justificativa_nao_enviado" class="mb-1 block text-xs font-medium text-slate-700">Motivo do cancelamento *</label>
                                    <textarea id="justificativa_nao_enviado" name="justificativa_nao_enviado" x-model="outroMotivo" :required="motivoPadrao === 'OUTRO'" rows="3" x-ref="justificativaNaoEnviado"
                                        class="block w-full rounded-md border border-slate-300 bg-white p-3 text-xs text-slate-800 shadow-sm focus:border-rose-400 focus:ring-rose-400"
                                        placeholder="Descreva o motivo do cancelamento da solicitação..."></textarea>
                                </div>

                                <div class="grid gap-3 pt-4 sm:grid-cols-2">
                                    <button type="button" @click="showNotSentModal = false" class="inline-flex w-full items-center justify-center rounded-lg border border-slate-200 bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-200">
                                        Voltar
                                    </button>
                                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-rose-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-rose-700">
                                        Confirmar Cancelamento
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- MODAL: Confirmar Recebimento -->
                    <div x-show="showReceiveModal" x-transition class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50" style="display:none;">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-md w-full mx-4 overflow-hidden">
                            <div class="bg-cyan-600 text-white px-6 py-4 flex items-center justify-between">
                                <h3 class="text-sm font-bold">Confirmar Recebimento</h3>
                                <button @click="showReceiveModal = false" class="text-white/70 hover:text-white">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <form method="POST" action="{{ route('solicitacoes-bens.receive', $solicitacao->id) }}" class="p-6 space-y-4" data-modal-form>
                                @csrf
                                @method('POST')

                                <p class="text-sm text-gray-600 dark:text-gray-400">Confirma que o item foi recebido e que esta solicitação deve ser encerrada?</p>

                                <div class="flex gap-2 pt-4">
                                    <button type="button" @click="showReceiveModal = false" class="flex-1 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition">
                                        Voltar
                                    </button>
                                    <button type="submit" class="flex-1 px-4 py-2 text-xs font-bold rounded-lg transition"
                                        style="background:#22d3ee;border:1px solid #67e8f9;color:#082f49;">
                                        Confirmar Recebimento
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- MODAL: Pedido Não Recebido -->
                    <div x-show="showNotReceivedModal" x-transition class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50" style="display:none;">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-md w-full mx-4 overflow-hidden">
                            <div class="bg-rose-600 text-white px-6 py-4 flex items-center justify-between">
                                <h3 class="text-sm font-bold">Pedido Não Recebido</h3>
                                <button @click="showNotReceivedModal = false" class="text-white/70 hover:text-white">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <form method="POST" action="{{ route('solicitacoes-bens.not-received', $solicitacao->id) }}" class="p-6 space-y-4" data-modal-form>
                                @csrf
                                @method('POST')

                                <div>
                                    <label for="justificativa_nao_recebido" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Justificativa *</label>
                                    <textarea id="justificativa_nao_recebido" name="justificativa_nao_recebido" required rows="3"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 text-xs p-2"
                                        placeholder="Descreva o motivo do não recebimento (ex.: item divergente, avariado, não entregue no local)..."></textarea>
                                </div>

                                <div class="flex gap-2 pt-4">
                                    <button type="button" @click="showNotReceivedModal = false" class="flex-1 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition">
                                        Voltar
                                    </button>
                                    <button type="submit" class="flex-1 px-4 py-2 text-xs font-semibold rounded-lg transition"
                                        style="background:#e11d48;border:1px solid #fb7185;color:#fff;">
                                        Salvar Não Recebido
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- MODAL: Contestar Não Recebido -->
                    <div x-show="showContestNotReceivedModal" x-transition class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50" style="display:none;">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-md w-full mx-4 overflow-hidden">
                            <div class="bg-violet-600 text-white px-6 py-4 flex items-center justify-between">
                                <h3 class="text-sm font-bold">Contestar Não Recebido</h3>
                                <button @click="showContestNotReceivedModal = false" class="text-white/70 hover:text-white">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <form method="POST" action="{{ route('solicitacoes-bens.contest-not-received', $solicitacao->id) }}" class="p-6 space-y-4" data-modal-form>
                                @csrf
                                @method('POST')

                                <div>
                                    <label for="status_destino" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Novo status do fluxo *</label>
                                    <select id="status_destino" name="status_destino" required
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 text-xs h-9 px-3">
                                        <option value="">Selecione...</option>
                                        <option value="PENDENTE">Solicitado</option>
                                        <option value="AGUARDANDO_CONFIRMACAO">Em análise</option>
                                        <option value="CONFIRMADO">Enviado</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="motivo_contestacao" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Motivo da contestação *</label>
                                    <textarea id="motivo_contestacao" name="motivo_contestacao" required rows="3"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 text-xs p-2"
                                        placeholder="Descreva a contestação e a ação tomada no fluxo..."></textarea>
                                </div>

                                <div class="flex gap-2 pt-4">
                                    <button type="button" @click="showContestNotReceivedModal = false" class="flex-1 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition">
                                        Voltar
                                    </button>
                                    <button type="submit" class="flex-1 px-4 py-2 text-xs font-semibold text-white bg-violet-600 hover:bg-violet-700 rounded-lg transition">
                                        Salvar Contestação
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- MODAL: Retornar para An&aacute;lise -->
                    <div x-show="showReturnModal" x-transition class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50" style="display:none;">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-md w-full mx-4 overflow-hidden">
                            <div class="bg-amber-600 text-white px-6 py-4 flex items-center justify-between">
                                <h3 class="text-sm font-bold">Voltar para Em An&aacute;lise</h3>
                                <button @click="showReturnModal = false" class="text-white/70 hover:text-white">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <form method="POST" action="{{ route('solicitacoes-bens.return-to-analysis', $solicitacao->id) }}" class="p-6 space-y-4" data-modal-form>
                                @csrf
                                @method('POST')

                                <p class="text-sm text-gray-600 dark:text-gray-400">Descreva o motivo para retornar a solicita&ccedil;&atilde;o para <strong>Em An&aacute;lise</strong>.</p>

                                <div>
                                    <label for="motivo_retorno" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Motivo *</label>
                                    <textarea id="motivo_retorno" name="motivo_retorno" required rows="3"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 text-xs p-2"
                                        placeholder="Descreva o motivo do retorno..."></textarea>
                                </div>

                                <div class="flex gap-2 pt-4">
                                    <button type="button" @click="showReturnModal = false" class="flex-1 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition">
                                        Cancelar
                                    </button>
                                    <button type="submit" class="flex-1 px-4 py-2 text-xs font-semibold text-white bg-amber-600 hover:bg-amber-700 rounded-lg transition border border-amber-700/70">
                                        Voltar para Em An&aacute;lise
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- MODAL: Cancelar Solicitação -->
                    <div x-show="showCancelModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4 py-6 dark:bg-black/70" style="display:none;">
                        <div class="w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl">
                            <div class="flex items-center justify-between bg-red-600 px-6 py-4 text-white">
                                <h3 class="text-sm font-bold">Cancelar Solicitação</h3>
                                <button @click="showCancelModal = false" class="text-white/70 hover:text-white">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <form method="POST" action="{{ route('solicitacoes-bens.cancel', $solicitacao->id) }}" class="space-y-4 p-6" data-modal-form>
                                @csrf
                                @method('POST')
                                
                                <div>
                                    <label for="justificativa_cancelamento" class="mb-1 block text-xs font-medium text-slate-700">Motivo do Cancelamento *</label>
                                    <textarea id="justificativa_cancelamento" name="justificativa_cancelamento" required rows="3"
                                        class="block w-full rounded-md border border-slate-300 bg-white p-3 text-xs text-slate-800 shadow-sm focus:border-red-400 focus:ring-red-400"
                                        placeholder="Descreva o motivo do cancelamento..."></textarea>
                                </div>

                                <div class="grid gap-3 pt-4 sm:grid-cols-2">
                                    <button type="button" @click="showCancelModal = false" class="inline-flex w-full items-center justify-center rounded-lg border border-slate-200 bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-200">
                                        Voltar
                                    </button>
                                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-red-700">
                                        Cancelar Solicitação
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- MODAL: Solicitar Novamente (Cancelada) -->
                    <div x-show="showRecreateCancelledModal" x-transition class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50" style="display:none;">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-md w-full mx-4 overflow-hidden">
                            <div class="bg-indigo-600 text-white px-6 py-4 flex items-center justify-between">
                                <h3 class="text-sm font-bold">Solicitar Novamente</h3>
                                <button @click="showRecreateCancelledModal = false" class="text-white/70 hover:text-white">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <form method="POST" action="{{ route('solicitacoes-bens.recreate-cancelled', $solicitacao->id) }}" class="p-6 space-y-4" data-modal-form>
                                @csrf
                                @method('POST')

                                <p class="text-sm text-gray-600 dark:text-gray-300">
                                    Uma nova solicitação será criada com os mesmos dados e itens desta solicitação cancelada.
                                </p>

                                <div>
                                    <label for="motivo_reenvio" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Motivo da correção *</label>
                                    <textarea id="motivo_reenvio" name="motivo_reenvio" required rows="3"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 text-xs p-2"
                                        placeholder="Descreva o que foi corrigido para reenviar a solicitação..."></textarea>
                                </div>

                                <div class="flex gap-2 pt-4">
                                    <button type="button" @click="showRecreateCancelledModal = false" class="flex-1 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition">
                                        Voltar
                                    </button>
                                    <button type="submit" class="flex-1 px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition">
                                        Criar Nova Solicitação
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Card de Histórico (legado - oculto) -->
            <div class="hidden mt-4 bg-[color:var(--solicitacao-modal-bg,#fcfdff)] shadow-sm rounded-xl border border-[color:var(--solicitacao-modal-border,#d6dde6)] overflow-hidden">
                <div class="px-4 py-3 border-b border-[color:var(--solicitacao-modal-border,#d6dde6)] bg-[color:var(--solicitacao-modal-input-bg,#f7f9fc)] flex items-center justify-between gap-2">
                    <h3 class="text-[13px] font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        Acompanhamento do Pedido
                    </h3>
                    <button type="button" @click="toggleSection('history')" class="ml-auto inline-flex items-center gap-1 text-[10px] uppercase tracking-wider text-gray-400 hover:text-gray-600 dark:text-slate-500 dark:hover:text-slate-300 transition-colors">
                        <span x-text="openSection === 'history' ? 'Contrair' : 'Expandir'"></span>
                        <svg class="w-3 h-3 transition-transform" :class="openSection === 'history' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>

                <!-- Body do Histórico -->
                <div class="p-6 overflow-x-auto" x-show="openSection === 'history'" x-transition.duration.300ms>
                    @php
                        // Dados do histórico - ORDENADO CRONOLOGICAMENTE
                        $historicoStatus = collect();
                        if (\Illuminate\Support\Facades\Schema::hasTable('solicitacoes_bens_status_historico')) {
                            $historicoStatus = $solicitacao->historicoStatus
                                ? $solicitacao->historicoStatus->sortBy('created_at')
                                : collect();
                        }

                        $statusAtual = $solicitacao->status;
                        
                        // Definição dos steps (Mantendo icones padrão Laravel/Tailwind)
                        $steps = [
                            [
                                'id' => 'PENDENTE',
                                'label' => 'Solicitado',
                                'desc' => 'Solicitação Aberta',
                                'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'
                            ],
                            [
                                'id' => 'AGUARDANDO_CONFIRMACAO',
                                'label' => 'Em Análise',
                                'desc' => 'Aguardando Aprovação',
                                'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'
                            ],
                            [
                                'id' => 'CONFIRMADO',
                                'label' => 'Concluído',
                                'desc' => 'Finalizado',
                                'icon' => 'M5 13l4 4L19 7'
                            ]
                        ];

                        $currentStepIndex = 0;
                        if ($statusAtual === 'AGUARDANDO_CONFIRMACAO') $currentStepIndex = 1;
                        if ($statusAtual === 'CONFIRMADO') $currentStepIndex = 2;
                        $isCancelado = ($statusAtual === 'CANCELADO');
                    @endphp

                    <!-- 1. Stepper Visual Simplificado -->
                    @if(!$isCancelado)
                        <div class="max-w-4xl mx-auto mb-10 px-4">
                            @php
                                $maxStepIndex = max(1, count($steps) - 1);
                                $progressClass = $currentStepIndex <= 0
                                    ? 'w-0'
                                    : ($currentStepIndex >= $maxStepIndex ? 'w-full' : 'w-1/2');
                            @endphp
                            <div class="relative flex items-center justify-between w-full">
                                <!-- Line Line Behind -->
                                <div class="absolute top-1/2 left-0 w-full h-0.5 bg-gray-200 dark:bg-gray-700 -translate-y-1/2 z-0"></div>
                                <div class="absolute top-1/2 left-0 h-0.5 bg-indigo-500 transition-all duration-1000 -translate-y-1/2 z-0 {{ $progressClass }}"></div>

                                @foreach($steps as $index => $step)
                                    @php
                                        $isActive = $index <= $currentStepIndex;
                                        $isCurrent = $index === $currentStepIndex;
                                        
                                        // Classes padrão Tailwind para consistência
                                        $bgClass = $isActive 
                                            ? 'bg-indigo-600 border-indigo-600 text-white' 
                                            : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-400 dark:text-gray-500';
                                            
                                        $textClass = $isActive
                                            ? 'text-indigo-600 dark:text-indigo-400 font-bold'
                                            : 'text-gray-500 dark:text-gray-400 font-medium';
                                    @endphp
                                    
                                    <div class="relative z-10 flex flex-col items-center bg-transparent group">
                                        <div class="w-10 h-10 rounded-full border-2 flex items-center justify-center transition-colors duration-200 {{ $bgClass }} {{ $isCurrent ? 'ring-4 ring-indigo-100 dark:ring-indigo-900/40' : '' }}">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $step['icon'] }}" />
                                            </svg>
                                        </div>
                                        <div class="absolute top-12 w-32 text-center">
                                            <p class="text-[11px] uppercase tracking-wider {{ $textClass }}">{{ $step['label'] }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <!-- Estado Cancelado - Padrão de Erro do Sistema -->
                        <div class="mb-8 mx-auto max-w-2xl bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-800 rounded-lg p-4 flex items-center justify-center gap-3 text-red-700 dark:text-red-400">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <h4 class="font-bold text-sm">Solicitação Cancelada</h4>
                                <p class="text-xs opacity-80">Este processo foi encerrado.</p>
                            </div>
                        </div>
                    @endif

                    <!-- 2. Timeline de Eventos (Cards Padrão) -->
                    <div class="mt-8 pt-6 border-t border-gray-100 dark:border-gray-800">
                        <h4 class="mb-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Linha do Tempo
                        </h4>
                        
                        <div class="relative group/scroll">
                            <div class="flex overflow-x-auto pb-4 gap-4 scrollbar-thin scrollbar-track-gray-50 scrollbar-thumb-gray-200 dark:scrollbar-track-slate-800 dark:scrollbar-thumb-slate-600 snap-x">
                                @foreach($historicoStatus as $index => $hist)
                                    @php
                                        $dataHist = optional($hist->created_at)->format('d/m/Y');
                                        $horaHist = optional($hist->created_at)->format('H:i');
                                        $usuarioNome = array_values(array_filter(explode(' ', $hist->usuario?->NOMEUSER ?? $hist->usuario?->NMLOGIN ?? "-")))[0];
                                        
                                        $statusNovo = $hist->status_novo;
                                        $acao       = $hist->acao;
                                        
                                        // Padrão de cores consistente com o sistema (Alertas/Sucesso/Neutro)
                                        $isSuccess = ($statusNovo === 'CONFIRMADO' || $acao === 'aprovar');
                                        $isDanger  = ($statusNovo === 'CANCELADO' || $acao === 'retornar' || $acao === 'cancelar');
                                        $isWarning = ($statusNovo === 'AGUARDANDO_CONFIRMACAO');
                                        
                                        if($isSuccess) {
                                            $borderClass = 'border-l-4 border-l-emerald-500';
                                            $iconClass   = 'text-emerald-500';
                                            $badgeClass  = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300';
                                        } elseif($isDanger) {
                                            $borderClass = 'border-l-4 border-l-red-500';
                                            $iconClass   = 'text-red-500';
                                            $badgeClass  = 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
                                        } elseif($isWarning) {
                                            $borderClass = 'border-l-4 border-l-amber-500';
                                            $iconClass   = 'text-amber-500';
                                            $badgeClass  = 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300';
                                        } else {
                                            $borderClass = 'border-l-4 border-l-gray-400';
                                            $iconClass   = 'text-gray-400';
                                            $badgeClass  = 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                                        }

                                        $isLast = $loop->last;
                                    @endphp

                                    <!-- Card Timeline -->
                                    <div class="min-w-[200px] max-w-[200px] snap-start bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden {{ $borderClass }}">
                                        <!-- Header -->
                                        <div class="px-3 py-2 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-between items-center">
                                            <span class="text-[10px] font-bold text-gray-600 dark:text-gray-300">{{ $dataHist }}</span>
                                            <span class="text-[9px] text-gray-400">{{ $horaHist }}</span>
                                        </div>

                                        <!-- Body -->
                                        <div class="p-3">
                                            <div class="flex items-center gap-2 mb-2">
                                                <div class="{{ $iconClass }}">
                                                    @if($isSuccess) <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    @elseif($isDanger) <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    @else <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> 
                                                    @endif
                                                </div>
                                                <p class="text-xs font-bold text-gray-800 dark:text-gray-100 truncate" title="{{ $acao }}">
                                                    {{ $hist->acao ? ucfirst($hist->acao) : 'Atualização' }}
                                                </p>
                                            </div>
                                            
                                            <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-semibold mb-3 {{ $badgeClass }}">
                                                {{ str_replace('_', ' ', $statusNovo) }}
                                            </span>

                                            <div class="flex items-center gap-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                                                <div class="w-5 h-5 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center text-[9px] font-bold text-gray-600 dark:text-gray-300">
                                                    {{ substr($usuarioNome, 0, 1) }}
                                                </div>
                                                <span class="text-[10px] text-gray-600 dark:text-gray-400 truncate">{{ $usuarioNome }}</span>
                                            </div>
                                            
                                            @if(!empty($hist->motivo))
                                                <div class="mt-2 text-[9px] italic text-gray-400 dark:text-gray-500 truncate" title="{{ $hist->motivo }}">
                                                    "{{ $hist->motivo }}"
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <!-- Seta de conexão (exceto no último) -->
                                    @if(!$isLast)
                                        <div class="flex items-center text-gray-300 dark:text-gray-600">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </div>
                                    @endif

                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
