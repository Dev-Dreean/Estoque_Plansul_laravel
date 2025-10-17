@props(['patrimonio' => null])

@if ($errors->any())
<div class="mb-3 bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded-lg relative text-sm" role="alert">
  <strong class="font-bold">Opa! Algo deu errado.</strong>
  <ul class="mt-1 list-disc list-inside text-xs">
    @foreach ($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
  </ul>
</div>
@endif

<div x-data='patrimonioForm({ patrimonio: @json($patrimonio), old: @json(old()) })' @keydown.enter.prevent="handleEnter($event)" class="space-y-4 text-sm">

  {{-- GRUPO 1: N° Patrimônio, N° OC, Campo Vazio --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
      <label for="NUPATRIMONIO" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Número do Patrimônio *</label>
      <div class="relative" @click.away="showPatDropdown=false">
        <input id="NUPATRIMONIO"
          x-model="patSearch"
          @input.debounce.300ms="(function(){ const t=String(patSearch||'').trim(); if(t.length>=3){ showPatDropdown=true; buscarPatrimonios(); } else { showPatDropdown=false; patrimoniosLista=[]; highlightedPatIndex=-1; } })()"
          @keydown.down.prevent="navegarPatrimonios(1)"
          @keydown.up.prevent="navegarPatrimonios(-1)"
          @keydown.enter.prevent="selecionarPatrimonioEnter()"
          @keydown.escape.prevent="showPatDropdown=false"
          name="NUPATRIMONIO"
          type="text"
          inputmode="numeric"
          tabindex="1"
          class="block w-full h-8 text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm pr-10 focus:ring-2 focus:ring-indigo-500"
          placeholder="Informe o número do patrimônio"
          required />
        <div class="absolute inset-y-0 right-0 flex items-center pr-2 gap-1">
          <div class="flex items-center gap-1">
            <button type="button" x-show="formData.NUPATRIMONIO" @click="limparPatrimonio" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none text-lg leading-none" title="Limpar seleção" tabindex="-1">×</button>
            <button type="button" @click="abrirDropdownPatrimonios(true)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 focus:outline-none" title="Abrir lista" tabindex="-1">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
              </svg>
            </button>
          </div>
        </div>
        <div x-show="showPatDropdown" x-transition class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-56 overflow-y-auto text-xs">
          <template x-if="loadingPatrimonios">
            <div class="p-2 text-gray-500 text-center text-xs">Buscando...</div>
          </template>
          <template x-if="!loadingPatrimonios && patrimoniosLista.length === 0">
            <div class="p-2 text-gray-500 text-center text-xs" x-text="String(patSearch || '').trim()==='' ? 'Digite para buscar' : 'Nenhum resultado'"></div>
          </template>
          <template x-for="(p, i) in (patrimoniosLista || [])" :key="p.NUSEQPATR || p.NUPATRIMONIO || i">
            <div data-pat-item @click="selecionarPatrimonio(p)" @mouseover="highlightedPatIndex = i" :class="['px-3 py-1.5 cursor-pointer text-xs', highlightedPatIndex === i ? 'bg-indigo-100 dark:bg-indigo-900' : 'hover:bg-indigo-50 dark:hover:bg-gray-700']">
              <span class="font-mono text-indigo-600 dark:text-indigo-400" x-text="p.NUPATRIMONIO"></span>
              <span class="ml-2 text-gray-700 dark:text-gray-300" x-text="' - ' + p.DEPATRIMONIO"></span>
              <span class="ml-2 text-green-600 dark:text-green-400 text-xs" x-text="p.CDPROJETO ? (p.CDPROJETO + ' - ' + p.NOMEPROJETO) : '—'"></span>
            </div>
          </template>
        </div>
      </div>
    </div>
    <div>
      <label for="NUMOF" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Número da Ordem de Compra</label>
      <input x-model="formData.NUMOF" id="NUMOF" name="NUMOF" type="number" tabindex="2" class="block w-full h-8 text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500" />
    </div>
    <div>
      <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Campo Extra</label>
      <input id="campo_extra" name="campo_extra" type="text" disabled tabindex="-1" class="block w-full h-8 text-sm border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 rounded-md cursor-not-allowed" />
    </div>
  </div>

  {{-- GRUPO 2: Descrição e Código do Objeto (INVERTIDOS) --}}
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    {{-- Descrição do Objeto (busca com dropdown) --}}
    <div class="md:col-span-3">
      <label for="DEOBJETO" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Descrição do Objeto *</label>
      <div class="relative" @click.away="showCodigoDropdown=false">
        <input id="DEOBJETO"
          x-model="descricaoSearch"
          @input.debounce.300ms="(function(){ const t=String(descricaoSearch||'').trim(); if(t.length>0){ showCodigoDropdown=true; buscarCodigos(); } else { showCodigoDropdown=false; codigosLista=[]; highlightedCodigoIndex=-1; } })()"
          @blur="(function(){ setTimeout(()=>{ buscarCodigo(); }, 150); })()"
          @keydown.down.prevent="navegarCodigos(1)"
          @keydown.up.prevent="navegarCodigos(-1)"
          @keydown.enter.prevent="selecionarCodigoEnter()"
          @keydown.escape.prevent="showCodigoDropdown=false"
          type="text"
          tabindex="3"
          class="block w-full h-8 text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm pr-10 focus:ring-2 focus:ring-indigo-500"
          placeholder="Informe a descrição do objeto" required />
        {{-- Valor enviado (hidden) --}}
        <input type="hidden" name="NUSEQOBJ" :value="formData.NUSEQOBJ" />
        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
          <div class="flex items-center gap-2">
            <button type="button" x-show="formData.NUSEQOBJ" @click="limparCodigo" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none" title="Limpar seleção" aria-label="Limpar seleção">✕</button>
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
            <div class="p-2 text-gray-500" x-text="String(descricaoSearch || '').trim()==='' ? 'Digite para buscar' : 'Nenhum resultado'"></div>
          </template>
          <template x-for="(c,i) in (codigosLista || [])" :key="c.CODOBJETO || i">
            <div data-cod-item @click="selecionarCodigo(c)" @mouseover="highlightedCodigoIndex=i" :class="['px-3 py-2 cursor-pointer', highlightedCodigoIndex===i ? 'bg-indigo-100 dark:bg-gray-700' : 'hover:bg-indigo-50 dark:hover:bg-gray-700']">
              <span class="ml-2 text-gray-700 dark:text-gray-300" x-text="c.DESCRICAO"></span>
              <span class="font-mono text-xs text-indigo-600 dark:text-indigo-400 ml-2" x-text="' (' + c.CODOBJETO + ')'"></span>
            </div>
          </template>
        </div>
        <p class="mt-1 text-xs" x-bind:class="isNovoCodigo ? 'text-amber-600' : (formData.NUSEQOBJ ? 'text-green-600' : '')" x-text="codigoBuscaStatus"></p>
      </div>
    </div>
    {{-- Código do Objeto (preenchido automaticamente) --}}
    <div class="md:col-span-1">
      <label for="NUSEQOBJ" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Código do Objeto *</label>
      <input id="NUSEQOBJ"
        x-model="formData.NUSEQOBJ"
        type="text"
        inputmode="numeric"
        tabindex="4"
        x-bind:readonly="!isNovoCodigo"
        x-bind:class="!isNovoCodigo ? 'bg-gray-100 dark:bg-gray-700 dark:text-gray-400 cursor-not-allowed' : ''"
        class="block w-full h-8 text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500"
        placeholder="Preenchido automaticamente" />
    </div>
  </div>

  {{-- GRUPO 3: Observação --}}
  <div>
    <x-input-label for="DEHISTORICO" value="Observações" />
    <textarea data-index="6" x-model="formData.DEHISTORICO" id="DEHISTORICO" name="DEHISTORICO" rows="2" tabindex="5" class="block mt-0.5 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm"></textarea>
  </div>

  {{-- GRUPO 4: Local, Cód. Termo e Projeto --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

    {{-- LOCAL: Botão + | Código | Dropdown Nome --}}
    <div class="md:col-span-2">
      <label for="CDLOCAL_INPUT" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Código do Local *</label>

      <div class="flex gap-3">
        {{-- Botão + (Criar Novo Local/Projeto) --}}
        <button type="button"
          @click="abrirModalCriarProjeto()"
          @keydown.space.prevent="abrirModalCriarProjeto()"
          class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-green-600 hover:bg-green-700 text-white rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 transition-colors"
          title="Criar novo local/projeto (Espaço)"
          tabindex="6">
          <span class="text-lg font-bold leading-none">+</span>
        </button>

        {{-- Input Código do Local --}}
        <div class="flex-shrink-0 w-24">
          <input id="CDLOCAL_INPUT"
            type="text"
            inputmode="numeric"
            x-model="codigoLocalDigitado"
            @input.debounce.300ms="buscarLocalPorCodigo()"
            placeholder="Informe o código do local"
            tabindex="7"
            class="block w-full h-8 text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500" />
          <input type="hidden" name="CDLOCAL" :value="formData.CDLOCAL" />
        </div>

        {{-- Dropdown/Input Nome do Local com Autocomplete --}}
        <div class="flex-1 relative" @click.away="showNomeLocalDropdown=false">
          <div class="relative">
            <input type="text"
              id="NOMELOCAL_INPUT"
              x-model="nomeLocalBusca"
              @focus="abrirDropdownNomesLocaisAoFocar()"
              @input.debounce.300ms="(function(){ buscarNomesLocais(); })()"
              @keydown.down.prevent="navegarNomesLocais(1)"
              @keydown.up.prevent="navegarNomesLocais(-1)"
              @keydown.enter.prevent="selecionarNomeLocalEnter()"
              @keydown.escape.prevent="showNomeLocalDropdown=false"
              :disabled="!codigoLocalDigitado || locaisEncontrados.length === 1"
              :placeholder="!codigoLocalDigitado ? 'Informe o código do local primeiro' : (locaisEncontrados.length === 1 ? 'Preenchido automaticamente' : 'Informe o nome do local')"
              tabindex="8"
              :class="[
                'block w-full h-8 text-sm rounded-md shadow-sm pr-10 focus:ring-2 focus:ring-indigo-500',
                !codigoLocalDigitado || locaisEncontrados.length === 1
                  ? 'border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 dark:text-gray-400 cursor-not-allowed'
                  : 'border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200'
              ]"
              autocomplete="off" />

            <div class="absolute inset-y-0 right-0 flex items-center pr-2 gap-1">
              <button type="button"
                x-show="nomeLocalBusca && codigoLocalDigitado"
                @click="limparNomeLocal"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none text-lg leading-none"
                title="Limpar seleção"
                tabindex="-1">×</button>
              <button type="button"
                @click="abrirDropdownNomesLocais(true)"
                :disabled="!codigoLocalDigitado"
                :class="[
                  'focus:outline-none',
                  codigoLocalDigitado 
                    ? 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-200' 
                    : 'text-gray-300 dark:text-gray-600 cursor-not-allowed'
                ]"
                title="Abrir lista"
                tabindex="-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                </svg>
              </button>
            </div>
          </div>

          {{-- Dropdown de sugestões --}}
          <div x-show="showNomeLocalDropdown"
            x-transition
            class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-56 overflow-y-auto text-xs">
            <template x-if="loadingNomesLocais">
              <div class="p-2 text-gray-500 text-center">Buscando...</div>
            </template>
            <template x-if="!loadingNomesLocais && nomesLocaisLista.length === 0 && nomeLocalBusca.trim() !== ''">
              <div class="p-2 text-gray-500 text-center">Nenhum resultado encontrado</div>
            </template>
            <template x-if="!loadingNomesLocais && nomesLocaisLista.length === 0 && nomeLocalBusca.trim() === ''">
              <div class="p-2 text-gray-500 text-center" x-text="'Total: ' + locaisEncontrados.length + ' local(ais) disponível(is)'"></div>
            </template>
            <template x-for="(local, i) in nomesLocaisLista" :key="local.id">
              <div @click="selecionarNomeLocal(local)"
                @mouseover="highlightedNomeLocalIndex = i"
                :class="['px-3 py-1.5 cursor-pointer', highlightedNomeLocalIndex === i ? 'bg-indigo-100 dark:bg-indigo-900' : 'hover:bg-indigo-50 dark:hover:bg-gray-700']">
                <span class="text-gray-700 dark:text-gray-300" x-text="local.LOCAL || local.delocal"></span>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>

    {{-- CAMPO CÓD TERMO --}}
    <div>
      <label for="NMPLANTA" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Código do Termo</label>
      <input x-model="formData.NMPLANTA"
        id="NMPLANTA"
        name="NMPLANTA"
        type="number"
        tabindex="9"
        class="block w-full h-8 text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500" />
    </div>

    {{-- CAMPO PROJETO (readonly, preenchido automaticamente) --}}
    <div class="md:col-span-3">
      <label for="CDPROJETO" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Projeto Vinculado ao Local</label>
      <input id="CDPROJETO"
        :value="projetoAssociadoSearch || (!formData.CDLOCAL ? 'Selecione um local para exibir o projeto' : 'Carregando...')"
        readonly
        tabindex="-1"
        class="block w-full h-8 text-sm border-gray-300 dark:border-gray-700 bg-gray-100 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm cursor-not-allowed" />
      <input type="hidden" name="CDPROJETO" :value="formData.CDPROJETO" />
    </div>
  </div>

  {{-- GRUPO 5: Marca, Modelo, Situação, Matrícula / Usuário --}}
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
    <div>
      <label for="MARCA" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Marca do Patrimônio</label>
      <input x-model="formData.MARCA" id="MARCA" name="MARCA" type="text" tabindex="10" class="block w-full h-8 text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500" />
    </div>
    <div>
      <label for="MODELO" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Modelo do Patrimônio</label>
      <input x-model="formData.MODELO" id="MODELO" name="MODELO" type="text" tabindex="11" class="block w-full h-8 text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500" />
    </div>
    <div>
      <label for="SITUACAO" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Situação do Patrimônio *</label>
      <select id="SITUACAO" name="SITUACAO" required tabindex="12"
        class="block w-full h-8 text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500">
        @php
        $situacaoAtual = old('SITUACAO', $patrimonio->situacao ?? 'EM USO');
        @endphp
        <option value="EM USO" @if ($situacaoAtual=='EM USO' ) selected @endif>EM USO</option>
        <option value="CONSERTO" @if ($situacaoAtual=='CONSERTO' ) selected @endif>CONSERTO</option>
        <option value="BAIXA" @if ($situacaoAtual=='BAIXA' ) selected @endif>BAIXA</option>
        <option value="À DISPOSIÇÃO" @if ($situacaoAtual=='À DISPOSIÇÃO' ) selected @endif>À DISPOSIÇÃO</option>
      </select>
    </div>
    <div class="relative" @click.away="showUserDropdown=false">
      <label for="matricula_busca" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Matrícula do Responsável *</label>
      <div class="relative">
        <input id="matricula_busca"
          x-model="userSearch"
          @input.debounce.300ms="(function(){ const t=String(userSearch||'').trim(); if(t.length>0){ showUserDropdown=true; buscarUsuarios(); } else { showUserDropdown=false; usuarios=[]; highlightedUserIndex=-1; } })()"
          @keydown.down.prevent="navegarUsuarios(1)"
          @keydown.up.prevent="navegarUsuarios(-1)"
          @keydown.enter.prevent="selecionarUsuarioEnter()"
          @keydown.escape.prevent="showUserDropdown=false"
          @blur="normalizarMatriculaBusca()"
          type="text"
          placeholder="Digite matrícula ou nome"
          tabindex="13"
          class="block w-full h-8 text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm pr-10 focus:ring-2 focus:ring-indigo-500"
          autocomplete="off" />
        <input type="hidden" name="CDMATRFUNCIONARIO" :value="formData.CDMATRFUNCIONARIO" />
        <div class="absolute inset-y-0 right-0 flex items-center pr-2 gap-1">
          <div class="flex items-center gap-1">
            <button type="button" x-show="formData.CDMATRFUNCIONARIO" @click="limparUsuario" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none text-lg leading-none" title="Limpar seleção" tabindex="-1">×</button>
            <button type="button" @click="abrirDropdownUsuarios(true)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 focus:outline-none" title="Abrir lista" tabindex="-1">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
              </svg>
            </button>
          </div>
        </div>
      </div>
      <div x-show="showUserDropdown" x-transition class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-56 overflow-y-auto text-xs">
        <template x-if="loadingUsers">
          <div class="p-2 text-gray-500 text-center">Buscando...</div>
        </template>
        <template x-if="!loadingUsers && usuarios.length === 0">
          <div class="p-2 text-gray-500 text-center" x-text="userSearch.trim()==='' ? 'Digite para buscar' : 'Nenhum resultado'"></div>
        </template>
        <template x-for="(u, i) in (usuarios || [])" :key="u.CDMATRFUNCIONARIO || i">
          <div data-user-item @click="selecionarUsuario(u)"
            @mouseover="highlightedUserIndex = i"
            :class="['px-3 py-1.5 cursor-pointer', highlightedUserIndex === i ? 'bg-indigo-100 dark:bg-indigo-900' : 'hover:bg-indigo-50 dark:hover:bg-gray-700']">
            <span class="font-mono text-indigo-600 dark:text-indigo-400" x-text="u.CDMATRFUNCIONARIO"></span>
            <span class="ml-2 text-gray-700 dark:text-gray-300" x-text="' - ' + (String(u.NOMEUSER || '').replace(/\d{2}\/\d{2}\/\d{4}/, '').replace(/\s+\d+\s*$/, '').replace(/[^A-Za-zÀ-ÿ\s]/g, '').trim())"></span>
          </div>
        </template>
      </div>
      <p class="mt-1 text-xs text-gray-500" x-show="formData.CDMATRFUNCIONARIO && userSelectedName">Selecionado: <span class="font-semibold" x-text="userSelectedName"></span></p>
      <x-input-error class="mt-2" :messages="$errors->get('CDMATRFUNCIONARIO')" />
    </div>
  </div>

  {{-- GRUPO 6: Datas --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
      <label for="DTAQUISICAO" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Data de Aquisição do Patrimônio</label>
      <input x-model="formData.DTAQUISICAO" id="DTAQUISICAO" name="DTAQUISICAO" type="date" tabindex="14" class="block w-full h-8 text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500" />
    </div>
    <div>
      <label for="DTBAIXA" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Data de Baixa do Patrimônio</label>
      <input x-model="formData.DTBAIXA" id="DTBAIXA" name="DTBAIXA" type="date" tabindex="15" class="block w-full h-8 text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500" />
      <x-input-error class="mt-2" :messages="$errors->get('DTBAIXA')" />
    </div>
  </div>

  {{-- ✨ MODAL DE CRIAR NOVO PROJETO/LOCAL --}}
  <div x-show="modalCriarProjetoOpen"
    x-transition
    x-cloak
    @keydown.escape.window="fecharModalCriarProjeto"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-2xl">
      <div class="flex justify-between items-center mb-4">
        <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-200">
          Criar Novo Local/Projeto
        </h4>
        <button type="button"
          class="text-gray-400 hover:text-gray-600 text-2xl leading-none"
          @click="fecharModalCriarProjeto"
          :disabled="salvandoCriacaoProjeto">×</button>
      </div>

      {{-- Formulário de Criação --}}
      <div class="space-y-4">
        {{-- 🔍 Input de Busca: Código do Projeto --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Código do Projeto *
          </label>
          <input type="text"
            x-model="novoProjeto.cdprojetoBusca"
            x-ref="inputCodProjetoBusca"
            @input="buscarProjetoExistente"
            placeholder="Ex: 001"
            class="w-full h-10 border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Informe o código do projeto para buscar
          </p>
        </div>

        {{-- Spinner de carregamento --}}
        <div x-show="carregandoProjeto" class="flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400">
          <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span>Buscando projeto...</span>
        </div>

        {{-- ✅ Quando projeto foi encontrado: mostrar campos desabilitados + campo de nome --}}
        <div x-show="novoProjeto.cdprojeto && !carregandoProjeto" class="space-y-4" x-effect="if(novoProjeto.cdprojeto && $refs.inputNomeLocal){ $nextTick(() => $refs.inputNomeLocal.focus()); }">
          {{-- Código do Local (desabilitado) --}}
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Código do Local
            </label>
            <input type="text"
              :value="novoProjeto.cdprojetoBusca"
              disabled
              class="w-full h-10 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md bg-gray-100 text-gray-600 cursor-not-allowed" />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Código identificado automaticamente</p>
          </div>

          {{-- Projeto Associado (desabilitado) --}}
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Projeto Associado
            </label>
            <input type="text"
              :value="novoProjeto.nmProjeto"
              disabled
              class="w-full h-10 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md bg-gray-100 text-gray-600 cursor-not-allowed" />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Projeto vinculado ao local (identificado automaticamente)</p>
          </div>

          {{-- Nome do Local (ÚNICO editável) --}}
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Nome do Local *
            </label>
            <input type="text"
              x-model="novoProjeto.nomeLocal"
              x-ref="inputNomeLocal"
              placeholder="Ex: Almoxarifado Central"
              class="w-full h-10 border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md focus:ring-2 focus:ring-blue-500" />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
              Preencha o nome do local
            </p>
          </div>
        </div>

        {{-- 📋 Campos que aparecem quando NÃO encontrou projeto --}}
        <div x-show="!novoProjeto.cdprojeto && !carregandoProjeto && novoProjeto.cdprojetoBusca">
          <div class="bg-amber-50 dark:bg-amber-900/30 border border-amber-300 dark:border-amber-700 rounded p-3 mb-4">
            <p class="text-sm text-amber-800 dark:text-amber-200">
              Projeto não encontrado. Preencha os dados abaixo para criar novo:
            </p>
          </div>

          {{-- Código do Projeto --}}
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Código do Projeto *
            </label>
            <input type="text"
              x-model="novoProjeto.cdlocal"
              placeholder="Ex: 001"
              class="w-full h-10 border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Código que identificará o novo projeto</p>
          </div>

          {{-- Nome do Local --}}
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Nome do Local *
            </label>
            <input type="text"
              x-model="novoProjeto.nomeLocal"
              placeholder="Ex: Almoxarifado Central"
              class="w-full h-10 border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Nome descritivo do local</p>
          </div>

          {{-- Projeto Associado --}}
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Projeto Associado *
            </label>
            <select x-model="novoProjeto.cdprojeto"
              class="w-full h-10 border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md">
              <option value="">Selecione um projeto</option>
              <template x-for="proj in projetosExistentes" :key="proj.CDPROJETO">
                <option :value="proj.CDPROJETO" x-text="proj.CDPROJETO + ' - ' + proj.NOMEPROJETO"></option>
              </template>
            </select>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Projeto ao qual este local será associado</p>
          </div>
        </div>
      </div>

      {{-- Mensagem de Erro --}}
      <p class="mt-4 text-sm text-red-500" x-show="erroCriacaoProjeto" x-text="erroCriacaoProjeto"></p>

      {{-- Botões --}}
      <div class="flex justify-end gap-2 mt-6">
        <button type="button"
          @click="fecharModalCriarProjeto"
          :disabled="salvandoCriacaoProjeto"
          class="px-4 py-2 text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50">
          Cancelar
        </button>
        <button type="button"
          @click="salvarNovoProjeto"
          :disabled="salvandoCriacaoProjeto"
          class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50 flex items-center gap-2">
          <span x-show="!salvandoCriacaoProjeto">✓ Criar</span>
          <span x-show="salvandoCriacaoProjeto">
            <svg class="animate-spin h-4 w-4 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Salvando...
          </span>
        </button>
      </div>
    </div>
  </div>

  {{-- MODAL DE EDITAR LOCAL --}}
  <div x-show="modalEditarLocalOpen" x-transition x-cloak @keydown.escape.window="fecharModalEditarLocal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-96">
      <div class="flex justify-between items-center mb-4">
        <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Editar Local</h4>
        <button type="button" class="text-gray-400 hover:text-gray-600" @click="fecharModalEditarLocal">✕</button>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Código do Local</label>
        <input type="text" x-model="editarLocalCodigo" class="w-full border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 dark:text-gray-200 rounded-md" readonly />
      </div>

      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nome do Local</label>
        <input type="text" x-model="editarLocalNome" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md" placeholder="Digite o nome do local" />
      </div>

      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Projeto Associado</label>
        <select x-model="editarLocalProjeto" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md">
          <option value="">Selecione um projeto</option>
          <template x-for="proj in projetosExistentes" :key="proj.CDPROJETO">
            <option :value="proj.CDPROJETO" x-text="proj.CDPROJETO + ' - ' + proj.NOMEPROJETO"></option>
          </template>
        </select>
      </div>

      <p class="text-xs text-red-500 mb-4" x-text="erroEdicao" x-show="erroEdicao"></p>

      <div class="flex justify-end gap-2">
        <button type="button" @click="fecharModalEditarLocal" class="px-4 py-2 text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700">Cancelar</button>
        <button type="button" @click="salvarEdicaoLocal" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50" :disabled="salvandoEdicao">
          <span x-show="!salvandoEdicao">Salvar</span>
          <span x-show="salvandoEdicao">Salvando...</span>
        </button>
      </div>
    </div>
  </div>

  {{-- MODAL DE PESQUISA (Não mexe na estrutura, fica no final) --}}
  <div x-show="searchModalOpen" x-cloak @keydown.window.escape="closeSearchModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div @click.away="closeSearchModal" class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-lg p-6">
      <h3 class="text-lg font-semibold mb-4">Pesquisar Patrimônio</h3>
      <input x-model="searchTerm" @input.debounce.300ms="search" type="text" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm" placeholder="Digite o nº ou descrição para buscar...">
      <ul class="mt-4 max-h-60 overflow-y-auto">
        <template x-for="(item, i) in (searchResults || [])" :key="item.NUSEQPATR || item.NUPATRIMONIO || i">
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
        // Novo fluxo de Código/Descrição do Código
        NUSEQOBJ: (config.old?.NUSEQOBJ ?? config.patrimonio?.CODOBJETO) || '',
        DEOBJETO: (config.old?.DEOBJETO ?? '') || '',
        // Mantemos DEPATRIMONIO somente para compatibilidade de carregamento de patrimônio existente (não é mais o campo de edição de descrição do código)
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
      // Autocomplete Descrição (antes chamado de Código)
      descricaoSearch: (config.old?.DEOBJETO ?? config.patrimonio?.DEOBJETO) || '',
      codigosLista: [],
      loadingCodigos: false,
      showCodigoDropdown: false,
      highlightedCodigoIndex: -1,
      isNovoCodigo: false,
      codigoBuscaStatus: '',
      // Autocomplete Projetos Associados
      projetoAssociadoSearch: '',
      projetosAssociadosLista: [],
      loadingProjetosAssociados: false,
      showProjetoAssociadoDropdown: false,
      highlightedProjetoAssociadoIndex: -1,

      // === SISTEMA SIMPLIFICADO DE LOCAIS ===
      codigoLocalDigitado: '', // Código digitado pelo usuário
      localNome: '', // ✅ Nome do local (preenchido automaticamente)
      locaisEncontrados: [], // Array de locais retornados pela API
      localSelecionadoId: null, // ID do local selecionado no dropdown
      mostrarDropdownBusca: false, // Controla visibilidade do dropdown de busca
      resultadosBusca: [], // Resultados brutos da busca (lupa ou digitação)
      resultadosBuscaGrouped: [], // Resultados agrupados por cdlocal

      // Autocomplete Nome do Local
      nomeLocalBusca: '', // Texto digitado pelo usuário no campo de nome
      nomesLocaisLista: [], // Lista de sugestões de nomes
      showNomeLocalDropdown: false, // Controla visibilidade do dropdown
      loadingNomesLocais: false, // Flag de carregamento
      highlightedNomeLocalIndex: -1, // Índice do item destacado

      // Variáveis antigas (manter compatibilidade)
      localSearch: '',
      nomeLocal: '',
      locaisFiltrados: [],
      showLocalDropdown: false,
      localFocused: false,
      highlightedLocalIndex: -1,
      get locaisComMesmoCodigo() {
        if (!this.formData.CDLOCAL) return [];
        return this.locais.filter(l => String(l.cdlocal) === String(this.formData.CDLOCAL));
      },
      // Autocomplete Patrimônio
      patSearch: (config.old?.NUPATRIMONIO ?? config.patrimonio?.NUPATRIMONIO) || '',
      patrimoniosLista: [],
      loadingPatrimonios: false,
      showPatDropdown: false,
      highlightedPatIndex: -1,

      // Modais de Local
      modalCriarLocalOpen: false,
      modalEditarLocalOpen: false,
      novoLocalNome: '',
      editarLocalCodigo: '',
      editarLocalNome: '',
      editarLocalProjeto: '',
      projetosExistentes: [],
      erroCriacao: '',
      erroEdicao: '',
      salvandoCriacao: false,
      salvandoEdicao: false,

      // 🆕 MODAL CRIAR PROJETO/LOCAL
      modalCriarProjetoOpen: false,
      novoProjeto: {
        cdlocal: '',
        nomeLocal: '',
        cdprojeto: '',
        cdprojetoBusca: '', // Campo de busca de projeto existente
        nmProjeto: '', // Nome do projeto encontrado
      },
      erroCriacaoProjeto: '',
      salvandoCriacaoProjeto: false,
      carregandoProjeto: false, // Flag para quando está buscando projeto
      buscaProjetoTimeout: null, // Armazena o timeout para cancelamento
      estadoTemporario: null, // Salva o estado do formulário antes de abrir o modal

      // == FUNÇÕES ==
      handleEnter(e) {
        // Se for textarea, permite quebra de linha
        if (e.target.tagName === 'TEXTAREA') return;
        // Se for botão submit, submete
        if (e.target.type === 'submit') {
          e.target.form && e.target.form.submit();
          return;
        }
        // Avança para o próximo campo (igual ao Tab)
        const form = e.target.form || this.$root.querySelector('form');
        if (!form) return;
        const focusables = Array.from(form.querySelectorAll('[tabindex]:not([tabindex="-1"]),input:not([type=hidden]),select,textarea,button')).filter(el => !el.disabled && el.offsetParent !== null);
        const idx = focusables.indexOf(e.target);
        if (idx > -1 && idx < focusables.length - 1) {
          focusables[idx + 1].focus();
        } else if (idx === focusables.length - 1) {
          // Último campo: submete
          form.submit();
        }
      },
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
            // Ajustes específicos de mapeamento entre API e formData atual
            if (data.hasOwnProperty('CODOBJETO')) {
              this.formData.NUSEQOBJ = data.CODOBJETO;
              this.descricaoSearch = String(data.DEPATRIMONIO || '');
              this.isNovoCodigo = false; // código existente, bloqueia edição
            }
            if (data.hasOwnProperty('DEPATRIMONIO')) {
              // Exibe descrição na caixa da descrição do código
              this.formData.DEOBJETO = data.DEPATRIMONIO || '';
              this.codigoBuscaStatus = this.formData.NUSEQOBJ ? 'Código encontrado e preenchido automaticamente.' : '';
            }
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
        this.locais = [];
        if (!this.formData.CDPROJETO) {
          return;
        }
        try {
          const locaisResponse = await fetch(`/api/locais/${this.formData.CDPROJETO}`, {
            credentials: 'same-origin',
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            }
          });
          if (locaisResponse.ok) {
            this.locais = await locaisResponse.json();
            console.log(`Carregados ${this.locais.length} locais do projeto ${this.formData.CDPROJETO}:`, this.locais);
            // Se já houver um CDLOCAL selecionado, sincroniza o nome exibido
            if (this.formData.CDLOCAL) {
              const found = this.locais.find(x => String(x.cdlocal) === String(this.formData.CDLOCAL));
              if (found) {
                this.localSearch = found.cdlocal;
                this.nomeLocal = found.LOCAL || found.delocal;
                // Manter o ID do local selecionado se ainda não estiver definido
                if (!this.localSelecionadoId) {
                  this.localSelecionadoId = found.id;
                }
              }
            }
          }
        } catch (error) {
          console.error('Erro ao buscar locais:', error);
        }
      },
      fecharSeFora(e) {
        // Se o clique for dentro do dropdown de locais ou no botão de abrir, não fecha
        const path = (e.composedPath && e.composedPath()) || (e.path) || [];
        const withinLocalDropdown = path.some(el => el && el.getAttribute && el.getAttribute('data-local-item') !== null);
        if (withinLocalDropdown) return;
        // Caso contrário, fecha ambos dropdowns
        this.showLocalDropdown = false;
        this.showProjetoDropdown = false;
      },

      // Fecha o dropdown de locais apenas quando o foco realmente saiu do input e do próprio dropdown
      handleLocalFocusOut(e) {
        // Pequeno atraso para permitir que o focus mude para um item do dropdown (mousedown)
        setTimeout(() => {
          try {
            const active = document.activeElement;
            const input = this.$root.querySelector('#CDLOCAL_INPUT');
            const dropdown = this.$root.querySelector('[x-show="showLocalDropdown"]');
            const isInsideDropdown = dropdown && dropdown.contains(active);
            const isInput = input && (active === input);
            if (!isInsideDropdown && !isInput) {
              this.showLocalDropdown = false;
            }
          } catch (err) {
            // Em caso de erro, fecha como fallback
            this.showLocalDropdown = false;
          }
        }, 0);
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
        // Sempre envia só a matrícula para o campo oculto
        this.formData.CDMATRFUNCIONARIO = String(u.CDMATRFUNCIONARIO).replace(/[^0-9]/g, '');
        let nomeLimpo = String(u.NOMEUSER || '').replace(/\d{2}\/\d{2}\/\d{4}/, '').replace(/\s+\d+\s*$/, '').replace(/[^A-Za-zÀ-ÿ\s]/g, '');
        nomeLimpo = nomeLimpo.trim();
        this.userSelectedName = `${u.CDMATRFUNCIONARIO} - ${nomeLimpo}`;
        this.userSearch = this.userSelectedName;
        this.showUserDropdown = false;
      },
      // Sanitiza o campo visível removendo datas/números após o nome e garante que o hidden receba só a matrícula
      normalizarMatriculaBusca() {
        let s = String(this.userSearch || '');
        // Corta qualquer data (ex: 10/02/1998) e o que vem depois
        const dateIdx = s.search(/\d{2}\/\d{2}\/\d{4}/);
        if (dateIdx >= 0) s = s.slice(0, dateIdx);
        // Remove números soltos no final (ex: "   0")
        s = s.replace(/\s+\d+\s*$/, '');
        // Mantém apenas "mat - nome" quando houver mais lixo depois
        const m = s.match(/^(\d+)\s*-\s*([^\d\/]+?)(?:\s+\d.*)?$/);
        if (m) {
          s = `${m[1]} - ${m[2].trim()}`;
        }
        this.userSearch = s.trim();
        // Tenta extrair a matrícula no início da string e atualizar o hidden
        const onlyMat = this.userSearch.match(/^(\d{1,12})\b/);
        if (onlyMat) {
          this.formData.CDMATRFUNCIONARIO = onlyMat[1];
        }
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

      // === Autocomplete Nome do Local ===
      async buscarNomesLocais() {
        const termo = this.nomeLocalBusca.trim();

        // Só busca se o código do local foi digitado
        if (!this.codigoLocalDigitado) {
          this.nomesLocaisLista = [];
          this.highlightedNomeLocalIndex = -1;
          return;
        }

        this.loadingNomesLocais = true;
        try {
          // Busca locais pelo código
          const url = `/api/locais/buscar?termo=${encodeURIComponent(this.codigoLocalDigitado)}`;
          const resp = await fetch(url);

          if (resp.ok) {
            let locais = await resp.json();

            // Se há termo de busca, filtra pelo nome
            if (termo) {
              const termoLower = termo.toLowerCase();
              locais = locais.filter(local => {
                const nomeLocal = (local.LOCAL || local.delocal || '').toLowerCase();
                return nomeLocal.includes(termoLower);
              });
            }

            this.nomesLocaisLista = locais;
            this.highlightedNomeLocalIndex = locais.length > 0 ? 0 : -1;

            // Abrir dropdown se houver resultados
            if (locais.length > 0) {
              this.showNomeLocalDropdown = true;
            }
          }
        } catch (e) {
          console.error('Erro ao buscar nomes de locais:', e);
        } finally {
          this.loadingNomesLocais = false;
        }
      },

      abrirDropdownNomesLocaisAoFocar() {
        // Abre dropdown ao focar no input
        if (!this.codigoLocalDigitado) {
          return; // Não abre se não tiver código
        }

        this.showNomeLocalDropdown = true;

        // Se o campo está vazio, busca todos os locais do código
        if (this.nomeLocalBusca.trim() === '') {
          this.buscarNomesLocais();
        }
      },

      abrirDropdownNomesLocais(force = false) {
        if (!this.codigoLocalDigitado) {
          return; // Não abre se não tiver código
        }

        this.showNomeLocalDropdown = true;

        // Se forçado (clique na lupa) ou campo vazio, busca todos do código
        if (force || this.nomeLocalBusca.trim() === '') {
          this.buscarNomesLocais();
        }
      },

      selecionarNomeLocal(local) {
        console.log('📍 [SELECIONAR NOME] Local selecionado:', local);

        // Atualizar o campo de busca com o nome
        this.nomeLocalBusca = local.LOCAL || local.delocal;

        // Atualizar o local selecionado
        this.localSelecionadoId = local.id;
        this.formData.CDLOCAL = local.id;
        this.localNome = local.LOCAL || local.delocal;

        // Atualizar projeto associado
        if (local.CDPROJETO) {
          this.formData.CDPROJETO = local.CDPROJETO;
          this.projetoAssociadoSearch = local.NOMEPROJETO ?
            `${local.CDPROJETO} - ${local.NOMEPROJETO}` :
            String(local.CDPROJETO);
        }

        // Fechar dropdown
        this.showNomeLocalDropdown = false;
        this.nomesLocaisLista = [];
        this.highlightedNomeLocalIndex = -1;

        console.log('✅ [SELECIONAR NOME] Atualizado:', {
          nome: this.nomeLocalBusca,
          id: this.localSelecionadoId,
          cdlocal: this.formData.CDLOCAL,
          projeto: this.projetoAssociadoSearch
        });
      },

      selecionarNomeLocalEnter() {
        if (!this.showNomeLocalDropdown) return;
        if (this.highlightedNomeLocalIndex < 0 || this.highlightedNomeLocalIndex >= this.nomesLocaisLista.length) return;
        this.selecionarNomeLocal(this.nomesLocaisLista[this.highlightedNomeLocalIndex]);
      },

      limparNomeLocal() {
        this.nomeLocalBusca = '';
        this.nomesLocaisLista = [];
        this.highlightedNomeLocalIndex = -1;
        this.showNomeLocalDropdown = false;

        // Limpar também a seleção do local
        this.localSelecionadoId = null;
        this.formData.CDLOCAL = '';
        this.localNome = '';
        this.formData.CDPROJETO = '';
        this.projetoAssociadoSearch = '';
      },

      navegarNomesLocais(delta) {
        if (!this.showNomeLocalDropdown || this.nomesLocaisLista.length === 0) return;
        const max = this.nomesLocaisLista.length - 1;

        if (this.highlightedNomeLocalIndex === -1) {
          this.highlightedNomeLocalIndex = 0;
        } else {
          this.highlightedNomeLocalIndex = Math.min(max, Math.max(0, this.highlightedNomeLocalIndex + delta));
        }

        // Scroll into view
        this.$nextTick(() => {
          const list = this.$root.querySelector('[x-show="showNomeLocalDropdown"]');
          if (!list) return;
          const items = list.querySelectorAll('[x-show="showNomeLocalDropdown"] > div');
          const el = items[this.highlightedNomeLocalIndex];
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
      // === Autocomplete Código (agora busca por Descrição) ===
      async buscarCodigo() {
        const valor = String(this.descricaoSearch || '').trim();
        this.codigoBuscaStatus = '';
        if (valor === '') {
          this.formData.NUSEQOBJ = '';
          this.isNovoCodigo = false;
          this.formData.DEOBJETO = '';
          return;
        }
        try {
          const r = await fetch(`/api/codigos/pesquisar?q=${encodeURIComponent(valor)}`);
          if (r.ok) {
            const data = await r.json();
            // Se encontrar resultados, seleciona o primeiro
            if (data.length > 0) {
              this.formData.NUSEQOBJ = data[0].CODOBJETO;
              this.formData.DEOBJETO = data[0].DESCRICAO || valor;
              this.isNovoCodigo = false; // bloqueia edição do código
              this.codigoBuscaStatus = 'Código encontrado e preenchido automaticamente.';
            } else {
              // Sem resultado: novo código
              this.formData.NUSEQOBJ = '';
              this.formData.DEOBJETO = valor;
              this.isNovoCodigo = true; // libera edição do código
              this.codigoBuscaStatus = 'Novo código. Você pode preencher o número do código.';
            }
          } else {
            // Erro na busca
            this.formData.NUSEQOBJ = '';
            this.formData.DEOBJETO = valor;
            this.isNovoCodigo = true;
            this.codigoBuscaStatus = 'Novo código. Você pode preencher o número do código.';
          }
        } catch (e) {
          console.error('Erro ao buscar código do objeto', e);
          this.codigoBuscaStatus = 'Erro na busca.';
          this.isNovoCodigo = true;
        }
      },
      async buscarCodigos() {
        const termo = this.descricaoSearch.trim();
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
        if (this.descricaoSearch.trim() !== '') this.buscarCodigos();
      },
      selecionarCodigo(c) {
        this.formData.NUSEQOBJ = c.CODOBJETO;
        this.descricaoSearch = c.DESCRICAO;
        this.formData.DEOBJETO = c.DESCRICAO;
        this.isNovoCodigo = false; // bloqueia edição do código
        this.codigoBuscaStatus = 'Código encontrado e preenchido automaticamente.';
        this.showCodigoDropdown = false;
      },
      selecionarCodigoEnter() {
        if (!this.showCodigoDropdown) return;
        if (this.highlightedCodigoIndex < 0 || this.highlightedCodigoIndex >= this.codigosLista.length) return;
        this.selecionarCodigo(this.codigosLista[this.highlightedCodigoIndex]);
      },
      limparCodigo() {
        this.formData.NUSEQOBJ = '';
        this.descricaoSearch = '';
        this.formData.DEOBJETO = '';
        this.isNovoCodigo = false;
        this.codigoBuscaStatus = '';
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
      // ========================================
      // 🔍 BUSCA INTELIGENTE DE LOCAIS POR CÓDIGO
      // ========================================
      async buscarLocaisPorCodigo() {
        const termo = String(this.localSearch || '').trim();

        // Se vazio, limpar tudo
        if (termo === '') {
          this.showLocalDropdown = false;
          this.locaisFiltrados = [];
          this.highlightedLocalIndex = -1;
          return;
        }

        console.log('🔍 [BUSCA LOCAL] Termo digitado:', termo);

        try {
          const url = `/api/locais/buscar?termo=${encodeURIComponent(termo)}`;
          console.log('🌐 [BUSCA LOCAL] URL chamada:', url);

          const resp = await fetch(url);

          console.log('📡 [BUSCA LOCAL] Status HTTP:', resp.status, resp.statusText);

          if (!resp.ok) {
            console.error('❌ [BUSCA LOCAL] Erro HTTP:', resp.status);
            const errorText = await resp.text();
            console.error('❌ [BUSCA LOCAL] Resposta erro:', errorText);
            this.locaisFiltrados = [];
            this.showLocalDropdown = true;
            return;
          }

          const todosLocais = await resp.json();
          console.log('📦 [BUSCA LOCAL] Total retornado da API:', todosLocais.length);
          console.log('📦 [BUSCA LOCAL] Dados completos:', JSON.stringify(todosLocais, null, 2));

          // Agrupar por CDLOCAL para detectar múltiplos locais com mesmo código
          const grupos = {};
          todosLocais.forEach(local => {
            const codigo = String(local.cdlocal);
            if (!grupos[codigo]) grupos[codigo] = [];
            grupos[codigo].push(local);
          });

          // Criar array de exibição com contagem
          this.locaisFiltrados = Object.keys(grupos).map(codigo => {
            const locaisDoGrupo = grupos[codigo];
            const primeiro = locaisDoGrupo[0];

            return {
              ...primeiro,
              _count: locaisDoGrupo.length,
              _isGrupo: locaisDoGrupo.length > 1
            };
          });

          console.log('📊 [BUSCA LOCAL] Grupos criados:', this.locaisFiltrados.length);
          this.locaisFiltrados.forEach(l => {
            console.log(`  - ${l.cdlocal}: ${l._count} local(is) | Nome: ${l.LOCAL || l.delocal}`);
          });

          this.showLocalDropdown = true;
          this.highlightedLocalIndex = this.locaisFiltrados.length > 0 ? 0 : -1;

          // AUTO-SELECIONAR se houver match exato com código completo
          if (this.locaisFiltrados.length > 0) {
            const matchExato = this.locaisFiltrados.find(l => String(l.cdlocal) === termo);
            if (matchExato) {
              console.log('🎯 [BUSCA LOCAL] Match exato encontrado! Auto-selecionando:', matchExato.cdlocal);
              // Aguardar um momento para evitar conflito com UI
              await this.$nextTick();
              await this.selecionarLocal(matchExato);
            }
          }

        } catch (e) {
          console.error('❌ [BUSCA LOCAL] Exceção:', e);
          this.locaisFiltrados = [];
          this.showLocalDropdown = true;
        }
      },

      // ========================================
      // 🔄 MOSTRAR TODOS OS LOCAIS (botão lupa)
      // ========================================
      async mostrarTodosLocais() {
        console.log('🔍 [MOSTRAR TODOS] Abrindo lista completa');
        this.localSearch = '';
        await this.buscarLocaisPorCodigo();
        // Buscar todos sem filtro
        try {
          const resp = await fetch(`/api/locais/buscar?termo=`);
          if (resp.ok) {
            const todosLocais = await resp.json();
            this.locaisFiltrados = todosLocais.slice(0, 50); // Limitar a 50
            this.showLocalDropdown = true;
            console.log('📋 [MOSTRAR TODOS] Exibindo', this.locaisFiltrados.length, 'locais');
          }
        } catch (e) {
          console.error('❌ [MOSTRAR TODOS] Erro:', e);
        }
      },

      // === Buscar Projetos Para Associar ===
      async buscarProjetosParaAssociar() {
        const termo = String(this.projetoAssociadoSearch || '').trim();
        // Se houver local selecionado, faz busca filtrada por local (mesmo que termo seja vazio)
        if (this.formData.CDLOCAL) {
          await this.buscarProjetosAssociadosPorLocal(this.formData.CDLOCAL, termo);
          return;
        }

        // Sem local: comportamento global de pesquisa por termo
        if (termo === '') {
          this.projetosAssociadosLista = [];
          return;
        }
        this.loadingProjetosAssociados = true;
        try {
          const resp = await fetch(`/api/projetos/pesquisar?q=${encodeURIComponent(termo)}`, {
            credentials: 'same-origin',
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            }
          });
          if (resp.ok) {
            this.projetosAssociadosLista = await resp.json();
            this.highlightedProjetoAssociadoIndex = this.projetosAssociadosLista.length > 0 ? 0 : -1;
          }
        } catch (e) {
          console.error('Falha busca projetos associados', e);
        } finally {
          this.loadingProjetosAssociados = false;
        }
      },
      abrirDropdownProjetos(force = false) {
        this.showProjetoDropdown = true;
        if (this.projetoSearch.trim() !== '') this.buscarProjetos();
      },
      // ========================================
      // ✅ SELECIONAR LOCAL DO DROPDOWN
      // ========================================
      async selecionarLocal(local) {
        console.log('✅ [SELECIONAR] Local clicado:', local);

        // Definir código do local
        this.formData.CDLOCAL = local.cdlocal;
        this.localSearch = local.cdlocal;
        this.showLocalDropdown = false;
        this.locaisFiltrados = [];
        this.highlightedLocalIndex = -1;

        console.log(`🔑 [SELECIONAR] formData.CDLOCAL definido para: ${this.formData.CDLOCAL}`);

        // Buscar TODOS os locais com esse código exato
        try {
          const resp = await fetch(`/api/locais/buscar?termo=${encodeURIComponent(local.cdlocal)}`);

          if (!resp.ok) {
            console.error('❌ [SELECIONAR] Erro ao buscar:', resp.status);
            return;
          }

          const todosLocais = await resp.json();
          const locaisDoMesmoCodigo = todosLocais.filter(l => String(l.cdlocal) === String(local.cdlocal));

          console.log(`📊 [SELECIONAR] Código ${local.cdlocal} tem ${locaisDoMesmoCodigo.length} local(is):`, locaisDoMesmoCodigo);

          // SUBSTITUIR array completo (garante reatividade)
          this.locais = locaisDoMesmoCodigo;

          // Aguardar Alpine.js processar
          await this.$nextTick();

          console.log(`🔢 [SELECIONAR] Computed property retorna: ${this.locaisComMesmoCodigo.length} local(is)`);

          // Selecionar automaticamente
          if (locaisDoMesmoCodigo.length === 1) {
            // Apenas 1 local - preencher automaticamente
            const unicoLocal = locaisDoMesmoCodigo[0];
            this.nomeLocal = unicoLocal.LOCAL || unicoLocal.delocal;
            this.localSelecionadoId = unicoLocal.id;
            this.formData.CDPROJETO = unicoLocal.CDPROJETO || '';
            this.projetoAssociadoSearch = unicoLocal.NOMEPROJETO ?
              `${unicoLocal.CDPROJETO} - ${unicoLocal.NOMEPROJETO}` :
              '';

            console.log(`✔️ [SELECIONAR] Único local: ${this.nomeLocal} | Projeto: ${this.projetoAssociadoSearch}`);

          } else {
            // Múltiplos locais - selecionar primeiro por padrão
            const primeiro = locaisDoMesmoCodigo[0];
            this.nomeLocal = primeiro.LOCAL || primeiro.delocal;
            this.localSelecionadoId = primeiro.id;
            this.formData.CDPROJETO = primeiro.CDPROJETO || '';
            this.projetoAssociadoSearch = primeiro.NOMEPROJETO ?
              `${primeiro.CDPROJETO} - ${primeiro.NOMEPROJETO}` :
              '';

            console.log(`🔽 [SELECIONAR] Múltiplos locais (${locaisDoMesmoCodigo.length}) - Dropdown DEVE aparecer!`);
            console.log(`   → Primeiro local: ${this.nomeLocal}`);
            console.log(`   → localSelecionadoId: ${this.localSelecionadoId}`);
            console.log(`   → locaisComMesmoCodigo.length: ${this.locaisComMesmoCodigo.length}`);
          }

        } catch (e) {
          console.error('❌ [SELECIONAR] Exceção:', e);
        }
      },
      // ========================================
      // 🔄 TROCAR LOCAL SELECIONADO (dropdown)
      // ========================================
      trocarLocalSelecionado(localId) {
        if (!localId) return;

        const local = this.locais.find(l => String(l.id) === String(localId));
        if (!local) {
          console.error('❌ [TROCAR LOCAL] ID não encontrado:', localId);
          return;
        }

        console.log('🔄 [TROCAR LOCAL] Novo local selecionado:', local);

        this.localSelecionadoId = local.id;
        this.nomeLocal = local.LOCAL || local.delocal;
        this.formData.CDPROJETO = local.CDPROJETO || '';
        this.projetoAssociadoSearch = local.NOMEPROJETO ?
          `${local.CDPROJETO} - ${local.NOMEPROJETO}` :
          '';

        console.log(`✅ [TROCAR LOCAL] Atualizado para: ${this.nomeLocal} | Projeto: ${this.projetoAssociadoSearch}`);
      },

      // ========================================
      // 🧹 LIMPAR SELEÇÃO DE LOCAL
      // ========================================
      limparLocal() {
        console.log('🧹 [LIMPAR] Limpando seleção de local');
        this.formData.CDLOCAL = '';
        this.localSearch = '';
        this.nomeLocal = '';
        this.localSelecionadoId = null;
        this.locais = [];
        this.formData.CDPROJETO = '';
        this.projetoAssociadoSearch = '';
      },
      selecionarProjetoEnter() {
        if (!this.showProjetoDropdown) return;
        if (this.highlightedProjetoIndex < 0 || this.highlightedProjetoIndex >= this.projetosLista.length) return;
        this.selecionarProjeto(this.projetosLista[this.highlightedProjetoIndex]);
      },
      // Nota: função principal de busca já declarada acima. Mantida referência para compatibilidade.

      selecionarProjetoAssociado(projeto) {
        this.formData.CDPROJETO = projeto.CDPROJETO;
        this.projetoAssociadoSearch = `${projeto.CDPROJETO} - ${projeto.NOMEPROJETO}`;
        this.showProjetoAssociadoDropdown = false;
        this.projetosAssociadosLista = [];
        this.highlightedProjetoAssociadoIndex = -1;
      },

      navegarProjetosAssociados(delta) {
        if (!this.showProjetoAssociadoDropdown || this.projetosAssociadosLista.length === 0) return;
        const max = this.projetosAssociadosLista.length - 1;
        if (this.highlightedProjetoAssociadoIndex === -1) this.highlightedProjetoAssociadoIndex = 0;
        else this.highlightedProjetoAssociadoIndex = Math.min(max, Math.max(0, this.highlightedProjetoAssociadoIndex + delta));
        this.$nextTick(() => {
          const list = this.$root.querySelector('[x-show="showProjetoAssociadoDropdown"]');
          if (!list) return;
          const items = list.querySelectorAll('[data-proj-assoc-item]');
          const el = items[this.highlightedProjetoAssociadoIndex];
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
      // Novo método: busca projetos vinculados ao local (aceita termo opcional)
      buscarProjetosAssociadosPorLocal: async function(cdlocal, termo = '') {
        this.loadingProjetosAssociados = true;
        try {
          const url = `/api/projetos/por-local/${cdlocal}` + (termo ? `?q=${encodeURIComponent(termo)}` : '');
          const resp = await fetch(url, {
            credentials: 'same-origin',
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            }
          });
          if (resp.ok) {
            this.projetosAssociadosLista = await resp.json();
            this.highlightedProjetoAssociadoIndex = this.projetosAssociadosLista.length > 0 ? 0 : -1;
          }
        } catch (e) {
          console.error('Falha busca projetos por local', e);
        } finally {
          this.loadingProjetosAssociados = false;
        }
      },

      selecionarProjetoAssociadoEnter() {
        if (!this.showProjetoAssociadoDropdown) return;
        if (this.highlightedProjetoAssociadoIndex < 0 || this.highlightedProjetoAssociadoIndex >= this.projetosAssociadosLista.length) return;
        this.selecionarProjetoAssociado(this.projetosAssociadosLista[this.highlightedProjetoAssociadoIndex]);
      },

      limparProjetoAssociado() {
        this.formData.CDPROJETO = '';
        this.projetoAssociadoSearch = '';
        this.highlightedProjetoAssociadoIndex = -1;
        this.showProjetoAssociadoDropdown = true;
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
        this.showLocalDropdown = true;
        // Sempre busca quando há termo ou é forçado
        if (String(this.localSearch || '').trim() !== '' || force) {
          this.buscarLocaisDisponiveis();
        }
      },

      selecionarLocal(l) {
        // Seleciona apenas o local, sem carregar projeto automaticamente
        this.formData.CDLOCAL = l.cdlocal;
        this.localSearch = l.cdlocal; // Apenas o código do local
        this.nomeLocal = l.LOCAL || l.delocal; // Nome do local na lateral
        this.showLocalDropdown = false;

        this.locaisFiltrados = [];
        this.highlightedLocalIndex = -1;

        console.log('Local selecionado:', {
          cdlocal: l.cdlocal,
          nome: l.LOCAL || l.delocal
        });
      },
      selecionarLocalEnter() {
        if (!this.showLocalDropdown) return;
        if (this.highlightedLocalIndex < 0 || this.highlightedLocalIndex >= this.locaisFiltrados.length) return;
        this.selecionarLocal(this.locaisFiltrados[this.highlightedLocalIndex]);
      },
      limparLocal() {
        this.formData.CDLOCAL = '';
        this.localSearch = '';
        this.nomeLocal = '';
        this.locaisFiltrados = [];
        this.highlightedLocalIndex = -1;
        this.showLocalDropdown = true;
      },

      // ========================================
      // 🆕 NOVAS FUNÇÕES - SISTEMA DROPDOWN SEMPRE
      // ✅ FUNÇÃO SIMPLES: Buscar local por código
      async buscarLocalPorCodigo() {
        const codigo = String(this.codigoLocalDigitado || '').trim();

        // Limpar se vazio
        if (codigo === '') {
          this.locaisEncontrados = [];
          this.localNome = '';
          this.nomeLocalBusca = '';
          this.formData.CDLOCAL = '';
          this.formData.CDPROJETO = '';
          this.projetoAssociadoSearch = '';
          this.localSelecionadoId = null;
          return;
        }

        try {
          const resp = await fetch(`/api/locais/buscar?termo=${encodeURIComponent(codigo)}`);
          if (!resp.ok) {
            this.locaisEncontrados = [];
            this.localNome = '';
            this.nomeLocalBusca = '';
            return;
          }

          const locais = await resp.json();
          this.locaisEncontrados = locais;

          // Se encontrou exatamente 1, selecionar automaticamente
          if (locais.length === 1) {
            const primeiro = locais[0];
            this.localNome = primeiro.LOCAL || primeiro.delocal || '';
            this.nomeLocalBusca = this.localNome; // Preencher campo de nome
            this.formData.CDLOCAL = primeiro.id; // Usar ID em vez de cdlocal
            this.localSelecionadoId = primeiro.id;
            this.formData.CDPROJETO = primeiro.CDPROJETO || '';
            this.projetoAssociadoSearch = primeiro.CDPROJETO && primeiro.NOMEPROJETO ?
              `${primeiro.CDPROJETO} - ${primeiro.NOMEPROJETO}` :
              '';
          } else if (locais.length > 1) {
            // Múltiplos locais - limpar seleção e habilitar autocomplete
            this.localNome = '';
            this.nomeLocalBusca = '';
            this.formData.CDLOCAL = '';
            this.localSelecionadoId = null;

            // Pegar projeto do primeiro (todos devem ter o mesmo)
            const primeiro = locais[0];
            this.formData.CDPROJETO = primeiro.CDPROJETO || '';
            this.projetoAssociadoSearch = primeiro.CDPROJETO && primeiro.NOMEPROJETO ?
              `${primeiro.CDPROJETO} - ${primeiro.NOMEPROJETO}` :
              '';
          } else {
            // Nenhum local encontrado
            this.localNome = '';
            this.nomeLocalBusca = '';
            this.formData.CDLOCAL = '';
            this.localSelecionadoId = null;
          }
        } catch (error) {
          console.error('Erro ao buscar local:', error);
          this.locaisEncontrados = [];
          this.localNome = '';
          this.nomeLocalBusca = '';
        }
      },

      // ========================================
      // 🆕 FUNÇÕES DO MODAL CRIAR PROJETO/LOCAL
      // ========================================

      /**
       * Buscar projeto existente pelo código
       * Sem delay: começa a buscar imediatamente com debounce
       * Mantém loading até encontrar ou exibir formulário
       */
      buscarProjetoExistente() {
        const cdproj = String(this.novoProjeto.cdprojetoBusca || '').trim();

        if (!cdproj) {
          console.log('🔍 [BUSCAR PROJETO] Campo vazio, limpando dados');
          // Cancelar timeout anterior se existir
          if (this.buscaProjetoTimeout) {
            clearTimeout(this.buscaProjetoTimeout);
            this.buscaProjetoTimeout = null;
          }
          this.novoProjeto.cdprojeto = '';
          this.novoProjeto.nmProjeto = '';
          this.novoProjeto.cdlocal = '';
          this.novoProjeto.nomeLocal = '';
          this.erroCriacaoProjeto = '';
          this.carregandoProjeto = false;
          return;
        }

        // Cancelar busca anterior se ainda estiver pendente
        if (this.buscaProjetoTimeout) {
          clearTimeout(this.buscaProjetoTimeout);
        }

        console.log('⌨️ [BUSCAR PROJETO] Digitando:', cdproj);
        this.carregandoProjeto = true; // Mostrar loading IMEDIATAMENTE

        // Debounce de 300ms para não fazer muitas requisições enquanto digita
        this.buscaProjetoTimeout = setTimeout(() => {
          console.log('🔍 [BUSCAR PROJETO] Executando busca após debounce');
          this._executarBuscaProjetoExistente(cdproj);
        }, 300); // 300ms debounce
      },

      async _executarBuscaProjetoExistente(cdproj) {
        console.log('🔍 [BUSCAR PROJETO] Buscando local/projeto:', cdproj);
        // carregandoProjeto já está true

        try {
          // Buscar na API de locais para ver se esse código de local já existe
          const responseLocal = await fetch(`/api/locais/buscar?termo=${encodeURIComponent(cdproj)}`);

          if (!responseLocal.ok) {
            throw new Error(`Erro HTTP ${responseLocal.status}`);
          }

          const dataLocais = await responseLocal.json();
          console.log('🔍 [BUSCAR PROJETO] Locais encontrados:', dataLocais);

          // Procurar por um local que tenha código EXATAMENTE igual
          let localEncontrado = null;

          if (Array.isArray(dataLocais)) {
            localEncontrado = dataLocais.find(l => String(l.cdlocal) === String(cdproj));
          }

          if (localEncontrado && localEncontrado.CDPROJETO) {
            console.log('✅ [BUSCAR PROJETO] Local encontrado com projeto associado:', localEncontrado);

            // Este local já existe e tem um projeto associado
            // Preencher dados do projeto associado ao local
            this.novoProjeto.cdprojeto = localEncontrado.CDPROJETO;
            this.novoProjeto.nmProjeto = localEncontrado.NOMEPROJETO || 'Projeto não nomeado';

            // Limpar campo de nome do local para o usuário preencher um NOVO nome
            this.novoProjeto.nomeLocal = '';

            console.log('✅ [BUSCAR PROJETO] Projeto do local preenchido, focando no campo de nome do local');

            // Focar no campo de nome do local
            this.$nextTick(() => {
              this.$refs.inputNomeLocal?.focus();
            });
          } else {
            console.log('ℹ️ [BUSCAR PROJETO] Local não encontrado - exibindo formulário para criar novo');

            // Deixar os campos de criação aparecerem (mantém loading visível até aqui)
            this.novoProjeto.cdprojeto = '';
            this.novoProjeto.nmProjeto = '';
          }

        } catch (error) {
          console.error('❌ [BUSCAR PROJETO] Erro:', error);

          // Permitir criar novo (sem mensagem de erro)
          this.novoProjeto.cdprojeto = '';
          this.novoProjeto.nmProjeto = '';
        } finally {
          this.carregandoProjeto = false;
          this.buscaProjetoTimeout = null;
        }
      },

      /**
       * Função auxiliar para aguardar com delay
       */
      _aguardarComDelay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
      },

      /**
       * Abrir modal de criar novo projeto/local
       * Salva estado atual do formulário antes de abrir
       */
      abrirModalCriarProjeto() {
        console.log('🟢 [MODAL CRIAR] Iniciando abertura do modal');

        // 1. Salvar estado atual do formulário
        this.estadoTemporario = {
          NUPATRIMONIO: this.formData.NUPATRIMONIO,
          NUMOF: this.formData.NUMOF,
          NUSEQOBJ: this.formData.NUSEQOBJ,
          DEOBJETO: this.formData.DEOBJETO,
          DEHISTORICO: this.formData.DEHISTORICO,
          MARCA: this.formData.MARCA,
          MODELO: this.formData.MODELO,
          SITUACAO: document.getElementById('SITUACAO')?.value || 'EM USO',
          NMPLANTA: this.formData.NMPLANTA,
          CDMATRFUNCIONARIO: this.formData.CDMATRFUNCIONARIO,
          DTAQUISICAO: this.formData.DTAQUISICAO,
          DTBAIXA: this.formData.DTBAIXA,
          // Campos de busca
          patSearch: this.patSearch,
          codigoSearch: this.codigoSearch,
          userSearch: this.userSearch,
          userSelectedName: this.userSelectedName,
        };

        console.log('🟢 [MODAL CRIAR] Estado salvo:', this.estadoTemporario);

        // 2. Preencher código do local se já foi digitado
        this.novoProjeto.cdlocal = this.codigoLocalDigitado || '';
        this.novoProjeto.nomeLocal = '';
        this.novoProjeto.cdprojeto = '';

        // 3. Limpar erros anteriores
        this.erroCriacaoProjeto = '';
        this.salvandoCriacaoProjeto = false;

        // 4. Abrir modal
        this.modalCriarProjetoOpen = true;

        // 5. Focar no campo de CÓDIGO DO PROJETO (busca)
        this.$nextTick(() => {
          const inputCodProjetoBusca = this.$refs.inputCodProjetoBusca;
          if (inputCodProjetoBusca) {
            inputCodProjetoBusca.focus();
            console.log('✅ [MODAL CRIAR] Focus no input "Código do Projeto"');
          } else {
            console.warn('⚠️ [MODAL CRIAR] Input "Código do Projeto" não encontrado');
          }
        });

        console.log('🟢 [MODAL CRIAR] Modal aberto com sucesso');
      },

      /**
       * Fechar modal de criar projeto/local
       */
      fecharModalCriarProjeto() {
        console.log('🔴 [MODAL CRIAR] Fechando modal');

        if (this.salvandoCriacaoProjeto) {
          console.log('🔴 [MODAL CRIAR] Salvamento em andamento, cancelando fechamento');
          return;
        }

        // Cancelar timeout de busca se existir
        if (this.buscaProjetoTimeout) {
          clearTimeout(this.buscaProjetoTimeout);
          this.buscaProjetoTimeout = null;
          console.log('⏱️ [MODAL CRIAR] Timeout de busca cancelado');
        }

        this.modalCriarProjetoOpen = false;
        this.novoProjeto = {
          cdlocal: '',
          nomeLocal: '',
          cdprojeto: '',
          cdprojetoBusca: '',
          nmProjeto: '',
        };
        this.erroCriacaoProjeto = '';
        this.salvandoCriacaoProjeto = false;
        this.carregandoProjeto = false;

        // 🎯 AUTO-FOCUS NO CAMPO "NOME DO LOCAL" AO FECHAR MODAL
        this.$nextTick(() => {
          const nomeLocalInput = document.getElementById('NOMELOCAL_INPUT');
          if (nomeLocalInput && !nomeLocalInput.disabled) {
            nomeLocalInput.focus();
            console.log('✅ [MODAL CRIAR] Focus no campo "Nome do Local"');
          } else {
            console.log('⚠️ [MODAL CRIAR] Campo "Nome do Local" desabilitado ou não encontrado');
          }
        });

        console.log('🔴 [MODAL CRIAR] Modal fechado');
      },

      /**
       * Salvar novo projeto/local
       * Após salvar, recarrega a página e restaura o estado
       */
      async salvarNovoProjeto() {
        console.log('💾 [SALVAR PROJETO] ════════════════════════════════');
        console.log('💾 [SALVAR PROJETO] Iniciando salvamento');
        console.log('💾 [SALVAR PROJETO] Dados:', this.novoProjeto);

        const nomeLocal = String(this.novoProjeto.nomeLocal || '').trim();
        const projetoEncontrado = this.novoProjeto.nmProjeto !== ''; // Se nmProjeto preenchido, projeto já existia

        // VALIDAÇÃO INICIAL: Verificar se projeto foi selecionado
        if (!nomeLocal) {
          this.erroCriacaoProjeto = '❌ Digite o nome do local';
          console.log('💾 [SALVAR PROJETO] Erro: Nome do local vazio');
          return;
        }

        if (!projetoEncontrado && !String(this.novoProjeto.cdprojetoBusca || '').trim()) {
          this.erroCriacaoProjeto = '❌ ⚠️ OBRIGATÓRIO: Digite o nome/código do projeto e aguarde a busca, ou selecione da lista';
          console.log('💾 [SALVAR PROJETO] Erro: Projeto não foi buscado/selecionado. nmProjeto:', this.novoProjeto.nmProjeto, 'cdprojetoBusca:', this.novoProjeto.cdprojetoBusca);
          return;
        }

        // ===== CENÁRIO 1: Projeto ENCONTRADO =====
        if (projetoEncontrado) {
          // Validação 1: Nome do local obrigatório
          if (!nomeLocal) {
            this.erroCriacaoProjeto = '❌ Digite o nome do local';
            console.log('💾 [SALVAR PROJETO] Erro: Nome do local vazio');
            return;
          }

          const cdlocal = String(this.novoProjeto.cdprojetoBusca || '').trim();
          const cdprojeto = Number(this.novoProjeto.cdprojeto) || null; // Manter como número, não string!

          if (!cdlocal) {
            this.erroCriacaoProjeto = '❌ Código do projeto não encontrado';
            return;
          }

          if (!cdprojeto) {
            this.erroCriacaoProjeto = '❌ Projeto não foi carregado corretamente';
            return;
          }

          this.salvandoCriacaoProjeto = true;
          this.erroCriacaoProjeto = '';

          try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            // Usar o código digitado (cdprojetoBusca) como cdlocal
            const payload = {
              cdlocal: cdlocal,
              local: nomeLocal,
              cdprojeto: cdprojeto,
            };

            console.log('💾 [SALVAR PROJETO] Payload (Projeto Encontrado):', payload);
            console.log('💾 [SALVAR PROJETO] CSRF Token:', csrfToken);

            const response = await fetch('/api/locais/criar', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
              },
              credentials: 'same-origin',
              body: JSON.stringify(payload)
            });

            console.log('💾 [SALVAR PROJETO] Status HTTP:', response.status);

            if (!response.ok) {
              let serverMsg = '';
              try {
                const errData = await response.clone().json();
                serverMsg = errData.message || JSON.stringify(errData.errors || errData);
                console.log('💾 [SALVAR PROJETO] Erro do servidor:', errData);
              } catch (_) {
                serverMsg = await response.text();
                console.log('💾 [SALVAR PROJETO] Erro do servidor (text):', serverMsg);
              }
              throw new Error(`Erro HTTP ${response.status}: ${serverMsg}`);
            }

            const data = await response.json();
            console.log('💾 [SALVAR PROJETO] Resposta do servidor:', data);

            if (data.success) {
              console.log('✅ [SALVAR PROJETO] Local criado com sucesso!');
              console.log('✅ [SALVAR PROJETO] ID do local:', data.local_id);

              // Salvar estado no sessionStorage antes de recarregar
              const estadoParaSalvar = {
                ...this.estadoTemporario,
                codigoLocalDigitado: cdlocal,
                timestamp: Date.now()
              };

              sessionStorage.setItem('patrimonioFormState', JSON.stringify(estadoParaSalvar));
              console.log('✅ [SALVAR PROJETO] Estado salvo no sessionStorage:', estadoParaSalvar);

              // Recarregar a página
              console.log('✅ [SALVAR PROJETO] Recarregando página...');
              window.location.reload();
            } else {
              this.erroCriacaoProjeto = data.message || '❌ Erro ao criar local';
              console.log('❌ [SALVAR PROJETO] Falha:', this.erroCriacaoProjeto);
              this.salvandoCriacaoProjeto = false;
            }

          } catch (error) {
            console.error('❌ [SALVAR PROJETO] Exceção:', error);
            this.erroCriacaoProjeto = error.message || '❌ Erro ao criar local';
            this.salvandoCriacaoProjeto = false;
          }
        }
        // ===== CENÁRIO 2: Projeto NÃO ENCONTRADO (criar novo) =====
        else {
          const cdlocal = String(this.novoProjeto.cdlocal || '').trim();
          const cdprojeto = Number(this.novoProjeto.cdprojeto) || null; // Manter como número, não string!

          // Validações
          if (!cdlocal) {
            this.erroCriacaoProjeto = '❌ Digite o código do projeto';
            console.log('💾 [SALVAR PROJETO] Erro: Código do projeto vazio');
            return;
          }

          if (!nomeLocal) {
            this.erroCriacaoProjeto = '❌ Digite o nome do local';
            console.log('💾 [SALVAR PROJETO] Erro: Nome do local vazio');
            return;
          }

          if (!cdprojeto) {
            this.erroCriacaoProjeto = '❌ Selecione um projeto';
            console.log('💾 [SALVAR PROJETO] Erro: Projeto não selecionado');
            return;
          }

          this.salvandoCriacaoProjeto = true;
          this.erroCriacaoProjeto = '';

          try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            // Criar novo com todos os dados
            const payload = {
              cdlocal: cdlocal,
              local: nomeLocal,
              cdprojeto: cdprojeto,
            };

            console.log('💾 [SALVAR PROJETO] Payload (Criar Novo):', payload);
            console.log('💾 [SALVAR PROJETO] CSRF Token:', csrfToken);

            const response = await fetch('/api/locais/criar', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
              },
              credentials: 'same-origin',
              body: JSON.stringify(payload)
            });

            console.log('💾 [SALVAR PROJETO] Status HTTP:', response.status);

            if (!response.ok) {
              let serverMsg = '';
              try {
                const errData = await response.clone().json();
                serverMsg = errData.message || JSON.stringify(errData.errors || errData);
                console.log('💾 [SALVAR PROJETO] Erro do servidor:', errData);
              } catch (_) {
                serverMsg = await response.text();
                console.log('💾 [SALVAR PROJETO] Erro do servidor (text):', serverMsg);
              }
              throw new Error(`Erro HTTP ${response.status}: ${serverMsg}`);
            }

            const data = await response.json();
            console.log('💾 [SALVAR PROJETO] Resposta do servidor:', data);

            if (data.success) {
              console.log('✅ [SALVAR PROJETO] Local criado com sucesso!');
              console.log('✅ [SALVAR PROJETO] ID do local:', data.local_id);

              // Salvar estado no sessionStorage antes de recarregar
              const estadoParaSalvar = {
                ...this.estadoTemporario,
                codigoLocalDigitado: cdlocal,
                timestamp: Date.now()
              };

              sessionStorage.setItem('patrimonioFormState', JSON.stringify(estadoParaSalvar));
              console.log('✅ [SALVAR PROJETO] Estado salvo no sessionStorage:', estadoParaSalvar);

              // Recarregar a página
              console.log('✅ [SALVAR PROJETO] Recarregando página...');
              window.location.reload();
            } else {
              this.erroCriacaoProjeto = data.message || '❌ Erro ao criar local';
              console.log('❌ [SALVAR PROJETO] Falha:', this.erroCriacaoProjeto);
              this.salvandoCriacaoProjeto = false;
            }

          } catch (error) {
            console.error('❌ [SALVAR PROJETO] Exceção:', error);
            this.erroCriacaoProjeto = error.message || '❌ Erro ao criar local';
            this.salvandoCriacaoProjeto = false;
          }
        }

        console.log('💾 [SALVAR PROJETO] ════════════════════════════════');
      },

      /**
       * Selecionar local do dropdown (quando múltiplos)
       */
      async selecionarLocalDoDropdown(localId) {
        if (!localId) return;

        console.log('📍 [SELECIONAR LOCAL] ID selecionado:', localId);

        const local = this.locaisEncontrados.find(l => String(l.id) === String(localId));
        if (!local) {
          console.error('❌ [SELECIONAR LOCAL] Local não encontrado:', localId);
          return;
        }

        console.log('📍 [SELECIONAR LOCAL] Local encontrado:', local);

        // Preencher dados do local
        this.formData.CDLOCAL = local.id;
        this.formData.CDPROJETO = local.CDPROJETO || '';
        this.localNome = local.LOCAL || local.delocal || '';
        this.projetoAssociadoSearch = local.NOMEPROJETO ?
          `${local.CDPROJETO} - ${local.NOMEPROJETO}` :
          '';

        console.log('📍 [SELECIONAR LOCAL] Dados preenchidos:');
        console.log('   - CDLOCAL:', this.formData.CDLOCAL);
        console.log('   - CDPROJETO:', this.formData.CDPROJETO);
        console.log('   - Nome:', this.localNome);
        console.log('   - Projeto:', this.projetoAssociadoSearch);
      },

      // ========================================
      // FUNÇÃO ANTIGA (COMPLEXA) - MANTIDA PARA NÃO QUEBRAR
      // ========================================
      async buscarLocaisPorCodigoDigitado() {
        const codigo = String(this.codigoLocalDigitado || '').trim();

        console.log('� [DEBUG buscarLocais] ═══════════════════════════════');
        console.log('🟠 [DEBUG buscarLocais] Função chamada');
        console.log('🟠 [DEBUG buscarLocais] Código digitado:', codigo);
        console.log('🟠 [DEBUG buscarLocais] formData.CDPROJETO ANTES:', this.formData.CDPROJETO);
        console.log('🟠 [DEBUG buscarLocais] projetoAssociadoSearch ANTES:', this.projetoAssociadoSearch);

        // Limpar se vazio
        if (codigo === '') {
          console.log('🟠 [DEBUG buscarLocais] Código vazio, limpando tudo');
          this.locaisEncontrados = [];
          this.resultadosBusca = [];
          this.mostrarDropdownBusca = false;
          this.localSelecionadoId = null;
          this.formData.CDLOCAL = '';
          this.formData.CDPROJETO = '';
          this.projetoAssociadoSearch = '';
          console.log('🟠 [DEBUG buscarLocais] ✅ Tudo limpo');
          return;
        }

        try {
          const resp = await fetch(`/api/locais/buscar?termo=${encodeURIComponent(codigo)}`);
          if (!resp.ok) {
            console.error('❌ [BUSCA] Erro HTTP:', resp.status);
            this.resultadosBusca = [];
            this.locaisEncontrados = [];
            return;
          }

          const todosLocais = await resp.json();

          // Mostrar TODOS os locais que contenham o código (não apenas exato)
          this.resultadosBusca = todosLocais;
          // Agrupar por cdlocal para apresentação condensada
          const grupos = {};
          this.resultadosBusca.forEach(l => {
            const k = String(l.cdlocal);
            if (!grupos[k]) grupos[k] = {
              cdlocal: k,
              count: 0,
              samples: []
            };
            grupos[k].count += 1;
            if (grupos[k].samples.length < 1) grupos[k].samples.push(l);
          });
          this.resultadosBuscaGrouped = Object.values(grupos).map(g => ({
            cdlocal: g.cdlocal,
            count: g.count,
            displayName: g.samples[0]?.LOCAL || g.samples[0]?.delocal || ''
          }));
          this.mostrarDropdownBusca = true;

          // Para o dropdown final, filtrar apenas código exato
          // PRIORIZAR: Se há múltiplos, escolher o que tem projeto associado (descartando vazios)
          this.locaisEncontrados = todosLocais.filter(l => String(l.cdlocal) === codigo);

          // Se há múltiplos com mesmo código, priorizar o que tem CDPROJETO preenchido
          if (this.locaisEncontrados.length > 1) {
            const comProjeto = this.locaisEncontrados.filter(l => l.CDPROJETO && String(l.CDPROJETO).trim() !== '');
            if (comProjeto.length > 0) {
              console.log(`✅ [BUSCA] Múltiplos locais encontrados. Priorizando ${comProjeto.length} com projeto associado`);
              this.locaisEncontrados = comProjeto;
            }
          }

          console.log(`✅ [BUSCA] ${this.resultadosBusca.length} resultado(s) | ${this.locaisEncontrados.length} com código exato`);
          console.log('🟠 [DEBUG buscarLocais] formData.CDPROJETO DEPOIS de buscar:', this.formData.CDPROJETO);
          console.log('🟠 [DEBUG buscarLocais] projetoAssociadoSearch DEPOIS de buscar:', this.projetoAssociadoSearch);

          // Se encontrou exatamente 1 local com código exato, auto-selecionar
          if (this.locaisEncontrados.length === 1) {
            console.log('🟠 [DEBUG buscarLocais] Auto-selecionando único local encontrado');
            await this.$nextTick();
            this.localSelecionadoId = this.locaisEncontrados[0].id;
            console.log('🟠 [DEBUG buscarLocais] Chamando selecionarLocalDoDropdown...');
            await this.selecionarLocalDoDropdown(this.locaisEncontrados[0].id);
            console.log('🟠 [DEBUG buscarLocais] formData.CDPROJETO DEPOIS de selecionarLocalDoDropdown:', this.formData.CDPROJETO);
            this.mostrarDropdownBusca = false;
          } else {
            console.log('🟠 [DEBUG buscarLocais] Múltiplos locais, resetando seleção');
            // Se mais de 1, resetar seleção para forçar escolha manual
            this.localSelecionadoId = null;
          }

          console.log('🟠 [DEBUG buscarLocais] ═══════════════════════════════');

        } catch (e) {
          console.error('❌ [BUSCA] Exceção:', e);
          this.resultadosBusca = [];
          this.locaisEncontrados = [];
        }
      },

      // Alias para compatibilidade
      async buscarLocais() {
        return await this.buscarLocaisPorCodigoDigitado();
      },

      async abrirBuscaLocais() {
        console.log('🔍 [LUPA] Abrindo busca de locais');
        try {
          const resp = await fetch('/api/locais/buscar?termo=');
          if (resp.ok) {
            this.resultadosBusca = (await resp.json()).slice(0, 100); // Limitar a 100
            this.mostrarDropdownBusca = true;
            console.log(`✅ [LUPA] ${this.resultadosBusca.length} locais disponíveis`);
          }
        } catch (e) {
          console.error('❌ [LUPA] Erro:', e);
        }
      },

      async selecionarDaBusca(local) {
        console.log('✅ [BUSCA] Local selecionado:', local);

        // Se o usuário clicar especificamente num item (não no grupo), usar o mesmo fluxo de grupo
        this.selecionarGrupoBusca(local.cdlocal);
      },

      // Selecionar grupo (cdlocal) a partir do dropdown de busca agrupado
      async selecionarGrupoBusca(cdlocal) {
        if (!cdlocal) return;
        this.codigoLocalDigitado = cdlocal;
        // Buscar todos com esse código para popular o dropdown final
        try {
          const resp = await fetch(`/api/locais/buscar?termo=${encodeURIComponent(cdlocal)}`);
          if (resp.ok) {
            const todosLocais = await resp.json();
            this.locaisEncontrados = todosLocais.filter(l => String(l.cdlocal) === String(cdlocal));
          } else {
            this.locaisEncontrados = [];
          }
        } catch (e) {
          console.error('❌ [GRUPO] Erro ao buscar:', e);
          this.locaisEncontrados = [];
        }

        // Abrir dropdown final para seleção manual (se houver mais de 1)
        this.localSelecionadoId = this.locaisEncontrados.length === 1 ? this.locaisEncontrados[0].id : null;
        if (this.locaisEncontrados.length === 1) {
          await this.selecionarLocalDoDropdown(this.locaisEncontrados[0].id);
        }
        this.mostrarDropdownBusca = false;
      },

      async selecionarLocalDoDropdown(localId) {
        if (!localId) return;

        const local = this.locaisEncontrados.find(l => String(l.id) === String(localId));
        if (!local) {
          console.error('❌ [NOVO] Local ID não encontrado:', localId);
          return;
        }

        console.log('✅ [NOVO] Local selecionado do dropdown:', local);

        // Preencher formData
        this.formData.CDLOCAL = local.cdlocal;
        this.formData.CDPROJETO = local.CDPROJETO || '';
        this.projetoAssociadoSearch = local.NOMEPROJETO ?
          `${local.CDPROJETO} - ${local.NOMEPROJETO}` : '';

        // Atualizar também variáveis antigas (compatibilidade)
        this.localSearch = local.cdlocal;
        this.nomeLocal = local.LOCAL || local.delocal;
        this.locais = [local];

        console.log(`   → CDLOCAL: ${this.formData.CDLOCAL}`);
        console.log(`   → CDPROJETO: ${this.formData.CDPROJETO}`);
        console.log(`   → Projeto: ${this.projetoAssociadoSearch}`);
      },

      limparLocalCompleto() {
        console.log('🧹 [LIMPAR] Limpando tudo');

        // Novas variáveis
        this.codigoLocalDigitado = '';
        this.locaisEncontrados = [];
        this.localSelecionadoId = null;
        this.resultadosBusca = [];
        this.mostrarDropdownBusca = false;

        // Variáveis antigas
        this.formData.CDLOCAL = '';
        this.localSearch = '';
        this.nomeLocal = '';
        this.locaisFiltrados = [];
        this.locais = [];

        // Limpar projeto também
        this.formData.CDPROJETO = '';
        this.projetoAssociadoSearch = '';
      },
      // ========================================

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









      focusNext(currentElement) {
        // Define a sequência exata de inputs segundo o fluxo definido
        const sequencia = [
          '#NUPATRIMONIO', // 1. Nº Patrimônio
          '#NUMOF', // 2. Nº OC
          '#NUSEQOBJ', // 3. Código
          // 4. Se código NÃO existir: Descrição do Código (DEOBJETO)
          // 5. Observação (DEHISTORICO)
          // 6. Botão + (abrindo espaço, com tabindex 6, pulará para o próximo)
          '#CDLOCAL_INPUT', // 7. Cód Local
          '#NOMELOCAL_INPUT', // 8. Nome Local
          '#NMPLANTA', // 9. Cód Termo
          '#MARCA', // 10. Marca
          '#MODELO', // 11. Modelo
          '#SITUACAO', // 12. Situação
          '#matricula_busca', // 13. Matrícula Responsável
          '#DTAQUISICAO', // 14. Data de Aquisição
          '#DTBAIXA', // 15. Data de Baixa
        ];

        const currentId = currentElement.id;
        let currentIndex = sequencia.indexOf('#' + currentId);

        // Lógica especial: se está em NUSEQOBJ (código) e código NÃO existe, pula para DEOBJETO
        if (currentId === 'NUSEQOBJ' && !this.formData.NUSEQOBJ) {
          const descricaoEl = document.getElementById('DEOBJETO');
          if (descricaoEl && !descricaoEl.readOnly && this.isNovoCodigo) {
            descricaoEl.focus();
            return;
          }
        }

        // Lógica: após DEHISTORICO (observação), pula para o botão + (que abrirá modal)
        if (currentId === 'DEHISTORICO') {
          const btnPlus = document.querySelector('button[title*="Criar novo local"]');
          if (btnPlus) {
            btnPlus.focus();
            return;
          }
        }

        // Buscar próximo elemento na sequência
        if (currentIndex >= 0 && currentIndex < sequencia.length - 1) {
          const nextId = sequencia[currentIndex + 1];
          const nextElement = document.querySelector(nextId);

          if (nextElement && !nextElement.disabled && !nextElement.readOnly) {
            nextElement.focus();
            return;
          }
        }

        // Se chegou ao final, focar no submit
        const submitBtn = currentElement.closest('form')?.querySelector('button[type="submit"]');
        if (submitBtn) {
          submitBtn.focus();
        }
      },

      // ========================================
      // 🔍 FUNÇÃO DE DEBUG COMPLETO
      // ========================================
      debugEstadoCompleto(momento) {
        console.log(`\n╔════════════════════════════════════════════════════════════╗`);
        console.log(`║  🔍 DEBUG COMPLETO - ${momento.toUpperCase().padEnd(40)} ║`);
        console.log(`╚════════════════════════════════════════════════════════════╝\n`);

        // 1. Estado do Alpine.js
        console.log('📊 [ALPINE STATE] locaisEncontrados:', JSON.parse(JSON.stringify(this.locaisEncontrados)));
        console.log('📊 [ALPINE STATE] localSelecionadoId:', this.localSelecionadoId);
        console.log('📊 [ALPINE STATE] formData.CDLOCAL:', this.formData.CDLOCAL);
        console.log('📊 [ALPINE STATE] formData.CDPROJETO:', this.formData.CDPROJETO);

        // 2. DOM do Select
        const selectElement = document.querySelector('select[name="CDLOCAL"]');
        if (selectElement) {
          console.log('🌐 [DOM SELECT] Encontrado:', true);
          console.log('� [DOM SELECT] Value atual:', selectElement.value);
          console.log('🌐 [DOM SELECT] Total de options:', selectElement.options.length);
          console.log('🌐 [DOM SELECT] Options disponíveis:');
          Array.from(selectElement.options).forEach((opt, idx) => {
            console.log(`   ${idx}: value="${opt.value}" text="${opt.text}" selected=${opt.selected}`);
          });
        } else {
          console.error('❌ [DOM SELECT] NÃO ENCONTRADO!');
        }

        // 3. Comparação
        console.log('\n🔍 [COMPARAÇÃO]:');
        console.log('   Alpine localSelecionadoId:', this.localSelecionadoId, typeof this.localSelecionadoId);
        console.log('   DOM select.value:', selectElement?.value, typeof selectElement?.value);
        console.log('   Match?', String(this.localSelecionadoId) === String(selectElement?.value));

        console.log(`\n════════════════════════════════════════════════════════════\n`);
      },

      // ========================================
      // �🆕 MODAL CRIAR NOVO LOCAL (SIMPLES)
      // ========================================
      abrirModalCriarLocal() {
        console.log('🔘 [BOTÃO +] Clicado!', {
          codigoLocalDigitado: this.codigoLocalDigitado,
          CDPROJETO: this.formData.CDPROJETO,
          projetoAssociadoSearch: this.projetoAssociadoSearch
        });

        if (!this.codigoLocalDigitado) {
          alert('Digite um código de local primeiro');
          return;
        }
        if (!this.formData.CDPROJETO) {
          alert('Selecione um local com projeto associado primeiro');
          return;
        }

        console.log('🔘 [MODAL ABRIR] Modal vai abrir');
        console.log('🔘 [MODAL ABRIR] formData.CDPROJETO:', this.formData.CDPROJETO);
        console.log('🔘 [MODAL ABRIR] projetoAssociadoSearch:', this.projetoAssociadoSearch);

        this.modalCriarNovoLocalOpen = true;
        this.novoLocalNome = '';
        this.erroNovoLocal = '';
        this.salvandoNovoLocal = false;

        // Focar no input do nome após o modal abrir
        this.$nextTick(() => {
          this.$refs.inputNovoLocal?.focus();
        });
      },

      fecharModalCriarNovoLocal() {
        console.log('🔘 [MODAL FECHAR] Modal vai fechar');
        console.log('🔘 [MODAL FECHAR] processandoCriacaoLocal:', this.processandoCriacaoLocal);

        this.modalCriarNovoLocalOpen = false;
        this.novoLocalNome = '';
        this.erroNovoLocal = '';
        this.salvandoNovoLocal = false;
      },

      async salvarNovoLocal() {
        console.log('\n\n');
        console.log('🟣🟣🟣🟣🟣🟣🟣🟣🟣🟣🟣🟣🟣🟣🟣🟣🟣🟣🟣🟣🟣🟣🟣🟣🟣🟣');
        console.log('🟣 [DEBUG SALVAR] ═══════════════════════════════════');
        console.log('🟣 [DEBUG SALVAR] ✨✨✨ FUNÇÃO CHAMADA! ✨✨✨');
        console.log('🟣 [DEBUG SALVAR] Iniciando salvarNovoLocal()');
        console.log('🟣 [DEBUG SALVAR] Estado ANTES de salvar:');
        console.log('   - novoLocalNome:', this.novoLocalNome);
        console.log('   - codigoLocalDigitado:', this.codigoLocalDigitado);
        console.log('   - formData.CDPROJETO:', this.formData.CDPROJETO);
        console.log('   - projetoAssociadoSearch:', this.projetoAssociadoSearch);
        console.log('   - localSelecionadoId:', this.localSelecionadoId);
        console.log('   - formData.CDLOCAL:', this.formData.CDLOCAL);
        console.log('   - locaisEncontrados.length:', this.locaisEncontrados.length);
        console.log('🟣 [DEBUG SALVAR] ═══════════════════════════════════');

        this.erroNovoLocal = '';

        const nome = String(this.novoLocalNome || '').trim();
        if (nome === '') {
          this.erroNovoLocal = 'Digite o nome do local';
          console.log('🟣 [DEBUG SALVAR] ❌ Nome vazio, abortando');
          return;
        }

        // ✅ ATIVAR FLAG DE PROTEÇÃO ANTES DE TUDO!
        console.log('🟣 [DEBUG SALVAR] ✅ Ativando processandoCriacaoLocal = true');
        this.processandoCriacaoLocal = true;
        this.salvandoNovoLocal = true;

        try {
          // Encontrar o tabfant_id do primeiro local do array (todos do mesmo código têm o mesmo projeto)
          const tabfantId = this.locaisEncontrados.length > 0 ?
            this.locaisEncontrados[0].tabfant_id :
            null;

          if (!tabfantId) {
            this.erroNovoLocal = 'Não foi possível identificar o projeto associado';
            this.salvandoNovoLocal = false;
            return;
          }

          const payload = {
            cdlocal: this.codigoLocalDigitado,
            local: nome,
            cdprojeto: this.locaisEncontrados[0].CDPROJETO
          };

          console.log('📤 [CRIAR LOCAL] Payload:', payload);

          const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

          const response = await fetch('/api/locais/criar', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrfToken,
              'X-Requested-With': 'XMLHttpRequest',
              'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
          });

          if (!response.ok) {
            let serverMsg = '';
            try {
              const errData = await response.clone().json();
              serverMsg = errData.message || JSON.stringify(errData.errors || errData);
            } catch (_) {
              serverMsg = await response.text();
            }
            throw new Error(`Erro HTTP ${response.status}: ${serverMsg}`);
          }

          const data = await response.json();
          console.log('✅ [CRIAR LOCAL] Resposta completa do servidor:', JSON.stringify(data, null, 2));

          if (data.success) {
            console.log('🟣 [PASSO 1] ═══════════════════════════════════════════');
            console.log('🟣 [PASSO 1] Salvando estado do projeto ANTES de tudo');

            const projetoSalvo = this.formData.CDPROJETO;
            const projetoNomeSalvo = this.projetoAssociadoSearch;
            const codigoLocalSalvo = this.codigoLocalDigitado;

            console.log('� [PASSO 1] projetoSalvo:', projetoSalvo);
            console.log('🟣 [PASSO 1] projetoNomeSalvo:', projetoNomeSalvo);
            console.log('� [PASSO 1] codigoLocalSalvo:', codigoLocalSalvo);
            console.log('🟣 [PASSO 1] ✅ Estado salvo com sucesso');

            console.log('\n🟣 [PASSO 2] ═══════════════════════════════════════════');
            console.log('🟣 [PASSO 2] Fechando modal');
            this.fecharModalCriarNovoLocal();
            console.log('🟣 [PASSO 2] ✅ Modal fechado');

            await this.$nextTick();

            console.log('\n🟣 [PASSO 3] ═══════════════════════════════════════════');
            console.log('🟣 [PASSO 3] Re-buscando locais do backend');
            console.log('� [PASSO 3] Garantindo código está setado:', codigoLocalSalvo);
            this.codigoLocalDigitado = codigoLocalSalvo;

            console.log('🟣 [PASSO 3] Chamando buscarLocais()...');
            await this.buscarLocais();
            console.log('🟣 [PASSO 3] ✅ Busca concluída');
            console.log('🟣 [PASSO 3] Total de locais encontrados:', this.locaisEncontrados.length);
            console.log('🟣 [PASSO 3] Locais:', this.locaisEncontrados.map(l => ({
              id: l.id,
              delocal: l.delocal,
              cdlocal: l.cdlocal
            })));

            await this.$nextTick();

            console.log('\n🟣 [PASSO 4] ═══════════════════════════════════════════');
            console.log('🟣 [PASSO 4] Verificando estado do projeto APÓS buscarLocais()');
            console.log('🟣 [PASSO 4] formData.CDPROJETO atual:', this.formData.CDPROJETO);
            console.log('🟣 [PASSO 4] projetoAssociadoSearch atual:', this.projetoAssociadoSearch);
            console.log('� [PASSO 4] Restaurando projeto...');
            this.formData.CDPROJETO = projetoSalvo;
            this.projetoAssociadoSearch = projetoNomeSalvo;
            console.log('🟣 [PASSO 4] ✅ Projeto restaurado');
            console.log('🟣 [PASSO 4] formData.CDPROJETO após restaurar:', this.formData.CDPROJETO);
            console.log('🟣 [PASSO 4] projetoAssociadoSearch após restaurar:', this.projetoAssociadoSearch);

            await this.$nextTick();

            console.log('\n🟣 [PASSO 5] ═══════════════════════════════════════════');
            console.log('🟣 [PASSO 5] Procurando local recém-criado');
            console.log('� [PASSO 5] Buscando por nome:', nome, '| código:', codigoLocalSalvo);

            const localCriado = this.locaisEncontrados.find(l =>
              l.delocal.toLowerCase() === nome.toLowerCase() &&
              String(l.cdlocal) === String(codigoLocalSalvo)
            );

            if (localCriado) {
              console.log('🟣 [PASSO 5] ✅ Local encontrado:', localCriado);

              console.log('\n🟣 [PASSO 6] ═══════════════════════════════════════════');
              console.log('🟣 [PASSO 6] Selecionando o local criado');
              console.log('🟣 [PASSO 6] localSelecionadoId:', localCriado.id);
              console.log('🟣 [PASSO 6] formData.CDLOCAL:', localCriado.id);

              this.localSelecionadoId = localCriado.id;
              this.formData.CDLOCAL = localCriado.id;

              await this.$nextTick();

              console.log('\n🟣 [PASSO 7] ═══════════════════════════════════════════');
              console.log('🟣 [PASSO 7] Forçando select visualmente');
              const selectElement = document.querySelector('select[name="CDLOCAL"]');
              if (selectElement) {
                console.log('🟣 [PASSO 7] Select encontrado, value ANTES:', selectElement.value);
                selectElement.value = localCriado.id;
                console.log('🟣 [PASSO 7] Select value DEPOIS:', selectElement.value);
                selectElement.dispatchEvent(new Event('change', {
                  bubbles: true
                }));
                console.log('🟣 [PASSO 7] ✅ Evento change disparado');
              } else {
                console.log('🟣 [PASSO 7] ❌ Select NÃO encontrado!');
              }

              console.log('\n🎉 ═══════════════════════════════════════════════════');
              console.log('🎉   SUCESSO TOTAL!');
              console.log('🎉   Local criado:', localCriado.delocal);
              console.log('🎉   Projeto:', projetoSalvo, '-', projetoNomeSalvo);
              console.log('🎉 ═══════════════════════════════════════════════════\n');

              // ✅ AUTO-FOCUS NO CAMPO "NOME DO LOCAL" APÓS CRIAR
              console.log('🟣 [FINAL] Auto-focando no campo "Nome do Local"');
              this.$nextTick(() => {
                const nomeLocalInput = document.getElementById('NOMELOCAL_INPUT');
                if (nomeLocalInput) {
                  nomeLocalInput.focus();
                  console.log('✅ [FOCUS] Campo "Nome do Local" focado com sucesso');
                } else {
                  console.warn('⚠️ [FOCUS] Campo "Nome do Local" não encontrado');
                }
              });

              // ✅ DESATIVAR FLAG DE PROTEÇÃO APENAS NO SUCESSO
              console.log('🟣 [FINAL] Desativando processandoCriacaoLocal = false');
              this.processandoCriacaoLocal = false;
            } else {
              console.error('❌ Local não encontrado após busca!');
              console.log('Locais disponíveis:', this.locaisEncontrados.map(l => ({
                id: l.id,
                delocal: l.delocal
              })));
              this.processandoCriacaoLocal = false;
            }

          } else {
            this.erroNovoLocal = data.message || 'Erro ao criar local';
            console.error('❌ [CRIAR LOCAL] Falha:', this.erroNovoLocal);
            this.processandoCriacaoLocal = false;
          }

        } catch (error) {
          console.error('❌ [CRIAR LOCAL] Erro:', error);
          this.erroNovoLocal = error.message || 'Erro ao criar local';
          this.processandoCriacaoLocal = false;
        } finally {
          this.salvandoNovoLocal = false;
        }
      },

      async init() {
        console.log('🚀 [INIT] Inicializando formulário...');

        // ========================================
        // 🆕 RESTAURAR ESTADO DO SESSION STORAGE
        // ========================================
        const estadoSalvo = sessionStorage.getItem('patrimonioFormState');
        if (estadoSalvo) {
          try {
            const estado = JSON.parse(estadoSalvo);
            const tempoDecorrido = Date.now() - (estado.timestamp || 0);

            // Só restaurar se passou menos de 5 minutos (segurança)
            if (tempoDecorrido < 5 * 60 * 1000) {
              console.log('🔄 [INIT] Restaurando estado salvo:', estado);

              // Restaurar formData
              this.formData.NUPATRIMONIO = estado.NUPATRIMONIO || '';
              this.formData.NUMOF = estado.NUMOF || '';
              this.formData.NUSEQOBJ = estado.NUSEQOBJ || '';
              this.formData.DEOBJETO = estado.DEOBJETO || '';
              this.formData.DEHISTORICO = estado.DEHISTORICO || '';
              this.formData.MARCA = estado.MARCA || '';
              this.formData.MODELO = estado.MODELO || '';
              this.formData.NMPLANTA = estado.NMPLANTA || '';
              this.formData.CDMATRFUNCIONARIO = estado.CDMATRFUNCIONARIO || '';
              this.formData.DTAQUISICAO = estado.DTAQUISICAO || '';
              this.formData.DTBAIXA = estado.DTBAIXA || '';

              // Restaurar campos de busca
              this.patSearch = estado.patSearch || '';
              this.codigoSearch = estado.codigoSearch || '';
              this.userSearch = estado.userSearch || '';
              this.userSelectedName = estado.userSelectedName || '';

              // Restaurar situação
              await this.$nextTick();
              const selectSituacao = document.getElementById('SITUACAO');
              if (selectSituacao && estado.SITUACAO) {
                selectSituacao.value = estado.SITUACAO;
              }

              // Restaurar código do local e buscar novamente
              if (estado.codigoLocalDigitado) {
                this.codigoLocalDigitado = estado.codigoLocalDigitado;
                await this.buscarLocalPorCodigo();
              }

              console.log('✅ [INIT] Estado restaurado com sucesso!');
            } else {
              console.log('⚠️ [INIT] Estado expirado (> 5 minutos), ignorando');
            }

            // Limpar sessionStorage após restaurar
            sessionStorage.removeItem('patrimonioFormState');

          } catch (error) {
            console.error('❌ [INIT] Erro ao restaurar estado:', error);
            sessionStorage.removeItem('patrimonioFormState');
          }
        }

        // ========================================
        // LÓGICA ORIGINAL DE INICIALIZAÇÃO
        // ========================================

        // Se já existe projeto nos dados atuais (inclusive old()), carrega nome do projeto e locais
        if (this.formData.CDPROJETO) {
          const targetCdLocal = this.formData.CDLOCAL;
          await this.buscarProjetoELocais();
          if (targetCdLocal) this.formData.CDLOCAL = targetCdLocal;

          // Carregar nome do projeto associado para exibição
          try {
            const r = await fetch(`/api/projetos/pesquisar?q=${this.formData.CDPROJETO}`);
            if (r.ok) {
              const projetos = await r.json();
              const projeto = projetos.find(p => String(p.CDPROJETO) === String(this.formData.CDPROJETO));
              if (projeto) {
                this.projetoAssociadoSearch = `${projeto.CDPROJETO} - ${projeto.NOMEPROJETO}`;
              }
            }
          } catch (e) {
            /* silencioso */
          }
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
        // Pré-carregar descrição do código quando já houver código (edição)
        if (this.formData.NUSEQOBJ) {
          this.codigoSearch = String(this.formData.NUSEQOBJ);
          await this.buscarCodigo();
        }
        // Pré-carregar o código do local e projeto associado quando já houver CDLOCAL
        if (this.formData.CDLOCAL) {
          this.localSearch = String(this.formData.CDLOCAL);
          // Buscar nome do local e projeto associado
          try {
            const r = await fetch(`/api/locais/buscar?termo=${this.formData.CDLOCAL}`);
            if (r.ok) {
              const locais = await r.json();
              const local = locais.find(l => String(l.cdlocal) === String(this.formData.CDLOCAL));
              if (local) {
                this.nomeLocal = local.LOCAL || local.delocal;
                this.localSelecionadoId = local.id; // Definir ID do local selecionado

                // Carregar projeto associado se existir
                if (local.CDPROJETO) {
                  this.formData.CDPROJETO = local.CDPROJETO;
                  if (local.NOMEPROJETO) {
                    this.projetoAssociadoSearch = `${local.CDPROJETO} - ${local.NOMEPROJETO}`;
                  }
                }
              }
            }
          } catch (e) {
            /* silencioso */
          }
        }
        // Carregar lista de projetos existentes para os selects dos modais
        await this.carregarProjetosExistentes();
        // Manter localSearch sincronizado quando CDLOCAL mudar e limpar projeto associado
        this.$watch('formData.CDLOCAL', async (val, oldVal) => {
          if (!val) {
            this.localSearch = '';
            this.nomeLocal = '';
            // Limpar projeto associado quando local for limpo
            this.formData.CDPROJETO = '';
            this.projetoAssociadoSearch = '';
            this.projetosAssociadosLista = [];
            return;
          }
          this.localSearch = String(val);

          // Se o local mudou (não é inicialização), limpar projeto associado
          if (oldVal && String(oldVal) !== String(val)) {
            this.formData.CDPROJETO = '';
            this.projetoAssociadoSearch = '';
            this.projetosAssociadosLista = [];
          }

          // Buscar nome do local e projeto associado
          try {
            const r = await fetch(`/api/locais/buscar?termo=${val}`);
            if (r.ok) {
              const locais = await r.json();
              const local = locais.find(l => String(l.cdlocal) === String(val));
              if (local) {
                this.nomeLocal = local.LOCAL || local.delocal;

                // Buscar automaticamente o projeto associado a este local
                // O endpoint retorna CDPROJETO e NOMEPROJETO (maiúsculas)
                if (local.CDPROJETO) {
                  this.formData.CDPROJETO = local.CDPROJETO;

                  // Preencher o campo de exibição do projeto
                  if (local.NOMEPROJETO) {
                    this.projetoAssociadoSearch = `${local.CDPROJETO} - ${local.NOMEPROJETO}`;
                  }
                } else {
                  // Se não houver projeto associado, limpar
                  this.formData.CDPROJETO = '';
                  this.projetoAssociadoSearch = '';
                }
              } else {
                this.nomeLocal = '';
              }
            }
          } catch (e) {
            this.nomeLocal = '';
          }
        });
        // Watch para sincronizar quando locaisEncontrados muda (apenas 1 local = auto-preencher)
        this.$watch('locaisEncontrados', (novoLista) => {
          if (novoLista && novoLista.length === 1) {
            const unico = novoLista[0];
            // Auto-preencher nome quando houver apenas 1 local
            this.nomeLocalBusca = unico.LOCAL || unico.delocal || '';
            this.localNome = this.nomeLocalBusca;
            this.localSelecionadoId = unico.id;
            this.formData.CDLOCAL = unico.id;

            // Desabilitar o input (já feito via :disabled no template)
            console.log('✅ [AUTO-PREENCHER] 1 local encontrado:', this.nomeLocalBusca);
          }
        }, {
          deep: false
        });
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
      },

      // == FUNÇÕES DOS MODAIS DE LOCAL ==
      async carregarProjetosExistentes() {
        try {
          const r = await fetch('/api/projetos/pesquisar?q=');
          if (r.ok) {
            this.projetosExistentes = await r.json();
          }
        } catch (e) {
          console.error('Erro ao carregar projetos:', e);
        }
      },


      abrirModalEditarLocal() {
        if (!this.formData.CDPROJETO) {
          alert('Selecione um projeto associado primeiro');
          return;
        }

        // Buscar dados do local atual
        const localAtual = this.locais.find(l => String(l.cdlocal) === String(this.formData.CDLOCAL));
        if (localAtual) {
          this.editarLocalCodigo = localAtual.cdlocal;
          this.editarLocalNome = localAtual.LOCAL || localAtual.delocal || '';
          this.editarLocalProjeto = this.formData.CDPROJETO;
        } else {
          this.editarLocalCodigo = this.formData.CDLOCAL;
          this.editarLocalNome = this.nomeLocal || '';
          this.editarLocalProjeto = this.formData.CDPROJETO;
        }

        this.erroEdicao = '';
        this.modalEditarLocalOpen = true;
      },

      fecharModalEditarLocal() {
        this.modalEditarLocalOpen = false;
        this.editarLocalCodigo = '';
        this.editarLocalNome = '';
        this.editarLocalProjeto = '';
        this.erroEdicao = '';
      },

      async salvarEdicaoLocal() {
        if (!this.editarLocalNome.trim()) {
          this.erroEdicao = 'Digite o nome do local';
          return;
        }
        if (!this.editarLocalProjeto) {
          this.erroEdicao = 'Selecione um projeto associado';
          return;
        }

        this.salvandoEdicao = true;
        this.erroEdicao = '';

        try {
          const resp = await fetch('/api/locais/atualizar', {
            method: 'PUT',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
              cdlocal: this.editarLocalCodigo,
              local: this.editarLocalNome.trim(),
              cdprojeto: this.editarLocalProjeto
            })
          });

          const data = await resp.json();

          if (!resp.ok) {
            this.erroEdicao = data.message || 'Erro ao atualizar local';
            return;
          }

          // Atualizar nome local na tela
          this.nomeLocal = this.editarLocalNome;

          // Se mudou o projeto, atualizar e recarregar locais
          if (this.editarLocalProjeto !== this.formData.CDPROJETO) {
            this.formData.CDPROJETO = this.editarLocalProjeto;
            await this.buscarProjetoELocais();
          } else {
            // Apenas atualizar a lista de locais
            await this.buscarProjetoELocais();
          }

          this.fecharModalEditarLocal();
        } catch (e) {
          this.erroEdicao = 'Erro ao atualizar local. Tente novamente.';
          console.error(e);
        } finally {
          this.salvandoEdicao = false;
        }
      }
    }
  }
</script>