<x-app-layout>
    <div class="py-12" x-data="mergeProjetos('{{ route('projetos.index') }}')" @init="init()">
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
                    {{-- 3. Área da tabela que será atualizada dinamicamente --}}
                    <div id="table-container" x-html="tableHtml">
                        {{-- O conteúdo inicial da tabela é carregado aqui --}}
                        @include('projetos._table_partial', ['locais' => $locais])
                    </div>
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
                tableHtml: document.getElementById('table-container').innerHTML,
                
                // Dados do Multi-Select
                selecionados: [],
                mostraModalDelecao: false,
                carregando: false,
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
                    const url = `${baseUrl}?search=${encodeURIComponent(params.join(','))}`;
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
                }
            }
        }
    </script>
    @endpush
</x-app-layout>