@php
    $containerClass = $isModal ? 'bg-transparent' : 'py-8 px-4 sm:px-6';
    $innerClass = $isModal ? 'w-full' : 'max-w-3xl mx-auto';
    $cardClass = $isModal ? 'w-full bg-white dark:bg-gray-800 rounded-lg shadow-sm' : 'bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden';
    $padClass = $isModal ? 'p-5 sm:p-6' : 'p-6 sm:p-8';
    $showStep2 = $errors->has('itens') || $errors->has('itens.0.descricao') || $errors->has('itens.0.quantidade') || $errors->has('itens.0.unidade') || $errors->has('itens.0.observacao');
@endphp

<div class="{{ $containerClass }}">
    <div class="{{ $innerClass }}">
        @if($errors->any())
            <div class="mb-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg text-sm flex items-start gap-2" role="alert">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                <div>
                    <span class="font-semibold block">Erro na validação</span>
                    <span class="text-sm">{{ $errors->first() }}</span>
                </div>
            </div>
        @endif

        <div class="{{ $cardClass }}">
            <div class="{{ $padClass }} text-gray-900 dark:text-gray-100 space-y-5" style="min-height: 200px;">
                <form method="POST" action="{{ route('solicitacoes-bens.store') }}" x-data="solicitacaoForm({ itensOld: @js($oldItens), showStep2: @js($showStep2) })" data-modal-form="{{ $isModal ? '1' : '0' }}" x-cloak class="space-y-4">
                    @csrf
                    @if($isModal)
                        <input type="hidden" name="modal" value="1" />
                    @endif
                    <input type="hidden" name="solicitante_nome" value="{{ $defaultNome }}" />
                    <input type="hidden" name="solicitante_matricula" value="{{ $defaultMatricula }}" />
                    <input type="hidden" name="uf" value="{{ $defaultUf }}" />
                    
                    <!-- SEÃ‡ÃƒO 1: Dados da SolicitaÃ§Ã£o -->
                    <div x-show="step === 1" class="space-y-3">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 border-b border-gray-200 dark:border-gray-700 pb-2">Dados da Solicitação</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3" x-data="projetoLocalForm()">
                            <!-- Projeto com Dropdown Searchable -->
                            <div>
                                <label for="projetoSearch" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Projeto *</label>
                                <div class="relative" @click.away="showProjetoDrop=false">
                                    <input id="projetoSearch" type="text" x-model="projetoSearch"
                                        @focus="showProjetoDrop=true; filtrarProjetos()"
                                        @input="filtrarProjetos()"
                                        @keydown.down.prevent="projetoIndex = Math.min(projetoIndex+1, projetosFiltrados.length-1)"
                                        @keydown.up.prevent="projetoIndex = Math.max(projetoIndex-1, 0)"
                                        @keydown.enter.prevent="selecionarProjeto(projetosFiltrados[projetoIndex])"
                                        class="block w-full h-8 text-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500 pr-6"
                                        placeholder="Buscar projeto..."
                                        required />
                                    <input type="hidden" id="projeto_id" name="projeto_id" :value="projetoSelecionado" />
                                    <button type="button" x-show="projetoSearch" @click="limparProjeto()" class="absolute inset-y-0 right-0 flex items-center pr-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" tabindex="-1">×</button>
                                    <div x-show="showProjetoDrop" x-transition class="absolute z-[999] top-full mt-1 w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg overflow-hidden text-xs">
                                        <div style="max-height: 145px; overflow-y: auto;">
                                        <template x-for="(proj, i) in projetosFiltrados" :key="proj.id">
                                            <button type="button" @click="selecionarProjeto(proj); showProjetoDrop=false"
                                                :class="{'bg-indigo-100 dark:bg-gray-700': projetoIndex === i, 'hover:bg-gray-50 dark:hover:bg-gray-700': projetoIndex !== i}"
                                                class="w-full text-left px-3 py-2 border-b border-gray-100 dark:border-gray-700 last:border-0 transition">
                                                <span class="text-indigo-600 dark:text-indigo-400 font-mono text-xs" x-text="proj.CDPROJETO"></span>
                                                <span class="text-gray-700 dark:text-gray-300 ml-2" x-text="proj.NOMEPROJETO"></span>
                                            </button>
                                        </template>
                                        <div x-show="projetosFiltrados.length === 0" class="px-3 py-2 text-gray-500 dark:text-gray-400">Nenhum projeto encontrado</div>
                                        </div>
                                    </div>
                                </div>
                                <x-input-error :messages="$errors->get('projeto_id')" class="mt-1" />
                            </div>

                            <!-- Local Destino (dropdown baseado no projeto) -->
                            <div>
                                <label for="localDestinoSearch" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Local Destino *</label>
                                <div class="relative" @click.away="showLocalDrop=false">
                                    <input id="localDestinoSearch" type="text" x-model="localDestinoSearch"
                                        @focus="abrirDropdownLocal()"
                                        @input="filtrarLocais()"
                                        @keydown.down.prevent="localIndex = Math.min(localIndex+1, locaisFiltrados.length-1)"
                                        @keydown.up.prevent="localIndex = Math.max(localIndex-1, 0)"
                                        @keydown.enter.prevent="selecionarLocal(locaisFiltrados[localIndex])"
                                        :disabled="!projetoSelecionado"
                                        class="block w-full h-8 text-xs rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500 pr-6"
                                        :class="projetoSelecionado ? 'border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200' : 'border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-500 cursor-not-allowed'"
                                        placeholder="Selecione o projeto primeiro..."
                                        required />
                                    <input type="hidden" id="local_destino" name="local_destino" :value="localSelecionado" />
                                    <button type="button" x-show="localDestinoSearch && projetoSelecionado" @click="limparLocal()" class="absolute inset-y-0 right-0 flex items-center pr-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" tabindex="-1">×</button>
                                    <div x-show="showLocalDrop" x-transition class="absolute z-[999] top-full mt-1 w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg overflow-hidden text-xs">
                                        <div style="max-height: 180px; overflow-y: auto;">
                                        <template x-if="loadingLocais">
                                            <div class="px-3 py-2 text-gray-500 dark:text-gray-400 text-center">Carregando locais...</div>
                                        </template>
                                        <template x-if="!loadingLocais && locaisFiltrados.length === 0">
                                            <div class="px-3 py-2 text-gray-500 dark:text-gray-400">Nenhum local encontrado</div>
                                        </template>
                                        <template x-for="(loc, i) in locaisFiltrados" :key="loc.id">
                                            <button type="button" @click="selecionarLocal(loc); showLocalDrop=false"
                                                :class="{'bg-indigo-100 dark:bg-gray-700': localIndex === i, 'hover:bg-gray-50 dark:hover:bg-gray-700': localIndex !== i}"
                                                class="w-full text-left px-3 py-2 border-b border-gray-100 dark:border-gray-700 last:border-0 transition">
                                                <span class="text-indigo-600 dark:text-indigo-400 font-mono text-xs" x-text="loc.cdlocal"></span>
                                                <span class="text-gray-700 dark:text-gray-300 ml-2" x-text="loc.delocal"></span>
                                            </button>
                                        </template>
                                        </div>
                                    </div>
                                </div>
                                <x-input-error :messages="$errors->get('local_destino')" class="mt-1" />
                            </div>
                        </div>

                        <!-- Observação -->
                        <div>
                            <label for="ObservaÃ§Ã£o" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Observações</label>
                            <textarea id="ObservaÃ§Ã£o" name="ObservaÃ§Ã£o" 
                                class="block w-full text-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500" 
                                rows="2" placeholder="Digite suas observações...">{{ old('observacao') }}</textarea>
                            <x-input-error :messages="$errors->get('observacao')" class="mt-1" />
                        </div>

                        <!-- Botão Próxima Etapa -->
                        <div class="flex items-center justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
                            <span class="text-xs text-gray-600 dark:text-gray-400">Etapa 1 de 2</span>
                            <button type="button" @click="nextStep()" 
                                class="px-4 py-2 text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                                Próxima Etapa →
                            </button>
                        </div>
                    </div>

                    <!-- SEÇÃO 2: Item Solicitado -->
                    <div x-show="step === 2" class="space-y-3">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 border-b border-gray-200 dark:border-gray-700 pb-2">Item Solicitado</h3>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Digite 2+ caracteres para buscar no estoque disponível.</p>

                        <div class="border-2 border-indigo-500 dark:border-indigo-400 rounded-lg p-4 bg-white dark:bg-gray-800 space-y-3 shadow-sm" 
                            x-data="patrimonioSearch(item)" @click.away="closeResults">

                            <!-- Busca de Patrimônio -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Patrimônio / Descrição *</label>
                                <div class="relative">
                                    <input type="text"
                                        class="w-full h-8 text-xs px-3 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        name="itens[0][patrimonio_busca]"
                                        x-model.trim="item.patrimonio_busca"
                                        @input.debounce.300ms="onInput"
                                        @focus="openResults"
                                        @keydown.escape.prevent="closeResults"
                                        placeholder="Digite número ou descrição..."
                                        autocomplete="off"
                                    />
                                    <input type="hidden" name="itens[0][descricao]" :value="item?.descricao || ''" />
                                    
                                    <!-- Dropdown de resultados -->
                                    <div x-cloak x-show="dropdownOpen" x-transition
                                        class="absolute z-20 top-full mt-1 w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-lg max-h-56 overflow-y-auto text-xs">
                                        <div x-show="loading" class="px-4 py-3 text-gray-500 dark:text-gray-400">Buscando...</div>
                                        <template x-if="!loading && resultados.length === 0">
                                            <div class="px-4 py-3 text-gray-500 dark:text-gray-400">Nenhum resultado encontrado.</div>
                                        </template>
                                        <template x-for="resultado in resultados" :key="resultado.id">
                                            <button type="button" @click="selectResultado(resultado)"
                                                class="w-full text-left px-4 py-2 hover:bg-indigo-50 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700 last:border-0 transition">
                                                <div class="text-xs font-medium text-gray-900 dark:text-gray-100" x-text="resultado.text"></div>
                                                <div class="text-xs text-green-600 dark:text-green-400" x-show="resultado.conferido">✓ Conferido</div>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                                <div x-show="item.selecionado" class="mt-1 inline-flex items-center rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 px-2.5 py-0.5 text-xs font-medium">
                                    ✓ Selecionado
                                </div>
                            </div>

                            <!-- Grid: Quantidade, Unidade (com Peso automático), Observação -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Quantidade *</label>
                                    <input type="number" min="1" 
                                        class="w-full h-8 text-xs px-3 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent" 
                                        name="itens[0][quantidade]" 
                                        :value="item?.quantidade || 1" 
                                        @input="item.quantidade = parseInt($el.value) || 1" 
                                        required />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Unidade / Peso</label>
                                    <input type="text" 
                                        class="w-full h-8 text-xs px-3 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent" 
                                        name="itens[0][unidade]" 
                                        :value="item?.unidade || ''" 
                                        @input="item.unidade = $el.value"
                                        :placeholder="item?.peso ? `Peso: ${item.peso} kg` : 'Un., Kg, L, ...'"
                                        :readonly="!!item?.peso"
                                        :class="item?.peso ? 'bg-gray-100 dark:bg-gray-700/80 text-gray-500 dark:text-gray-300 border-gray-300 dark:border-gray-600 cursor-not-allowed' : ''" />
                                    <p x-show="item?.peso" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Peso do item: <span class="font-semibold" x-text="item.peso + ' kg'"></span>
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Observação</label>
                                    <input type="text" 
                                        class="w-full h-8 text-xs px-3 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent" 
                                        name="itens[0][observacao]" 
                                        :value="item?.observacao || ''" 
                                        @input="item.observacao = $el.value" 
                                        placeholder="Adicionar observação..." />
                                </div>
                            </div>
                        </div>

                        <!-- Botões de Ação -->
                        <div class="flex items-center justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
                            <button type="button" @click="step = 1" 
                                class="px-4 py-2 text-xs font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-gray-100 rounded-md shadow-sm transition">
                                ← Voltar
                            </button>
                            <div class="flex items-center gap-2">
                                <button type="button" data-modal-close="true" 
                                    class="px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                                    Cancelar
                                </button>
                                <x-primary-button data-modal-close="false" class="px-4 py-2 text-xs">
                                    Salvar Solicitação
                                </x-primary-button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

