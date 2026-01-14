<x-app-layout>
    {{-- Mostrar header apenas se NÃO for modal --}}
    @unless(request('modal'))
        <x-slot name="header">
            <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Nova Solicitacao de Bens') }}
            </h2>
        </x-slot>
    @endunless

    @php
        $defaultNome = old('solicitante_nome', $user?->NOMEUSER ?? '');
        $defaultMatricula = old('solicitante_matricula', $user?->CDMATRFUNCIONARIO ?? '');
        $defaultUf = old('uf', $user?->UF ?? '');
        $oldItens = old('itens');
        if (!is_array($oldItens) || count($oldItens) === 0) {
            $oldItens = [
                ['descricao' => '', 'quantidade' => 1, 'unidade' => '', 'observacao' => ''],
            ];
        }
        $isModal = request('modal') === '1';
    @endphp

    {{-- Container ajustado para modal vs página normal --}}
    <div class="{{ $isModal ? 'h-full overflow-y-auto' : 'py-12' }}">
        <div class="{{ $isModal ? 'w-full' : 'max-w-4xl mx-auto sm:px-6 lg:px-8' }}">
            @if($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded" role="alert">
                    <span class="font-semibold">Erro:</span> {{ $errors->first() }}
                </div>
            @endif

            <div class="{{ $isModal ? 'w-full h-full' : 'bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg' }}">
                <div class="{{ $isModal ? 'p-4 sm:p-6' : 'p-6' }} text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route('solicitacoes-bens.store') }}" x-data="solicitacaoForm({ itensOld: @js($oldItens) })" data-modal-form="{{ $isModal ? '1' : '0' }}" x-cloak>
                        @csrf
                        @if($isModal)
                            <input type="hidden" name="modal" value="1" />
                        @endif
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <x-input-label for="solicitante_nome" value="Solicitante *" />
                                <x-text-input id="solicitante_nome" name="solicitante_nome" type="text" class="mt-1 block w-full" value="{{ $defaultNome }}" required />
                                <x-input-error :messages="$errors->get('solicitante_nome')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="solicitante_matricula" value="Matricula" />
                                <x-text-input id="solicitante_matricula" name="solicitante_matricula" type="text" class="mt-1 block w-full" value="{{ $defaultMatricula }}" />
                                <x-input-error :messages="$errors->get('solicitante_matricula')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="uf" value="UF" />
                                <x-text-input id="uf" name="uf" type="text" class="mt-1 block w-full uppercase" maxlength="2" value="{{ $defaultUf }}" />
                                <x-input-error :messages="$errors->get('uf')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="setor" value="Setor *" />
                                <x-text-input id="setor" name="setor" type="text" class="mt-1 block w-full" value="{{ old('setor') }}" required />
                                <x-input-error :messages="$errors->get('setor')" class="mt-2" />
                            </div>
                            <div class="md:col-span-2">
                                <x-input-label for="local_destino" value="Local destino *" />
                                <x-text-input id="local_destino" name="local_destino" type="text" class="mt-1 block w-full" value="{{ old('local_destino') }}" required />
                                <x-input-error :messages="$errors->get('local_destino')" class="mt-2" />
                            </div>
                            <div class="md:col-span-2">
                                <x-input-label for="observacao" value="Observacao" />
                                <textarea id="observacao" name="observacao" class="input-base mt-1 block w-full" rows="3">{{ old('observacao') }}</textarea>
                                <x-input-error :messages="$errors->get('observacao')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mt-8">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-lg font-semibold">Itens solicitados</h3>
                                <button type="button" class="text-sm text-indigo-600 hover:text-indigo-800" @click="addItem">Adicionar item</button>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                Selecione itens do estoque disponivel. Digite 2+ caracteres para filtrar (lista limitada a 50).
                            </p>

                            <template x-for="(item, idx) in itens" :key="item.key" x-cloak>
                                <div class="grid gap-3 md:grid-cols-6 items-end mb-4 border-b dark:border-gray-700 pb-4" x-show="item" x-data="patrimonioSearch(item)" @click.away="closeResults">
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Patrimonio / Descricao *</label>
                                        <div class="relative">
                                            <input
                                                type="text"
                                                class="input-base mt-1 block w-full"
                                                :name="`itens[${idx}][patrimonio_busca]`"
                                                x-model.trim="item.patrimonio_busca"
                                                @input.debounce.300ms="onInput"
                                                @focus="openResults"
                                                @keydown.escape.prevent="closeResults"
                                                placeholder="Digite numero ou descricao..."
                                                autocomplete="off"
                                            />
                                            <input type="hidden" :name="`itens[${idx}][descricao]`" :value="item?.descricao || ''" />
                                            <div
                                                x-cloak
                                                x-show="dropdownOpen"
                                                x-transition
                                                class="absolute z-20 mt-1 w-full rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-lg max-h-60 overflow-auto"
                                            >
                                                <div x-show="loading" class="px-3 py-2 text-xs text-gray-500">Buscando...</div>
                                                <template x-if="!loading && resultados.length === 0">
                                                    <div class="px-3 py-2 text-xs text-gray-500">Nenhum resultado.</div>
                                                </template>
                                                <template x-for="resultado in resultados" :key="resultado.id">
                                                    <button
                                                        type="button"
                                                        class="w-full text-left px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-800"
                                                        @click="selectResultado(resultado)"
                                                    >
                                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="resultado.text"></div>
                                                        <div class="text-xs text-gray-500" x-show="resultado.conferido">Conferido</div>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                        <div class="mt-1 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                            <span>Apenas itens disponiveis do estoque.</span>
                                            <span x-show="item.selecionado" class="inline-flex items-center rounded-full bg-green-100 text-green-700 px-2 py-0.5">Selecionado</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Quantidade *</label>
                                        <input type="number" min="1" class="input-base mt-1 block w-full" :name="`itens[${idx}][quantidade]`" :value="item?.quantidade || 1" @input="item.quantidade = parseInt($el.value) || 1" required />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Unidade</label>
                                        <input type="text" class="input-base mt-1 block w-full" :name="`itens[${idx}][unidade]`" :value="item?.unidade || ''" @input="item.unidade = $el.value" />
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Observacao</label>
                                        <input type="text" class="input-base mt-1 block w-full" :name="`itens[${idx}][observacao]`" :value="item?.observacao || ''" @input="item.observacao = $el.value" />
                                    </div>
                                    <div>
                                        <button type="button" class="text-red-600 hover:text-red-800 text-sm font-medium" @click="removeItem(idx)" x-show="itens.length > 1">Remover</button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                            Um email de confirmacao sera enviado apos o registro da solicitacao.
                        </div>

                        <div class="mt-6 flex items-center gap-3">
                            <x-primary-button data-modal-close="false">Salvar solicitacao</x-primary-button>
                            <button type="button" data-modal-close="true" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function solicitacaoForm({ itensOld }) {
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
                    itens: (itensOld && itensOld.length) ? itensOld.map(buildItem) : [buildItem()],
                    addItem() {
                        this.itens.push(buildItem());
                    },
                    removeItem(idx) {
                        if (this.itens.length <= 1) return;
                        this.itens.splice(idx, 1);
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
                        this.resultados = [];
                        this.dropdownOpen = false;
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
