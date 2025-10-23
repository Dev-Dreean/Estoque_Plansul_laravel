<x-app-layout>
    <div class="py-12"
        data-search-container
        x-data="searchTagFilterProjetos('{{ route('projetos.index') }}')"
        @init="init()"> {{-- Inicializa as tags ao carregar --}}
        <div class="w-full sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-m sm:rounded-lg">
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
                    {{-- 3. Área da tabela que será atualizada dinamicamente --}}
                    <div id="table-container" 
                        x-data="tableMultiSelect()"
                        x-html="tableHtml"
                        @table:update="Object.assign($data, $event.detail)">
                        {{-- O conteúdo inicial da tabela é carregado aqui --}}
                        @include('projetos._table_partial', ['locais' => $locais, 'sortField' => '', 'sortDir' => 'asc'])
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    {{-- 4. Lógica Javascript do Alpine.js --}}
    <script>
        function searchTagFilterProjetos(baseUrl) {
            return {
                inputValue: '',
                tags: [],
                tableHtml: document.getElementById('table-container').innerHTML,
                ordenacao: {
                    campo: null,
                    direcao: 'asc'  // 'asc' ou 'desc'
                },
                init() {
                    // Restaura as tags da URL ao carregar a página
                    const params = new URLSearchParams(window.location.search);
                    const searchParam = params.get('search');
                    if (searchParam) {
                        this.tags = searchParam.split(',').filter(tag => tag.trim().length > 0);
                    }
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
                ordenarPor(campo) {
                    // Se clicou no mesmo campo, alterna a direção
                    if (this.ordenacao.campo === campo) {
                        this.ordenacao.direcao = this.ordenacao.direcao === 'asc' ? 'desc' : 'asc';
                    } else {
                        // Se é novo campo, começa em ascendente
                        this.ordenacao.campo = campo;
                        this.ordenacao.direcao = 'asc';
                    }
                    this.search();
                },
                search() {
                    // Monta a URL com os parâmetros de busca (tags + input)
                    let params = [];
                    if (this.inputValue.trim().length > 0) {
                        params = [...this.tags, this.inputValue.trim()];
                    } else {
                        params = [...this.tags];
                    }
                    let url = `${baseUrl}?search=${encodeURIComponent(params.join(','))}`;
                    
                    // Adiciona parâmetros de ordenação
                    if (this.ordenacao.campo) {
                        url += `&sort=${this.ordenacao.campo}&direction=${this.ordenacao.direcao}`;
                    }
                    
                    fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        })
                        .then(response => response.text())
                        .then(html => {
                            this.tableHtml = html;
                        })
                        .catch(error => console.error('Erro ao buscar os dados:', error));
                }
            }
        }

        // Função Alpine para multi-select
        function tableMultiSelect() {
            return {
                selecionados: [],
                mostraModalDelecao: false,
                carregando: false,
                toast: {
                    show: false,
                    mensagem: '',
                    tipo: 'sucesso' // 'sucesso' ou 'erro'
                },

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
                        // Seleciona todos os locais visíveis na página
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
                    }
                },

                limparSelecao() {
                    this.selecionados = [];
                    // Desmarca o checkbox do header
                    const headerCheckbox = document.querySelector('thead input[type="checkbox"]');
                    if (headerCheckbox) {
                        headerCheckbox.checked = false;
                    }
                },

                irParaEdicao(url) {
                    window.location.href = url;
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

                            // Mostra toast de sucesso
                            this.mostrarToast(`✓ ${quantidade} local(is) removido(s) com sucesso!`, 'sucesso', 3000);

                            // Recarrega a página após 1.5 segundos
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
                }
            }
        }
    </script>
    @endpush
</x-app-layout>