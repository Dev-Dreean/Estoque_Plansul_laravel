@php
    $isModal = $isModal ?? false;
    $containerClass = $isModal ? 'p-4 sm:p-5' : 'py-12';
    $wrapperClass = $isModal ? 'w-full' : 'max-w-6xl mx-auto sm:px-6 lg:px-8';
    $statusColors = [
        'PENDENTE' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-700',
        'AGUARDANDO_CONFIRMACAO' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300 border border-blue-200 dark:border-blue-700',
        'CONFIRMADO' => 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300 border border-green-200 dark:border-green-700',
        'CANCELADO' => 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300 border border-red-200 dark:border-red-700',
    ];
    $matriculaOld = old('matricula_recebedor', $solicitacao->matricula_recebedor ?? '');
    $nomeOld = old('nome_recebedor', $solicitacao->nome_recebedor ?? '');
    $matriculaTrim = trim((string) $matriculaOld);
    $nomeTrim = trim((string) $nomeOld);
    $lookupOnInit = $nomeTrim === '' && $matriculaTrim !== '';
    if ($matriculaTrim !== '' && $nomeTrim !== '') {
        $recebedorDisplay = $matriculaTrim . ' - ' . $nomeTrim;
    } elseif ($nomeTrim !== '') {
        $recebedorDisplay = $nomeTrim;
    } else {
        $recebedorDisplay = $matriculaTrim;
    }
    $canManage = $canManage ?? false;
    $canUpdateData = auth()->user()?->temAcessoTela('1012') ?? false;

    // Se pode gerenciar, usa duas colunas (1/1) a partir de md.
    $gridClass = $canManage ? 'grid-cols-1 md:grid-cols-2' : 'grid-cols-1';
    $leftColClass = '';
@endphp

