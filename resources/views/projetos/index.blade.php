<x-app-layout>
    <script>
        function locaisPage(baseUrl) {
            return {
                tags: [],
                inputValue: '',
                selectedLocalIds: [],
                showDeleteModal: false,
                deleteTarget: null,
                loadingDelete: false,
                toast: {
                    show: false,
                    mensagem: '',
                    tipo: 'sucesso'
                },

                init() {
                    const params = new URLSearchParams(window.location.search);
                    const multiSearch = params.getAll('search[]');
                    if (multiSearch.length > 0) {
                        this.tags = multiSearch.map((t) => t.trim()).filter(Boolean);
                    } else {
                        const singleSearch = params.get('search');
                        if (singleSearch) {
                            this.tags = singleSearch.split(',').map((t) => t.trim()).filter(Boolean);
                        }
                    }

                    this.$nextTick(() => this.setupRowHandlers());
                },

                addTag() {
                    const value = this.inputValue.trim();
                    if (value && !this.tags.includes(value)) {
                        this.tags.push(value);
                        this.inputValue = '';
                        this.search();
                    }
                },

                removeTag(index) {
                    this.tags.splice(index, 1);
                    this.search();
                },

                removeLastTag() {
                    if (!this.inputValue && this.tags.length > 0) {
                        this.tags.pop();
                        this.search();
                    }
                },

                search() {
                    const params = new URLSearchParams();
                    const currentUrlParams = new URLSearchParams(window.location.search);

                    ['sort', 'direction'].forEach((key) => {
                        const value = currentUrlParams.get(key);
                        if (value) params.set(key, value);
                    });

                    const terms = [...this.tags];
                    if (this.inputValue.trim()) {
                        terms.push(this.inputValue.trim());
                    }
                    if (terms.length) {
                        terms.forEach((term) => params.append('search[]', term));
                    }
                    params.set('page', '1');

                    const nextUrl = `${baseUrl}?${params.toString()}`;
                    window.history.replaceState({}, '', nextUrl);

                    fetch(`${nextUrl}&api=1`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    })
                        .then((response) => response.json())
                        .then((data) => {
                            const tbody = document.querySelector('tbody');
                            if (tbody) {
                                tbody.innerHTML = data.html || '<tr><td colspan="5" class="px-4 py-3 text-center text-sm">Nenhum local encontrado.</td></tr>';
                                this.selectedLocalIds = [];
                                this.syncHeaderCheckbox();
                                this.setupRowHandlers();
                            }
                        })
                        .catch((error) => console.error('Erro ao buscar os dados:', error));
                },

                setupRowHandlers() {
                    const tbody = document.querySelector('tbody');
                    if (!tbody) return;

                    if (tbody._checkboxHandler) {
                        tbody.removeEventListener('change', tbody._checkboxHandler);
                    }
                    if (tbody._clickHandler) {
                        tbody.removeEventListener('click', tbody._clickHandler);
                    }

                    tbody._checkboxHandler = (event) => {
                        const checkbox = event.target.closest('.checkbox-local');
                        if (!checkbox) return;

                        const localId = parseInt(checkbox.dataset.localId, 10);
                        if (Number.isNaN(localId)) return;

                        if (checkbox.checked) {
                            if (!this.selectedLocalIds.includes(localId)) {
                                this.selectedLocalIds.push(localId);
                            }
                        } else {
                            this.selectedLocalIds = this.selectedLocalIds.filter((id) => id !== localId);
                        }
                        this.syncHeaderCheckbox();
                    };

                    tbody._clickHandler = (event) => {
                        const deleteBtn = event.target.closest('.delete-btn-local');
                        if (!deleteBtn) return;

                        event.preventDefault();
                        const localId = parseInt(deleteBtn.dataset.localId, 10);
                        const localName = deleteBtn.dataset.localName || '';
                        this.openSingleDeleteModal(localId, localName);
                    };

                    tbody.addEventListener('change', tbody._checkboxHandler);
                    tbody.addEventListener('click', tbody._clickHandler);
                },

                toggleAll(checked) {
                    const checkboxes = document.querySelectorAll('.checkbox-local');
                    this.selectedLocalIds = [];
                    if (checked) {
                        checkboxes.forEach((checkbox) => {
                            const localId = parseInt(checkbox.dataset.localId, 10);
                            if (!Number.isNaN(localId)) {
                                this.selectedLocalIds.push(localId);
                                checkbox.checked = true;
                            }
                        });
                    } else {
                        checkboxes.forEach((checkbox) => {
                            checkbox.checked = false;
                        });
                    }
                    this.syncHeaderCheckbox();
                },

                clearSelection() {
                    this.selectedLocalIds = [];
                    document.querySelectorAll('.checkbox-local').forEach((checkbox) => {
                        checkbox.checked = false;
                    });
                    this.syncHeaderCheckbox();
                },

                syncHeaderCheckbox() {
                    const headerCheckbox = document.querySelector('#checkbox-header');
                    if (!headerCheckbox) return;

                    const totalCheckboxes = document.querySelectorAll('.checkbox-local').length;
                    const selected = this.selectedLocalIds.length;

                    headerCheckbox.checked = totalCheckboxes > 0 && selected === totalCheckboxes;
                    headerCheckbox.indeterminate = selected > 0 && selected < totalCheckboxes;
                },

                openMultipleDeleteModal() {
                    if (!this.selectedLocalIds.length) {
                        this.showToast('Selecione ao menos um local para remover.', 'erro');
                        return;
                    }
                    this.deleteTarget = null;
                    this.showDeleteModal = true;
                },

                openSingleDeleteModal(localId, localName) {
                    if (!localId) return;
                    this.deleteTarget = { id: localId, nome: localName };
                    this.showDeleteModal = true;
                },

                closeDeleteModal() {
                    this.showDeleteModal = false;
                    this.deleteTarget = null;
                },

                async confirmDelete() {
                    this.loadingDelete = true;

                    try {
                        if (this.deleteTarget) {
                            await this.deleteSingle();
                        } else {
                            await this.deleteMultiple();
                        }
                    } finally {
                        this.loadingDelete = false;
                    }
                },

                async deleteSingle() {
                    const localId = this.deleteTarget?.id;
                    if (!localId) {
                        this.showToast('Não foi possível identificar o local para remoção.', 'erro');
                        return;
                    }

                    const response = await fetch(`/projetos/${localId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        this.showToast(data.message || 'Erro ao remover local.', 'erro');
                        return;
                    }

                    this.closeDeleteModal();
                    this.showToast('Local removido com sucesso!', 'sucesso', 2500);
                    setTimeout(() => window.location.reload(), 700);
                },

                async deleteMultiple() {
                    if (!this.selectedLocalIds.length) {
                        this.showToast('Selecione ao menos um local para remover.', 'erro');
                        return;
                    }

                    const response = await fetch('{{ route("projetos.delete-multiple") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ ids: this.selectedLocalIds })
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        this.showToast(data.message || 'Erro ao remover locais.', 'erro');
                        return;
                    }

                    const quantidade = this.selectedLocalIds.length;
                    this.closeDeleteModal();
                    this.clearSelection();
                    this.showToast(`${quantidade} local(is) removido(s) com sucesso!`, 'sucesso', 2500);
                    setTimeout(() => window.location.reload(), 700);
                },

                showToast(mensagem, tipo = 'sucesso', duracao = 4000) {
                    this.toast.mensagem = mensagem;
                    this.toast.tipo = tipo;
                    this.toast.show = true;

                    if (duracao > 0) {
                        setTimeout(() => {
                            this.toast.show = false;
                        }, duracao);
                    }
                }
            };
        }
    </script>

    <div class="py-12" x-data="locaisPage('{{ route('projetos.index') }}')" x-init="init()">
        <div x-show="toast.show"
            :class="toast.tipo === 'sucesso' ? 'bg-green-500' : 'bg-red-500'"
            x-transition:enter="transition ease-out duration-300"
            x-transition:leave="transition ease-in duration-200"
            class="fixed top-4 right-4 z-50 text-white px-6 py-3 rounded-lg shadow-lg max-w-sm">
            <span x-text="toast.mensagem"></span>
        </div>

        <div class="w-full sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex justify-between items-center mb-4">
                        <div class="w-1/2">
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
                                    @input.debounce.500ms="search"
                                    type="text"
                                    placeholder="Buscar..."
                                    :style="'width:' + Math.max(120, inputValue.length * 10) + 'px'"
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
                        <a href="{{ route('projetos.create') }}" class="bg-plansul-blue hover:bg-opacity-90 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                            Criar Novo Local
                        </a>
                    </div>

                    @include('projetos._table_partial', [
                        'locais' => $locais,
                        'sort' => $sort ?? request('sort', 'delocal'),
                        'direction' => $direction ?? request('direction', 'asc')
                    ])
                </div>
            </div>
        </div>

        <div x-show="showDeleteModal" x-transition class="fixed inset-0 bg-black/50 dark:bg-black/70 z-50 flex items-center justify-center" @click.self="closeDeleteModal()" style="display: none;">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 max-w-sm mx-4" @click.stop>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Confirmar Remoção</h3>
                <p class="text-gray-600 dark:text-gray-300 mb-6">
                    <template x-if="deleteTarget">
                        <span>Tem certeza que deseja remover o local "<strong x-text="deleteTarget.nome"></strong>"?</span>
                    </template>
                    <template x-if="!deleteTarget">
                        <span>Tem certeza que deseja remover <strong x-text="selectedLocalIds.length"></strong> local(is)?</span>
                    </template>
                </p>

                <div class="flex gap-3 justify-end">
                    <button
                        type="button"
                        @click="closeDeleteModal()"
                        :disabled="loadingDelete"
                        class="px-4 py-2 text-sm bg-gray-300 hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 rounded transition disabled:opacity-50 disabled:cursor-not-allowed">
                        Cancelar
                    </button>
                    <button
                        type="button"
                        @click="confirmDelete()"
                        :disabled="loadingDelete"
                        class="px-4 py-2 text-sm bg-red-600 hover:bg-red-700 text-white rounded transition font-semibold flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <template x-if="!loadingDelete">
                            <span>Remover</span>
                        </template>
                        <template x-if="loadingDelete">
                            <span>Removendo...</span>
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
