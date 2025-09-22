@props(['patrimonio' => null])
<div x-data='patrimonioForm({ patrimonio: @json($patrimonio), old: @json(old()) })' @keydown.enter.prevent="focusNext($event.target)" class="space-y-4 md:space-y-5 text-sm">

  {{-- GRUPO 1: N° Patrimônio, N° OC, Campo Vazio --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-5">
    <div>
      <x-input-label for="NUPATRIMONIO" value="Nº Patrimônio *" />
      <div class="relative mt-0.5" @click.away="showPatDropdown=false">
        <input id="NUPATRIMONIO"
          x-model="patSearch"
          @focus="abrirDropdownPatrimonios()"
          @input.debounce.300ms="buscarPatrimonios"
          @keydown.down.prevent="navegarPatrimonios(1)"
          @keydown.up.prevent="navegarPatrimonios(-1)"
          @keydown.enter.prevent="selecionarPatrimonioEnter()"
          @keydown.escape.prevent="showPatDropdown=false"
          name="NUPATRIMONIO"
          type="text"
          inputmode="numeric"
          class="block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm pr-10"
          placeholder="Digite número ou descrição"
          required />
        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
          <div class="flex items-center gap-2">
            <button type="button" x-show="formData.NUPATRIMONIO" @click="limparPatrimonio" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none" title="Limpar seleção" aria-label="Limpar seleção">✕</button>
            <button type="button" @click="abrirDropdownPatrimonios(true)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 focus:outline-none" title="Abrir lista" aria-label="Abrir lista">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
              </svg>
            </button>
          </div>
        </div>
        <div x-show="showPatDropdown" x-transition class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-64 overflow-y-auto text-sm">
          <template x-if="loadingPatrimonios">
            <div class="p-2 text-gray-500">Buscando...</div>
          </template>
          <template x-if="!loadingPatrimonios && patrimoniosLista.length === 0">
            <div class="p-2 text-gray-500" x-text="patSearch.trim()==='' ? 'Digite para buscar' : 'Nenhum resultado'"></div>
          </template>
          <template x-for="(p, i) in patrimoniosLista" :key="p.NUPATRIMONIO">
            <div data-pat-item @click="selecionarPatrimonio(p)" @mouseover="highlightedPatIndex = i" :class="['px-3 py-2 cursor-pointer', highlightedPatIndex === i ? 'bg-indigo-100 dark:bg-gray-700' : 'hover:bg-indigo-50 dark:hover:bg-gray-700']">
              <span class="font-mono text-xs text-indigo-600 dark:text-indigo-400" x-text="p.NUPATRIMONIO"></span>
              <span class="ml-2" x-text="' - ' + p.DEPATRIMONIO"></span>
            </div>
          </template>
        </div>
      </div>
      <span x-show="loading" class="text-sm text-gray-500">Buscando...</span>
    </div>
    <div>
      <x-input-label for="NUMOF" value="Nº OC" />
      <x-text-input data-index="2" x-model="formData.NUMOF" id="NUMOF" name="NUMOF" type="number" class="mt-0.5 block w-full" />
    </div>
    <div>
      <x-input-label for="campo_extra" value="-" />
      <x-text-input data-index="3" id="campo_extra" name="campo_extra" type="text" class="mt-0.5 block w-full" disabled />
    </div>
  </div>

  {{-- GRUPO 2: Código e Descrição --}}
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4 md:gap-5">
    <div class="md:col-span-1">
      <x-input-label for="CODOBJETO" value="Código *" />
      <div class="relative mt-0.5" @click.away="showCodigoDropdown=false">
        <input id="CODOBJETO"
          x-model="codigoSearch"
          @focus="abrirDropdownCodigos()"
          @input.debounce.300ms="buscarCodigos"
          @keydown.down.prevent="navegarCodigos(1)"
          @keydown.up.prevent="navegarCodigos(-1)"
          @keydown.enter.prevent="selecionarCodigoEnter()"
          @keydown.escape.prevent="showCodigoDropdown=false"
          name="CODOBJETO"
          type="text"
          inputmode="numeric"
          class="block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm pr-10"
          placeholder="Digite nº ou descrição" required />
        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
          <div class="flex items-center gap-2">
            <button type="button" x-show="formData.CODOBJETO" @click="limparCodigo" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none" title="Limpar seleção" aria-label="Limpar seleção">✕</button>
            <button type="button" @click="abrirDropdownCodigos(true)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 focus:outline-none" title="Abrir lista" aria-label="Abrir lista">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
              </svg>
            </button>
          </div>
        </div>
        <div x-show="showCodigoDropdown" x-transition class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-64 overflow-y-auto text-sm">
          <template x-if="loadingCodigos">
            <div class="p-2 text-gray-500">Buscando...</div>
          </template>
          <template x-if="!loadingCodigos && codigosLista.length === 0">
            <div class="p-2 text-gray-500" x-text="codigoSearch.trim()==='' ? 'Digite para buscar' : 'Nenhum resultado'"></div>
          </template>
          <template x-for="(c,i) in codigosLista" :key="c.CODOBJETO">
            <div data-cod-item @click="selecionarCodigo(c)" @mouseover="highlightedCodigoIndex=i" :class="['px-3 py-2 cursor-pointer', highlightedCodigoIndex===i ? 'bg-indigo-100 dark:bg-gray-700' : 'hover:bg-indigo-50 dark:hover:bg-gray-700']">
              <span class="font-mono text-xs text-indigo-600 dark:text-indigo-400" x-text="c.CODOBJETO"></span>
              <span class="ml-2" x-text="' - ' + c.DESCRICAO"></span>
            </div>
          </template>
        </div>
      </div>
    </div>
    <div class="md:col-span-3">
      <x-input-label for="DEPATRIMONIO" value="Descrição do Código" />
      <x-text-input data-index="5" x-model="formData.DEPATRIMONIO" id="DEPATRIMONIO" name="DEPATRIMONIO" type="text" class="mt-0.5 block w-full bg-gray-100 dark:bg-gray-900" readonly />
    </div>
  </div>

  {{-- GRUPO 3: Observação --}}
  <div>
    <x-input-label for="DEHISTORICO" value="Observação" />
    <textarea data-index="6" x-model="formData.DEHISTORICO" id="DEHISTORICO" name="DEHISTORICO" rows="2" class="block mt-0.5 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm"></textarea>
  </div>

  {{-- GRUPO 4: Projeto, Local e Cód. Termo --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-5">
    <div class="md:col-span-2">
      <x-input-label for="CDPROJETO" value="Projeto" />
      <div class="flex items-center space-x-2 relative" @click.away="showProjetoDropdown=false">
        <div class="relative w-1/3 mt-0.5">
          <input id="CDPROJETO" name="CDPROJETO" x-model="projetoSearch"
            @focus="abrirDropdownProjetos()"
            @input.debounce.300ms="buscarProjetos"
            @keydown.down.prevent="navegarProjetos(1)"
            @keydown.up.prevent="navegarProjetos(-1)"
            @keydown.enter.prevent="selecionarProjetoEnter()"
            @keydown.escape.prevent="showProjetoDropdown=false"
            type="text" inputmode="numeric"
            class="block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm pr-10"
            placeholder="Código" />
          <div class="absolute inset-y-0 right-0 flex items-center pr-3">
            <div class="flex items-center gap-2">
              <button type="button" x-show="formData.CDPROJETO" @click="limparProjeto" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="Limpar seleção">✕</button>
              <button type="button" @click="abrirDropdownProjetos(true)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" title="Abrir lista" aria-label="Abrir lista">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                </svg>
              </button>
            </div>
          </div>
          <div x-show="showProjetoDropdown" x-transition class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-56 overflow-y-auto text-sm">
            <template x-if="loadingProjetos">
              <div class="p-2 text-gray-500">Buscando...</div>
            </template>
            <template x-if="!loadingProjetos && projetosLista.length===0">
              <div class="p-2 text-gray-500" x-text="projetoSearch.trim()==='' ? 'Digite para buscar' : 'Nenhum resultado'"></div>
            </template>
            <template x-for="(pr,i) in projetosLista" :key="pr.CDPROJETO">
              <div data-proj-item @click="selecionarProjeto(pr)" @mouseover="highlightedProjetoIndex=i" :class="['px-3 py-2 cursor-pointer', highlightedProjetoIndex===i ? 'bg-indigo-100 dark:bg-gray-700' : 'hover:bg-indigo-50 dark:hover:bg-gray-700']">
                <span class="font-mono text-xs text-indigo-600 dark:text-indigo-400" x-text="pr.CDPROJETO"></span>
                <span class="ml-2" x-text="' - ' + pr.NOMEPROJETO"></span>
              </div>
            </template>
          </div>
        </div>
        <x-text-input x-model="nomeProjeto" type="text" class="mt-0.5 block w-2/3 bg-gray-100 dark:bg-gray-900" placeholder="Nome do Projeto" readonly />
      </div>
    </div>
    <div>
      <x-input-label for="NMPLANTA" value="Cód Termo" />
      <x-text-input data-index="8" x-model="formData.NMPLANTA" id="NMPLANTA" name="NMPLANTA" type="number" class="mt-0.5 block w-full" />
    </div>
    <div class="md:col-span-3">
      <x-input-label for="CDLOCAL" value="Local" />
      <div class="flex gap-2 items-start">
        <div class="flex-1 relative mt-0.5" @click.away="showLocalDropdown=false">
          <input id="CDLOCAL" name="CDLOCAL" x-model="localSearch"
            @focus="abrirDropdownLocais()"
            @input.debounce.300ms="filtrarLocais"
            @keydown.down.prevent="navegarLocais(1)"
            @keydown.up.prevent="navegarLocais(-1)"
            @keydown.enter.prevent="selecionarLocalEnter()"
            @keydown.escape.prevent="showLocalDropdown=false"
            :disabled="locais.length===0"
            class="block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm pr-10 disabled:opacity-60"
            placeholder="Local" />
          <input type="hidden" :value="formData.CDLOCAL" />
          <div class="absolute inset-y-0 right-0 flex items-center pr-3">
            <div class="flex items-center gap-2">
              <button type="button" x-show="formData.CDLOCAL" @click="limparLocal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="Limpar seleção">✕</button>
              <button type="button" @click="abrirDropdownLocais(true)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" title="Abrir lista" aria-label="Abrir lista">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                </svg>
              </button>
            </div>
          </div>
          <div x-show="showLocalDropdown" x-transition class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-56 overflow-y-auto text-sm">
            <template x-if="locaisFiltrados.length===0">
              <div class="p-2 text-gray-500" x-text="localSearch.trim()==='' ? (locais.length===0 ? 'Carregue um projeto' : 'Digite para filtrar') : 'Nenhum resultado'"></div>
            </template>
            <template x-for="(l,i) in locaisFiltrados" :key="l.id">
              <div data-local-item @click="selecionarLocal(l)" @mouseover="highlightedLocalIndex=i" :class="['px-3 py-2 cursor-pointer', highlightedLocalIndex===i ? 'bg-indigo-100 dark:bg-gray-700' : 'hover:bg-indigo-50 dark:hover:bg-gray-700']">
                <span class="text-xs text-indigo-600 dark:text-indigo-400" x-text="l.LOCAL"></span>
              </div>
            </template>
          </div>
        </div>
        <button type="button" @click="abrirNovoLocal()" class="mt-0.5 inline-flex items-center justify-center w-9 h-9 rounded-md bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2" title="Cadastrar novo local" aria-label="Cadastrar novo local">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
          </svg>
        </button>
      </div>
      <!-- Mini modal / popover cadastro local -->
      <div x-show="novoLocalOpen" x-transition @keydown.escape.window="fecharNovoLocal" class="relative">
        <div class="absolute z-50 mt-2 w-72 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg shadow-xl p-3">
          <div class="flex justify-between items-center mb-1">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Novo Local</h4>
            <button type="button" class="text-gray-400 hover:text-gray-600" @click="fecharNovoLocal">✕</button>
          </div>
          <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Nome do Local *</label>
          <input type="text" x-model="novoLocalNome" @keydown.enter.prevent="salvarNovoLocal" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md text-sm mt-0.5" placeholder="Ex: Almoxarifado" />
          <p class="text-xs text-red-500 mt-1" x-text="novoLocalErro"></p>
          <div class="mt-2 flex justify-end gap-2">
            <button type="button" @click="fecharNovoLocal" class="px-2 py-1 text-xs rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Cancelar</button>
            <button type="button" @click="salvarNovoLocal" class="px-2.5 py-1 text-xs rounded bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50" :disabled="salvandoNovoLocal">
              <span x-show="!salvandoNovoLocal">Salvar</span>
              <span x-show="salvandoNovoLocal">Salvando...</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- GRUPO 5: Marca, Modelo, Situação, Matrícula / Usuário --}}
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4 md:gap-5 pt-5 border-t border-gray-200 dark:border-gray-700">
    <div>
      <x-input-label for="MARCA" value="Marca" />
      <x-text-input data-index="11" x-model="formData.MARCA" id="MARCA" name="MARCA" type="text" class="mt-0.5 block w-full" />
    </div>
    <div>
      <x-input-label for="MODELO" value="Modelo" />
      <x-text-input data-index="12" x-model="formData.MODELO" id="MODELO" name="MODELO" type="text" class="mt-0.5 block w-full" />
    </div>
    <div>
      <x-input-label for="SITUACAO" value="Situação *" />
      <select data-index="13" x-model="formData.SITUACAO" id="SITUACAO" name="SITUACAO" class="block w-full mt-0.5 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm" required>
        <option value="EM USO">EM USO</option>
        <option value="CONSERTO">CONSERTO</option>
        <option value="BAIXA">BAIXA</option>
        <option value="À DISPOSIÇÃO">À DISPOSIÇÃO</option>
      </select>
      <x-input-error class="mt-2" :messages="$errors->get('SITUACAO')" />
    </div>
    <div class="relative" @click.away="showUserDropdown=false">
      <x-input-label for="matricula_busca" value="Matrícula Responsável *" />
      <div class="relative mt-0.5">
        <input id="matricula_busca"
          x-model="userSearch"
          @focus="abrirDropdownUsuarios()"
          @input.debounce.300ms="buscarUsuarios"
          @keydown.down.prevent="navegarUsuarios(1)"
          @keydown.up.prevent="navegarUsuarios(-1)"
          @keydown.enter.prevent="selecionarUsuarioEnter()"
          @keydown.escape.prevent="showUserDropdown=false"
          type="text"
          placeholder="Digite matrícula ou nome"
          class="block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm pr-10"
          autocomplete="off" />
        <input type="hidden" name="CDMATRFUNCIONARIO" :value="formData.CDMATRFUNCIONARIO" />
        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
          <div class="flex items-center gap-2">
            <button type="button" x-show="formData.CDMATRFUNCIONARIO" @click="limparUsuario" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none" title="Limpar seleção" aria-label="Limpar seleção">✕</button>
            <button type="button" @click="abrirDropdownUsuarios(true)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 focus:outline-none" title="Abrir lista" aria-label="Abrir lista">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
              </svg>
            </button>
          </div>
        </div>
      </div>
      <div x-show="showUserDropdown" x-transition class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-60 overflow-y-auto text-sm">
        <template x-if="loadingUsers">
          <div class="p-2 text-gray-500">Buscando...</div>
        </template>
        <template x-if="!loadingUsers && usuarios.length === 0">
          <div class="p-2 text-gray-500" x-text="userSearch.trim()==='' ? 'Digite para buscar' : 'Nenhum resultado'"></div>
        </template>
        <template x-for="(u, i) in usuarios" :key="u.CDMATRFUNCIONARIO">
          <div data-user-item @click="selecionarUsuario(u)"
            @mouseover="highlightedUserIndex = i"
            :class="['px-3 py-2 cursor-pointer', highlightedUserIndex === i ? 'bg-indigo-100 dark:bg-gray-700' : 'hover:bg-indigo-50 dark:hover:bg-gray-700']">
            <span class="font-mono text-xs text-indigo-600 dark:text-indigo-400" x-text="u.CDMATRFUNCIONARIO"></span>
            <span class="ml-2" x-text="' - ' + u.NOMEUSER"></span>
          </div>
        </template>
      </div>
      <p class="mt-1 text-xs text-gray-500" x-show="formData.CDMATRFUNCIONARIO && userSelectedName">Selecionado: <span class="font-semibold" x-text="userSelectedName"></span></p>
      <x-input-error class="mt-2" :messages="$errors->get('CDMATRFUNCIONARIO')" />
    </div>
  </div>

  {{-- GRUPO 6: Datas --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-5">
    <div>
      <x-input-label for="DTAQUISICAO" value="Data de Aquisição" />
      <x-text-input data-index="14" x-model="formData.DTAQUISICAO" id="DTAQUISICAO" name="DTAQUISICAO" type="date" class="mt-0.5 block w-full" />
    </div>
    <div>
      <x-input-label for="DTBAIXA" value="Data de Baixa" />
      <x-text-input data-index="15" x-model="formData.DTBAIXA" id="DTBAIXA" name="DTBAIXA" type="date" class="mt-0.5 block w-full" />
      <x-input-error class="mt-2" :messages="$errors->get('DTBAIXA')" />
    </div>
  </div>

  {{-- MODAL DE PESQUISA (Não mexe na estrutura, fica no final) --}}
  <div x-show="searchModalOpen" x-cloak @keydown.window.escape="closeSearchModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div @click.away="closeSearchModal" class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-lg p-6">
      <h3 class="text-lg font-semibold mb-4">Pesquisar Patrimônio</h3>
      <input x-model="searchTerm" @input.debounce.300ms="search" type="text" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm" placeholder="Digite o nº ou descrição para buscar...">
      <ul class="mt-4 max-h-60 overflow-y-auto">
        <template x-for="item in searchResults" :key="item.NUSEQPATR">
          <li @click="selectPatrimonio(item)" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer border-b dark:border-gray-600">
            <span class="font-bold" x-text="item.NUPATRIMONIO"></span> - <span x-text="item.DEPATRIMONIO"></span>
          </li>
        </template>
        <template x-if="!loadingSearch && searchResults.length === 0 && searchTerm !== ''">
          <li class="p-2 text-gray-500">Nenhum resultado encontrado.</li>
        </template>
        <template x-if="loadingSearch">
          <li class="p-2 text-gray-500">Buscando...</li>
        </template>
      </ul>
      <div class="mt-4 text-right">
        <button @click="closeSearchModal" type="button" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-100">Fechar</button>
      </div>
    </div>
  </div>
</div>
<script>
  function patrimonioForm(config) {
    return {
      // == DADOS DO FORMULÁRIO ==
      formData: {
        NUPATRIMONIO: (config.old?.NUPATRIMONIO ?? config.patrimonio?.NUPATRIMONIO) || '',
        NUMOF: (config.old?.NUMOF ?? config.patrimonio?.NUMOF) || '',
        CODOBJETO: (config.old?.CODOBJETO ?? config.patrimonio?.CODOBJETO) || '',
        DEPATRIMONIO: (config.old?.DEPATRIMONIO ?? config.patrimonio?.DEPATRIMONIO) || '',
        DEHISTORICO: (config.old?.DEHISTORICO ?? config.patrimonio?.DEHISTORICO) || '',
        CDPROJETO: (config.old?.CDPROJETO ?? config.patrimonio?.CDPROJETO) || '',
        CDLOCAL: (config.old?.CDLOCAL ?? config.patrimonio?.CDLOCAL) || '',
        NMPLANTA: (config.old?.NMPLANTA ?? config.patrimonio?.NMPLANTA) || '',
        MARCA: (config.old?.MARCA ?? config.patrimonio?.MARCA) || '',
        MODELO: (config.old?.MODELO ?? config.patrimonio?.MODELO) || '',
        SITUACAO: (config.old?.SITUACAO ?? config.patrimonio?.SITUACAO) || 'EM USO',
        DTAQUISICAO: (config.old?.DTAQUISICAO ?? (config.patrimonio?.DTAQUISICAO ? config.patrimonio.DTAQUISICAO.split(' ')[0] : '')),
        DTBAIXA: (config.old?.DTBAIXA ?? (config.patrimonio?.DTBAIXA ? config.patrimonio.DTBAIXA.split(' ')[0] : '')),
        CDMATRFUNCIONARIO: (config.old?.CDMATRFUNCIONARIO ?? config.patrimonio?.CDMATRFUNCIONARIO) || '',
      },
      // == ESTADO DA UI ==
      loading: false,
      searchModalOpen: false,
      searchTerm: '',
      searchResults: [],
      loadingSearch: false,
      nomeProjeto: '',
      locais: [],
      // Autocomplete Usuário
      userSearch: '',
      usuarios: [],
      highlightedUserIndex: -1,
      loadingUsers: false,
      showUserDropdown: false,
      userSelectedName: '',
      // Autocomplete Código
      codigoSearch: (config.old?.CODOBJETO ?? config.patrimonio?.CODOBJETO) || '',
      codigosLista: [],
      loadingCodigos: false,
      showCodigoDropdown: false,
      highlightedCodigoIndex: -1,
      // Autocomplete Projeto
      projetoSearch: (config.old?.CDPROJETO ?? config.patrimonio?.CDPROJETO) || '',
      projetosLista: [],
      loadingProjetos: false,
      showProjetoDropdown: false,
      highlightedProjetoIndex: -1,
      // Autocomplete Local
      localSearch: '',
      locaisFiltrados: [],
      showLocalDropdown: false,
      highlightedLocalIndex: -1,
      // Autocomplete Patrimônio
      patSearch: (config.old?.NUPATRIMONIO ?? config.patrimonio?.NUPATRIMONIO) || '',
      patrimoniosLista: [],
      loadingPatrimonios: false,
      showPatDropdown: false,
      highlightedPatIndex: -1,
      // Novo Local
      novoLocalOpen: false,
      novoLocalNome: '',
      novoLocalErro: '',
      salvandoNovoLocal: false,

      // == FUNÇÕES ==
      openSearchModal() {
        this.searchModalOpen = true;
        this.search();
      },
      closeSearchModal() {
        this.searchModalOpen = false;
        this.searchTerm = '';
        this.searchResults = [];
      },
      async search() {
        this.loadingSearch = true;
        try {
          const response = await fetch(`/api/patrimonios/pesquisar?q=${this.searchTerm}`);
          if (response.ok) this.searchResults = await response.json();
        } catch (error) {
          console.error('Erro na pesquisa:', error);
        } finally {
          this.loadingSearch = false;
        }
      },
      selectPatrimonio(item) {
        this.formData.NUPATRIMONIO = item.NUPATRIMONIO;
        this.buscarPatrimonio();
        this.closeSearchModal();
      },
      async buscarPatrimonio() {
        if (!this.formData.NUPATRIMONIO) return;
        this.loading = true;
        try {
          const response = await fetch(`/api/patrimonios/buscar/${this.formData.NUPATRIMONIO}`);
          if (response.ok) {
            const data = await response.json();
            Object.keys(this.formData).forEach(key => {
              if (data.hasOwnProperty(key) && data[key] !== null) {
                if (key.startsWith('DT')) this.formData[key] = data[key].split(' ')[0];
                else this.formData[key] = data[key];
              }
            });
            if (this.formData.CDPROJETO) {
              await this.buscarProjetoELocais();
              this.formData.CDLOCAL = data.CDLOCAL;
            }
          } else {
            const numPatrimonio = this.formData.NUPATRIMONIO;
            Object.keys(this.formData).forEach(key => {
              if (key !== 'NUPATRIMONIO') this.formData[key] = ''
            });
            this.formData.NUPATRIMONIO = numPatrimonio;
          }
        } catch (error) {
          console.error('Erro ao buscar patrimônio:', error);
        } finally {
          this.loading = false;
        }
      },
      async buscarDescricaoCodigo() {
        this.formData.DEPATRIMONIO = 'Buscando...';
        if (!this.formData.CODOBJETO) {
          this.formData.DEPATRIMONIO = '';
          return;
        }
        try {
          const response = await fetch(`/api/codigos/buscar/${this.formData.CODOBJETO}`);
          if (response.ok) {
            const data = await response.json();
            this.formData.DEPATRIMONIO = data?.descricao ?? '';
          } else {
            this.formData.DEPATRIMONIO = 'Código não encontrado.';
          }
        } catch (error) {
          this.formData.DEPATRIMONIO = 'Erro na busca.';
        }
      },
      async buscarProjetoELocais() {
        this.nomeProjeto = 'Buscando...';
        this.locais = [];
        if (!this.formData.CDPROJETO) {
          this.nomeProjeto = '';
          return;
        }
        try {
          const projResponse = await fetch(`/api/projetos/buscar/${this.formData.CDPROJETO}`);
          this.nomeProjeto = projResponse.ok ? (await projResponse.json()).NOMEPROJETO : 'Projeto não encontrado';
        } catch (error) {
          this.nomeProjeto = 'Erro na busca';
        }
        try {
          const locaisResponse = await fetch(`/api/locais/${this.formData.CDPROJETO}`);
          if (locaisResponse.ok) this.locais = await locaisResponse.json();
        } catch (error) {
          console.error('Erro ao buscar locais:', error);
        }
      },
      async buscarUsuarios() { // agora busca funcionarios
        const termo = this.userSearch.trim();
        if (termo === '') {
          this.usuarios = [];
          this.highlightedUserIndex = -1;
          return;
        }
        this.loadingUsers = true;
        try {
          const resp = await fetch(`/api/funcionarios/pesquisar?q=${encodeURIComponent(termo)}`);
          if (resp.ok) {
            // Normalizamos campos para manter nomenclatura existente (NOMEUSER -> NMFUNCIONARIO)
            const data = await resp.json();
            this.usuarios = data.map(f => ({
              CDMATRFUNCIONARIO: f.CDMATRFUNCIONARIO,
              NOMEUSER: f.NMFUNCIONARIO,
              _origem: 'funcionario'
            }));
            this.highlightedUserIndex = this.usuarios.length > 0 ? 0 : -1;
          }
        } catch (e) {
          console.error('Falha busca funcionários', e);
        } finally {
          this.loadingUsers = false;
        }
      },
      abrirDropdownUsuarios(force = false) {
        this.showUserDropdown = true;
        // Se já tem texto, busca. Se vazio e for forçado (clique na lupa), não busca mas mostra mensagem.
        if (this.userSearch.trim() !== '') {
          this.buscarUsuarios();
        }
      },
      selecionarUsuario(u) {
        this.formData.CDMATRFUNCIONARIO = u.CDMATRFUNCIONARIO;
        this.userSelectedName = `${u.CDMATRFUNCIONARIO} - ${u.NOMEUSER}`;
        this.userSearch = this.userSelectedName;
        this.showUserDropdown = false;
      },
      selecionarUsuarioEnter() {
        if (!this.showUserDropdown) return;
        if (this.highlightedUserIndex < 0 || this.highlightedUserIndex >= this.usuarios.length) return;
        this.selecionarUsuario(this.usuarios[this.highlightedUserIndex]);
      },
      limparUsuario() {
        this.formData.CDMATRFUNCIONARIO = '';
        this.userSelectedName = '';
        this.userSearch = '';
        this.usuarios = [];
        this.showUserDropdown = true;
        this.highlightedUserIndex = -1;
      },
      navegarUsuarios(delta) {
        if (!this.showUserDropdown || this.usuarios.length === 0) return;
        const max = this.usuarios.length - 1;
        if (this.highlightedUserIndex === -1) {
          this.highlightedUserIndex = 0;
        } else {
          this.highlightedUserIndex = Math.min(max, Math.max(0, this.highlightedUserIndex + delta));
        }
        // Scroll into view
        this.$nextTick(() => {
          const list = this.$root.querySelector('[x-show="showUserDropdown"]');
          if (!list) return;
          const items = list.querySelectorAll('[data-user-item]');
          const el = items[this.highlightedUserIndex];
          if (el && typeof el.scrollIntoView === 'function') {
            const parentRect = list.getBoundingClientRect();
            const elRect = el.getBoundingClientRect();
            if (elRect.top < parentRect.top || elRect.bottom > parentRect.bottom) {
              el.scrollIntoView({
                block: 'nearest'
              });
            }
          }
        });
      },
      // === Autocomplete Patrimônio ===
      async buscarPatrimonios() {
        const termo = this.patSearch.trim();
        if (termo === '') {
          this.patrimoniosLista = [];
          this.highlightedPatIndex = -1;
          return;
        }
        this.loadingPatrimonios = true;
        try {
          const resp = await fetch(`/api/patrimonios/pesquisar?q=${encodeURIComponent(termo)}`);
          if (resp.ok) {
            this.patrimoniosLista = await resp.json();
            this.highlightedPatIndex = this.patrimoniosLista.length > 0 ? 0 : -1;
          }
        } catch (e) {
          console.error('Falha busca patrimonios', e);
        } finally {
          this.loadingPatrimonios = false;
        }
      },
      // === Autocomplete Código ===
      async buscarCodigos() {
        const termo = this.codigoSearch.trim();
        if (termo === '') {
          this.codigosLista = [];
          this.highlightedCodigoIndex = -1;
          return;
        }
        this.loadingCodigos = true;
        try {
          const resp = await fetch(`/api/codigos/pesquisar?q=${encodeURIComponent(termo)}`);
          if (resp.ok) {
            this.codigosLista = await resp.json();
            this.highlightedCodigoIndex = this.codigosLista.length > 0 ? 0 : -1;
          }
        } catch (e) {
          console.error('Falha busca codigos', e);
        } finally {
          this.loadingCodigos = false;
        }
      },
      abrirDropdownCodigos(force = false) {
        this.showCodigoDropdown = true;
        if (this.codigoSearch.trim() !== '') this.buscarCodigos();
      },
      selecionarCodigo(c) {
        this.formData.CODOBJETO = c.CODOBJETO;
        this.codigoSearch = c.CODOBJETO;
        this.formData.DEPATRIMONIO = c.DESCRICAO;
        this.showCodigoDropdown = false;
      },
      selecionarCodigoEnter() {
        if (!this.showCodigoDropdown) return;
        if (this.highlightedCodigoIndex < 0 || this.highlightedCodigoIndex >= this.codigosLista.length) return;
        this.selecionarCodigo(this.codigosLista[this.highlightedCodigoIndex]);
      },
      limparCodigo() {
        this.formData.CODOBJETO = '';
        this.codigoSearch = '';
        this.formData.DEPATRIMONIO = '';
        this.codigosLista = [];
        this.highlightedCodigoIndex = -1;
        this.showCodigoDropdown = true;
      },
      navegarCodigos(delta) {
        if (!this.showCodigoDropdown || this.codigosLista.length === 0) return;
        const max = this.codigosLista.length - 1;
        if (this.highlightedCodigoIndex === -1) this.highlightedCodigoIndex = 0;
        else this.highlightedCodigoIndex = Math.min(max, Math.max(0, this.highlightedCodigoIndex + delta));
        this.$nextTick(() => {
          const list = this.$root.querySelector('[x-show="showCodigoDropdown"]');
          if (!list) return;
          const items = list.querySelectorAll('[data-cod-item]');
          const el = items[this.highlightedCodigoIndex];
          if (el && el.scrollIntoView) {
            const parentRect = list.getBoundingClientRect();
            const elRect = el.getBoundingClientRect();
            if (elRect.top < parentRect.top || elRect.bottom > parentRect.bottom) {
              el.scrollIntoView({
                block: 'nearest'
              });
            }
          }
        });
      },
      // === Autocomplete Projeto ===
      async buscarProjetos() {
        const termo = this.projetoSearch.trim();
        if (termo === '') {
          this.projetosLista = [];
          this.highlightedProjetoIndex = -1;
          return;
        }
        this.loadingProjetos = true;
        try {
          const resp = await fetch(`/api/projetos/pesquisar?q=${encodeURIComponent(termo)}`);
          if (resp.ok) {
            this.projetosLista = await resp.json();
            this.highlightedProjetoIndex = this.projetosLista.length > 0 ? 0 : -1;
          }
        } catch (e) {
          console.error('Falha busca projetos', e);
        } finally {
          this.loadingProjetos = false;
        }
      },
      abrirDropdownProjetos(force = false) {
        this.showProjetoDropdown = true;
        if (this.projetoSearch.trim() !== '') this.buscarProjetos();
      },
      async selecionarProjeto(p) {
        this.formData.CDPROJETO = p.CDPROJETO;
        this.projetoSearch = p.CDPROJETO;
        this.nomeProjeto = p.NOMEPROJETO;
        this.showProjetoDropdown = false;
        await this.buscarProjetoELocais();
        this.localSearch = '';
        this.locaisFiltrados = this.locais.slice(0, 50);
      },
      selecionarProjetoEnter() {
        if (!this.showProjetoDropdown) return;
        if (this.highlightedProjetoIndex < 0 || this.highlightedProjetoIndex >= this.projetosLista.length) return;
        this.selecionarProjeto(this.projetosLista[this.highlightedProjetoIndex]);
      },
      limparProjeto() {
        this.formData.CDPROJETO = '';
        this.projetoSearch = '';
        this.nomeProjeto = '';
        this.locais = [];
        this.locaisFiltrados = [];
        this.highlightedProjetoIndex = -1;
        this.showProjetoDropdown = true;
      },
      navegarProjetos(delta) {
        if (!this.showProjetoDropdown || this.projetosLista.length === 0) return;
        const max = this.projetosLista.length - 1;
        if (this.highlightedProjetoIndex === -1) this.highlightedProjetoIndex = 0;
        else this.highlightedProjetoIndex = Math.min(max, Math.max(0, this.highlightedProjetoIndex + delta));
        this.$nextTick(() => {
          const list = this.$root.querySelector('[x-show="showProjetoDropdown"]');
          if (!list) return;
          const items = list.querySelectorAll('[data-proj-item]');
          const el = items[this.highlightedProjetoIndex];
          if (el && el.scrollIntoView) {
            const pr = list.getBoundingClientRect();
            const er = el.getBoundingClientRect();
            if (er.top < pr.top || er.bottom > pr.bottom) {
              el.scrollIntoView({
                block: 'nearest'
              });
            }
          }
        });
      },
      // === Autocomplete Local ===
      abrirDropdownLocais(force = false) {
        if (this.locais.length === 0) return;
        this.showLocalDropdown = true;
        this.filtrarLocais();
      },
      filtrarLocais() {
        const termo = this.localSearch.trim().toLowerCase();
        if (termo === '') {
          this.locaisFiltrados = this.locais.slice(0, 100);
        } else {
          this.locaisFiltrados = this.locais.filter(l => l.LOCAL.toLowerCase().includes(termo)).slice(0, 100);
        }
        this.highlightedLocalIndex = this.locaisFiltrados.length > 0 ? 0 : -1;
      },
      selecionarLocal(l) {
        this.formData.CDLOCAL = l.id;
        this.localSearch = l.LOCAL;
        this.showLocalDropdown = false;
      },
      selecionarLocalEnter() {
        if (!this.showLocalDropdown) return;
        if (this.highlightedLocalIndex < 0 || this.highlightedLocalIndex >= this.locaisFiltrados.length) return;
        this.selecionarLocal(this.locaisFiltrados[this.highlightedLocalIndex]);
      },
      limparLocal() {
        this.formData.CDLOCAL = '';
        this.localSearch = '';
        this.locaisFiltrados = this.locais.slice(0, 100);
        this.highlightedLocalIndex = -1;
        this.showLocalDropdown = true;
      },
      navegarLocais(delta) {
        if (!this.showLocalDropdown || this.locaisFiltrados.length === 0) return;
        const max = this.locaisFiltrados.length - 1;
        if (this.highlightedLocalIndex === -1) this.highlightedLocalIndex = 0;
        else this.highlightedLocalIndex = Math.min(max, Math.max(0, this.highlightedLocalIndex + delta));
        this.$nextTick(() => {
          const list = this.$root.querySelector('[x-show="showLocalDropdown"]');
          if (!list) return;
          const items = list.querySelectorAll('[data-local-item]');
          const el = items[this.highlightedLocalIndex];
          if (el && el.scrollIntoView) {
            const pr = list.getBoundingClientRect();
            const er = el.getBoundingClientRect();
            if (er.top < pr.top || er.bottom > pr.bottom) {
              el.scrollIntoView({
                block: 'nearest'
              });
            }
          }
        });
      },
      abrirDropdownPatrimonios(force = false) {
        this.showPatDropdown = true;
        if (this.patSearch.trim() !== '') {
          this.buscarPatrimonios();
        }
      },
      selecionarPatrimonio(p) {
        this.formData.NUPATRIMONIO = p.NUPATRIMONIO;
        this.patSearch = p.NUPATRIMONIO;
        this.showPatDropdown = false;
        this.buscarPatrimonio();
      },
      selecionarPatrimonioEnter() {
        if (!this.showPatDropdown) return;
        if (this.highlightedPatIndex < 0 || this.highlightedPatIndex >= this.patrimoniosLista.length) return;
        this.selecionarPatrimonio(this.patrimoniosLista[this.highlightedPatIndex]);
      },
      limparPatrimonio() {
        this.formData.NUPATRIMONIO = '';
        this.patSearch = '';
        this.patrimoniosLista = [];
        this.highlightedPatIndex = -1;
        this.showPatDropdown = true;
      },
      navegarPatrimonios(delta) {
        if (!this.showPatDropdown || this.patrimoniosLista.length === 0) return;
        const max = this.patrimoniosLista.length - 1;
        if (this.highlightedPatIndex === -1) {
          this.highlightedPatIndex = 0;
        } else {
          this.highlightedPatIndex = Math.min(max, Math.max(0, this.highlightedPatIndex + delta));
        }
        this.$nextTick(() => {
          const list = this.$root.querySelector('[x-show="showPatDropdown"]');
          if (!list) return;
          const items = list.querySelectorAll('[data-pat-item]');
          const el = items[this.highlightedPatIndex];
          if (el && typeof el.scrollIntoView === 'function') {
            const parentRect = list.getBoundingClientRect();
            const elRect = el.getBoundingClientRect();
            if (elRect.top < parentRect.top || elRect.bottom > parentRect.bottom) {
              el.scrollIntoView({
                block: 'nearest'
              });
            }
          }
        });
      },
      abrirNovoLocal() {
        if (!this.formData.CDPROJETO) {
          alert('Informe um projeto antes de cadastrar o local.');
          return;
        }
        this.novoLocalOpen = true;
        this.novoLocalNome = '';
        this.novoLocalErro = '';
        this.$nextTick(() => {
          const el = document.querySelector('input[x-model="novoLocalNome"]');
          el?.focus();
        });
      },
      fecharNovoLocal() {
        this.novoLocalOpen = false;
      },
      async salvarNovoLocal() {
        if (!this.novoLocalNome.trim()) {
          this.novoLocalErro = 'Digite o nome do local';
          return;
        }
        this.salvandoNovoLocal = true;
        this.novoLocalErro = '';
        try {
          const resp = await fetch(`/api/locais/${this.formData.CDPROJETO}`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
              delocal: this.novoLocalNome
            })
          });
          if (resp.ok) {
            const novo = await resp.json();
            this.locais.push(novo);
            this.formData.CDLOCAL = novo.id;
            this.fecharNovoLocal();
          } else {
            const err = await resp.json().catch(() => ({}));
            this.novoLocalErro = err.error || 'Erro ao salvar.';
          }
        } catch (e) {
          this.novoLocalErro = 'Falha na requisição.';
        } finally {
          this.salvandoNovoLocal = false;
        }
      },
      focusNext(currentElement) {
        const focusable = Array.from(currentElement.closest('form').querySelectorAll('input:not([readonly]):not([disabled]), select:not([readonly]):not([disabled]), textarea:not([readonly]):not([disabled])'));
        const currentIndex = focusable.indexOf(currentElement);
        const nextElement = focusable[currentIndex + 1];
        if (nextElement) {
          nextElement.focus();
        } else {
          currentElement.closest('form').querySelector('button[type="submit"]')?.focus();
        }
      },
      async init() {
        // Se já existe projeto nos dados atuais (inclusive old()), carrega nome do projeto e locais
        if (this.formData.CDPROJETO) {
          const targetCdLocal = this.formData.CDLOCAL;
          await this.buscarProjetoELocais();
          if (targetCdLocal) this.formData.CDLOCAL = targetCdLocal;
        }
        // Pré-carregar nome do responsável (funcionário) se edição
        if (this.formData.CDMATRFUNCIONARIO) {
          try {
            const r = await fetch(`/api/funcionarios/pesquisar?q=${this.formData.CDMATRFUNCIONARIO}`);
            if (r.ok) {
              const lista = await r.json();
              const f = lista.find(x => String(x.CDMATRFUNCIONARIO) === String(this.formData.CDMATRFUNCIONARIO));
              if (f) {
                this.userSelectedName = `${f.CDMATRFUNCIONARIO} - ${f.NMFUNCIONARIO}`;
                this.userSearch = this.userSelectedName;
              }
            }
          } catch (e) {
            /* silencioso */
          }
        }
        // Se situação for BAIXA e não houver data, sugere hoje (apenas UX; ainda valida no backend)
        this.$watch('formData.SITUACAO', (val) => {
          const dt = document.getElementById('DTBAIXA');
          if (val === 'BAIXA') {
            dt?.setAttribute('required', 'required');
            // Se vazio, preenche com a data de hoje (YYYY-MM-DD)
            if (!this.formData.DTBAIXA) {
              const today = new Date();
              const yyyy = today.getFullYear();
              const mm = String(today.getMonth() + 1).padStart(2, '0');
              const dd = String(today.getDate()).padStart(2, '0');
              this.formData.DTBAIXA = `${yyyy}-${mm}-${dd}`;
            }
          } else {
            dt?.removeAttribute('required');
          }
        });
      }
    }
  }
</script>