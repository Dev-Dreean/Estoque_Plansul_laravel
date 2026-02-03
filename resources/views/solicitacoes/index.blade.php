<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Solicitações de Bens') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="solicitacaoBemsIndex()"
        data-projetos='@json($projetos ?? [])'
        data-confirm-url="{{ route('solicitacoes-bens.confirm', ['solicitacao' => '__ID__']) }}"
        data-approve-url="{{ route('solicitacoes-bens.approve', ['solicitacao' => '__ID__']) }}"
        data-cancel-url="{{ route('solicitacoes-bens.cancel', ['solicitacao' => '__ID__']) }}">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex flex-col gap-4 mb-6">
                        <div x-data="{ open: false }" class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg">
                            <div class="flex justify-between items-center">
                                <h3 class="font-semibold text-lg">Filtros de Busca</h3>
                                <button type="button" @click="open = !open" aria-expanded="open" aria-controls="filtros-solicitacoes-bens" class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                    <span class="sr-only">Expandir filtros</span>
                                </button>
                            </div>
                            <div x-show="open" x-transition class="mt-4" style="display: none;">
                                <form method="GET" action="{{ route('solicitacoes-bens.index') }}" id="filtros-solicitacoes-bens" class="grid gap-4 md:grid-cols-5 items-end">
                                    <div class="md:col-span-2">
                                        <x-input-label for="search" value="Buscar" />
                                        <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" value="{{ request('search') }}" placeholder="Nome, matricula, setor, local" />
                                    </div>
                                    <div>
                                        <x-input-label for="status" value="Status" />
                                        <select id="status" name="status" class="input-base mt-1 block w-full">
                                            <option value="">Todos</option>
                                            @foreach($statusOptions as $status)
                                                <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <x-input-label for="uf" value="UF" />
                                        <x-text-input id="uf" name="uf" type="text" maxlength="2" class="mt-1 block w-full uppercase" value="{{ request('uf') }}" placeholder="UF" />
                                    </div>
                                    <div>
                                        <x-input-label for="per_page" value="Itens por pagina" />
                                        <select id="per_page" name="per_page" class="input-base mt-1 block w-full">
                                            @foreach([10, 30, 50, 100, 200] as $opt)
                                                <option value="{{ $opt }}" @selected((int) request('per_page', 30) === $opt)>{{ $opt }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="md:col-span-5 flex items-center gap-3">
                                        <x-primary-button class="h-10 px-4">Filtrar</x-primary-button>
                                        <a href="{{ route('solicitacoes-bens.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">Limpar</a>
                                    </div>
                                </form>
                            </div>
                        </div>

                        @php
                            $userForCreate = auth()->user();
                            $isAdmin = $userForCreate?->isAdmin() ?? false;
                            $canCreateSolicitacao = $isAdmin || ($userForCreate?->temAcessoTela('1013') ?? false);
                        @endphp
                        @if($canCreateSolicitacao)
                            <div>
                                <button type="button" @click="openCreateModal()" class="bg-plansul-blue hover:bg-opacity-90 text-white font-semibold py-2 px-4 rounded inline-flex items-center">
                                    <x-heroicon-o-plus-circle class="w-5 h-5 mr-2" />
                                    <span>Nova solicitacao</span>
                                </button>
                            </div>
                        @endif
                    </div>

                    @php
                        $statusColors = [
                            'PENDENTE' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                            'AGUARDANDO_CONFIRMACAO' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                            'CONFIRMADO' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                            'CANCELADO' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                        ];
                    @endphp

                    @php
                        $currentUser = auth()->user();
                        $currentUserId = $currentUser?->getAuthIdentifier();
                        $currentUserMatricula = trim((string) ($currentUser?->CDMATRFUNCIONARIO ?? ''));
                        $isAdminUser = $currentUser?->isAdmin() ?? false;
                    @endphp

                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3">Numero</th>
                                    <th class="px-4 py-3">Solicitante</th>
                                    <th class="px-4 py-3">Setor</th>
                                    <th class="px-4 py-3">Local destino</th>
                                    <th class="px-4 py-3">UF</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Itens</th>
                                    <th class="px-4 py-3">Criado</th>
                                    <th class="px-4 py-3">Acoes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($solicitacoes as $solicitacao)
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-slate-50 dark:hover:bg-slate-700/40 transition-colors cursor-pointer" @click="openShowModal({{ $solicitacao->id }})">
                                        <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">#{{ $solicitacao->id }}</td>
                                        <td class="px-4 py-3">
                                            <div class="text-gray-900 dark:text-gray-100">{{ $solicitacao->solicitante_nome ?? '-' }}</div>
                                            <div class="text-xs text-gray-500">{{ $solicitacao->solicitante_matricula ?? '-' }}</div>
                                        </td>
                                        <td class="px-4 py-3">{{ $solicitacao->setor ?? '-' }}</td>
                                        <td class="px-4 py-3">{{ $solicitacao->local_destino ?? '-' }}</td>
                                        <td class="px-4 py-3">{{ $solicitacao->uf ?? '-' }}</td>
                                        <td class="px-4 py-3">
                                            <x-status-badge :status="$solicitacao->status" :color-map="$statusColors" />
                                        </td>
                                        <td class="px-4 py-3">{{ $solicitacao->itens_count ?? 0 }}</td>
                                        <td class="px-4 py-3">{{ optional($solicitacao->created_at)->format('d/m/Y H:i') }}</td>
                                        <td class="px-4 py-3">
                                            @php
                                                $isOwner = $currentUserId
                                                    && (string) $solicitacao->solicitante_id === (string) $currentUserId;
                                                if (!$isOwner && $currentUserMatricula !== '') {
                                                    $isOwner = trim((string) ($solicitacao->solicitante_matricula ?? '')) === $currentUserMatricula;
                                                }
                                                $canConfirm = $currentUser?->temAcessoTela('1011') ?? false;
                                                $canApprove = ($currentUser?->temAcessoTela('1014') ?? false)
                                                    && $solicitacao->status === 'AGUARDANDO_CONFIRMACAO';
                                                $canCancel = ($currentUser?->temAcessoTela('1015') ?? false)
                                                    && $solicitacao->status === 'PENDENTE';
                                            @endphp
                                            <div class="flex items-center gap-2" @click.stop>
                                                @if($canConfirm && $solicitacao->status === 'PENDENTE')
                                                    <button type="button" title="Confirmar" @click="mostrarModalConfirmar({{ $solicitacao->id }})" 
                                                        class="inline-flex items-center justify-center p-1.5 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/30 rounded-lg transition">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                    </button>
                                                @endif

                                                @if($canApprove)
                                                    <button type="button" title="Aprovar" @click="mostrarModalAprovar({{ $solicitacao->id }})" 
                                                        class="inline-flex items-center justify-center p-1.5 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </button>
                                                @endif

                                                @if($canCancel)
                                                    <button type="button" title="Cancelar" @click="mostrarModalCancelar({{ $solicitacao->id }})" 
                                                        class="inline-flex items-center justify-center p-1.5 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                @endif

                                                @if($isAdminUser || $isOwner)
                                                    <form method="POST" action="{{ route('solicitacoes-bens.destroy', $solicitacao) }}" onsubmit="return confirm('Remover a solicitacao #{{ $solicitacao->id }}?');" class="inline" @click.stop>
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="inline-flex items-center justify-center p-1.5 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition" title="Remover">
                                                            <x-heroicon-o-trash class="h-4 w-4" />
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-4 py-6 text-center text-gray-500">Nenhuma solicitacao encontrada.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <div class="mt-4">
                            {{ $solicitacoes->links() }}
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
                            <form method="POST" :action="urlConfirm()" class="p-6 space-y-4">
                                @csrf
                                @method('POST')
                                
                                <div>
                                    <label for="quick_recebedor_search" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Responsavel Recebedor *</label>
                                    <x-user-autocomplete
                                        id="quick_recebedor_search"
                                        name="recebedor_matricula"
                                        value=""
                                        placeholder="Digite matricula ou nome..."
                                        class="h-8 text-xs border-gray-300 dark:border-gray-600" />
                                    <x-input-error :messages="$errors->get('recebedor_matricula')" class="mt-1" />
                                </div>

                                <div>
                                    <label for="quick_tracking_code" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Código de Rastreio *</label>
                                    <input type="text" id="quick_tracking_code" name="tracking_code" required 
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 text-xs h-8 px-3"
                                        placeholder="Ex: RAS-2025-001" />
                                </div>
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

                    <!-- Modal: Aprovar (Quick) -->
                    <div x-show="showQuickApproveModal" x-transition class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50" style="display:none;">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-md w-full mx-4 overflow-hidden">
                            <div class="bg-blue-600 text-white px-6 py-4 flex items-center justify-between">
                                <h3 class="text-sm font-bold">Aprovar Solicitação</h3>
                                <button @click="fecharModais()" class="text-white/70 hover:text-white">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <form method="POST" :action="urlApprove()" class="p-6 space-y-4">
                                @csrf
                                @method('POST')
                                
                                <p class="text-sm text-gray-600 dark:text-gray-400">Tem certeza que deseja aprovar esta solicitação? Ela será marcada como confirmada.</p>

                                <div class="flex gap-2 pt-4">
                                    <button type="button" @click="fecharModais()" class="flex-1 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition">
                                        Cancelar
                                    </button>
                                    <button type="submit" class="flex-1 px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">
                                        Aprovar
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
                            <form method="POST" :action="urlCancel()" class="p-6 space-y-4">
                                @csrf
                                @method('POST')
                                
                                <div>
                                    <label for="quick_justificativa" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Motivo do Cancelamento *</label>
                                    <textarea id="quick_justificativa" name="justificativa_cancelamento" required rows="3"
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

        <!-- Modal para criar solicitacao -->
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
            <div class="flex flex-col items-center gap-6">
                <div class="relative w-20 h-20">
                    <svg class="w-full h-full animate-spin" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" style="color: rgb(209, 213, 219);"></circle>
                        <path class="opacity-100" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" style="color: rgb(99, 102, 241);"></path>
                    </svg>
                </div>
                <div class="text-center">
                    <h3 class="text-xl font-semibold text-white mb-2">Carregando...</h3>
                    <p class="text-gray-300 text-sm">Preparando o formulário</p>
                </div>
            </div>
        </div>

        <!-- Loading Screen (Show modal) -->
        <div
            x-show="showModalLoading"
            x-transition:leave="transition ease-out duration-300"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            x-cloak
            class="fixed inset-0 z-[70] flex items-center justify-center pointer-events-none"
        >
            <div class="flex flex-col items-center gap-6">
                <div class="relative w-20 h-20">
                    <svg class="w-full h-full animate-spin" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" style="color: rgb(209, 213, 219);"></circle>
                        <path class="opacity-100" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" style="color: rgb(99, 102, 241);"></path>
                    </svg>
                </div>
                <div class="text-center">
                    <h3 class="text-xl font-semibold text-white mb-2">Carregando...</h3>
                    <p class="text-gray-300 text-sm">Buscando detalhes da solicitacao</p>
                </div>
            </div>
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
        <!-- Modal de detalhes da solicitacao -->
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
                    <button type="button" @click="closeShowModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 text-2xl leading-none">×</button>
                </div>
                <div class="relative flex-1 overflow-y-auto bg-white dark:bg-gray-800 min-h-[320px] solicitacao-modal-scroll">
                    <div x-show="showModalLoading" class="absolute inset-0 flex items-center justify-center bg-white/90 dark:bg-gray-800/90 z-10">
                        <div class="text-sm text-indigo-600 dark:text-indigo-400 bg-white dark:bg-gray-800 px-4 py-2 rounded shadow-lg border border-indigo-200 dark:border-indigo-700 animate-pulse">Carregando detalhes...</div>
                    </div>
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
                    patrimonio_busca: item.patrimonio_busca || item.descricao || '',
                    selecionado: Boolean(item.descricao),
                });

                return {
                    step: showStep2 ? 2 : 1,
                    item: (itensOld && itensOld.length) ? buildItem(itensOld[0]) : buildItem(),
                    nextStep() {
                        const fields = ['projeto_id', 'setor', 'local_destino'];
                        for (const id of fields) {
                            const el = document.getElementById(id);
                            if (!el) continue;
                            if (!String(el.value || '').trim()) {
                                if (typeof el.reportValidity === 'function') {
                                    el.reportValidity();
                                }
                                el.focus();
                                return;
                            }
                        }
                        this.step = 2;
                    },
                };
            }

            function projetoSearch(projetosInjected = null) {
                // Se projetos nao for passado, tenta pegar do global (caso estivesse no script inline)
                // Mas aqui assumimos que virá via argumento ou usamos a lista global da pagina index
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
                        this.item.descricao = '';
                        this.item.selecionado = false;

                        const term = (this.item?.patrimonio_busca || '').trim();
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
                                console.error('[SOLICITACAO] Erro ao buscar patrimonios', err);
                            }
                        } finally {
                            this.loading = false;
                        }
                    },

                    selectResultado(resultado) {
                        const text = resultado?.text
                            || [resultado?.nupatrimonio, resultado?.descricao].filter(Boolean).join(' - ');
                        this.item.descricao = text;
                        this.item.patrimonio_busca = text;
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
                    approveUrlBase: '',
                    cancelUrlBase: '',
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
                    init() {
                        this.confirmUrlBase = this.$el?.dataset?.confirmUrl || '';
                        this.approveUrlBase = this.$el?.dataset?.approveUrl || '';
                        this.cancelUrlBase = this.$el?.dataset?.cancelUrl || '';
                    },


                    csrf() {
                        return document.querySelector('meta[name=csrf-token]')?.content || '';
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
                        this.showModalTitle = `Solicita\u00e7\u00e3o #${id}`;
                        this.showModalOpen = false;
                        this.showModalLoading = true;
                        this.showModalContentReady = false;
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
                            ? 'Crie uma nova solicitacao de bens.'
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

                    async submitModalForm(form, targetId) {
                        if (!form) return;
                        
                        // Fechar modal imediatamente ao submeter
                        this.closeFormModal();
                        this.closeShowModal();
                        
                        // Mostrar loading (reutilizar o loading do show modal)
                        this.showModalLoading = true;
                        
                        const formData = new FormData(form);
                        const method = (form.getAttribute('method') || 'POST').toUpperCase();

                        try {
                            const resp = await fetch(form.action, {
                                method,
                                body: formData,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'text/html', // Prefer HTML for errors, JSON for success handled by content-type check
                                },
                            });
                            
                            const contentType = resp.headers.get('content-type') || '';
                            const responseText = await resp.text();

                            // Se for JSON (sucesso explícito)
                            if (contentType.includes('application/json')) {
                                try {
                                    const data = JSON.parse(responseText);
                                    if (data.redirect) {
                                        window.location.href = data.redirect;
                                        return;
                                    }
                                    if (data.success) {
                                        // Sucesso sem redirect: reload page
                                        window.location.reload();
                                        return;
                                    }
                                } catch (e) {
                                    console.warn('Resposta JSON invalida', e);
                                }
                            }

                            // Se chegou aqui, é HTML.
                            // Pode ser erro de validacao (422) ou sucesso com redirect seguido (200 com HTML).
                            // Se for sucesso (200 OK) e HTML, pode ser que o controller redirecionou para INDEX ou SHOW page.
                            // Mas se estamos num modal, não queremos renderizar a INDEX inteira dentro do modal.
                            // ASSUMCAO: Se retornou HTML, é porque deu erro de validacao e o Laravel redirecionou 'back' (para a URL do modal).
                            
                            // Se tiver erro, reabrir o modal
                            this.showModalLoading = false;
                            this.formModalOpen = true;
                            this.formModalLoading = false;
                            
                            const target = document.getElementById(targetId);
                            if (target) {
                                renderSolicitacaoModalContent(responseText, target);
                                if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                                    window.Alpine.initTree(target);
                                }
                                // Rebind handlers no novo HTML
                                bindSolicitacaoModalHandlers(
                                    target,
                                    () => {
                                        if (targetId === 'solicitacao-form-modal-body') this.closeFormModal();
                                        else this.closeShowModal();
                                    },
                                    (f) => this.submitModalForm(f, targetId)
                                );
                            }
                        } catch (err) {
                            console.error('[SOLICITACAO] Modal submit error', err);
                            console.log('Error details:', err.message);
                            this.showModalLoading = false;
                            alert('Falha ao salvar solicitacao. Verifique sua conexao.');
                        } finally {
                            // Não fazer nada aqui pois já controlamos acima
                        }
                    },

                    // Modais rápidos (Confirmar/Aprovar/Cancelar)
                    showQuickConfirmModal: false,
                    showQuickApproveModal: false,
                    showQuickCancelModal: false,
                    selectedSolicitacaoId: null,
                    urlConfirm() { return this.confirmUrlBase.replace('__ID__', this.selectedSolicitacaoId); },
                    urlApprove() { return this.approveUrlBase.replace('__ID__', this.selectedSolicitacaoId); },
                    urlCancel() { return this.cancelUrlBase.replace('__ID__', this.selectedSolicitacaoId); },

                    mostrarModalConfirmar(id) {
                        this.selectedSolicitacaoId = id;
                        this.showQuickConfirmModal = true;
                    },
                    mostrarModalAprovar(id) {
                        this.selectedSolicitacaoId = id;
                        this.showQuickApproveModal = true;
                    },
                    mostrarModalCancelar(id) {
                        this.selectedSolicitacaoId = id;
                        this.showQuickCancelModal = true;
                    },
                    fecharModais() {
                        this.showQuickConfirmModal = false;
                        this.showQuickApproveModal = false;
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
