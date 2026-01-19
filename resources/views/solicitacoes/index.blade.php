<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Solicitacoes de Bens') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="solicitacaoBemsIndex()">
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

                        <div>
                            <button type="button" @click="openCreateModal()" class="bg-plansul-blue hover:bg-opacity-90 text-white font-semibold py-2 px-4 rounded inline-flex items-center">
                                <x-heroicon-o-plus-circle class="w-5 h-5 mr-2" />
                                <span>Nova solicitacao</span>
                            </button>
                        </div>
                    </div>

                    @php
                        $statusColors = [
                            'PENDENTE' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                            'SEPARADO' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                            'CONCLUIDO' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                            'CANCELADO' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                        ];
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
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
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
                                            <button type="button" @click="openShowModal({{ $solicitacao->id }})" class="text-indigo-600 dark:text-indigo-400 hover:underline">Ver</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-4 py-6 text-center text-gray-500">Nenhuma solicitacao encontrada.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $solicitacoes->links() }}
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
            x-show="formModalOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-cloak
            class="fixed inset-0 z-[60] bg-black/60 dark:bg-black/80 p-3 sm:p-6"
            @click="if(!formModalLoading) closeFormModal()"
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
                <div class="relative flex-1 min-h-0 overflow-hidden">
                    <div id="solicitacao-form-modal-body" class="h-full min-h-0 overflow-y-auto overscroll-contain"></div>
                </div>
            </div>
        </div>
        <!-- Modal de detalhes da solicitacao -->
        <div
            x-show="showModalOpen"
            x-cloak
            class="fixed inset-0 z-[60] flex items-center justify-center p-3 sm:p-6 pointer-events-none"
        >
            <div
                x-show="showModalOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="solicitacao-modal-theme rounded-2xl shadow-2xl w-full max-w-5xl h-auto max-h-[90vh] overflow-hidden border flex flex-col min-h-0 pointer-events-auto"
                @click.self="closeShowModal"
            >
                <div class="flex items-center justify-between px-4 sm:px-6 py-3 bg-[var(--solicitacao-modal-bg)] border-b border-gray-200 dark:border-gray-700">
                    <div>
                        <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white" x-text="showModalTitle"></h3>
                    </div>
                    <button type="button" @click="closeShowModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 text-2xl leading-none">×</button>
                </div>
                <div class="relative flex-1 min-h-0 overflow-hidden">
                    <div x-show="showModalLoading" class="absolute inset-0 flex items-center justify-center bg-black/20">
                        <div class="text-sm text-white">Carregando...</div>
                    </div>
                    <div id="solicitacao-show-modal-body" class="h-full min-h-0 overflow-y-auto overscroll-contain"></div>
                </div>
            </div>
        </div>
    </div>

    </div>

    @push('scripts')
        <script>
            function renderSolicitacaoModalContent(html, target) {
                if (!target) return;
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const scripts = Array.from(doc.querySelectorAll('script'));
                scripts.forEach((script) => script.remove());
                target.innerHTML = doc.body.innerHTML;
                scripts.forEach((original) => {
                    const script = document.createElement('script');
                    if (original.type) {
                        script.type = original.type;
                    }
                    if (original.src) {
                        script.src = original.src;
                        script.async = false;
                    } else {
                        script.text = original.textContent || '';
                    }
                    document.body.appendChild(script);
                });
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

            function solicitacaoBemsIndex() {
                return {
                    formModalOpen: false,
                    formModalLoading: false,
                    formModalTitle: '',
                    formModalSubtitle: '',
                    formModalMode: null,
                    formModalId: null,
                    showModalOpen: false,
                    showModalLoading: false,
                    showModalTitle: '',

                    csrf() {
                        return document.querySelector('meta[name=csrf-token]')?.content || '';
                    },

                    openCreateModal() {
                        this.openFormModal('create');
                    },
                    openShowModal(id) {
                        if (!id) return;
                        const modalBody = document.getElementById('solicitacao-show-modal-body');
                        if (!modalBody) return;
                        this.showModalTitle = `Solicitacao #${id}`;
                        this.showModalOpen = true;
                        this.showModalLoading = true;

                        const url = "{{ url('solicitacoes-bens') }}/" + encodeURIComponent(id) + "?modal=1";
                        fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'text/html',
                            },
                        })
                            .then((resp) => {
                                if (!resp.ok) {
                                    throw new Error(`HTTP ${resp.status}`);
                                }
                                return resp.text();
                            })
                            .then((html) => {
                                renderSolicitacaoModalContent(html, modalBody);
                            })
                            .catch((err) => {
                                console.error('[SOLICITACAO] Show modal fetch error', err);
                                modalBody.innerHTML = '<div class="p-6 text-sm text-red-600">Falha ao carregar detalhes.</div>';
                            })
                            .finally(() => {
                                this.showModalLoading = false;
                            });
                    },

                    openFormModal(mode, id = null) {
                        const modalBody = document.getElementById('solicitacao-form-modal-body');
                        if (!modalBody) return;
                        if (mode === 'edit' && !id) return;

                        this.formModalMode = mode;
                        this.formModalId = id;
                        this.formModalTitle = mode === 'create' ? 'Nova Solicitacao de Bens' : 'Editar Solicitacao';
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
                                if (!resp.ok) {
                                    throw new Error(`HTTP ${resp.status}`);
                                }
                                return resp.text();
                            })
                            .then((html) => {
                                this.applyFormModalHtml(html);
                            })
                            .catch((err) => {
                                console.error('[SOLICITACAO] Modal fetch error', err);
                                modalBody.innerHTML = '<div class="p-6 text-sm text-red-600">Falha ao carregar formulario.</div>';
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
                            (form) => this.submitModalForm(form)
                        );
                    },

                    async submitModalForm(form) {
                        if (!form) return;
                        this.formModalLoading = true;
                        const formData = new FormData(form);
                        const method = (form.getAttribute('method') || 'POST').toUpperCase();

                        try {
                            const resp = await fetch(form.action, {
                                method,
                                body: formData,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'text/html',
                                },
                            });
                            const contentType = resp.headers.get('content-type') || '';
                            if (resp.status === 422) {
                                const html = await resp.text();
                                this.applyFormModalHtml(html);
                                return;
                            }
                            if (contentType.includes('application/json')) {
                                const data = await resp.json().catch(() => ({}));
                                if (data.redirect) {
                                    window.location.href = data.redirect;
                                    return;
                                }
                            }
                            // ✅ SUCESSO: Fechar modal e recarregar página
                            this.closeFormModal();
                            window.location.reload();
                        } catch (err) {
                            console.error('[SOLICITACAO] Modal submit error', err);
                            alert('Falha ao salvar solicitacao.');
                        } finally {
                            this.formModalLoading = false;
                        }
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
