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
    $returnButtonLabel = match ($solicitacao->status) {
        'AGUARDANDO_CONFIRMACAO', 'NAO_ENVIADO' => 'Voltar para Solicitado',
        'LIBERACAO' => 'Voltar para Em Análise',
        'CONFIRMADO' => 'Voltar para Liberação',
        'NAO_RECEBIDO' => 'Voltar para Envio',
        default => 'Voltar um status',
    };

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
                showArchiveCancelledModal: false,
                openSection: 'history',
                closeActionModals() {
                    this.showConfirmModal = false;
                    this.showForwardModal = false;
                    this.showApproveModal = false;
                    this.showSendModal = false;
                    this.showNotSentModal = false;
                    this.showReceiveModal = false;
                    this.showNotReceivedModal = false;
                    this.showContestNotReceivedModal = false;
                    this.showReturnModal = false;
                    this.showCancelModal = false;
                    this.showRecreateCancelledModal = false;
                    this.showArchiveCancelledModal = false;
                },
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
                            <button type="button" @click="showForwardModal = true" class="sol-flow-action sol-flow-action--forward">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                                Encaminhar para Liberação
                            </button>
                        @endif
                        @if(!in_array($solicitacao->status, ['CANCELADO', 'NAO_ENVIADO', 'RECEBIDO'], true) && $canCancelAction)
                            <button type="button" @click="showCancelModal = true" class="sol-flow-action sol-flow-action--cancel">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 11-12.728 0 9 9 0 0112.728 0zM9 9l6 6m0-6l-6 6" /></svg>
                                Cancelar Solicita&ccedil;&atilde;o
                            </button>
                        @endif
                        @if($solicitacao->status === 'LIBERACAO' && $canReleaseAction)
                            <button type="button" @click="showApproveModal = true" class="sol-flow-action sol-flow-action--release">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                Liberar Pedido
                            </button>
                        @endif
                        @php
                            $canGoBackOneStep = in_array($solicitacao->status, ['AGUARDANDO_CONFIRMACAO', 'NAO_ENVIADO', 'LIBERACAO', 'CONFIRMADO', 'NAO_RECEBIDO'], true);
                        @endphp
                        @if($canGoBackOneStep && $canReturnAction)
                            <button type="button" @click="showReturnModal = true" class="sol-flow-action sol-flow-action--return">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12a9 9 0 1015.364-6.364M3 12H9m-6 0l3-3m-3 3l3 3" /></svg>
                                {{ $returnButtonLabel }}
                            </button>
                        @endif
                        @if($solicitacao->status === 'CANCELADO' && $canRecriarCancelada)
                            <button type="button" @click="showRecreateCancelledModal = true" class="flex-1 min-w-[220px] inline-flex items-center justify-center gap-2 h-11 px-3 rounded-lg text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-sm">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8 8 0 106.582 9m0 0H9m-4 0V4" /></svg>
                                Solicitar Novamente
                            </button>
                            <button type="button" @click="showArchiveCancelledModal = true" class="flex-1 min-w-[220px] inline-flex items-center justify-center gap-2 h-11 px-3 rounded-lg text-xs font-semibold text-white bg-gray-500 hover:bg-gray-600 transition shadow-sm">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M7 8V6a1 1 0 011-1h8a1 1 0 011 1v2m-1 0v10a2 2 0 01-2 2H10a2 2 0 01-2-2V8m3 4v5m4-5v5" /></svg>
                                Arquivar Solicitação
                            </button>
                        @endif
                        @if($solicitacao->status === 'CONFIRMADO' && trim((string) ($solicitacao->tracking_code ?? '')) === '' && $canSendAction)
                            <button type="button" @click="showSendModal = true" class="sol-flow-action sol-flow-action--send">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                                Enviar Pedido
                            </button>
                        @endif
                        @if($solicitacao->status === 'CONFIRMADO' && trim((string) ($solicitacao->tracking_code ?? '')) !== '' && $canMarkReceived)
                            <button type="button" @click="showReceiveModal = true" class="sol-flow-action sol-flow-action--receive">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                Confirmar Recebimento
                            </button>
                        @endif
                        @if($solicitacao->status === 'CONFIRMADO' && trim((string) ($solicitacao->tracking_code ?? '')) !== '' && $canMarkNotReceived)
                            <button type="button" @click="showNotReceivedModal = true" class="sol-flow-action sol-flow-action--not-received">
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
                $mapearStatusParaEtapa = function (?string $status): int {
                    return match (strtoupper(trim((string) $status))) {
                        'PENDENTE' => 1,
                        'AGUARDANDO_CONFIRMACAO', 'NAO_ENVIADO' => 2,
                        'LIBERACAO' => 3,
                        'CONFIRMADO', 'NAO_RECEBIDO' => 4,
                        'RECEBIDO' => 5,
                        default => 1,
                    };
                };
                $ultimoEventoCancelamentoTopo = $historicoStatusTopo->reverse()->first(function ($hist) {
                    $statusNovo = strtoupper((string) ($hist->status_novo ?? ''));
                    $acao = strtolower((string) ($hist->acao ?? ''));

                    return in_array($acao, ['cancelar', 'nao_enviado'], true)
                        || in_array($statusNovo, ['CANCELADO', 'NAO_ENVIADO'], true);
                });
                $isCancelamentoAtual = in_array($statusAtualTopo, ['CANCELADO', 'NAO_ENVIADO'], true);
                $etapaCancelamento = $isCancelamentoAtual
                    ? $mapearStatusParaEtapa(data_get($ultimoEventoCancelamentoTopo, 'status_anterior', $statusAtualTopo))
                    : null;
                $ultimoHistoricoComMotivo = $historicoStatusTopo->reverse()->first(function ($hist) {
                    return trim((string) ($hist->motivo ?? '')) !== '';
                });
                $motivoDetalhesCompleto = trim((string) data_get($ultimoHistoricoComMotivo, 'motivo', $solicitacao->justificativa_cancelamento ?? ''));
                $rotuloMotivoDetalhes = match ($statusAtualTopo) {
                    'CANCELADO', 'NAO_ENVIADO' => 'Motivo do cancelamento',
                    'NAO_RECEBIDO' => 'Motivo do não recebimento',
                    default => 'Motivo informado',
                };
                $etapaAtual = match ($statusAtualTopo) {
                    'PENDENTE', 'CANCELADO' => 1,
                    'AGUARDANDO_CONFIRMACAO', 'NAO_ENVIADO' => 2,
                    'LIBERACAO' => 3,
                    'CONFIRMADO', 'NAO_RECEBIDO' => 4,
                    'RECEBIDO' => 5,
                    default => 1,
                };
                if ($isCancelamentoAtual && $etapaCancelamento !== null) {
                    $etapaAtual = $etapaCancelamento;
                }
                $progressBlueWidth = (($etapaAtual - 1) / 4) * 100;
                $progressRedLeft = 0;
                $progressRedWidth = 0;
                if ($isCancelamentoAtual && $etapaAtual > 1) {
                    $progressBlueWidth = (($etapaAtual - 2) / 4) * 100;
                    $progressRedLeft = $progressBlueWidth;
                    $progressRedWidth = 25;
                }
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
                $stepsTopo = [
                    ['n' => 1, 'label' => 'Solicitado'],
                    ['n' => 2, 'label' => 'Em Análise'],
                    ['n' => 3, 'label' => 'Liberação'],
                    ['n' => 4, 'label' => 'Envio'],
                    ['n' => 5, 'label' => 'Recebido'],
                ];
                $historicoEtapas = $historicoStatusTopo
                    ->sortBy('created_at')
                    ->values()
                    ->filter(function ($hist) {
                        $status = strtoupper((string) ($hist->status_novo ?? ''));
                        $acao = strtolower((string) ($hist->acao ?? ''));
                        return $status !== '' || $acao !== '';
                    })
                    ->values();

                if ($historicoEtapas->isEmpty()) {
                    $historicoEtapas = collect([
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

                $buscarPrimeiroHistorico = fn (callable $callback) => $historicoEtapas->first($callback);
                $buscarUltimoHistorico = fn (callable $callback) => $historicoEtapas->reverse()->first($callback);
                $formatarDataHoraHistorico = function ($historico, $fallback = null): array {
                    $dataBase = data_get($historico, 'created_at') ?: $fallback;

                    return [
                        'data' => optional($dataBase)->format('d/m/Y') ?: '-',
                        'hora' => optional($dataBase)->format('H:i') ?: '-',
                    ];
                };
                $formatarResponsavelHistorico = function ($historico, ?string $fallbackNome = null, ?string $fallbackMatricula = null) use ($nomeCurto): string {
                    $nomeUsuario = trim((string) data_get($historico, 'usuario.NOMEUSER', data_get($historico, 'usuario.NMLOGIN', '')));
                    $matriculaUsuario = trim((string) data_get($historico, 'usuario.CDMATRFUNCIONARIO', ''));

                    $nomeBase = $nomeUsuario !== '' ? $nomeCurto($nomeUsuario) : trim((string) $fallbackNome);
                    $matriculaBase = $matriculaUsuario !== '' ? $matriculaUsuario : trim((string) $fallbackMatricula);

                    if ($nomeBase === '' && $matriculaBase === '') {
                        return '-';
                    }

                    return trim($nomeBase . ($matriculaBase !== '' ? ' (' . $matriculaBase . ')' : ''));
                };
                $paletasEtapa = [
                    'Solicitado' => ['border_class' => 'border-yellow-400 dark:border-yellow-400', 'border_style' => 'border-color:#facc15;', 'bar_style' => 'background-color:#facc15;', 'tag_style' => 'background-color:#facc15;border-color:#eab308;color:#000;'],
                    'Em Análise' => ['border_class' => 'border-blue-400 dark:border-blue-400', 'border_style' => 'border-color:#60a5fa;', 'bar_style' => 'background-color:#60a5fa;', 'tag_style' => 'background-color:#60a5fa;border-color:#3b82f6;color:#000;'],
                    'Liberação' => ['border_class' => 'border-violet-500 dark:border-violet-400', 'border_style' => 'border-color:#8b5cf6;', 'bar_style' => 'background-color:#8b5cf6;', 'tag_style' => 'background-color:#c4b5fd;border-color:#8b5cf6;color:#000;'],
                    'Envio' => ['border_class' => 'border-sky-500 dark:border-sky-400', 'border_style' => 'border-color:#0ea5e9;', 'bar_style' => 'background-color:#0ea5e9;', 'tag_style' => 'background-color:#dbeafe;border-color:#60a5fa;color:#1e3a8a;'],
                    'Envio Concluido' => ['border_class' => 'border-cyan-500 dark:border-cyan-400', 'border_style' => 'border-color:#06b6d4;', 'bar_style' => 'background-color:#06b6d4;', 'tag_style' => 'background-color:#cffafe;border-color:#22d3ee;color:#155e75;'],
                    'Recebido' => ['border_class' => 'border-green-400 dark:border-green-400', 'border_style' => 'border-color:#22c55e;', 'bar_style' => 'background-color:#22c55e;', 'tag_style' => 'background-color:#4ade80;border-color:#22c55e;color:#000;'],
                    'Não Recebido' => ['border_class' => 'border-rose-400 dark:border-rose-400', 'border_style' => 'border-color:#fb7185;', 'bar_style' => 'background-color:#fb7185;', 'tag_style' => 'background-color:#fecdd3;border-color:#fb7185;color:#881337;'],
                    'Cancelado' => ['border_class' => 'border-red-400 dark:border-red-400', 'border_style' => 'border-color:#f87171;', 'bar_style' => 'background-color:#f87171;', 'tag_style' => 'background-color:#fecaca;border-color:#f87171;color:#7f1d1d;'],
                    'Retornado' => ['border_class' => 'border-orange-400 dark:border-orange-400', 'border_style' => 'border-color:#fb923c;', 'bar_style' => 'background-color:#fb923c;', 'tag_style' => 'background-color:#fed7aa;border-color:#fb923c;color:#9a3412;'],
                ];
                $montarCardEtapa = function (
                    string $secao,
                    string $titulo,
                    string $statusLabel,
                    ?object $historico,
                    array $detalhes,
                    ?string $responsavel = null,
                    ?string $fallbackNome = null,
                    ?string $fallbackMatricula = null,
                    $fallbackData = null,
                    ?string $paleta = null
                ) use ($formatarDataHoraHistorico, $formatarResponsavelHistorico, $paletasEtapa): array {
                    $cores = $paletasEtapa[$paleta ?: $secao] ?? $paletasEtapa['Solicitado'];
                    $dataHora = $formatarDataHoraHistorico($historico, $fallbackData);
                    $responsavelFinal = $responsavel ?: $formatarResponsavelHistorico($historico, $fallbackNome, $fallbackMatricula);

                    return [
                        'secao' => $secao,
                        'titulo' => $titulo,
                        'status_label' => $statusLabel,
                        'data' => $dataHora['data'],
                        'hora' => $dataHora['hora'],
                        'detalhes' => collect($detalhes)->filter(fn ($detalhe) => trim((string) ($detalhe['value'] ?? '')) !== '')->values()->all(),
                        'responsavel' => $responsavelFinal,
                        'border_class' => $cores['border_class'],
                        'border_style' => $cores['border_style'],
                        'bar_style' => $cores['bar_style'],
                        'tag_style' => $cores['tag_style'],
                    ];
                };

                $histSolicitado = $buscarPrimeiroHistorico(function ($hist) {
                    $status = strtoupper((string) ($hist->status_novo ?? ''));
                    $acao = strtolower((string) ($hist->acao ?? ''));
                    return $status === 'PENDENTE' || $acao === 'criado';
                }) ?? $historicoEtapas->first();
                $histAnalise = $buscarUltimoHistorico(function ($hist) {
                    $status = strtoupper((string) ($hist->status_novo ?? ''));
                    $acao = strtolower((string) ($hist->acao ?? ''));
                    return $status === 'AGUARDANDO_CONFIRMACAO' || $acao === 'confirmar';
                });
                $histLiberacaoEncaminhada = $buscarUltimoHistorico(function ($hist) {
                    $status = strtoupper((string) ($hist->status_novo ?? ''));
                    $acao = strtolower((string) ($hist->acao ?? ''));
                    return $acao === 'encaminhar_liberacao' || $status === 'LIBERACAO';
                });
                $histLiberacaoFinal = $buscarUltimoHistorico(
                    fn ($hist) => strtolower((string) ($hist->acao ?? '')) === 'liberar_pedido'
                );
                $histEnvio = $buscarUltimoHistorico(function ($hist) use ($solicitacao) {
                    $status = strtoupper((string) ($hist->status_novo ?? ''));
                    $acao = strtolower((string) ($hist->acao ?? ''));
                    return in_array($acao, ['enviar_pedido', 'liberar_enviar'], true)
                        || ($status === 'CONFIRMADO' && trim((string) ($solicitacao->tracking_code ?? '')) !== '');
                });
                $histRecebimento = $buscarUltimoHistorico(function ($hist) {
                    $status = strtoupper((string) ($hist->status_novo ?? ''));
                    $acao = strtolower((string) ($hist->acao ?? ''));
                    return in_array($status, ['RECEBIDO', 'NAO_RECEBIDO'], true) || $acao === 'contestar_nao_recebido';
                });

                $detalhesBase = [
                    ['label' => 'Item', 'value' => $primeiroItemDescricao ?: '-'],
                    ['label' => 'Projeto', 'value' => $projetoLabel !== '' ? $projetoLabel : '-'],
                    ['label' => 'Local', 'value' => $solicitacao->local_destino ?: '-'],
                ];
                $detalhesPessoa = $mesmoSolicitanteRecebedor
                    ? [['label' => 'Pessoa', 'value' => $solicitanteNomeCurto]]
                    : [
                        ['label' => 'Solicitante', 'value' => $solicitanteNomeCurto],
                        ['label' => 'Recebedor', 'value' => $recebedorNomeCurto],
                    ];
                $responsavelSolicitante = trim($solicitanteNomeCurto . ($solicitanteMatricula !== '' ? ' (' . $solicitanteMatricula . ')' : ''));
                $recebedorMatriculaCard = trim((string) ($solicitacao->matricula_recebedor ?: $solicitacao->solicitante_matricula ?: '-'));
                $rastreioAtual = trim((string) ($solicitacao->tracking_code ?? ''));
                $etapaAnaliseConcluida = in_array($statusFluxo, ['AGUARDANDO_CONFIRMACAO', 'LIBERACAO', 'CONFIRMADO', 'RECEBIDO', 'NAO_ENVIADO', 'NAO_RECEBIDO'], true)
                    || $histAnalise !== null;
                $etapaLiberacaoConcluida = in_array($statusFluxo, ['LIBERACAO', 'CONFIRMADO', 'RECEBIDO', 'NAO_RECEBIDO'], true)
                    || $histLiberacaoEncaminhada !== null
                    || $histLiberacaoFinal !== null;
                $etapaEnvioConcluida = in_array($statusFluxo, ['CONFIRMADO', 'RECEBIDO', 'NAO_RECEBIDO'], true)
                    || $histEnvio !== null;
                $etapaRecebimentoConcluida = in_array($statusFluxo, ['RECEBIDO', 'NAO_RECEBIDO'], true)
                    || $histRecebimento !== null;

                $mapearEtapaFluxo = function (?string $status): ?string {
                    $status = strtoupper(trim((string) $status));

                    return match ($status) {
                        'PENDENTE' => 'Solicitado',
                        'AGUARDANDO_CONFIRMACAO', 'NAO_ENVIADO' => 'Em Análise',
                        'LIBERACAO' => 'Liberação',
                        'CONFIRMADO', 'NAO_RECEBIDO' => 'Envio',
                        'RECEBIDO' => 'Recebido',
                        default => null,
                    };
                };

                $cardSolicitado = $montarCardEtapa(
                    'Solicitado',
                    'Solicitação realizada',
                    'Solicitado',
                    $histSolicitado,
                    array_merge($detalhesBase, $detalhesPessoa),
                    $responsavelSolicitante !== '' ? $responsavelSolicitante : '-',
                    $solicitanteNomeCurto,
                    $solicitanteMatricula,
                    $solicitacao->created_at
                );
                $cardAnalise = $etapaAnaliseConcluida
                    ? $montarCardEtapa(
                        'Em Análise',
                        $histAnalise ? 'Solicitação em análise' : 'Aguardando análise',
                        $histAnalise ? 'Aguardando confirmação' : 'Pendente',
                        $histAnalise,
                        array_merge($detalhesBase, $detalhesPessoa, [
                            ['label' => 'Matrícula', 'value' => $recebedorMatriculaCard !== '' ? $recebedorMatriculaCard : '-'],
                        ]),
                        null,
                        null,
                        $solicitacao->solicitante_matricula ?? null
                    )
                    : null;
                $cardLiberacao = null;
                if ($etapaLiberacaoConcluida) {
                    $cardLiberacao = $histLiberacaoFinal
                        ? $montarCardEtapa(
                            'Liberação',
                            'Pedido liberado',
                            'Liberado',
                            $histLiberacaoFinal,
                            $detalhesBase
                        )
                        : $montarCardEtapa(
                            'Liberação',
                            'Encaminhado para liberação',
                            'Aguardando liberação',
                            $histLiberacaoEncaminhada,
                            $detalhesBase
                        );
                }
                $cardEnvio = null;
                if ($etapaEnvioConcluida) {
                    $cardEnvio = $histEnvio
                        ? $montarCardEtapa(
                            'Envio',
                            'Pedido enviado',
                            'Enviado',
                            $histEnvio,
                            array_merge($detalhesBase, [
                                ['label' => 'Rastreio', 'value' => $rastreioAtual !== '' ? $rastreioAtual : 'Sem rastreio informado'],
                                ['label' => 'Recebimento', 'value' => 'Aguardando confirmação do recebimento.'],
                            ]),
                            null,
                            null,
                            null,
                            null,
                            'Envio Concluido'
                        )
                        : $montarCardEtapa(
                            'Envio',
                            'Aguardando envio do pedido',
                            'Aguardando envio',
                            null,
                            array_merge($detalhesBase, [
                                ['label' => 'Rastreio', 'value' => 'Aguardando informação de rastreio.'],
                            ]),
                            '-',
                            null,
                            null,
                            $solicitacao->updated_at
                        );
                }
                $cardRecebimento = null;
                if ($etapaRecebimentoConcluida) {
                    $cardRecebimento = $statusFluxo === 'NAO_RECEBIDO'
                        ? $montarCardEtapa(
                            'Recebido',
                            'Pedido não recebido',
                            'Não recebido',
                            $histRecebimento,
                            array_merge($detalhesBase, [
                                ['label' => 'Rastreio', 'value' => $rastreioAtual !== '' ? $rastreioAtual : 'Sem rastreio informado'],
                            ]),
                            null,
                            null,
                            null,
                            $solicitacao->updated_at,
                            'Não Recebido'
                        )
                        : $montarCardEtapa(
                            'Recebido',
                            'Recebimento confirmado',
                            'Recebido',
                            $histRecebimento,
                            array_merge($detalhesBase, [
                                ['label' => 'Rastreio', 'value' => $rastreioAtual !== '' ? $rastreioAtual : 'Sem rastreio informado'],
                            ]),
                            null,
                            null,
                            null,
                            $solicitacao->updated_at
                        );
                }

                $cardsFluxo = [];
                $etapasInseridas = [];
                $adicionarEtapa = function (string $chave, ?array $card) use (&$cardsFluxo, &$etapasInseridas): void {
                    if ($card === null || isset($etapasInseridas[$chave])) {
                        return;
                    }

                    $cardsFluxo[] = $card;
                    $etapasInseridas[$chave] = true;
                };
                $montarCardEspecial = function (string $tipo, object $historico) use (
                    $montarCardEtapa,
                    $mapearEtapaFluxo,
                    $detalhesBase,
                    $solicitacao
                ): array {
                    if ($tipo === 'Cancelado') {
                        $motivo = trim((string) ($historico->motivo ?? ''));
                        if ($motivo === '') {
                            $motivo = trim((string) ($solicitacao->justificativa_cancelamento ?? ''));
                        }

                        $etapaOrigem = $mapearEtapaFluxo($historico->status_anterior ?? null)
                            ?: $mapearEtapaFluxo($historico->status_novo ?? null)
                            ?: 'Fluxo atual';

                        return $montarCardEtapa(
                            'Cancelado',
                            'Solicitação cancelada',
                            'Cancelado',
                            $historico,
                            array_merge($detalhesBase, [
                                ['label' => 'Etapa', 'value' => $etapaOrigem],
                                ['label' => 'Motivo', 'value' => $motivo !== '' ? $motivo : 'Motivo não informado.'],
                            ]),
                            null,
                            null,
                            null,
                            null,
                            'Cancelado'
                        );
                    }

                    $etapaOrigem = $mapearEtapaFluxo($historico->status_anterior ?? null) ?: 'Etapa anterior';
                    $etapaDestino = $mapearEtapaFluxo($historico->status_novo ?? null) ?: 'Etapa atual';

                    return $montarCardEtapa(
                        'Retornado',
                        'Retornou para etapa anterior',
                        'Retornado',
                        $historico,
                        array_merge($detalhesBase, [
                            ['label' => 'De', 'value' => $etapaOrigem],
                            ['label' => 'Para', 'value' => $etapaDestino],
                        ]),
                        null,
                        null,
                        null,
                        null,
                        'Retornado'
                    );
                };

                foreach ($historicoEtapas as $hist) {
                    $statusNovoHistorico = strtoupper((string) ($hist->status_novo ?? ''));
                    $acaoHistorico = strtolower((string) ($hist->acao ?? ''));

                    if ($statusNovoHistorico === 'PENDENTE' || $acaoHistorico === 'criado') {
                        $adicionarEtapa('solicitado', $cardSolicitado);
                    }

                    if (
                        $statusNovoHistorico === 'AGUARDANDO_CONFIRMACAO'
                        || $acaoHistorico === 'confirmar'
                        || ($acaoHistorico === 'retornar' && $statusNovoHistorico === 'AGUARDANDO_CONFIRMACAO')
                    ) {
                        $adicionarEtapa('analise', $cardAnalise);
                    }

                    if (
                        $statusNovoHistorico === 'LIBERACAO'
                        || in_array($acaoHistorico, ['encaminhar_liberacao', 'liberar_pedido'], true)
                    ) {
                        $adicionarEtapa('liberacao', $cardLiberacao);
                    }

                    if (
                        $statusNovoHistorico === 'CONFIRMADO'
                        || in_array($acaoHistorico, ['enviar_pedido', 'liberar_enviar'], true)
                    ) {
                        $adicionarEtapa('envio', $cardEnvio);
                    }

                    if (in_array($statusNovoHistorico, ['RECEBIDO', 'NAO_RECEBIDO'], true) || $acaoHistorico === 'contestar_nao_recebido') {
                        $adicionarEtapa('recebido', $cardRecebimento);
                    }

                    if (
                        in_array($acaoHistorico, ['cancelar', 'nao_enviado'], true)
                        || in_array($statusNovoHistorico, ['CANCELADO', 'NAO_ENVIADO'], true)
                    ) {
                        $cardsFluxo[] = $montarCardEspecial('Cancelado', $hist);
                    }

                    if ($acaoHistorico === 'retornar') {
                        $cardsFluxo[] = $montarCardEspecial('Retornado', $hist);
                    }
                }

                $adicionarEtapa('solicitado', $cardSolicitado);
                if ($etapaAnaliseConcluida) {
                    $adicionarEtapa('analise', $cardAnalise);
                }
                if ($etapaLiberacaoConcluida) {
                    $adicionarEtapa('liberacao', $cardLiberacao);
                }
                if ($etapaEnvioConcluida) {
                    $adicionarEtapa('envio', $cardEnvio);
                }
                if ($etapaRecebimentoConcluida) {
                    $adicionarEtapa('recebido', $cardRecebimento);
                }
            @endphp

            <div class="mb-3 bg-[color:var(--solicitacao-modal-bg,#fcfdff)] dark:bg-slate-900/90 shadow-sm rounded-xl border border-[color:var(--solicitacao-modal-border,#d6dde6)] dark:border-slate-700 overflow-hidden">
                <div class="px-4 py-3 border-b border-[color:var(--solicitacao-modal-border,#d6dde6)] dark:border-slate-700 bg-[color:var(--solicitacao-modal-input-bg,#f7f9fc)] dark:bg-slate-800/80 flex items-center justify-between gap-2">
                    <h3 class="text-[13px] font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2" /></svg>
                        Acompanhamento do Pedido
                    </h3>
                    <button type="button" @click="toggleSection('history')" class="ml-auto inline-flex items-center gap-1 text-[10px] uppercase tracking-wider text-gray-400 hover:text-gray-600 dark:text-slate-500 dark:hover:text-slate-300 transition-colors">
                        <span x-text="openSection === 'history' ? 'Fechar' : 'Abrir'"></span>
                        <svg class="w-3 h-3 transition-transform" :class="openSection === 'history' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                </div>
                <div class="p-4">
                    <div class="relative flex items-center justify-between">
                        <div class="absolute top-4 left-4 right-4 h-0.5">
                            <div class="absolute inset-0 bg-gray-200 dark:bg-gray-700"></div>
                            <div class="absolute top-0 left-0 h-0.5 bg-indigo-500 transition-all duration-500" style="width: {{ $progressBlueWidth }}%"></div>
                        @if($progressRedWidth > 0)
                                <div class="absolute top-0 h-0.5 bg-red-500 transition-all duration-500" style="left: {{ $progressRedLeft }}%; width: {{ $progressRedWidth }}%;"></div>
                        @endif
                        </div>
                        @foreach($stepsTopo as $stepTopo)
                            @php
                                $isCancelamentoStep = $isCancelamentoAtual && $stepTopo['n'] === $etapaAtual;
                                $ativo = $isCancelamentoAtual ? $stepTopo['n'] < $etapaAtual : $stepTopo['n'] <= $etapaAtual;
                            @endphp
                            <div class="relative z-10 flex w-24 flex-col items-center">
                                <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center text-xs font-bold {{ $isCancelamentoStep ? 'bg-red-500 border-red-500 text-white' : ($ativo ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white dark:bg-slate-900 border-gray-300 dark:border-gray-600 text-gray-400') }}">
                                    {{ $stepTopo['n'] }}
                                </div>
                                <div class="mt-2 min-h-[38px] text-center">
                                    <span class="block text-[10px] {{ $isCancelamentoStep ? 'text-red-600 dark:text-red-400 font-semibold' : ($ativo ? 'text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-400') }}">
                                        {{ $isCancelamentoStep ? 'Cancelado' : $stepTopo['label'] }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div x-show="openSection === 'history'" x-transition.duration.300ms class="mt-4 border-t border-[color:var(--solicitacao-modal-border,#d6dde6)] pt-4">
                        <div class="overflow-x-auto pb-1">
                            <div class="flex flex-nowrap gap-3 min-w-max">
                                @foreach($cardsFluxo as $cardEtapa)
                                    <div class="w-[300px] shrink-0 rounded-xl border-2 {{ $cardEtapa['border_class'] }} bg-[color:var(--solicitacao-modal-bg,#fcfdff)] dark:bg-slate-900/85 overflow-hidden shadow-sm" style="{{ $cardEtapa['border_style'] }}">
                                        <div class="h-1" style="{{ $cardEtapa['bar_style'] }}"></div>
                                        <div class="px-3 py-2.5 border-b border-[color:var(--solicitacao-modal-border,#d6dde6)] dark:border-slate-700/90 flex items-start justify-between gap-2">
                                            <div class="min-w-0">
                                                <h4 class="text-[11px] font-bold tracking-[0.16em] text-slate-700 dark:text-slate-200 uppercase">{{ $cardEtapa['secao'] }}</h4>
                                                <div class="text-[10px] text-slate-500 dark:text-slate-400">{{ $cardEtapa['data'] }} {{ $cardEtapa['hora'] }}</div>
                                            </div>
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold whitespace-nowrap border" style="{{ $cardEtapa['tag_style'] }}">{{ $cardEtapa['status_label'] }}</span>
                                        </div>

                                        <div class="p-3 space-y-2.5">
                                            <div class="text-[15px] leading-tight font-semibold text-gray-900 dark:text-gray-100">{{ $cardEtapa['titulo'] }}</div>

                                            <dl class="space-y-1.5 text-[12px] leading-snug">
                                                @foreach($cardEtapa['detalhes'] as $detalhe)
                                                    <div class="grid grid-cols-[84px,1fr] gap-x-2">
                                                        <dt class="uppercase tracking-wider text-[10px] text-slate-500 dark:text-slate-400">{{ $detalhe['label'] }}</dt>
                                                        <dd class="font-semibold break-words {{ $detalhe['class'] ?? 'text-gray-900 dark:text-gray-100' }}">{{ $detalhe['value'] }}</dd>
                                                    </div>
                                                @endforeach
                                            </dl>

                                            <div class="pt-2 border-t border-[color:var(--solicitacao-modal-border,#d6dde6)] dark:border-slate-700/90">
                                                <div class="text-[10px] uppercase tracking-wider text-slate-500 dark:text-slate-400">Responsável</div>
                                                <div class="text-[12px] font-semibold text-gray-900 dark:text-gray-100">{{ $cardEtapa['responsavel'] }}</div>
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
                            <button type="button" @click="toggleSection('details')" class="ml-auto inline-flex items-center gap-1 text-[10px] uppercase tracking-wider text-gray-400 hover:text-gray-600 dark:text-slate-500 dark:hover:text-slate-300 transition-colors">
                                <span x-text="openSection === 'details' ? 'Fechar' : 'Abrir'"></span>
                                <svg class="w-3 h-3 transition-transform" :class="openSection === 'details' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </div>

                        <!-- Grid de Informacoes -->
                        <div class="p-4" x-show="openSection === 'details'" x-transition.duration.250ms>
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
                                    <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3">Projeto / Destinação</div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div class="space-y-2">
                                            <div>
                                                <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Cód</div>
                                                <div class="text-base font-bold text-gray-900 dark:text-gray-100">{{ $solicitacao->projeto?->CDPROJETO ?? '-' }}</div>
                                            </div>
                                            <div>
                                                <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Destinação</div>
                                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $solicitacao->destination_type ? 'Filial/Projeto' : '-' }}</div>
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <div>
                                                <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Status</div>
                                                <div class="mt-1">
                                                    <x-status-badge :status="$statusBadgeAtual" :color-map="$statusColors" class="px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider rounded-full shadow-sm" />
                                                </div>
                                            </div>
                                            <div>
                                                <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Criado em</div>
                                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-200">{{ optional($solicitacao->created_at)->format('d/m/Y H:i') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    @if($solicitacao->tracking_code)
                                        <div class="mt-3 text-xs text-slate-500 dark:text-slate-400">Rastreio: <span class="font-mono font-semibold text-gray-900 dark:text-gray-100">{{ $solicitacao->tracking_code }}</span></div>
                                    @endif
                                    @if($motivoDetalhesCompleto !== '')
                                        <div class="mt-3 rounded-lg border border-[color:var(--solicitacao-modal-border,#d6dde6)] dark:border-slate-700 bg-white/70 dark:bg-slate-900/50 p-3">
                                            <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">{{ $rotuloMotivoDetalhes }}</div>
                                            <div class="text-sm leading-6 text-gray-900 dark:text-gray-100 whitespace-pre-line break-words">{{ $motivoDetalhesCompleto }}</div>
                                        </div>
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
                            <form method="POST" action="{{ route('solicitacoes-bens.confirm', $solicitacao->id) }}" class="p-6 space-y-4" data-modal-form @submit="closeActionModals()">
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
                            <form method="POST" action="{{ route('solicitacoes-bens.forward-to-liberacao', $solicitacao->id) }}" class="p-6 space-y-4" data-modal-form @submit="closeActionModals()">
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
                            <form method="POST" action="{{ route('solicitacoes-bens.release', $solicitacao->id) }}" class="p-6 space-y-4" data-modal-form @submit="closeActionModals()">
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
                            <form method="POST" action="{{ route('solicitacoes-bens.send', $solicitacao->id) }}" class="p-6 space-y-4" data-modal-form @submit="closeActionModals()">
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
                            <form method="POST" action="{{ route('solicitacoes-bens.not-sent', $solicitacao->id) }}" class="space-y-4 p-6" data-modal-form @submit="closeActionModals()">
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
                            <form method="POST" action="{{ route('solicitacoes-bens.receive', $solicitacao->id) }}" class="p-6 space-y-4" data-modal-form @submit="closeActionModals()">
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
                            <form method="POST" action="{{ route('solicitacoes-bens.not-received', $solicitacao->id) }}" class="p-6 space-y-4" data-modal-form @submit="closeActionModals()">
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
                            <form method="POST" action="{{ route('solicitacoes-bens.contest-not-received', $solicitacao->id) }}" class="p-6 space-y-4" data-modal-form @submit="closeActionModals()">
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
                                <h3 class="text-sm font-bold">{{ $returnButtonLabel ?? 'Voltar um status' }}</h3>
                                <button @click="showReturnModal = false" class="text-white/70 hover:text-white">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <form method="POST" action="{{ route('solicitacoes-bens.return-to-analysis', $solicitacao->id) }}" class="p-6 space-y-4" data-modal-form @submit="closeActionModals()">
                                @csrf
                                @method('POST')

                                <p class="text-sm text-gray-600 dark:text-gray-400">Descreva o motivo para retornar a solicitação para a etapa anterior.</p>

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
                                        {{ $returnButtonLabel ?? 'Voltar um status' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- MODAL: Cancelar Solicitação -->
                    <div x-show="showCancelModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4 py-6 dark:bg-black/70" style="display:none;">
                        <div class="w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl" x-data="{ motivoPadrao: '', outroMotivo: '' }">
                            <div class="flex items-center justify-between bg-red-600 px-6 py-4 text-white">
                                <h3 class="text-sm font-bold">Cancelar Solicitação</h3>
                                <button @click="showCancelModal = false" class="text-white/70 hover:text-white">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <form method="POST" action="{{ route('solicitacoes-bens.cancel', $solicitacao->id) }}" class="space-y-4 p-6" data-modal-form @submit="closeActionModals()">
                                @csrf
                                @method('POST')

                                <p class="text-sm leading-6 text-slate-600">Use esta opção quando a solicitação não puder seguir no fluxo. Selecione um motivo comum ou descreva manualmente o cancelamento.</p>
                                <div class="space-y-1">
                                    <label for="motivo_padrao_cancelamento_inicial" class="mb-1 block text-xs font-medium text-slate-700">Motivo comum</label>
                                    <select id="motivo_padrao_cancelamento_inicial"
                                        x-model="motivoPadrao"
                                        @change="
                                            if (motivoPadrao && motivoPadrao !== 'OUTRO') {
                                                $refs.justificativaCancelamento.value = motivoPadrao;
                                            } else if (motivoPadrao === 'OUTRO') {
                                                $refs.justificativaCancelamento.value = outroMotivo;
                                            } else {
                                                $refs.justificativaCancelamento.value = '';
                                            }
                                        "
                                        class="block h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-xs text-slate-800 shadow-sm focus:border-red-400 focus:ring-red-400">
                                        <option value="">Selecione...</option>
                                        <option value="Sem estoque">Sem estoque</option>
                                        <option value="OUTRO">Outro motivo</option>
                                    </select>
                                </div>
                                <div x-show="motivoPadrao === 'OUTRO'" x-transition style="display: none;">
                                    <label for="justificativa_cancelamento" class="mb-1 block text-xs font-medium text-slate-700">Motivo do cancelamento *</label>
                                    <textarea id="justificativa_cancelamento" name="justificativa_cancelamento" x-model="outroMotivo" :required="motivoPadrao === 'OUTRO'" rows="3" x-ref="justificativaCancelamento"
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
                            <form method="POST" action="{{ route('solicitacoes-bens.recreate-cancelled', $solicitacao->id) }}" class="p-6 space-y-4" data-modal-form @submit="closeActionModals()">
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

                    <div x-show="showArchiveCancelledModal" x-transition class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50" style="display:none;">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-md w-full mx-4 overflow-hidden">
                            <div class="bg-gray-600 text-white px-6 py-4 flex items-center justify-between">
                                <h3 class="text-sm font-bold">Arquivar Solicitação</h3>
                                <button @click="showArchiveCancelledModal = false" class="text-white/70 hover:text-white">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <form method="POST" action="{{ route('solicitacoes-bens.archive-cancelled', $solicitacao->id) }}" class="p-6 space-y-4" data-modal-form @submit="closeActionModals()">
                                @csrf
                                @method('POST')

                                <p class="text-sm text-gray-600 dark:text-gray-300">
                                    Esta solicitação cancelada será arquivada e deixará de aparecer na listagem principal.
                                </p>

                                <div class="flex gap-2 pt-4">
                                    <button type="button" @click="showArchiveCancelledModal = false" class="flex-1 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition">
                                        Voltar
                                    </button>
                                    <button type="submit" class="flex-1 px-4 py-2 text-xs font-semibold text-white bg-gray-600 hover:bg-gray-700 rounded-lg transition">
                                        Confirmar Arquivamento
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
                        <span x-text="openSection === 'history' ? 'Fechar' : 'Abrir'"></span>
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
