{{-- Seção de Supervisão no Formulário de Usuário --}}

@if($usuariosUsrDisponiveis->count() > 0)
    <div x-data="supervisorToggle($el)" x-init="$watch('ativoSupervisor', v => { if(!v) { selecionados = []; } })" class="space-y-4"
         data-ativo="{!! json_encode(!empty($usuario->supervisor_de ?? null)) !!}"
         data-selecionados='{!! json_encode($usuario->supervisor_de ?? []) !!}'
         data-usuarios='{!! json_encode($usuariosUsrDisponiveisArray ?? $usuariosUsrDisponiveis->toArray()) !!}'>
    <!-- Toggle de Ativar Supervisão -->
    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
        <div class="flex items-center justify-between">
            <div>
                <label class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">
                    👥 Gerenciar Supervisão
                </label>
                <p class="text-xs text-gray-600 dark:text-gray-400">
                    Este usuário pode supervisionar outros e ver seus patrimônios
                </p>
            </div>
            <button 
                type="button"
                @click="ativoSupervisor = !ativoSupervisor"
                :class="`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${ativoSupervisor ? 'bg-indigo-600' : 'bg-gray-300'}`"
            >
                <span 
                    :class="`${ativoSupervisor ? 'translate-x-6' : 'translate-x-1'} inline-block h-4 w-4 transform rounded-full bg-white transition-transform`"
                ></span>
            </button>
        </div>
    </div>

    <!-- Painel de Seleção (Escondido por padrão) -->
    <div x-show="ativoSupervisor" x-transition class="space-y-4 p-4 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        
        <!-- Informação -->
        <div class="text-sm text-indigo-800 dark:text-indigo-300">
            <p class="mb-2">
                <strong>ℹ️ Como funciona:</strong> Selecione quais usuários este supervisor poderá monitorar. Ele verá os patrimônios lançados por eles sem ter acesso de administrador.
            </p>
        </div>

        <!-- Barra de Busca -->
        <div>
            <input 
                type="text"
                x-model="searchTerm"
                placeholder="Buscar por nome ou login..."
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm focus:ring-2 focus:ring-plansul-blue focus:border-transparent text-sm"
            />
        </div>

        <!-- Atalhos Rápidos -->
        <div class="flex gap-2 flex-wrap">
            <button type="button" @click="marcarTodos()" class="inline-flex items-center px-3 py-1 bg-indigo-600 text-white text-xs rounded-md hover:bg-indigo-700 transition">
                ✓ Marcar Todos
            </button>
            <button type="button" @click="desmarcarTodos()" class="inline-flex items-center px-3 py-1 bg-gray-400 text-white text-xs rounded-md hover:bg-gray-500 transition">
                ✗ Desmarcar Todos
            </button>
            <button type="button" @click="inverterSeleção()" class="inline-flex items-center px-3 py-1 bg-amber-600 text-white text-xs rounded-md hover:bg-amber-700 transition">
                ⇄ Inverter
            </button>
        </div>

        <!-- Lista de Usuários USR -->
        <div class="border border-gray-300 dark:border-gray-700 rounded-lg divide-y divide-gray-200 dark:divide-gray-700 max-h-48 overflow-y-auto bg-white dark:bg-gray-800">
            <template x-for="user in filteredUsuarios" :key="user.NMLOGIN">
                <label class="flex items-center gap-3 p-3 hover:bg-gray-50 dark:hover:bg-gray-700/40 cursor-pointer transition">
                    <input 
                        type="checkbox"
                        :value="user.NMLOGIN"
                        x-model="selecionados"
                        class="w-4 h-4 text-indigo-600 rounded border-gray-300 dark:border-gray-700 focus:ring-indigo-500"
                    />
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate" x-text="user.NOMEUSER"></p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 font-mono" x-text="user.NMLOGIN"></p>
                    </div>
                </label>
            </template>

            <div x-show="filteredUsuarios.length === 0" class="p-4 text-center text-sm text-gray-500 dark:text-gray-400">
                Nenhum usuário encontrado
            </div>
        </div>

        <!-- Resumo -->
        <div class="flex items-center justify-between p-3 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-700 dark:text-gray-300">
                <strong x-text="selecionados.length"></strong> usuário(s) selecionado(s)
            </p>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                de <strong x-text="usuariosTotal"></strong> disponível(is)
            </p>
        </div>

        <!-- Inputs Hidden para Submissão como array -->
        <template x-for="s in selecionados" :key="s">
            <input type="hidden" :name="'supervisor_de[]'" :value="s" />
        </template>
        <!-- Garantir que enviemos a chave mesmo se nenhum selecionado (para permitir limpar no servidor) -->
        <template x-if="selecionados.length === 0">
            <input type="hidden" name="supervisor_de[]" value="" />
        </template>
    </div>
</div>

    <script>
    function supervisorToggle(el) {
        // read initial data from data- attributes to avoid blade directives inside script
        const ativo = el.dataset.ativo === '1' || el.dataset.ativo === 'true';
        const selecionadosInit = el.dataset.selecionados ? JSON.parse(el.dataset.selecionados) : [];
        const usuariosInit = el.dataset.usuarios ? JSON.parse(el.dataset.usuarios) : [];

        return {
            ativoSupervisor: ativo,
            searchTerm: '',
            selecionados: selecionadosInit,
            usuariosDisponiveis: usuariosInit,

            get usuariosTotal() {
                return this.usuariosDisponiveis.length;
            },

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
            }
        };
    }
</script>
@endif
