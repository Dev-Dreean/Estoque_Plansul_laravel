@php
    $containerClass = $isModal ? 'bg-transparent' : 'py-12';
    $innerClass = $isModal ? 'w-full' : 'max-w-5xl mx-auto px-4 sm:px-6 lg:px-8';
    $cardClass = $isModal ? 'w-full border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 shadow-sm' : 'bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg';
    $padClass = $isModal ? 'p-3 sm:p-4' : 'p-6';
    $showStep2 = $errors->has('itens') || $errors->has('itens.0.descricao') || $errors->has('itens.0.quantidade') || $errors->has('itens.0.unidade') || $errors->has('itens.0.observacao');
@endphp

<div class="{{ $containerClass }}">
    <div class="{{ $innerClass }}">
        @if($errors->any())
            <div class="mb-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded-lg" role="alert">
                <span class="font-semibold">Erro:</span> {{ $errors->first() }}
            </div>
        @endif

        <div class="{{ $cardClass }}">
            <div class="{{ $padClass }} text-gray-900 dark:text-gray-100">
                <form method="POST" action="{{ route('solicitacoes-bens.store') }}" x-data="solicitacaoForm({ itensOld: @js($oldItens), showStep2: @js($showStep2) })" data-modal-form="{{ $isModal ? '1' : '0' }}" x-cloak>
                    @csrf
                    @if($isModal)
                        <input type="hidden" name="modal" value="1" />
                    @endif
                    <input type="hidden" name="solicitante_nome" value="{{ $defaultNome }}" />
                    <input type="hidden" name="solicitante_matricula" value="{{ $defaultMatricula }}" />
                    <input type="hidden" name="uf" value="{{ $defaultUf }}" />
                    
