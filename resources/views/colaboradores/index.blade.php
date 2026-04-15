<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
                    Gestão de Colaboradores
                </h2>
                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
                    <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-700 dark:text-gray-300">
                        <svg class="h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ number_format($total, 0, ',', '.') }} colaboradores
                    </span>
                    @if($ultimaSincronizacaoFormatada)
                        <span class="text-gray-400 dark:text-gray-600 hidden sm:inline">&bull;</span>
                        <span class="inline-flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                            <svg class="h-3.5 w-3.5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M4 9a9 9 0 0114.93-4.93M20 15a9 9 0 01-14.93 4.93"/></svg>
                            Última sincronização: <strong class="text-gray-700 dark:text-gray-300">{{ $ultimaSincronizacaoFormatada }}</strong>
                        </span>
                    @else
                        <span class="text-xs text-amber-500 dark:text-amber-400">⚠ Nenhuma sincronização registrada</span>
                    @endif
                </div>
            </div>
        </div>
    </x-slot>

    <div
        x-data="gestaoColaboradores()"
        x-init="init()"
        class="py-12"
    >

        {{-- Toast (fixo) --}}
        <div
            x-show="toast.show"
            :class="toast.tipo === 'sucesso' ? 'bg-green-500' : 'bg-red-500'"
            x-transition:enter="transition ease-out duration-300"
            x-transition:leave="transition ease-in duration-200"
            class="fixed top-4 right-4 z-[90] text-white px-6 py-3 rounded-lg shadow-lg max-w-sm"
            style="display:none;">
            <span x-text="toast.mensagem"></span>
        </div>

        <div class="w-full sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                @if(session('success'))
                    <div class="mb-4 rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 px-4 py-3 text-sm text-green-700 dark:text-green-300 flex items-center gap-2">
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        {{ session('success') }}
                    </div>
                @endif

                {{-- Header: busca dinâmica + botões --}}
                <div class="flex justify-between items-start mb-4">
                    {{-- Busca dinâmica sem ícone --}}
                    <div class="relative">
                        <input
                            x-model="buscaDinamica"
                            @input.debounce.500ms="buscarColaboradores()"
                            type="text"
                            placeholder="Buscar por nome ou matrícula…"
                            class="h-10 w-72 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 text-sm text-gray-900 dark:text-gray-100 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                        />
                    </div>

                    {{-- Botões com subtítulo interno --}}
                    <div class="flex items-start gap-2">
                        <button
                            @click="sincronizar()"
                            :disabled="sincronizando"
                            class="rounded inline-flex flex-col items-center justify-center px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-700 dark:hover:bg-indigo-600 text-white transition disabled:opacity-60 disabled:cursor-not-allowed min-w-[140px]"
                        >
                            <span class="flex items-center gap-1.5 font-bold text-sm leading-snug">
                                <svg x-show="!sincronizando" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M4 9a9 9 0 0114.93-4.93M20 15a9 9 0 01-14.93 4.93"/></svg>
                                <svg x-show="sincronizando" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                <span x-text="sincronizando ? 'Sincronizando…' : 'Sincronizar'"></span>
                            </span>
                            <span class="text-[11px] text-indigo-200 leading-tight">Atualizar colaboradores</span>
                        </button>

                        <button
                            @click="mostraModalAdicionar = true"
                            class="rounded inline-flex flex-col items-center justify-center px-4 py-1.5 bg-plansul-blue hover:bg-opacity-90 text-white transition min-w-[140px]"
                        >
                            <span class="flex items-center gap-1.5 font-bold text-sm leading-snug">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                Adicionar
                            </span>
                            <span class="text-[11px] text-blue-100 leading-tight">Cadastro manual</span>
                        </button>
                    </div>
                </div>

                {{-- Tabela com overlay de sync/busca --}}
                <div class="relative overflow-x-auto shadow-md sm:rounded-lg min-h-[200px]">
                    {{-- Loading overlay dentro do grid --}}
                    <div x-show="sincronizando || buscaCarregando" x-cloak
                        class="absolute inset-0 bg-white/85 dark:bg-gray-800/90 z-20 flex flex-col items-center justify-start pt-6 rounded-lg"
                        style="display:none;">
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2"
                            x-text="sincronizando ? 'Sincronizando colaboradores…' : 'Buscando colaboradores…'"></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4"
                            x-text="sincronizando ? 'Importando dados do KingHost, aguarde.' : 'Pesquisando na base de dados…'"></p>
                        <svg class="h-7 w-7 animate-spin text-indigo-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                    </div>
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-4 py-2">Matrícula</th>
                                <th scope="col" class="px-4 py-2">Nome</th>
                                <th scope="col" class="px-4 py-2 hidden sm:table-cell">Cargo</th>
                                <th scope="col" class="px-4 py-2">Acesso</th>
                                <th scope="col" class="px-4 py-2">Login</th>
                                <th scope="col" class="px-4 py-2 hidden md:table-cell">Sincronizado</th>
                                <th scope="col" class="px-4 py-2">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="colaboradores-tbody">
                            @include('colaboradores._table_rows', ['colaboradores' => $colaboradores])
                        </tbody>
                    </table>
                </div>

                {{-- Paginação --}}
                <div class="mt-4">
                    {{ $colaboradores->links() }}
                </div>

                </div>{{-- /p-6 --}}
            </div>{{-- /card --}}
        </div>{{-- /w-full --}}

        {{-- ── MODAL: ADICIONAR COLABORADOR ── --}}
        <div x-show="mostraModalAdicionar" x-transition
            class="fixed inset-0 bg-black/60 dark:bg-black/75 z-50 flex items-center justify-center"
            @click.self="fecharModalAdicionar()"
            style="display:none;">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md mx-4" @click.stop>
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Adicionar Colaborador</h3>
                    <button @click="fecharModalAdicionar()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Matrícula <span class="text-red-500">*</span></label>
                        <input x-model="novoMatricula" @input.debounce.600ms="verificarMatricula()" type="text" autocomplete="off" placeholder="Ex: 12345"
                            class="block w-full h-9 text-sm rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 px-3 shadow-sm focus:ring-2 focus:ring-indigo-500 uppercase" />
                        <div class="mt-1 min-h-[18px]">
                            <p x-show="verificando" class="text-xs text-gray-500 flex items-center gap-1"><svg class="h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Verificando…</p>
                            <p x-show="jaExiste && !verificando" class="text-xs text-red-600 dark:text-red-400 flex items-center gap-1"><svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg> Colaborador já cadastrado</p>
                            <p x-show="matriculaDisponivel && !verificando && novoMatricula" class="text-xs text-green-600 dark:text-green-400 flex items-center gap-1"><svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Matrícula disponível</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Nome Completo <span class="text-red-500">*</span></label>
                        <input x-model="novoNome" :readonly="jaExiste" type="text" placeholder="Ex: MARIA DA SILVA"
                            :class="jaExiste ? 'opacity-60 cursor-not-allowed' : ''"
                            class="block w-full h-9 text-sm rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 px-3 shadow-sm focus:ring-2 focus:ring-indigo-500 uppercase" />
                    </div>
                </div>
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <button @click="fecharModalAdicionar()" class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded transition">Cancelar</button>
                    <button @click="adicionarColaborador()" :disabled="jaExiste || !novoMatricula || !novoNome || adicionando"
                        class="px-4 py-2 text-sm bg-plansul-blue hover:bg-opacity-90 text-white rounded font-semibold flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed transition">
                        <svg x-show="adicionando" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Adicionar Colaborador
                    </button>
                </div>
            </div>
        </div>

        {{-- ── MODAL: CRIAR LOGIN / EDITAR PERMISSÕES ── --}}
        <div x-show="mostraModalPermissoes" x-transition
            class="fixed inset-0 bg-black/60 dark:bg-black/75 z-50 flex items-center justify-center"
            @click.self="mostraModalPermissoes = false; loginCriadoExibir = null"
            style="display:none;">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto" @click.stop>
                {{-- Header --}}
                <div class="flex items-start justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 z-10">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white"
                            x-text="loginCriadoExibir ? 'Login Criado!' : (modalPermissoes.temLogin ? 'Editar Permissões' : 'Criar Login')"></h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5" x-text="modalPermissoes.nome"></p>
                    </div>
                    <button @click="mostraModalPermissoes = false; loginCriadoExibir = null; buscarColaboradores()"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 mt-0.5">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Credenciais criadas --}}
                <template x-if="loginCriadoExibir">
                    <div class="px-6 py-5">
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 space-y-3 mb-4">
                            <p class="text-sm font-semibold text-green-700 dark:text-green-300">✓ Anote as credenciais e repasse ao colaborador:</p>
                            <div class="flex items-center gap-3 bg-white dark:bg-gray-900 border border-green-100 dark:border-green-900 rounded px-3 py-2">
                                <span class="text-xs text-gray-500 w-12 shrink-0">Login:</span>
                                <code class="font-mono text-sm text-gray-900 dark:text-gray-100 flex-1" x-text="loginCriadoExibir.login"></code>
                                <button @click="copiar(loginCriadoExibir.login)" class="text-xs text-indigo-600 hover:underline shrink-0">Copiar</button>
                            </div>
                            <div class="flex items-center gap-3 bg-white dark:bg-gray-900 border border-green-100 dark:border-green-900 rounded px-3 py-2">
                                <span class="text-xs text-gray-500 w-12 shrink-0">Senha:</span>
                                <code class="font-mono text-sm text-gray-900 dark:text-gray-100 flex-1" x-text="loginCriadoExibir.senha"></code>
                                <button @click="copiar(loginCriadoExibir.senha)" class="text-xs text-indigo-600 hover:underline shrink-0">Copiar</button>
                            </div>
                            <p class="text-xs text-amber-600 dark:text-amber-400">O colaborador precisará alterar a senha no primeiro acesso.</p>
                        </div>
                        <div class="flex justify-end">
                            <button @click="mostraModalPermissoes = false; loginCriadoExibir = null; buscarColaboradores()"
                                class="px-4 py-2 text-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded font-semibold transition">Fechar</button>
                        </div>
                    </div>
                </template>

                {{-- Seleção de preset --}}
                <template x-if="!loginCriadoExibir">
                    <div class="px-6 py-5">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                            <span x-text="modalPermissoes.temLogin ? 'Altere o perfil de acesso de' : 'Selecione um perfil de acesso para'"></span>
                            <strong class="text-gray-900 dark:text-gray-100" x-text="' ' + modalPermissoes.nome"></strong>:
                        </p>
                        <template x-if="modalPermissoes.temLogin && modalPermissoes.loginAtual">
                            <p class="text-xs text-gray-400 mb-3">Login atual: <code class="font-mono" x-text="modalPermissoes.loginAtual"></code></p>
                        </template>

                        {{-- Cards de preset — visual com chips --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3 mb-5">
                            <template x-for="preset in presets" :key="preset.key">
                                <div @click="presetSelecionado = preset.key"
                                    :class="presetSelecionado === preset.key
                                        ? 'ring-2 ring-indigo-500 border-indigo-300 dark:border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20'
                                        : 'border-gray-200 dark:border-gray-600 hover:border-indigo-300 dark:hover:border-indigo-500 bg-white dark:bg-gray-700/30'"
                                    class="cursor-pointer rounded-xl border-2 p-4 transition select-none">
                                    {{-- Cabeçalho do card --}}
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center gap-2">
                                            <span class="text-2xl" x-text="preset.emoji"></span>
                                            <p class="font-bold text-sm text-gray-900 dark:text-gray-100" x-text="preset.nome"></p>
                                        </div>
                                        <span class="text-xs px-2 py-0.5 rounded-full font-medium shrink-0" :class="preset.cor_nivel" x-text="preset.nivel"></span>
                                    </div>
                                    {{-- Descrição curta --}}
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3 leading-snug" x-text="preset.descricao"></p>
                                    {{-- Chips de capacidade --}}
                                    <div class="flex flex-wrap gap-1.5">
                                        <template x-for="chip in preset.chips" :key="chip.label">
                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full" :class="chip.cor" x-text="chip.label"></span>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="flex justify-end gap-3 border-t border-gray-200 dark:border-gray-700 pt-4">
                            <button @click="mostraModalPermissoes = false"
                                class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded transition">Cancelar</button>
                            <button @click="salvarPermissoes()" :disabled="!presetSelecionado || salvandoPermissoes"
                                class="px-4 py-2 text-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded font-semibold flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed transition">
                                <svg x-show="salvandoPermissoes" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                <span x-text="modalPermissoes.temLogin ? 'Salvar Permissões' : 'Criar Login'"></span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- ── MODAL: REMOVER ACESSO ── --}}
        <div x-show="mostraModalRemocao" x-transition
            class="fixed inset-0 bg-black/60 dark:bg-black/75 z-50 flex items-center justify-center"
            @click.self="mostraModalRemocao = false"
            style="display:none;">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 max-w-sm mx-4" @click.stop>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-2">Remover Acesso ao Sistema</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">Tem certeza que deseja remover o acesso de <strong x-text="usuarioParaRemover.nome"></strong>?</p>
                <p class="text-xs text-amber-600 dark:text-amber-400 mb-5">O cadastro como colaborador será mantido. Apenas o login será removido.</p>
                <div class="flex gap-3 justify-end">
                    <button @click="mostraModalRemocao = false" :disabled="removendo"
                        class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded transition disabled:opacity-50">Cancelar</button>
                    <button @click="confirmarRemocao()" :disabled="removendo"
                        class="px-4 py-2 text-sm bg-red-600 hover:bg-red-700 text-white rounded font-semibold flex items-center gap-2 disabled:opacity-50 transition">
                        <svg x-show="removendo" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Remover Acesso
                    </button>
                </div>
            </div>
        </div>

        {{-- Modal de loading sincronização removido: overlay agora fica dentro do grid --}}

    </div>{{-- /x-data --}}

    @push('scripts')
    <script>
    function gestaoColaboradores() {
        return {
            // Busca dinâmica
            buscaDinamica: '',
            buscaCarregando: false,

            // Sync
            sincronizando: false,

            // Modal: Adicionar
            mostraModalAdicionar: false,
            novoMatricula: '',
            novoNome: '',
            verificando: false,
            jaExiste: false,
            matriculaDisponivel: false,
            adicionando: false,

            // Modal: Permissões / Criar Login
            mostraModalPermissoes: false,
            modalPermissoes: { matricula: '', nome: '', temLogin: false, usuarioId: null, loginAtual: '' },
            presetSelecionado: '',
            salvandoPermissoes: false,
            loginCriadoExibir: null,
            presets: @json($presets),

            // Modal: Remover
            mostraModalRemocao: false,
            usuarioParaRemover: { id: null, nome: '' },
            removendo: false,

            // Toast
            toast: { show: false, mensagem: '', tipo: 'sucesso' },

            init() {
                window.gestaoInstance = this;
                @if(session('success'))
                    this.mostrarToast('{{ addslashes(session('success')) }}', 'sucesso');
                @endif
                @if(session('error'))
                    this.mostrarToast('{{ addslashes(session('error')) }}', 'erro');
                @endif
            },

            // ── Busca dinâmica ──────────────────────────────
            async buscarColaboradores() {
                // Não dispara busca com menos de 2 caracteres (exceto vazio = limpar filtro)
                const termo = this.buscaDinamica.trim();
                if (termo.length > 0 && termo.length < 2) return;
                this.buscaCarregando = true;
                try {
                    const params = new URLSearchParams();
                    if (this.buscaDinamica.trim()) params.set('busca', this.buscaDinamica.trim());
                    params.set('api', '1');
                    const resp = await fetch(`{{ route('colaboradores.index') }}?${params}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await resp.json();
                    const tbody = document.getElementById('colaboradores-tbody');
                    if (tbody) tbody.innerHTML = data.html ?? '';
                } catch (e) { console.error('Erro busca:', e); }
                finally { this.buscaCarregando = false; }
            },

            // ── Modal: Adicionar ────────────────────────────
            fecharModalAdicionar() {
                this.mostraModalAdicionar = false;
                this.novoMatricula = '';
                this.novoNome = '';
                this.jaExiste = false;
                this.matriculaDisponivel = false;
            },

            async verificarMatricula() {
                const mat = this.novoMatricula.trim().toUpperCase();
                if (!mat) { this.jaExiste = false; this.matriculaDisponivel = false; this.novoNome = ''; return; }
                this.verificando = true; this.jaExiste = false; this.matriculaDisponivel = false;
                try {
                    const resp = await fetch('{{ route('api.funcionarios.verificarMatricula') }}?matricula=' + encodeURIComponent(mat), {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await resp.json();
                    if (data.existe) { this.jaExiste = true; this.novoNome = data.funcionario?.nome ?? ''; }
                    else { this.matriculaDisponivel = true; }
                } catch(e) { console.error(e); }
                finally { this.verificando = false; }
            },

            async adicionarColaborador() {
                if (this.jaExiste || !this.novoMatricula || !this.novoNome) return;
                this.adicionando = true;
                try {
                    const csrf = document.querySelector('meta[name="csrf-token"]').content;
                    const resp = await fetch('{{ route('colaboradores.store') }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({ CDMATRFUNCIONARIO: this.novoMatricula.toUpperCase(), NMFUNCIONARIO: this.novoNome.toUpperCase() }),
                    });
                    const data = await resp.json();
                    if (resp.ok) {
                        this.fecharModalAdicionar();
                        this.mostrarToast('Colaborador adicionado com sucesso!', 'sucesso');
                        await this.buscarColaboradores();
                    } else {
                        this.mostrarToast(data.message ?? 'Erro ao adicionar colaborador.', 'erro');
                    }
                } catch(e) { this.mostrarToast('Erro: ' + e.message, 'erro'); }
                finally { this.adicionando = false; }
            },

            // ── Modal: Permissões ───────────────────────────
            abrirModalPermissoes(matricula, nome, temLogin, usuarioId, loginAtual) {
                this.modalPermissoes = { matricula, nome, temLogin, usuarioId: usuarioId || null, loginAtual: loginAtual || '' };
                this.presetSelecionado = '';
                this.loginCriadoExibir = null;
                this.salvandoPermissoes = false;
                this.mostraModalPermissoes = true;
            },

            async salvarPermissoes() {
                if (!this.presetSelecionado) return;
                this.salvandoPermissoes = true;
                try {
                    const csrf = document.querySelector('meta[name="csrf-token"]').content;
                    if (!this.modalPermissoes.temLogin) {
                        const resp = await fetch('{{ route('colaboradores.criarLogin') }}', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify({ CDMATRFUNCIONARIO: this.modalPermissoes.matricula, preset: this.presetSelecionado }),
                        });
                        const data = await resp.json();
                        if (resp.ok) {
                            this.loginCriadoExibir = { login: data.login, senha: data.senha };
                        } else {
                            this.mostrarToast(data.message ?? 'Erro ao criar login.', 'erro');
                            this.mostraModalPermissoes = false;
                        }
                    } else {
                        const resp = await fetch(`/colaboradores/${this.modalPermissoes.matricula}/permissoes`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify({ preset: this.presetSelecionado }),
                        });
                        const data = await resp.json();
                        if (resp.ok) {
                            this.mostraModalPermissoes = false;
                            this.mostrarToast('Permissões atualizadas com sucesso!', 'sucesso');
                            await this.buscarColaboradores();
                        } else {
                            this.mostrarToast(data.message ?? 'Erro ao atualizar permissões.', 'erro');
                        }
                    }
                } catch(e) { this.mostrarToast('Erro: ' + e.message, 'erro'); }
                finally { this.salvandoPermissoes = false; }
            },

            // ── Sync ────────────────────────────────────────
            async sincronizar() {
                if (this.sincronizando) return;
                this.sincronizando = true;
                try {
                    const csrf = document.querySelector('meta[name="csrf-token"]').content;
                    const resp = await fetch('{{ route('colaboradores.sincronizar') }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/json' },
                    });
                    if (!resp.ok) { const j = await resp.json().catch(()=>({})); throw new Error(j.mensagem ?? 'Erro ao sincronizar.'); }
                    const blob = await resp.blob();
                    const disposition = resp.headers.get('Content-Disposition') ?? '';
                    let fileName = 'sync_colaboradores.xlsx';
                    const match = disposition.match(/filename[^;=\n]*=(['"]?)([^'";\n]+)\1/);
                    if (match) fileName = match[2];
                    const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = fileName; a.click(); URL.revokeObjectURL(a.href);
                    this.mostrarToast('Sincronização concluída! Relatório baixado.', 'sucesso');
                    setTimeout(() => window.location.reload(), 2000);
                } catch(e) { this.mostrarToast(e.message ?? 'Erro na sincronização.', 'erro'); }
                finally { this.sincronizando = false; }
            },

            // ── Remover ─────────────────────────────────────
            abrirModalRemocao(usuarioId, usuarioNome) {
                this.usuarioParaRemover = { id: usuarioId, nome: usuarioNome };
                this.mostraModalRemocao = true;
            },

            async confirmarRemocao() {
                if (!this.usuarioParaRemover.id) return;
                this.removendo = true;
                try {
                    const csrf = document.querySelector('meta[name="csrf-token"]').content;
                    const resp = await fetch(`/colaboradores/login/${this.usuarioParaRemover.id}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    });
                    const data = await resp.json();
                    if (resp.ok) {
                        this.mostraModalRemocao = false;
                        this.mostrarToast('Acesso removido com sucesso!', 'sucesso');
                        await this.buscarColaboradores();
                    } else { this.mostrarToast(data.message ?? 'Erro ao remover acesso.', 'erro'); }
                } catch(e) { this.mostrarToast('Erro: ' + e.message, 'erro'); }
                finally { this.removendo = false; }
            },

            // ── Utilitários ─────────────────────────────────
            copiar(texto) {
                navigator.clipboard.writeText(texto).then(() => this.mostrarToast('Copiado!', 'sucesso', 1500));
            },

            mostrarToast(mensagem, tipo = 'sucesso', duracao = 4000) {
                this.toast = { show: true, mensagem, tipo };
                if (duracao > 0) setTimeout(() => { this.toast.show = false; }, duracao);
            },
        };
    }
    </script>
    @endpush

</x-app-layout>
