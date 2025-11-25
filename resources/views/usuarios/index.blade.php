<x-app-layout>
    <script>
        function searchTagFilterUsuarios(baseUrl) {
            return {
                tags: [],
                inputValue: '',
                mostraModalDelecao: false,
                usuarioParaDelecao: { id: null, nome: '' },
                carregando: false,
                toast: {
                    show: false,
                    mensagem: '',
                    tipo: 'sucesso'
                },

                init() {
                    // Aguarda o DOM estar pronto antes de anexar listeners
                    this.$nextTick(() => {
                        this.setupDeleteListeners();
                    });
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
                    if (!this.inputValue && this.tags.length) {
                        this.tags.pop();
                        this.search();
                    }
                },
                search() {
                    let params = new URLSearchParams();
                    let allTerms = [...this.tags];
                    if (this.inputValue.trim()) {
                        allTerms.push(this.inputValue.trim());
                    }
                    if (allTerms.length) {
                        allTerms.forEach(t => params.append('search[]', t));
                    }
                    const url = `${baseUrl}?${params.toString()}&api=1`;
                    fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        })
                        .then(response => response.json())
                        .then(data => {
                            const tbody = document.querySelector('tbody');
                            if (tbody) {
                                tbody.innerHTML = data.html || '<tr><td colspan="6" class="px-6 py-4 text-center">Nenhum usuário encontrado.</td></tr>';
                                this.setupDeleteListeners();
                            }
                        })
                        .catch(error => console.error('Erro ao buscar os dados:', error));
                },

                abrirModalDelecao(usuarioId, usuarioNome) {
                    this.usuarioParaDelecao = { id: usuarioId, nome: usuarioNome };
                    this.mostraModalDelecao = true;
                },

                async confirmarDelecaoUsuario() {
                    if (!this.usuarioParaDelecao.id) {
                        this.mostrarToast('Erro ao obter usuário para deleção', 'erro');
                        return;
                    }

                    this.carregando = true;

                    try {
                        const response = await fetch(`/usuarios/${this.usuarioParaDelecao.id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            }
                        });

                        const data = await response.json();

                        if (response.ok) {
                            this.carregando = false;
                            this.mostraModalDelecao = false;
                            this.mostrarToast(`✓ Usuário "${this.usuarioParaDelecao.nome}" removido com sucesso!`, 'sucesso', 3000);
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            this.carregando = false;
                            this.mostrarToast(data.message || 'Erro ao remover usuário', 'erro');
                        }
                    } catch (error) {
                        this.carregando = false;
                        console.error('Erro:', error);
                        this.mostrarToast('Erro ao remover usuário: ' + error.message, 'erro');
                    }
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

                setupDeleteListeners() {
                    const tbody = document.querySelector('tbody');
                    if (!tbody) {
                        console.log('❌ ERRO: tbody não encontrado!');
                        return;
                    }
                    
                    console.log('✅ tbody encontrado:', tbody);

                    const self = this;
                    
                    // Remove listener antigo se existir
                    if (tbody._deleteHandler) {
                        tbody.removeEventListener('click', tbody._deleteHandler);
                    }
                    
                    // Cria novo handler usando event delegation
                    tbody._deleteHandler = function(e) {
                        console.log('📍 Clique detectado em:', e.target);
                        const btn = e.target.closest('.delete-btn-usuario');
                        console.log('🔍 Botão encontrado:', btn);
                        if (!btn) {
                            console.log('❌ Botão com classe delete-btn-usuario não encontrado');
                            return;
                        }
                        
                        console.log('✅ Botão deletar clicado!');
                        e.preventDefault();
                        e.stopPropagation();
                        
                        const usuarioId = parseInt(btn.getAttribute('data-usuario-id'));
                        const usuarioNome = btn.getAttribute('data-usuario-nome');
                        console.log('📊 Dados:', { usuarioId, usuarioNome });
                        self.abrirModalDelecao(usuarioId, usuarioNome);
                    };
                    
                    tbody.addEventListener('click', tbody._deleteHandler);
                    console.log('✅ Listener anexado ao tbody');
                }
            }
        }
    </script>

    <div class="py-12" x-data="searchTagFilterUsuarios('{{ route('usuarios.index') }}')">
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
                        <a href="{{ route('usuarios.create') }}" class="bg-plansul-blue hover:bg-opacity-90 text-white font-bold py-2 px-4 rounded">
                            Criar Novo Usuário
                        </a>
                    </div>
                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="w-full text-base text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Nome</th>
                                    <th scope="col" class="px-6 py-3">Login</th>
                                    <th scope="col" class="px-6 py-3">Matrícula</th>
                                    <th scope="col" class="px-6 py-3">UF</th>
                                    <th scope="col" class="px-6 py-3">Perfil</th>
                                    <th scope="col" class="px-6 py-3">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @include('usuarios._table_rows_usuarios', ['usuarios' => $usuarios])
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $usuarios->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal de Confirmação --}}
        <div x-show="mostraModalDelecao" x-transition class="fixed inset-0 bg-black/50 dark:bg-black/70 z-50 flex items-center justify-center" @click.self="mostraModalDelecao = false" style="display: none;">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 max-w-sm mx-4" @click.stop>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Confirmar Remoção</h3>

                <p class="text-gray-600 dark:text-gray-300 mb-6">
                    Tem certeza que deseja remover o usuário "<strong x-text="usuarioParaDelecao.nome"></strong>"?
                </p>

                <div class="flex gap-3 justify-end">
                    <button
                        type="button"
                        @click="mostraModalDelecao = false"
                        :disabled="carregando"
                        class="px-4 py-2 text-sm bg-gray-300 hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 rounded transition disabled:opacity-50 disabled:cursor-not-allowed">
                        Cancelar
                    </button>
                    <button
                        type="button"
                        @click="confirmarDelecaoUsuario()"
                        :disabled="carregando"
                        class="px-4 py-2 text-sm bg-red-600 hover:bg-red-700 text-white rounded transition font-semibold flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <template x-if="!carregando">
                            <span>Remover</span>
                        </template>
                        <template x-if="carregando">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Removendo...</span>
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>