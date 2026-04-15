<x-app-layout>
  {{-- Abas de navegação do patrimônio --}}
  <x-patrimonio-nav-tabs />

  <div x-data="window.atribuirPage()" x-init="init()" @atribuir-aplicar-filtros.window="aplicarFiltros()" class="py-4">
    <div class="py-3">
      <div class="w-full px-2 sm:px-4 lg:px-6">
        <div class="bg-white dark:bg-gray-900 shadow-lg sm:rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div class="p-4 sm:p-5 space-y-3">
        <!-- Mensagens de Feedback -->
        @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded relative" role="alert">
          <strong class="font-bold">Sucesso!</strong>
          <span class="block sm:inline">{{ session('success') }}</span>
        </div>
        @endif
        @if(session('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded relative" role="alert">
          <strong class="font-bold">Erro!</strong>
          <span class="block sm:inline">{{ session('error') }}</span>
        </div>
        @endif
        @if(session('warning'))
        <div class="mb-4 bg-amber-100 border border-amber-400 text-amber-800 px-4 py-3 rounded relative" role="alert">
          <strong class="font-bold">Atenção!</strong>
          <span class="block sm:inline">{{ session('warning') }}</span>
        </div>
        @endif
        @if($errors->any())
        <div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded relative" role="alert">
          <strong class="font-bold">Erro de validação!</strong>
          <span class="block sm:inline">{{ $errors->first() }}</span>
        </div>
        @endif

            <div class="space-y-6">
              <!-- Filtros alinhados ao Controle de Patrimônio -->
              @php
                $statusAtribuir = request('status', 'disponivel');
                $temFiltroAtribuirAtivo = collect([
                  request('filtro_termo'),
                  request('filtro_numero'),
                  request('filtro_descricao'),
                  request('filtro_modelo'),
                  request('filtro_projeto'),
                  request('filtro_matr_responsavel'),
                  request('filtro_matr_cadastrador'),
                ])->filter(fn ($value) => filled($value))->isNotEmpty();
              @endphp
              <div
                x-data="{
                  open: @js($temFiltroAtribuirAtivo),
                  temFiltroAtivo: @js($temFiltroAtribuirAtivo),
                  textoFiltro: '',
                  focusFirst() {
                    this.open = true;
                    this.$nextTick(() => {
                      const el = this.$refs.firstFilterInput || document.getElementById('filtro_numero');
                      if (el) {
                        el.focus();
                        if (typeof el.select === 'function') {
                          el.select();
                        }
                      }
                    });
                  },
                  handleKey(event) {
                    if (!event || event.defaultPrevented) return;
                    if (event.ctrlKey || event.metaKey || event.altKey) return;
                    if (event.key !== 'f' && event.key !== 'F') return;
                    const target = event.target;
                    const tag = target ? target.tagName : '';
                    if (target && (target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(tag))) return;
                    event.preventDefault();
                    this.focusFirst();
                  },
                  handleEnter(event) {
                    if (!event) return;
                    const target = event.target;
                    const tag = target ? target.tagName : '';
                    if (!target || !['INPUT', 'TEXTAREA', 'SELECT'].includes(tag)) return;
                    event.preventDefault();
                    this.aplicar();
                  },
                  updateFiltroTexto() {
                    const termo = document.getElementById('filtro_termo')?.value || '';
                    const numero = document.getElementById('filtro_numero')?.value || '';
                    const descricao = document.getElementById('filtro_descricao')?.value || '';
                    const modelo = document.getElementById('filtro_modelo')?.value || '';
                    const projeto = document.getElementById('filtro_projeto')?.value || '';
                    const matrResp = document.getElementById('filtro_matr_responsavel')?.value || '';
                    const matrCad = document.getElementById('filtro_matr_cadastrador')?.value || '';

                    const partes = [];
                    if (termo) partes.push('Termo ' + termo);
                    if (numero) partes.push('Patrimônio ' + numero);
                    if (descricao) partes.push('Item ' + descricao);
                    if (modelo) partes.push('Modelo ' + modelo);
                    if (projeto) partes.push('Projeto ' + projeto);
                    if (matrResp) partes.push('Responsável ' + matrResp);
                    if (matrCad) partes.push('Cadastrador ' + matrCad);

                    this.temFiltroAtivo = partes.length > 0;
                    this.textoFiltro = partes.join(' | ');
                  },
                  aplicar() {
                    this.updateFiltroTexto();
                    this.open = false;
                    aplicarFiltros();
                  },
                  limpar() {
                    this.temFiltroAtivo = false;
                    this.textoFiltro = '';
                    this.open = false;
                    limparFiltros();
                  }
                }"
                x-init="updateFiltroTexto()"
                @click.outside="open = false"
                @keydown.window="handleKey($event)"
                @keydown.enter.prevent="handleEnter($event)"
                class="bg-gray-100 dark:bg-gray-800 p-3 rounded-lg mb-3"
                x-id="['filtro-atribuir']"
                :aria-expanded="open.toString()"
                :aria-controls="$id('filtro-atribuir')"
              >
                <div class="flex justify-between items-center gap-3">
                  <div class="flex items-center gap-3 flex-wrap">
                    <h3 class="font-semibold text-lg">Filtros de Busca</h3>
                    <span
                      x-cloak
                      x-show="temFiltroAtivo"
                      class="inline-flex items-center text-xs px-2 py-1 rounded-full border bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-100 border-amber-200 dark:border-amber-700"
                    >
                      Ativo: <span class="ml-1" x-text="textoFiltro"></span>
                    </span>
                  </div>
                  <button
                    type="button"
                    @click="open = !open"
                    :aria-expanded="open.toString()"
                    :aria-controls="$id('filtro-atribuir')"
                    class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-gray-600 dark:border-gray-600 bg-gray-600 dark:bg-gray-800 hover:bg-gray-700 dark:hover:bg-gray-700 text-white transition focus:outline-none focus:ring-2 focus:ring-gray-500"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                    <span class="sr-only">Abrir filtros</span>
                  </button>
                </div>
                <div x-cloak x-show="open" x-transition class="mt-4 bg-gray-200 dark:bg-gray-800 rounded-lg p-4" :id="$id('filtro-atribuir')">
                  <form @submit.prevent="aplicar()">
                    <input type="hidden" name="status" value="{{ $statusAtribuir }}">
                    <div class="flex flex-wrap gap-3 lg:gap-4 overflow-visible pb-2 w-full mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                      @if($statusAtribuir === 'indisponivel')
                        <div class="flex-1 min-w-[100px] max-w-[140px] basis-[110px]">
                          <input
                            type="text"
                            id="filtro_termo"
                            name="filtro_termo"
                            value="{{ request('filtro_termo') }}"
                            placeholder="Nº do termo ou nome"
                            @input="updateFiltroTexto()"
                            x-ref="firstFilterInput"
                            class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-200 rounded-md"
                          />
                        </div>
                      @endif
                      <div class="flex-1 min-w-[100px] max-w-[140px] basis-[110px]">
                        <input
                          type="text"
                          id="filtro_numero"
                          name="filtro_numero"
                          value="{{ request('filtro_numero') }}"
                          placeholder="Nº Patr."
                          @input="updateFiltroTexto()"
                          @if($statusAtribuir !== 'indisponivel') x-ref="firstFilterInput" @endif
                          class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-200 rounded-md"
                        />
                      </div>
                      <div class="flex-1 min-w-[150px] basis-[220px]">
                        <input
                          type="text"
                          id="filtro_descricao"
                          name="filtro_descricao"
                          value="{{ request('filtro_descricao') }}"
                          placeholder="Item"
                          @input="updateFiltroTexto()"
                          class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-200 rounded-md"
                        />
                      </div>
                      <div class="flex-1 min-w-[140px] basis-[180px]">
                        <input
                          type="text"
                          id="filtro_modelo"
                          name="filtro_modelo"
                          value="{{ request('filtro_modelo') }}"
                          placeholder="Modelo"
                          @input="updateFiltroTexto()"
                          class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-200 rounded-md"
                        />
                      </div>
                      <div class="flex-1 min-w-[120px] max-w-[170px] basis-[130px]">
                        <input
                          type="number"
                          id="filtro_projeto"
                          name="filtro_projeto"
                          value="{{ request('filtro_projeto') }}"
                          placeholder="Cód. Projeto"
                          @input="updateFiltroTexto()"
                          class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-200 rounded-md"
                        />
                      </div>
                      <div class="flex-1 min-w-[220px] basis-[260px]">
                        <x-employee-autocomplete
                          id="filtro_matr_responsavel"
                          name="filtro_matr_responsavel"
                          placeholder="Responsável"
                          value="{{ request('filtro_matr_responsavel') }}"
                        />
                      </div>
                      <div class="flex-1 min-w-[220px] basis-[260px]">
                        <x-employee-autocomplete
                          id="filtro_matr_cadastrador"
                          name="filtro_matr_cadastrador"
                          placeholder="Cadastrador"
                          value="{{ request('filtro_matr_cadastrador') }}"
                        />
                      </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-3 mt-4">
                      <div class="flex items-center gap-3">
                        <button type="submit" class="btn-accent h-10">Filtrar</button>
                        <button
                          type="button"
                          @click="limpar()"
                          class="h-10 px-4 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                        >
                          Limpar
                        </button>
                      </div>
                      <div class="flex items-center gap-3 ml-auto">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Itens por página</span>
                        <select
                          id="per_page"
                          name="per_page"
                          @change="updateFiltroTexto(); aplicar()"
                          class="h-10 px-2 pr-8 w-24 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-200 rounded-md shadow-sm"
                        >
                          @foreach([30,50,100,200] as $opt)
                            <option value="{{ $opt }}" @selected(request('per_page', 30) == $opt)>{{ $opt }}</option>
                          @endforeach
                        </select>
                      </div>
                    </div>
                  </form>
                </div>
              </div>

              <div class="flex flex-wrap items-center mb-4 gap-3 w-full" x-data="{}">
                <div class="flex flex-wrap items-center gap-3">
                  <a href="{{ route('patrimonios.atribuir.codigos', ['status' => 'disponivel']) }}" class="text-xs px-4 py-2.5 rounded-xl font-semibold border shadow-sm transition {{ request('status', 'disponivel') == 'disponivel' ? 'bg-green-600 text-white border-green-600' : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-700 hover:bg-green-600/10 dark:hover:bg-green-600/10' }}">Disponíveis</a>
                  <a href="{{ route('patrimonios.atribuir.codigos', ['status' => 'indisponivel']) }}" class="text-xs px-4 py-2.5 rounded-xl font-semibold border shadow-sm transition {{ request('status') == 'indisponivel' ? 'bg-red-600 text-white border-red-600' : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-700 hover:bg-red-600/10 dark:hover:bg-red-600/10' }}">Atribuídos</a>
                </div>

                <div class="flex-1 min-w-[1rem]"></div>
                @if(request('status') == 'indisponivel')
                  <button
                    type="button"
                    @click="$dispatch('abrir-termo-responsabilidade-massa', { projeto: document.getElementById('filtro_projeto')?.value || '' })"
                    class="bg-slate-700 hover:bg-slate-800 text-white font-semibold py-2.5 px-4 rounded-xl inline-flex items-center shadow whitespace-nowrap"
                  >
                    <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.75h6l4.5 4.5v11.25A2.25 2.25 0 0 1 15.75 21h-8.25A2.25 2.25 0 0 1 5.25 18.75V6A2.25 2.25 0 0 1 7.5 3.75Z" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 3.75V9h4.5" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 13.5h7.5M8.25 17.25h4.5" />
                    </svg>
                    Termos em lote
                  </button>
                @endif
                <template x-if="selectedPatrimonios.length > 0">
                  <span id="contador-selecionados-tabs" class="text-[11px] text-muted" x-text="contadorTexto"></span>
                </template>
              </div>

              <!-- Forms para geração e atribuição direta de códigos -->
              <form id="form-gerar-codigo" method="POST" action="{{ route('patrimonios.gerarCodigo') }}" class="hidden">
                @csrf
              </form>
              <form id="form-atribuir-codigo" method="POST" action="{{ route('patrimonios.atribuirCodigo') }}" class="hidden">
                @csrf
                <input type="hidden" name="codigo" x-model="codigoTermo">
              </form>
            </div>

            <!-- Tabela (estrutura idêntica ao index) -->
            <div id="atribuir-grid-container">
            <div class="relative overflow-x-auto shadow-md sm:rounded-lg z-0">
              {{-- MODO DISPONÍVEIS: Usa componente reutilizável --}}
              @if(!request('status') || request('status')=='disponivel')
              @php
                // Colunas simplificadas para evitar confusão com a tela principal
                $colsDisponiveis = ['nupatrimonio','projeto','local','descricao','situacao','responsavel','cadastrador'];
              @endphp
              <x-patrimonio-table 
                :patrimonios="$patrimonios"
                :columns="$colsDisponiveis"
                :show-checkbox="true"
                :show-actions="false"
                :clickable="false"
                on-checkbox-change="handleCheckboxChange($event)"
                on-select-all-change="toggleAll($event)"
                checkbox-class="patrimonio-checkbox"
                empty-message="Nenhum patrimônio encontrado. Não há patrimônios disponíveis para atribuição ou nenhum atende aos filtros aplicados."
              />
              {{-- MODO ATRIBUÍDOS: Mantém estrutura original agrupada --}}
              @else
              <table class="w-full text-base text-left text-gray-500 dark:text-gray-400">
                <tbody>
                  @forelse($patrimonios_grouped as $grupo_codigo => $grupo_patrimonios)
                  @php
                  $grupo_id = 'grupo_' . ($grupo_codigo === '__sem_termo__' ? 'sem_termo' : $grupo_codigo);
                  $item_count = $grupo_patrimonios->count();
                  $is_sem_termo = $grupo_codigo === '__sem_termo__';

                  // Agrupar por DEPATRIMONIO + MODELO para mostrar quantidade
                  $grupo_patrimonios_agrupado = $grupo_patrimonios->groupBy(function($item) {
                  return $item->DEPATRIMONIO . '|' . ($item->MODELO ?? '');
                  })->map(function($items) {
                  return [
                  'quantidade' => $items->count(),
                  'items' => $items,
                  'primeiro' => $items->first()
                  ];
                  });
                  $termoMeta = !$is_sem_termo ? ($termosMetadados[(string) $grupo_codigo] ?? ['titulo' => null, 'pode_editar' => false]) : null;
                  $tituloPersonalizado = trim((string) data_get($termoMeta, 'titulo', ''));
                  $adminPodeEditarTitulo = auth()->check() && (auth()->user()->isGod() || auth()->user()->isAdmin());
                  $podeEditarTitulo = $adminPodeEditarTitulo || (bool) data_get($termoMeta, 'pode_editar', false);
                  @endphp

                  {{-- Cabeçalho Colapsável do Grupo --}}
                  <tr class="group-header border-b-2 border-gray-200 dark:border-gray-700 transition cursor-pointer bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 shadow-sm"
                    data-group-id="{{ $grupo_id }}"
                    @click="toggleGroup(@js($grupo_id))"
                    :data-expanded="groupState[@js($grupo_id)] === true ? 'true' : 'false'">
                    <td colspan="5" class="px-4 py-4 bg-white dark:bg-gray-800 border-l-4 border-indigo-400 dark:border-indigo-400">
                      <div class="flex items-center justify-between gap-4">
                        {{-- Ícone de Abrir + Info do Grupo --}}
                        <div class="flex items-center gap-4 flex-1 min-w-0">
                          <button type="button"
                            class="flex-shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-md border-2 border-indigo-400 dark:border-indigo-400 bg-white dark:bg-gray-800 hover:bg-indigo-50 dark:hover:bg-indigo-700 transition transform"
                            :class="{ 'rotate-180': groupState[@js($grupo_id)] === true }"
                            @click.stop="toggleGroup(@js($grupo_id))">
                            <svg class="w-4 h-4 text-indigo-400 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                          </button>

                          <div class="flex items-start gap-3 flex-1 min-w-0">
                            @if(!$is_sem_termo)
                            <div class="flex flex-col gap-2 min-w-0">
                              <div class="flex items-center gap-2 min-w-0">
                                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border-2 border-gray-400 dark:border-gray-600 flex-shrink-0 max-w-full">
                                  <span class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate" title="Termo {{ $grupo_codigo }}">Termo {{ $grupo_codigo }}</span>
                                  <span
                                    x-show="getTituloAgrupado(@js((string) $grupo_codigo)) !== ''"
                                    x-text="getTituloAgrupado(@js((string) $grupo_codigo))"
                                    style="display: none;"
                                    class="text-xs font-semibold text-indigo-600 dark:text-indigo-300 truncate max-w-[14rem]">
                                  </span>
                                </span>
                                @if(false && $podeEditarTitulo)
                                <button type="button"
                                  @click.stop="abrirEditorTitulo(@js((string) $grupo_codigo), getTituloAgrupado(@js((string) $grupo_codigo)))"
                                  class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-indigo-200 dark:border-indigo-700 text-indigo-600 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition flex-shrink-0"
                                  title="Editar título do termo">
                                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L12 15l-4 1 1-4 9.586-9.586z"></path>
                                  </svg>
                                </button>
                                @endif
                              </div>
                              @if($podeEditarTitulo)
                              <div x-show="editingTermoCodigo === @js((string) $grupo_codigo)"
                                x-transition
                                @click.stop
                                style="display: none;"
                                class="flex flex-wrap items-center gap-2">
                                <input type="text"
                                  id="titulo-termo-{{ $grupo_codigo }}"
                                  x-model="editingTitulo"
                                  @keydown.enter.prevent="salvarTituloTermo(@js((string) $grupo_codigo))"
                                  maxlength="120"
                                  placeholder="Digite um nome para identificar este agrupado"
                                  class="w-full max-w-md px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <button type="button"
                                  @click.stop="salvarTituloTermo(@js((string) $grupo_codigo))"
                                  :disabled="salvandoTitulo"
                                  class="inline-flex items-center px-3 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-60 disabled:cursor-not-allowed transition">
                                  <span x-text="salvandoTitulo ? 'Salvando...' : 'Salvar nome'"></span>
                                </button>
                                <button type="button"
                                  @click.stop="cancelarEdicaoTitulo()"
                                  class="inline-flex items-center px-3 py-2 text-xs font-semibold text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                  Cancelar
                                </button>
                              </div>
                              @endif
                            </div>
                            @else
                            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-white dark:bg-gray-800 border-2 border-amber-300 dark:border-amber-400 flex-shrink-0">
                              <span class="text-sm font-semibold text-amber-900 dark:text-amber-200">Sem Termo</span>
                            </span>
                            @endif

                            {{-- Lista de itens como badges individuais --}}
                            @php
                              $itensAgrupados = $grupo_patrimonios->pluck('DEPATRIMONIO')->filter()->unique()->values();
                            @endphp
                            <div class="flex items-center gap-2 flex-1 min-w-0 max-w-[26rem] sm:max-w-[28rem] lg:max-w-[32rem] xl:max-w-[36rem] 2xl:max-w-[42rem] overflow-hidden">
                              <span x-show="getTituloAgrupado(@js((string) $grupo_codigo)) !== ''"
                                style="display: none;"
                                class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-200 border border-indigo-200 dark:border-indigo-700 whitespace-nowrap max-w-full min-w-0">
                                <span class="truncate"
                                  :title="getTituloAgrupado(@js((string) $grupo_codigo))"
                                  x-text="getTituloAgrupado(@js((string) $grupo_codigo))"></span>
                              </span>
                              @if($podeEditarTitulo)
                              <button type="button"
                                @click.stop="abrirEditorTitulo(@js((string) $grupo_codigo), getTituloAgrupado(@js((string) $grupo_codigo)))"
                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-indigo-200 dark:border-indigo-700 text-indigo-600 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition flex-shrink-0"
                                title="Editar nome do agrupado">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L12 15l-4 1 1-4 9.586-9.586z"></path>
                                </svg>
                              </button>
                              @endif
                              <div x-show="getTituloAgrupado(@js((string) $grupo_codigo)) === ''"
                                class="flex items-center gap-2 min-w-0 overflow-hidden"
                                style="display: none;">
                                @foreach($itensAgrupados->take(2) as $item)
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border border-gray-300 dark:border-gray-600 whitespace-nowrap min-w-0 max-w-[10rem] sm:max-w-[12rem] lg:max-w-[14rem] xl:max-w-[15rem]">
                                  <span class="truncate" title="{{ $item }}">{{ $item }}</span>
                                </span>
                                @endforeach
                                @if($itensAgrupados->count() > 2)
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border border-gray-300 dark:border-gray-600 whitespace-nowrap flex-shrink-0">
                                  +{{ $itensAgrupados->count() - 2 }} mais
                                </span>
                                @endif
                              </div>
                            </div>
                          </div>
                        </div>

                        {{-- Botões de Ação (Baixar e Desatribuir) --}}
                        <div class="flex-shrink-0 flex gap-2 items-center">
                          {{-- Badge de Quantidade --}}
                          <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600">
                            <span class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $item_count }}</span>
                            <span class="text-xs font-semibold text-gray-900 dark:text-gray-100">{{ $item_count === 1 ? 'item' : 'itens' }}</span>
                          </span>

                          @if(!$is_sem_termo)
                          {{-- Botão Baixar Documento Termo (Word - Azul Office) --}}
                          <button type="button"
                            @click.stop="adicionarPatrimoniosAoTermo(@js((string) $grupo_codigo))"
                            class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-blue-600 dark:text-blue-400 bg-white dark:bg-gray-800 rounded-lg border-2 border-blue-600 dark:border-blue-600 hover:bg-blue-50 dark:hover:bg-blue-700 transition whitespace-nowrap"
                            title="Adicionar novos patrimônios a este termo">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <span>Adicionar patrimônio</span>
                          </button>

                          <button type="button"
                            @click.stop="downloadTermoGrupo([{{ $grupo_patrimonios->pluck('NUSEQPATR')->join(',') }}])"
                            class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-blue-600 dark:text-blue-400 bg-white dark:bg-gray-800 rounded-lg border-2 border-blue-600 dark:border-blue-600 hover:bg-blue-50 dark:hover:bg-blue-700 transition whitespace-nowrap"
                            title="Baixar documento de termo com todos os itens">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span>Baixar Documento Termo</span>
                          </button>

                          {{-- Botão Baixar Planilha Termo (Excel - Verde Office) --}}
                          <button type="button"
                            @click.stop="downloadPlanilhaTermo([{{ $grupo_patrimonios->pluck('NUSEQPATR')->join(',') }}], @js($grupo_codigo))"
                            class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-green-600 dark:text-green-400 bg-white dark:bg-gray-800 rounded-lg border-2 border-green-600 dark:border-green-600 hover:bg-green-50 dark:hover:bg-green-700/10 transition whitespace-nowrap"
                            title="Baixar planilha com todos os itens do termo">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span>Baixar Planilha Termo</span>
                          </button>

                          {{-- Botão Desatribuir Termo (Vermelho) --}}
                          <button type="button"
                            @click.stop="desatribuirGrupo([{{ $grupo_patrimonios->pluck('NUSEQPATR')->join(',') }}])"
                            class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 bg-white dark:bg-gray-800 rounded-lg border-2 border-red-600 dark:border-red-600 hover:bg-red-50 dark:hover:bg-red-700/10 transition whitespace-nowrap"
                            title="Desatribuir todos os itens deste termo">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            <span>Desatribuir Termo</span>
                          </button>
                          @endif
                        </div>
                      </div>
                    </td>
                  </tr>

                  {{-- Header do Grupo (Colunas) --}}
                  <tr class="text-xs text-gray-700 dark:text-gray-100 uppercase bg-gray-100 dark:bg-gray-700 border-b border-gray-300 dark:border-gray-600"
                    x-show="groupState[@js($grupo_id)] === true"
                    style="display: none;">
                    <th class="px-4 py-3">
                      @if(!request('status') || request('status')=='disponivel')
                      <input type="checkbox" class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-600" @change="toggleGroupCheckboxes(@js($grupo_id), $event)">
                      @endif
                    </th>
                    <th class="px-4 py-3">Nº Pat.</th>
                    <th class="px-4 py-3">Itens</th>
                    <th class="px-4 py-3">Modelo</th>
                    <th class="px-4 py-3" colspan="2">Qntd</th>
                  </tr>

                  {{-- Detalhes do Grupo (Linhas dos Itens Agrupados por Descrio+Modelo) --}}
                  @foreach($grupo_patrimonios_agrupado as $grupo_id_item => $grupo_dados)
                  <tr class="group-details border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/30 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                    data-group-id="{{ $grupo_id }}"
                    data-row-id="{{ $grupo_dados['primeiro']->NUSEQPATR }}"
                    x-show="groupState[@js($grupo_id)] === true"
                    style="display: none;">
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                      <div class="flex items-center justify-center">
                        <input class="grupo-item-checkbox h-4 w-4 rounded border-gray-400 dark:border-gray-400 text-indigo-600 focus:ring-indigo-600"
                          type="checkbox" data-grupo-id="{{ $grupo_id }}" value="{{ $grupo_dados['primeiro']->NUSEQPATR }}" @change="updateGroupSelection(@js($grupo_id))">
                      </div>
                    </td>
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">
                      {{ $grupo_dados['primeiro']->NUPATRIMONIO }}
                    </td>
                    @php
                      $primeiro = $grupo_dados['primeiro'];
                            $descRaw = trim((string) ($primeiro->DEPATRIMONIO ?? ''));
                      $descAscii = strtoupper(Str::ascii($descRaw));
                      $descUpper = strtoupper($descRaw);
                      // Captura casos acentuados e casos corrompidos (SEM DESCRIO etc)
                      $semDescricao = $descRaw === '' ||
                        str_contains($descAscii, 'SEM DESCRICAO') ||
                        str_contains($descUpper, 'SEM DESCRI') ||
                        str_contains($descRaw, 'SEM DESCRI');
                      $fallback = $primeiro->MARCA ?: ($primeiro->MODELO ?: '-');
                      $displayDesc = $semDescricao ? $fallback : $descRaw;
                    @endphp
                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300 max-w-xs truncate" :title="@js($displayDesc)">
                      {{ Str::limit($displayDesc, 50) }}
                    </td>
                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                      {{ $grupo_dados['primeiro']->MODELO ?? '-' }}
                    </td>
                    <td class="px-4 py-3" colspan="2">
                      <div class="flex items-center justify-between gap-3">
                        <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-100 text-xs font-bold min-w-fit">
                          {{ $grupo_dados['quantidade'] }}
                        </span>
                      </div>
                    </td>
                  </tr>
                  @endforeach
                  @empty
                  <tr>
                    <td colspan="5" class="px-6 py-12 text-center">
                      <div class="flex flex-col items-center justify-center text-gray-600 dark:text-gray-400">
                        <svg class="w-12 h-12 mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                        </svg>
                        <h3 class="text-base font-semibold mb-1">Nenhum patrimônio encontrado</h3>
                        <p class="text-sm">
                          @if(request('status') == 'indisponivel')
                          Não há patrimônios atribuídos ou nenhum atende aos filtros aplicados.
                          @else
                          Não há patrimônios disponíveis para atribuição ou nenhum atende aos filtros aplicados.
                          @endif
                        </p>
                      </div>
                    </td>
                  </tr>
                  @endforelse
                </tbody>
              </table>
              @endif
            </div>
            <div class="mt-4" id="atribuir-pagination">
              {{ $patrimonios->appends(request()->query())->links() }}
            </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    window.createTermoResponsabilidadeModal = window.createTermoResponsabilidadeModal || function createTermoResponsabilidadeModal(config) {
      return {
        massPdfModalOpen: false,
        massPdfResumoOpen: false,
        massPdfProjeto: config.projetoInicial || '',
        massPdfProjetoSelecionado: config.projetoInicial || '',
        massPdfProjetoResultados: [],
        massPdfProjetoDropdownOpen: false,
        massPdfProjetoLoading: false,
        massPdfBaixando: false,
        massPdfSucesso: '',
        massPdfProjetoErro: '',
        massPdfProjetoHighlight: -1,
        massPdfProjetoRequest: 0,
        massPdfProgresso: 0,
        massPdfEtapa: 'Aguardando envio...',
        massPdfResumo: {
          termos: 0,
          itens: 0,
          projetoCodigo: '',
          projetoNome: '',
          formato: 'ZIP'
        },
        massPdfTimer: null,
        massPdfDownloadUrl: '',
        massPdfDownloadNome: '',
        massPdfDownloadJaClicado: false,
        massPdfFraseIndex: 0,
        massPdfFrases: [
          'Validando dados do projeto...',
          'Buscando funcionários e patrimônios...',
          'Carregando template de termo...',
          'Gerando DOCX por funcionário...',
          'Processando responsabilidades...',
          'Preenchendo dados nos documentos...',
          'Montando arquivos no servidor...',
          'Organizando lote de documentos...',
          'Validando integridade dos arquivos...',
          'Compactando em arquivo ZIP...',
          'Finalizando processo...',
          'Preparando para download...',
          'Falta pouco, muitos dados...'
        ],
        resetarFeedbackTermoMassa() {
          this.massPdfProjetoErro = '';
          this.massPdfSucesso = '';
        },
        resetarResumoTermoMassa() {
          this.massPdfResumoOpen = false;
          if (this.massPdfDownloadUrl) {
            window.URL.revokeObjectURL(this.massPdfDownloadUrl);
          }
          this.massPdfDownloadUrl = '';
          this.massPdfDownloadNome = '';
          this.massPdfDownloadJaClicado = false;
          this.massPdfFraseIndex = 0;
          this.massPdfResumo = {
            termos: 0,
            itens: 0,
            projetoCodigo: '',
            projetoNome: '',
            formato: 'ZIP'
          };
        },
        fecharModalTermoMassa() {
          if (this.massPdfBaixando) return;
          this.massPdfModalOpen = false;
          this.massPdfProjetoDropdownOpen = false;
          this.resetarFeedbackTermoMassa();
        },
        fecharResumoTermoMassa() {
          this.massPdfResumoOpen = false;
          this.massPdfModalOpen = false;
          this.resetarFeedbackTermoMassa();
        },
        obterNomeArquivo(response, fallback) {
          const disposition = response.headers.get('content-disposition') || '';
          const partes = disposition.split(';').map((parte) => parte.trim()).filter(Boolean);
          const utf8Parte = partes.find((parte) => parte.toLowerCase().indexOf("filename*=utf-8''") === 0);
          if (utf8Parte) {
            const valor = utf8Parte.split("''").slice(1).join("''");
            return decodeURIComponent(valor || fallback);
          }
          const nomeParte = partes.find((parte) => parte.toLowerCase().indexOf('filename=') === 0);
          if (!nomeParte) return fallback;
          const bruto = nomeParte.substring(9).trim();
          return bruto.replace(/^"+|"+$/g, '') || fallback;
        },
        iniciarProgressoTermoMassa() {
          this.massPdfFraseIndex = 0;
          clearInterval(this.massPdfTimer);
          this.massPdfTimer = setInterval(() => {
            if (this.massPdfFraseIndex === this.massPdfFrases.length - 1) {
              this.massPdfFraseIndex = this.massPdfFrases.length - 2;
            } else {
              this.massPdfFraseIndex = (this.massPdfFraseIndex + 1) % this.massPdfFrases.length;
            }
          }, 3000);
        },
        finalizarProgressoTermoMassa() {
          clearInterval(this.massPdfTimer);
          this.massPdfTimer = null;
        },
        async baixarArquivoTermoMassa(form) {
          const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
            headers: {
              Accept: 'application/octet-stream',
              'X-Requested-With': 'XMLHttpRequest'
            }
          });

          if (!response.ok) {
            let mensagem = 'Não foi possível gerar o lote.';
            const contentType = response.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
              const data = await response.json().catch(() => null);
              if (data && data.message) mensagem = data.message;
            } else {
              const texto = await response.text().catch(() => '');
              if (texto.includes('Projeto não encontrado')) mensagem = 'Projeto não encontrado para gerar os termos.';
              if (texto.includes('Nenhum patrimônio com responsável')) mensagem = 'Nenhum patrimônio com responsável foi encontrado nesse projeto.';
            }
            throw new Error(mensagem);
          }

          const blob = await response.blob();
          const nomeArquivo = this.obterNomeArquivo(response, 'termos_responsabilidade.zip');
          if (this.massPdfDownloadUrl) {
            window.URL.revokeObjectURL(this.massPdfDownloadUrl);
          }
          this.massPdfDownloadUrl = window.URL.createObjectURL(blob);
          this.massPdfDownloadNome = nomeArquivo;

          this.massPdfResumo = {
            termos: Number(response.headers.get('X-Termos-Gerados') || 0),
            itens: Number(response.headers.get('X-Itens-Gerados') || 0),
            projetoCodigo: String(response.headers.get('X-Projeto-Codigo') || this.massPdfProjetoSelecionado || ''),
            projetoNome: String(response.headers.get('X-Projeto-Nome') || ''),
            formato: String(response.headers.get('X-Pacote-Formato') || 'ZIP').toUpperCase()
          };
        },
        executarDownloadTermo() {
          if (!this.massPdfDownloadUrl) return;
          this.massPdfDownloadJaClicado = true;
          const link = document.createElement('a');
          link.href = this.massPdfDownloadUrl;
          link.download = this.massPdfDownloadNome;
          document.body.appendChild(link);
          link.click();
          link.remove();
        },
        formatarProjetoOpcao(projeto) {
          return `${projeto.CDPROJETO} - ${projeto.NOMEPROJETO}`;
        },
        async buscarProjetosTermo(termo) {
          const consulta = String(termo || this.massPdfProjeto || '').trim();
          const requestId = ++this.massPdfProjetoRequest;
          this.massPdfProjetoLoading = true;
          try {
            const resp = await fetch(`/api/projetos/pesquisar?q=${encodeURIComponent(consulta)}`, {
              headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
              credentials: 'same-origin'
            });
            if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
            const dados = await resp.json();
            if (requestId !== this.massPdfProjetoRequest) return [];
            this.massPdfProjetoResultados = Array.isArray(dados) ? dados : [];
            this.massPdfProjetoHighlight = this.massPdfProjetoResultados.length > 0 ? 0 : -1;
            return this.massPdfProjetoResultados;
          } catch (_) {
            if (requestId === this.massPdfProjetoRequest) {
              this.massPdfProjetoResultados = [];
              this.massPdfProjetoHighlight = -1;
            }
            return [];
          } finally {
            if (requestId === this.massPdfProjetoRequest) this.massPdfProjetoLoading = false;
          }
        },
        async preencherProjetoTermo(codigoProjeto) {
          const codigo = String(codigoProjeto || '').trim();
          if (!codigo) {
            this.massPdfProjeto = '';
            this.massPdfProjetoSelecionado = '';
            return;
          }
          const resultados = await this.buscarProjetosTermo(codigo);
          const exato = resultados.find((projeto) => String(projeto.CDPROJETO) === codigo);
          if (exato) {
            this.selecionarProjetoTermo(exato);
            return;
          }
          this.massPdfProjeto = codigo;
          this.massPdfProjetoSelecionado = codigo;
        },
        selecionarProjetoTermo(projeto) {
          this.massPdfProjetoSelecionado = String(projeto.CDPROJETO || '').trim();
          this.massPdfProjeto = this.formatarProjetoOpcao(projeto);
          this.massPdfProjetoDropdownOpen = false;
          this.massPdfProjetoErro = '';
        },
        limparProjetoTermo() {
          this.massPdfProjeto = '';
          this.massPdfProjetoSelecionado = '';
          this.massPdfProjetoResultados = [];
          this.massPdfProjetoDropdownOpen = false;
          this.massPdfProjetoHighlight = -1;
          this.resetarFeedbackTermoMassa();
        },
        async abrirProjetoTermo(force) {
          this.massPdfProjetoDropdownOpen = true;
          if (force || this.massPdfProjetoResultados.length === 0 || String(this.massPdfProjeto || '').trim() !== '') {
            await this.buscarProjetosTermo(this.massPdfProjeto);
          }
        },
        moverProjetoTermo(delta) {
          if (!this.massPdfProjetoResultados.length) return;
          const total = this.massPdfProjetoResultados.length;
          this.massPdfProjetoHighlight = (this.massPdfProjetoHighlight + delta + total) % total;
        },
        confirmarProjetoTermoHighlight() {
          if (this.massPdfProjetoHighlight < 0 || this.massPdfProjetoHighlight >= this.massPdfProjetoResultados.length) return;
          this.selecionarProjetoTermo(this.massPdfProjetoResultados[this.massPdfProjetoHighlight]);
        },
        async garantirProjetoTermoSelecionado() {
          const termo = String(this.massPdfProjeto || '').trim();
          if (!termo && !this.massPdfProjetoSelecionado) {
            this.massPdfProjetoErro = 'Selecione um projeto para gerar o lote.';
            return false;
          }
          if (this.massPdfProjetoSelecionado) return true;
          const resultados = await this.buscarProjetosTermo(termo);
          if (resultados.length === 0) {
            this.massPdfProjetoErro = 'Projeto não encontrado.';
            return false;
          }
          const termoLower = termo.toLowerCase();
          const exato = resultados.find((projeto) => String(projeto.CDPROJETO) === termo || this.formatarProjetoOpcao(projeto).toLowerCase() === termoLower);
          this.selecionarProjetoTermo(exato || resultados[0]);
          return Boolean(this.massPdfProjetoSelecionado);
        },
        async submitTermoMassa(event) {
          event.preventDefault();
          if (this.massPdfBaixando) return;
          this.resetarFeedbackTermoMassa();
          this.resetarResumoTermoMassa();
          const ok = await this.garantirProjetoTermoSelecionado();
          if (!ok) {
            this.massPdfProjetoDropdownOpen = true;
            return;
          }
          this.massPdfBaixando = true;
          this.iniciarProgressoTermoMassa();
          try {
            await this.baixarArquivoTermoMassa(event.target);
            this.finalizarProgressoTermoMassa();
            this.massPdfResumoOpen = true;
          } catch (error) {
            clearInterval(this.massPdfTimer);
            this.massPdfTimer = null;
            this.massPdfProgresso = 0;
            this.massPdfEtapa = 'Aguardando envio...';
            this.massPdfProjetoErro = error && error.message ? error.message : 'Não foi possível gerar o lote.';
          } finally {
            this.massPdfBaixando = false;
          }
        }
      };
    };
  </script>

  <div
    x-data="window.createTermoResponsabilidadeModal({ projetoInicial: @js((string) request('filtro_projeto', '')) })"
    @abrir-termo-responsabilidade-massa.window="
      limparProjetoTermo();
      resetarFeedbackTermoMassa();
      resetarResumoTermoMassa();
      massPdfBaixando = false;
      massPdfDownloadUrl = '';
      massPdfDownloadNome = '';
      massPdfDownloadJaClicado = false;
      massPdfFraseIndex = 0;
      massPdfModalOpen = true;
      $nextTick(async () => {
        await preencherProjetoTermo($event.detail?.projeto || '');
        document.getElementById('mass_pdf_cdprojeto_autocomplete')?.focus();
      });
    "
    @keydown.escape.window="if (!massPdfResumoOpen) fecharModalTermoMassa()"
    x-cloak
  >
    <div x-show="massPdfModalOpen" class="fixed inset-0 z-[60] overflow-y-auto" aria-labelledby="modal-termo-massa-autocomplete" role="dialog" aria-modal="true">
      <div class="flex min-h-screen items-center justify-center px-4 py-6">
        <div x-show="massPdfModalOpen" x-transition.opacity class="fixed inset-0 bg-black/50" @click="fecharModalTermoMassa()"></div>
        <div x-show="massPdfModalOpen" x-transition class="relative z-10 w-full max-w-lg rounded-xl bg-white dark:bg-gray-800 shadow-2xl border border-gray-200 dark:border-gray-700">

          {{-- Overlay de loading igual ao do modal de relatório --}}
          <div x-show="massPdfBaixando" class="absolute inset-0 bg-white/70 dark:bg-gray-800/70 rounded-xl backdrop-blur-sm flex items-center justify-center z-40">
            <div class="flex flex-col items-center gap-4">
              <svg class="animate-spin h-12 w-12 text-indigo-600 dark:text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
              </svg>
              <div class="text-center">
                <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">Gerando Termos</p>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1" x-text="massPdfFrases[massPdfFraseIndex]"></p>
              </div>
            </div>
          </div>
          <form method="POST" action="{{ route('termos.responsabilidade.massa.docx') }}" @submit="submitTermoMassa($event)">
            @csrf
            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
              <div class="flex items-start gap-2">
                <div class="flex-1">
                  <h3 id="modal-termo-massa-autocomplete" class="text-lg font-semibold text-gray-900 dark:text-gray-100">Gerar termos em massa</h3>
                  <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Gera um termo DOCX para cada responsável com os patrimônios assinados. Baixe todos em um único ZIP.</p>
                </div>
                <div class="relative group shrink-0 flex flex-col items-center justify-center gap-1.5">
                  {{-- Texto animado piscando apontando pro ícone --}}
                  <span class="flex flex-col items-center gap-0.5 text-xs text-indigo-500 dark:text-indigo-400 animate-pulse select-none pointer-events-none">
                    <span>Saiba mais</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                    </svg>
                  </span>
                  <button type="button" class="w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 flex items-center justify-center text-gray-600 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition" tabindex="-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                  </button>
                  <div class="absolute right-0 z-50 w-[52rem] max-w-[min(calc(100vw-2rem),52rem)] bottom-full mb-6 rounded-2xl border border-indigo-400/20 bg-gradient-to-br from-slate-950 via-gray-900 to-slate-950 text-white shadow-[0_18px_50px_rgba(0,0,0,0.45)] opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 pointer-events-none overflow-hidden sm:right-0 max-sm:left-1/2 max-sm:right-auto max-sm:w-[calc(100vw-1.5rem)] max-sm:-translate-x-1/2">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(99,102,241,0.18),_transparent_45%)]"></div>
                    <div class="relative space-y-4 p-5">
                      {{-- O QUÊ É --}}
                      <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-indigo-300 mb-2">O que é?</p>
                        <p class="text-sm text-slate-200 leading-6">Ferramenta para gerar automaticamente termos de responsabilidade em formato DOCX para múltiplos funcionários de um projeto em uma única operação.</p>
                      </div>

                      {{-- PARA QUE SERVE --}}
                      <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-indigo-300 mb-2">Para que serve?</p>
                        <p class="text-sm text-slate-200 leading-6">Agiliza o processo de assinatura e documentação de patrimônios. Cria um documento legal individual para cada responsável com a lista de bens sob sua guarda.</p>
                      </div>

                      {{-- COMO USAR --}}
                      <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-indigo-300 mb-3">Como usar?</p>
                        <div class="grid gap-3 text-slate-200 md:grid-cols-2 xl:grid-cols-4">
                          <div class="flex gap-3 rounded-lg bg-black/10 px-3 py-2">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-500/20 text-xs font-semibold text-indigo-200">1</span>
                            <span class="text-sm leading-5">Selecione o projeto desejado</span>
                          </div>
                          <div class="flex gap-3 rounded-lg bg-black/10 px-3 py-2">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-500/20 text-xs font-semibold text-indigo-200">2</span>
                            <span class="text-sm leading-5">Sistema gera um DOCX por responsável com patrimônios vinculados</span>
                          </div>
                          <div class="flex gap-3 rounded-lg bg-black/10 px-3 py-2">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-500/20 text-xs font-semibold text-indigo-200">3</span>
                            <span class="text-sm leading-5">Compacta todos em arquivo ZIP</span>
                          </div>
                          <div class="flex gap-3 rounded-lg bg-black/10 px-3 py-2">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-500/20 text-xs font-semibold text-indigo-200">4</span>
                            <span class="text-sm leading-5">Baixe e distribua aos responsáveis</span>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="absolute right-6 top-full h-4 w-4 -translate-y-1/2 rotate-45 border-r border-b border-indigo-400/20 bg-slate-950 max-sm:left-1/2 max-sm:right-auto max-sm:-translate-x-1/2"></div>
                  </div>
                </div>
              </div>
            </div>
            <div class="px-6 py-5 space-y-4">
              <div @click.away="massPdfProjetoDropdownOpen = false">
                <label for="mass_pdf_cdprojeto_autocomplete" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Projeto</label>
                <input type="hidden" name="cdprojeto" :value="massPdfProjetoSelecionado" />
                <div class="relative mt-1">
                  <input id="mass_pdf_cdprojeto_autocomplete" type="text" x-model.trim="massPdfProjeto" @input.debounce.250ms="massPdfProjetoSelecionado = ''; massPdfProjetoErro = ''; if (String(massPdfProjeto || '').trim().length > 0) { abrirProjetoTermo(true); }" @keydown.arrow-down.prevent="moverProjetoTermo(1)" @keydown.arrow-up.prevent="moverProjetoTermo(-1)" @keydown.enter.prevent="confirmarProjetoTermoHighlight()" @keydown.escape.prevent="massPdfProjetoDropdownOpen = false" autocomplete="off" required placeholder="Digite o código ou nome do projeto" class="h-11 px-3 pr-20 w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-md" />
                  <div class="absolute inset-y-0 right-0 flex items-center pr-3 gap-2">
                    <button type="button" x-show="massPdfProjeto" @click="limparProjetoTermo()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none text-lg leading-none" title="Limpar seleção" tabindex="-1">×</button>
                    <button type="button" @click="abrirProjetoTermo(true)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 focus:outline-none" title="Abrir lista" tabindex="-1">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                      </svg>
                    </button>
                  </div>
                  <div x-show="massPdfProjetoDropdownOpen" x-transition class="absolute left-0 right-0 w-full z-50 top-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-64 overflow-y-auto text-sm">
                    <template x-if="massPdfProjetoLoading"><div class="p-2 text-gray-500 dark:text-gray-400">Buscando projetos...</div></template>
                    <template x-if="!massPdfProjetoLoading && massPdfProjetoResultados.length === 0"><div class="p-2 text-gray-500 dark:text-gray-400" x-text="String(massPdfProjeto || '').trim() === '' ? 'Digite para buscar' : 'Nenhum resultado'"></div></template>
                    <template x-for="(projeto, index) in massPdfProjetoResultados" :key="`mass-projeto-atribuir-${index}-${projeto.CDPROJETO}`">
                      <div @mousedown.prevent="selecionarProjetoTermo(projeto)" :class="massPdfProjetoHighlight === index ? 'bg-indigo-50 dark:bg-gray-700' : ''" class="px-3 py-2 cursor-pointer hover:bg-indigo-50 dark:hover:bg-gray-700">
                        <span class="font-mono text-xs text-indigo-600 dark:text-indigo-400" x-text="projeto.CDPROJETO"></span>
                        <span class="ml-2 text-gray-700 dark:text-gray-300" x-text="`- ${projeto.NOMEPROJETO}`"></span>
                      </div>
                    </template>
                  </div>
                </div>
                <p class="mt-1 text-xs text-red-500" x-show="massPdfProjetoErro" x-text="massPdfProjetoErro"></p>
              </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-end gap-3">
              <button type="button" @click="fecharModalTermoMassa()" :disabled="massPdfBaixando" class="px-4 py-2 rounded-md border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">Cancelar</button>
              <button type="submit" :disabled="massPdfBaixando" class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white disabled:opacity-50 disabled:cursor-not-allowed">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span x-show="!massPdfBaixando">Gerar</span>
                <span x-show="massPdfBaixando">Gerando...</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div x-show="massPdfResumoOpen" x-cloak class="fixed inset-0 z-[70] overflow-y-auto" aria-labelledby="modal-resumo-termo-massa" role="dialog" aria-modal="true">
      <div class="flex min-h-screen items-center justify-center px-4 py-6">
        <div class="fixed inset-0 bg-black/60" @click="fecharResumoTermoMassa()"></div>
        <div class="relative z-10 w-full max-w-md rounded-xl bg-white dark:bg-gray-800 shadow-2xl border border-gray-200 dark:border-gray-700">
          <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 flex items-start justify-between gap-3">
            <div>
              <h3 id="modal-resumo-termo-massa" class="text-lg font-semibold text-gray-900 dark:text-gray-100">Resumo da geração</h3>
              <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Lote gerado com sucesso. Clique em <strong>Baixar ZIP</strong> para salvar o arquivo.</p>
            </div>
            <button type="button" @click="fecharResumoTermoMassa()" class="mt-0.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none shrink-0" title="Fechar" aria-label="Fechar">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
            </button>
          </div>
          <div class="px-6 py-5 space-y-3 text-sm text-gray-700 dark:text-gray-200">
            <div class="flex items-center justify-between gap-4"><span>Projeto</span><span class="font-semibold text-right" x-text="massPdfResumo.projetoNome ? `${massPdfResumo.projetoCodigo} - ${massPdfResumo.projetoNome}` : massPdfResumo.projetoCodigo"></span></div>
            <div class="flex items-center justify-between gap-4"><span>Termos gerados</span><span class="font-semibold" x-text="massPdfResumo.termos"></span></div>
            <div class="flex items-center justify-between gap-4"><span>Itens processados</span><span class="font-semibold" x-text="massPdfResumo.itens"></span></div>
            <div class="flex items-center justify-between gap-4"><span>Formato</span><span class="font-semibold" x-text="massPdfResumo.formato"></span></div>
          </div>
          <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-center">
            <button
              type="button"
              @click="executarDownloadTermo()"
              x-show="massPdfDownloadUrl"
              :class="massPdfDownloadJaClicado
                ? 'bg-green-600 hover:bg-green-700'
                : 'bg-green-500 hover:bg-green-600 shadow-lg shadow-green-400/50 dark:shadow-green-900/60 ring-2 ring-green-300 dark:ring-green-500 ring-offset-2 animate-pulse'"
              class="inline-flex items-center gap-2 px-4 py-2 rounded-md text-white transition-all duration-300 text-sm disabled:opacity-50">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
              Baixar ZIP
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div x-data="{showConfirmModal:false, selectedPatrimonios:[], codigoTermo:''}" x-show="showConfirmModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div x-show="showConfirmModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-surface-alt0 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

      <div x-show="showConfirmModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-surface rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <div class="bg-surface px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <div class="sm:flex sm:items-start">
            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
              <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 19.5c-.77.833.192 2.5 1.732 2.5z" />
              </svg>
            </div>
            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
              <h3 class="text-lg leading-6 font-medium text-on-surface" id="modal-title">
                Confirmar Atribuio
              </h3>
              <div class="mt-2">
                <p class="text-sm text-muted">(Modal legado inativo)</p>
                <p class="text-xs text-muted mt-1">
                  Esta ação não pode ser desfeita facilmente.
                </p>
              </div>
            </div>
          </div>
        </div>
        <div class="bg-surface-alt px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button type="button" @click="showConfirmModal=false" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
            Fechar
          </button>
          <button type="button" @click="showConfirmModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
            Cancelar
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal de Desatribuicao removido no novo fluxo -->

  <!-- Modal de Codigos removido (simplificacao solicitada) -->

  </div>

  <!-- Forms auxiliares invisiveis removidos: geracao de codigo via fetch -->

  <script>
    const patrimonioAtribuirSelection = (() => {
      const params = new URLSearchParams(window.location.search);
      const status = params.get('status') || 'disponivel';
      const key = 'patrimonios.atribuir.selection.' + status;
      const normalize = (ids) => {
        const unique = new Set();
        (ids || []).forEach((id) => {
          if (id === null || typeof id === 'undefined') return;
          const value = String(id).trim();
          if (!value) return;
          unique.add(value);
        });
        return Array.from(unique);
      };
      const read = () => {
        try {
          const raw = sessionStorage.getItem(key);
          if (!raw) return [];
          const data = JSON.parse(raw);
          if (Array.isArray(data)) return normalize(data);
          return normalize(data && data.ids ? data.ids : []);
        } catch (_) {
          return [];
        }
      };
      const write = (ids) => {
        try {
          sessionStorage.setItem(key, JSON.stringify({ ids: normalize(ids) }));
        } catch (_) {}
      };
      const clear = () => {
        try {
          sessionStorage.removeItem(key);
        } catch (_) {}
      };
      return { key, status, read, write, clear, normalize };
    })();
    window.patrimonioAtribuirSelection = patrimonioAtribuirSelection;
    const atribuirCodigosBaseUrl = @json(route('patrimonios.atribuir.codigos'));
    const termosBaseUrl = @json(url('/termos'));
    const initialSelectedTermoCode = @json((string) request('codigo', ''));
    const initialGroupTitles = @json(collect($termosMetadados ?? [])->mapWithKeys(function ($meta, $codigo) {
      return [(string) $codigo => trim((string) ($meta['titulo'] ?? ''))];
    })->all());

    window.atribuirPage = function atribuirPage() {
      return {
        // Animao custom: classe usada: animate-fadeInScale
        showFilters: false,
        showConfirmModal: false,
        showDesatribuirModal: false,
        selectedPatrimonios: [],
        updatedIds: [],
        contadorTexto: '0 patrimônios selecionados',
        codigoTermo: '',
        termoFiltro: '',
        desatribuirItem: null,
        atribuindo: false,
        erroCodigo: false,
        gerandoCodigo: false,
        editingTermoCodigo: null,
        editingTitulo: '',
        salvandoTitulo: false,
        customGroupTitles: initialGroupTitles,
        groupState: {}, // Estado dos grupos (expandido/colapsado)
        grupoSelecionados: {}, // Itens selecionados por grupo
        selectionEnabled: false,
        ajaxHandlersBound: false,
        // Estados de listagem de códigos removidos (modal removido)
        init() {
          this.selectionEnabled = !!(window.patrimonioAtribuirSelection && window.patrimonioAtribuirSelection.status === 'disponivel');
          if (this.selectionEnabled) {
            this.loadSelection();
            this.syncCheckboxes();
          }
          this.updateCounter();
          // ESC para fechar popover e modais leves
          window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
              if (this.showCodigosModal) this.showCodigosModal = false;
            }
          });

          // Monitora mudança de status (disponível -> indisponível) para cancelar código não utilizado
          window.addEventListener('beforeunload', () => {
            const params = new URLSearchParams(window.location.search);
            if (params.get('status') === 'indisponivel') {
              // Se navegando para Atribuídos, cancelar código via footer
              const footerElement = document.querySelector('[x-data*="footerAcoes"]');
              if (footerElement && footerElement.__x) {
                footerElement.__x.$data.cancelar();
              }
            }
          });

          // Monitora selees para mostrar/esconder o footer com background
          const updateFooterVisibility = () => {
            const checkboxes = document.querySelectorAll('input.patrimonio-checkbox:checked, input.grupo-item-checkbox:checked');
            const htmlElement = document.documentElement;
            const hasSelection = this.selectionEnabled ? this.selectedPatrimonios.length > 0 : checkboxes.length > 0;
            if (hasSelection) {
              htmlElement.classList.add('with-visible-footer');
              htmlElement.classList.remove('without-visible-footer');
            } else {
              htmlElement.classList.add('without-visible-footer');
              htmlElement.classList.remove('with-visible-footer');
            }
          };

          // Listener para todas as mudanças de checkbox
          document.addEventListener('change', (e) => {
            if (e.target.matches('input[type="checkbox"]')) {
              updateFooterVisibility();
            }
          });

          // Verificao inicial
          updateFooterVisibility();
          this.bindAjaxHandlers();
        },
        bindAjaxHandlers() {
          if (this.ajaxHandlersBound) return;
          this.ajaxHandlersBound = true;
          document.addEventListener('click', (e) => {
            const sortLink = e.target.closest('[data-ajax-sort]');
            if (sortLink) {
              e.preventDefault();
              const href = sortLink.getAttribute('href');
              if (!href) return;
              const hrefParams = this.parseHrefParams(href);
              const params = this.buildFilterParams({
                sort: hrefParams.get('sort'),
                direction: hrefParams.get('direction'),
                page: null,
              });
              this.ajaxFetchParams(params);
              return;
            }

            const pagLink = e.target.closest('#atribuir-pagination a');
            if (pagLink) {
              e.preventDefault();
              const href = pagLink.getAttribute('href');
              if (!href) return;
              const hrefParams = this.parseHrefParams(href);
              const params = this.buildFilterParams({
                page: hrefParams.get('page'),
                per_page: hrefParams.get('per_page'),
              });
              this.ajaxFetchParams(params);
            }
          });
        },
        abrirEditorTitulo(codigo, tituloAtual = '') {
          this.editingTermoCodigo = String(codigo);
          this.editingTitulo = tituloAtual || '';
          this.$nextTick(() => {
            const input = document.getElementById(`titulo-termo-${codigo}`);
            if (input) {
              input.focus();
              input.select?.();
            }
          });
        },
        cancelarEdicaoTitulo() {
          this.editingTermoCodigo = null;
          this.editingTitulo = '';
          this.salvandoTitulo = false;
        },
        getTituloAgrupado(codigo) {
          return String(this.customGroupTitles[String(codigo)] || '').trim();
        },
        async salvarTituloTermo(codigo) {
          if (this.salvandoTitulo) {
            return;
          }

          this.salvandoTitulo = true;

          try {
            const res = await fetch(`${termosBaseUrl}/${encodeURIComponent(codigo)}/titulo`, {
              method: 'PATCH',
              headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
              },
              body: JSON.stringify({
                titulo: this.editingTitulo,
              }),
            });

            const json = await res.json().catch(() => ({}));

            if (!res.ok) {
              alert(json.message || 'Não foi possível atualizar o nome do agrupado.');
              return;
            }

            this.customGroupTitles = { ...this.customGroupTitles, [String(codigo)]: String(json?.data?.titulo || '').trim() };
            this.cancelarEdicaoTitulo();
          } catch (e) {
            console.error('Erro ao atualizar nome do agrupado:', e);
            alert('Erro ao atualizar o nome do agrupado. Tente novamente.');
          } finally {
            this.salvandoTitulo = false;
          }
        },
        buildFilterParams(overrides = {}) {
          const params = new URLSearchParams(window.location.search);
          [
            'filtro_numero',
            'filtro_descricao',
            'filtro_modelo',
            'filtro_projeto',
            'filtro_termo',
            'filtro_matr_responsavel',
            'filtro_matr_cadastrador',
            'per_page',
            'page',
            'sort',
            'direction',
          ].forEach(k => params.delete(k));
          const numero = document.getElementById('filtro_numero')?.value;
          const descricao = document.getElementById('filtro_descricao')?.value;
          const modelo = document.getElementById('filtro_modelo')?.value;
          const projeto = document.getElementById('filtro_projeto')?.value;
          const termo = document.getElementById('filtro_termo')?.value;
          const matrResp = document.getElementById('filtro_matr_responsavel')?.value;
          const matrCad = document.getElementById('filtro_matr_cadastrador')?.value;
          const perPage = document.getElementById('per_page')?.value;
          if (numero) params.set('filtro_numero', numero);
          if (descricao) params.set('filtro_descricao', descricao);
          if (modelo) params.set('filtro_modelo', modelo);
          if (projeto) params.set('filtro_projeto', projeto);
          if (termo) params.set('filtro_termo', termo);
          if (matrResp) params.set('filtro_matr_responsavel', matrResp);
          if (matrCad) params.set('filtro_matr_cadastrador', matrCad);
          if (perPage) params.set('per_page', perPage);
          Object.entries(overrides || {}).forEach(([key, value]) => {
            if (value == null || value === '') {
              params.delete(key);
            } else {
              params.set(key, value);
            }
          });
          return params;
        },
        updateHistory(params) {
          if (!window.history) return;
          const query = params.toString();
          const url = query ? window.location.pathname + '?' + query : window.location.pathname;
          window.history.replaceState(null, '', url);
        },
        parseHrefParams(href) {
          try {
            const url = new URL(href, window.location.origin);
            return url.searchParams;
          } catch (_) {
            const idx = href.indexOf('?');
            return new URLSearchParams(idx >= 0 ? href.slice(idx + 1) : '');
          }
        },
        ajaxFetchParams(params) {
          const gridContainer = document.getElementById('atribuir-grid-container');
          if (!gridContainer) {
            const query = params.toString();
          const destino = query ? atribuirCodigosBaseUrl + '?' + query : atribuirCodigosBaseUrl;
          window.location.href = destino;
            return;
          }
          if (this.selectionEnabled) {
            this.saveSelection();
          }
          const query = params.toString();
          const url = query ? atribuirCodigosBaseUrl + '?' + query : atribuirCodigosBaseUrl;
          gridContainer.classList.add('opacity-60');
          fetch(url, {
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
              'Accept': 'text/html',
            },
          })
            .then(resp => resp.text())
            .then(html => this.swapGrid(html))
            .then(() => this.updateHistory(params))
            .catch((err) => {
              console.error('[ATRIBUIR] ajax fetch error', err);
              alert('Falha ao atualizar a listagem. Tente novamente.');
            })
            .finally(() => {
              gridContainer.classList.remove('opacity-60');
            });
        },
        swapGrid(html) {
          const gridContainer = document.getElementById('atribuir-grid-container');
          if (!gridContainer) return;
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, 'text/html');
          const fresh = doc.querySelector('#atribuir-grid-container');
          if (fresh) {
            gridContainer.innerHTML = fresh.innerHTML;
            if (window.Alpine && typeof Alpine.initTree === 'function') {
              Alpine.initTree(gridContainer);
            }
          }
          if (this.selectionEnabled) {
            this.syncCheckboxes();
          }
          this.updateCounter();
        },
        loadSelection() {

          if (!this.selectionEnabled || !window.patrimonioAtribuirSelection) return;
          this.selectedPatrimonios = window.patrimonioAtribuirSelection.read();
        },
        saveSelection() {
          if (!this.selectionEnabled || !window.patrimonioAtribuirSelection) return;
          window.patrimonioAtribuirSelection.write(this.selectedPatrimonios);
        },
        syncCheckboxes() {
          if (!this.selectionEnabled) return;
          const selectedSet = new Set((this.selectedPatrimonios || []).map(String));
          const checkboxes = document.querySelectorAll("input.patrimonio-checkbox[name='ids[]']");
          checkboxes.forEach(cb => {
            cb.checked = selectedSet.has(String(cb.value));
          });
        },
        handleCheckboxChange(event) {
          if (!this.selectionEnabled) {
            this.updateCounter();
            return;
          }
          const target = event?.target;
          if (!target || !target.matches("input.patrimonio-checkbox[name='ids[]']")) return;
          const selectedSet = new Set((this.selectedPatrimonios || []).map(String));
          const id = String(target.value);
          if (target.checked) {
            selectedSet.add(id);
          } else {
            selectedSet.delete(id);
          }
          this.selectedPatrimonios = Array.from(selectedSet);
          this.saveSelection();
          this.updateCounter();
        },
        syncCodigoTermo(value) {
          this.codigoTermo = value;
        },
        aplicarFiltros() {
          const params = this.buildFilterParams();
          this.ajaxFetchParams(params);
        },
        limparFiltros() {
          ['filtro_numero', 'filtro_descricao', 'filtro_modelo', 'filtro_projeto', 'filtro_termo', 'filtro_matr_responsavel', 'filtro_matr_cadastrador'].forEach((id) => {
            const el = document.getElementById(id);
            if (el) el.value = '';
          });
          const perPage = document.getElementById('per_page');
          if (perPage) {
            const first = perPage.querySelector('option');
            perPage.value = first ? first.value : '';
          }
          const params = this.buildFilterParams({ codigo: null, page: null });
          this.ajaxFetchParams(params);
        },
        toggleAll(event) {
          const source = event.target;
          const checkboxes = document.querySelectorAll('.patrimonio-checkbox');
          checkboxes.forEach(cb => {
            cb.checked = source.checked;
            // Dispara evento change para notificar o footerAcoes()
            cb.dispatchEvent(new Event('change', {
              bubbles: true
            }));
          });
          this.updateCounter();
        },
        updateCounter() {
          if (this.selectionEnabled) {
            const selectedSet = new Set((this.selectedPatrimonios || []).map(String));
            const count = selectedSet.size;
            this.contadorTexto = count === 0
              ? '0 patrimônios selecionados'
              : count + ' patrimônio' + (count > 1 ? 's' : '') + ' selecionado' + (count > 1 ? 's' : '');
            const selectAll = document.getElementById('selectAll');
            if (selectAll) {
              const allCheckboxes = document.querySelectorAll('.patrimonio-checkbox');
              const selectedVisible = Array.from(allCheckboxes).filter(cb => selectedSet.has(String(cb.value))).length;
              selectAll.checked = allCheckboxes.length > 0 && selectedVisible === allCheckboxes.length;
              selectAll.indeterminate = selectedVisible > 0 && selectedVisible < allCheckboxes.length;
            }
            this.selectedPatrimonios = Array.from(selectedSet);
            return;
          }
          const checkboxes = document.querySelectorAll('.patrimonio-checkbox:checked');
          const count = checkboxes.length;
          this.contadorTexto = count === 0
            ? '0 patrimônios selecionados'
            : count + ' patrimônio' + (count > 1 ? 's' : '') + ' selecionado' + (count > 1 ? 's' : '');
          const selectAll = document.getElementById('selectAll');
          if (selectAll) {
            const allCheckboxes = document.querySelectorAll('.patrimonio-checkbox');
            selectAll.checked = allCheckboxes.length > 0 && checkboxes.length === allCheckboxes.length;
            selectAll.indeterminate = checkboxes.length > 0 && checkboxes.length < allCheckboxes.length;
          }
          this.selectedPatrimonios = Array.from(checkboxes).map(cb => cb.value);
        },
        async downloadTermoDocx() {
          if (this.selectedPatrimonios.length === 0) {
            alert('Selecione pelo menos um patrimônio para gerar o termo.');
            return;
          }

          try {
            // Criar form oculto para POST com os IDs selecionados
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("termos.docx.zip") }}';
            form.style.display = 'none';

            // CSRF Token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            form.appendChild(csrfInput);

            // IDs dos patrimônios
            this.selectedPatrimonios.forEach(id => {
              const input = document.createElement('input');
              input.type = 'hidden';
              input.name = 'ids[]';
              input.value = id;
              form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();

            // Remover form após submit
            setTimeout(() => form.remove(), 100);
          } catch (e) {
            console.error('Erro ao gerar termo DOCX:', e);
            alert('Erro ao gerar documento. Tente novamente.');
          }
        },
        async downloadTermoGrupo(ids) {
          if (!ids || ids.length === 0) {
            alert('Nenhum patrimônio disponível para download.');
            return;
          }

          try {
            // Criar form oculto para POST com os IDs do grupo
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("termos.docx.zip") }}';
            form.style.display = 'none';

            // CSRF Token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            form.appendChild(csrfInput);

            // IDs dos patrimônios do grupo
            ids.forEach(id => {
              const input = document.createElement('input');
              input.type = 'hidden';
              input.name = 'ids[]';
              input.value = id;
              form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();

            // Remover form após submit
            setTimeout(() => form.remove(), 100);
          } catch (e) {
            console.error('Erro ao gerar termo DOCX do grupo:', e);
            alert('Erro ao gerar documento. Tente novamente.');
          }
        },
        async downloadPlanilhaTermo(ids, codigoTermo) {
          if (!ids || ids.length === 0) {
            alert('Nenhum patrimônio disponível para download.');
            return;
          }

          try {
            // Criar form oculto para POST com os IDs do grupo
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("termos.exportar.excel") }}';
            form.style.display = 'none';

            // CSRF Token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            form.appendChild(csrfInput);

            // Código do termo
            const codigoInput = document.createElement('input');
            codigoInput.type = 'hidden';
            codigoInput.name = 'cod_termo';
            codigoInput.value = codigoTermo;
            form.appendChild(codigoInput);

            // IDs dos patrimônios do grupo
            ids.forEach(id => {
              const input = document.createElement('input');
              input.type = 'hidden';
              input.name = 'ids[]';
              input.value = id;
              form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();

            // Remover form após submit
            setTimeout(() => form.remove(), 100);
          } catch (e) {
            console.error('Erro ao gerar planilha termo:', e);
            alert('Erro ao gerar planilha. Tente novamente.');
          }
        },
        async gerarPlanilhaTermo() {
          if (this.selectedPatrimonios.length === 0) {
            alert('Selecione pelo menos um patrimônio para gerar a planilha.');
            return;
          }

          try {
            // Criar form oculto para POST com os IDs selecionados
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("termos.exportar.excel") }}';
            form.style.display = 'none';

            // CSRF Token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            form.appendChild(csrfInput);

            // IDs dos patrimônios selecionados
            this.selectedPatrimonios.forEach(id => {
              const input = document.createElement('input');
              input.type = 'hidden';
              input.name = 'ids[]';
              input.value = id;
              form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();

            // Remover form após submit
            setTimeout(() => form.remove(), 100);
          } catch (e) {
            console.error('Erro ao gerar planilha termo:', e);
            alert('Erro ao gerar planilha. Tente novamente.');
          }
        },
        async desatribuirGrupo(ids) {
          if (!ids || ids.length === 0) {
            alert('Nenhum patrimônio disponível para desatribuição.');
            return;
          }

          if (!confirm('Tem certeza que deseja desatribuir todos os itens deste termo?')) {
            return;
          }

          try {
            // Usar a rota nova de desatribuição via AJAX
            const res = await fetch("{{ route('patrimonios.desatribuirCodigo') }}", {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                ids: ids
              })
            });
            const json = await res.json();
            if (!res.ok) {
              alert(json.message || 'Erro ao desatribuir');
              return;
            }
            // Redireciona imediatamente para página de disponíveis
            window.location.href = "{{ route('patrimonios.atribuir.codigos', ['status' => 'disponivel']) }}";
          } catch (e) {
            console.error('Erro ao desatribuir grupo:', e);
            alert('Erro ao desatribuir itens. Tente novamente.');
          }
        },
        async desatribuirItem(id) {
          if (!confirm('Tem certeza que deseja remover este item do termo?')) {
            return;
          }

          try {
            // Usar a rota nova de desatribuição via AJAX
            const res = await fetch("{{ route('patrimonios.desatribuirCodigo') }}", {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                ids: [id]
              })
            });
            const json = await res.json();
            if (!res.ok) {
              alert(json.message || 'Erro ao desatribuir');
              return;
            }
            // Redireciona imediatamente para página de disponíveis
            window.location.href = "{{ route('patrimonios.atribuir.codigos', ['status' => 'disponivel']) }}";
          } catch (e) {
            console.error('Erro ao desatribuir item:', e);
            alert('Erro ao desatribuir item. Tente novamente.');
          }
        },
        // FUNÇÃO LEGADA - NÃO USE MAIS
        // Use o novo fluxo: footerAcoes() -> gerar() -> atribuir()
        async processarAtribuicaoLegacy() {
          // Esta função não deve ser chamada mais
          console.warn('processarAtribuicao() legado não deve ser chamado!');
          return;
        },
        async gerarCodigo() {
          this.erroCodigo = false;
          this.gerandoCodigo = true;
          try {
            const res = await fetch("{{ route('termos.codigos.sugestao') }}");
            if (res.ok) {
              const json = await res.json();
              this.codigoTermo = json.sugestao;
            }
          } catch (e) {
            console.error('Erro ao gerar código', e);
          } finally {
            this.gerandoCodigo = false;
          }
        },
        filtrarPorCodigo() {
          const params = this.buildFilterParams({ codigo: this.codigoTermo || null, page: null });
          this.ajaxFetchParams(params);
        },
        // FUNÇÃO LEGADA - NÃO USE MAIS
        // Use desatribuirGrupo() ou footerDesatribuir()
        processarDesatribuicaoLegacy() {
          console.warn('processarDesatribuicao() legado não deve ser chamado!');
          return;
        },
        selectedPatrimoniosAtribuidos() {
          const rows = Array.from(document.querySelectorAll('tr'));
          const result = [];
          rows.forEach(r => {
            const cb = r.querySelector('input.patrimonio-checkbox:checked');
            if (!cb) return;
            const codigoCell = r.children[5];
            if (codigoCell && codigoCell.textContent.trim() && codigoCell.textContent.trim() !== '?') {
              result.push(cb.value);
            }
          });
          return result;
        },
        submitDownloadForm(event) {
          event.preventDefault();
          const form = event.target.closest('form');
          const action = form.getAttribute('action');
          const ids = Array.from(form.querySelectorAll('input[name="ids[]"]')).map(i => i.value);

          console.log(' DOWNLOAD INICIADO', {
            acao: 'submitDownloadForm',
            rota: action,
            ids: ids,
            quantidade: ids.length,
            timestamp: new Date().toISOString()
          });

          // Enviar formulário normalmente
          form.submit();
        },
        updateGroupSelection(grupoId) {
          // Obter todos os checkboxes do grupo
          const checkboxes = document.querySelectorAll('input.grupo-item-checkbox[data-grupo-id="' + grupoId + '"]:checked');
          const ids = Array.from(checkboxes).map(cb => cb.value);

          // Atualizar o estado de seleção do grupo
          if (ids.length > 0) {
            this.grupoSelecionados[grupoId] = ids;
          } else {
            delete this.grupoSelecionados[grupoId];
          }

          // Forçar reatividade
          this.grupoSelecionados = {
            ...this.grupoSelecionados
          };
        },
        toggleGroup(groupId) {
          this.groupState[groupId] = !this.groupState[groupId];
          this.$nextTick(() => {
            // Atualizar icone de rotao
            const header = document.querySelector('tr[data-group-id="' + groupId + '"]');
            if (header) {
              header.style.transition = 'background-color 0.2s ease';
            }
          });
        },
        adicionarPatrimoniosAoTermo(codigo) {
          const destino = new URL(atribuirCodigosBaseUrl, window.location.origin);
          destino.searchParams.set('status', 'disponivel');
          destino.searchParams.set('codigo', String(codigo));
          window.location.href = destino.toString();
        }
      }
    }
  </script>
  <style>
    @keyframes slideUpIn {
      from {
        opacity: 0;
        transform: translateY(100%);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes slideDownOut {
      from {
        opacity: 1;
        transform: translateY(0);
      }

      to {
        opacity: 0;
        transform: translateY(100%);
      }
    }

    .footer-slide-in {
      animation: slideUpIn 0.3s ease-out forwards;
      display: flex !important;
    }

    .footer-slide-out {
      animation: slideDownOut 0.3s ease-out forwards;
      display: none !important;
    }

    /* Controlar visibilidade do footer (background cinza) */
    .site-footer {
      transition: opacity 0.3s ease-out;
    }

    /* Quando há seleção, mostrar footer */
    .with-visible-footer .site-footer {
      opacity: 1;
      pointer-events: auto;
    }

    /* Quando não há seleção, esconder footer */
    .without-visible-footer .site-footer {
      opacity: 0;
      pointer-events: none;
    }

    [x-cloak] {
      display: none !important;
    }
  </style>
  <!-- Modal de códigos ativo (popover legado removido) -->
  @section('footer-actions')
  @if(!request('status') || request('status')=='disponivel')
  <div x-data="footerAcoes()" x-init="init()"
    :class="qtdSelecionados > 0 ? 'footer-slide-in' : 'footer-slide-out'"
    x-show="qtdSelecionados > 0"
    class="w-full">
    <template x-if="generatedCode">
      <div class="w-full flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3 flex-wrap">
          <span class="footer-counter font-medium" x-text="qtdSelecionados + (qtdSelecionados === 1 ? ' patrimônio selecionado' : ' patrimônios selecionados')"></span>
          <span class="code-badge px-4 py-2 rounded-xl text-base font-semibold tracking-wide shadow-sm" x-text="generatedCode"></span>
        </div>
        <div class="flex items-center justify-end gap-3 flex-wrap">
          <p class="footer-actions-help text-sm font-semibold text-white" x-show="state==='generated'" x-text="fixedCode !== '' ? 'Selecione os patrimônios e clique em adicionar ao termo.' : 'Selecione os patrimônios e clique em atribuir.'"></p>
          <button type="button" @click="atribuir()" class="btn btn-accent h-11 rounded-xl px-5 shadow-sm disabled:opacity-60 disabled:cursor-not-allowed" :disabled="qtdSelecionados===0 || state!=='generated'">
            <span x-show="state==='generated'" x-text="fixedCode !== '' ? 'Adicionar ao termo' : 'Atribuir'"></span>
            <span x-show="state==='assigning'" class="inline-flex items-center gap-2">
              <svg class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
              </svg>
              Atribuindo...
            </span>
          </button>
        </div>
      </div>
    </template>
  </div>
  @endif
  @if(request('status')=='indisponivel')
  <div x-data="footerDesatribuir()" x-init="init()"
    :class="qtdSelecionados > 0 ? 'footer-slide-in' : 'footer-slide-out'"
    x-show="qtdSelecionados > 0"
    class="w-full">
    <div class="w-full flex flex-wrap items-center justify-between gap-3">
      <span class="footer-counter font-medium" x-text="qtdSelecionados + (qtdSelecionados === 1 ? ' patrimônio selecionado' : ' patrimônios selecionados')"></span>
      <div class="flex items-center justify-end gap-3 flex-wrap">
      <p class="footer-actions-help text-sm font-semibold text-white" x-show="state==='idle'">Selecione os patrimônios e clique em desatribuir.</p>
      <button type="button" @click="executar()" class="btn btn-danger h-11 rounded-xl px-5 shadow-sm disabled:opacity-60 disabled:cursor-not-allowed inline-flex items-center gap-2" :disabled="qtdSelecionados===0 || state==='processing'">
        <span x-show="state==='idle'">Desatribuir</span>
        <span x-show="state==='processing'" class="inline-flex items-center gap-2">
          <svg class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
          </svg>
          Processando...
        </span>
      </button>
      </div>
    </div>
  </div>
  @endif

  <script>
    function footerAcoes() {
      return {
        state: 'idle',
        loading: false,
        generatedCode: null,
        fixedCode: String(initialSelectedTermoCode || '').trim(),
        qtdSelecionados: 0,
        init() {
          this.wireCheckboxListener();
          if (this.fixedCode !== '') {
            this.generatedCode = this.fixedCode;
            this.state = 'generated';
            return;
          }
          // Auto-gerar código ao inicializar
          this.gerarAutomatico();
        },
        getSelectedIds() {
          if (window.patrimonioAtribuirSelection && window.patrimonioAtribuirSelection.status === 'disponivel') {
            return window.patrimonioAtribuirSelection.read();
          }
          return Array.from(document.querySelectorAll("input.patrimonio-checkbox[name='ids[]']:checked")).map(cb => cb.value);
        },
        wireCheckboxListener() {
          const update = () => {
            const ids = this.getSelectedIds();
            this.qtdSelecionados = ids.length;
            // Mudar para estado 'generated' quando houver seleção
            if (this.qtdSelecionados > 0 && this.generatedCode && this.state === 'idle') {
              this.state = 'generated';
            } else if (this.qtdSelecionados === 0 && this.state === 'generated') {
              this.state = 'idle';
            }
          };
          document.addEventListener('change', e => {
            if (e.target.matches("input.patrimonio-checkbox[name='ids[]']")) update();
          });
          update();
        },
        async gerarAutomatico() {
          if (this.fixedCode !== '') {
            this.generatedCode = this.fixedCode;
            this.state = 'generated';
            return;
          }
          this.loading = true;
          try {
            const res = await fetch("{{ route('patrimonios.gerarCodigo') }}", {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
              }
            });
            if (!res.ok) throw new Error('fail');
            const json = await res.json();
            this.generatedCode = json.code;
            this.state = 'generated';
          } catch (e) {
            console.error(e);
            // Se falhar, deixa em estado idle para o usuário tentar manualmente
            this.state = 'idle';
          } finally {
            this.loading = false;
          }
        },
        async gerar() {
          if (this.fixedCode !== '') {
            this.generatedCode = this.fixedCode;
            this.state = 'generated';
            return;
          }
          this.loading = true;
          try {
            const res = await fetch("{{ route('patrimonios.gerarCodigo') }}", {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
              }
            });
            if (!res.ok) throw new Error('fail');
            const json = await res.json();
            this.generatedCode = json.code; // inteiro
            this.state = 'generated';
          } catch (e) {
            console.error(e);
            alert('Erro ao gerar código');
          } finally {
            this.loading = false;
          }
        },
        async atribuir() {
          if (this.qtdSelecionados === 0 || !this.generatedCode) return;
          this.state = 'assigning';
          try {
            const ids = this.getSelectedIds();
            const res = await fetch("{{ route('patrimonios.atribuirCodigo') }}", {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                code: this.generatedCode,
                ids
              })
            });
            const json = await res.json();
            if (!res.ok) {
              alert(json.message || 'Erro ao atribuir');
              this.state = 'generated';
              return;
            }
            json.updated_ids.forEach(id => {
              const row = document.querySelector("tr[data-row-id='" + id + "']");
              if (row) {
                const cell = row.querySelector('[data-col="codigo-termo"]');
                if (cell) {
                  cell.innerHTML = '<span class="badge-indigo font-mono">' + this.generatedCode + '</span>';
                }
                const cb = row.querySelector("input.patrimonio-checkbox[name='ids[]']");
                if (cb) {
                  cb.checked = false;
                  cb.disabled = true;
                }
              }
            });
            this.qtdSelecionados = 0;
            if (window.patrimonioAtribuirSelection) {
              window.patrimonioAtribuirSelection.clear();
            }
            // Redireciona imediatamente para aba de atribuídos COM filtro do termo criado
            window.location.href = "{{ route('patrimonios.atribuir.codigos') }}?status=indisponivel&filtro_termo=" + this.generatedCode;
            this.state = 'generated';
          } catch (e) {
            console.error(e);
            alert('Erro inesperado');
            this.state = 'generated';
          }
        },
        cancelar() {
          if (this.fixedCode !== '') {
            return;
          }
          // Limpar código local (ele será descartado no backend após expiração)
          this.generatedCode = null;
          this.state = 'idle';
        }
      }
    }

    function footerDesatribuir() {
      return {
        state: 'idle',
        qtdSelecionados: 0,
        init() {
          this.wire();
        },
        wire() {
          const u = () => {
            // Contar checkboxes dos grupos (modo atribuídos)
            const grupoCheckboxes = document.querySelectorAll("input.grupo-item-checkbox:checked").length;
            this.qtdSelecionados = grupoCheckboxes;
          };
          document.addEventListener('change', e => {
            if (e.target.matches("input.grupo-item-checkbox")) u();
          });
          u();
        },
        async executar() {
          if (this.qtdSelecionados === 0) return;
          this.state = 'processing';
          try {
            // Coletar IDs dos checkboxes de grupo (modo atribuídos)
            const ids = Array.from(document.querySelectorAll("input.grupo-item-checkbox:checked")).map(cb => cb.value);

            if (ids.length === 0) {
              alert('Nenhum item selecionado');
              this.state = 'idle';
              return;
            }

            const res = await fetch("{{ route('patrimonios.desatribuirCodigo') }}", {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                ids
              })
            });
            const json = await res.json();
            if (!res.ok) {
              alert(json.message || 'Erro');
              this.state = 'idle';
              return;
            }

            // Redireciona imediatamente para atualizar a página
            window.location.href = "{{ route('patrimonios.atribuir.codigos', ['status' => 'indisponivel']) }}";
          } catch (e) {
            console.error(e);
            alert('Erro inesperado');
          } finally {
            this.state = 'idle';
          }
        },
        limparSelecao() {
          document.querySelectorAll("input.patrimonio-checkbox[name='ids[]']").forEach(cb => {
            cb.checked = false;
          });
          this.qtdSelecionados = 0;
        }
      }
    }
  </script>

  {{-- Script para interceptar respostas e registrar em console --}}
  <script>
    // Monitorar submissões de formulário
    document.addEventListener('submit', function(e) {
      const form = e.target;
      const action = form.getAttribute('action') || '';

      // Se é um formulário de download de termo
      if (action.includes('docx')) {
        console.log('✅ Iniciando download do Termo...', {
          action: action,
          items: Array.from(form.querySelectorAll('input[name="ids[]"]')).length,
          timestamp: new Date().toISOString()
        });
      }

      // Se é formulário de atribuição (não deveria ser após clicar em "Baixar")
      if (action.includes('atribuir.processar')) {
        console.warn('⚠️ Formulário de atribuição disparado', {
          action: action,
          timestamp: new Date().toISOString()
        });
      }
    });
  </script>
  @endsection

  {{-- CHECK:
php artisan route:clear
php artisan view:clear
php artisan config:clear
php artisan route:list | grep patrimonios
--}}
</x-app-layout>

<!-- CHECK (não executa):
 # rg -n "<style" resources/views/patrimonios/atribuir.blade.php || true
 # rg -n "style=\"" resources/views/patrimonios/atribuir.blade.php || true
-->
