@props(['patrimonio' => null])

@if ($errors->any())
<div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative" role="alert">
  <strong class="font-bold">Opa! Algo deu errado.</strong>
  <ul class="mt-2 list-disc list-inside text-sm">
    @foreach ($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
  </ul>
</div>
@endif

<div x-data='patrimonioForm({ patrimonio: @json($patrimonio), old: @json(old()) })' @keydown.enter.prevent="focusNext($event.target)" class="space-y-4 md:space-y-5 text-sm">

  {{-- GRUPO 1: N° Patrimônio, N° OC, Campo Vazio --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-5">
    <div>
      <x-input-label for="NUPATRIMONIO" value="Nº Patrimônio *" />
      <div class="relative mt-0.5" @click.away="showPatDropdown=false">
        <input id="NUPATRIMONIO"
          x-model="patSearch"
          @input="(function(){ const t=String(patSearch||'').trim(); if(t.length>0){ showPatDropdown=true; buscarPatrimonios(); } else { showPatDropdown=false; patrimoniosLista=[]; highlightedPatIndex=-1; } })()"
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
            <div class="p-2 text-gray-500" x-text="String(patSearch || '').trim()==='' ? 'Digite para buscar' : 'Nenhum resultado'"></div>
          </template>
          <template x-for="(p, i) in (patrimoniosLista || [])" :key="p.NUSEQPATR || p.NUPATRIMONIO || i">
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
      <x-input-label for="NUSEQOBJ" value="Código *" />
      <div class="relative mt-0.5" @click.away="showCodigoDropdown=false">
        <input id="NUSEQOBJ"
          x-model="codigoSearch"
          @input="(function(){ const t=String(codigoSearch||'').trim(); if(t.length>0){ showCodigoDropdown=true; buscarCodigos(); } else { showCodigoDropdown=false; codigosLista=[]; highlightedCodigoIndex=-1; } })()"
          @blur="buscarCodigo"
          @keydown.down.prevent="navegarCodigos(1)"
          @keydown.up.prevent="navegarCodigos(-1)"
          @keydown.enter.prevent="selecionarCodigoEnter()"
          @keydown.escape.prevent="showCodigoDropdown=false"
          type="text"
          inputmode="numeric"
          class="block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm pr-10"
          placeholder="Digite nº ou descrição" required />
        <!-- Valor efetivo enviado no submit -->
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
            <div class="p-2 text-gray-500" x-text="String(codigoSearch || '').trim()==='' ? 'Digite para buscar' : 'Nenhum resultado'"></div>
          </template>
          <template x-for="(c,i) in (codigosLista || [])" :key="c.CODOBJETO || i">
            <div data-cod-item @click="selecionarCodigo(c)" @mouseover="highlightedCodigoIndex=i" :class="['px-3 py-2 cursor-pointer', highlightedCodigoIndex===i ? 'bg-indigo-100 dark:bg-gray-700' : 'hover:bg-indigo-50 dark:hover:bg-gray-700']">
              <span class="font-mono text-xs text-indigo-600 dark:text-indigo-400" x-text="c.CODOBJETO"></span>
              <span class="ml-2" x-text="' - ' + c.DESCRICAO"></span>
            </div>
          </template>
        </div>
        <p class="mt-1 text-xs" x-bind:class="isNovoCodigo ? 'text-amber-600' : (formData.NUSEQOBJ ? 'text-green-600' : '')" x-text="codigoBuscaStatus"></p>
      </div>
    </div>
    <div class="md:col-span-3">
      <x-input-label for="DEOBJETO" value="Descrição do Código" />
      <x-text-input data-index="5" x-model="formData.DEOBJETO" id="DEOBJETO" name="DEOBJETO" type="text" class="mt-0.5 block w-full" x-bind:readonly="!isNovoCodigo" x-bind:class="!isNovoCodigo ? 'bg-gray-100 dark:bg-gray-900' : ''" />
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
      <x-input-label for="CDLOCAL" value="Local" />
      <div class="flex items-center space-x-2 relative" @focusin="localFocused = true" @focusout="localFocused = false; handleLocalFocusOut($event)">
        <div class="relative w-1/3 mt-0.5">
          <input id="CDLOCAL_INPUT" name="CDLOCAL" x-model="localSearch"
            @input="(function(){ const t=String(localSearch||'').trim(); if(t.length>0){ showLocalDropdown=true; buscarLocaisDisponiveis(); } else { showLocalDropdown=false; locaisFiltrados=[]; highlightedLocalIndex=-1; } })()"
            @keydown.down.prevent="navegarLocais(1)"
            @keydown.up.prevent="navegarLocais(-1)"
            @keydown.enter.prevent="selecionarLocalEnter()"
            @keydown.escape.prevent="showLocalDropdown=false"
            type="text" inputmode="numeric"
            class="block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm pr-10"
            placeholder="Código do Local" />
          <div class="absolute inset-y-0 right-0 flex items-center pr-3">
            <div class="flex items-center gap-2">
              <button type="button" x-show="formData.CDLOCAL" @click="limparLocal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="Limpar seleção">✕</button>
              <button type="button" @click.stop="abrirDropdownLocais(true)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" title="Abrir lista" aria-label="Abrir lista">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                </svg>
              </button>
            </div>
          </div>
          <div x-show="showLocalDropdown" x-transition @click.stop class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-56 overflow-y-auto text-sm">
            <template x-if="locaisFiltrados.length===0">
              <div class="p-2 text-gray-500" x-text="String(localSearch || '').trim()==='' ? 'Digite para buscar' : 'Nenhum resultado'"></div>
            </template>
            <template x-for="(l,i) in (locaisFiltrados || [])" :key="l.id">
              <div data-local-item @mousedown.prevent @click="selecionarLocal(l)" @mouseover="highlightedLocalIndex=i" :class="['px-3 py-2 cursor-pointer', highlightedLocalIndex===i ? 'bg-indigo-100 dark:bg-gray-700' : 'hover:bg-indigo-50 dark:hover:bg-gray-700']">
                <span class="font-mono text-xs text-indigo-600 dark:text-indigo-400" x-text="l.cdlocal"></span>
                <span class="ml-2" x-text="' - ' + (l.LOCAL ?? l.delocal ?? '')"></span>
              </div>
            </template>
          </div>
        </div>
        <x-text-input x-model="nomeLocal" type="text" class="mt-0.5 block w-2/3 bg-gray-100 dark:bg-gray-900" placeholder="Nome do Local" readonly />
      </div>
    </div>
    <div>
      <x-input-label for="NMPLANTA" value="Cód Termo" />
      <x-text-input data-index="8" x-model="formData.NMPLANTA" id="NMPLANTA" name="NMPLANTA" type="number" class="mt-0.5 block w-full" />
    </div>
    <div class="md:col-span-3">
      <x-input-label for="CDPROJETO" value="Projeto" />
      <div class="flex gap-2 items-start">
        <div class="flex-1 relative mt-0.5" @click.away="fecharSeFora($event)">
          <!-- Campo visível apenas para exibição do nome do local; não enviar no submit -->
          <input id="CDPROJETO" x-model="projetoAssociadoSearch"
            @input="(function(){ const t=String(projetoAssociadoSearch||'').trim(); if(t.length>0){ showProjetoAssociadoDropdown=true; buscarProjetosParaAssociar(); } else { showProjetoAssociadoDropdown=false; projetosAssociadosLista=[]; highlightedProjetoAssociadoIndex=-1; } })()"
            @keydown.down.prevent="navegarProjetosAssociados(1)"
            @keydown.up.prevent="navegarProjetosAssociados(-1)"
            @keydown.enter.prevent="selecionarProjetoAssociadoEnter()"
            @keydown.escape.prevent="showProjetoAssociadoDropdown=false"
            :disabled="!formData.CDLOCAL"
            class="block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm pr-10 disabled:opacity-60"
            :placeholder="!formData.CDLOCAL ? 'Selecione um local primeiro' : 'Projeto Associado'" />
          <!-- Valor efetivo enviado no submit: ID do projeto associado ao local -->
          <input type="hidden" name="CDPROJETO" :value="formData.CDPROJETO" />
          <div class="absolute inset-y-0 right-0 flex items-center pr-3">
            <div class="flex items-center gap-2">
              <button type="button" x-show="formData.CDPROJETO" @click="limparProjetoAssociado" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="Limpar seleção">✕</button>
              <button type="button" @click.stop="showProjetoAssociadoDropdown=true; buscarProjetosParaAssociar()" :disabled="!formData.CDLOCAL" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 disabled:opacity-50" title="Abrir lista" aria-label="Abrir lista">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                </svg>
              </button>
            </div>
          </div>
          <div x-show="showProjetoAssociadoDropdown" x-transition class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-56 overflow-y-auto text-sm">
            <template x-if="loadingProjetosAssociados">
              <div class="p-2 text-gray-500">Buscando...</div>
            </template>
            <template x-if="!loadingProjetosAssociados && projetosAssociadosLista.length===0">
              <div class="p-2 text-gray-500" x-text="String(projetoAssociadoSearch || '').trim()==='' ? 'Digite para buscar' : 'Nenhum resultado'"></div>
            </template>
            <template x-for="(pr,i) in (projetosAssociadosLista || [])" :key="pr.CDPROJETO || i">
              <div data-proj-assoc-item @click="selecionarProjetoAssociado(pr)" @mouseover="highlightedProjetoAssociadoIndex=i" :class="['px-3 py-2 cursor-pointer', highlightedProjetoAssociadoIndex===i ? 'bg-indigo-100 dark:bg-gray-700' : 'hover:bg-indigo-50 dark:hover:bg-gray-700']">
                <span class="font-mono text-xs text-indigo-600 dark:text-indigo-400" x-text="pr.CDPROJETO"></span>
                <span class="ml-2" x-text="' - ' + pr.NOMEPROJETO"></span>
              </div>
            </template>
          </div>
        </div>
        <button type="button" @click="abrirModalCriar()" :disabled="!formData.CDLOCAL" class="mt-0.5 inline-flex items-center justify-center w-9 h-9 rounded-md bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed" title="Criar novo local ou projeto (selecione um local primeiro)" aria-label="Criar novo local ou projeto">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
          </svg>
        </button>
      </div>


      <!-- Modal de criação de local/projeto -->
      <div x-show="modalCriarOpen" x-transition x-cloak @keydown.escape.window="fecharModalCriar" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-96">
          <div class="flex justify-between items-center mb-4">
            <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Criar Local e Projeto</h4>
            <button type="button" class="text-gray-400 hover:text-gray-600" @click="fecharModalCriar">✕</button>
          </div>

          <!-- Campo Nome do Local -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nome do Local</label>
            <input type="text" x-model="novoLocalNome" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md" placeholder="Digite o nome do local" />
            <button type="button" @click="usarLocalAtual()" class="mt-1 text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300" x-show="nomeLocal">Usar atual</button>
            <p class="text-xs text-gray-500 mt-1" x-show="nomeLocal" x-text="'Atual: ' + nomeLocal"></p>
          </div>

          <!-- Campo Projeto Associado -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Projeto Associado</label>
            <input type="text" x-model="novoProjetoNome" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md" placeholder="Digite o nome do projeto" />
            <button type="button" @click="usarProjetoAtual()" class="mt-1 text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300" x-show="formData.CDPROJETO && projetoAssociadoSearch">Usar atual</button>
            <p class="text-xs text-gray-500 mt-1" x-show="formData.CDPROJETO && projetoAssociadoSearch" x-text="'Atual: ' + projetoAssociadoSearch"></p>
          </div>

          <p class="text-xs text-red-500 mb-4" x-text="erroCriacao" x-show="erroCriacao"></p>

          <p class="text-xs text-red-500 mb-4" x-text="erroCriacao" x-show="erroCriacao"></p>

          <div class="flex justify-end gap-2">
            <button type="button" @click="fecharModalCriar" class="px-4 py-2 text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700">Cancelar</button>
            <button type="button" @click="salvarNovoCriar" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50" :disabled="salvandoCriacao">
              <span x-show="!salvandoCriacao">Criar</span>
              <span x-show="salvandoCriacao">Criando...</span>
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
      <label for="SITUACAO" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Situação *</label>
      <select id="SITUACAO" name="SITUACAO" required
        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
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
      <x-input-label for="matricula_busca" value="Matrícula Responsável *" />
      <div class="relative mt-0.5">
        <input id="matricula_busca"
          x-model="userSearch"
          @input="(function(){ const t=String(userSearch||'').trim(); if(t.length>0){ showUserDropdown=true; buscarUsuarios(); } else { showUserDropdown=false; usuarios=[]; highlightedUserIndex=-1; } })()"
          @keydown.down.prevent="navegarUsuarios(1)"
          @keydown.up.prevent="navegarUsuarios(-1)"
          @keydown.enter.prevent="selecionarUsuarioEnter()"
          @keydown.escape.prevent="showUserDropdown=false"
          @blur="normalizarMatriculaBusca()"
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
        <template x-for="(u, i) in (usuarios || [])" :key="u.CDMATRFUNCIONARIO || i">
          <div data-user-item @click="selecionarUsuario(u)"
            @mouseover="highlightedUserIndex = i"
            :class="['px-3 py-2 cursor-pointer', highlightedUserIndex === i ? 'bg-indigo-100 dark:bg-gray-700' : 'hover:bg-indigo-50 dark:hover:bg-gray-700']">
            <span class="font-mono text-xs text-indigo-600 dark:text-indigo-400" x-text="u.CDMATRFUNCIONARIO"></span>
            <span class="ml-2" x-text="' - ' + (String(u.NOMEUSER || '').replace(/\d{2}\/\d{2}\/\d{4}/, '').replace(/\s+\d+\s*$/, '').replace(/[^A-Za-zÀ-ÿ\s]/g, '').trim())"></span>
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
      // Autocomplete Código
      codigoSearch: (config.old?.NUSEQOBJ ?? config.patrimonio?.CODOBJETO) || '',
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
      // Autocomplete Local
      localSearch: '',
      nomeLocal: '',
      locaisFiltrados: [],
      showLocalDropdown: false,
      localFocused: false,
      highlightedLocalIndex: -1,
      // Autocomplete Patrimônio
      patSearch: (config.old?.NUPATRIMONIO ?? config.patrimonio?.NUPATRIMONIO) || '',
      patrimoniosLista: [],
      loadingPatrimonios: false,
      showPatDropdown: false,
      highlightedPatIndex: -1,

      // Modal Criação
      modalCriarOpen: false,
      novoLocalNome: '',
      novoProjetoNome: '',
      erroCriacao: '',
      salvandoCriacao: false,

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
            // Ajustes específicos de mapeamento entre API e formData atual
            if (data.hasOwnProperty('CODOBJETO')) {
              this.formData.NUSEQOBJ = data.CODOBJETO;
              this.codigoSearch = String(data.CODOBJETO || '');
            }
            if (data.hasOwnProperty('DEPATRIMONIO')) {
              // Exibe descrição na caixa da descrição do código
              this.formData.DEOBJETO = data.DEPATRIMONIO || '';
              this.isNovoCodigo = false; // código existente carrega descrição e mantém readonly
              this.codigoBuscaStatus = this.formData.NUSEQOBJ ? 'Código encontrado.' : '';
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
      async buscarCodigo() {
        const valor = String(this.codigoSearch || '').trim();
        this.codigoBuscaStatus = '';
        if (valor === '') {
          this.formData.NUSEQOBJ = '';
          this.isNovoCodigo = false;
          this.formData.DEOBJETO = '';
          return;
        }
        // Ajusta o valor do hidden para enviar no submit
        this.formData.NUSEQOBJ = valor;
        try {
          const r = await fetch(`/api/codigos/buscar/${encodeURIComponent(valor)}`);
          if (r.ok) {
            const data = await r.json();
            if (data.found) {
              this.formData.DEOBJETO = data.descricao || '';
              this.isNovoCodigo = false; // bloqueia edição
              this.codigoBuscaStatus = 'Código encontrado.';
            } else {
              // Em teoria 404 cai no else abaixo, mas mantemos por segurança
              if (!this.formData.DEOBJETO) this.formData.DEOBJETO = '';
              this.isNovoCodigo = true; // libera edição
              this.codigoBuscaStatus = 'Novo código. Preencha a descrição.';
            }
          } else {
            // Não encontrado
            if (!this.formData.DEOBJETO) this.formData.DEOBJETO = '';
            this.isNovoCodigo = true; // libera edição
            this.codigoBuscaStatus = 'Novo código. Preencha a descrição.';
          }
        } catch (e) {
          console.error('Erro ao buscar código do objeto', e);
          this.codigoBuscaStatus = 'Erro na busca.';
        }
      },
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
        this.formData.NUSEQOBJ = c.CODOBJETO;
        this.codigoSearch = c.CODOBJETO;
        this.formData.DEOBJETO = c.DESCRICAO;
        this.isNovoCodigo = false;
        this.codigoBuscaStatus = 'Código encontrado.';
        this.showCodigoDropdown = false;
      },
      selecionarCodigoEnter() {
        if (!this.showCodigoDropdown) return;
        if (this.highlightedCodigoIndex < 0 || this.highlightedCodigoIndex >= this.codigosLista.length) return;
        this.selecionarCodigo(this.codigosLista[this.highlightedCodigoIndex]);
      },
      limparCodigo() {
        this.formData.NUSEQOBJ = '';
        this.codigoSearch = '';
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
      // === Buscar Locais Disponíveis ===
      async buscarLocaisDisponiveis() {
        const termo = String(this.localSearch || '').trim();
        try {
          console.log('Buscando locais com termo:', termo);

          // Busca todos os locais disponíveis por código ou nome
          const resp = await fetch(`/api/locais/buscar?termo=${encodeURIComponent(termo)}`, {
            credentials: 'same-origin',
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            }
          });

          console.log('Response status:', resp.status);

          if (resp.ok) {
            this.locaisFiltrados = await resp.json();
            this.highlightedLocalIndex = this.locaisFiltrados.length > 0 ? 0 : -1;
            console.log('Locais encontrados:', this.locaisFiltrados.length, this.locaisFiltrados);
          } else {
            console.error('Erro na resposta:', resp.status, resp.statusText);
            this.locaisFiltrados = [];
          }
        } catch (e) {
          console.error('Falha busca locais', e);
          this.locaisFiltrados = [];
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
      async selecionarLocal(local) {
        // Seleciona o local e limpa seleção de projeto associado
        this.formData.CDLOCAL = local.cdlocal;
        this.localSearch = local.cdlocal;
        this.nomeLocal = local.LOCAL || local.delocal;
        this.showLocalDropdown = false;

        this.locaisFiltrados = [];
        this.highlightedLocalIndex = -1;

        // Limpa seleção anterior de projeto para evitar inconsistência
        this.formData.CDPROJETO = '';
        this.projetoAssociadoSearch = '';

        // Buscar projetos vinculados ao local selecionado e abrir dropdown (não selecionar automaticamente)
        await this.buscarProjetosAssociadosPorLocal(local.cdlocal, '');
        this.showProjetoAssociadoDropdown = true;
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


      // Métodos do modal de criação
      abrirModalCriar() {
        if (!this.formData.CDLOCAL) {
          alert('Selecione um local primeiro');
          return;
        }
        this.modalCriarOpen = true;
        this.limparCamposCriacao();
      },

      fecharModalCriar() {
        this.modalCriarOpen = false;
        this.limparCamposCriacao();
      },

      limparCamposCriacao() {
        this.novoLocalNome = '';
        this.novoProjetoNome = '';
        this.erroCriacao = '';
        this.salvandoCriacao = false;
      },

      usarProjetoAtual() {
        if (this.projetoAssociadoSearch) {
          // Extrair apenas o nome do projeto (remover código)
          const nomeProjeto = this.projetoAssociadoSearch.split(' - ')[1] || this.projetoAssociadoSearch;
          this.novoProjetoNome = nomeProjeto;
        }
      },

      usarLocalAtual() {
        if (this.nomeLocal) {
          this.novoLocalNome = this.nomeLocal;
        }
      },



      async salvarNovoCriar() {
        this.erroCriacao = '';

        if (!this.novoLocalNome.trim() && !this.novoProjetoNome.trim()) {
          this.erroCriacao = 'Preencha pelo menos um campo';
          return;
        }

        this.salvandoCriacao = true;

        try {
          const payload = {
            nomeLocal: this.novoLocalNome.trim() || this.nomeLocal || null,
            nomeProjeto: this.novoProjetoNome.trim() || null,
            cdlocal: this.formData.CDLOCAL,
            nomeLocalAtual: this.nomeLocal || null,
            projetoAtual: this.formData.CDPROJETO || null
          };

          console.log('Payload enviado:', payload);

          // Obter CSRF token
          const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
          console.log('CSRF Token:', csrfToken);

          const response = await fetch('/api/locais-projetos/criar', {
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

          console.log('Response status:', response.status);
          console.log('Response headers:', response.headers);

          if (!response.ok) {
            // Tenta extrair mensagem de erro do servidor
            let serverMsg = '';
            try {
              const errData = await response.clone().json();
              if (errData && (errData.message || errData.errors)) {
                serverMsg = errData.message || Object.values(errData.errors).flat().join(' \n');
              }
            } catch (_) {
              // ignora se não for JSON
            }
            const httpMsg = `HTTP error! status: ${response.status}`;
            throw new Error(serverMsg ? `${httpMsg} - ${serverMsg}` : httpMsg);
          }

          const contentType = response.headers.get('content-type');
          if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Response não é JSON:', text);
            throw new Error('Resposta do servidor não é JSON válido');
          }

          const data = await response.json();
          console.log('Response data:', data);

          if (data.success) {
            // Atualizar campos do formulário com os dados criados
            if (data.local) {
              // Se foi criado um novo local, atualizar o código e nome
              this.formData.CDLOCAL = data.local.cdlocal;
              this.nomeLocal = data.local.delocal;
              this.localSearch = data.local.cdlocal; // Atualizar campo de busca
            }

            if (data.projeto) {
              // Se foi criado um novo projeto, selecionar
              this.formData.CDPROJETO = data.projeto.CDPROJETO;
              this.projetoAssociadoSearch = `${data.projeto.CDPROJETO} - ${data.projeto.NOMEPROJETO}`;

              // Atualizar lista de projetos associados para incluir o novo
              this.projetosAssociadosLista.push({
                CDPROJETO: data.projeto.CDPROJETO,
                NOMEPROJETO: data.projeto.NOMEPROJETO
              });
            }

            // Atualizar dropdowns se necessário
            if (data.local) {
              this.buscarLocaisDisponiveis();
            }
            if (data.projeto) {
              this.buscarProjetosParaAssociar();
            }

            this.fecharModalCriar();
          } else {
            this.erroCriacao = data.message || 'Erro ao criar';
          }
        } catch (error) {
          console.error('Erro ao criar:', error);
          if (error.message.includes('JSON')) {
            this.erroCriacao = 'Erro no servidor - resposta inválida';
          } else if (error.message.includes('HTTP error')) {
            this.erroCriacao = `${error.message}`;
          } else {
            this.erroCriacao = 'Erro de comunicação com o servidor';
          }
        } finally {
          this.salvandoCriacao = false;
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
        // Pré-carregar apenas o código do local quando já houver CDLOCAL
        if (this.formData.CDLOCAL) {
          this.localSearch = String(this.formData.CDLOCAL);
          // Buscar nome do local
          try {
            const r = await fetch(`/api/locais/buscar?termo=${this.formData.CDLOCAL}`);
            if (r.ok) {
              const locais = await r.json();
              const local = locais.find(l => String(l.cdlocal) === String(this.formData.CDLOCAL));
              if (local) {
                this.nomeLocal = local.LOCAL || local.delocal;
              }
            }
          } catch (e) {
            /* silencioso */
          }
        }
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

          // Buscar nome do local
          try {
            const r = await fetch(`/api/locais/buscar?termo=${val}`);
            if (r.ok) {
              const locais = await r.json();
              const local = locais.find(l => String(l.cdlocal) === String(val));
              if (local) {
                this.nomeLocal = local.LOCAL || local.delocal;
              } else {
                this.nomeLocal = '';
              }
            }
          } catch (e) {
            this.nomeLocal = '';
          }
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
      }
    }
  }
</script>