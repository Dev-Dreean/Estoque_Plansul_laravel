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
    $lookupOnInit = trim((string) $nomeOld) === '' && trim((string) $matriculaOld) !== '';
    $canUpdate = $canUpdate ?? false;

    // Se pode atualizar, usa colunas lateral. Se nao, full width.
    $gridClass = $canUpdate ? 'lg:grid-cols-3' : 'lg:grid-cols-1';
    $leftColClass = $canUpdate ? 'lg:col-span-2' : '';
@endphp

<div data-solicitacao-modal-content>
    <div class="{{ $containerClass }}">
        <div class="{{ $wrapperClass }}">
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

            <div class="grid gap-3 {{ $gridClass }}" x-data="{ showUpdate: true, showConfirmModal: false, showApproveModal: false, showCancelModal: false }">

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
                                    <h3 class="text-[13px] font-semibold text-gray-900 dark:text-white">Detalhes da Solicita&ccedil;&atilde;o</h3>
                                    <p class="text-[10px] text-gray-500 dark:text-slate-400 mt-0.5">Resumo das informa&ccedil;&otilde;es principais</p>
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
                                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Matr&iacute;cula: <span class="font-mono text-gray-700 dark:text-slate-200">{{ $solicitacao->solicitante_matricula ?? '-' }}</span></div>
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
                                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Setor: <span class="text-gray-700 dark:text-gray-200">{{ $solicitacao->setor ?? 'Setor n&atilde;o informado' }}</span></div>
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
                                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">C&oacute;d: <span class="font-mono text-gray-700 dark:text-slate-200">{{ $solicitacao->projeto?->CDPROJETO ?? '-' }}</span></div>
                                    </div>
                                </div>

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

                                <!-- Tipo de Destino -->
                                @if($solicitacao->destination_type)
                                    <div class="flex items-start gap-3">
                                        <div class="mt-1 p-1.5 bg-cyan-50 dark:bg-cyan-900/20 rounded-lg text-cyan-600 dark:text-cyan-400 flex-shrink-0">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-0.5">Destinação</div>
                                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                @if($solicitacao->destination_type === 'FILIAL')
                                                    🏢 Filial
                                                @elseif($solicitacao->destination_type === 'PROJETO')
                                                    📍 Projeto
                                                @else
                                                    {{ $solicitacao->destination_type }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif

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
                                    <h4 class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Observa&ccedil;&atilde;o do Solicitante</h4>
                                    <div class="bg-[color:var(--solicitacao-modal-input-bg,#f7f9fc)] rounded-lg p-3 text-xs text-gray-700 dark:text-slate-200 italic whitespace-pre-line border border-[color:var(--solicitacao-modal-border,#d6dde6)]">
                                        {{ $solicitacao->observacao }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Card de Itens -->
                    <div class="bg-[color:var(--solicitacao-modal-bg,#fcfdff)] shadow-sm rounded-xl border border-[color:var(--solicitacao-modal-border,#d6dde6)] overflow-hidden">
                        <div class="px-4 py-2.5 border-b border-[color:var(--solicitacao-modal-border,#d6dde6)] bg-[color:var(--solicitacao-modal-input-bg,#f7f9fc)]">
                            <h3 class="text-[13px] font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                Itens Solicitados
                            </h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs text-left">
                                <thead class="text-[10px] text-gray-700 uppercase bg-[color:var(--solicitacao-modal-input-bg,#f7f9fc)] dark:text-slate-400 border-b border-[color:var(--solicitacao-modal-border,#d6dde6)]">
                                    <tr>
                                        <th class="px-3 py-2 font-semibold tracking-wide">Descri&ccedil;&atilde;o / Patrim&ocirc;nio</th>
                                        <th class="px-3 py-2 font-semibold tracking-wide text-center">Qtd</th>
                                        <th class="px-3 py-2 font-semibold tracking-wide text-center">Unidade</th>
                                        <th class="px-3 py-2 font-semibold tracking-wide">Observa&ccedil;&atilde;o</th>
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
                                                Nenhum item registrado para esta solicita&ccedil;&atilde;o.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Coluna Lateral (Acoes) -->
                @if($canUpdate)
                    <div class="lg:col-span-1">
                        <div class="bg-[color:var(--solicitacao-modal-bg,#fcfdff)] shadow-lg rounded-xl border border-[color:var(--solicitacao-modal-border,#d6dde6)] overflow-hidden sticky top-4">

                            <!-- Header Acoes -->
                            <div class="bg-[color:var(--solicitacao-modal-input-bg,#f7f9fc)] p-3 border-b border-[color:var(--solicitacao-modal-border,#d6dde6)]">
                                <h3 class="text-sm font-bold text-indigo-600 dark:text-indigo-300">Painel de Controle</h3>
                                <p class="text-slate-500 dark:text-slate-400 text-[10px] mt-0.5">Gerenciamento da Solicita&ccedil;&atilde;o #{{ $solicitacao->id }}</p>
                            </div>

                            <div class="p-3">
                                <div x-data="matriculaLookup({
                                    matriculaOld: @js($matriculaOld),
                                    nomeOld: @js($nomeOld),
                                    lookupOnInit: @js($lookupOnInit)
                                })" x-init="initLookup()">

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
                                                <label for="matricula_recebedor" class="block text-[10px] font-medium text-gray-700 dark:text-slate-300 mb-1">Matr&iacute;cula Recebedor</label>
                                                <div class="relative rounded-md shadow-sm">
                                                    <input id="matricula_recebedor" name="matricula_recebedor" type="text"
                                                        class="block w-full rounded-md border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900/80 focus:border-indigo-500 focus:ring-indigo-500 text-xs h-7"
                                                        value="{{ $matriculaOld }}" x-model="matricula" @blur="onMatriculaBlur" @input="onMatriculaInput" placeholder="Ex: 12345" />
                                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none" x-show="matriculaExiste" style="display: none;">
                                                        <svg class="h-4 w-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </div>
                                                </div>
                                                <x-input-error :messages="$errors->get('matricula_recebedor')" class="mt-1" />
                                            </div>

                                            <div>
                                                <label for="nome_recebedor" class="block text-[10px] font-medium text-gray-700 dark:text-slate-300 mb-1">Nome Recebedor</label>
                                                <input id="nome_recebedor" name="nome_recebedor" type="text"
                                                    class="block w-full rounded-md border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xs h-7 transition-colors"
                                                    value="{{ $nomeOld }}" x-model="nome" x-bind:readonly="nomeBloqueado"
                                                    x-bind:class="nomeBloqueado ? 'text-gray-500' : 'bg-white dark:bg-slate-900/80'" />
                                                <x-input-error :messages="$errors->get('nome_recebedor')" class="mt-1" />
                                            </div>

                                            <div>
                                                <label for="local_destino" class="block text-[10px] font-medium text-gray-700 dark:text-slate-300 mb-1">Local Atualizado</label>
                                                <input id="local_destino" name="local_destino" type="text"
                                                    class="block w-full rounded-md border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900/80 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xs h-7"
                                                    value="{{ old('local_destino', $solicitacao->local_destino) }}" />
                                                <x-input-error :messages="$errors->get('local_destino')" class="mt-1" />
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
                                                rows="2" placeholder="Anota&ccedil;&otilde;es do almoxarifado...">{{ old('observacao_controle', $solicitacao->observacao_controle) }}</textarea>
                                            <x-input-error :messages="$errors->get('observacao_controle')" class="mt-1" />
                                        </div>

                                        <div class="space-y-2">
                                            <button type="submit" class="w-full relative flex justify-center items-center gap-2 py-1.5 px-3 border border-transparent rounded-lg shadow-md text-[11px] font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all dark:focus:ring-offset-slate-900">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                                </svg>
                                                Salvar Altera&ccedil;&otilde;es
                                            </button>

                                            <!-- Botões de Ação do Fluxo de Aprovação -->
                                            <div class="border-t border-gray-200 dark:border-gray-700 pt-2 space-y-2">
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

                                                <!-- Botão Aprovar (Solicitante) -->
                                                @if($solicitacao->status === 'AGUARDANDO_CONFIRMACAO' && $solicitacao->solicitante_id === auth()->id())
                                                    <button type="button" @click="showApproveModal = true" 
                                                        class="w-full relative flex justify-center items-center gap-2 py-1.5 px-3 border border-transparent rounded-lg shadow-md text-[11px] font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all dark:focus:ring-offset-slate-900">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h-4V6h4v4zM12 14a2 2 0 100-4 2 2 0 000 4z" />
                                                        </svg>
                                                        Aprovar Solicitação
                                                    </button>
                                                @endif

                                                <!-- Botão Cancelar -->
                                                @if(in_array($solicitacao->status, ['PENDENTE', 'AGUARDANDO_CONFIRMACAO']) && $solicitacao->solicitante_id === auth()->id())
                                                    <button type="button" @click="showCancelModal = true" 
                                                        class="w-full relative flex justify-center items-center gap-2 py-1.5 px-3 border border-transparent rounded-lg shadow-md text-[11px] font-semibold text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all dark:focus:ring-offset-slate-900">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                        Cancelar Solicitação
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="bg-[color:var(--solicitacao-modal-input-bg,#f7f9fc)] px-4 py-2 border-t border-[color:var(--solicitacao-modal-border,#d6dde6)] text-center">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wide">&Uacute;ltima atualiza&ccedil;&atilde;o: {{ $solicitacao->updated_at->diffForHumans() }}</p>
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
                                    <label for="tracking_code" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Código de Rastreio *</label>
                                    <input type="text" id="tracking_code" name="tracking_code" required 
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 text-xs h-8 px-3"
                                        placeholder="Ex: RAS-2025-001" />
                                </div>

                                <div>
                                    <label for="destination_type" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo de Destino *</label>
                                    <select id="destination_type" name="destination_type" required 
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 text-xs h-8 px-3">
                                        <option value="">Selecione...</option>
                                        <option value="FILIAL">🏢 Filial</option>
                                        <option value="PROJETO">📍 Projeto</option>
                                    </select>
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
        </div>
    </div>
</div>