<div data-solicitacao-modal-content>
    <div class="{{ $containerClass }}">
        <div class="{{ $wrapperClass }}" x-data="{
                showUpdate: true,
                showConfirmModal: false,
                showApproveModal: false,
                showReturnModal: false,
                showCancelModal: false,
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

            <div class="grid gap-3 {{ $gridClass }}">

                <!-- Coluna Principal (Detalhes + Itens) -->
                <div class="{{ $leftColClass }} space-y-3">

                    <!-- Card de Detalhes -->
                    <div class="bg-[color:var(--solicitacao-modal-bg,#fcfdff)] shadow-sm rounded-xl border border-[color:var(--solicitacao-modal-border,#d6dde6)] overflow-hidden">

                        <!-- Header do Card -->
                        <div class="px-4 py-2.5 border-b border-[color:var(--solicitacao-modal-border,#d6dde6)] flex items-center justify-between gap-2 bg-[color:var(--solicitacao-modal-input-bg,#f7f9fc)]">
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
                        </div>

                        <!-- Grid de Informacoes -->
                        <div class="p-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-y-3 gap-x-5">
                                <!-- Solicitante -->
                                <div class="flex items-start gap-3">
                                    <div class="mt-1 p-1.5 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg text-indigo-600 dark:text-indigo-400 flex-shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-0.5">Solicitante</div>
                                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $solicitacao->solicitante_nome ?? '-' }}</div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Matrícula: <span class="font-mono text-gray-700 dark:text-slate-200">{{ $solicitacao->solicitante_matricula ?? '-' }}</span></div>
                                    </div>
                                </div>

                                <!-- Local/UF -->
                                <div class="flex items-start gap-3">
                                    <div class="mt-1 p-1.5 bg-purple-50 dark:bg-purple-900/20 rounded-lg text-purple-600 dark:text-purple-400 flex-shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-0.5">Local / UF</div>
                                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $solicitacao->local_destino ?? '-' }}</div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">UF: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $solicitacao->uf ?? '-' }}</span></div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Setor: <span class="text-gray-700 dark:text-slate-200">{{ $solicitacao->setor ?? 'Setor não informado' }}</span></div>
                                    </div>
                                </div>

                                <!-- Projeto -->
                                <div class="flex items-start gap-3">
                                    <div class="mt-1 p-1.5 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg text-emerald-600 dark:text-emerald-400 flex-shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-0.5">Projeto</div>
                                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $solicitacao->projeto?->NOMEPROJETO ?? '-' }}</div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Cód: <span class="font-mono text-gray-700 dark:text-slate-200">{{ $solicitacao->projeto?->CDPROJETO ?? '-' }}</span></div>
                                    </div>
                                </div>

                                <!-- Destinação (abaixo de Local/UF) -->
                                @if($solicitacao->destination_type)
                                    <div class="col-span-1 sm:col-span-2 lg:col-span-3">
                                        <div class="mt-2">
                                            <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-0.5">Destinação</div>
                                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">Filial/Projeto</div>
                                        </div>
                                    </div>
                                @endif

                                <!-- Situacao -->
                                <div class="flex items-start gap-3 sm:col-span-2 lg:col-span-3">
                                    <div class="mt-1 p-1.5 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-blue-600 dark:text-blue-400 flex-shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-0.5">Situação</div>
                                        <div class="flex flex-col gap-2">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs text-slate-500 dark:text-slate-400">Status:</span>
                                                <x-status-badge :status="$solicitacao->status" :color-map="$statusColors" class="px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider rounded-full shadow-sm" />
                                            </div>
                                            <div class="text-xs text-slate-500 dark:text-slate-400">Criado em: <span class="font-semibold text-gray-900 dark:text-gray-200">{{ optional($solicitacao->created_at)->format('d/m/Y H:i') }}</span></div>
                                            
                                            @if($solicitacao->confirmado_em)
                                                <div class="text-xs text-slate-500 dark:text-slate-400">Confirmado em: <span class="font-semibold text-gray-900 dark:text-gray-200">{{ $solicitacao->confirmado_em->format('d/m/Y H:i') }}</span></div>
                                                <div class="text-xs text-slate-500 dark:text-slate-400">Confirmado por: <span class="font-semibold text-gray-900 dark:text-gray-200">{{ $solicitacao->confirmadoPor?->NOMEUSER ?? '-' }}</span></div>
                                            @endif
                                            
                                            @if($solicitacao->cancelado_em)
                                                <div class="text-xs text-slate-500 dark:text-slate-400">Cancelado em: <span class="font-semibold text-gray-900 dark:text-gray-200">{{ $solicitacao->cancelado_em->format('d/m/Y H:i') }}</span></div>
                                                <div class="text-xs text-slate-500 dark:text-slate-400">Cancelado por: <span class="font-semibold text-gray-900 dark:text-gray-200">{{ $solicitacao->canceladoPor?->NOMEUSER ?? '-' }}</span></div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Código de Rastreio -->
                                @if($solicitacao->tracking_code)
                                    <div class="flex items-start gap-3">
                                        <div class="mt-1 p-1.5 bg-orange-50 dark:bg-orange-900/20 rounded-lg text-orange-600 dark:text-orange-400 flex-shrink-0">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-0.5">Código de Rastreio</div>
                                            <div class="text-sm font-mono font-semibold text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">{{ $solicitacao->tracking_code }}</div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Observacao -->
                            @if($solicitacao->observacao)
                                <div class="mx-4 mt-2 mb-4 pt-3 border-t border-[color:var(--solicitacao-modal-border,#d6dde6)]">
                                    <h4 class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Observação do Solicitante</h4>
                                    <div class="bg-[color:var(--solicitacao-modal-input-bg,#f7f9fc)] rounded-lg p-3 text-xs text-gray-700 dark:text-slate-200 italic whitespace-pre-line border border-[color:var(--solicitacao-modal-border,#d6dde6)]">
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
                                        <thead class="text-[10px] text-gray-700 uppercase bg-[color:var(--solicitacao-modal-input-bg,#f7f9fc)] dark:text-slate-400 border-b border-[color:var(--solicitacao-modal-border,#d6dde6)]">
                                            <tr>
                                                <th class="px-3 py-2 font-semibold tracking-wide">Descrição / Patrimônio</th>
                                                <th class="px-3 py-2 font-semibold tracking-wide text-center">Qtd</th>
                                                <th class="px-3 py-2 font-semibold tracking-wide text-center">Unidade</th>
                                                <th class="px-3 py-2 font-semibold tracking-wide">Observação</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-[color:var(--solicitacao-modal-border,#d6dde6)]">
                                            @forelse($solicitacao->itens as $item)
                                                <tr class="hover:bg-[color:var(--solicitacao-modal-input-bg,#f7f9fc)] transition-colors">
                                                    <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">{{ $item->descricao }}</td>
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

                <!-- Coluna Lateral (Acoes) -->
                @if($canManage)
                    <div class="sm:col-span-1">
                        <div class="bg-[color:var(--solicitacao-modal-bg,#fcfdff)] shadow-lg rounded-xl border border-[color:var(--solicitacao-modal-border,#d6dde6)] overflow-hidden sticky top-4">

                            <!-- Header Acoes -->
                            <!-- Botões de Ação Rápida (Acima do Painel) -->
                            <div class="bg-gradient-to-r from-indigo-50 to-blue-50 dark:from-indigo-900/30 dark:to-blue-900/30 p-3 border-b border-[color:var(--solicitacao-modal-border,#d6dde6)] space-y-2">
                                <!-- Botão Confirmar (Tiago/Beatriz) -->
                                @if($solicitacao->status === 'PENDENTE' && auth()->user()->temAcessoTela('1011'))
                                    <button type="button" @click="showConfirmModal = true" 
                                        class="w-full relative flex justify-center items-center gap-2 py-1.5 px-3 border border-transparent rounded-lg shadow-md text-[11px] font-semibold text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-all dark:focus:ring-offset-slate-900">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Confirmar Solicitação
                                    </button>
                                @endif

                                <!-- Botão Aprovar (Solicitante) - aparece DEPOIS de confirmado -->
                                @if($solicitacao->status === 'AGUARDANDO_CONFIRMACAO' && auth()->user()->temAcessoTela('1014'))
                                    <button type="button" @click="showApproveModal = true" 
                                        class="w-full relative flex justify-center items-center gap-2 py-1.5 px-3 border border-transparent rounded-lg shadow-md text-[11px] font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all dark:focus:ring-offset-slate-900">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h-4V6h4v4zM12 14a2 2 0 100-4 2 2 0 000 4z" />
                                        </svg>
                                        Aprovar Solicitação
                                    </button>
                                @endif

                                <!-- Botao Voltar para An&aacute;lise (Solicitante) - aparece em AGUARDANDO_CONFIRMACAO -->
                                @if($solicitacao->status === 'AGUARDANDO_CONFIRMACAO' && auth()->user()->temAcessoTela('1014'))
                                    <button type="button" @click="showReturnModal = true" 
                                        class="w-full relative flex justify-center items-center gap-2 py-1.5 px-3 border border-transparent rounded-lg shadow-md text-[11px] font-semibold text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-all dark:focus:ring-offset-slate-900">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12a9 9 0 1015.364-6.364M3 12H9m-6 0l3-3m-3 3l3 3" />
                                        </svg>
                                        Voltar para an&aacute;lise
                                    </button>
                                @endif


                                <!-- Botão Cancelar - APENAS em PENDENTE (desaparece após confirmar) -->
                                @if($solicitacao->status === 'PENDENTE' && auth()->user()->temAcessoTela('1015'))
                                    <button type="button" @click="showCancelModal = true" 
                                        class="w-full relative flex justify-center items-center gap-2 py-1.5 px-3 border border-transparent rounded-lg shadow-md text-[11px] font-semibold text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all dark:focus:ring-offset-slate-900">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        Cancelar Solicitação
                                    </button>
                                @endif
                            </div>

                            <div class="bg-[color:var(--solicitacao-modal-input-bg,#f7f9fc)] p-3 border-b border-[color:var(--solicitacao-modal-border,#d6dde6)]">
                                <h3 class="text-sm font-bold text-indigo-600 dark:text-indigo-300">Painel de Controle</h3>
                                <p class="text-slate-500 dark:text-slate-400 text-[10px] mt-0.5">Gerenciamento da Solicitação #{{ $solicitacao->id }}</p>
                            </div>

                            <div class="p-3">
                                @if($canUpdateData)
                                <form method="POST" action="{{ route('solicitacoes-bens.update', $solicitacao) }}" data-modal-form="{{ $isModal ? '1' : '0' }}" class="space-y-3">
                                    @csrf
                                    @method('PATCH')
                                    @if($isModal)
                                        <input type="hidden" name="modal" value="1" />
                                    @endif

                                    <div class="relative">
                                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                            <div class="w-full border-t border-[color:var(--solicitacao-modal-border,#d6dde6)]"></div>
                                        </div>
                                        <div class="relative flex justify-center">
                                            <span class="bg-[color:var(--solicitacao-modal-bg,#fcfdff)] px-2 text-[10px] text-gray-400">Dados de Entrega</span>
                                        </div>
                                    </div>

                                    <!-- Recebedor Info -->
                                    <div class="space-y-3">
                                        <div>
                                            <label for="recebedor_search" class="block text-[10px] font-medium text-gray-700 dark:text-slate-300 mb-1">Responsável Recebedor * <span class="text-red-500">*</span></label>
                                            <x-user-autocomplete 
                                                id="recebedor_search"
                                                name="recebedor_matricula"
                                                :value="$matriculaOld"
                                                :initial-display="$recebedorDisplay"
                                                :lookup-on-init="$lookupOnInit"
                                                placeholder="Digite matrícula ou nome..."
                                                class="h-7 text-xs border-gray-300 dark:border-slate-600" />
                                            <x-input-error :messages="$errors->get('recebedor_matricula')" class="mt-1" />
                                            <p class="mt-1 text-[9px] text-gray-500 dark:text-gray-400">Obrigatório: Deve ter um responsável para aprovar o envio</p>
                                        </div>

                                        <div>
                                            <label for="local_destino_id" class="block text-[10px] font-medium text-gray-700 dark:text-slate-300 mb-1">Local Atualizado</label>
                                            @php
                                                $projetoId = $solicitacao->projeto_id;
                                                $locaisDoProj = $projetoId 
                                                    ? \App\Models\LocalProjeto::where('tabfant_id', $projetoId)->orderBy('delocal')->get()
                                                    : collect([]);
                                                $localAtualId = old('local_destino_id', null);
                                                $localAtualText = '';
                                                if ($localAtualId) {
                                                    $localAtualText = $locaisDoProj->firstWhere('id', $localAtualId)?->delocal ?? '';
                                                } elseif ($solicitacao->local_destino) {
                                                    $localAtualText = $solicitacao->local_destino;
                                                }
                                            @endphp
                                            @php
                                                $locaisOptions = $locaisDoProj->map(function ($local) {
                                                    return ['id' => $local->id, 'label' => $local->delocal];
                                                })->values();
                                            @endphp
                                            <div class="relative" x-data="{
                                                localSearch: @js($localAtualText),
                                                localId: @js($localAtualId),
                                                options: @js($locaisOptions),
                                                showLocalDropdown: false,
                                                filtered() {
                                                    const term = (this.localSearch || '').toLowerCase();
                                                    if (!term) return this.options;
                                                    return this.options.filter(opt => (opt.label || '').toLowerCase().includes(term));
                                                },
                                                selectLocal(option) {
                                                    this.localSearch = option.label;
                                                    this.localId = option.id;
                                                    this.showLocalDropdown = false;
                                                }
                                            }" @click.outside="showLocalDropdown = false">
                                                <input type="text"
                                                    id="local_destino_search"
                                                    name="local_destino"
                                                    placeholder="Buscar local..."
                                                    x-model="localSearch"
                                                    @focus="showLocalDropdown = true"
                                                    @input="showLocalDropdown = true; localId = null"
                                                    @keydown.escape="showLocalDropdown = false"
                                                    class="block w-full h-7 text-xs border-gray-300 dark:border-slate-600 dark:bg-slate-900/80 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                                <input type="hidden" id="local_destino_id" name="local_destino_id" x-model="localId" value="{{ $localAtualId }}" />
                                                <div x-show="showLocalDropdown" x-transition
                                                    class="absolute z-50 mt-1 w-full rounded-md border border-slate-700 bg-slate-950/95 shadow-xl max-h-48 overflow-y-auto ring-1 ring-slate-700/50">
                                                    <template x-for="option in filtered()" :key="option.id">
                                                        <button type="button"
                                                            @click="selectLocal(option)"
                                                            class="w-full text-left px-3 py-2 text-xs text-slate-200 hover:bg-indigo-600/20 focus:outline-none focus:bg-indigo-600/20">
                                                            <span x-text="option.label"></span>
                                                        </button>
                                                    </template>
                                                    <template x-if="filtered().length === 0">
                                                        <div class="px-3 py-2 text-xs text-slate-400">
                                                            Nenhum local encontrado
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                            <x-input-error :messages="$errors->get('local_destino_id')" class="mt-1" />
                                        </div>
                                    </div>

                                        <div class="relative">
                                            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                                <div class="w-full border-t border-[color:var(--solicitacao-modal-border,#d6dde6)]"></div>
                                            </div>
                                            <div class="relative flex justify-center">
                                                <span class="bg-[color:var(--solicitacao-modal-bg,#fcfdff)] px-2 text-[10px] text-gray-400">Controle</span>
                                            </div>
                                        </div>

                                        <!-- Controle Interno -->
                                        <div>
                                            <label for="observacao_controle" class="block text-[10px] font-medium text-gray-700 dark:text-slate-300 mb-1">Notas Internas</label>
                                            <textarea id="observacao_controle" name="observacao_controle"
                                                class="block w-full rounded-lg border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900/80 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xs"
                                                rows="2" placeholder="Anotações do almoxarifado...">{{ old('observacao_controle', $solicitacao->observacao_controle) }}</textarea>
                                            <x-input-error :messages="$errors->get('observacao_controle')" class="mt-1" />
                                        </div>

                                        <div class="space-y-2">
                                            <button type="submit" class="w-full relative flex justify-center items-center gap-2 py-1.5 px-3 border border-transparent rounded-lg shadow-md text-[11px] font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all dark:focus:ring-offset-slate-900">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                                </svg>
                                                Salvar Alterações
                                            </button>
                                        </div>
                                    </form>
                                @else
                                <div class="rounded-lg border border-dashed border-slate-600/60 bg-slate-900/60 px-3 py-2 text-[11px] text-slate-400">
                                    Sem permissão para atualizar dados de entrega.
                                </div>
                                @endif
                            </div>
                            <div class="bg-[color:var(--solicitacao-modal-input-bg,#f7f9fc)] px-4 py-2 border-t border-[color:var(--solicitacao-modal-border,#d6dde6)] text-center">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wide">Última atualização: {{ $solicitacao->updated_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    </div>

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
                            <form method="POST" action="{{ route('solicitacoes-bens.confirm', $solicitacao->id) }}" class="p-6 space-y-4">
                                @csrf
                                @method('POST')
                                
                                <div>
                                    <label for="confirm_recebedor_search" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Responsavel Recebedor *</label>
                                    <x-user-autocomplete
                                        id="confirm_recebedor_search"
                                        name="recebedor_matricula"
                                        :value="$matriculaOld"
                                        :initial-display="$recebedorDisplay"
                                        :lookup-on-init="$lookupOnInit"
                                        placeholder="Digite matricula ou nome..."
                                        class="h-8 text-xs border-gray-300 dark:border-gray-600" />
                                    <x-input-error :messages="$errors->get('recebedor_matricula')" class="mt-1" />
                                </div>

                                <div>
                                    <label for="tracking_code" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Código de Rastreio *</label>
                                    <input type="text" id="tracking_code" name="tracking_code" required 
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 text-xs h-8 px-3"
                                        placeholder="Ex: RAS-2025-001" />
                                </div>
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

                    <!-- MODAL: Aprovar Solicitação -->
                    <div x-show="showApproveModal" x-transition class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50" style="display:none;">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-md w-full mx-4 overflow-hidden">
                            <div class="bg-blue-600 text-white px-6 py-4 flex items-center justify-between">
                                <h3 class="text-sm font-bold">Aprovar Solicitação</h3>
                                <button @click="showApproveModal = false" class="text-white/70 hover:text-white">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <form method="POST" action="{{ route('solicitacoes-bens.approve', $solicitacao->id) }}" class="p-6 space-y-4">
                                @csrf
                                @method('POST')
                                
                                <p class="text-sm text-gray-600 dark:text-gray-400">Tem certeza que deseja aprovar esta solicitação? Ela será marcada como confirmada.</p>

                                <div class="flex gap-2 pt-4">
                                    <button type="button" @click="showApproveModal = false" class="flex-1 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition">
                                        Cancelar
                                    </button>
                                    <button type="submit" class="flex-1 px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">
                                        Aprovar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- MODAL: Retornar para An&aacute;lise -->
                    <div x-show="showReturnModal" x-transition class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50" style="display:none;">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-md w-full mx-4 overflow-hidden">
                            <div class="bg-amber-600 text-white px-6 py-4 flex items-center justify-between">
                                <h3 class="text-sm font-bold">Voltar para An&aacute;lise</h3>
                                <button @click="showReturnModal = false" class="text-white/70 hover:text-white">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <form method="POST" action="{{ route('solicitacoes-bens.return-to-analysis', $solicitacao->id) }}" class="p-6 space-y-4">
                                @csrf
                                @method('POST')

                                <p class="text-sm text-gray-600 dark:text-gray-400">Descreva o problema para retornar a solicita&ccedil;&atilde;o para an&aacute;lise.</p>

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
                                        Voltar para an&aacute;lise
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- MODAL: Cancelar Solicitação -->
                    <div x-show="showCancelModal" x-transition class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50" style="display:none;">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-md w-full mx-4 overflow-hidden">
                            <div class="bg-red-600 text-white px-6 py-4 flex items-center justify-between">
                                <h3 class="text-sm font-bold">Cancelar Solicitação</h3>
                                <button @click="showCancelModal = false" class="text-white/70 hover:text-white">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <form method="POST" action="{{ route('solicitacoes-bens.cancel', $solicitacao->id) }}" class="p-6 space-y-4">
                                @csrf
                                @method('POST')
                                
                                <div>
                                    <label for="justificativa_cancelamento" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Motivo do Cancelamento *</label>
                                    <textarea id="justificativa_cancelamento" name="justificativa_cancelamento" required rows="3"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 text-xs p-2"
                                        placeholder="Descreva o motivo do cancelamento..."></textarea>
                                </div>

                                <div class="flex gap-2 pt-4">
                                    <button type="button" @click="showCancelModal = false" class="flex-1 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition">
                                        Voltar
                                    </button>
                                    <button type="submit" class="flex-1 px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition">
                                        Cancelar Solicitação
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Card de Historico (Abaixo dos Outros) -->
            <div class="mt-4 bg-[color:var(--solicitacao-modal-bg,#fcfdff)] shadow-sm rounded-xl border border-[color:var(--solicitacao-modal-border,#d6dde6)] overflow-hidden">
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

                <!-- Body do Historico -->
                <div class="p-6 overflow-x-auto" x-show="openSection === 'history'" x-transition.duration.300ms>
                    @php
                        // Dados do histórico - ORDENADO CRONOLOGICAMENTE
                        $historicoStatus = $solicitacao->historicoStatus
                            ? $solicitacao->historicoStatus->sortBy('created_at')
                            : collect();

                        $statusAtual = $solicitacao->status;
                        
                        // Definição dos steps (Mantendo icones padrão Laravel/Tailwind)
                        $steps = [
                            [
                                'id' => 'PENDENTE',
                                'label' => 'Criado',
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
                            <div class="relative flex items-center justify-between w-full">
                                <!-- Line Line Behind -->
                                <div class="absolute top-1/2 left-0 w-full h-0.5 bg-gray-200 dark:bg-gray-700 -translate-y-1/2 z-0"></div>
                                <div class="absolute top-1/2 left-0 h-0.5 bg-indigo-500 transition-all duration-1000 -translate-y-1/2 z-0" 
                                     style="width: {{ ($currentStepIndex / (count($steps) - 1)) * 100 }}%"></div>

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
