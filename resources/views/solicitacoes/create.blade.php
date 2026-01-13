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
                    <form method="POST" action="{{ route('solicitacoes-bens.store') }}" x-data="solicitacaoForm({ itensOld: @js($oldItens) })" data-modal-form="{{ $isModal ? '1' : '0' }}" x-cloak>>
                        @csrf                        @if($isModal)
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
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-lg font-semibold">Itens solicitados</h3>
                                <button type="button" class="text-sm text-indigo-600 hover:text-indigo-800" @click="addItem">Adicionar item</button>
                            </div>

                            <template x-for="(item, idx) in itens" :key="idx" x-cloak>
                                <div class="grid gap-3 md:grid-cols-6 items-end mb-4 border-b dark:border-gray-700 pb-4" x-show="item">
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Patrimonio / Descricao *</label>
                                        <div class="relative">
                                            <input 
                                                type="text" 
                                                class="input-base mt-1 block w-full" 
                                                :name="`itens[${idx}][patrimonio_busca]`"
                                                :value="item?.patrimonio_busca || ''"
                                                @input="item.patrimonio_busca = $el.value"
                                                placeholder="Digite numero ou descrição..."
                                                autocomplete="off"
                                            />
                                            <input type="hidden" :name="`itens[${idx}][descricao]`" :value="item?.descricao || ''" />
                                        </div>
                                        <small class="text-xs text-gray-500 dark:text-gray-400 mt-1 block">Busca em estoque disponível</small>
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
                return {
                    itens: itensOld || [],
                    addItem() {
                        this.itens.push({ descricao: '', quantidade: 1, unidade: '', observacao: '', patrimonio_busca: '' });
                    },
                    removeItem(idx) {
                        if (this.itens.length <= 1) return;
                        this.itens.splice(idx, 1);
                    }
                };
            }

            function patrimonioSearch(idx) {
                return {
                    busca: '',
                    resultados: [],
                    item: null,
                    
                    buscarPatrimonios() {
                        if (this.busca.length < 2) {
                            this.resultados = [];
                            return;
                        }

                        fetch(`{{ route('solicitacoes-bens.patrimonio-disponivel') }}?q=${encodeURIComponent(this.busca)}`)
                            .then(res => res.json())
                            .then(data => {
                                this.resultados = data;
                            })
                            .catch(err => console.error('Erro ao buscar patrimônios:', err));
                    },

                    selecionarPatrimonio(patrimonio) {
                        // Encontrar o item no formulário principal (parent alpine)
                        const parentForm = document.querySelector('[x-data*="solicitacaoForm"]').__x.$data;
                        parentForm.itens[idx].descricao = patrimonio.text;
                        this.busca = patrimonio.text;
                        this.resultados = [];
                    }
                };
            }
        </script>
    @endpush
</x-app-layout>
