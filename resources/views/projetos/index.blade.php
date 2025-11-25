<x-app-layout>
    <div class="py-12" x-data="mergeProjetos('{{ route('projetos.index') }}')" @init="init()">
        <!-- Toast Notification -->
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
                                                <span>Para apagar uma tag, apague todo o texto do input ou clique no <span class="text-red-500 font-bold">×</span> da tag.</span>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <a href="{{ route('projetos.create') }}" class="bg-plansul-blue hover:bg-opacity-90 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                            <x-heroicon-o-plus-circle class="w-5 h-5 mr-2" />
                            <span>Incluir Local</span>
                        </a>
                    </div>
                    {{-- 3. Área da tabela (dentro do escopo Alpine, SEM x-html) --}}
                    @include('projetos._table_partial', ['locais' => $locais])
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    {{-- 4. Lógica Javascript do Alpine.js - Mergeado searchTagFilter + tableMultiSelect --}}
    <script>
        function mergeProjetos(baseUrl) {
            return {
                // Dados do Search/Filter
                inputValue: '',
                tags: [],
                
                // Dados do Multi-Select
                selecionados: [],
                mostraModalDelecao: false,
                carregando: false,
                localParaDelecaoIndividual: null, // Para deleção individual
                toast: {
                    show: false,
                    mensagem: '',
                    tipo: 'sucesso'
                },

                init() {
                    // Restaura as tags da URL ao carregar a página
                    const params = new URLSearchParams(window.location.search);
                    const searchParam = params.get('search');
                    if (searchParam) {
                        this.tags = searchParam.split(',').filter(tag => tag.trim().length > 0);
                    }
                    // Inicializa listeners dos checkboxes e botões
                    this.setupCheckboxListeners();
                },

                addTag() {
                    const val = this.inputValue.trim();
                    if (val && !this.tags.includes(val)) {
                        this.tags.push(val);
                        this.inputValue = '';
                        this.search();
                    }
                },

                removeTag(idx) {
                    this.tags.splice(idx, 1);
                    this.search();
                },

                removeLastTag() {
                    if (this.inputValue === '' && this.tags.length > 0) {
                        this.tags.pop();
                        this.search();
                    }
                },

                search() {
                    // Limpar seleção ao buscar
                    this.selecionados = [];
                    this.mostraModalDelecao = false;
                    
                    // Monta a URL com os parâmetros de busca
                    let params = [];
                    if (this.inputValue.trim().length > 0) {
                        params = [...this.tags, this.inputValue.trim()];
                    } else {
                        params = [...this.tags];
                    }
                    const url = `${baseUrl}?search=${encodeURIComponent(params.join(','))}&api=1`;
                    fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        })
                        .then(response => response.json())
                        .then(data => {
                            // data.html contém apenas as linhas <tr> já renderizadas
                            const tbody = document.querySelector('tbody');
                            if (tbody) {
                                tbody.innerHTML = data.html || '<tr><td colspan="5" class="px-6 py-4 text-center">Nenhum local encontrado.</td></tr>';
                                // Re-inicializa listeners após a nova injeção
                                reinitializeCheckboxListeners();
                            }
                        })
                        .catch(error => console.error('Erro ao buscar os dados:', error));
                },

                // Multi-Select Methods
                toggleSelecao(id, checked) {
                    if (checked) {
                        if (!this.selecionados.includes(id)) {
                            this.selecionados.push(id);
                        }
                    } else {
                        this.selecionados = this.selecionados.filter(s => s !== id);
                    }
                },

                toggleTodos(checked) {
                    if (checked) {
                        const rows = document.querySelectorAll('tbody tr[data-local-id]');
                        this.selecionados = [];
                        rows.forEach(row => {
                            const id = parseInt(row.dataset.localId);
                            if (!isNaN(id)) {
                                this.selecionados.push(id);
                            }
                        });
                    } else {
                        this.selecionados = [];
                        const headerCheckbox = document.querySelector('thead input[type="checkbox"]');
                        if (headerCheckbox) headerCheckbox.checked = false;
                    }
                },

                limparSelecao() {
                    this.selecionados = [];
                    const headerCheckbox = document.querySelector('thead input[type="checkbox"]');
                    if (headerCheckbox) {
                        headerCheckbox.checked = false;
                    }
                },

                irParaEdicao(url) {
                    window.location.href = url;
                },

                abrirModalDelecaoIndividual(localId, localName) {
                    this.localParaDelecaoIndividual = { id: localId, nome: localName };
                    this.mostraModalDelecao = true;
                },

                abrirModalDelecao() {
                    this.mostraModalDelecao = true;
                },

                mostrarToast(mensagem, tipo = 'sucesso', duracao = 4000) {
                    this.toast.mensagem = mensagem;
                    this.toast.tipo = tipo;
                    this.toast.show = true;

                    if (duracao > 0) {
                        setTimeout(() => {
                            this.toast.show = false;
                        }, duracao);
                    }
                },

                async confirmarDelecao() {
                    // Se há deleção individual
                    if (this.localParaDelecaoIndividual) {
                        this.confirmarDelecaoIndividual();
                        return;
                    }

                    // Deleção múltipla
                    if (this.selecionados.length === 0) {
                        this.mostrarToast('Nenhum local selecionado', 'erro');
                        return;
                    }

                    this.carregando = true;

                    try {
                        const response = await fetch('{{ route("projetos.delete-multiple") }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                ids: this.selecionados
                            })
                        });

                        const data = await response.json();

                        if (response.ok) {
                            this.carregando = false;
                            this.mostraModalDelecao = false;
                            const quantidade = this.selecionados.length;
                            this.selecionados = [];

                            this.mostrarToast(`✓ ${quantidade} local(is) removido(s) com sucesso!`, 'sucesso', 3000);

                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            this.carregando = false;
                            this.mostrarToast(data.message || 'Erro ao remover locais', 'erro');
                        }
                    } catch (error) {
                        this.carregando = false;
                        console.error('Erro:', error);
                        this.mostrarToast('Erro ao remover locais: ' + error.message, 'erro');
                    }
                },

                async confirmarDelecaoIndividual() {
                    if (!this.localParaDelecaoIndividual) {
                        this.mostrarToast('Erro ao obter local para deleção', 'erro');
                        return;
                    }

                    console.log('🗑️ Deletando local:', this.localParaDelecaoIndividual);
                    console.log('📊 ID:', this.localParaDelecaoIndividual.id, 'Tipo:', typeof this.localParaDelecaoIndividual.id);

                    if (!this.localParaDelecaoIndividual.id) {
                        console.error('❌ ID não foi definido!');
                        this.mostrarToast('Erro: ID do local não definido', 'erro');
                        return;
                    }

                    this.carregando = true;

                    try {
                        const url = `/projetos/${this.localParaDelecaoIndividual.id}`;
                        console.log('📍 Fazendo DELETE para:', url);
                        
                        const response = await fetch(url, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            }
                        });

                        console.log('✅ Response status:', response.status);
                        const data = await response.json();
                        console.log('📦 Response data:', data);

                        if (response.ok) {
                            this.carregando = false;
                            this.mostraModalDelecao = false;
                            this.localParaDelecaoIndividual = null;

                            this.mostrarToast(`✓ Local removido com sucesso!`, 'sucesso', 3000);

                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            this.carregando = false;
                            this.mostrarToast(data.message || 'Erro ao remover local', 'erro');
                        }
                    } catch (error) {
                        this.carregando = false;
                        console.error('Erro:', error);
                        this.mostrarToast('Erro ao remover local: ' + error.message, 'erro');
                    }
                },

                setupCheckboxListeners() {
                    // Limpar listeners antigos clonando o tbody
                    const tbody = document.querySelector('tbody');
                    if (!tbody) return;

                    // Clone sem listeners
                    const newTbody = tbody.cloneNode(true);
                    tbody.parentNode.replaceChild(newTbody, tbody);

                    // Agora adicionar listeners ao novo tbody
                    const newtbody = document.querySelector('tbody');
                    const self = this;

                    newtbody.addEventListener('change', (event) => {
                        if (event.target.classList.contains('checkbox-local')) {
                            const localId = parseInt(event.target.getAttribute('data-local-id'));
                            if (event.target.checked) {
                                if (!self.selecionados.includes(localId)) {
                                    self.selecionados.push(localId);
                                }
                            } else {
                                self.selecionados = self.selecionados.filter(s => s !== localId);
                            }
                        }
                    });

                    // Escuta cliques nos botões de deleção individual
                    const deleteButtons = newtbody.querySelectorAll('.delete-btn');
                    deleteButtons.forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            const localId = parseInt(btn.getAttribute('data-local-id'));
                            const localName = btn.getAttribute('data-local-name');
                            console.log('Abrindo modal para deletar:', localId, localName);
                            self.abrirModalDelecaoIndividual(localId, localName);
                        });
                    });
                }
            }
        }
    </script>
    <script>
        // Inicializa os event listeners quando a página carrega
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                const app = document.querySelector('[x-data]');
                if (app && app._x_dataStack) {
                    const data = app._x_dataStack[0];
                    if (data && data.setupCheckboxListeners) {
                        data.setupCheckboxListeners();
                    }
                }
            }, 100);
        });

        // Re-inicializa listeners após fetch/AJAX
        function reinitializeCheckboxListeners() {
            const app = document.querySelector('[x-data]');
            if (app && app._x_dataStack) {
                const data = app._x_dataStack[0];
                if (data && data.setupCheckboxListeners) {
                    data.setupCheckboxListeners();
                }
            }
        }
    </script>
    @endpush
</x-app-layout>