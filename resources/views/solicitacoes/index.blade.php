<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Solicitações de Bens') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="solicitacaoBemsIndex()"
        data-projetos='@json($projetos ?? [])'
        data-confirm-url="{{ route('solicitacoes-bens.confirm', ['solicitacao' => '__ID__']) }}"
        data-forward-url="{{ route('solicitacoes-bens.forward-to-liberacao', ['solicitacao' => '__ID__']) }}"
        data-approve-url="{{ route('solicitacoes-bens.release', ['solicitacao' => '__ID__']) }}"
        data-send-url="{{ route('solicitacoes-bens.send', ['solicitacao' => '__ID__']) }}"
        data-cancel-url="{{ route('solicitacoes-bens.cancel', ['solicitacao' => '__ID__']) }}">
        <div class="w-full sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded" role="alert">
                    <span class="font-semibold">Sucesso:</span> {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded" role="alert">
                    <span class="font-semibold">Erro:</span> {{ session('error') }}
                </div>
            @endif
            @if($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded" role="alert">
                    <span class="font-semibold">Erro:</span> {{ $errors->first() }}
                </div>
            @endif

            @include('solicitacoes.partials.subnav')

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @php
                        $userForCreate = auth()->user();
                        $isAdmin = $userForCreate?->isAdmin() ?? false;
                        $canCreateSolicitacao = $isAdmin || ($userForCreate?->temAcessoTela('1013') ?? false);
                        $statusTagOptions = [
                            ['key' => 'PENDENTE', 'label' => 'Pendente'],
                            ['key' => 'AGUARDANDO_CONFIRMACAO', 'label' => 'Aguardando confirmação'],
                            ['key' => 'LIBERACAO', 'label' => 'Liberação'],
                            ['key' => 'CONFIRMADO', 'label' => 'Envio'],
                            ['key' => 'ENVIADO', 'label' => 'Enviado'],
                            ['key' => 'RECEBIDO', 'label' => 'Recebido'],
                            ['key' => 'NAO_RECEBIDO', 'label' => 'Não recebido'],
                            ['key' => 'CANCELADO', 'label' => 'Cancelado'],
                        ];
                    @endphp

                    <div class="sol-index__toolbar">
                        <div class="sol-index__search-panel">
                            <div class="flex flex-col gap-1">
                                <template x-if="tags.length > 0">
                                    <div class="flex flex-wrap items-center gap-2 mb-3">
                                        <template x-for="(tag, idx) in tags" :key="tag">
                                            <span class="inline-flex items-center px-2 py-1 bg-indigo-100 text-indigo-700 rounded-full text-xs font-semibold mr-1">
                                                <span x-text="tag"></span>
                                                <button type="button" @click="removeTag(idx)" class="ml-1 text-indigo-500 hover:text-red-500 focus:outline-none">&times;</button>
                                            </span>
                                        </template>
                                    </div>
                                </template>
                                <input
                                    x-model="inputValue"
                                    @keydown.enter.prevent="addTag()"
                                    @keydown.tab.prevent="addTag()"
                                    @keydown.backspace="removeLastTag()"
                                    type="text"
                                    placeholder="Buscar..."
                                    :style="'width:' + Math.max(160, inputValue.length * 10) + 'px'"
                                    class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm transition-all duration-200">
                                <template x-if="inputValue.length > 0">
                                    <div class="w-full mt-1">
                                        <div class="text-[11px] text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 rounded px-2 py-1 shadow-sm text-left">
                                            <template x-if="tags.length === 0">
                                                <span>Pressione <kbd class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded border border-gray-300 dark:border-gray-600 text-[10px]">Enter</kbd> para criar uma <span class="font-semibold text-indigo-600">tag</span> e refinar a busca.</span>
                                            </template>
                                            <template x-if="tags.length > 0">
                                                <span>Para apagar uma tag, apague todo o texto do input ou clique no <span class="text-red-500 font-bold">&times;</span> da tag.</span>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        @if($canCreateSolicitacao)
                            <button
                                type="button"
                                @click="openCreateModal()"
                                class="sol-index__create-button">
                                <x-heroicon-o-plus-circle class="w-5 h-5 mr-2" />
                                <span>Nova solicitação</span>
                            </button>
                        @endif
                    </div>

                    <div class="sol-index__status-bar">
                        <button
                            type="button"
                            @click="clearStatusTags()"
                            class="sol-index__status-chip"
                            :class="{ 'sol-index__status-chip--active': statusTags.length === 0 }">
                            Todos
                        </button>
                        @foreach($statusTagOptions as $statusTag)
                            <button
                                type="button"
                                @click="toggleStatusTag('{{ $statusTag['key'] }}')"
                                class="sol-index__status-chip"
                                data-status-chip="{{ $statusTag['key'] }}"
                                :class="{ 'sol-index__status-chip--active': hasStatusTag('{{ $statusTag['key'] }}') }">
                                {{ $statusTag['label'] }}
                            </button>
                        @endforeach
                    </div>

                    <div class="sol-index__grid-shell">
                        <div
                            x-show="gridLoading"
                            x-cloak
                            class="sol-index__grid-loading"
                            style="display: none;">
                            Atualizando lista...
                        </div>
                        <div id="solicitacoes-grid-container" x-ref="gridContainer" @click="handleGridClick($event)">
                            @include('solicitacoes.partials.index-grid', ['solicitacoes' => $solicitacoes, 'sort' => $sort, 'direction' => $direction])
                        </div>
                    </div>

                        <!-- MODAIS RÁPIDOS -->
                        <!-- Modal: Confirmar (Quick) -->
                        <div x-show="showQuickConfirmModal" x-transition class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50" style="display:none;">
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-md w-full mx-4 overflow-hidden">
                                <div class="bg-emerald-600 text-white px-6 py-4 flex items-center justify-between">
                                    <h3 class="text-sm font-bold">Confirmar Solicitação</h3>
                                    <button @click="fecharModais()" class="text-white/70 hover:text-white">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                            </div>
                            <form method="POST" :action="urlConfirm()" class="p-6 space-y-4" @submit.prevent="submitQuickAction($event)">
                                @csrf
                                @method('POST')

                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Confirme para mover esta solicitação para a etapa de análise.
                                </p>
<div class="flex gap-2 pt-4">
                                    <button type="button" @click="fecharModais()" class="flex-1 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition">
                                        Cancelar
                                    </button>
                                    <button type="submit" class="flex-1 px-4 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition">
                                        Confirmar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Modal: Encaminhar para Liberação (Quick) -->
                    <div x-show="showQuickForwardModal" x-transition class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50" style="display:none;">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-md w-full mx-4 overflow-hidden">
                            <div class="bg-violet-600 text-white px-6 py-4 flex items-center justify-between">
                                <h3 class="text-sm font-bold">Encaminhar para Liberação</h3>
                                <button @click="fecharModais()" class="text-white/70 hover:text-white">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <form method="POST" :action="urlForward()" class="p-6 space-y-4" @submit.prevent="submitQuickAction($event)">
                                @csrf
                                @method('POST')

                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Confirme para encaminhar esta solicitação para a etapa de liberação final.
                                </p>

                                <div class="flex gap-2 pt-4">
                                    <button type="button" @click="fecharModais()" class="flex-1 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition">
                                        Cancelar
                                    </button>
                                    <button type="submit" class="flex-1 px-4 py-2 text-xs font-semibold text-white bg-violet-600 hover:bg-violet-700 rounded-lg transition">
                                        Encaminhar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Modal: Liberar Pedido (Quick) -->
                    <div x-show="showQuickApproveModal" x-transition class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50" style="display:none;">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-md w-full mx-4 overflow-hidden">
                            <div class="bg-blue-600 text-white px-6 py-4 flex items-center justify-between">
                                <h3 class="text-sm font-bold">Liberar Pedido</h3>
                                <button @click="fecharModais()" class="text-white/70 hover:text-white">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <form method="POST" :action="urlApprove()" class="p-6 space-y-4" @submit.prevent="submitQuickAction($event)">
                                @csrf
                                @method('POST')

                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Confirme para concluir a etapa de <strong>Liberação</strong> e mover a solicitação para <strong>Envio</strong>.
                                </p>

                                <div class="flex gap-2 pt-4">
                                    <button type="button" @click="fecharModais()" class="flex-1 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition">
                                        Cancelar
                                    </button>
                                    <button type="submit" class="flex-1 px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">
                                        Liberar Pedido
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Modal: Enviar Pedido (Quick) -->
                    <div x-show="showQuickSendModal" x-transition class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50" style="display:none;">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-md w-full mx-4 overflow-hidden">
                            <div class="bg-indigo-600 text-white px-6 py-4 flex items-center justify-between">
                                <h3 class="text-sm font-bold">Enviar Pedido</h3>
                                <button @click="fecharModais()" class="text-white/70 hover:text-white">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <form method="POST" :action="urlSend()" class="p-6 space-y-4" @submit.prevent="submitQuickAction($event)">
                                @csrf
                                @method('POST')

                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Informe o código de rastreio para registrar o envio do pedido.
                                </p>

                                <div>
                                    <label for="quick_tracking_code_enviado" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Código de Rastreio *</label>
                                    <input type="text" id="quick_tracking_code_enviado" name="tracking_code" required
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 text-xs h-8 px-3"
                                        placeholder="Ex: RAS-2026-001" />
                                </div>

                                <div class="flex gap-2 pt-4">
                                    <button type="button" @click="fecharModais()" class="flex-1 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition">
                                        Cancelar
                                    </button>
                                    <button type="submit" class="flex-1 px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition">
                                        Enviar Pedido
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Modal: Cancelar Solicitação (Quick) -->
                    <div x-show="showQuickCancelModal" x-transition class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50" style="display:none;">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-md w-full mx-4 overflow-hidden">
                            <div class="bg-red-600 text-white px-6 py-4 flex items-center justify-between">
                                <h3 class="text-sm font-bold">Cancelar Solicitação</h3>
                                <button @click="fecharModais()" class="text-white/70 hover:text-white">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <form method="POST" :action="urlCancel()" class="p-6 space-y-4" @submit.prevent="submitQuickAction($event)" x-data="{ motivoPadrao: '', outroMotivo: '' }">
                                @csrf
                                @method('POST')

                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Use esta opção quando a solicitação não puder seguir no fluxo. Selecione um motivo comum ou descreva manualmente o cancelamento.
                                </p>

                                <div class="space-y-1">
                                    <label for="quick_motivo_padrao_cancelamento" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Motivo comum</label>
                                    <select id="quick_motivo_padrao_cancelamento"
                                        x-model="motivoPadrao"
                                        @change="
                                            if (motivoPadrao && motivoPadrao !== 'OUTRO') {
                                                $refs.quickJustificativa.value = motivoPadrao;
                                            } else if (motivoPadrao === 'OUTRO') {
                                                $refs.quickJustificativa.value = outroMotivo;
                                            } else {
                                                $refs.quickJustificativa.value = '';
                                            }
                                        "
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 text-xs h-10 px-3">
                                        <option value="">Selecione...</option>
                                        <option value="Sem estoque">Sem estoque</option>
                                        <option value="OUTRO">Outro motivo</option>
                                    </select>
                                </div>

                                <div x-show="motivoPadrao === 'OUTRO'" x-transition style="display:none;">
                                    <label for="quick_justificativa" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Motivo do cancelamento *</label>
                                    <textarea id="quick_justificativa" name="justificativa_cancelamento" x-model="outroMotivo" :required="motivoPadrao === 'OUTRO'" rows="3" x-ref="quickJustificativa"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 text-xs p-2"
                                        placeholder="Descreva o motivo do cancelamento..."></textarea>
                                </div>

                                <div class="flex gap-2 pt-4">
                                    <button type="button" @click="fecharModais()" class="flex-1 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition">
                                        Voltar
                                    </button>
                                    <button type="submit" class="flex-1 px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition">
                                        Cancelar Solicitação
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Modal para criar Solicitação -->
        <style>
            html[data-theme='light'] .solicitacao-modal-theme {
                --solicitacao-modal-bg: #fcfdff;
                --solicitacao-modal-border: #d6dde6;
                --solicitacao-modal-input-bg: #f7f9fc;
                --solicitacao-modal-input-border: #d1d9e2;
                --solicitacao-modal-input-text: #111827;
                --solicitacao-modal-input-placeholder: #6b7280;
            }

            html[data-theme='dark'] .solicitacao-modal-theme {
                --solicitacao-modal-bg: #0b1220;
                --solicitacao-modal-border: #2b3a55;
                --solicitacao-modal-input-bg: #0f172a;
                --solicitacao-modal-input-border: #334155;
                --solicitacao-modal-input-text: #e2e8f0;
                --solicitacao-modal-input-placeholder: #94a3b8;
            }

            .solicitacao-modal-theme {
                background: var(--solicitacao-modal-bg);
                border-color: var(--solicitacao-modal-border);
            }

            .solicitacao-modal-scroll {
                scrollbar-width: thin;
                scrollbar-color: #3b82f6 #0f172a;
            }

            html[data-theme='light'] .solicitacao-modal-scroll {
                scrollbar-color: #2563eb #e5e7eb;
            }

            .solicitacao-modal-scroll::-webkit-scrollbar {
                width: 8px;
            }

            .solicitacao-modal-scroll::-webkit-scrollbar-track {
                background: #0f172a;
            }

            html[data-theme='light'] .solicitacao-modal-scroll::-webkit-scrollbar-track {
                background: #e5e7eb;
            }

            .solicitacao-modal-scroll::-webkit-scrollbar-thumb {
                background: linear-gradient(180deg, #60a5fa 0%, #2563eb 100%);
                border-radius: 999px;
                border: 2px solid #0f172a;
            }

            html[data-theme='light'] .solicitacao-modal-scroll::-webkit-scrollbar-thumb {
                background: linear-gradient(180deg, #60a5fa 0%, #2563eb 100%);
                border: 2px solid #e5e7eb;
            }

            .solicitacao-modal-theme .input-base,
            .solicitacao-modal-theme input:not([type="checkbox"]):not([type="radio"]):not([type="file"]):not([type="color"]),
            .solicitacao-modal-theme select,
            .solicitacao-modal-theme textarea {
                background-color: var(--solicitacao-modal-input-bg) !important;
                border-color: var(--solicitacao-modal-input-border) !important;
                color: var(--solicitacao-modal-input-text) !important;
            }

            .solicitacao-modal-theme input::placeholder,
            .solicitacao-modal-theme textarea::placeholder {
                color: var(--solicitacao-modal-input-placeholder) !important;
            }
        </style>
        <div class="w-full">
        <!-- Overlay Background -->
        <div
            x-show="formModalOpen || showModalOpen || showModalLoading"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-cloak
            class="fixed inset-0 z-[60] bg-black/60 dark:bg-black/80 p-3 sm:p-6"
            @click="if(!formModalLoading && !showModalLoading) { closeFormModal(); closeShowModal(); }"
        ></div>

        <!-- Loading Screen -->
        <div
            x-show="formModalOpen && formModalLoading"
            x-transition:leave="transition ease-out duration-300"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            x-cloak
            class="fixed inset-0 z-[70] flex items-center justify-center pointer-events-none"
        >
            <x-modal-loading
                class="fixed inset-0 z-[70] pointer-events-none"
                title="Carregando formulário"
                subtitle="Preparando os dados para edição..."
            />
        </div>

        <!-- Loading Screen (Show modal) -->
        <div
            x-show="showModalLoading && !showModalOpen"
            x-transition:leave="transition ease-out duration-300"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            x-cloak
            class="fixed inset-0 z-[70] flex items-center justify-center pointer-events-none"
        >
            <x-modal-loading
                class="fixed inset-0 z-[70] pointer-events-none"
                title="Carregando Solicitação"
                subtitle="Buscando os detalhes da solicitação..."
            />
        </div>

        <!-- Modal Principal -->
        <div
            x-show="formModalOpen && !formModalLoading"
            x-cloak
            class="fixed inset-0 z-[60] flex items-center justify-center p-3 sm:p-6 pointer-events-none"
        >
            <div
                x-show="formModalOpen && !formModalLoading"
                x-transition:enter="transition ease-out duration-500 delay-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="solicitacao-modal-theme rounded-2xl shadow-2xl w-full max-w-2xl h-auto max-h-[90vh] overflow-hidden border flex flex-col min-h-0 pointer-events-auto"
                @click.self="closeFormModal"
            >
                <div class="flex items-center justify-between px-4 sm:px-6 py-3 bg-[var(--solicitacao-modal-bg)] border-b border-gray-200 dark:border-gray-700">
                    <div>
                        <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white" x-text="formModalTitle"></h3>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400" x-text="formModalSubtitle" x-show="formModalSubtitle"></p>
                    </div>
                    <button type="button" @click="closeFormModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 text-2xl leading-none">×</button>
                </div>
                <div class="relative flex-1 min-h-[300px]">
                    <div id="solicitacao-form-modal-body" class="h-full"></div>
                </div>
            </div>
        </div>
        <!-- Modal de detalhes da Solicitação -->
        <div
            x-show="showModalOpen"
            x-cloak
            class="fixed inset-0 z-[65] flex items-center justify-center p-3 sm:p-6"
        >
            <div
                x-show="showModalOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="solicitacao-modal-theme rounded-2xl shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-hidden border flex flex-col bg-white dark:bg-gray-800"
                @click.stop
            >
                <div class="flex items-center justify-between px-4 sm:px-6 py-3 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                    <div>
                        <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white" x-text="showModalTitle"></h3>
                    </div>
                    <div class="flex items-center gap-2 relative">
                        <template x-if="showModalCanManageAccess && showModalAccessGranted.length > 0">
                            <div class="flex items-center gap-2 text-xs bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-700 rounded-lg px-3 py-2">
                                <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                                </svg>
                                <span class="text-indigo-700 dark:text-indigo-300 font-medium">Acesso:</span>
                                <span class="text-indigo-600 dark:text-indigo-400" x-text="showModalAccessGranted.map(u => u.login).join(', ')"></span>
                            </div>
                        </template>
                        <template x-if="showModalCanManageAccess">
                            <div class="relative">
                                <button
                                    type="button"
                                    @click="showAccessDropdown = !showAccessDropdown"
                                    class="inline-flex items-center gap-1 rounded-lg border border-indigo-300 dark:border-indigo-700 bg-indigo-50 dark:bg-indigo-900/30 px-3 py-1.5 text-xs font-semibold text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/50">
                                    Acesso
                                    <span class="text-[10px]" x-text="showModalAccessSelected.length ? `(${showModalAccessSelected.length})` : ''"></span>
                                </button>
                                <div
                                    x-show="showAccessDropdown"
                                    x-cloak
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    @click.outside="showAccessDropdown = false"
                                    class="absolute right-0 mt-2 w-[22rem] max-w-[90vw] rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-2xl p-0 z-20 overflow-hidden ring-1 ring-black ring-opacity-5">
                                    <div class="border-b border-gray-100 dark:border-gray-800 px-4 py-3 bg-gray-50 dark:bg-gray-800">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Gerenciar acesso</p>
                                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Marque quem terá acesso. Desmarque quem deve perder acesso.</p>
                                    </div>
                                    <div class="max-h-80 overflow-y-auto px-2 py-3 space-y-3 scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 scrollbar-track-transparent">
                                        <template x-if="showModalAccessUsers.length > 0">
                                            <div>
                                                <p class="text-[11px] font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide px-1 mb-2">Usuários permitidos</p>
                                                <div class="space-y-1.5">
                                                    <template x-for="usuario in showModalAccessUsers" :key="`access-${usuario.id}`">
                                                        <label 
                                                            class="group flex items-start gap-3 rounded-lg border px-3 py-2.5 text-left cursor-pointer transition-all"
                                                            :class="showModalAccessSelected.includes(String(usuario.id)) ? 'bg-indigo-50 dark:bg-indigo-900/30 border-indigo-200 dark:border-indigo-700' : 'border-transparent hover:bg-gray-50 dark:hover:bg-gray-800 hover:border-gray-200 dark:hover:border-gray-700'">
                                                            <div class="flex items-center h-full pt-0.5">
                                                                <input type="checkbox" class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-indigo-600 focus:ring-indigo-500 transition-colors"
                                                                    :value="String(usuario.id)" x-model="showModalAccessSelected">
                                                            </div>
                                                            <span class="min-w-0 flex-1">
                                                                <span class="block truncate text-sm font-bold text-gray-900 dark:text-gray-100 transition-colors"
                                                                    :class="showModalAccessSelected.includes(String(usuario.id)) ? 'text-indigo-700 dark:text-indigo-400' : 'group-hover:text-indigo-600 dark:group-hover:text-indigo-400'" 
                                                                    x-text="usuario.login"></span>
                                                                <span class="mt-0.5 block truncate text-[11px] text-gray-500 dark:text-gray-400" x-text="usuario.nome"></span>
                                                            </span>
                                                        </label>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Nenhum usuário -->
                                        <template x-if="!showModalAccessUsers.length">
                                            <div class="rounded-lg border border-amber-200 dark:border-amber-900/50 bg-amber-50 dark:bg-amber-900/20 px-3 py-3 text-xs text-amber-700 dark:text-amber-400 text-center">
                                                Nenhum usuário disponível para gerenciar o acesso nesta solicitação.
                                            </div>
                                        </template>
                                    </div>
                                    <div class="flex items-center justify-between gap-3 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800 px-4 py-3">
                                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                            <span x-text="showModalAccessSelected.length" class="text-gray-900 dark:text-white font-semibold"></span> selecionado(s)
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button type="button" @click="showAccessDropdown = false"
                                                class="px-3 py-1.5 text-xs font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">Fechar</button>
                                            <button type="button" @click="saveShowModalAccessChanges()"
                                                :disabled="!showModalAccessHasChanges"
                                                :class="showModalAccessHasChanges ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm' : 'bg-gray-200 dark:bg-gray-800 border border-transparent dark:border-gray-700 text-gray-400 dark:text-gray-600 cursor-not-allowed'"
                                                class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all">
                                                <span x-text="showModalAccessActionLabel"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div x-show="showModalAccessFeedback.message"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 translate-y-1"
                            class="hidden sm:flex items-center justify-center rounded-full border h-8 w-8"
                            :class="showModalAccessFeedback.success ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700'"
                            :title="showModalAccessFeedback.message">
                            <template x-if="showModalAccessFeedback.success">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                                </svg>
                            </template>
                            <template x-if="!showModalAccessFeedback.success">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </template>
                        </div>
                        <button type="button" @click="closeShowModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 text-2xl leading-none">×</button>
                    </div>
                </div>
                <div class="relative flex-1 overflow-y-auto bg-white dark:bg-gray-800 min-h-[320px] solicitacao-modal-scroll">
                    <x-modal-loading
                        x-show="showModalLoading"
                        x-cloak
                        class="absolute inset-0 z-10"
                        title="Carregando detalhes"
                        subtitle="Atualizando o fluxo da solicitação..."
                    />
                    <div id="solicitacao-show-modal-body" class="min-h-full" x-show="showModalContentReady" x-cloak></div>
                </div>
            </div>
        </div>
    </div>

    </div>

    @push('scripts')
        <script>
            function renderSolicitacaoModalContent(html, target) {
                if (!target) return;
                
                // Simplificado: Apenas insere o HTML. Scripts devem ser globais.
                target.innerHTML = html;
                
                // Reiniciar Alpine se necessario
                if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                    // Pequeno delay para garantir que o DOM esteja pronto
                    requestAnimationFrame(() => {
                         window.Alpine.initTree(target);
                    });
                }
            }

            function bindSolicitacaoModalHandlers(root, onClose, onSubmit) {
                if (!root) return;
                root.querySelectorAll('[data-modal-close]').forEach((btn) => {
                    btn.addEventListener('click', (event) => {
                        if (btn.dataset.modalClose === 'false') {
                            return;
                        }
                        event.preventDefault();
                        onClose();
                    });
                });
                root.querySelectorAll('form[data-modal-form]').forEach((form) => {
                    if (form.dataset.modalBound === 'true') return;
                    form.dataset.modalBound = 'true';
                    form.addEventListener('submit', (event) => {
                        event.preventDefault();
                        onSubmit(form);
                    });
                });
            }

            function getProjetosFromDataset() {
                const root = document.querySelector('[data-projetos]');
                if (!root) return [];
                const raw = root.dataset.projetos;
                if (!raw) return [];
                try {
                    return JSON.parse(raw);
                } catch (error) {
                    console.warn('[SOLICITACAO] Falha ao ler projetos do dataset', error);
                    return [];
                }
            }

            function extractModalHtml(html, selector) {
                if (!html) return '';
                if (typeof DOMParser === 'undefined') return html;
                try {
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const node = doc.querySelector(selector);
                    return node ? node.innerHTML : html;
                } catch (error) {
                    console.warn('[SOLICITACAO] Falha ao extrair HTML do modal', error);
                    return html;
                }
            }

            // --- FUNCOES GLOBAIS PARA MODAIS (Form & Show) ---

            // Funcao usada em show.blade.php
            function matriculaLookup({ matriculaOld, nomeOld, lookupOnInit }) {
                return {
                    matricula: matriculaOld || '',
                    nome: nomeOld || '',
                    lookupOnInit: !!lookupOnInit,
                    matriculaExiste: false,
                    nomeBloqueado: false,
                    initLookup() {
                        if (this.lookupOnInit && this.matricula) {
                            this.lookupMatricula(this.matricula);
                        }
                    },
                    onMatriculaInput(e) {
                        const val = (e?.target?.value ?? '').trim();
                        if (val === '') {
                            this.matriculaExiste = false;
                            this.nomeBloqueado = false;
                            this.nome = '';
                        }
                    },
                    async onMatriculaBlur() {
                        const mat = (this.matricula || '').trim();
                        if (!mat) return;
                        await this.lookupMatricula(mat);
                    },
                    async lookupMatricula(mat) {
                        try {
                            const url = `{{ route('api.usuarios.porMatricula') }}?matricula=${encodeURIComponent(mat)}`;
                            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                            if (!res.ok) throw new Error('Falha busca matricula');
                            const data = await res.json();
                            this.matriculaExiste = !!data?.exists;
                            if (data?.exists && data?.nome) {
                                this.nome = data.nome;
                                this.nomeBloqueado = true;
                            } else {
                                this.nomeBloqueado = false;
                            }
                        } catch (e) {
                            console.warn('Lookup matricula falhou', e);
                        }
                    }
                };
            }

            // Funcoes usadas em form.blade.php
            function solicitacaoForm({ itensOld, showStep2 }) {
                const buildItem = (item = {}) => ({
                    key: item.key || `item-${Date.now()}-${Math.random().toString(16).slice(2)}`,
                    descricao: item.descricao || '',
                    quantidade: parseInt(item.quantidade, 10) || 1,
                    unidade: item.unidade || '',
                    observacao: item.observacao || '',
                    patrimonio_busca: item.patrimonio_busca || item['patrimônio_busca'] || item.descricao || '',
                    selecionado: Boolean(item.descricao || item.patrimonio_busca || item['patrimônio_busca']),
                });

                return {
                    step: showStep2 ? 2 : 1,
                    item: (itensOld && itensOld.length) ? buildItem(itensOld[0]) : buildItem(),
                    nextStep() {
                        const validations = [
                            {
                                valueEl: document.getElementById('projeto_id'),
                                focusEl: document.getElementById('projetoSearch'),
                                message: 'Selecione um projeto da lista.',
                            },
                            {
                                valueEl: document.getElementById('local_destino'),
                                focusEl: document.getElementById('localDestinoSearch'),
                                message: 'Informe o local de destino.',
                            },
                        ];

                        for (const validation of validations) {
                            const valueEl = validation.valueEl;
                            const focusEl = validation.focusEl || valueEl;

                            if (!valueEl || !focusEl) {
                                continue;
                            }

                            if (!String(valueEl.value || '').trim()) {
                                if (typeof focusEl.setCustomValidity === 'function') {
                                    focusEl.setCustomValidity(validation.message);
                                }
                                if (typeof focusEl.reportValidity === 'function') {
                                    focusEl.reportValidity();
                                }
                                focusEl.focus();
                                return;
                            }

                            if (typeof focusEl.setCustomValidity === 'function') {
                                focusEl.setCustomValidity('');
                            }
                        }

                        this.step = 2;
                    },
                };
            }

            function projetoSearch(projetosInjected = null) {
                // Se projetos não for passado, tenta pegar do global (caso estivesse no script inline)
                // Mas aqui assumimos que virá via argumento ou usamos a lista global da página index
                const todasProjetos = projetosInjected || getProjetosFromDataset();
                
                return {
                    projetoSearch: '',
                    projetoSelecionado: '',
                    showProjetoDrop: false,
                    projetoIndex: -1,
                    allProjetos: todasProjetos,

                    get projetosFiltrados() {
                        const termo = (this.projetoSearch || '').toLowerCase().trim();
                        let filtrados = this.allProjetos;
                        
                        if (termo) {
                            filtrados = this.allProjetos.filter(proj => {
                                const cdMatch = String(proj.CDPROJETO || '').toLowerCase().includes(termo);
                                const nomeMatch = String(proj.NOMEPROJETO || '').toLowerCase().includes(termo);
                                return cdMatch || nomeMatch;
                            });
                        }

                        // Order by CDPROJETO numeric
                        return filtrados.sort((a, b) => {
                            return String(a.CDPROJETO).localeCompare(String(b.CDPROJETO), undefined, { numeric: true });
                        });
                    },

                    filtrarProjetos() {
                        this.projetoIndex = -1;
                        if (!this.projetoSearch.trim()) {
                            this.showProjetoDrop = true;
                        }
                    },

                    selecionarProjeto(proj) {
                        if (!proj) return;
                        this.projetoSelecionado = proj.id;
                        this.projetoSearch = `${proj.CDPROJETO} - ${proj.NOMEPROJETO}`;
                        this.showProjetoDrop = false;
                        this.projetoIndex = -1;
                        
                        // Atualiza hidden input
                        const hiddenInput = document.getElementById('projeto_id');
                        if (hiddenInput) hiddenInput.value = proj.id;
                    },
                };
            }

            function projetoLocalForm() {
                const todasProjetos = getProjetosFromDataset();
                
                return {
                    projetoSearch: '',
                    projetoSelecionado: '',
                    showProjetoDrop: false,
                    projetoIndex: -1,
                    allProjetos: todasProjetos,
                    
                    localDestinoSearch: '',
                    localSelecionado: '',
                    showLocalDrop: false,
                    localIndex: -1,
                    locaisList: [],
                    loadingLocais: false,

                    get projetosFiltrados() {
                        const termo = (this.projetoSearch || '').toLowerCase().trim();
                        let filtrados = this.allProjetos;
                        
                        if (termo) {
                            filtrados = this.allProjetos.filter(proj => {
                                const cdMatch = String(proj.CDPROJETO || '').toLowerCase().includes(termo);
                                const nomeMatch = String(proj.NOMEPROJETO || '').toLowerCase().includes(termo);
                                return cdMatch || nomeMatch;
                            });
                        }

                        return filtrados.sort((a, b) => {
                            return String(a.CDPROJETO).localeCompare(String(b.CDPROJETO), undefined, { numeric: true });
                        });
                    },
                    
                    get locaisFiltrados() {
                        const termo = (this.localDestinoSearch || '').toLowerCase().trim();
                        let filtrados = this.locaisList;
                        
                        if (termo) {
                            filtrados = this.locaisList.filter(loc => {
                                const cdMatch = String(loc.cdlocal || '').toLowerCase().includes(termo);
                                const nomeMatch = String(loc.delocal || '').toLowerCase().includes(termo);
                                return cdMatch || nomeMatch;
                            });
                        }

                        return filtrados;
                    },

                    filtrarProjetos() {
                        this.projetoIndex = -1;
                        if (!this.projetoSearch.trim()) {
                            this.showProjetoDrop = true;
                        }
                    },

                    async selecionarProjeto(proj) {
                        if (!proj) return;
                        this.projetoSelecionado = proj.id;
                        this.projetoSearch = `${proj.CDPROJETO} - ${proj.NOMEPROJETO}`;
                        this.showProjetoDrop = false;
                        this.projetoIndex = -1;
                        
                        // Limpar local selecionado
                        this.localDestinoSearch = '';
                        this.localSelecionado = '';
                        this.locaisList = [];
                        
                        // Buscar locais do projeto
                        await this.buscarLocais(proj.id);
                    },
                    
                    limparProjeto() {
                        this.projetoSearch = '';
                        this.projetoSelecionado = '';
                        this.projetoIndex = -1;
                        this.localDestinoSearch = '';
                        this.localSelecionado = '';
                        this.locaisList = [];
                    },
                    
                    async buscarLocais(projetoId) {
                        if (!projetoId) return;
                        
                        this.loadingLocais = true;
                        try {
                            const resp = await fetch(`/api/locais/lookup?projeto_id=${projetoId}`);
                            if (!resp.ok) throw new Error('Erro ao buscar locais');
                            const data = await resp.json();
                            this.locaisList = data;
                        } catch (err) {
                            console.error('Erro ao buscar locais:', err);
                            this.locaisList = [];
                        } finally {
                            this.loadingLocais = false;
                        }
                    },
                    
                    abrirDropdownLocal() {
                        if (!this.projetoSelecionado) return;
                        this.showLocalDrop = true;
                        this.localIndex = -1;
                    },
                    
                    filtrarLocais() {
                        this.localIndex = -1;
                    },
                    
                    selecionarLocal(loc) {
                        if (!loc) return;
                        this.localSelecionado = loc.delocal; // Armazena o nome do local
                        this.localDestinoSearch = `${loc.cdlocal} - ${loc.delocal}`;
                        this.showLocalDrop = false;
                        this.localIndex = -1;
                    },
                    
                    limparLocal() {
                        this.localDestinoSearch = '';
                        this.localSelecionado = '';
                        this.localIndex = -1;
                    }
                };
            }

            function patrimonioSearch(item) {
                return {
                    resultados: [],
                    item,
                    dropdownOpen: false,
                    loading: false,
                    controller: null,

                    openResults() {
                        this.dropdownOpen = true;
                        const term = (this.item?.patrimonio_busca || '').trim();
                        if (term.length === 0) {
                            this.buscar('');
                        } else if (term.length >= 2) {
                            this.buscar(term);
                        }
                    },

                    closeResults() {
                        this.dropdownOpen = false;
                    },

                    onInput() {
                        const term = (this.item?.patrimonio_busca || '').trim();
                        this.item.descricao = term;
                        this.item.selecionado = false;
                        if (term.length < 2) {
                            this.resultados = [];
                            return;
                        }

                        this.buscar(term);
                    },

                    async buscar(term) {
                        const query = (term ?? '').trim();

                        if (this.controller) {
                            this.controller.abort();
                        }
                        this.controller = new AbortController();
                        this.loading = true;
                        this.dropdownOpen = true;

                        try {
                            const url = `{{ route('solicitacoes-bens.patrimonio-disponivel') }}?q=${encodeURIComponent(query)}`;
                            const res = await fetch(url, {
                                signal: this.controller.signal,
                                headers: { 'Accept': 'application/json' },
                            });
                            if (!res.ok) {
                                throw new Error(`HTTP ${res.status}`);
                            }
                            const data = await res.json();
                            this.resultados = Array.isArray(data) ? data : [];
                        } catch (err) {
                            if (err.name !== 'AbortError') {
                                console.error('[SOLICITACAO] Erro ao buscar patrimônios', err);
                            }
                        } finally {
                            this.loading = false;
                        }
                    },

                    selectResultado(resultado) {
                        const descricao = (resultado?.descricao || '').trim()
                            || (resultado?.text || '').trim()
                            || [resultado?.nupatrimonio, resultado?.descricao].filter(Boolean).join(' - ');
                        this.item.descricao = descricao;
                        this.item.patrimonio_busca = descricao;
                        this.item.selecionado = true;
                        
                        // Se tiver peso, preenche automaticamente o campo unidade
                        if (resultado?.peso && resultado.peso > 0) {
                            this.item.peso = resultado.peso;
                            this.item.unidade = `${resultado.peso} kg`;
                        } else {
                            this.item.peso = null;
                            // Mantém unidade editável quando não há peso
                        }
                        this.resultados = [];
                        this.dropdownOpen = false;
                    },
                };
            }

            function solicitacaoBemsIndex() {
                return {
                    confirmUrlBase: '',
                    forwardUrlBase: '',
                    approveUrlBase: '',
                    sendUrlBase: '',
                    cancelUrlBase: '',
                    tags: [],
                    statusTags: [],
                    inputValue: '',
                    gridLoading: false,
                    gridAbortController: null,
                    popstateHandler: null,
                    formModalOpen: false,
                    formModalLoading: false,
                    formModalTitle: '',
                    formModalSubtitle: '',
                    formModalMode: null,
                    formModalId: null,
                    showModalOpen: false,
                    showModalLoading: false,
                    showModalContentReady: false,
                    showModalTitle: '',
                    showAccessDropdown: false,
                    showModalCanManageAccess: false,
                    showModalAccessUsers: [],
                    showModalAccessGranted: [],
                    showModalAccessSelected: [],
                    showModalAccessInitiallyGranted: [],
                    showModalAccessGrantUrl: '',
                    showModalAccessRevokeUrl: '',
                    showModalAccessFeedback: { success: false, message: '' },
                    currentSolicitacaoId: null,
                    get showModalAccessAddedIds() {
                        const initial = new Set(this.showModalAccessInitiallyGranted);
                        return this.showModalAccessSelected.filter((id) => !initial.has(id));
                    },
                    get showModalAccessRemovedIds() {
                        const selected = new Set(this.showModalAccessSelected);
                        return this.showModalAccessInitiallyGranted.filter((id) => !selected.has(id));
                    },
                    get showModalAccessHasChanges() {
                        return this.showModalAccessAddedIds.length > 0 || this.showModalAccessRemovedIds.length > 0;
                    },
                    get showModalAccessActionLabel() {
                        const added = this.showModalAccessAddedIds.length;
                        const removed = this.showModalAccessRemovedIds.length;
                        if (added > 0 && removed > 0) return 'Salvar acesso';
                        if (removed > 0) return 'Remover acesso';
                        return 'Conceder acesso';
                    },
                    init() {
                        this.confirmUrlBase = this.$el?.dataset?.confirmUrl || '';
                        this.forwardUrlBase = this.$el?.dataset?.forwardUrl || '';
                        this.approveUrlBase = this.$el?.dataset?.approveUrl || '';
                        this.sendUrlBase = this.$el?.dataset?.sendUrl || '';
                        this.cancelUrlBase = this.$el?.dataset?.cancelUrl || '';
                        this.syncFiltersFromUrl(window.location.href);
                        this.popstateHandler = () => {
                            this.syncFiltersFromUrl(window.location.href);
                            this.loadGrid(window.location.href, { pushHistory: false, syncState: false });
                        };
                        window.addEventListener('popstate', this.popstateHandler);
                    },

                    extractTagsFromParams(params) {
                        const multiSearch = params.getAll('search[]').map((tag) => tag.trim()).filter(Boolean);
                        if (multiSearch.length > 0) {
                            return [...new Set(multiSearch)];
                        }

                        const singleSearch = params.get('search');
                        if (!singleSearch) {
                            return [];
                        }

                        return [...new Set(singleSearch.split(',').map((tag) => tag.trim()).filter(Boolean))];
                    },

                    normalizeStatusTag(status) {
                        return String(status ?? '')
                            .trim()
                            .toUpperCase()
                            .replace(/[-\s]+/g, '_');
                    },

                    extractStatusTagsFromParams(params) {
                        const multiStatus = params.getAll('status_visual[]');
                        const singleStatus = params.getAll('status_visual');

                        return [...new Set(
                            [...multiStatus, ...singleStatus]
                                .map((status) => this.normalizeStatusTag(status))
                                .filter(Boolean)
                        )];
                    },

                    syncFiltersFromUrl(url) {
                        const nextUrl = new URL(url, window.location.origin);
                        this.tags = this.extractTagsFromParams(nextUrl.searchParams);
                        this.statusTags = this.extractStatusTagsFromParams(nextUrl.searchParams);
                    },

                    hasStatusTag(status) {
                        return this.statusTags.includes(this.normalizeStatusTag(status));
                    },

                    toggleStatusTag(status) {
                        const normalizedStatus = this.normalizeStatusTag(status);
                        if (!normalizedStatus) {
                            return;
                        }

                        if (this.statusTags.includes(normalizedStatus)) {
                            this.statusTags = this.statusTags.filter((item) => item !== normalizedStatus);
                        } else {
                            this.statusTags = [...this.statusTags, normalizedStatus];
                        }

                        this.applyFilters();
                    },

                    clearStatusTags() {
                        if (this.statusTags.length === 0) {
                            return;
                        }

                        this.statusTags = [];
                        this.applyFilters();
                    },

                    addTag() {
                        const value = this.inputValue.trim();
                        if (value && !this.tags.includes(value)) {
                            this.tags.push(value);
                            this.inputValue = '';
                            this.applyFilters();
                        }
                    },

                    removeTag(index) {
                        this.tags.splice(index, 1);
                        this.applyFilters();
                    },

                    removeLastTag() {
                        if (!this.inputValue && this.tags.length > 0) {
                            this.tags.pop();
                            this.applyFilters();
                        }
                    },

                    applyFilters() {
                        const nextUrl = this.buildGridUrl({ resetPage: true });
                        this.loadGrid(nextUrl, { pushHistory: true, syncState: false });
                    },

                    buildGridUrl({ resetPage = false, sourceUrl = window.location.href } = {}) {
                        const nextUrl = new URL(sourceUrl, window.location.origin);
                        const params = nextUrl.searchParams;
                        params.delete('search');
                        params.delete('search[]');
                        params.delete('status_visual');
                        params.delete('status_visual[]');

                        this.tags.forEach((tag) => params.append('search[]', tag));
                        this.statusTags.forEach((status) => params.append('status_visual[]', status));

                        if (resetPage) {
                            params.set('page', '1');
                        }

                        return nextUrl.toString();
                    },

                    handleGridClick(event) {
                        const link = event.target.closest('a[href]');
                        if (!link || !this.$refs.gridContainer?.contains(link)) {
                            return;
                        }

                        if (link.target === '_blank' || link.hasAttribute('download')) {
                            return;
                        }

                        const nextUrl = new URL(link.href, window.location.origin);
                        if (nextUrl.origin !== window.location.origin) {
                            return;
                        }

                        event.preventDefault();
                        this.loadGrid(nextUrl.toString(), { pushHistory: true, syncState: true });
                    },

                    async loadGrid(url, { pushHistory = true, syncState = false } = {}) {
                        const nextUrl = typeof url === 'string' ? url : url.toString();
                        if (syncState) {
                            this.syncFiltersFromUrl(nextUrl);
                        }

                        if (this.gridAbortController) {
                            this.gridAbortController.abort();
                        }

                        const controller = new AbortController();
                        this.gridAbortController = controller;
                        this.gridLoading = true;

                        try {
                            const response = await fetch(nextUrl, {
                                signal: controller.signal,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-Solicitacoes-Grid': '1',
                                    'Accept': 'text/html',
                                },
                            });

                            if (!response.ok) {
                                throw new Error(`Erro HTTP ${response.status}`);
                            }

                            const html = await response.text();
                            if (!this.$refs.gridContainer) {
                                throw new Error('Container da grade não encontrado.');
                            }

                            this.$refs.gridContainer.innerHTML = html;
                            if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                                window.Alpine.initTree(this.$refs.gridContainer);
                            }

                            if (pushHistory) {
                                window.history.pushState({}, '', nextUrl);
                            }
                        } catch (error) {
                            if (error.name === 'AbortError') {
                                return;
                            }

                            console.error('[SOLICITACAO] Erro ao atualizar a grade', error);
                            window.location.href = nextUrl;
                        } finally {
                            if (this.gridAbortController === controller) {
                                this.gridAbortController = null;
                                this.gridLoading = false;
                            }
                        }
                    },

                    csrf() {
                        return document.querySelector('meta[name=csrf-token]')?.content || '';
                    },

                    setShowModalAccessFeedback(success, message, duration = 2600) {
                        this.showModalAccessFeedback = {
                            success: !!success,
                            message: message || '',
                        };

                        if (this.showModalAccessFeedbackTimer) {
                            clearTimeout(this.showModalAccessFeedbackTimer);
                        }

                        if (!message) {
                            return;
                        }

                        this.showModalAccessFeedbackTimer = setTimeout(() => {
                            this.showModalAccessFeedback = { success: false, message: '' };
                            this.showModalAccessFeedbackTimer = null;
                        }, duration);
                    },

                    hydrateShowModalAccessData(modalBody) {
                        this.showModalCanManageAccess = false;
                        this.showModalAccessUsers = [];
                        this.showModalAccessGranted = [];
                        this.showModalAccessSelected = [];
                        this.showModalAccessInitiallyGranted = [];
                        this.showModalAccessGrantUrl = '';
                        this.showModalAccessRevokeUrl = '';
                        this.setShowModalAccessFeedback(false, '', 0);
                        this.showAccessDropdown = false;

                        if (!modalBody) return;
                        const source = modalBody.querySelector('#solicitacao-permissoes-data');
                        if (!source || source.dataset.enabled !== '1') return;

                        this.showModalCanManageAccess = true;
                        this.showModalAccessGrantUrl = source.dataset.grantUrl || '';
                        this.showModalAccessRevokeUrl = source.dataset.revokeUrl || '';
                        try {
                            const users = JSON.parse(source.dataset.users || '[]');
                            this.showModalAccessUsers = Array.isArray(users) ? users : [];
                        } catch (e) {
                            this.showModalAccessUsers = [];
                        }
                        try {
                            const grantedUsers = JSON.parse(source.dataset.grantedUsers || '[]');
                            this.showModalAccessGranted = Array.isArray(grantedUsers) ? grantedUsers : [];
                        } catch (e) {
                            this.showModalAccessGranted = [];
                        }
                        const allUsers = [...this.showModalAccessGranted, ...this.showModalAccessUsers];
                        const uniqueUsers = [];
                        const seen = new Set();
                        allUsers.forEach((usuario) => {
                            const id = String(usuario.id ?? '').trim();
                            if (!id || seen.has(id)) return;
                            seen.add(id);
                            uniqueUsers.push({
                                id: Number(usuario.id),
                                nome: usuario.nome || '',
                                login: usuario.login || '',
                            });
                        });
                        this.showModalAccessUsers = uniqueUsers;
                        this.showModalAccessInitiallyGranted = this.showModalAccessGranted.map((usuario) => String(usuario.id));
                        this.showModalAccessSelected = [...this.showModalAccessInitiallyGranted];
                    },

                    async saveShowModalAccessChanges() {
                        if (!this.showModalAccessGrantUrl || !this.showModalAccessHasChanges) {
                            return;
                        }

                        const addedIds = [...new Set(this.showModalAccessAddedIds.map((v) => String(v).trim()).filter(Boolean))];
                        const removedIds = [...new Set(this.showModalAccessRemovedIds.map((v) => String(v).trim()).filter(Boolean))];

                        this.showModalLoading = true;
                        this.setShowModalAccessFeedback(false, '', 0);
                        try {
                            if (addedIds.length) {
                                const formData = new FormData();
                                addedIds.forEach((usuarioId) => formData.append('usuario_ids[]', usuarioId));
                                formData.append('modal', '1');

                                const response = await fetch(this.showModalAccessGrantUrl, {
                                    method: 'POST',
                                    body: formData,
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'X-CSRF-TOKEN': this.csrf(),
                                        'Accept': 'application/json',
                                    },
                                });

                                const payload = await response.json().catch(() => ({}));
                                if (!response.ok || payload.success === false) {
                                    this.setShowModalAccessFeedback(false, payload.message || 'Não foi possível conceder o acesso selecionado.');
                                    return;
                                }
                            }

                            for (const usuarioId of removedIds) {
                                const response = await fetch(`${this.showModalAccessRevokeUrl}/${encodeURIComponent(usuarioId)}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'X-CSRF-TOKEN': this.csrf(),
                                        'Accept': 'application/json',
                                    },
                                });

                                const payload = await response.json().catch(() => ({}));
                                if (!response.ok || payload.success === false) {
                                    this.setShowModalAccessFeedback(false, payload.message || 'Não foi possível remover o acesso selecionado.');
                                    return;
                                }
                            }

                            this.setShowModalAccessFeedback(true, 'Acesso atualizado com sucesso.');
                            this.showModalAccessGranted = this.showModalAccessUsers.filter((usuario) =>
                                this.showModalAccessSelected.includes(String(usuario.id))
                            );
                            this.showModalAccessInitiallyGranted = [...this.showModalAccessSelected];
                            this.showAccessDropdown = false;
                        } catch (err) {
                            console.error('[SOLICITACAO] Erro ao liberar acessos', err);
                            this.renderModalError('solicitacao-show-modal-body', 'Falha ao liberar acesso. Verifique sua conexão.');
                        } finally {
                            this.showModalLoading = false;
                        }
                    },

                    openCreateModal() {
                        this.openFormModal('create');
                    },
                    openShowModal(id) {
                        if (!id) return;
                        const modalBody = document.getElementById('solicitacao-show-modal-body');
                        if (!modalBody) {
                            console.error('[SOLICITACAO] Modal body not found!');
                            return;
                        }
                        
                        console.log('[SOLICITACAO] Opening show modal for ID:', id);
                        this.currentSolicitacaoId = id;
                        this.showModalTitle = `Solicita\u00e7\u00e3o #${id}`;
                        this.showModalOpen = false;
                        this.showModalLoading = true;
                        this.showModalContentReady = false;
                        this.showAccessDropdown = false;
                        this.showModalCanManageAccess = false;
                        this.showModalAccessUsers = [];
                        this.showModalAccessGranted = [];
                        this.showModalAccessSelected = [];
                        this.showModalAccessInitiallyGranted = [];
                        this.showModalAccessGrantUrl = '';
                        this.showModalAccessRevokeUrl = '';
                        this.setShowModalAccessFeedback(false, '', 0);
                        modalBody.innerHTML = '';

                        const url = "{{ url('solicitacoes-bens') }}/" + encodeURIComponent(id) + "/show-modal";
                        console.log('[SOLICITACAO] Fetching:', url);
                        
                        fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'text/html',
                            },
                        })
                            .then((resp) => {
                                if (!resp.ok) {
                                    if (resp.status === 401) {
                                        throw new Error('Sua sessão expirou. Por favor, faça login novamente.');
                                    } else if (resp.status === 403) {
                                        throw new Error('Você não tem permissão para visualizar esta solicitação.');
                                    }
                                    throw new Error(`Erro HTTP ${resp.status}`);
                                }
                                return resp.text();
                            })
                            .then((html) => {
                                console.log('[SOLICITACAO] Show modal HTML received, length:', html?.length);
                                let content = extractModalHtml(html, '[data-solicitacao-modal-content]');
                                if (!content || !content.trim()) {
                                    content = html;
                                }
                                renderSolicitacaoModalContent(content, modalBody);
                                console.log('[SOLICITACAO] Content inserted into modal body');
                                if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                                    window.Alpine.initTree(modalBody);
                                    console.log('[SOLICITACAO] Alpine initialized');
                                }
                                bindSolicitacaoModalHandlers(
                                    modalBody,
                                    () => this.closeShowModal(),
                                    (form) => this.submitModalForm(form, 'solicitacao-show-modal-body')
                                );
                                this.hydrateShowModalAccessData(modalBody);
                                console.log('[SOLICITACAO] Handlers bound');
                                this.showModalContentReady = true;
                            })
                            .catch((err) => {
                                console.error('[SOLICITACAO] Show modal fetch error', err);
                                const message = err.message || 'Falha ao carregar detalhes.';
                                modalBody.innerHTML = `<div class="p-6 text-sm text-red-600 dark:text-red-400"><strong>Erro:</strong> ${message}</div>`;
                                this.showModalContentReady = true;
                            })
                            .finally(() => {
                                this.showModalLoading = false;
                                this.showModalOpen = true;
                            });
                    },

                    openFormModal(mode, id = null) {
                        const modalBody = document.getElementById('solicitacao-form-modal-body');
                        if (!modalBody) return;
                        if (mode === 'edit' && !id) return;

                        this.formModalMode = mode;
                        this.formModalId = id;
                        this.formModalTitle = mode === 'create' ? 'Nova Solicita\u00e7\u00e3o de Bens' : 'Editar Solicita\u00e7\u00e3o';
                        this.formModalSubtitle = mode === 'create'
                            ? 'Crie uma Nova solicitação de bens.'
                            : 'Atualize os dados da solicitacao.';
                        this.formModalOpen = true;
                        this.formModalLoading = true;

                        const baseUrl = mode === 'create'
                            ? "{{ route('solicitacoes-bens.create') }}"
                            : "{{ url('solicitacoes-bens') }}/" + encodeURIComponent(id) + "/edit";
                        const url = baseUrl + (baseUrl.includes('?') ? '&' : '?') + 'modal=1';

                        fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'text/html',
                            },
                        })
                            .then((resp) => {
                                console.log('[SOLICITACAO] Fetch response status:', resp.status, 'URL:', url);
                                if (resp.redirected && resp.url && !resp.url.includes('/solicitacoes-bens')) {
                                    window.location.href = resp.url;
                                    return '';
                                }
                                if (!resp.ok) {
                                    if (resp.status === 401) {
                                        throw new Error('Sua sessão expirou. Por favor, faça login novamente.');
                                    } else if (resp.status === 403) {
                                        throw new Error('Você não tem permissão para acessar esta funcionalidade. Verifique com o administrador.');
                                    }
                                    throw new Error(`Erro HTTP ${resp.status}`);
                                }
                                return resp.text();
                            })
                            .then((html) => {
                                if (!html) return;
                                this.applyFormModalHtml(html);
                            })
                            .catch((err) => {
                                console.error('[SOLICITACAO] Modal fetch error', err);
                                const message = err.message || 'Falha ao carregar formulario.';
                                modalBody.innerHTML = `<div class="p-6 text-sm text-red-600 dark:text-red-400"><strong>Erro:</strong> ${message}</div>`;
                            })
                            .finally(() => {
                                this.formModalLoading = false;
                            });
                    },

                    closeFormModal() {
                        this.formModalOpen = false;
                        this.formModalLoading = false;
                        this.formModalTitle = '';
                        this.formModalSubtitle = '';
                        this.formModalMode = null;
                        this.formModalId = null;
                        const modalBody = document.getElementById('solicitacao-form-modal-body');
                        if (modalBody) {
                            modalBody.innerHTML = '';
                        }
                    },
                    closeShowModal() {
                        this.showModalOpen = false;
                        this.showModalLoading = false;
                        this.showModalContentReady = false;
                        this.showModalTitle = '';
                        this.showAccessDropdown = false;
                        this.showModalCanManageAccess = false;
                        this.showModalAccessUsers = [];
                        this.showModalAccessGranted = [];
                        this.showModalAccessSelected = [];
                        this.showModalAccessInitiallyGranted = [];
                        this.showModalAccessGrantUrl = '';
                        this.showModalAccessRevokeUrl = '';
                        this.setShowModalAccessFeedback(false, '', 0);
                        this.currentSolicitacaoId = null;
                        const modalBody = document.getElementById('solicitacao-show-modal-body');
                        if (modalBody) {
                            modalBody.innerHTML = '';
                        }
                    },

                    applyFormModalHtml(html) {
                        const modalBody = document.getElementById('solicitacao-form-modal-body');
                        if (!modalBody) return;
                        renderSolicitacaoModalContent(html, modalBody);
                        if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                            window.Alpine.initTree(modalBody);
                        }
                        bindSolicitacaoModalHandlers(
                            modalBody,
                            () => this.closeFormModal(),
                            (form) => this.submitModalForm(form, 'solicitacao-form-modal-body')
                        );
                    },

                    renderModalError(targetId, message) {
                        const target = document.getElementById(targetId);
                        if (!target) return;

                        const safeMessage = String(message || 'Falha ao salvar solicitacao.')
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;')
                            .replace(/\"/g, '&quot;')
                            .replace(/'/g, '&#039;');
                        const html = `
                            <div class="mb-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg text-sm" role="alert" data-modal-error="true">
                                <span class="font-semibold">Erro:</span> ${safeMessage}
                            </div>
                        `;

                        const oldError = target.querySelector('[data-modal-error="true"]');
                        if (oldError) oldError.remove();

                        const form = target.querySelector('form');
                        if (form && form.parentNode) {
                            form.insertAdjacentHTML('beforebegin', html);
                        } else {
                            target.insertAdjacentHTML('afterbegin', html);
                        }
                    },

                    async refreshGrid() {
                        const grid = document.querySelector('[data-solicitacoes-grid]');
                        if (!grid) return;

                        const response = await fetch(window.location.href, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'text/html',
                            },
                        });

                        if (!response.ok) {
                            throw new Error(`Erro ao recarregar grid (${response.status})`);
                        }

                        const html = await response.text();
                        const doc = new DOMParser().parseFromString(html, 'text/html');
                        const nextGrid = doc.querySelector('[data-solicitacoes-grid]');
                        if (!nextGrid) {
                            throw new Error('Grid atualizado não encontrado na resposta.');
                        }

                        grid.innerHTML = nextGrid.innerHTML;

                        if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                            window.Alpine.initTree(grid);
                        }
                    },

                    async refreshShowModal() {
                        if (!this.currentSolicitacaoId) return;

                        const modalBody = document.getElementById('solicitacao-show-modal-body');
                        if (!modalBody) return;

                        const url = "{{ url('solicitacoes-bens') }}/" + encodeURIComponent(this.currentSolicitacaoId) + "/show-modal";
                        const response = await fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'text/html',
                            },
                        });

                        if (!response.ok) {
                            throw new Error(`Erro ao atualizar modal (${response.status})`);
                        }

                        const html = await response.text();
                        let content = extractModalHtml(html, '[data-solicitacao-modal-content]');
                        if (!content || !content.trim()) {
                            content = html;
                        }

                        renderSolicitacaoModalContent(content, modalBody);
                        if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                            window.Alpine.initTree(modalBody);
                        }
                        bindSolicitacaoModalHandlers(
                            modalBody,
                            () => this.closeShowModal(),
                            (form) => this.submitModalForm(form, 'solicitacao-show-modal-body')
                        );
                        this.hydrateShowModalAccessData(modalBody);
                        this.showModalContentReady = true;
                        this.showModalOpen = true;
                    },

                    async submitModalForm(form, targetId) {
                        if (!form) return;

                        const isFormModal = targetId === 'solicitacao-form-modal-body';
                        let keepLoadingUntilNavigate = false;
                        if (isFormModal) {
                            this.formModalOpen = true;
                            this.formModalLoading = true;
                        } else {
                            this.showModalOpen = true;
                            this.showModalLoading = true;
                        }

                        const formData = new FormData(form);
                        const method = (form.getAttribute('method') || 'POST').toUpperCase();

                        try {
                            const resp = await fetch(form.action, {
                                method,
                                body: formData,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json, text/html',
                                },
                            });

                            const contentType = resp.headers.get('content-type') || '';
                            const responseText = await resp.text();

                            if (contentType.includes('application/json')) {
                                let data = {};
                                try {
                                    data = JSON.parse(responseText);
                                } catch (e) {
                                    console.warn('Resposta JSON invalida', e);
                                    this.renderModalError(targetId, 'Resposta invalida do servidor.');
                                    return;
                                }

                                if (data.redirect) {
                                    keepLoadingUntilNavigate = true;
                                    window.location.href = data.redirect;
                                    return;
                                }
                                if (data.success) {
                                    await this.refreshGrid();

                                    if (isFormModal) {
                                        this.closeFormModal();
                                    } else {
                                        await this.refreshShowModal();
                                    }
                                    return;
                                }

                                const errorMessage = data.message
                                    || Object.values(data.errors || {})?.[0]?.[0]
                                    || 'Falha ao salvar solicitacao.';
                                this.renderModalError(targetId, errorMessage);
                                return;
                            }

                            const target = document.getElementById(targetId);
                            if (target) {
                                const selector = isFormModal
                                    ? '#solicitacao-form-modal-body'
                                    : '[data-solicitacao-modal-content]';
                                const htmlToRender = extractModalHtml(responseText, selector);
                                renderSolicitacaoModalContent(htmlToRender || responseText, target);
                                if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                                    window.Alpine.initTree(target);
                                }
                                bindSolicitacaoModalHandlers(
                                    target,
                                    () => {
                                        if (isFormModal) this.closeFormModal();
                                        else this.closeShowModal();
                                    },
                                    (f) => this.submitModalForm(f, targetId)
                                );
                            }
                        } catch (err) {
                            console.error('[SOLICITACAO] Modal submit error', err);
                            this.renderModalError(targetId, 'Falha ao salvar solicitacao. Verifique sua conexão.');
                        } finally {
                            if (keepLoadingUntilNavigate) {
                                return;
                            }
                            if (isFormModal) {
                                this.formModalLoading = false;
                            } else {
                                this.showModalLoading = false;
                            }
                        }
                    },

                    // Modais rápidos (Confirmar/Aprovar/Cancelar)
                    showQuickConfirmModal: false,
                    showQuickForwardModal: false,
                    showQuickApproveModal: false,
                    showQuickSendModal: false,
                    showQuickCancelModal: false,
                    selectedSolicitacaoId: null,
                    urlConfirm() { return this.confirmUrlBase.replace('__ID__', this.selectedSolicitacaoId); },
                    urlForward() { return this.forwardUrlBase.replace('__ID__', this.selectedSolicitacaoId); },
                    urlApprove() { return this.approveUrlBase.replace('__ID__', this.selectedSolicitacaoId); },
                    urlSend() { return this.sendUrlBase.replace('__ID__', this.selectedSolicitacaoId); },
                    urlCancel() { return this.cancelUrlBase.replace('__ID__', this.selectedSolicitacaoId); },

                    mostrarModalConfirmar(id) {
                        this.selectedSolicitacaoId = id;
                        this.showQuickConfirmModal = true;
                    },
                    mostrarModalEncaminharLiberacao(id) {
                        this.selectedSolicitacaoId = id;
                        this.showQuickForwardModal = true;
                    },
                    mostrarModalAprovar(id) {
                        this.selectedSolicitacaoId = id;
                        this.showQuickApproveModal = true;
                    },
                    mostrarModalEnviar(id) {
                        this.selectedSolicitacaoId = id;
                        this.showQuickSendModal = true;
                    },
                    mostrarModalCancelar(id) {
                        this.selectedSolicitacaoId = id;
                        this.showQuickCancelModal = true;
                    },
                    async submitQuickAction(event) {
                        const form = event?.target;
                        if (!form) return;

                        const formData = new FormData(form);
                        const method = (form.getAttribute('method') || 'POST').toUpperCase();

                        try {
                            const response = await fetch(form.action, {
                                method,
                                body: formData,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json, text/html',
                                },
                            });

                            const contentType = response.headers.get('content-type') || '';
                            const responseText = await response.text();

                            if (!contentType.includes('application/json')) {
                                throw new Error('Resposta inesperada ao salvar a ação rápida.');
                            }

                            let data = {};
                            try {
                                data = JSON.parse(responseText);
                            } catch (error) {
                                throw new Error('Resposta inválida do servidor.');
                            }

                            if (!response.ok || data.success === false) {
                                throw new Error(
                                    data.message
                                    || Object.values(data.errors || {})?.[0]?.[0]
                                    || 'Falha ao processar a ação.'
                                );
                            }

                            this.fecharModais();
                            await this.refreshGrid();

                            if (this.showModalOpen && this.currentSolicitacaoId === this.selectedSolicitacaoId) {
                                this.showModalLoading = true;
                                await this.refreshShowModal();
                            }
                        } catch (error) {
                            console.error('[SOLICITACAO] Quick action error', error);
                            alert(error.message || 'Falha ao processar a ação.');
                        } finally {
                            this.showModalLoading = false;
                        }
                    },
                    fecharModais() {
                        this.showQuickConfirmModal = false;
                        this.showQuickForwardModal = false;
                        this.showQuickApproveModal = false;
                        this.showQuickSendModal = false;
                        this.showQuickCancelModal = false;
                    }
                };
            }

            document.addEventListener('DOMContentLoaded', () => {
                // Habilitar escape para fechar o modal
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        const alpine = document.querySelector('[x-data*="solicitacaoBemsIndex"]')?.__x?.$.data;
                        if (alpine && alpine.formModalOpen) {
                            alpine.closeFormModal();
                        }
                        if (alpine && alpine.showModalOpen) {
                            alpine.closeShowModal();
                        }
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>







