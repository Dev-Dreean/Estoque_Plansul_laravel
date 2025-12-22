@props(['patrimonio' => null, 'ultimaVerificacao' => null])

@php
  $rawConferido = old('FLCONFERIDO', $patrimonio?->FLCONFERIDO);
  $rawConferido = is_string($rawConferido) ? strtoupper(trim($rawConferido)) : ($rawConferido !== null ? (string) $rawConferido : '');
  $isConferido = in_array($rawConferido, ['S','1','SIM','TRUE','T','Y','YES','ON'], true);
@endphp

@if ($errors->any())
<div class="mb-3 bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded-lg relative text-sm" role="alert">
  <strong class="font-bold">Opa! Algo deu errado.</strong>
  <ul class="mt-1 list-disc list-inside text-xs">
    @foreach ($errors->all() as $error)
      @if (str_contains($error, 'Já existe um patrimônio') || str_contains($error, 'duplicat'))
        <li class="font-semibold text-red-700">
          {{ $error }}
          <br>
          <span class="mt-2 block text-red-600">
            💡 <strong>Dica:</strong> Clique no botão <strong style="background: #16a34a; color: white; padding: 2px 6px; border-radius: 3px;">⟳</strong> (verde) para gerar um novo número de patrimônio automaticamente.
          </span>
        </li>
      @else
        <li>{{ $error }}</li>
      @endif
    @endforeach
  </ul>
</div>
@endif

<div x-data="patrimonioForm($el)"
  x-init="init(); if (patSearch) { $nextTick(() => buscarPatrimonio()); }"
  @keydown.enter.prevent="handleEnter($event)" class="space-y-4 text-sm"
  data-patrimonio='@json($patrimonio)'
  data-old='@json(old())'>

  {{-- GRUPO 1: 4 Inputs lado a lado - Botão Gerar, Número Patrimônio, OC, Descrição e Código do Objeto --}}
  @if($patrimonio)
    @php
      $usuarioAtual = auth()->user()?->NMLOGIN ?? auth()->user()?->NOMEUSER ?? 'SISTEMA';
      $usuarioAtual = trim((string) $usuarioAtual) !== '' ? (string) $usuarioAtual : 'SISTEMA';
      $ultimaUsuario = $ultimaVerificacao?->USUARIO ?? null;
    @endphp

    <div
      x-data="{
        conferido: @js($isConferido),
        confirmOpen: false,
        pendingAction: null,
        usuarioAtual: @js($usuarioAtual),
        ultimaUsuario: @js($ultimaUsuario),
      }"
      x-init="formData.FLCONFERIDO = conferido ? 'S' : 'N'"
      x-effect="formData.FLCONFERIDO = conferido ? 'S' : 'N'"
      class="rounded-xl border border-l-4 p-3 sm:p-4 shadow-sm"
      :style="conferido
        ? 'border-color: var(--ok); border-left-color: var(--ok); background: color-mix(in srgb, var(--ok) 18%, var(--surface));'
        : 'border-color: var(--danger); border-left-color: var(--danger); background: color-mix(in srgb, var(--danger) 16%, var(--surface));'"
    >
      <input type="hidden" name="FLCONFERIDO" :value="conferido ? 'S' : 'N'">

      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
        <div class="flex items-start gap-3 min-w-0">
          <div class="mt-0.5 flex-shrink-0" :style="conferido ? 'color: var(--ok);' : 'color: var(--danger);'">
            <svg x-show="conferido" class="w-7 h-7" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 00-1.06 1.06l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
            </svg>
            <svg x-show="!conferido" class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <circle cx="12" cy="12" r="9" />
              <path d="M12 7v6" stroke-linecap="round" />
              <path d="M12 16h.01" stroke-linecap="round" />
            </svg>
          </div>

          <div class="min-w-0">
            <div class="text-xs sm:text-sm font-extrabold tracking-wide" :style="conferido ? 'color: var(--ok);' : 'color: var(--danger);'">
              <span x-text="conferido ? 'PATRIMONIO VERIFICADO' : 'PATRIMONIO NAO VERIFICADO'"></span>
            </div>

            <div class="mt-1 text-xs text-[var(--text)]">
              <template x-if="conferido">
                <div class="font-semibold">
                  Verificado por <span class="font-mono" x-text="ultimaUsuario || '—'"></span>
                </div>
              </template>
              <template x-if="!conferido">
                <div class="font-semibold">
                  Este patrimonio ainda nao foi verificado.
                </div>
              </template>
            </div>

            <div class="mt-1 text-[11px] text-[var(--text-soft)]">
              Clique em <span class="font-semibold">Atualizar Patrimonio</span> para salvar.
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0">
          <button
            type="button"
            class="px-3 py-2 rounded-md text-xs font-bold border border-[var(--border)] bg-[var(--surface)] hover:opacity-90"
            @click="pendingAction = conferido ? 'unverify' : 'verify'; confirmOpen = true"
          >
            <span x-text="conferido ? 'Desmarcar verificacao' : 'Marcar como verificado'"></span>
          </button>
        </div>
      </div>

      <div x-show="confirmOpen" x-cloak x-transition class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4" @keydown.escape.window="confirmOpen = false" @click.self="confirmOpen = false">
        <div class="bg-surface text-app rounded-lg shadow-lg p-6 max-w-sm w-full border border-app" @click.stop>
          <h3 class="text-lg font-semibold text-app mb-2" x-text="pendingAction === 'verify' ? 'Confirmar verificacao' : 'Confirmar remocao da verificacao'"></h3>

          <p class="text-muted mb-6">
            <span x-show="pendingAction === 'verify'" class="block">Deseja marcar este patrimonio como <strong>verificado</strong>?</span>
            <span x-show="pendingAction === 'unverify'" class="block">Deseja marcar este patrimonio como <strong>nao verificado</strong>?</span>
            Esta alteracao sera aplicada ao clicar em <strong>Atualizar Patrimonio</strong>.
          </p>

          <div class="flex gap-3 justify-end">
            <button
              type="button"
              class="px-4 py-2 text-sm font-semibold rounded-md border border-app bg-surface text-app hover:bg-[var(--surface-2)] transition"
              @click="confirmOpen = false"
            >
              Cancelar
            </button>
            <button
              type="button"
              class="px-4 py-2 text-sm text-white rounded font-semibold transition"
              :class="pendingAction === 'verify' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-red-600 hover:bg-red-700'"
              @click="
                conferido = (pendingAction === 'verify');
                ultimaUsuario = conferido ? usuarioAtual : null;
                confirmOpen = false;
              "
            >
              <span x-text="pendingAction === 'verify' ? 'Confirmar verificacao' : 'Confirmar'"></span>
            </button>
          </div>
        </div>
      </div>
    </div>
  @endif

  <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    {{-- Número do Patrimônio (Dropdown com patrimônios do usuário) --}}
    <div>
      <label for="patSearch" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Nº Patrimônio (Selecione ou Gere) *</label>
      <div class="flex items-stretch gap-2">
        {{-- Botão para gerar novo número --}}
        <button
          type="button"
          id="btnGerarNumPatrimonio"
          @click.prevent="gerarProximoNumeroPatrimonio()"
          @keydown.space.prevent="gerarProximoNumeroPatrimonio()"
          @keydown.tab.prevent="(function(){ document.getElementById('patSearch').focus(); })()"
          title="Gerar um novo número de patrimônio (opcional)"
          tabindex="1"
          class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-green-600 hover:bg-green-700 text-white rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 transition-colors active:bg-green-800">
          ⟳
        </button>

        {{-- Input de Busca/Seleção --}}
        <div class="flex-grow relative" @click.away="showPatDropdown=false">
          <input
            id="patSearch"
            x-model="patSearch"
            @focus="(function(){ showPatDropdown=true; if(patSearch.trim()){buscarPatrimonios();} })()"
            @blur.debounce.150ms="showPatDropdown=false"
            @input.debounce.300ms="(function(){ const t=String(patSearch||'').trim(); if(t.length>0){ showPatDropdown=true; buscarPatrimonios(); } else { showPatDropdown=false; patrimoniosLista=[]; highlightedPatIndex=-1; } })()"
            @keydown.down.prevent="(function(){ highlightedPatIndex = Math.min(highlightedPatIndex+1, patrimoniosLista.length-1); })()"
            @keydown.up.prevent="(function(){ highlightedPatIndex = Math.max(highlightedPatIndex-1, -1); })()"
            @keydown.enter.prevent="(function(){ if(highlightedPatIndex>=0 && patrimoniosLista[highlightedPatIndex]){ selectPatrimonio(patrimoniosLista[highlightedPatIndex]); buscarPatrimonio(); } })()"
            @keydown.escape.prevent="showPatDropdown=false"
            type="text"
            inputmode="numeric"
            tabindex="2"
            class="block w-full h-8 text-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm pr-6 focus:ring-2 focus:ring-indigo-500"
            placeholder="Digite nº ou selecione"
            required />

          {{-- Valor oculto com o número selecionado (enviado para o servidor) --}}
          <input type="hidden" name="NUPATRIMONIO" :value="patSearch" />

          {{-- Botão Limpar (DENTRO do input, à direita) --}}
          <button type="button" x-show="patSearch" @click.prevent="(function(){ patSearch=''; patrimoniosLista=[]; highlightedPatIndex=-1; showPatDropdown=false; })()" title="Limpar seleção" tabindex="-1" class="absolute inset-y-0 right-0 flex items-center pr-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-lg leading-none">×</button>

          {{-- Dropdown de Patrimônios do Usuário --}}
          <div x-show="showPatDropdown" x-transition class="absolute z-50 top-full mt-1 w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-56 overflow-y-auto text-xs">
            <template x-if="loadingPatrimonios">
              <div class="p-2 text-gray-500 text-center">Buscando seus patrimônios...</div>
            </template>
            <template x-if="!loadingPatrimonios && patrimoniosLista.length === 0">
              <div class="p-2 text-gray-500 text-center" x-text="String(patSearch || '').trim()==='' ? 'Digite para buscar ou clique no campo' : 'Nenhum patrimônio encontrado'"></div>
            </template>
            <template x-for="(p,i) in (patrimoniosLista || [])" :key="p.NUSEQPATR || p.NUPATRIMONIO || i">
              <div @click="selectPatrimonio(p); buscarPatrimonio();" @mouseover="highlightedPatIndex=i" :class="['px-3 py-1.5 cursor-pointer border-b border-gray-200 dark:border-gray-700 last:border-0', highlightedPatIndex===i ? 'bg-indigo-500 dark:bg-indigo-600 text-white' : 'hover:bg-indigo-50 dark:hover:bg-gray-700']">
                <div class="flex justify-between items-center gap-2">
                  <span class="font-semibold text-indigo-600 dark:text-indigo-400" :class="highlightedPatIndex===i ? 'text-white' : ''" x-text="p.NUPATRIMONIO"></span>
                  <span class="text-gray-700 dark:text-gray-300 flex-grow text-xs" :class="highlightedPatIndex===i ? 'text-white' : ''" x-text="' - ' + (p.DEPATRIMONIO || p.descricao || '—')"></span>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div> {{-- Número da Ordem de Compra --}}
    <div>
      <label for="NUMOF" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Número da Ordem de Compra</label>
      <input x-model="formData.NUMOF" id="NUMOF" name="NUMOF" type="number" tabindex="3" @focus="focarNumOf" class="block w-full h-8 text-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500" />
    </div>

    {{-- Descrição do Objeto (busca com dropdown) --}}
    <div>
      <label for="DEOBJETO" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Descrição do Objeto *</label>
      <div class="flex items-stretch gap-2">
        <button type="button"
          @click="abrirModalCriarBem()"
          @keydown.space.prevent="abrirModalCriarBem()"
          class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-green-600 hover:bg-green-700 text-white rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 transition-colors"
          title="Criar novo bem (Espaço)"
          tabindex="-1">
          <span class="text-lg font-bold leading-none">+</span>
        </button>
        <div class="relative flex-grow min-w-0" @click.away="showCodigoDropdown=false">
          <input id="DEOBJETO"
            x-model="descricaoSearch"
            @focus="abrirDropdownCodigos()"
            @blur.debounce.150ms="(function(){ showCodigoDropdown=false; buscarCodigo(); })()"
            @input.debounce.300ms="(function(){ const t=String(descricaoSearch||'').trim(); if(t.length>0){ showCodigoDropdown=true; buscarCodigos(); } else { showCodigoDropdown=false; codigosLista=[]; highlightedCodigoIndex=-1; } })()"
            @keydown.down.prevent="navegarCodigos(1)"
            @keydown.up.prevent="navegarCodigos(-1)"
            @keydown.enter.prevent="selecionarCodigoEnter()"
            @keydown.tab.prevent="selecionarCodigoTab($event)"
            @keydown.escape.prevent="showCodigoDropdown=false"
            type="text"
            tabindex="4"
            class="block w-full h-8 text-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm pr-14 focus:ring-2 focus:ring-indigo-500"
            placeholder="Informe a descrição" required />
          {{-- Valor enviado (hidden) --}}
          <input type="hidden" name="NUSEQOBJ" :value="formData.NUSEQOBJ" />
          <div class="absolute inset-y-0 right-0 flex items-center pr-6 gap-2">
            <button type="button" x-show="formData.NUSEQOBJ" @click="limparCodigo" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none text-lg leading-none" title="Limpar seleção" tabindex="-1">×</button>
            <button type="button" @click="abrirDropdownCodigos(true)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 focus:outline-none" title="Abrir lista" tabindex="-1">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
              </svg>
            </button>
          </div>
          <div x-show="showCodigoDropdown" x-transition class="absolute z-50 top-full mt-1 w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-48 overflow-y-auto text-xs">
            <template x-if="loadingCodigos">
              <div class="p-2 text-gray-500 text-center">Buscando...</div>
            </template>
            <template x-if="!loadingCodigos && codigosLista.length === 0">
              <div class="p-2 text-gray-500 text-center" x-text="String(descricaoSearch || '').trim()==='' ? 'Digite para buscar' : 'Nenhum resultado'"></div>
            </template>
            <template x-for="(c,i) in (codigosLista || [])" :key="c.CODOBJETO || i">
              <div data-cod-item @click="selecionarCodigo(c)" @mouseover="highlightedCodigoIndex=i" :class="['px-3 py-1.5 cursor-pointer text-xs', highlightedCodigoIndex===i ? 'bg-indigo-100 dark:bg-gray-700' : 'hover:bg-indigo-50 dark:hover:bg-gray-700']">
                <span class="text-gray-700 dark:text-gray-300" x-text="c.DESCRICAO"></span>
                <span class="font-mono text-xs text-indigo-600 dark:text-indigo-400 ml-1" x-text="'(' + c.CODOBJETO + ')'"></span>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>

