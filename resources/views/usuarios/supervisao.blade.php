<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
                    👥 Gerenciar Supervisão
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ $usuario->NOMEUSER }} ({{ $usuario->NMLOGIN }})
                </p>
            </div>
            <a href="{{ route('usuarios.edit', $usuario) }}" class="inline-flex items-center px-4 py-2 bg-plansul-blue hover:bg-plansul-blue/90 text-white rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-plansul-blue focus:ring-offset-2 transition ease-in-out duration-150">
                ← Voltar
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Painel de Informações -->
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">ℹ️ Informações</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Nome</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $usuario->NOMEUSER }}</p>
                            </div>
                            
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Login</p>
                                <p class="text-sm font-mono font-medium text-gray-900 dark:text-gray-100">{{ $usuario->NMLOGIN }}</p>
                            </div>
                            
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Perfil</p>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $usuario->PERFIL === 'ADM' ? 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300' }}">
                                    {{ $usuario->PERFIL === 'ADM' ? '🔐 Administrador' : '👤 Usuário Padrão' }}
                                </span>
                            </div>

                            <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Supervisionando</p>
                                <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ count($supervisionados) }}
                                </p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">usuários USR</p>
                            </div>
                        </div>

                        <div class="mt-6 p-3 rounded-md bg-plansul-blue/10 dark:bg-plansul-blue/20 border border-plansul-blue/20 dark:border-plansul-blue/30">
                            <p class="text-xs text-plansul-blue">
                                <strong>💡 Como funciona:</strong> Um supervisor vê todos os patrimônios que seus supervisionados lançaram, sem precisar de acesso de administrador.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Painel de Seleção -->
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">👥 Selecionar Supervisionados</h3>
                        
                            <div x-data="supervisorForm($el)" class="space-y-4"
                                data-selecionados='{!! json_encode($supervisionados) !!}'
                                data-usuarios='{!! json_encode($usuariosDisponiveis) !!}'>
                            <!-- Campo de Busca -->
                            <div>
                                <input 
                                    type="text"
                                    x-model="searchTerm"
                                    placeholder="Buscar por nome ou login..."
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                />
                            </div>

                            <!-- Atalhos -->
                            <div class="flex gap-2 flex-wrap">
                                <button type="button" @click="marcarTodos()" class="inline-flex items-center px-3 py-1 bg-plansul-blue text-white text-xs rounded-md hover:bg-plansul-blue/90 transition">
                                    ✓ Marcar Todos
                                </button>
                                <button type="button" @click="desmarcarTodos()" class="inline-flex items-center px-3 py-1 bg-gray-500 text-white text-xs rounded-md hover:bg-gray-600 transition">
                                    ✗ Desmarcar Todos
                                </button>
                                <button type="button" @click="inverterSeleção()" class="inline-flex items-center px-3 py-1 bg-amber-600 text-white text-xs rounded-md hover:bg-amber-700 transition">
                                    ⇄ Inverter
                                </button>
                            </div>

                            <!-- Lista de Usuários -->
                            <div class="border border-gray-300 dark:border-gray-700 rounded-lg divide-y divide-gray-200 dark:divide-gray-700 max-h-96 overflow-y-auto">
                                <template x-for="user in filteredUsuarios" :key="user.NMLOGIN">
                                    <label class="flex items-center gap-3 p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition">
                                        <input 
                                            type="checkbox"
                                            :value="user.NMLOGIN"
                                            x-model="selecionados"
                                            class="w-4 h-4 text-indigo-600 rounded border-gray-300 dark:border-gray-700 focus:ring-indigo-500"
                                        />
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate" x-text="user.NOMEUSER"></p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 font-mono" x-text="user.NMLOGIN"></p>
                                        </div>
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                            <span x-text="`${user.contagem || 0} patrimônios`"></span>
                                        </span>
                                    </label>
                                </template>

                                <div x-show="filteredUsuarios.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">
                                    <p class="text-sm">Nenhum usuário encontrado</p>
                                </div>
                            </div>

                            <!-- Resumo de Seleção -->
                            <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700/30 border border-gray-200 dark:border-gray-700">
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    <strong x-text="selecionados.length"></strong> usuário(s) selecionado(s)
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    de <strong x-text="usuariosDisponiveis.length"></strong> total
                                </p>
                            </div>

                            <!-- Botão Salvar -->
                            <button 
                                type="button"
                                @click="salvar()"
                                :disabled="salvando"
                                class="w-full inline-flex items-center justify-center px-4 py-2 bg-plansul-blue text-white rounded-md font-semibold text-sm hover:bg-plansul-blue/90 focus:outline-none focus:ring-2 focus:ring-plansul-blue focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition ease-in-out duration-150"
                            >
                                <span x-show="!salvando">💾 Salvar Alterações</span>
                                <span x-show="salvando" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Salvando...
                                </span>
                            </button>

                            <!-- Mensagem de Status -->
                            <div x-show="mensagem" :class="`p-3 rounded-lg text-sm transition ${statusSucesso ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 border border-green-300 dark:border-green-800' : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 border border-red-300 dark:border-red-800'}`">
                                <p x-text="mensagem"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista Atual de Supervisionados -->
            @if (count($supervisionados) > 0)
            <div class="mt-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">✓ Supervisionando Atualmente</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($usuariosDisponiveis as $u)
                        @if (in_array($u->NMLOGIN, $supervisionados))
                        <div class="flex items-start gap-3 p-4 rounded-lg border-l-4 border-plansul-blue bg-plansul-blue/10 dark:bg-plansul-blue/20">
                            <span class="text-xl">✓</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $u->NOMEUSER }}
                                </p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 font-mono">
                                    {{ $u->NMLOGIN }}
                                </p>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
            @else
            <div class="mt-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 text-center">
                <p class="text-gray-600 dark:text-gray-400">
                    Nenhum usuário sendo supervisionado no momento.
                </p>
            </div>
            @endif
        </div>
    </div>

    <script>
        function supervisorForm(elOrConfig) {
            const ds = (elOrConfig && elOrConfig.dataset) ? elOrConfig.dataset : {};
            let inicialSelecionados = [];
            let inicialUsuarios = [];
            try { inicialSelecionados = ds.selecionados ? JSON.parse(ds.selecionados) : []; } catch (e) { inicialSelecionados = []; }
            try { inicialUsuarios = ds.usuarios ? JSON.parse(ds.usuarios) : []; } catch (e) { inicialUsuarios = []; }

            return {
                searchTerm: '',
                selecionados: inicialSelecionados,
                usuariosDisponiveis: inicialUsuarios,
                salvando: false,
                mensagem: '',
                statusSucesso: false,

                get filteredUsuarios() {
                    if (!this.searchTerm) return this.usuariosDisponiveis;
                    
                    const term = this.searchTerm.toLowerCase();
                    return this.usuariosDisponiveis.filter(u => 
                        u.NOMEUSER.toLowerCase().includes(term) || 
                        u.NMLOGIN.toLowerCase().includes(term)
                    );
                },

                marcarTodos() {
                    this.selecionados = this.filteredUsuarios.map(u => u.NMLOGIN);
                },

                desmarcarTodos() {
                    this.selecionados = [];
                },

                inverterSeleção() {
                    const logins = this.filteredUsuarios.map(u => u.NMLOGIN);
                    this.selecionados = this.selecionados.filter(s => !logins.includes(s))
                        .concat(logins.filter(l => !this.selecionados.includes(l)));
                },

                async salvar() {
                    this.salvando = true;
                    this.mensagem = '';

                    try {
                        const response = await fetch('{{ route("usuarios.supervisao.update", $usuario) }}', {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                supervisionados: this.selecionados
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.statusSucesso = true;
                            this.mensagem = data.message;
                            setTimeout(() => {
                                this.mensagem = '';
                            }, 4000);
                        } else {
                            this.statusSucesso = false;
                            this.mensagem = data.message || 'Erro ao salvar alterações';
                        }
                    } catch (error) {
                        this.statusSucesso = false;
                        this.mensagem = 'Erro ao processar requisição: ' + error.message;
                    } finally {
                        this.salvando = false;
                    }
                }
            };
        }
    </script>
</x-app-layout>