<!-- SECAO 1: Dados da solicitacao -->
                    <div x-show="step === 1" class="border-b border-gray-200 dark:border-gray-700 pb-3 mb-3">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-2">Dados da Solicitacao</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                            <div>
                                <x-input-label class="text-xs" for="projeto_id" value="Projeto *" />
                                <select id="projeto_id" name="projeto_id" class="mt-1 block w-full h-8 text-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500" required>
                                    <option value="">-- Selecione um projeto --</option>
                                    @foreach($projetos as $projeto)
                                        <option value="{{ $projeto->id }}" @selected(old('projeto_id') == $projeto->id)>
                                            {{ $projeto->CDPROJETO }} - {{ $projeto->NOMEPROJETO }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('projeto_id')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label class="text-xs" for="setor" value="Setor *" />
                                <x-text-input id="setor" name="setor" type="text" class="mt-1 block w-full h-8 text-xs" value="{{ old('setor') }}" required />
                                <x-input-error :messages="$errors->get('setor')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label class="text-xs" for="local_destino" value="Local destino *" />
                                <x-text-input id="local_destino" name="local_destino" type="text" class="mt-1 block w-full h-8 text-xs" value="{{ old('local_destino') }}" required />
                                <x-input-error :messages="$errors->get('local_destino')" class="mt-1" />
                            </div>
                        </div>
                        <div class="mt-2">
                            <x-input-label class="text-xs" for="observacao" value="Observacao" />
                            <textarea id="observacao" name="observacao" class="mt-1 block w-full border border-gray-300 text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent" rows="2">{{ old('observacao') }}</textarea>
                            <x-input-error :messages="$errors->get('observacao')" class="mt-1" />
                        </div>
                        <div class="mt-3 flex items-center gap-3">
                            <button type="button" class="px-4 py-2 text-xs font-semibold bg-plansul-blue hover:bg-opacity-90 text-white rounded-md" @click="nextStep()">
                                Proxima etapa
                            </button>
                        </div>
                    </div>

<!-- SECAO 3: Item Solicitado -->
                    <div x-show="step === 2" class="pb-3 mb-3">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-1">Item Solicitado</h3>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                            Selecione um item do estoque disponivel. Digite 2+ caracteres para filtrar (lista limitada a 50).
                        </p>

                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-2 bg-gray-50 dark:bg-gray-900" x-data="patrimonioSearch(item)" @click.away="closeResults">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
<!-- Campo Patrimonio/Descricao -->
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Patrimonio / Descricao *</label>
                                    <div class="relative">
                                        <input
                                            type="text"
                                            class="w-full h-8 text-xs px-3 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                            name="itens[0][patrimonio_busca]"
                                            x-model.trim="item.patrimonio_busca"
                                            @input.debounce.300ms="onInput"
                                            @focus="openResults"
                                            @keydown.escape.prevent="closeResults"
                                            placeholder="Digite numero ou descricao..."
                                            autocomplete="off"
                                        />
                                        <input type="hidden" name="itens[0][descricao]" :value="item?.descricao || ''" />
                                        
                                        <!-- Dropdown de resultados -->
                                        <div
                                            x-cloak
                                            x-show="dropdownOpen"
                                            x-transition
                                            class="absolute z-20 mt-1 w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-lg max-h-60 overflow-auto"
                                        >
                                            <div x-show="loading" class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">Buscando...</div>
                                            <template x-if="!loading && resultados.length === 0">
                                                <div class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">Nenhum resultado encontrado.</div>
                                            </template>
                                            <template x-for="resultado in resultados" :key="resultado.id">
                                                <button
                                                    type="button"
                                                    class="w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700 last:border-b-0 transition"
                                                    @click="selectResultado(resultado)"
                                                >
                                                    <div class="text-xs font-medium text-gray-900 dark:text-gray-100" x-text="resultado.text"></div>
                                                    <div class="text-xs text-green-600 dark:text-green-400" x-show="resultado.conferido">Conferido</div>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="mt-1 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                        <span>Apenas itens disponiveis do estoque</span>
                                        <span x-show="item.selecionado" class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 px-2.5 py-0.5 text-xs font-medium">Selecionado</span>
                                    </div>
                                </div>

<!-- Quantidade, Unidade, Observacao -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Quantidade *</label>
                                    <input 
                                        type="number" 
                                        min="1" 
                                        class="w-full h-8 text-xs px-3 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent" 
                                        name="itens[0][quantidade]" 
                                        :value="item?.quantidade || 1" 
                                        @input="item.quantidade = parseInt($el.value) || 1" 
                                        required 
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Unidade</label>
                                    <input 
                                        type="text" 
                                        class="w-full h-8 text-xs px-3 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent" 
                                        name="itens[0][unidade]" 
                                        :value="item?.unidade || ''" 
                                        @input="item.unidade = $el.value" 
                                        placeholder="Ex: Un., Kg, L"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Observacao</label>
                                    <input 
                                        type="text" 
                                        class="w-full h-8 text-xs px-3 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent" 
                                        name="itens[0][observacao]" 
                                        :value="item?.observacao || ''" 
                                        @input="item.observacao = $el.value" 
                                        placeholder="Adicione observacoes adicionais se necessario"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

<!-- SECAO 4: Confirmacao e Acoes -->
                    <div x-show="step === 2" class="border-t border-gray-200 dark:border-gray-700 pt-3">
                        <div class="flex items-center justify-start gap-3">
                            <x-primary-button data-modal-close="false">
                                <span>Salvar Solicitacao</span>
                            </x-primary-button>
                            <button 
                                type="button" 
                                data-modal-close="true" 
                                class="px-3 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-0 dark:focus:ring-offset-0 transition"
                            >
                                Cancelar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@if($isModal)
    <script>
        function solicitacaoForm({ itensOld, showStep2 }) {
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
                step: showStep2 ? 2 : 1,
                item: (itensOld && itensOld.length) ? buildItem(itensOld[0]) : buildItem(),
                nextStep() {
                    const fields = ['projeto_id', 'setor', 'local_destino'];
                    for (const id of fields) {
                        const el = document.getElementById(id);
                        if (!el) continue;
                        if (!String(el.value || '').trim()) {
                            if (typeof el.reportValidity === 'function') {
                                el.reportValidity();
                            }
                            el.focus();
                            return;
                        }
                    }
                    this.step = 2;
                },
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
@else
    @push('scripts')
        <script>
            function solicitacaoForm({ itensOld, showStep2 }) {
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
                    step: showStep2 ? 2 : 1,
                    item: (itensOld && itensOld.length) ? buildItem(itensOld[0]) : buildItem(),
                    nextStep() {
                        const fields = ['projeto_id', 'setor', 'local_destino'];
                        for (const id of fields) {
                            const el = document.getElementById(id);
                            if (!el) continue;
                            if (!String(el.value || '').trim()) {
                                if (typeof el.reportValidity === 'function') {
                                    el.reportValidity();
                                }
                                el.focus();
                                return;
                            }
                        }
                        this.step = 2;
                    },
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
@endif