{{-- Código do Objeto (preenchido automaticamente) --}}
    <div>
      <label for="NUSEQOBJ" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Código do Objeto *</label>
      <input id="NUSEQOBJ"
        x-model="formData.NUSEQOBJ"
        type="text"
        inputmode="numeric"
        tabindex="5"
        x-bind:readonly="!isNovoCodigo"
        :class="[
          'block w-full h-8 text-xs border-gray-300 dark:border-gray-700 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500',
          !isNovoCodigo
            ? 'bg-gray-100 dark:bg-gray-700 dark:text-gray-400 text-gray-600 cursor-not-allowed'
            : 'dark:bg-gray-900 dark:text-gray-200'
        ]"
        placeholder="" />
    </div>
  </div>

  {{-- GRUPO 3: Observação com Auto-Crescimento --}}
  <div>
    <label for="DEHISTORICO" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Observações</label>
    <textarea
      id="DEHISTORICO"
      x-model="formData.DEHISTORICO"
      @input="ajustarAltura($event)"
      name="DEHISTORICO"
      tabindex="5"
      class="block w-full resize-none border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500 text-xs overflow-hidden"
      style="min-height: 32px; height: 32px; padding: 6px 8px; line-height: 1.5;"
      placeholder="Digite suas observações..."></textarea>
  </div>

  {{-- GRUPO 4: Local, Cód. Termo e Projeto (REORDENADO) --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

    {{-- PROJETO (AGORA EM PRIMEIRO LUGAR - SELECIONÁVEL COM DROPDOWN) --}}
    <div class="md:col-span-3">
      <label for="projetoSelect" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Projeto *</label>
      <div class="relative" @click.away="showProjetoDropdown=false">
        <input id="projetoSelect"
          x-model="projetoSearch"
          @focus="abrirDropdownProjetos()"
          @blur.debounce.150ms="showProjetoDropdown=false"
          @input.debounce.300ms="(function(){ const t=String(projetoSearch||'').trim(); if(t.length>0){ showProjetoDropdown=true; buscarProjetosDisponiveis(); } else { showProjetoDropdown=false; projetosDisponiveisList=[]; highlightedProjetoIndex=-1; } })()"
          @keydown.down.prevent="navegarProjetos(1)"
          @keydown.up.prevent="navegarProjetos(-1)"
          @keydown.enter.prevent="selecionarProjetoEnter()"
          @keydown.tab.prevent="selecionarProjetoTab($event)"
          @keydown.escape.prevent="showProjetoDropdown=false"
          type="text"
          tabindex="6"
          class="block w-full h-8 text-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm pr-14 focus:ring-2 focus:ring-indigo-500"
          placeholder="Informe o código ou nome do projeto" required />
        <input type="hidden" name="CDPROJETO" :value="formData.CDPROJETO" />
        <div class="absolute inset-y-0 right-0 flex items-center pr-6 gap-2">
          <button type="button" x-show="formData.CDPROJETO" @click="limparProjeto" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none text-lg leading-none" title="Limpar seleção" tabindex="-1">×</button>
          <button type="button" @click="abrirDropdownProjetos(true)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 focus:outline-none" title="Abrir lista" tabindex="-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
            </svg>
          </button>
        </div>
        <div x-show="showProjetoDropdown" x-transition class="absolute z-50 top-full mt-1 w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-64 overflow-y-auto text-sm">
          <template x-if="loadingProjetos">
            <div class="p-2 text-gray-500">Buscando...</div>
          </template>
          <template x-if="!loadingProjetos && projetosDisponiveisList.length === 0">
            <div class="p-2 text-gray-500" x-text="String(projetoSearch || '').trim()==='' ? 'Digite para buscar' : 'Nenhum resultado'"></div>
          </template>
          <template x-for="(p,i) in (projetosDisponiveisList || [])" :key="'proj-' + i">
            <div data-proj-item @click="selecionarProjeto(p)" @mouseover="highlightedProjetoIndex=i" :class="['px-3 py-2 cursor-pointer', highlightedProjetoIndex===i ? 'bg-indigo-100 dark:bg-gray-700' : 'hover:bg-indigo-50 dark:hover:bg-gray-700']">
              <span class="font-mono text-xs text-indigo-600 dark:text-indigo-400" x-text="p.CDPROJETO"></span>
              <span class="ml-2 text-gray-700 dark:text-gray-300" x-text="' - ' + p.NOMEPROJETO"></span>
            </div>
          </template>
        </div>
      </div>
    </div>

    {{-- LOCAL: Botão + | Código | Dropdown Nome --}}
    <div class="md:col-span-2">
      <label for="CDLOCAL_INPUT" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Local Físico *</label>

      <div class="flex gap-3 items-stretch">
        {{-- Botão + (Criar Novo Local/Projeto) --}}
        <button type="button"
          @click="abrirModalCriarProjeto()"
          @keydown.space.prevent="abrirModalCriarProjeto()"
          class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-green-600 hover:bg-green-700 text-white rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 transition-colors"
          title="Criar novo Local Físico/projeto (Espaço)"
          tabindex="6">
          <span class="text-lg font-bold leading-none">+</span>
        </button>

        {{-- Input Local (Agora com Dropdown Searchable) --}}
        <div class="flex-grow max-w-xs relative" @click.away="showCodigoLocalDropdown=false">
          <input id="CDLOCAL_INPUT"
            type="text"
            inputmode="numeric"
            x-model="codigoLocalDigitado"
            @focus="abrirDropdownCodigosLocais(true)"
            @blur.debounce.150ms="(function(){ validarCodigoLocalNoBlur(); showCodigoLocalDropdown=false; })()"
            @input.debounce.300ms="(function(){ handleCodigoLocalInput(); buscarCodigosLocaisFiltrados(); })()"
            @keydown.down.prevent="navegarCodigosLocais(1)"
            @keydown.up.prevent="navegarCodigosLocais(-1)"
            @keydown.enter.prevent="selecionarCodigoLocalEnter()"
            @keydown.escape.prevent="showCodigoLocalDropdown=false"
            :disabled="!formData.CDPROJETO"
            placeholder="Digite código ou nome do Local Físico"
            tabindex="7"
            :class="[
              'block w-full h-8 text-xs rounded-md shadow-sm pr-14 focus:ring-2 focus:ring-indigo-500 border',
              !formData.CDPROJETO
                ? 'border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 dark:text-gray-400 text-gray-600 cursor-not-allowed'
                : 'border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 text-gray-700'
            ]" />
          <input type="hidden" name="CDLOCAL" :value="formData.CDLOCAL" />

          {{-- Botão Lupa e Limpar --}}
          <div class="absolute inset-y-0 right-0 flex items-center pr-6 gap-2">
            <button type="button"
              x-show="codigoLocalDigitado && formData.CDPROJETO"
              @click="limparCodigoLocal()"
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none text-lg leading-none"
              title="Limpar seleção"
              tabindex="-1">×</button>
            <button type="button"
              @click="abrirDropdownCodigosLocais(true)"
              :disabled="!formData.CDPROJETO"
              :class="[
                'focus:outline-none',
                formData.CDPROJETO
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

          {{-- Dropdown de Códigos Locais --}}
          <div x-show="showCodigoLocalDropdown"
            x-transition
            class="absolute z-50 top-full mt-1 w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-56 overflow-y-auto text-xs">
            <template x-if="loadingCodigosLocais">
              <div class="p-3 text-gray-500 text-center">
                <svg class="animate-spin h-4 w-4 inline mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Carregando locais...
              </div>
            </template>
            <template x-if="!loadingCodigosLocais && codigosLocaisFiltrados.length === 0">
              <div class="p-3 text-gray-500 text-center" x-text="String(codigoLocalDigitado || '').trim() === '' ? 'Nenhum local disponível para este projeto' : 'Nenhum resultado encontrado'"></div>
            </template>
            <template x-for="(codigo, i) in codigosLocaisFiltrados" :key="codigo.id || codigo.cdlocal">
              <div @click="selecionarCodigoLocal(codigo)"
                @mouseover="highlightedCodigoLocalIndex = i"
                :class="['px-3 py-2 cursor-pointer border-b border-gray-200 dark:border-gray-700 last:border-0 transition-colors', highlightedCodigoLocalIndex === i ? 'bg-indigo-500 dark:bg-indigo-600 text-white' : 'hover:bg-indigo-50 dark:hover:bg-gray-700']">
                <div class="flex justify-between items-center gap-2">
                  <span class="font-mono font-semibold text-sm" :class="highlightedCodigoLocalIndex === i ? 'text-white' : 'text-indigo-600 dark:text-indigo-400'" x-text="codigo.cdlocal"></span>
                  <span class="text-gray-700 dark:text-gray-300 flex-grow" :class="highlightedCodigoLocalIndex === i ? 'text-white' : ''" x-text="codigo.LOCAL || codigo.delocal || '—'"></span>
                </div>
              </div>
            </template>
          </div>
        </div>

        {{-- Nome do Local - Campo Somente Leitura (Auto-preenchido) --}}
        <div class="flex-grow relative">
          <input type="text"
            id="NOMELOCAL_INPUT"
            x-model="nomeLocalBusca"
            readonly
            :placeholder="!codigoLocalDigitado ? 'Será preenchido automaticamente' : 'Preenchido automaticamente ✓'"
            tabindex="-1"
            class="block w-full h-8 text-xs rounded-md shadow-sm border
              bg-gray-100 dark:bg-gray-700 dark:text-gray-300 text-gray-600 
              border-gray-300 dark:border-gray-600
              cursor-not-allowed" />
        </div>
      </div>
    </div>

    {{-- CAMPO CÓD TERMO --}}
    <div>
      <label for="NMPLANTA" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Código do Termo</label>
      <input x-model="formData.NMPLANTA"
        id="NMPLANTA"
        name="NMPLANTA"
        type="number"
        tabindex="9"
        class="block w-full h-8 text-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500" />
    </div>
  </div>

  {{-- GRUPO 5: Marca, Modelo, Situação --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
    <div>
      <label for="MARCA" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Marca do Patrimônio</label>
      <input x-model="formData.MARCA" id="MARCA" name="MARCA" type="text" tabindex="10" class="block w-full h-8 text-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500" />
    </div>
    <div>
      <label for="MODELO" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Modelo do Patrimônio</label>
      <input x-model="formData.MODELO" id="MODELO" name="MODELO" type="text" tabindex="11" class="block w-full h-8 text-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500" />
    </div>
    <div>
      <label for="SITUACAO" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Situação do Patrimônio *</label>
      <select id="SITUACAO" name="SITUACAO" x-model="formData.SITUACAO" @change="setTimeout(() => { const matricula = document.getElementById('matricula_busca'); if(matricula) { matricula.focus(); console.log('🎯 [SITUACAO change] Focus movido para matricula_busca'); } }, 50)" required tabindex="12"
        class="block w-full h-8 text-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500">
        <option value="EM USO">EM USO</option>
        <option value="CONSERTO">CONSERTO</option>
        <option value="BAIXA">BAIXA</option>
        <option value="À DISPOSIÇÃO">À DISPOSIÇÃO</option>
      </select>
    </div>
  </div>

  {{-- GRUPO 6: Matrícula do Responsável, Data de Aquisição e Data de Baixa --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="relative" @click.away="showUserDropdown=false">
      <label for="matricula_busca" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Matrícula do Responsável *</label>
      <div class="relative">
        <input id="matricula_busca"
          x-model="userSearch"
          @focus="abrirDropdownUsuarios()"
          @blur.debounce.150ms="showUserDropdown=false"
          @input.debounce.300ms="(function(){ const t=String(userSearch||'').trim(); if(t.length>0){ showUserDropdown=true; buscarUsuarios(); } else { showUserDropdown=false; usuarios=[]; highlightedUserIndex=-1; } })()"
          @keydown.down.prevent="navegarUsuarios(1)"
          @keydown.up.prevent="navegarUsuarios(-1)"
          @keydown.enter.prevent="selecionarUsuarioEnter()"
          @keydown.tab.prevent="selecionarUsuarioTab($event)"
          @keydown.escape.prevent="showUserDropdown=false"
          @blur="normalizarMatriculaBusca()"
          type="text"
          placeholder="Digite matrícula ou nome"
          tabindex="13"
          class="block w-full h-8 text-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm pr-14 focus:ring-2 focus:ring-indigo-500"
          autocomplete="off" />
        <input type="hidden" name="CDMATRFUNCIONARIO" :value="formData.CDMATRFUNCIONARIO" />
        <div class="absolute inset-y-0 right-0 flex items-center pr-6 gap-2">
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

    <div>
      <label for="DTAQUISICAO" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Data de Aquisição</label>
      <input x-model="formData.DTAQUISICAO" id="DTAQUISICAO" name="DTAQUISICAO" type="date" @keydown.tab.prevent="(function(){ const dtBaixa = document.getElementById('DTBAIXA'); if(dtBaixa) { dtBaixa.focus(); } })()" tabindex="14" class="block w-full h-8 text-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500" />
    </div>

    <div>
      <label for="DTBAIXA" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Data de Baixa do Patrimônio</label>
      <input x-model="formData.DTBAIXA"
        id="DTBAIXA"
        name="DTBAIXA"
        type="date"
        @keydown.tab.prevent="(function(){ const btnSalvar = document.querySelector('button[type=submit]'); if(btnSalvar) { btnSalvar.focus(); } })()"
        tabindex="15"
        class="block w-full h-8 text-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500" />
      <x-input-error class="mt-2" :messages="$errors->get('DTBAIXA')" />
    </div>
  </div>

  {{-- GRUPO 7: Peso e Tamanho (Novos Campos) --}}
  <div class="md:col-span-4">
    <p class="text-xs font-semibold text-indigo-600 dark:text-indigo-300 mb-1">Novos campos</p>
    <div class="border-2 border-indigo-500 dark:border-indigo-400 rounded-lg p-2">
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label for="PESO" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Peso (kg)</label>
          <input x-model="formData.PESO" id="PESO" name="PESO" type="number" step="0.01" tabindex="16" 
            class="block w-full h-8 text-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500" 
            placeholder="Ex: 15.50" />
        </div>
        <div>
          <label for="TAMANHO" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Dimensões</label>
          <input x-model="formData.TAMANHO" id="TAMANHO" name="TAMANHO" type="text" tabindex="17" 
            class="block w-full h-8 text-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500" 
            placeholder="Ex: 10x20x30 cm" />
        </div>
      </div>
    </div>
  </div>

  {{-- MODAL DE CRIAR NOVO BEM --}}
  <div x-show="modalCriarBemOpen"
    x-transition
    x-cloak
    @keydown.escape.window="fecharModalCriarBem"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-md"
      @keydown.enter.stop.prevent="salvarNovoBem">
      <div class="flex justify-between items-center mb-4">
        <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Cadastrar Bem</h4>
        <button type="button"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none"
          @click="fecharModalCriarBem"
          :disabled="salvandoBem"
          title="Fechar">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
          </svg>
        </button>
      </div>

      <div class="space-y-4">
        <div class="grid gap-3" style="grid-template-columns: 140px 1fr;">
          <div>
            <label for="modal_nuseqtipo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cód. Tipo</label>
            <input id="modal_nuseqtipo"
              x-ref="inputBemTipoCodigo"
              x-model="novoBem.NUSEQTIPOPATR"
              type="number"
              class="mt-1 block w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md h-10 px-3"
              placeholder="Ex.: 1" />
          </div>
          <div>
            <label for="modal_detipo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo</label>
            <input id="modal_detipo"
              x-model="novoBem.DETIPOPATR"
              type="text"
              class="mt-1 block w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md h-10 px-3"
              placeholder="Ex.: APARADOR DE GRAMA" />
            <p class="mt-1 text-xs text-gray-500">Informe o nome do tipo se o código não existir.</p>
          </div>
        </div>

        <div>
          <label for="modal_deobjeto" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição do Bem</label>
          <input id="modal_deobjeto"
            x-model="novoBem.DEOBJETO"
            type="text"
            class="mt-1 block w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md h-10 px-3"
            placeholder="Ex.: APARADOR DE GRAMA TRAMONTINA 127V" />
        </div>

        <template x-if="erroCriacaoBem">
          <p class="text-sm text-red-600" x-text="erroCriacaoBem"></p>
        </template>
      </div>

      <div class="mt-6 flex items-center justify-end gap-3">
        <button type="button"
          class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500"
          @click="fecharModalCriarBem"
          :disabled="salvandoBem">Cancelar</button>
        <button type="button"
          class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-70"
          @click="salvarNovoBem"
          :disabled="salvandoBem">Salvar</button>
      </div>
    </div>
  </div>

  {{-- ✨ MODAL DE CRIAR NOVO LOCAL --}}
  <div x-show="modalCriarProjetoOpen"
    x-transition
    x-cloak
    @keydown.escape.window="fecharModalCriarProjeto"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-2xl">
      <div class="flex justify-between items-center mb-4">
        <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-200">
          Criar Novo Local
        </h4>
        <button type="button"
          class="text-gray-400 hover:text-gray-600 text-2xl leading-none"
          @click="fecharModalCriarProjeto"
          :disabled="salvandoCriacaoProjeto">×</button>
      </div>

      {{-- Formulário de Criação --}}
      <div class="space-y-4">
        {{-- 🎯 PRIMEIRA INFORMAÇÃO: Projeto (Dropdown Searchable) --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Projeto *
          </label>
          <div class="relative" @click.away="showModalProjetoDropdown = false">
            <input type="text"
              x-model="modalProjetoSearch"
              x-ref="inputProjetoAssociado"
              @focus="abrirModalDropdownProjeto()"
              @blur.debounce.150ms="showModalProjetoDropdown = false"
              @input.debounce.300ms="buscarModalProjetos()"
              @keydown.down.prevent="navegarModalProjetos(1)"
              @keydown.up.prevent="navegarModalProjetos(-1)"
              @keydown.enter.prevent="selecionarModalProjetoEnter()"
              @keydown.escape.prevent="showModalProjetoDropdown = false"
              placeholder="Digite o código ou nome do projeto"
              class="w-full h-10 border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md pr-10" />

            {{-- Botão limpar e lupa --}}
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 gap-2">
              <button type="button"
                x-show="novoProjeto.cdprojeto"
                @click="limparModalProjeto()"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-lg leading-none">×</button>
              <button type="button"
                @click="abrirModalDropdownProjeto(true)"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                </svg>
              </button>
            </div>

            {{-- Dropdown de projetos --}}
            <div x-show="showModalProjetoDropdown"
              x-transition
              class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-56 overflow-y-auto text-sm">
              <template x-if="loadingModalProjetos">
                <div class="p-2 text-gray-500 text-center">Buscando...</div>
              </template>
              <template x-if="!loadingModalProjetos && modalProjetosLista.length === 0">
                <div class="p-2 text-gray-500 text-center" x-text="modalProjetoSearch.trim() === '' ? 'Nenhum projeto disponível' : 'Nenhum resultado'"></div>
              </template>
              <template x-for="(p, i) in modalProjetosLista" :key="'modal-proj-' + i">
                <div @click="selecionarModalProjeto(p)"
                  @mouseover="highlightedModalProjetoIndex = i"
                  :class="['px-3 py-2 cursor-pointer border-b border-gray-200 dark:border-gray-700 last:border-b-0', highlightedModalProjetoIndex === i ? 'bg-indigo-100 dark:bg-indigo-900' : 'hover:bg-indigo-50 dark:hover:bg-gray-700']">
                  <span class="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400" x-text="p.CDPROJETO"></span>
                  <span class="ml-2 text-gray-700 dark:text-gray-300" x-text="' - ' + p.NOMEPROJETO"></span>
                </div>
              </template>
            </div>
          </div>
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Clique para ver os primeiros 50 projetos em ordem numérica ou digite para buscar
          </p>
        </div>

        {{-- Spinner de carregamento --}}
        <div x-show="carregandoCodigosLocaisModal" class="flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400">
          <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span>Carregando códigos de locais...</span>
        </div>

        {{-- ✅ Campos que aparecem APÓS selecionar o projeto --}}
        <div x-show="novoProjeto.cdprojeto && !carregandoCodigosLocaisModal" class="space-y-4">

          {{-- 🔍 Código do projeto que será gerado (Minimalista com informação) --}}
          <div x-show="novoProjeto.cdlocal">
            <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Código do projeto que será gerado:</p>
            <p class="text-lg font-semibold text-gray-800 dark:text-gray-100 font-mono" x-text="novoProjeto.cdlocal"></p>
          </div>

          {{-- 📝 Nome do Local (Campo editável para criar novo) --}}
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Nome do Local *
            </label>
            <input type="text"
              x-model="novoProjeto.nomeLocal"
              x-ref="inputNomeLocal"
              @keydown.enter.prevent="salvarNovoLocal"
              placeholder="Ex: Almoxarifado Central - Setor B"
              class="w-full h-10 border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md focus:ring-2 focus:ring-blue-500" />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
              Digite o nome (pressione Enter para criar)
            </p>
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
          @click="salvarNovoLocal"
          :disabled="salvandoCriacaoProjeto || !novoProjeto.cdprojeto || !novoProjeto.cdlocal || !novoProjeto.nomeLocal"
          class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50 flex items-center gap-2">
          <span x-show="!salvandoCriacaoProjeto">✓ Criar Local</span>
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
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Local</label>
        <input type="text" x-model="editarLocalCodigo" class="w-full border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 dark:text-gray-200 rounded-md" readonly />
      </div>

      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nome do Local</label>
        <input type="text" x-model="editarLocalNome" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md" placeholder="Digite o nome do local" />
      </div>

      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Projeto</label>
        <select x-model="editarLocalProjeto" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md">
          <option value="">Selecione um projeto</option>
          <template x-for="(proj, idx) in projetosExistentes" :key="'edit-proj-' + idx">
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
  function patrimonioForm(elOrConfig, isReadOnly = false) {
    let config = elOrConfig || {};
    // If an element was passed (via $el), read initial data from its data-* attributes
    if (elOrConfig && elOrConfig.dataset) {
      const ds = elOrConfig.dataset;
      try {
        config = config || {};
        config.patrimonio = ds.patrimonio ? JSON.parse(ds.patrimonio) : null;
      } catch (e) {
        config.patrimonio = null;
      }
      try {
        config.old = ds.old ? JSON.parse(ds.old) : {};
      } catch (e) {
        config.old = {};
      }
    }
    return {
      // == DADOS DO FORMULÁRIO ==
      formData: {
        NUPATRIMONIO: (config.old?.NUPATRIMONIO ?? config.patrimonio?.NUPATRIMONIO) || '',
        NUMOF: (config.old?.NUMOF ?? config.patrimonio?.NUMOF) || '',
        // Novo fluxo de Código/Descrição do Código
        NUSEQOBJ: (config.old?.NUSEQOBJ ?? config.patrimonio?.CODOBJETO) || '',
        DEOBJETO: (config.old?.DEOBJETO ?? (config.patrimonio?.DEOBJETO || config.patrimonio?.DEPATRIMONIO)) || '',
        // Mantemos DEPATRIMONIO somente para compatibilidade de carregamento de patrimônio existente (não é mais o campo de edição de descrição do código)
        DEPATRIMONIO: (config.old?.DEPATRIMONIO ?? config.patrimonio?.DEPATRIMONIO) || '',
        DEHISTORICO: (config.old?.DEHISTORICO ?? config.patrimonio?.DEHISTORICO) || '',
        // Usar projeto_correto se disponível (pega do local->projeto), senão usar CDPROJETO direto
        CDPROJETO: (config.old?.CDPROJETO ?? (config.patrimonio?.projeto_correto ?? config.patrimonio?.CDPROJETO)) || '',
        CDLOCAL: (config.old?.CDLOCAL ?? config.patrimonio?.CDLOCAL) || '',
        NMPLANTA: (config.old?.NMPLANTA ?? config.patrimonio?.NMPLANTA) || '',
        MARCA: (config.old?.MARCA ?? config.patrimonio?.MARCA) || '',
        MODELO: (config.old?.MODELO ?? config.patrimonio?.MODELO) || '',
        SITUACAO: (config.old?.SITUACAO ?? config.patrimonio?.SITUACAO) || 'EM USO',
        DTAQUISICAO: (config.old?.DTAQUISICAO ?? (config.patrimonio?.DTAQUISICAO ? (() => {
          const d = config.patrimonio.DTAQUISICAO;
          return d.includes('T') ? d.split('T')[0] : (d.includes(' ') ? d.split(' ')[0] : d);
        })() : '')),
        DTBAIXA: (config.old?.DTBAIXA ?? (config.patrimonio?.DTBAIXA ? (() => {
          const d = config.patrimonio.DTBAIXA;
          return d.includes('T') ? d.split('T')[0] : (d.includes(' ') ? d.split(' ')[0] : d);
        })() : '')),
        CDMATRFUNCIONARIO: (config.old?.CDMATRFUNCIONARIO ?? config.patrimonio?.CDMATRFUNCIONARIO) || '',
        PESO: (config.old?.PESO ?? config.patrimonio?.PESO) || '',
        TAMANHO: (config.old?.TAMANHO ?? config.patrimonio?.TAMANHO) || '',
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
      descricaoSearch: (config.old?.DEOBJETO ?? (config.patrimonio?.DEOBJETO || config.patrimonio?.DEPATRIMONIO)) || '',
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

      // Autocomplete Projetos (Novo - Seleção de Projeto Primário)
      projetoSearch: '',
      projetosDisponiveisList: [],
      loadingProjetos: false,
      showProjetoDropdown: false,
      highlightedProjetoIndex: -1,

      // === SISTEMA SIMPLIFICADO DE LOCAIS ===
      codigoLocalDigitado: '', // Código digitado pelo usuário
      codigoLocalSelecionado: '', // Código (cdlocal) efetivamente selecionado; evita submit com CDLOCAL antigo
      localNome: '', // ✅ Nome do local (preenchido automaticamente)
      nomeLocalBusca: '', // ✅ Valor para o campo readonly x-model
      locaisEncontrados: [], // Array de locais retornados pela API
      localSelecionadoId: null, // ID do local selecionado no dropdown
      mostrarDropdownBusca: false, // Controla visibilidade do dropdown de busca
      resultadosBusca: [], // Resultados brutos da busca (lupa ou digitação)
      resultadosBuscaGrouped: [], // Resultados agrupados por cdlocal

      // Dropdown de Códigos Locais (Novo)
      codigosLocaisFiltrados: [],
      loadingCodigosLocais: false,
      showCodigoLocalDropdown: false,
      highlightedCodigoLocalIndex: -1,

      // Variáveis antigas (manter compatibilidade)
      localSearch: '',
      nomeLocal: '',
      locaisFiltrados: [],
      showLocalDropdown: false,
      localFocused: false,
      highlightedLocalIndex: -1,
      get locaisComMesmoCodigo() {
        if (!this.formData.CDLOCAL) return [];
        return this.locais.filter(l => String(l.id) === String(this.formData.CDLOCAL));
      },
      // Autocomplete Patrimônio
      patSearch: (config.old?.NUPATRIMONIO ?? config.patrimonio?.NUPATRIMONIO) || '',
      patrimoniosLista: [],
      loadingPatrimonios: false,
      showPatDropdown: false,
      highlightedPatIndex: -1,

      // Modal de Bem
      modalCriarBemOpen: false,
      novoBem: {
        NUSEQTIPOPATR: '',
        DETIPOPATR: '',
        DEOBJETO: '',
      },
      salvandoBem: false,
      erroCriacaoBem: '',

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

      // 🆕 MODAL CRIAR LOCAL (NOVO FLUXO)
      modalCriarProjetoOpen: false,
      novoProjeto: {
        cdlocal: '', // Local selecionado
        nomeLocal: '', // Nome do novo local (editável)
        cdprojeto: '', // ID do projeto associado selecionado
        nmProjeto: '', // Nome do projeto (para exibição)
      },
      // Dropdown de Projeto Associado (Modal)
      modalProjetoSearch: '',
      modalProjetosLista: [],
      loadingModalProjetos: false,
      showModalProjetoDropdown: false,
      highlightedModalProjetoIndex: -1,
      // Dropdown de Local (Modal)
      modalCodigoLocalSearch: '',
      modalCodigosLocaisDisponiveis: [], // Todos os códigos disponíveis do projeto
      modalCodigosLocaisFiltrados: [], // Códigos filtrados pela busca
      modalCodigosLocaisMap: {}, // Mapa: { cdlocal: ['nome1', 'nome2', ...] }
      showModalCodigoLocalDropdown: false,
      highlightedModalCodigoLocalIndex: -1,
      carregandoCodigosLocaisModal: false,
      // Controle
      erroCriacaoProjeto: '',
      salvandoCriacaoProjeto: false,
      estadoTemporario: null, // Salva o estado do formulário antes de abrir o modal
      desativarWatchCDLOCAL: false, // Flag para desativar watch durante preenchimento do modal

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
      ajustarAltura(event) {
        const textarea = event.target;
        // Reseta para calcular o tamanho real do conteúdo
        textarea.style.height = 'auto';
        // Calcula a altura necessária baseado no scrollHeight
        const scrollHeight = textarea.scrollHeight;
        // Apenas muda a altura se for maior que o mínimo (32px)
        if (scrollHeight > 32) {
          textarea.style.height = scrollHeight + 'px';
        } else {
          textarea.style.height = '32px';
        }
      },
      selecionarDropdownInteligente(nomeDropdown, nomeSearch, nomeLista, callbackSelecao) {
        /**
         * Função genérica para selecionar automaticamente item do dropdown ao pressionar Tab
         * @param {string} nomeDropdown - Propriedade que controla visibilidade (ex: 'showCodigoDropdown')
         * @param {string} nomeSearch - Propriedade do termo digitado (ex: 'descricaoSearch')
         * @param {string} nomeLista - Propriedade da lista de resultados (ex: 'codigosLista')
         * @param {function} callbackSelecao - Função callback para selecionar o item
         */
        const termo = this[nomeSearch]?.toString().trim();
        if (!termo) return false;

        // Se há resultados na lista, seleciona o primeiro (mais relevante)
        if (this[nomeLista] && this[nomeLista].length > 0) {
          callbackSelecao(this[nomeLista][0]);
          this[nomeDropdown] = false;
          return true;
        }

        return false;
      },
      formatarData(valor) {
        /**
         * Converte data ISO (2011-12-11T02:00:00.000000Z) para formato yyyy-MM-dd
         */
        if (!valor) return '';

        // Se já está no formato correto (yyyy-MM-dd), retorna
        if (/^\d{4}-\d{2}-\d{2}$/.test(valor)) {
          return valor;
        }

        // Tenta extrair a data de formatos ISO (2011-12-11T02:00:00.000000Z)
        if (valor.includes('T')) {
          return valor.split('T')[0];
        }

        // Tenta com espaço (2011-12-11 02:00:00)
        if (valor.includes(' ')) {
          return valor.split(' ')[0];
        }

        return valor;
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
          const response = await fetch(`/api/patrimonios/pesquisar?q=${this.searchTerm}`, { credentials: 'same-origin' });
          if (response.ok) this.searchResults = await response.json();
        } catch (error) {
          console.error('Erro na pesquisa:', error);
        } finally {
          this.loadingSearch = false;
        }
      },
      selectPatrimonio(item) {
        this.formData.NUPATRIMONIO = item.NUPATRIMONIO;
        this.patSearch = String(item.NUPATRIMONIO); // Sincronizar com campo visível
        this.showPatDropdown = false; // Fechar dropdown
        this.buscarPatrimonio();
        this.closeSearchModal();

        // Auto-focus no próximo input (NUMOF - Número da Ordem de Compra)
        this.$nextTick(() => {
          setTimeout(() => {
            const inputNumof = document.getElementById('NUMOF');
            if (inputNumof) {
              inputNumof.focus();
              console.log('🎯 [selectPatrimonio] Focus movido para NUMOF');
            }
          }, 50);
        });
      },
      async buscarPatrimonio() {
        if (!this.formData.NUPATRIMONIO) return;
        this.loading = true;
        try {
          const response = await fetch(`/api/patrimonios/buscar/${this.formData.NUPATRIMONIO}`, { credentials: 'same-origin' });
          if (response.ok) {
            const data = await response.json();

            // Preencher todos os campos do formData
            Object.keys(this.formData).forEach(key => {
              if (data.hasOwnProperty(key) && data[key] !== null) {
                if (key.startsWith('DT')) {
                  this.formData[key] = this.formatarData(data[key]);
                } else {
                  this.formData[key] = data[key];
                }
              }
            });

            // 🆕 PREENCHER CÓDIGO DO OBJETO E DESCRIÇÃO
            if (data.hasOwnProperty('CODOBJETO')) {
              this.formData.NUSEQOBJ = data.CODOBJETO;
              this.descricaoSearch = String(data.DEPATRIMONIO || '');
              this.isNovoCodigo = false; // código existente, bloqueia edição
            }
            if (data.hasOwnProperty('DEPATRIMONIO')) {
              this.formData.DEOBJETO = data.DEPATRIMONIO || '';
              this.codigoBuscaStatus = this.formData.NUSEQOBJ ? 'Código encontrado e preenchido automaticamente.' : '';
            }

            // 🆕 PREENCHER PROJETO (se existir)
            // Preferir projeto_correto (que vem do local->projeto), senão usar CDPROJETO direto
            const cdProjetoCorreto = data.projeto_correto || data.CDPROJETO;
            if (cdProjetoCorreto) {
              this.formData.CDPROJETO = cdProjetoCorreto;
              // Buscar nome do projeto para exibir no campo
              try {
                const projetoResp = await fetch(`/api/projetos/pesquisar?q=${cdProjetoCorreto}`, { credentials: 'same-origin' });
                if (projetoResp.ok) {
                  const projetos = await projetoResp.json();
                  if (projetos && projetos.length > 0) {
                    const projeto = projetos.find(p => String(p.CDPROJETO) === String(cdProjetoCorreto)) || projetos[0];
                    this.projetoSearch = `${projeto.CDPROJETO} - ${projeto.NOMEPROJETO}`;
                  }
                }
              } catch (e) {
                console.error('❌ Erro ao buscar projeto ' + cdProjetoCorreto, e);
              }
            }

            // 🆕 PREENCHER LOCAL (se existir)
            if (data.CDLOCAL) {
              this.formData.CDLOCAL = data.CDLOCAL;

              // Primeiro tentar usar o objeto 'local' que vem do eager loading
              if (data.local && data.local.id) {
                const local = data.local;
                this.codigoLocalDigitado = local.CDLOCAL || local.cdlocal || '';
                this.nomeLocalBusca = local.NOMELOCAL || local.LOCAL || local.delocal || '';
                this.localNome = this.nomeLocalBusca;
                this.localSelecionadoId = local.id;
                this.locaisEncontrados = [local];
                // Normalizar para ID do local (evita confusão com cdlocal legado)
                this.formData.CDLOCAL = String(local.id);
                // Opcional: manter dropdown alinhado ao projeto
                this.codigosLocaisFiltrados = [local];

                // Se o local tem projeto associado e ainda não preenchemos, preencher agora
                if (!this.formData.CDPROJETO && local.CDPROJETO) {
                  this.formData.CDPROJETO = local.CDPROJETO;
                  if (local.NOMEPROJETO) {
                    this.projetoSearch = `${local.CDPROJETO} - ${local.NOMEPROJETO}`;
                  }
                }
              } else {
                // Se não veio no eager loading, buscar via API
                try {
                  const localResp = await fetch(`/api/locais/buscar?termo=&cdprojeto=${encodeURIComponent(this.formData.CDPROJETO || '')}`, { credentials: 'same-origin' });
                  if (localResp.ok) {
                    const todosLocais = await localResp.json();
                    const local = todosLocais.find(l => String(l.id) === String(data.CDLOCAL));

                    if (local) {
                      this.codigoLocalDigitado = local.cdlocal;
                      this.nomeLocalBusca = local.LOCAL || local.delocal || '';
                      this.localNome = this.nomeLocalBusca;
                      this.localSelecionadoId = local.id;
                this.formData.CDLOCAL = String(local.id);
                this.codigosLocaisFiltrados = [local];
                      this.locaisEncontrados = [local];
                      this.codigosLocaisFiltrados = [local];
                      this.formData.CDLOCAL = String(local.id);

                      if (!this.formData.CDPROJETO && local.CDPROJETO) {
                        this.formData.CDPROJETO = local.CDPROJETO;
                        if (local.NOMEPROJETO) {
                          this.projetoSearch = `${local.CDPROJETO} - ${local.NOMEPROJETO}`;
                        }
                      }
                    } else {
                      console.error('❌ Local ' + data.CDLOCAL + ' não encontrado');
                    }
                  }
                } catch (e) {
                  console.error('❌ Erro ao buscar local:', e);
                }
              }
            }

            // 🆕 PREENCHER USUÁRIO RESPONSÁVEL (CDMATRFUNCIONARIO)
            if (data.hasOwnProperty('CDMATRFUNCIONARIO') && data.CDMATRFUNCIONARIO) {
              const matricula = String(data.CDMATRFUNCIONARIO || '').trim();
              this.formData.CDMATRFUNCIONARIO = matricula.replace(/[^0-9]/g, '');

              // Se a API retornou os dados do funcionário, buscar o nome
              if (data.funcionario && data.funcionario.NMFUNCIONARIO) {
                let nomeLimpo = String(data.funcionario.NMFUNCIONARIO || '').trim();
                // Remove datas no padrão dd/mm/yyyy
                nomeLimpo = nomeLimpo.replace(/\d{2}\/\d{2}\/\d{4}/g, '');
                // Remove múltiplos espaços e números ao final
                nomeLimpo = nomeLimpo.replace(/\s+\d+\s*$/g, '');
                // Remove caracteres especiais mantendo apenas letras, acentos e espaço
                nomeLimpo = nomeLimpo.replace(/[^A-Za-zÀ-ÿ\s]/g, '').trim();
                // Remove espaços extras
                nomeLimpo = nomeLimpo.replace(/\s+/g, ' ').trim();

                this.userSelectedName = `${matricula} - ${nomeLimpo}`;
                this.userSearch = this.userSelectedName;
              }
            }


            // Focar no campo de Descrição do Objeto para o fluxo continuar
            this.$nextTick(() => {
              try {
                document.getElementById('DEOBJETO')?.focus();
              } catch (e) {
                console.error('Erro ao focar campo:', e);
              }
            });
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
          // Usar endpoint de busca filtrando pelo projeto para evitar rota ambígua /api/locais/{id}
          const url = `/api/locais/buscar?cdprojeto=${encodeURIComponent(this.formData.CDPROJETO)}&termo=`;
          const locaisResponse = await fetch(url, {
            credentials: 'same-origin',
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            }
          });

          if (locaisResponse.ok) {
            this.locais = await locaisResponse.json();
            // Sempre alinhar o dropdown com a lista do projeto
            this.codigosLocaisFiltrados = this.locais;

            // Se já houver um CDLOCAL selecionado, sincroniza o nome exibido
            if (this.formData.CDLOCAL) {
              const found = this.locais.find(x => String(x.id) === String(this.formData.CDLOCAL));
              if (found) {
                this.localSearch = found.cdlocal;
                this.nomeLocal = found.LOCAL || found.delocal;
                if (!this.localSelecionadoId) {
                  this.localSelecionadoId = found.id;
                }
              } else {
                // Tentar casar pelo código (caso CDLOCAL esteja guardado como cdlocal legado)
                const byCode = this.locais.find(x => String(x.cdlocal) === String(this.formData.CDLOCAL) || String(x.cdlocal) === String(this.codigoLocalDigitado));
                if (byCode) {
                  this.formData.CDLOCAL = String(byCode.id);
                  this.localSelecionadoId = byCode.id;
                  this.codigoLocalDigitado = byCode.cdlocal;
                  this.nomeLocalBusca = byCode.LOCAL || byCode.delocal || '';
                  this.localNome = this.nomeLocalBusca;
                  this.codigosLocaisFiltrados = [byCode];
                } else {
                  // Se não pertence ao projeto, limpar para forçar escolha correta
                  this.formData.CDLOCAL = '';
                  this.localSelecionadoId = null;
                  this.codigoLocalDigitado = '';
                  this.nomeLocalBusca = '';
                  this.localNome = '';
                }
              }
            }
          } else {
            console.error('❌ Erro ao carregar locais do projeto');
          }
        } catch (error) {
          console.error('❌ Erro em buscarProjetoELocais:', error);
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
          const resp = await fetch(`/api/funcionarios/pesquisar?q=${encodeURIComponent(termo)}`, { credentials: 'same-origin' });
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
        const matricula = String(u.CDMATRFUNCIONARIO || '').trim();
        this.formData.CDMATRFUNCIONARIO = matricula.replace(/[^0-9]/g, '');

        // Limpar o nome: remover datas (dd/mm/yyyy), números ao final, caracteres especiais
        let nomeLimpo = String(u.NOMEUSER || '').trim();
        // Remove datas no padrão dd/mm/yyyy (uma ou várias vezes)
        nomeLimpo = nomeLimpo.replace(/\d{2}\/\d{2}\/\d{4}/g, '');
        // Remove múltiplos espaços e números ao final
        nomeLimpo = nomeLimpo.replace(/\s+\d+\s*$/g, '');
        // Remove múltiplos espaços consecutivos
        nomeLimpo = nomeLimpo.replace(/\s+/g, ' ');
        // Remove caracteres especiais mantendo apenas letras, acentos e espaço
        nomeLimpo = nomeLimpo.replace(/[^A-Za-zÀ-ÿ\s]/g, '').trim();
        // Remove espaços extras novamente após remover caracteres especiais
        nomeLimpo = nomeLimpo.replace(/\s+/g, ' ').trim();

        this.userSelectedName = `${matricula} - ${nomeLimpo}`;
        this.userSearch = this.userSelectedName;
        this.showUserDropdown = false;
        // Auto-focus para a próxima field (pular datas de calendário - Data de Aquisição)
        // Ir direto para Data de Baixa se a de aquisição já estiver preenchida, ou saltar para botão salvar
        this.$nextTick(() => {
          setTimeout(() => {
            // Tentar focar Data de Baixa primeiro
            const dtBaixa = document.getElementById('DTBAIXA');
            if (dtBaixa) {
              dtBaixa.focus();
              console.log('🎯 [selecionarUsuario] Focus movido para DTBAIXA (Data de Baixa)');
            }
          }, 50);
        });
      },
      // Sanitiza o campo visível removendo datas/números após o nome e garante que o hidden receba só a matrícula
      normalizarMatriculaBusca() {
        let s = String(this.userSearch || '');
        // Remover datas no padrão dd/mm/yyyy
        s = s.replace(/\d{2}\/\d{2}\/\d{4}/g, '');
        // Remove números soltos no final (ex: "   0")
        s = s.replace(/\s+\d+\s*$/, '');
        // Remove múltiplos espaços
        s = s.replace(/\s+/g, ' ').trim();
        // Mantém apenas "mat - nome" quando houver mais lixo depois
        const m = s.match(/^(\d{1,12})\s*-\s*([^\d]+?)(?:\s+.*)?$/);
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
      selecionarUsuarioTab(event) {
        const termo = this.userSearch.trim();
        if (termo === '') return;

        if (this.usuarios && this.usuarios.length > 0) {
          this.selecionarUsuario(this.usuarios[0]);
          this.$nextTick(() => {
            try {
              event.target?.form?.querySelector('[tabindex="14"]')?.focus();
            } catch (e) {
              console.warn('Erro ao focar próximo campo:', e);
            }
          });
          return;
        }

        if (this.loadingUsers) {
          setTimeout(() => {
            this.selecionarUsuarioTab(event);
          }, 150);
          return;
        }

        // Força a busca agora
        this.loadingUsers = true;
        fetch(`/api/funcionarios/pesquisar?q=${encodeURIComponent(termo)}`)
          .then(resp => {
            if (resp.ok) return resp.json();
            throw new Error('Erro na busca');
          })
          .then(data => {
            this.usuarios = data || [];
            if (this.usuarios.length > 0) {
              this.selecionarUsuario(this.usuarios[0]);
            }
            try {
              event.target?.form?.querySelector('[tabindex="14"]')?.focus();
            } catch (e) {
              console.warn('Erro ao focar próximo campo:', e);
            }
          })
          .catch(e => {
            console.error('Falha ao buscar usuários:', e);
            try {
              event.target?.form?.querySelector('[tabindex="14"]')?.focus();
            } catch (err) {
              console.warn('Erro ao focar próximo campo:', err);
            }
          })
          .finally(() => {
            this.loadingUsers = false;
          });
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

      // === Autocomplete Projetos (Novo) ===
      async buscarProjetosDisponiveis() {
        const termo = String(this.projetoSearch || '').trim();
        if (termo.length === 0) {
          this.projetosDisponiveisList = [];
          this.highlightedProjetoIndex = -1;
          return;
        }
        this.loadingProjetos = true;
        try {
          // Busca todos os projetos que contenham o termo (no código ou nome)
          const resp = await fetch(`/api/projetos/pesquisar?q=${encodeURIComponent(termo)}`, {
            credentials: 'same-origin',
          });
          if (resp.ok) {
            this.projetosDisponiveisList = await resp.json();
            this.highlightedProjetoIndex = this.projetosDisponiveisList.length > 0 ? 0 : -1;
          } else {
            this.projetosDisponiveisList = [];
            this.highlightedProjetoIndex = -1;
          }
        } catch (e) {
          console.error('Erro ao buscar projetos:', e);
          this.projetosDisponiveisList = [];
          this.highlightedProjetoIndex = -1;
        } finally {
          this.loadingProjetos = false;
        }
      },
      abrirDropdownProjetos(force = false) {
        this.showProjetoDropdown = true;
        if (this.projetoSearch.trim() !== '') {
          this.buscarProjetosDisponiveis();
        }
      },
      selecionarProjeto(projeto) {
        // Atualiza o campo hidden com o código do projeto
        this.formData.CDPROJETO = String(projeto.CDPROJETO).replace(/[^0-9]/g, '');
        // Mostra "código - nome" no campo visível
        this.projetoSearch = `${projeto.CDPROJETO} - ${projeto.NOMEPROJETO}`;
        this.showProjetoDropdown = false;
        // Agora que o projeto foi selecionado, precisa recarregar os locais/códigos disponíveis
        // Limpar a seleção de local anterior para forçar nova busca
        this.formData.CDLOCAL = '';
        this.codigoLocalDigitado = '';
        this.localNome = '';
        this.nomeLocalBusca = '';
        this.locaisEncontrados = [];
        this.codigosLocaisFiltrados = [];
        // IMPORTANTE: Carregar os locais disponíveis para o novo projeto e focar no campo Local
        this.$nextTick(() => {
          try {
            // Abre o dropdown de códigos de locais e carrega todos disponíveis para este projeto
            this.abrirDropdownCodigosLocais(true);
            // Foca no campo CDLOCAL_INPUT para o usuário começar a digitar/selecionar
            document.getElementById('CDLOCAL_INPUT')?.focus();
          } catch (e) {
            console.warn('Erro ao preparar campo Local:', e);
          }
        });
      },
      selecionarProjetoEnter() {
        if (!this.showProjetoDropdown) return;
        if (this.highlightedProjetoIndex < 0 || this.highlightedProjetoIndex >= this.projetosDisponiveisList.length) return;
        this.selecionarProjeto(this.projetosDisponiveisList[this.highlightedProjetoIndex]);
      },
      selecionarProjetoTab(event) {
        const termo = this.projetoSearch.trim();
        if (termo === '') return;

        if (this.projetosDisponiveisList && this.projetosDisponiveisList.length > 0) {
          this.selecionarProjeto(this.projetosDisponiveisList[0]);
          this.$nextTick(() => {
            try {
              event.target?.form?.querySelector('[tabindex="7"]')?.focus();
            } catch (e) {
              console.warn('Erro ao focar próximo campo:', e);
            }
          });
          return;
        }

        if (this.loadingProjetos) {
          setTimeout(() => {
            this.selecionarProjetoTab(event);
          }, 150);
          return;
        }

        // Força a busca agora
        this.loadingProjetos = true;
        fetch(`/api/projetos/pesquisar?q=${encodeURIComponent(termo)}`)
          .then(resp => {
            if (resp.ok) return resp.json();
            throw new Error('Erro na busca');
          })
          .then(data => {
            this.projetosDisponiveisList = data || [];
            if (this.projetosDisponiveisList.length > 0) {
              this.selecionarProjeto(this.projetosDisponiveisList[0]);
            }
            try {
              event.target?.form?.querySelector('[tabindex="7"]')?.focus();
            } catch (e) {
              console.warn('Erro ao focar próximo campo:', e);
            }
          })
          .catch(e => {
            console.error('Falha ao buscar projetos:', e);
            try {
              event.target?.form?.querySelector('[tabindex="7"]')?.focus();
            } catch (err) {
              console.warn('Erro ao focar próximo campo:', err);
            }
          })
          .finally(() => {
            this.loadingProjetos = false;
          });
      },
      limparProjeto() {
        this.formData.CDPROJETO = '';
        this.projetoSearch = '';
        this.projetosDisponiveisList = [];
        this.showProjetoDropdown = false;
        this.highlightedProjetoIndex = -1;
        // Limpar dependências
        this.formData.CDLOCAL = '';
        this.codigoLocalDigitado = '';
        this.localNome = '';
        this.locaisEncontrados = [];
        this.codigosLocaisFiltrados = [];
      },
      navegarProjetos(delta) {
        if (!this.showProjetoDropdown || this.projetosDisponiveisList.length === 0) return;
        const max = this.projetosDisponiveisList.length - 1;
        if (this.highlightedProjetoIndex === -1) {
          this.highlightedProjetoIndex = 0;
        } else {
          this.highlightedProjetoIndex = Math.min(max, Math.max(0, this.highlightedProjetoIndex + delta));
        }
        // Scroll into view
        this.$nextTick(() => {
          const list = this.$root.querySelector('[x-show="showProjetoDropdown"]');
          if (!list) return;
          const items = list.querySelectorAll('[data-proj-item]');
          const el = items[this.highlightedProjetoIndex];
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

      // === Dropdown de Códigos Locais (Novo) ===
      async buscarCodigosLocaisFiltrados() {
        const termo = String(this.codigoLocalDigitado || '').trim();

        // Regra: projeto define locais. Sem projeto, não lista nada.
        if (!this.formData.CDPROJETO) {
          this.codigosLocaisFiltrados = [];
          this.locaisEncontrados = [];
          this.loadingCodigosLocais = false;
          return;
        }

        // Sempre buscar quando field tem foco (mesmo se vazio)
        this.loadingCodigosLocais = true;
        try {
          let url = `/api/locais/buscar?termo=${encodeURIComponent(termo)}`;
          if (this.formData.CDPROJETO) {
            url += `&cdprojeto=${encodeURIComponent(this.formData.CDPROJETO)}`;
          }

          const resp = await fetch(url);
          if (resp.ok) {
            const locais = await resp.json();
            // Armazenar para próxima consulta e atualizar lista filtrada
            this.locaisEncontrados = locais;

            // Se tem termo, filtrar; se vazio, mostrar todos
            if (termo === '') {
              // Ordenar alfabeticamente crescente por código
              this.codigosLocaisFiltrados = locais.sort((a, b) => {
                const codigoA = String(a.cdlocal).toLowerCase().trim();
                const codigoB = String(b.cdlocal).toLowerCase().trim();
                return codigoA.localeCompare(codigoB, undefined, {
                  numeric: true
                });
              });
            } else {
              // Manter apenas aqueles que começam com o termo ou contêm
              let filtrados = locais.filter(l =>
                String(l.cdlocal).toLowerCase().includes(termo.toLowerCase()) ||
                String(l.LOCAL || l.delocal || '').toLowerCase().includes(termo.toLowerCase())
              );
              // Ordenar alfabeticamente crescente por código
              this.codigosLocaisFiltrados = filtrados.sort((a, b) => {
                const codigoA = String(a.cdlocal).toLowerCase().trim();
                const codigoB = String(b.cdlocal).toLowerCase().trim();
                return codigoA.localeCompare(codigoB, undefined, {
                  numeric: true
                });
              });
            }
            this.highlightedCodigoLocalIndex = this.codigosLocaisFiltrados.length > 0 ? 0 : -1;
          }
        } catch (e) {
          console.error('Erro ao buscar códigos de locais:', e);
          this.codigosLocaisFiltrados = [];
        } finally {
          this.loadingCodigosLocais = false;
        }
      },
      abrirDropdownCodigosLocais(force = false) {
        this.showCodigoLocalDropdown = true;
        // Se clicou na lupa ou focou no campo, sempre buscar todos os códigos
        if (force || String(this.codigoLocalDigitado || '').trim() === '') {
          this.buscarCodigosLocaisFiltrados();
        } else if (String(this.codigoLocalDigitado || '').trim() !== '') {
          this.buscarCodigosLocaisFiltrados();
        }
      },
      selecionarCodigoLocal(codigo) {
        console.log('✅ [SELECIONAR CÓDIGO] Código selecionado:', codigo.cdlocal, '→ ID:', codigo.id);

        // 1️⃣ Atualizar o Local
        this.codigoLocalDigitado = String(codigo.cdlocal);
        this.formData.CDLOCAL = String(codigo.id); // ✅ DEVE SER o ID, não cdlocal!
        this.codigoLocalSelecionado = String(codigo.cdlocal);

        // 2️⃣ 🆕 PREENCHER AUTOMATICAMENTE O NOME DO LOCAL
        // Este é o campo visível que o usuário vê
        const nomeLocal = codigo.LOCAL || codigo.delocal || '';
        this.nomeLocalBusca = nomeLocal; // Campo visível no input
        this.localNome = nomeLocal; // Variável interna

        // 3️⃣ Preencher ID do local selecionado
        this.localSelecionadoId = codigo.id;

        // 4️⃣ Fechar dropdown do código
        this.showCodigoLocalDropdown = false;

        // 5️⃣ Buscar todos os locais com este código para validação
        // Isso mantém a lista de locais para caso haja múltiplos
        this.buscarLocalPorCodigo();

        // Auto-focus no próximo input após selecionar Local
        this.$nextTick(() => {
          setTimeout(() => {
            // Próximo campo após CDLOCAL é NMPLANTA (Código do Termo)
            const inputNmplanta = document.getElementById('NMPLANTA');
            if (inputNmplanta) {
              inputNmplanta.focus();
              console.log('🎯 [selecionarCodigoLocal] Focus movido para NMPLANTA');
            }
          }, 50);
        });
      },
      selecionarCodigoLocalEnter() {
        if (!this.showCodigoLocalDropdown) return;
        if (this.highlightedCodigoLocalIndex < 0 || this.highlightedCodigoLocalIndex >= this.codigosLocaisFiltrados.length) return;
        this.selecionarCodigoLocal(this.codigosLocaisFiltrados[this.highlightedCodigoLocalIndex]);
      },
      limparCodigoLocal() {
        console.log('🧹 [LIMPAR LOCAL] Limpando tudo');
        this.codigoLocalDigitado = '';
        this.formData.CDLOCAL = '';
        this.codigoLocalSelecionado = '';
        this.localNome = '';
        this.nomeLocalBusca = '';
        this.codigosLocaisFiltrados = [];
        this.showCodigoLocalDropdown = false;
        this.highlightedCodigoLocalIndex = -1;
        this.locaisEncontrados = [];
      },

      handleCodigoLocalInput() {
        // Se o usuário digitar algo diferente do selecionado,
        // limpar o ID para não enviar um CDLOCAL antigo no submit.
        const digitado = String(this.codigoLocalDigitado || '').trim();
        if (this.codigoLocalSelecionado && digitado !== this.codigoLocalSelecionado) {
          this.formData.CDLOCAL = '';
          this.localSelecionadoId = null;
          this.nomeLocalBusca = '';
          this.localNome = '';
          this.codigoLocalSelecionado = '';
        }
      },

      async validarCodigoLocalNoBlur() {
        // Regra: projeto define locais. Sem projeto, não valida.
        if (!this.formData.CDPROJETO) {
          return;
        }

        const digitado = String(this.codigoLocalDigitado || '').trim();

        // Se vazio, garantir tudo limpo
        if (digitado === '') {
          this.limparCodigoLocal();
          return;
        }

        // Já está selecionado corretamente
        if (this.codigoLocalSelecionado && digitado === this.codigoLocalSelecionado && this.formData.CDLOCAL) {
          return;
        }

        try {
          const url = `/api/locais/buscar?cdprojeto=${encodeURIComponent(this.formData.CDPROJETO)}&termo=${encodeURIComponent(digitado)}`;
          const resp = await fetch(url, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          });

          if (!resp.ok) {
            // Falhou validar: não manter estado antigo
            this.formData.CDLOCAL = '';
            this.localSelecionadoId = null;
            this.nomeLocalBusca = '';
            this.localNome = '';
            this.codigoLocalSelecionado = '';
            return;
          }

          const locais = await resp.json();
          const matchExato = (locais || []).find(l => String(l.cdlocal) === digitado);
          if (matchExato) {
            this.selecionarCodigoLocal(matchExato);
            return;
          }

          // Não existe no projeto: limpar seleção para forçar escolha válida
          this.formData.CDLOCAL = '';
          this.localSelecionadoId = null;
          this.nomeLocalBusca = '';
          this.localNome = '';
          this.codigoLocalSelecionado = '';
        } catch (e) {
          console.warn('⚠️ [validarCodigoLocalNoBlur] Falha ao validar local:', e);
          this.formData.CDLOCAL = '';
          this.localSelecionadoId = null;
          this.nomeLocalBusca = '';
          this.localNome = '';
          this.codigoLocalSelecionado = '';
        }
      },
      navegarCodigosLocais(delta) {
        if (!this.showCodigoLocalDropdown || this.codigosLocaisFiltrados.length === 0) return;
        const max = this.codigosLocaisFiltrados.length - 1;
        if (this.highlightedCodigoLocalIndex === -1) {
          this.highlightedCodigoLocalIndex = 0;
        } else {
          this.highlightedCodigoLocalIndex = Math.min(max, Math.max(0, this.highlightedCodigoLocalIndex + delta));
        }
        // Scroll into view
        this.$nextTick(() => {
          const list = this.$root.querySelector('[x-show="showCodigoLocalDropdown"]');
          if (!list) return;
          const items = list.querySelectorAll('[x-for*="codigosLocaisFiltrados"]');
          const el = items[this.highlightedCodigoLocalIndex];
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
        this.loadingPatrimonios = true;

        try {
          // Buscar com ou sem termo (sem termo = lista completa do usuário)
          const resp = await fetch(`/api/patrimonios/pesquisar?q=${encodeURIComponent(termo)}`);
          if (resp.ok) {
            this.patrimoniosLista = await resp.json();
            this.highlightedPatIndex = this.patrimoniosLista.length > 0 ? 0 : -1;
            
            // Mostrar dropdown se houver resultados
            if (this.patrimoniosLista.length > 0) {
              this.showPatDropdown = true;
            } else {
              // Se vazio e não tem termo, deixa aberto para o usuário digitar
              if (termo === '') {
                this.showPatDropdown = true;
              }
            }
          } else if (resp.status === 403) {
            // Não autorizado
            this.patrimoniosLista = [];
            this.showPatDropdown = true;
          }
        } catch (e) {
          console.error('Falha busca patrimonios', e);
          this.patrimoniosLista = [];
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
          const r = await fetch(`/api/codigos/pesquisar?q=${encodeURIComponent(valor)}`, { credentials: 'same-origin' });
          if (r.ok) {
            const data = await r.json();
            // Se encontrar resultados, seleciona o primeiro
            if (data.length > 0) {
              this.formData.NUSEQOBJ = data[0].CODOBJETO;
              this.formData.DEOBJETO = data[0].DESCRICAO || valor;
              this.isNovoCodigo = false; // bloqueia edição do código
              this.codigoBuscaStatus = ''; // sem mensagem quando encontrado
            } else {
              // Sem resultado: novo código
              this.formData.NUSEQOBJ = '';
              this.formData.DEOBJETO = valor;
              this.isNovoCodigo = true; // libera edição do código
              this.codigoBuscaStatus = 'Preencha o número do código do objeto.';
            }
          } else {
            // Erro na busca
            this.formData.NUSEQOBJ = '';
            this.formData.DEOBJETO = valor;
            this.isNovoCodigo = true;
            this.codigoBuscaStatus = 'Preencha o número do código do objeto.';
          }
        } catch (e) {
          console.error('Erro ao buscar código do objeto', e);
          this.codigoBuscaStatus = 'Preencha o número do código do objeto.';
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
          const resp = await fetch(`/api/codigos/pesquisar?q=${encodeURIComponent(termo)}`, { credentials: 'same-origin' });
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
        this.codigoBuscaStatus = ''; // sem mensagem quando selecionado
        this.showCodigoDropdown = false;

        // Auto-focus para o próximo campo: Observações (DEHISTORICO)
        this.$nextTick(() => {
          setTimeout(() => {
            const dehistorico = document.getElementById('DEHISTORICO');
            if (dehistorico) {
              dehistorico.focus();
              console.log('🎯 [selecionarCodigo] Focus movido para DEHISTORICO (Observações)');
            }
          }, 50);
        });
      },
      selecionarCodigoEnter() {
        if (!this.showCodigoDropdown) return;
        if (this.highlightedCodigoIndex < 0 || this.highlightedCodigoIndex >= this.codigosLista.length) return;
        this.selecionarCodigo(this.codigosLista[this.highlightedCodigoIndex]);
      },
      selecionarCodigoTab(event) {
        const termo = this.descricaoSearch.trim();
        if (termo === '') {
          return;
        }

        // Se já há resultados na lista, seleciona o primeiro
        if (this.codigosLista && this.codigosLista.length > 0) {
          this.selecionarCodigo(this.codigosLista[0]);
          this.$nextTick(() => {
            try {
              event.target?.form?.querySelector('[tabindex="4"]')?.focus();
            } catch (e) {
              console.warn('Erro ao focar próximo campo:', e);
            }
          });
          return;
        }

        // Se está carregando, aguarda e tenta novamente
        if (this.loadingCodigos) {
          setTimeout(() => {
            this.selecionarCodigoTab(event);
          }, 150);
          return;
        }

        // Se não há resultados mas também não está carregando, força a busca agora
        this.loadingCodigos = true;
        fetch(`/api/codigos/pesquisar?q=${encodeURIComponent(termo)}`)
          .then(resp => {
            if (resp.ok) return resp.json();
            throw new Error('Erro na busca');
          })
          .then(data => {
            this.codigosLista = data || [];
            if (this.codigosLista.length > 0) {
              this.selecionarCodigo(this.codigosLista[0]);
              try {
                event.target?.form?.querySelector('[tabindex="4"]')?.focus();
              } catch (e) {
                console.warn('Erro ao focar próximo campo:', e);
              }
            } else {
              try {
                event.target?.form?.querySelector('[tabindex="4"]')?.focus();
              } catch (e) {
                console.warn('Erro ao focar próximo campo:', e);
              }
            }
          })
          .catch(e => {
            console.error('Falha ao buscar códigos:', e);
            try {
              event.target?.form?.querySelector('[tabindex="4"]')?.focus();
            } catch (err) {
              console.warn('Erro ao focar próximo campo:', err);
            }
          })
          .finally(() => {
            this.loadingCodigos = false;
          });
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

        // Regra: sem projeto não existe busca de locais
        if (!this.formData.CDPROJETO) {
          this.showLocalDropdown = true;
          this.locaisFiltrados = [];
          this.highlightedLocalIndex = -1;
          return;
        }

        // Se vazio, limpar tudo
        if (termo === '') {
          this.showLocalDropdown = false;
          this.locaisFiltrados = [];
          this.highlightedLocalIndex = -1;
          return;
        }

        console.log('🔍 [BUSCA LOCAL] Termo digitado:', termo);

        try {
          // Se um projeto foi selecionado, incluir como parâmetro para filtrar
          let url = `/api/locais/buscar?termo=${encodeURIComponent(termo)}`;
          url += `&cdprojeto=${encodeURIComponent(this.formData.CDPROJETO)}`;
          console.log('🔍 [BUSCA LOCAL] Filtrando por projeto:', this.formData.CDPROJETO);
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
        if (!this.formData.CDPROJETO) {
          console.warn('Selecione um projeto antes de listar locais');
          this.locaisFiltrados = [];
          this.showLocalDropdown = true;
          return;
        }
        this.localSearch = '';
        await this.buscarLocaisPorCodigo();
        // Buscar todos sem filtro
        try {
          let url = `/api/locais/buscar?termo=`;
          if (this.formData.CDPROJETO) {
            url += `&cdprojeto=${encodeURIComponent(this.formData.CDPROJETO)}`;
          }
          const resp = await fetch(url);
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
      // ========================================
      // ✅ SELECIONAR LOCAL DO DROPDOWN
      // ========================================
      async selecionarLocal(local) {
        console.log('✅ [SELECIONAR] Local clicado:', local);

        // Definir Local (DEVE SER o ID!)
        this.formData.CDLOCAL = local.id; // ✅ DEVE SER o ID!
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

      // === Funções para Projetos Associados ===
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
        this.formData.CDLOCAL = l.id; // ✅ DEVE SER o ID!
        this.localSearch = l.cdlocal; // Apenas o Local
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

        // Sem projeto definido, não buscar locais (regra: projeto define os locais)
        if (!this.formData.CDPROJETO) {
          this.locaisEncontrados = [];
          return;
        }

        try {
          let url = `/api/locais/buscar?termo=${encodeURIComponent(codigo)}`;
          if (this.formData.CDPROJETO) {
            url += `&cdprojeto=${encodeURIComponent(this.formData.CDPROJETO)}`;
          }
          const resp = await fetch(url);
          if (!resp.ok) {
            this.locaisEncontrados = [];
            // NÃO limpar nomeLocalBusca aqui - já foi preenchido em selecionarCodigoLocal
            return;
          }

          const locais = await resp.json();
          this.locaisEncontrados = locais;

          // Se encontrou exatamente 1, selecionar automaticamente
          if (locais.length === 1) {
            const primeiro = locais[0];
            // 🆕 MANTER o nome que foi preenchido em selecionarCodigoLocal
            // Só sobrescrever se não estiver preenchido
            if (!this.nomeLocalBusca) {
              this.nomeLocalBusca = primeiro.LOCAL || primeiro.delocal || '';
            }
            this.localNome = this.nomeLocalBusca;
            this.formData.CDLOCAL = primeiro.id; // Usar ID em vez de cdlocal
            this.localSelecionadoId = primeiro.id;
            this.formData.CDPROJETO = primeiro.CDPROJETO || '';
            this.projetoAssociadoSearch = primeiro.CDPROJETO && primeiro.NOMEPROJETO ?
              `${primeiro.CDPROJETO} - ${primeiro.NOMEPROJETO}` :
              '';
          } else if (locais.length > 1) {
            // Múltiplos locais - MAS MANTER O CDLOCAL QUE FOI SELECIONADO
            // O CDLOCAL já foi definido em selecionarCodigoLocal
            // O nomeLocalBusca já foi definido em selecionarCodigoLocal
            // NÃO zeramos aqui para manter o preenchimento automático
            this.localNome = this.nomeLocalBusca;
            // IMPORTANTE: NÃO LIMPAR formData.CDLOCAL - já foi atribuído em selecionarCodigoLocal!
            // this.formData.CDLOCAL = ''; ← REMOVIDO!
            // this.localSelecionadoId = null; ← REMOVIDO!

            // Pegar projeto do primeiro (todos devem ter o mesmo)
            const primeiro = locais[0];
            this.formData.CDPROJETO = primeiro.CDPROJETO || '';
            this.projetoAssociadoSearch = primeiro.CDPROJETO && primeiro.NOMEPROJETO ?
              `${primeiro.CDPROJETO} - ${primeiro.NOMEPROJETO}` :
              '';
            console.log(`ℹ️ [BUSCAR LOCAL POR CÓDIGO] ${locais.length} locais encontrados com código ${this.codigoLocalDigitado}, mantendo CDLOCAL = ${this.formData.CDLOCAL}`);
          } else {
            // Nenhum local encontrado
            // NÃO limpar nomeLocalBusca - deixar para o usuário decidir
            this.localNome = this.nomeLocalBusca; // Manter o que foi preenchido
            this.formData.CDLOCAL = '';
            this.localSelecionadoId = null;
          }
        } catch (error) {
          console.error('Erro ao buscar local:', error);
          this.locaisEncontrados = [];
          // NÃO limpar nomeLocalBusca aqui - deixar o preenchimento
        }
      },

      // ========================================
      // � FUNÇÕES DE ORDENAÇÃO POR PROXIMIDADE
      // ========================================

      /**
       * Calcular distância de Levenshtein entre duas strings
       * Quanto menor, mais similares são
       */
      calcularDistanciaLevenshtein(s1, s2) {
        s1 = String(s1).toLowerCase();
        s2 = String(s2).toLowerCase();

        const len1 = s1.length;
        const len2 = s2.length;

        if (len1 === 0) return len2;
        if (len2 === 0) return len1;

        const matriz = Array(len2 + 1).fill(null).map(() => Array(len1 + 1).fill(0));

        for (let i = 0; i <= len1; i++) matriz[0][i] = i;
        for (let j = 0; j <= len2; j++) matriz[j][0] = j;

        for (let j = 1; j <= len2; j++) {
          for (let i = 1; i <= len1; i++) {
            const custo = s1[i - 1] === s2[j - 1] ? 0 : 1;
            matriz[j][i] = Math.min(
              matriz[j][i - 1] + 1, // Inserção
              matriz[j - 1][i] + 1, // Deleção
              matriz[j - 1][i - 1] + custo // Substituição
            );
          }
        }

        return matriz[len2][len1];
      },

      /**
       * Ordenar projetos por proximidade ao termo digitado
       * Prioridade: Match exato do código → Começa com termo → Contém termo
       */
      ordenarPorProximidade(items, termo, fieldCodigo, fieldNome) {
        const termoLower = String(termo).toLowerCase().trim();

        // Calcular score para cada item
        const itemsComScore = items.map(item => {
          const codigo = String(item[fieldCodigo] || '').toLowerCase().trim();
          const nome = String(item[fieldNome] || '').toLowerCase().trim();

          let score = 1000; // Default alto (pior)

          // 🥇 Match exato do código (prioridade máxima)
          if (codigo === termoLower) {
            score = 0;
          }
          // 🥈 Código começa com o termo
          else if (codigo.startsWith(termoLower)) {
            score = 10 + this.calcularDistanciaLevenshtein(codigo, termoLower);
          }
          // 🥉 Código contém o termo
          else if (codigo.includes(termoLower)) {
            const posicao = codigo.indexOf(termoLower);
            score = 50 + posicao + this.calcularDistanciaLevenshtein(codigo, termoLower);
          }
          // Nome começa com o termo
          else if (nome.startsWith(termoLower)) {
            score = 100 + this.calcularDistanciaLevenshtein(nome, termoLower);
          }
          // Nome contém o termo
          else if (nome.includes(termoLower)) {
            const posicao = nome.indexOf(termoLower);
            score = 200 + posicao + this.calcularDistanciaLevenshtein(nome, termoLower);
          }
          // Distância de Levenshtein (similaridade)
          else {
            score = 500 + this.calcularDistanciaLevenshtein(codigo, termoLower);
          }

          return {
            item,
            score
          };
        });

        // Ordenar por score (menor primeiro = mais relevante)
        return itemsComScore
          .sort((a, b) => {
            // Se scores são iguais, ordenar por código alfabeticamente
            if (a.score === b.score) {
              const codigoA = String(a.item[fieldCodigo] || '');
              const codigoB = String(b.item[fieldCodigo] || '');
              return codigoA.localeCompare(codigoB, undefined, {
                numeric: true
              });
            }
            return a.score - b.score;
          })
          .map(x => x.item);
      },

      
      // ========================================
      // FUNCOES DO MODAL CRIAR BEM
      // ========================================
      abrirModalCriarBem() {
        this.erroCriacaoBem = '';
        this.salvandoBem = false;
        this.novoBem = {
          NUSEQTIPOPATR: '',
          DETIPOPATR: '',
          DEOBJETO: '',
        };

        const descricaoAtual = String(this.descricaoSearch || this.formData.DEOBJETO || '').trim();
        if (descricaoAtual) {
          this.novoBem.DEOBJETO = descricaoAtual;
        }

        this.modalCriarBemOpen = true;

        this.$nextTick(() => {
          const input = this.$refs.inputBemTipoCodigo;
          if (input) {
            input.focus();
          }
        });
      },

      fecharModalCriarBem() {
        this.modalCriarBemOpen = false;
        this.erroCriacaoBem = '';
        this.salvandoBem = false;
        this.novoBem = {
          NUSEQTIPOPATR: '',
          DETIPOPATR: '',
          DEOBJETO: '',
        };
      },

      async salvarNovoBem() {
        const tipoCodigo = String(this.novoBem.NUSEQTIPOPATR || '').trim();
        const tipoNome = String(this.novoBem.DETIPOPATR || '').trim();
        const descricao = String(this.novoBem.DEOBJETO || '').trim();

        if (!tipoCodigo) {
          this.erroCriacaoBem = 'Informe o c?digo do tipo.';
          return;
        }

        if (!descricao) {
          this.erroCriacaoBem = 'Informe a descrição do bem.';
          return;
        }

        this.salvandoBem = true;
        this.erroCriacaoBem = '';

        try {
          const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
          const payload = {
            NUSEQTIPOPATR: tipoCodigo,
            DETIPOPATR: tipoNome || null,
            DEOBJETO: descricao,
          };

          const response = await fetch("{{ route('relatorios.bens.store') }}", {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrfToken,
              'X-Requested-With': 'XMLHttpRequest',
              'Accept': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
          });

          if (!response.ok) {
            let message = '';
            try {
              const errData = await response.clone().json();
              if (errData?.errors) {
                const key = Object.keys(errData.errors)[0];
                message = errData.errors[key]?.[0] || '';
              }
              if (!message) {
                message = errData?.message || '';
              }
            } catch (_) {
              message = '';
            }

            if (!message) {
              try {
                message = await response.text();
              } catch (_) {
                message = '';
              }
            }

            throw new Error(message || 'Erro ao criar bem.');
          }

          const data = await response.json();
          const novoId = data?.data?.id || data?.id || '';
          const descricaoFinal = data?.data?.descricao || descricao;

          if (!novoId) {
            throw new Error('C?digo do bem n?o retornado.');
          }

          this.formData.NUSEQOBJ = String(novoId);
          this.formData.DEOBJETO = descricaoFinal;
          this.descricaoSearch = descricaoFinal;
          this.isNovoCodigo = false;
          this.codigoBuscaStatus = '';
          this.showCodigoDropdown = false;
          this.codigosLista = [];
          this.highlightedCodigoIndex = -1;

          this.fecharModalCriarBem();

          this.$nextTick(() => {
            setTimeout(() => {
              document.getElementById('DEHISTORICO')?.focus();
            }, 50);
          });
        } catch (e) {
          this.erroCriacaoBem = e?.message || 'Erro ao criar bem.';
        } finally {
          this.salvandoBem = false;
        }
      },

// ========================================
      // �🆕 FUNÇÕES DO MODAL CRIAR PROJETO/LOCAL
      // ========================================

      // ═══════════════════════════════════════════════════════════
      // 🆕 NOVO FLUXO DO MODAL (Projeto → Código → Nome)
      // ═══════════════════════════════════════════════════════════

      /**
       * Abrir modal de criar novo local
       * Salva estado atual do formulário antes de abrir
       */
      abrirModalCriarProjeto() {
        console.log('🟢 [MODAL CRIAR] Abrindo modal para criar novo local');

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
          userSearch: this.userSearch,
          userSelectedName: this.userSelectedName,
        };

        // 2. Limpar dados do modal
        this.novoProjeto = {
          cdlocal: '',
          nomeLocal: '',
          cdprojeto: '',
          nmProjeto: '',
        };
        this.modalProjetoSearch = '';
        this.modalProjetosLista = [];
        this.showModalProjetoDropdown = false;
        this.modalCodigoLocalSearch = '';
        this.modalCodigosLocaisDisponiveis = [];
        this.modalCodigosLocaisFiltrados = [];
        this.showModalCodigoLocalDropdown = false;
        this.erroCriacaoProjeto = '';
        this.salvandoCriacaoProjeto = false;

        // 3. Abrir modal
        this.modalCriarProjetoOpen = true;

        // 4. Focar no campo de Projeto
        this.$nextTick(() => {
          const input = this.$refs.inputProjetoAssociado;
          if (input) {
            input.focus();
            console.log('✅ [MODAL CRIAR] Focus no campo "Projeto"');
          }
        });
      },

      /**
       * Buscar projetos para o dropdown do modal
       * A API já retorna ordenado numericamente
       * Se vazio: mostra primeiros 50
       * Se tem termo: busca por matching
       */
      async buscarModalProjetos() {
        const termo = String(this.modalProjetoSearch || '').trim();

        this.loadingModalProjetos = true;
        try {
          let projetos = [];

          if (termo === '') {
            // Quando vazio, buscar todos os projetos (sem filtro)
            const resp = await fetch(`/api/projetos/pesquisar?q=`);
            if (resp.ok) {
              projetos = await resp.json();

              console.log('📊 [MODAL] Total de projetos retornados:', projetos.length);
              console.log('🔢 [MODAL] Códigos retornados:', projetos.map(p => p.CDPROJETO).join(', '));

              // API já retorna ordenado numericamente, apenas limita aos primeiros 50
              projetos = projetos.slice(0, 50);

              console.log('✂️ [MODAL] Após slice(0,50):', projetos.length, 'projetos');
              console.log('✅ [MODAL] Primeiros 50 códigos:', projetos.map(p => p.CDPROJETO).join(', '));
            }
          } else {
            // Com termo de busca, faz a busca normalmente
            const resp = await fetch(`/api/projetos/pesquisar?q=${encodeURIComponent(termo)}`);
            if (resp.ok) {
              projetos = await resp.json();
            }
          }

          this.modalProjetosLista = projetos;
          this.highlightedModalProjetoIndex = this.modalProjetosLista.length > 0 ? 0 : -1;
        } catch (e) {
          console.error('Erro ao buscar projetos:', e);
          this.modalProjetosLista = [];
        } finally {
          this.loadingModalProjetos = false;
        }
      },

      abrirModalDropdownProjeto(force = false) {
        this.showModalProjetoDropdown = true;
        if (force || this.modalProjetoSearch.trim() === '') {
          // Se force ou se vazio, buscar
          this.buscarModalProjetos();
        }
      },

      async selecionarModalProjeto(projeto) {
        // Atualizar dados do projeto
        this.novoProjeto.cdprojeto = projeto.CDPROJETO;
        this.novoProjeto.nmProjeto = projeto.NOMEPROJETO;
        this.modalProjetoSearch = `${projeto.CDPROJETO} - ${projeto.NOMEPROJETO}`;
        this.showModalProjetoDropdown = false;

        // Gerar Local automaticamente
        await this.gerarCodigoLocalAutomatico(projeto.CDPROJETO);

        // Focar no campo de nome do local com delay para garantir renderização
        setTimeout(() => {
          this.$nextTick(() => {
            const input = this.$refs.inputNomeLocal;
            console.log('🎯 [MODAL] Tentando focar em inputNomeLocal...');
            console.log('🎯 [MODAL] Elemento encontrado?', !!input);
            if (input) {
              input.focus();
              console.log('🎯 [MODAL] ✅ Focus aplicado ao inputNomeLocal');
            } else {
              console.log('🎯 [MODAL] ❌ inputNomeLocal não encontrado');
            }
          });
        }, 300);
      },

      selecionarModalProjetoEnter() {
        if (!this.showModalProjetoDropdown) return;
        if (this.highlightedModalProjetoIndex < 0 || this.highlightedModalProjetoIndex >= this.modalProjetosLista.length) return;
        this.selecionarModalProjeto(this.modalProjetosLista[this.highlightedModalProjetoIndex]);
      },

      limparModalProjeto() {
        this.novoProjeto.cdprojeto = '';
        this.novoProjeto.nmProjeto = '';
        this.novoProjeto.cdlocal = '';
        this.novoProjeto.nomeLocal = '';
        this.modalProjetoSearch = '';
        this.modalProjetosLista = [];
        this.showModalProjetoDropdown = false;
        this.modalCodigosLocaisDisponiveis = [];
        this.modalCodigosLocaisFiltrados = [];
        this.modalCodigoLocalSearch = '';
      },

      navegarModalProjetos(delta) {
        if (!this.showModalProjetoDropdown || this.modalProjetosLista.length === 0) return;
        const max = this.modalProjetosLista.length - 1;
        if (this.highlightedModalProjetoIndex === -1) {
          this.highlightedModalProjetoIndex = 0;
        } else {
          this.highlightedModalProjetoIndex = Math.min(max, Math.max(0, this.highlightedModalProjetoIndex + delta));
        }
      },

      /**
       * Carregar códigos de locais já existentes do projeto selecionado
       */
      /**
      * Gerar Local automaticamente baseado no sequencial do projeto
       */
      async gerarCodigoLocalAutomatico(cdprojeto) {
        this.carregandoCodigosLocaisModal = true;
        try {
          // Buscar todos os locais deste projeto
          const resp = await fetch(`/api/locais/buscar?cdprojeto=${encodeURIComponent(cdprojeto)}`);
          if (resp.ok) {
            const locais = await resp.json();

            // Encontrar o próximo código disponível
            const codigosExistentes = locais.map(local => {
              const cod = String(local.cdlocal || '');
              return parseInt(cod) || 0;
            }).filter(cod => !isNaN(cod) && cod > 0);

            // Próximo código = máximo existente + 1
            const proximoCodigo = (codigosExistentes.length > 0 ? Math.max(...codigosExistentes) : 0) + 1;

            // Gerar com zero-padding (ex: 001, 002, etc)
            this.novoProjeto.cdlocal = String(proximoCodigo).padStart(3, '0');
          }
        } catch (e) {
          console.error('❌ [MODAL CÓDIGO] Erro ao gerar código:', e);
          // Em caso de erro, tenta usar um padrão simples
          this.novoProjeto.cdlocal = '001';
        } finally {
          this.carregandoCodigosLocaisModal = false;
        }
      },

      /**
       * Fechar modal de criar local
       */
      fecharModalCriarProjeto() {
        console.log('🔴 [MODAL CRIAR] Fechando modal');

        // Fechar o modal independente do estado de salvandoCriacaoProjeto
        this.modalCriarProjetoOpen = false;
        this.novoProjeto = {
          cdlocal: '',
          nomeLocal: '',
          cdprojeto: '',
          nmProjeto: '',
        };
        this.modalProjetoSearch = '';
        this.modalCodigoLocalSearch = '';
        this.modalProjetosLista = [];
        this.modalCodigosLocaisDisponiveis = [];
        this.modalCodigosLocaisFiltrados = [];
        this.erroCriacaoProjeto = '';
        this.salvandoCriacaoProjeto = false;

        // 🎯 AUTO-FOCUS NO CAMPO "LOCAL"
        this.$nextTick(() => {
          const input = document.getElementById('CDLOCAL_INPUT');
          if (input && !input.disabled) {
            input.focus();
          }
        });
      },

      /**
       * Salvar novo local com código gerado automaticamente
       */
      async salvarNovoLocal() {
        const cdlocal = String(this.novoProjeto.cdlocal || '').trim();
        const nomeLocal = String(this.novoProjeto.nomeLocal || '').trim();
        const cdprojeto = this.novoProjeto.cdprojeto;

        // Validações
        if (!cdprojeto) {
          this.erroCriacaoProjeto = '❌ Selecione um projeto';
          return;
        }

        if (!cdlocal) {
          this.erroCriacaoProjeto = '❌ Local não foi gerado corretamente';
          return;
        }

        if (!nomeLocal) {
          this.erroCriacaoProjeto = '❌ Digite o nome do local';
          return;
        }

        this.salvandoCriacaoProjeto = true;
        this.erroCriacaoProjeto = '';

        try {
          const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

          const payload = {
            cdlocal: cdlocal,
            local: nomeLocal,
            cdprojeto: cdprojeto,
          };

          console.log('💾 [SALVAR LOCAL] Payload:', payload);

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

          if (data.success) {
            console.log('✅ [MODAL] Local criado com sucesso! Preenchendo formulário...');

            // 🚫 Desativar watch para não buscar o local novamente
            this.desativarWatchCDLOCAL = true;

            // 🎯 REMOVER ZEROS À ESQUERDA DO CÓDIGO (017 → 17, 001 → 1)
            const cdlocalSemZeros = String(cdlocal).replace(/^0+/, '') || '0';

            // 🎯 USAR O ID RETORNADO PELA API (não o cdlocal!)
            const novoLocalId = data.data?.id || data.id;
            console.log(`🔑 [MODAL] ID do local criado: ${novoLocalId}, cdlocal: ${cdlocalSemZeros}`);

            // ✅ PASSO 1: PREENCHER CDPROJETO PRIMEIRO (para ativar o input CDLOCAL_INPUT)
            this.formData.CDPROJETO = String(cdprojeto); // Garantir que é string para consistência
            console.log('✅ [PREENCHER] formData.CDPROJETO setado para:', this.formData.CDPROJETO);

            await this.buscarProjetoELocais();

            // ✅ PASSO 2: AGUARDAR RENDERIZAÇÃO PARA GARANTIR QUE O INPUT FOI HABILITADO
            await this.$nextTick();
            await this.$nextTick(); // Duplo nextTick para maior segurança

            // ✅ PASSO 3: PREENCHER CAMPOS DO LOCAL
            this.codigoLocalDigitado = cdlocalSemZeros;
            this.nomeLocal = nomeLocal;
            this.nomeLocalBusca = nomeLocal;
            this.localSelecionadoId = novoLocalId; // ✅ USAR ID DO BANCO
            this.formData.CDLOCAL = novoLocalId; // ✅ USAR ID DO BANCO, NÃO CDLOCAL!
            this.projetoAssociadoSearch = `${cdprojeto} - ${this.novoProjeto.nmProjeto}`;
            this.projetoSearch = `${cdprojeto} - ${this.novoProjeto.nmProjeto}`;

            // ✅ PASSO 4: BUSCAR O LOCAL CRIADO E PREENCHER AUTOMATICAMENTE OS PROJETOS ASSOCIADOS
            try {
              const resLocal = await fetch('/api/locais/buscar?termo=');
              if (resLocal.ok) {
                const locais = await resLocal.json();
                const localCriado = locais.find(l => String(l.id) === String(novoLocalId));

                if (localCriado) {
                  // Chamar função para preencher dados completos do local (incluindo projeto)
                  this.preencherDadosLocal(localCriado);
                  console.log('✅ [MODAL] Dados completos do local preenchidos (incluindo projeto):', localCriado);
                } else {
                  console.warn('⚠️ [MODAL] Local criado não encontrado na API');
                }
              }
            } catch (e) {
              console.error('❌ [MODAL] Erro ao buscar local criado:', e);
            }

            // Restaurar também os campos que foram salvos no estadoTemporario
            this.formData.NUPATRIMONIO = this.estadoTemporario.NUPATRIMONIO || '';
            this.formData.NUMOF = this.estadoTemporario.NUMOF || '';
            this.formData.NUSEQOBJ = this.estadoTemporario.NUSEQOBJ || '';
            this.formData.DEOBJETO = this.estadoTemporario.DEOBJETO || '';
            this.formData.DEHISTORICO = this.estadoTemporario.DEHISTORICO || '';
            this.formData.MARCA = this.estadoTemporario.MARCA || '';
            this.formData.MODELO = this.estadoTemporario.MODELO || '';
            this.formData.NMPLANTA = this.estadoTemporario.NMPLANTA || '';
            this.formData.CDMATRFUNCIONARIO = this.estadoTemporario.CDMATRFUNCIONARIO || '';
            this.formData.DTAQUISICAO = this.estadoTemporario.DTAQUISICAO || '';
            this.formData.DTBAIXA = this.estadoTemporario.DTBAIXA || '';

            // Restaurar campos de busca
            this.patSearch = this.estadoTemporario.patSearch || '';
            this.userSearch = this.estadoTemporario.userSearch || '';
            this.userSelectedName = this.estadoTemporario.userSelectedName || '';

            // Restaurar situação
            await this.$nextTick();
            const selectSituacao = document.getElementById('SITUACAO');
            if (selectSituacao && this.estadoTemporario.SITUACAO) {
              selectSituacao.value = this.estadoTemporario.SITUACAO;
            }

            // Fechar o modal após preencher
            this.fecharModalCriarProjeto();

            console.log('✅ [MODAL] Formulário preenchido:');
            console.log('   - Projeto:', this.formData.CDPROJETO);
            console.log('   - Local:', this.formData.CDLOCAL);
            console.log('   - Nome Local:', this.nomeLocal);
            console.log('   - Projeto:', this.projetoAssociadoSearch);

            // ✅ PASSO 5: REATIVAR WATCH E DAR FOCUS NO INPUT "LOCAL"
            setTimeout(() => {
              this.desativarWatchCDLOCAL = false;
              console.log('🔄 [FOCUS] Watch CDLOCAL reativado');

              // Aguardar renderização completa
              this.$nextTick().then(() => {
                this.$nextTick().then(() => {
                  console.log('🔄 [FOCUS] $nextTick(x2) concluído');

                  // 🎯 DAR FOCUS NO CAMPO "LOCAL" (CDLOCAL_INPUT)
                  setTimeout(() => {
                    const inputCodigoLocal = document.getElementById('CDLOCAL_INPUT');
                    console.log('🎯 [FOCUS] ========== INICIANDO FOCUS ==========');
                    console.log('🎯 [FOCUS] Elemento CDLOCAL_INPUT encontrado?', !!inputCodigoLocal);

                    if (!inputCodigoLocal) {
                      console.error('❌ [FOCUS] Elemento não encontrado!');
                      return;
                    }

                    // Debug completo
                    const computed = window.getComputedStyle(inputCodigoLocal);
                    console.log('🎯 [FOCUS] Display:', computed.display);
                    console.log('🎯 [FOCUS] Visibility:', computed.visibility);
                    console.log('🎯 [FOCUS] Opacity:', computed.opacity);
                    console.log('🎯 [FOCUS] OffsetHeight:', inputCodigoLocal.offsetHeight);
                    console.log('🎯 [FOCUS] Disabled:', inputCodigoLocal.disabled);
                    console.log('🎯 [FOCUS] formData.CDPROJETO:', this.formData.CDPROJETO);
                    console.log('🎯 [FOCUS] formData.CDPROJETO é truthy?', !!this.formData.CDPROJETO);

                    // Verificar condição de habilitação
                    if (!this.formData.CDPROJETO) {
                      console.error('❌ [FOCUS] formData.CDPROJETO está vazio ou falsy');
                      return;
                    }

                    if (inputCodigoLocal.disabled) {
                      console.error('❌ [FOCUS] Input está desabilitado mesmo com CDPROJETO preenchido');
                      return;
                    }

                    // Tentar focar
                    console.log('🎯 [FOCUS] Tentando focar...');
                    inputCodigoLocal.scrollIntoView({
                      behavior: 'smooth',
                      block: 'center'
                    });

                    setTimeout(() => {
                      try {
                        inputCodigoLocal.focus({
                          preventScroll: true
                        });
                        inputCodigoLocal.select();

                        console.log('✅ [FOCUS] Focus aplicado com sucesso!');
                        console.log('✅ [FOCUS] activeElement agora é:', document.activeElement?.id);
                        console.log('✅ [FOCUS] hasFocus?', document.activeElement === inputCodigoLocal);
                      } catch (e) {
                        console.error('❌ [FOCUS] Erro ao aplicar focus:', e);
                      }
                    }, 150);
                  }, 300);
                });
              });
            }, 100);
          } else {
            this.erroCriacaoProjeto = data.message || '❌ Erro ao criar local';
            this.salvandoCriacaoProjeto = false;
          }

        } catch (error) {
          console.error('❌ [MODAL] Erro ao criar local:', error);
          this.erroCriacaoProjeto = error.message || '❌ Erro ao criar local';
          this.salvandoCriacaoProjeto = false;
        }
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
          // PRIORIZAR: Se há múltiplos, escolher o que tem projeto (descartando vazios)
          this.locaisEncontrados = todosLocais.filter(l => String(l.cdlocal) === codigo);

          // Se há múltiplos com mesmo código, priorizar o que tem CDPROJETO preenchido
          if (this.locaisEncontrados.length > 1) {
            const comProjeto = this.locaisEncontrados.filter(l => l.CDPROJETO && String(l.CDPROJETO).trim() !== '');
            if (comProjeto.length > 0) {
              console.log(`✅ [BUSCA] Múltiplos locais encontrados. Priorizando ${comProjeto.length} com projeto`);
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
        this.formData.CDLOCAL = local.id; // ✅ DEVE SER o ID!
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
      selecionarPatrimonio(p) {
        console.log('\n' + '='.repeat(80));
        console.log('🖱️  [SELECIONAR PATRIMONIO] CLICOU NO GRID');
        console.log('='.repeat(80));
        console.log('📌 Patrimônio clicado:', JSON.stringify(p, null, 2));
        this.formData.NUPATRIMONIO = p.NUPATRIMONIO;
        this.patSearch = p.NUPATRIMONIO;
        console.log('✓ formData.NUPATRIMONIO atualizado para:', this.formData.NUPATRIMONIO);
        this.showPatDropdown = false; // FECHAR O DROPDOWN ANTES DE BUSCAR
        this.patrimoniosLista = []; // Limpar a lista para evitar reabertura
        console.log('✓ Dropdown fechado e lista limpa');
        console.log('🔄 Chamando buscarPatrimonio()...');
        this.buscarPatrimonio();
      },
      selecionarPatrimonioEnter() {
        if (!this.showPatDropdown) return;
        if (this.highlightedPatIndex < 0 || this.highlightedPatIndex >= this.patrimoniosLista.length) return;
        this.selecionarPatrimonio(this.patrimoniosLista[this.highlightedPatIndex]);
      },
      selecionarPatrimonioTab(event) {
        const termo = this.patSearch.trim();
        if (termo === '') return;

        // Se já há resultados, seleciona o primeiro
        if (this.patrimoniosLista && this.patrimoniosLista.length > 0) {
          this.selecionarPatrimonio(this.patrimoniosLista[0]);
          this.$nextTick(() => {
            try {
              event.target?.form?.querySelector('[tabindex="2"]')?.focus();
            } catch (e) {
              console.warn('Erro ao focar próximo campo:', e);
            }
          });
          return;
        }

        // Se está carregando, aguarda
        if (this.loadingPatrimonios) {
          setTimeout(() => {
            this.selecionarPatrimonioTab(event);
          }, 150);
          return;
        }

        // Força a busca agora
        this.loadingPatrimonios = true;
        fetch(`/api/patrimonios/pesquisar?q=${encodeURIComponent(termo)}`)
          .then(resp => {
            if (resp.ok) return resp.json();
            throw new Error('Erro na busca');
          })
          .then(data => {
            this.patrimoniosLista = data || [];
            if (this.patrimoniosLista.length > 0) {
              this.selecionarPatrimonio(this.patrimoniosLista[0]);
            }
            try {
              event.target?.form?.querySelector('[tabindex="2"]')?.focus();
            } catch (e) {
              console.warn('Erro ao focar próximo campo:', e);
            }
          })
          .catch(e => {
            console.error('Falha ao buscar patrimônios:', e);
            try {
              event.target?.form?.querySelector('[tabindex="2"]')?.focus();
            } catch (err) {
              console.warn('Erro ao focar próximo campo:', err);
            }
          })
          .finally(() => {
            this.loadingPatrimonios = false;
          });
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




      async init() {
        console.log('\n' + '='.repeat(80));
        console.log('🚀 [INIT] Inicializando formulário...');
        console.log('='.repeat(80));
        console.log('📌 Modo:', this.isEditMode() ? 'EDIÇÃO' : 'CRIAÇÃO');
        console.log('📌 Dados do formulário (formData):', JSON.stringify({
          NUPATRIMONIO: this.formData.NUPATRIMONIO,
          NUSEQOBJ: this.formData.NUSEQOBJ,
          DEOBJETO: this.formData.DEOBJETO,
          CDPROJETO: this.formData.CDPROJETO,
          CDLOCAL: this.formData.CDLOCAL,
          CDMATRFUNCIONARIO: this.formData.CDMATRFUNCIONARIO,
          SITUACAO: this.formData.SITUACAO,
          MARCA: this.formData.MARCA,
          MODELO: this.formData.MODELO,
        }, null, 2));
        console.log('📌 descricaoSearch:', this.descricaoSearch);

        // ✨ Se é modo CRIAÇÃO, dar foco NO CAMPO DE BUSCA DE PATRIMÔNIOS
        if (!this.isEditMode()) {
          this.$nextTick(() => {
            setTimeout(() => {
              const inputPat = document.getElementById('patSearch');
              if (inputPat) {
                inputPat.focus();
                console.log('🎯 [INIT CRIAÇÃO] Focus movido para campo de busca de patrimônios');
              }
            }, 100);
          });
        }

        // Se é modo EDIÇÃO e há patrimônio carregado
        if (this.isEditMode()) {
          await this.carregarDadosEdicao();

          // Após carregar o patrimônio, garantir que a lista de locais do projeto seja carregada em segundo plano
          if (this.formData.CDPROJETO) {
            try {
              await this.buscarProjetoELocais();
              console.log('✅ [INIT] Locais do projeto carregados para edição');
            } catch (e) {
              console.warn('⚠️ [INIT] Falha ao carregar locais do projeto na edição', e);
            }
          }
        }

        // Carregar lista de projetos existentes para os modais
        await this.carregarProjetosExistentes();
        console.log('='.repeat(80) + '\n');
      },

      /**
       * Gera o próximo número sequencial de patrimônio
       */
      async gerarProximoNumeroPatrimonio() {
        try {
          console.log('📊 [GERAR NUM] Carregando próximo número de patrimônio...');
          const response = await fetch('/api/patrimonios/proximo-numero');

          if (!response.ok) {
            console.error('❌ [GERAR NUM] Erro ao buscar próximo número');
            alert('❌ Erro ao gerar número de patrimônio. Tente novamente.');
            return;
          }

          const data = await response.json();
          if (data.success && data.numero) {
            this.formData.NUPATRIMONIO = String(data.numero);
            this.patSearch = String(data.numero); // Sincronizar com o campo de busca
            console.log('✅ [GERAR NUM] Próximo número gerado:', data.numero);

            // Dar focus no campo NUMOF (Número da Ordem de Compra) após gerar o número
            this.$nextTick(() => {
              setTimeout(() => {
                const inputNumof = document.getElementById('NUMOF');
                if (inputNumof) {
                  inputNumof.focus();
                  console.log('🎯 [GERAR NUM] Focus movido para NUMOF (Número da Ordem de Compra)');
                }
              }, 100);
            });
          }
        } catch (error) {
          console.error('❌ [GERAR NUM] Erro ao gerar número:', error);
          alert('❌ Erro ao gerar número de patrimônio. Tente novamente.');
        }
      },

      /**
       * Função chamada quando NUMOF recebe focus (para consistência com fluxo esperado)
       */
      focarNumOf() {
        console.log('🎯 [NUMOF] Campo Número da Ordem de Compras focado');
      },

      // 🆕 Verifica se é modo EDIÇÃO
      isEditMode() {
        return Boolean(this.formData.NUPATRIMONIO);
      },

      // 🆕 Carrega TODOS os dados de um patrimônio para edição
      async carregarDadosEdicao() {
        console.log('\n' + '='.repeat(80));
        console.log('📥 [CARREGA EDIÇÃO] Iniciando carregamento completo do patrimônio');
        console.log('='.repeat(80));

        try {
          // 0️⃣ SINCRONIZAR patSearch COM formData.NUPATRIMONIO (em modo edição)
          if (this.formData.NUPATRIMONIO) {
            this.patSearch = String(this.formData.NUPATRIMONIO);
            console.log(`✅ [CARREGA EDIÇÃO] patSearch sincronizado: ${this.patSearch}`);
          }

          // 1️⃣ CARREGAR NOME DO PROJETO
          if (this.formData.CDPROJETO) {
            console.log(`🔍 [CARREGA EDIÇÃO] Carregando projeto ${this.formData.CDPROJETO}...`);
            try {
              const projResp = await fetch(`/api/projetos/pesquisar?q=${this.formData.CDPROJETO}`);
              if (projResp.ok) {
                const projetos = await projResp.json();
                const projeto = projetos.find(p => String(p.CDPROJETO) === String(this.formData.CDPROJETO)) || projetos[0];
                if (projeto) {
                  this.projetoSearch = `${projeto.CDPROJETO} - ${projeto.NOMEPROJETO}`;
                  console.log(`✅ [CARREGA EDIÇÃO] Projeto: ${this.projetoSearch}`);
                }
              }
            } catch (e) {
              console.warn(`⚠️ [CARREGA EDIÇÃO] Erro ao carregar projeto:`, e);
            }
          }

          // 2️⃣ CARREGAR LOCAL
          if (this.formData.CDLOCAL) {
            console.log(`🔍 [CARREGA EDIÇÃO] Carregando local ${this.formData.CDLOCAL} (normalização por projeto)...`);
            try {
              // ✅ Regra: normalizar usando a lista do projeto (evita confundir cdlocal legado com id)
              if (!this.formData.CDPROJETO) {
                console.warn('⚠️ [CARREGA EDIÇÃO] Sem CDPROJETO; não é possível validar local. Limpando CDLOCAL.');
                this.formData.CDLOCAL = '';
                this.codigoLocalDigitado = '';
                this.nomeLocalBusca = '';
                this.localSelecionadoId = null;
              } else {
                const url = `/api/locais/buscar?cdprojeto=${encodeURIComponent(this.formData.CDPROJETO)}&termo=`;
                const locaisResp = await fetch(url, {
                  credentials: 'same-origin',
                  headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (!locaisResp.ok) {
                  console.error('❌ [CARREGA EDIÇÃO] Erro ao carregar locais do projeto:', locaisResp.status);
                } else {
                  const locaisDoProjeto = await locaisResp.json();
                  this.locais = locaisDoProjeto;
                  this.codigosLocaisFiltrados = locaisDoProjeto;

                  // 1) Primeiro tenta por ID
                  let local = locaisDoProjeto.find(l => String(l.id) === String(this.formData.CDLOCAL));

                  // 2) Se não achar, tentar por cdlocal (compatibilidade legado)
                  if (!local) {
                    local = locaisDoProjeto.find(l => String(l.cdlocal) === String(this.formData.CDLOCAL));
                    if (local) {
                      console.warn(`⚠️ [CARREGA EDIÇÃO] CDLOCAL legado detectado (${this.formData.CDLOCAL}); normalizando para ID ${local.id}`);
                    }
                  }

                  if (local) {
                    this.preencherDadosLocal(local);
                    // Dropdown deve conter SOMENTE os locais do projeto
                    this.codigosLocaisFiltrados = locaisDoProjeto;
                  } else {
                    console.warn(`⚠️ [CARREGA EDIÇÃO] Local ${this.formData.CDLOCAL} não pertence ao projeto ${this.formData.CDPROJETO}; limpando seleção.`);
                    this.formData.CDLOCAL = '';
                    this.codigoLocalDigitado = '';
                    this.nomeLocalBusca = '';
                    this.localSelecionadoId = null;
                  }
                }
              }
            } catch (e) {
              console.warn(`⚠️ [CARREGA EDIÇÃO] Erro ao carregar local:`, e);
            }
          }

          // 3️⃣ CARREGAR CÓDIGO DO OBJETO
          if (this.formData.NUSEQOBJ) {
            console.log(`� [CARREGA EDIÇÃO] Carregando código ${this.formData.NUSEQOBJ}...`);
            try {
              const codResp = await fetch(`/api/codigos/buscar/${this.formData.NUSEQOBJ}`);
              if (codResp.ok) {
                const codigo = await codResp.json();
                if (codigo && codigo.descricao) {
                  this.descricaoSearch = codigo.descricao;
                  this.formData.DEOBJETO = codigo.descricao;
                  console.log(`✅ [CARREGA EDIÇÃO] Código: ${this.descricaoSearch}`);
                }
              }
            } catch (e) {
              console.warn(`⚠️ [CARREGA EDIÇÃO] Erro ao carregar código:`, e);
            }
          }

          // 4️⃣ CARREGAR FUNCIONÁRIO RESPONSÁVEL
          if (this.formData.CDMATRFUNCIONARIO) {
            console.log(`🔍 [CARREGA EDIÇÃO] Carregando funcionário ${this.formData.CDMATRFUNCIONARIO}...`);
            try {
              const funcResp = await fetch(`/api/funcionarios/pesquisar?q=${this.formData.CDMATRFUNCIONARIO}`);
              if (funcResp.ok) {
                const funcs = await funcResp.json();
                const func = funcs.find(f => String(f.CDMATRFUNCIONARIO) === String(this.formData.CDMATRFUNCIONARIO));
                if (func) {
                  let nomeLimpo = String(func.NMFUNCIONARIO || '').trim();
                  nomeLimpo = nomeLimpo.replace(/\d{2}\/\d{2}\/\d{4}/g, '');
                  nomeLimpo = nomeLimpo.replace(/\s+\d+\s*$/, '');
                  nomeLimpo = nomeLimpo.replace(/\s+/g, ' ').trim();

                  this.userSelectedName = `${this.formData.CDMATRFUNCIONARIO} - ${nomeLimpo}`;
                  this.userSearch = this.userSelectedName;
                  console.log(`✅ [CARREGA EDIÇÃO] Funcionário: ${this.userSelectedName}`);
                }
              }
            } catch (e) {
              console.warn(`⚠️ [CARREGA EDIÇÃO] Erro ao carregar funcionário:`, e);
            }
          }

          console.log('✅ [CARREGA EDIÇÃO] Carregamento completo finalizado!');
          console.log('='.repeat(80) + '\n');

        } catch (e) {
          console.error('❌ [CARREGA EDIÇÃO] Erro geral:', e);
        }
      },

      // ========================================
      // Helper function para preencher dados do local
      // Sincroniza Local, nome, e projeto
      // ========================================
      preencherDadosLocal(local) {
        if (!local) return;

        // Preencher o novo sistema de dropdown de Local
        this.codigoLocalDigitado = String(local.cdlocal);
        this.codigoLocalSelecionado = String(local.cdlocal);
        this.nomeLocalBusca = local.LOCAL || local.delocal || '';
        this.nomeLocal = this.nomeLocalBusca;
        this.localNome = this.nomeLocalBusca;
        this.localSelecionadoId = local.id;
        this.formData.CDLOCAL = String(local.id);

        // Carregar projeto se existir
        if (local.CDPROJETO) {
          this.formData.CDPROJETO = local.CDPROJETO;
          if (local.NOMEPROJETO) {
            this.projetoSearch = `${local.CDPROJETO} - ${local.NOMEPROJETO}`;
            this.projetoAssociadoSearch = `${local.CDPROJETO} - ${local.NOMEPROJETO}`;
          }
        }

        console.log('✅ [preencherDadosLocal]', {
          cdlocal: local.cdlocal,
          nome: this.nomeLocalBusca,
          projeto: local.CDPROJETO,
          nomeProjeto: local.NOMEPROJETO
        });
      },

      /**
       * Funções de edição de local
       */
      async carregarProjetosExistentes() {
        try {
          const r = await fetch('/api/projetos/pesquisar?q=');
          if (r.ok) {
            this.projetosExistentes = await r.json();
          } else {
            console.warn('Erro ao carregar projetos existentes:', r.status);
            this.projetosExistentes = [];
          }
        } catch (e) {
          console.error('Erro ao carregar projetos:', e);
          this.projetosExistentes = [];
        }
      },


      abrirModalEditarLocal() {
        if (!this.formData.CDPROJETO) {
          alert('Selecione um projeto primeiro');
          return;
        }

        // Buscar dados do local atual
        const localAtual = this.locais.find(l => String(l.id) === String(this.formData.CDLOCAL));
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
          this.erroEdicao = 'Selecione um projeto';
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

