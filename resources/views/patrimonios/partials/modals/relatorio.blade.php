<div x-show="relatorioModalOpen" x-transition
      class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center" style="display: none;">
      <div @click.outside="relatorioModalOpen = false"
        class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl p-6">
        <div x-show="reportLoading" class="absolute inset-0 bg-white/80 dark:bg-gray-900/80 rounded-lg backdrop-blur-sm flex items-center justify-center z-50" style="display: none;">
          <div class="flex flex-col items-center gap-3">
            <svg class="animate-spin h-10 w-10 text-indigo-600 dark:text-indigo-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            <div class="text-center">
              <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">Gerando relatório</p>
              <p class="text-sm text-gray-600 dark:text-gray-300">Aguarde enquanto preparamos a lista</p>
            </div>
          </div>
        </div>
        <div>
          <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Relatório Geral de Bens
          </h3>
          <template x-if="relatorioGlobalError">
            <div class="mb-4 p-3 rounded-md bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 text-sm text-red-800 dark:text-red-300">
              <strong class="font-semibold" x-text="relatorioGlobalError"></strong>
              <ul class="list-disc ml-5 mt-1 space-y-0.5" x-html="Object.values(relatorioErrors).map(e=>`<li>${e}</li>`).join('')"></ul>
            </div>
          </template>
          <form @submit.prevent="gerarRelatorio">
            @csrf
            <div class="space-y-4">
              <div class="grid grid-cols-2 gap-x-6 gap-y-4">
                <label class="flex items-center space-x-2 cursor-pointer"><input type="radio"
                    name="tipo_relatorio" value="NÃºmero" x-model="tipoRelatorio"
                    class="form-radio text-indigo-600"><span
                    class="text-gray-700 dark:text-gray-300">Por Número</span></label>
                <label class="flex items-center space-x-2 cursor-pointer"><input type="radio"
                    name="tipo_relatorio" value="DescriÃ§Ã£o" x-model="tipoRelatorio"
                    class="form-radio text-indigo-600"><span
                    class="text-gray-700 dark:text-gray-300">Por Descrição</span></label>
                <label class="flex items-center space-x-2 cursor-pointer"><input type="radio"
                    name="tipo_relatorio" value="aquisicao" x-model="tipoRelatorio"
                    class="form-radio text-indigo-600"><span
                    class="text-gray-700 dark:text-gray-300">Por Período de
                    Aquisição</span></label>
                <label class="flex items-center space-x-2 cursor-pointer"><input type="radio"
                    name="tipo_relatorio" value="cadastro" x-model="tipoRelatorio"
                    class="form-radio text-indigo-600"><span
                    class="text-gray-700 dark:text-gray-300">Por Período Cadastro</span></label>
                <label class="flex items-center space-x-2 cursor-pointer"><input type="radio"
                    name="tipo_relatorio" value="projeto" x-model="tipoRelatorio"
                    class="form-radio text-indigo-600"><span
                    class="text-gray-700 dark:text-gray-300">Por Projeto</span></label>
                <label class="flex items-center space-x-2 cursor-pointer"><input type="radio"
                    name="tipo_relatorio" value="oc" x-model="tipoRelatorio"
                    class="form-radio text-indigo-600"><span
                    class="text-gray-700 dark:text-gray-300">Por OC</span></label>
                <label class="flex items-center space-x-2 cursor-pointer"><input type="radio"
                    name="tipo_relatorio" value="uf" x-model="tipoRelatorio"
                    class="form-radio text-indigo-600"><span
                    class="text-gray-700 dark:text-gray-300">Por UF</span></label>
                <label class="flex items-center space-x-2 cursor-pointer"><input type="radio"
                    name="tipo_relatorio" value="SituaÃ§Ã£o" x-model="tipoRelatorio"
                    class="form-radio text-indigo-600"><span
                    class="text-gray-700 dark:text-gray-300">Por Situação</span></label>
              </div>
              <!-- Campo de busca de descrição quando tipo DescriÃ§Ã£o -->
              <div x-data="{ open: false }" @click.outside="open = false" class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <div class="flex items-center justify-between gap-3">
                  <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Filtros adicionais</p>
                  <button type="button" @click="open = !open" :aria-expanded="open.toString()" class="inline-flex items-center justify-center w-7 h-7 rounded-md border border-gray-600 dark:border-gray-600 bg-gray-600 dark:bg-gray-800 hover:bg-gray-700 dark:hover:bg-gray-700 text-white transition focus:outline-none focus:ring-2 focus:ring-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                    <span class="sr-only">Expandir filtros adicionais</span>
                  </button>
                </div>
                <div x-cloak x-show="open" x-transition class="mt-3">
                  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">
                  <div>
                    <label for="relatorio_cdprojeto" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Projeto (cÃ³digo)</label>
                    <input type="text" id="relatorio_cdprojeto" name="cdprojeto" list="relatorio_projetos" placeholder="Ex: 101" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm" />
                  </div>
                  <div>
                    <label for="relatorio_cdlocal" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Local fÃ­sico (cÃ³digo)</label>
                    <input type="text" id="relatorio_cdlocal" name="cdlocal" list="relatorio_locais" placeholder="Ex: 2002" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm" />
                  </div>
                  <div>
                    <label for="relatorio_conferido" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Conferido</label>
                    <select id="relatorio_conferido" name="conferido" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                      <option value="">Todos</option>
                      <option value="S">Verificado</option>
                      <option value="N">Nao verificado</option>
                    </select>
                  </div>
                  <div>
                    <label for="relatorio_voltagem" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Voltagem</label>
                    <input type="text" id="relatorio_voltagem" name="voltagem" placeholder="Ex: 110V, 220V" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm" />
                  </div>
                  </div>
                </div>
                <datalist id="relatorio_projetos">
                  @foreach(($projetos ?? collect()) as $p)
                    <option value="{{ $p->codigo }}">{{ $p->codigo }} - {{ $p->descricao }}</option>
                  @endforeach
                </datalist>
                <datalist id="relatorio_locais">
                  @foreach(($locais ?? collect()) as $l)
                    <option value="{{ $l->codigo }}">{{ $l->codigo }} - {{ $l->descricao }}</option>
                  @endforeach
                </datalist>
              </div>
              <div x-show="tipoRelatorio === 'descricao'" class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg" style="display:none;">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div class="md:col-span-2">
                    <label for="descricao_busca" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Descrição contém</label>
                    <input type="text" id="descricao_busca" name="descricao_busca" placeholder="Parte da descrição" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm" />
                  </div>
                  <div>
                    <label for="sort_direction" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Ordem</label>
                    <select id="sort_direction" name="sort_direction" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                      <option value="asc">Crescente (A-Z)</option>
                      <option value="desc">Decrescente (Z-A)</option>
                    </select>
                  </div>
                </div>
              </div>
              <!-- Campo projeto múltiplos códigos -->
              <div x-show="tipoRelatorio === 'projeto'" class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg" style="display:none;">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div class="md:col-span-2">
                    <label for="projeto_busca" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Codigos de Projeto (separar por virgula, opcional)</label>
                    <input type="text" id="projeto_busca" name="projeto_busca" placeholder="Ex: 101, 202, 303" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm" />
                  </div>
                  <div>
                    <label for="sort_direction_proj" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Ordem Nº Patr.</label>
                    <select id="sort_direction_proj" name="sort_direction" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                      <option value="asc">Crescente</option>
                      <option value="desc">Decrescente</option>
                    </select>
                  </div>
                </div>
              </div>
              
              <!-- Campo de Situação (checkboxes) quando tipo = SituaÃ§Ã£o -->
              <div x-show="tipoRelatorio === 'situacao'" class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 rounded-lg border-2 border-blue-200 dark:border-blue-700" style="display:none;">
                <p class="text-sm font-bold text-gray-800 dark:text-gray-200 mb-3">Selecione as situações desejadas:</p>
                <div class="flex flex-wrap gap-3">
                  <label class="flex items-center space-x-2 cursor-pointer group">
                    <input type="checkbox" name="situacao_busca[]" value="EM USO" checked
                      class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:bg-gray-700 dark:border-gray-600">
                    <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">Em uso</span>
                  </label>
                  <label class="flex items-center space-x-2 cursor-pointer group">
                    <input type="checkbox" name="situacao_busca[]" value="A DISPOSICAO"
                      class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:bg-gray-700 dark:border-gray-600">
                    <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">À disposição</span>
                  </label>
                  <label class="flex items-center space-x-2 cursor-pointer group">
                    <input type="checkbox" name="situacao_busca[]" value="CONSERTO"
                      class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:bg-gray-700 dark:border-gray-600">
                    <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">Conserto</span>
                  </label>
                  <label class="flex items-center space-x-2 cursor-pointer group">
                    <input type="checkbox" name="situacao_busca[]" value="BAIXA"
                      class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:bg-gray-700 dark:border-gray-600">
                    <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">Baixa</span>
                  </label>
                </div>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-3">✓ Marque uma ou mais situações. Deixe todas desmarcadas para incluir todas.</p>
                <p class="text-xs text-red-500 mt-1" x-show="relatorioErrors.situacao_busca" x-text="relatorioErrors.situacao_busca"></p>
              </div>

              <hr class="dark:border-gray-600 my-4">
              <div x-show="tipoRelatorio === 'aquisicao'"
                class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div><label for="data_inicio_aquisicao"
                      class="block font-medium text-sm text-gray-700 dark:text-gray-300">Data
                      Início</label><input type="date" id="data_inicio_aquisicao"
                      name="data_inicio_aquisicao"
                      class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                    <p class="text-xs text-red-500 mt-1" x-show="relatorioErrors.periodo && tipoRelatorio==='aquisicao'" x-text="relatorioErrors.periodo"></p>
                  </div>
                  <div><label for="data_fim_aquisicao"
                      class="block font-medium text-sm text-gray-700 dark:text-gray-300">Data
                      Fim</label><input type="date" id="data_fim_aquisicao"
                      name="data_fim_aquisicao"
                      class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                  </div>
                </div>
              </div>
              <div x-show="tipoRelatorio === 'cadastro'"
                class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div><label for="data_inicio_cadastro"
                      class="block font-medium text-sm text-gray-700 dark:text-gray-300">Data
                      Início</label><input type="date" id="data_inicio_cadastro"
                      name="data_inicio_cadastro"
                      class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                    <p class="text-xs text-red-500 mt-1" x-show="relatorioErrors.periodo && tipoRelatorio==='cadastro'" x-text="relatorioErrors.periodo"></p>
                  </div>
                  <div><label for="data_fim_cadastro"
                      class="block font-medium text-sm text-gray-700 dark:text-gray-300">Data
                      Fim</label><input type="date" id="data_fim_cadastro"
                      name="data_fim_cadastro"
                      class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                  </div>
                </div>
              </div>
              <div x-show="tipoRelatorio === 'numero'" class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <div>
                  <label for="numero_busca" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Número do Patrimônio</label>
                  <input type="number" id="numero_busca" name="numero_busca" placeholder="Digite o número" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                  <p class="text-xs text-red-500 mt-1" x-show="relatorioErrors.numero_busca" x-text="relatorioErrors.numero_busca"></p>
                </div>
              </div>

              <div x-show="tipoRelatorio === 'aquisicao'" class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg" style="display: none;">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label for="data_inicio_aquisicao" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Data Início</label>
                    <input type="date" id="data_inicio_aquisicao" name="data_inicio_aquisicao" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                  </div>
                  <div>
                    <label for="data_fim_aquisicao" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Data Fim</label>
                    <input type="date" id="data_fim_aquisicao" name="data_fim_aquisicao" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                  </div>
                </div>
              </div>
              <div x-show="tipoRelatorio === 'oc'"
                class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div><label for="oc_busca"
                      class="block font-medium text-sm text-gray-700 dark:text-gray-300">OC</label><input
                      type="text" id="oc_busca" name="oc_busca" placeholder="Digite a OC"
                      class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                    <p class="text-xs text-red-500 mt-1" x-show="relatorioErrors.oc_busca" x-text="relatorioErrors.oc_busca"></p>
                  </div>
                  <div><label
                      class="block font-medium text-sm text-gray-700 dark:text-gray-300">Combo
                      OC</label><select
                      class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm"
                      disabled>
                      <option>A definir</option>
                    </select></div>
                </div>
              </div>
              <!-- Campo UF quando tipo uf -->
              <div x-show="tipoRelatorio === 'uf'" class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg" style="display:none;">
                <div>
                  <label for="uf_busca" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Unidade Federativa (UF)</label>
                  <select id="uf_busca" name="uf_busca" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                    <option value="">-- Selecione uma UF --</option>
                    <option value="AC">Acre</option>
                    <option value="AL">Alagoas</option>
                    <option value="AP">Amapá</option>
                    <option value="AM">Amazonas</option>
                    <option value="BA">Bahia</option>
                    <option value="CE">Ceará</option>
                    <option value="DF">Distrito Federal</option>
                    <option value="ES">Espírito Santo</option>
                    <option value="GO">Goiás</option>
                    <option value="MA">Maranhão</option>
                    <option value="MT">Mato Grosso</option>
                    <option value="MS">Mato Grosso do Sul</option>
                    <option value="MG">Minas Gerais</option>
                    <option value="PA">Pará</option>
                    <option value="PB">Paraíba</option>
                    <option value="PR">Paraná</option>
                    <option value="PE">Pernambuco</option>
                    <option value="PI">Piauí</option>
                    <option value="RJ">Rio de Janeiro</option>
                    <option value="RN">Rio Grande do Norte</option>
                    <option value="RS">Rio Grande do Sul</option>
                    <option value="RO">Rondônia</option>
                    <option value="RR">Roraima</option>
                    <option value="SC">Santa Catarina</option>
                    <option value="SP">São Paulo</option>
                    <option value="SE">Sergipe</option>
                    <option value="TO">Tocantins</option>
                  </select>
                  <p class="text-xs text-red-500 mt-1" x-show="relatorioErrors.uf_busca" x-text="relatorioErrors.uf_busca"></p>
                </div>
              </div>
            </div>
            <div class="mt-6 flex justify-end space-x-4">
              <div class="mr-auto flex items-center">
                <button @click="exportarRelatorioFuncionarios()" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded inline-flex items-center transition-colors" title="Exportar lista completa de funcionários" :disabled="reportLoading">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                  </svg>
                  <span>Relatório de Funcionários</span>
                </button>
              </div>
              <button type="button" @click="relatorioModalOpen = false"
                class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500"
                :disabled="isLoading">Sair</button>

              <button type="submit"
                class="px-4 py-2 bg-plansul-blue text-white rounded-md hover:bg-opacity-90 flex items-center min-w-[100px] justify-center"
                :disabled="isLoading">
                <span x-show="!isLoading">Gerar</span>
                <span x-show="isLoading">
                  <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10"
                      stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                  </svg>
                  Gerando...
                </span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    {{-- INÍCIO DO NOVO MODAL DE RESULTADOS --}}
    <div x-show="resultadosModalOpen" x-transition
      class="fixed inset-0 z-50 bg-black bg-opacity-75 flex items-center justify-center" style="display: none;">
      <div @click.outside="resultadosModalOpen = false" class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-7xl p-6 max-h-[90vh] flex flex-col relative">
        
        {{-- OVERLAY DE LOADING --}}
        <div x-show="reportLoading" class="absolute inset-0 bg-white/70 dark:bg-gray-800/70 rounded-lg backdrop-blur-sm flex items-center justify-center z-40" style="display: none;">
          <div class="flex flex-col items-center gap-4">
            {{-- Spinner --}}
            <svg class="animate-spin h-12 w-12 text-indigo-600 dark:text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            {{-- Texto --}}
            <div class="text-center">
              <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">Carregando Relatório</p>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Por favor, aguarde...</p>
            </div>
          </div>
        </div>

        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">Resultado do Relatório</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
          <strong>Filtro:</strong> <span x-text="getFilterLabel(tipoRelatorio)"></span>
          <template x-if="tipoRelatorio === 'numero'">
            <span> → <strong x-text="reportFilters.numero_busca || 'Todos'"></strong></span>
          </template>
          <template x-if="tipoRelatorio === 'descricao'">
            <span> → <strong x-text="reportFilters.descricao_busca || 'Todos'"></strong></span>
          </template>
          <template x-if="tipoRelatorio === 'projeto'">
            <span> → <strong x-text="reportFilters.projeto_busca || 'Todos'"></strong></span>
          </template>
          <template x-if="tipoRelatorio === 'oc'">
            <span> → <strong x-text="reportFilters.oc_busca || 'Todos'"></strong></span>
          </template>
          <template x-if="tipoRelatorio === 'uf'">
            <span> → <strong x-text="reportFilters.uf_busca"></strong></span>
          </template>
          <template x-if="tipoRelatorio === 'situacao'">
            <span> → <strong x-text="reportFilters.situacao_busca"></strong></span>
          </template>
          <template x-if="['aquisicao', 'cadastro'].includes(tipoRelatorio)">
            <span> → <strong x-text="(reportFilters.data_inicio_aquisicao || reportFilters.data_inicio_cadastro) + ' a ' + (reportFilters.data_fim_aquisicao || reportFilters.data_fim_cadastro)"></strong></span>
          </template>
        </p>
        <div class="mt-2 flex flex-wrap gap-2 text-xs">
          <template x-if="reportFilters.cdprojeto">
            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-slate-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-slate-200 dark:border-gray-600">
              <span class="font-semibold">Projeto</span>
              <span x-text="reportFilters.cdprojeto"></span>
            </span>
          </template>
          <template x-if="reportFilters.cdlocal">
            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-slate-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-slate-200 dark:border-gray-600">
              <span class="font-semibold">Local</span>
              <span x-text="reportFilters.cdlocal"></span>
            </span>
          </template>
          <template x-if="reportFilters.conferido">
            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-slate-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-slate-200 dark:border-gray-600">
              <span class="font-semibold">Conferido</span>
              <span x-text="formatConferido(reportFilters.conferido)"></span>
            </span>
          </template>
          <template x-if="reportFilters.situacao_busca && tipoRelatorio !== 'situacao'">
            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-slate-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-slate-200 dark:border-gray-600">
              <span class="font-semibold">SituaÃ§Ã£o</span>
              <span x-text="reportFilters.situacao_busca"></span>
            </span>
          </template>
          <template x-if="reportFilters.voltagem">
            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-slate-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-slate-200 dark:border-gray-600">
              <span class="font-semibold">Voltagem</span>
              <span x-text="reportFilters.voltagem"></span>
            </span>
          </template>
        </div>
        <div class="flex-grow overflow-y-auto">
          <table class="w-full table-fixed text-[11px] text-left text-gray-500 dark:text-gray-400">
            <thead
                  class="text-xs text-white uppercase bg-blue-900 dark:bg-blue-900 sticky top-0 font-bold shadow-sm">
              <tr>
                <!-- Coluna dinâmica em primeiro lugar (conforme o tipo de filtro) -->
                  <template x-if="tipoRelatorio === 'numero'">
                  <th scope="col" class="px-6 py-3 font-bold">Nº Patrimônio</th>
                </template>
                <template x-if="tipoRelatorio === 'descricao'">
                  <th scope="col" class="px-6 py-3 font-bold">Descrição</th>
                </template>
                <template x-if="tipoRelatorio === 'projeto'">
                  <th scope="col" class="px-6 py-3 font-bold">Código Projeto</th>
                </template>
                <template x-if="tipoRelatorio === 'oc'">
                  <th scope="col" class="px-6 py-3 font-bold">OC</th>
                </template>
                <template x-if="tipoRelatorio === 'uf'">
                  <th scope="col" class="px-6 py-3 font-bold">UF</th>
                </template>
                <template x-if="tipoRelatorio === 'situacao'">
                  <th scope="col" class="px-6 py-3 font-bold text-xs">Situação</th>
                </template>
                <template x-if="tipoRelatorio === 'aquisicao'">
                  <th scope="col" class="px-6 py-3 font-bold">Data Aquisição</th>
                </template>
                <template x-if="tipoRelatorio === 'cadastro'">
                  <th scope="col" class="px-6 py-3 font-bold">Data Cadastro</th>
                </template>

                <!-- Colunas fixas (sempre aparecem depois) -->
                <th scope="col" class="px-6 py-3">N? Patrim?nio</th>
                <th scope="col" class="px-6 py-3">Descri??o</th>
                {{-- Colunas extras para contexto do RelatÃ³rio --}}
                <th scope="col" class="px-6 py-3">Projeto</th>
                <th scope="col" class="px-6 py-3">Modelo</th>
                <th scope="col" class="px-6 py-3 text-xs">Situa??o</th>
                <th scope="col" class="px-6 py-3">Conferido</th>
                <th scope="col" class="px-6 py-3">Local F?sico</th>
                <th scope="col" class="px-6 py-3">Cadastrador</th>
              </tr>
            </thead>
            <tbody>
              <template x-if="reportData.length === 0">
                <tr>
                  <td colspan="9" class="px-6 py-4 text-center text-lg">
                    Nenhum patrimônio encontrado para os filtros aplicados.
                  </td>
                </tr>
              </template>
              <template x-for="(patrimonio, idx) in reportData" :key="patrimonio.NUSEQPATR">
                <tr
                  :class="(idx % 2 === 0 ? 'bg-blue-50 dark:bg-gray-900' : 'bg-blue-100 dark:bg-gray-800') + ' border-b-2 border-blue-200 dark:border-gray-600 hover:bg-blue-300 dark:hover:bg-gray-700 cursor-pointer'">
                  <!-- Coluna dinâmica em primeiro lugar (conforme o tipo de filtro) -->
                  <template x-if="tipoRelatorio === 'numero'">
                    <td :class="'px-6 py-4 font-bold ' + getColumnColor().replace('bg-', 'text-').replace(/dark:bg-/, 'dark:text-')">
                      <span x-text="patrimonio.NUPATRIMONIO || 'N/A'"></span>
                    </td>
                  </template>
                  <template x-if="tipoRelatorio === 'descricao'">
                    <td :class="'px-6 py-4 font-bold ' + getColumnColor().replace('bg-', 'text-').replace(/dark:bg-/, 'dark:text-')">
                      <span x-text="patrimonio.DEPATRIMONIO || 'N/A'"></span>
                    </td>
                  </template>
                  <template x-if="tipoRelatorio === 'projeto'">
                    <td :class="'px-6 py-4 font-bold ' + getColumnColor().replace('bg-', 'text-').replace(/dark:bg-/, 'dark:text-')">
                      <span x-text="patrimonio.CDPROJETO || 'N/A'"></span>
                    </td>
                  </template>
                  <template x-if="tipoRelatorio === 'oc'">
                    <td :class="'px-6 py-4 font-bold ' + getColumnColor().replace('bg-', 'text-').replace(/dark:bg-/, 'dark:text-')">
                      <span x-text="patrimonio.NUMOF || 'N/A'"></span>
                    </td>
                  </template>
                  <template x-if="tipoRelatorio === 'uf'">
                    <td :class="'px-6 py-4 font-bold ' + getColumnColor().replace('bg-', 'text-').replace(/dark:bg-/, 'dark:text-')">
                      <span x-text="patrimonio.projeto_uf || 'N/A'"></span>
                    </td>
                  </template>
                  <template x-if="tipoRelatorio === 'situacao'">
                    <td :class="'px-6 py-4 font-bold ' + getColumnColor().replace('bg-', 'text-').replace(/dark:bg-/, 'dark:text-')">
                      <span x-text="patrimonio.SITUACAO || 'N/A'"></span>
                    </td>
                  </template>
                  <template x-if="tipoRelatorio === 'aquisicao'">
                    <td :class="'px-6 py-4 font-bold ' + getColumnColor().replace('bg-', 'text-').replace(/dark:bg-/, 'dark:text-')">
                      <span x-text="patrimonio.DTAQUISICAO || 'N/A'"></span>
                    </td>
                  </template>
                  <template x-if="tipoRelatorio === 'cadastro'">
                    <td :class="'px-6 py-4 font-bold ' + getColumnColor().replace('bg-', 'text-').replace(/dark:bg-/, 'dark:text-')">
                      <span x-text="patrimonio.DTOPERACAO || 'N/A'"></span>
                    </td>
                  </template>

                  <!-- Colunas fixas (sempre aparecem depois) -->
                  <td class="px-6 py-4" x-text="patrimonio.NUPATRIMONIO || 'N/A'"></td>
                  <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white"
                    x-text="patrimonio.DEPATRIMONIO"></td>
                  <td class="px-6 py-4" x-text="formatProjeto(patrimonio)"></td>
                  <td class="px-6 py-4" x-text="patrimonio.MODELO || 'N/A'"></td>
                  <td class="px-6 py-4" x-text="patrimonio.SITUACAO"></td>
                  <td class="px-6 py-4" x-text="formatConferido(patrimonio.FLCONFERIDO)"></td>
                  <td class="px-6 py-4"
                    x-text="patrimonio.local ? patrimonio.local.LOCAL : 'SISTEMA'"></td>
                  <td class="px-6 py-4"
                    x-text="patrimonio.creator ? patrimonio.creator.NOMEUSER : 'SISTEMA'">
                  </td>
                  <td class="px-6 py-4"
                    x-text="patrimonio.creator ? patrimonio.creator.NOMEUSER : 'SISTEMA'">
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
        <div class="mt-4 pt-4 border-t dark:border-gray-700 flex justify-between items-center">
          <span class="text-sm text-gray-500">
            Total de registros encontrados: <strong x-text="reportData.length"></strong>
          </span>

          <div class="flex items-center space-x-2">
            {{-- Botão PDF (vermelho) --}}
            <button @click="exportarRelatorio('pdf')" title="Exportar para PDF"
              class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-3 rounded inline-flex items-center">
              <x-heroicon-o-document-text class="w-5 h-5 mr-2" />
              <span>PDF</span>
            </button>
            {{-- Botão Excel (verde) --}}
            <button @click="exportarRelatorio('excel')" title="Exportar para Excel"
              class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-3 rounded inline-flex items-center">
              <x-heroicon-o-table-cells class="w-5 h-5 mr-2" />
              <span>Excel</span>
            </button>
            {{-- Botão CSV (verde-escuro) --}}
            <button @click="exportarRelatorio('csv')" title="Exportar para CSV"
              class="bg-green-800 hover:bg-green-900 text-white font-bold py-2 px-3 rounded inline-flex items-center">
              <x-heroicon-o-document-chart-bar class="w-5 h-5 mr-2" />
              <span>CSV</span>
            </button>
            {{-- Botão ODS (azul) --}}
            <button @click="exportarRelatorio('ods')" title="Exportar para LibreOffice/OpenOffice"
              class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-3 rounded inline-flex items-center">
              <x-heroicon-o-document-duplicate class="w-5 h-5 mr-2" />
              <span>ODS</span>
            </button>
            {{-- Botão Fechar --}}
            <button @click="resultadosModalOpen = false"
              class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-bold rounded">
              Fechar
            </button>
          </div>
        </div>
      </div>
    </div>
