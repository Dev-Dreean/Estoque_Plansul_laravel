<x-app-layout>
  {{-- Abas de navegação do patrimônio --}}
  <x-patrimonio-nav-tabs />

  <div x-data="atribuirPage()" x-init="init()">
    <div class="py-12">
      <div class="w-full sm:px-6 lg:px-8">
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
        <div class="mb-4 bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded relative" role="alert">
          <strong class="font-bold">Atenção!</strong>
          <span class="block sm:inline">{{ session('warning') }}</span>
        </div>
        @endif
        @if($errors->any())
        <div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded relative" role="alert">
          <strong class="font-bold">Erro de Validação!</strong>
          <span class="block sm:inline">{{ $errors->first() }}</span>
        </div>
        @endif

        <div class="section">
          <div class="section-body">
            <div class="space-y-6">
              <!-- Filtros (layout replicado do index) -->
              <div x-data="{ open: false, temFiltroAtivo: false, textofiltro: '' }"
                x-init="
                  const num = document.getElementById('filtro_numero').value;
                  const desc = document.getElementById('filtro_descricao').value;
                  const mod = document.getElementById('filtro_modelo').value;
                  const proj = document.getElementById('filtro_projeto').value;
                  const termo = document.getElementById('filtro_termo') ? document.getElementById('filtro_termo').value : '';
                  temFiltroAtivo = num || desc || mod || proj || termo ? true : false;
                  let partes = [];
                  if (num) partes.push('Nº=' + num);
                  if (desc) partes.push('Item=' + desc);
                  if (mod) partes.push('Modelo=' + mod);
                  if (proj) partes.push('Projeto=' + proj);
                  if (termo) partes.push('Termo=' + termo);
                  textofiltro = partes.join(', ');
                "
                @click.outside="open = false" class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg mb-6" x-id="['filtro-atribuir']" :aria-expanded="open.toString()" :aria-controls="$id('filtro-atribuir')">
                <div class="flex justify-between items-center">
                  <h3 class="font-semibold text-lg">
                    Filtros de Busca
                    <span x-show="temFiltroAtivo" class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-100">
                      Ativo: <span x-text="textofiltro" class="ml-1"></span>
                    </span>
                  </h3>
                  <button type="button" @click="open = !open" aria-expanded="open" aria-controls="$id('filtro-atribuir')" class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                    <span class="sr-only">Expandir filtros</span>
                  </button>
                </div>
                <div x-show="open" x-transition class="mt-4" x-cloak :id="$id('filtro-atribuir')">
                  <div class="flex flex-row gap-3 sm:gap-4">
                    @if(request('status') == 'indisponivel')
                    <div class="flex-1 min-w-[150px]">
                      <input type="text" id="filtro_termo" name="filtro_termo" value="{{ request('filtro_termo') }}" placeholder="Nº Termo" @input="
                        const num = document.getElementById('filtro_numero').value;
                        const desc = document.getElementById('filtro_descricao').value;
                        const mod = document.getElementById('filtro_modelo').value;
                        const proj = document.getElementById('filtro_projeto').value;
                        const termo = document.getElementById('filtro_termo').value;
                        temFiltroAtivo = num || desc || mod || proj || termo ? true : false;
                        let partes = [];
                        if (termo) partes.push('Termo=' + termo);
                        if (num) partes.push('Nº=' + num);
                        if (desc) partes.push('Item=' + desc);
                        if (mod) partes.push('Modelo=' + mod);
                        if (proj) partes.push('Projeto=' + proj);
                        textofiltro = partes.join(', ');
                      " class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                    </div>
                    @endif
                    <div class="flex-1 min-w-[150px]">
                      <input type="text" id="filtro_numero" name="filtro_numero" value="{{ request('filtro_numero') }}" placeholder="Nº Patr." @input="
                        const num = document.getElementById('filtro_numero').value;
                        const desc = document.getElementById('filtro_descricao').value;
                        const mod = document.getElementById('filtro_modelo').value;
                        const proj = document.getElementById('filtro_projeto').value;
                        const termo = document.getElementById('filtro_termo') ? document.getElementById('filtro_termo').value : '';
                        temFiltroAtivo = num || desc || mod || proj || termo ? true : false;
                        let partes = [];
                        if (termo) partes.push('Termo=' + termo);
                        if (num) partes.push('Nº=' + num);
                        if (desc) partes.push('Item=' + desc);
                        if (mod) partes.push('Modelo=' + mod);
                        if (proj) partes.push('Projeto=' + proj);
                        textofiltro = partes.join(', ');
                      " class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                    </div>
                    <div class="flex-1 min-w-[150px]">
                      <input type="text" id="filtro_descricao" name="filtro_descricao" value="{{ request('filtro_descricao') }}" placeholder="Item" @input="
                        const num = document.getElementById('filtro_numero').value;
                        const desc = document.getElementById('filtro_descricao').value;
                        const mod = document.getElementById('filtro_modelo').value;
                        const proj = document.getElementById('filtro_projeto').value;
                        const termo = document.getElementById('filtro_termo') ? document.getElementById('filtro_termo').value : '';
                        temFiltroAtivo = num || desc || mod || proj || termo ? true : false;
                        let partes = [];
                        if (termo) partes.push('Termo=' + termo);
                        if (num) partes.push('Nº=' + num);
                        if (desc) partes.push('Item=' + desc);
                        if (mod) partes.push('Modelo=' + mod);
                        if (proj) partes.push('Projeto=' + proj);
                        textofiltro = partes.join(', ');
                      " class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                    </div>
                    <div class="flex-1 min-w-[150px]">
                      <input type="text" id="filtro_modelo" name="filtro_modelo" value="{{ request('filtro_modelo') }}" placeholder="Modelo" @input="
                        const num = document.getElementById('filtro_numero').value;
                        const desc = document.getElementById('filtro_descricao').value;
                        const mod = document.getElementById('filtro_modelo').value;
                        const proj = document.getElementById('filtro_projeto').value;
                        const termo = document.getElementById('filtro_termo') ? document.getElementById('filtro_termo').value : '';
                        temFiltroAtivo = num || desc || mod || proj || termo ? true : false;
                        let partes = [];
                        if (termo) partes.push('Termo=' + termo);
                        if (num) partes.push('Nº=' + num);
                        if (desc) partes.push('Item=' + desc);
                        if (mod) partes.push('Modelo=' + mod);
                        if (proj) partes.push('Projeto=' + proj);
                        textofiltro = partes.join(', ');
                      " class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                    </div>
                    <div class="flex-1 min-w-[150px]">
                      <input type="number" id="filtro_projeto" name="filtro_projeto" value="{{ request('filtro_projeto') }}" placeholder="Cód. Projeto" @input="
                        const num = document.getElementById('filtro_numero').value;
                        const desc = document.getElementById('filtro_descricao').value;
                        const mod = document.getElementById('filtro_modelo').value;
                        const proj = document.getElementById('filtro_projeto').value;
                        const termo = document.getElementById('filtro_termo') ? document.getElementById('filtro_termo').value : '';
                        const matrResp = document.getElementById('filtro_matr_responsavel') ? document.getElementById('filtro_matr_responsavel').value : '';
                        const matrCad = document.getElementById('filtro_matr_cadastrador') ? document.getElementById('filtro_matr_cadastrador').value : '';
                        temFiltroAtivo = num || desc || mod || proj || termo || matrResp || matrCad ? true : false;
                        let partes = [];
                        if (termo) partes.push('Termo=' + termo);
                        if (num) partes.push('Nº=' + num);
                        if (desc) partes.push('Item=' + desc);
                        if (mod) partes.push('Modelo=' + mod);
                        if (proj) partes.push('Projeto=' + proj);
                        if (matrResp) partes.push('Resp=' + matrResp);
                        if (matrCad) partes.push('Cad=' + matrCad);
                        textofiltro = partes.join(', ');
                      " class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                    </div>
                    <div class="flex-1 min-w-[150px]">
                      <x-employee-autocomplete 
                        id="filtro_matr_responsavel"
                        name="filtro_matr_responsavel"
                        placeholder="Responsável"
                        value="{{ request('filtro_matr_responsavel') }}"
                      />
                    </div>
                    <div class="flex-1 min-w-[150px]">
                      <x-employee-autocomplete 
                        id="filtro_matr_cadastrador"
                        name="filtro_matr_cadastrador"
                        placeholder="Cadastrador"
                        value="{{ request('filtro_matr_cadastrador') }}"
                      />
                    </div>
                  </div>
                  <div class="flex flex-wrap items-center justify-between mt-4 gap-4">
                    <div class="flex items-center gap-3">
                      <button type="button" @click="aplicarFiltros()" class="btn-accent h-10">Filtrar</button>
                      <a href="{{ route('patrimonios.atribuir.codigos') }}" @click="temFiltroAtivo = false; open = false" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md">Limpar</a>
                    </div>
                    <label class="flex items-center gap-2 ml-auto shrink-0">
                      <span class="text-sm text-gray-700 dark:text-gray-300">Itens por página</span>
                      <select id="per_page" name="per_page" class="h-10 px-2 pr-8 w-24 border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm">
                        @foreach([30,50,100,200] as $opt)
                        <option value="{{ $opt }}" @selected(request('per_page',30)==$opt)>{{ $opt }}</option>
                        @endforeach
                      </select>
                    </label>
                  </div>
                </div>
              </div>
              <!-- Barra de ações: filtros à esquerda, ação de planilha à direita -->
              <div class="flex flex-wrap items-center mb-4 gap-3 w-full" x-data="{ }">
                <!-- Esquerda: Disponíveis/Atribuídos -->
                <div class="flex flex-wrap items-center gap-3">
                  <a href="{{ route('patrimonios.atribuir.codigos', ['status'=>'disponivel']) }}" class="text-[11px] px-3 py-2 rounded-md font-semibold border transition {{ request('status','disponivel')=='disponivel' ? 'bg-green-600 text-white border-green-600' : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-700 hover:bg-green-600/10 dark:hover:bg-green-600/10' }}">Disponíveis</a>
                  <a href="{{ route('patrimonios.atribuir.codigos', ['status'=>'indisponivel']) }}" class="text-[11px] px-3 py-2 rounded-md font-semibold border transition {{ request('status')=='indisponivel' ? 'bg-red-600 text-white border-red-600' : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-700 hover:bg-red-600/10 dark:hover:bg-red-600/10' }}">Atribuídos</a>
                </div>

                <!-- Centro: contador quando houver seleção -->
                <div class="flex-1 min-w-[1rem]"></div>
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
              <form method="POST" action="{{ route('patrimonios.atribuir.processar') }}" id="form-atribuir-lote">
                @csrf
            </div>

            <!-- Tabela (estrutura idêntica ao index) -->
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
                  @endphp

                  {{-- Cabeçalho Colapsável do Grupo --}}
                  <tr class="group-header border-b-2 border-gray-200 dark:border-gray-700 transition cursor-pointer bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 shadow-sm"
                    data-group-id="{{ $grupo_id }}"
                    @click="toggleGroup('{{ $grupo_id }}')"
                    :data-expanded="groupState['{{ $grupo_id }}'] === true ? 'true' : 'false'">
                    <td colspan="5" class="px-4 py-4 bg-white dark:bg-gray-800 border-l-4 border-indigo-400 dark:border-indigo-400">
                      <div class="flex items-center justify-between gap-4">
                        {{-- Ícone de Expandir + Info do Grupo --}}
                        <div class="flex items-center gap-4 flex-1 min-w-0">
                          <button type="button"
                            class="flex-shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-md border-2 border-indigo-400 dark:border-indigo-400 bg-white dark:bg-gray-800 hover:bg-indigo-50 dark:hover:bg-indigo-700 transition transform"
                            :class="{ 'rotate-180': groupState['{{ $grupo_id }}'] === true }"
                            @click.stop="toggleGroup('{{ $grupo_id }}')">
                            <svg class="w-4 h-4 text-indigo-400 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                          </button>

                          <div class="flex items-center gap-3 flex-1 min-w-0">
                            @if(!$is_sem_termo)
                            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border-2 border-gray-400 dark:border-gray-600 flex-shrink-0">
                              <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">Termo {{ $grupo_codigo }}</span>
                            </span>
                            @else
                            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-white dark:bg-gray-800 border-2 border-amber-300 dark:border-amber-400 flex-shrink-0">
                              <span class="text-sm font-semibold text-amber-900 dark:text-amber-200">Sem Termo</span>
                            </span>
                            @endif

                            {{-- Lista de itens como badges individuais --}}
                            <div class="flex flex-wrap gap-2 flex-shrink">
                              @foreach($grupo_patrimonios->pluck('DEPATRIMONIO')->unique()->take(5) as $item)
                              <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border border-gray-300 dark:border-gray-600 whitespace-nowrap">
                                {{ Str::limit($item, 30) }}
                              </span>
                              @endforeach
                              @if($grupo_patrimonios->pluck('DEPATRIMONIO')->unique()->count() > 5)
                              <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border border-gray-300 dark:border-gray-600">
                                +{{ $grupo_patrimonios->pluck('DEPATRIMONIO')->unique()->count() - 5 }} mais
                              </span>
                              @endif
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
                            @click.stop="downloadPlanilhaTermo([{{ $grupo_patrimonios->pluck('NUSEQPATR')->join(',') }}], '{{ $grupo_codigo }}')"
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
                    x-show="groupState['{{ $grupo_id }}'] === true"
                    style="display: none;">
                    <th class="px-4 py-3">
                      @if(!request('status') || request('status')=='disponivel')
                      <input type="checkbox" class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-600" @change="toggleGroupCheckboxes('{{ $grupo_id }}', $event)">
                      @endif
                    </th>
                    <th class="px-4 py-3">Nº Pat.</th>
                    <th class="px-4 py-3">Itens</th>
                    <th class="px-4 py-3">Modelo</th>
                    <th class="px-4 py-3" colspan="2">Qntd</th>
                  </tr>

                  {{-- Detalhes do Grupo (Linhas dos Itens Agrupados por Descrição+Modelo) --}}
                  @foreach($grupo_patrimonios_agrupado as $grupo_id_item => $grupo_dados)
                  <tr class="group-details border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/30 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                    data-group-id="{{ $grupo_id }}"
                    data-row-id="{{ $grupo_dados['primeiro']->NUSEQPATR }}"
                    x-show="groupState['{{ $grupo_id }}'] === true"
                    style="display: none;">
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                      <div class="flex items-center justify-center">
                        <input class="grupo-item-checkbox h-4 w-4 rounded border-gray-400 dark:border-gray-400 text-indigo-600 focus:ring-indigo-600"
                          type="checkbox" data-grupo-id="{{ $grupo_id }}" value="{{ $grupo_dados['primeiro']->NUSEQPATR }}" @change="updateGroupSelection('{{ $grupo_id }}')">
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
                      // Captura casos acentuados e casos corrompidos (SEM DESCRIÃ etc)
                      $semDescricao = $descRaw === '' ||
                        str_contains($descAscii, 'SEM DESCRICAO') ||
                        str_contains($descUpper, 'SEM DESCRI') ||
                        str_contains($descRaw, 'SEM DESCRI');
                      $fallback = $primeiro->MARCA ?: ($primeiro->MODELO ?: '-');
                      $displayDesc = $semDescricao ? $fallback : $descRaw;
                    @endphp
                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300 max-w-xs truncate" :title="'{{ $displayDesc }}'">
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
            <div class="mt-4">
              {{ $patrimonios->appends(request()->query())->links() }}
            </div>
            </form> <!-- Fechamento do form-atribuir-lote -->
          </div> <!-- /mb-6 flex flex-col gap-6 -->
        </div> <!-- /space-y-6 -->
      </div>
    </div>
  </div><!-- /w-full wrapper -->
  </div><!-- /py-12 wrapper -->



  <!-- Modal de Confirmação de Atribuição (encapsulado em x-data isolado para não gerar erros se não usado) -->
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
                Confirmar Atribuição
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

  <!-- Modal de Desatribuição removido no novo fluxo -->

  <!-- Modal de CÃ³digos removido (simplificaÃ§Ã£o solicitada) -->

  </div>

  <!-- Forms auxiliares invisíveis removidos: geração de código via fetch -->

  <script>
    const patrimonioAtribuirSelection = (() => {
      const params = new URLSearchParams(window.location.search);
      const status = params.get('status') || 'disponivel';
      const key = `patrimonios.atribuir.selection.${status}`;
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

    function atribuirPage() {
      return {
        // Animação custom: classe usada: animate-fadeInScale
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
        groupState: {}, // Estado dos grupos (expandido/colapsado)
        grupoSelecionados: {}, // Itens selecionados por grupo
        bulkToggle: false,
        selectionEnabled: false,
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

          // Monitora mudança de status (disponivel -> indisponivel) para cancelar código não utilizado
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

          // Monitora seleções para mostrar/esconder o footer com background
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

          // Verificação inicial
          updateFooterVisibility();
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
          if (!this.bulkToggle) {
            this.saveSelection();
            this.updateCounter();
          }
        },
        syncCodigoTermo(value) {
          this.codigoTermo = value;
        },
        aplicarFiltros() {
          const params = new URLSearchParams(window.location.search);
          // Limpa filtros antigos para reconstruir
          ['filtro_numero', 'filtro_descricao', 'filtro_modelo', 'filtro_projeto', 'filtro_termo', 'filtro_matr_responsavel', 'filtro_matr_cadastrador', 'per_page'].forEach(k => params.delete(k));
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
          else params.delete('per_page');
          if (this.selectionEnabled) {
            this.saveSelection();
          }
          window.location.href = '{{ route("patrimonios.atribuir.codigos") }}?' + params.toString();
        },
        toggleAll(event) {
          const source = event.target;
          const checkboxes = document.querySelectorAll('.patrimonio-checkbox');
          if (this.selectionEnabled) {
            this.bulkToggle = true;
          }
          checkboxes.forEach(cb => {
            cb.checked = source.checked;
            // Dispara evento change para notificar o footerAcoes()
            cb.dispatchEvent(new Event('change', {
              bubbles: true
            }));
          });
          if (this.selectionEnabled) {
            this.bulkToggle = false;
            this.saveSelection();
          }
          this.updateCounter();
        },
        updateCounter() {
          const checkboxes = document.querySelectorAll('.patrimonio-checkbox:checked');
          const count = checkboxes.length;
          this.contadorTexto = count === 0 ? '0 patrimônios selecionados' : `${count} patrimônio${count>1?'s':''} selecionado${count>1?'s':''}`;
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
          // Reaproveita lÃ³gica aplicarFiltros adicionando parametro codigoTermo
          const params = new URLSearchParams(window.location.search);
          if (this.codigoTermo) params.set('codigo', this.codigoTermo);
          else params.delete('codigo');
          window.location.href = '{{ route("patrimonios.atribuir.codigos") }}?' + params.toString();
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
            if (codigoCell && codigoCell.textContent.trim() && codigoCell.textContent.trim() !== '—') {
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

          console.log('🔽 DOWNLOAD INICIADO', {
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
          const checkboxes = document.querySelectorAll(`input.grupo-item-checkbox[data-grupo-id="${grupoId}"]:checked`);
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
            // Atualizar icone de rotação
            const header = document.querySelector(`tr[data-group-id="${groupId}"]`);
            if (header) {
              header.style.transition = 'background-color 0.2s ease';
            }
          });
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
    class="flex items-center justify-end gap-4">
    <template x-if="generatedCode">
      <div class="flex items-center gap-3 flex-wrap">
        <span class="code-badge px-4 py-2 rounded-lg bg-indigo-600 text-white font-bold text-lg" x-text="generatedCode"></span>
        <div class="flex items-center gap-2">
          <button type="button" @click="atribuir()" class="btn-green" :disabled="qtdSelecionados===0 || state!=='generated'">
            <span x-show="state==='generated'">✓ Atribuir</span>
            <span x-show="state==='assigning'" class="inline-flex items-center gap-2">
              <svg class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
              </svg>
              Atribuindo...
            </span>
          </button>
          <p class="text-sm font-bold text-white uppercase" x-show="state==='generated'">Selecione os patrimônios e clique em Atribuir.</p>
        </div>
      </div>
    </template>
  </div>
  @endif
  @if(request('status')=='indisponivel')
  <div x-data="footerDesatribuir()" x-init="init()"
    :class="qtdSelecionados > 0 ? 'footer-slide-in' : 'footer-slide-out'"
    x-show="qtdSelecionados > 0"
    class="flex items-center justify-end gap-4">
    <div class="flex items-center gap-3 flex-wrap">
      <button type="button" @click="executar()" class="btn-red flex items-center gap-2" :disabled="qtdSelecionados===0 || state==='processing'">
        <span x-show="state==='idle'">Desatribuir</span>
        <span x-show="state==='processing'" class="inline-flex items-center gap-2">
          <svg class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
          </svg>
          Processando...
        </span>
      </button>
      <p class="text-sm font-bold text-white uppercase" x-show="state==='idle'">Selecione os patrimônios e clique em Desatribuir.</p>
    </div>
  </div>
  @endif

  <script>
    function footerAcoes() {
      return {
        state: 'idle',
        loading: false,
        generatedCode: null,
        qtdSelecionados: 0,
        init() {
          this.wireCheckboxListener();
          // Auto-gerar código ao inicializar
          this.gerarAutomatico();
        },
        wireCheckboxListener() {
          const update = () => {
            this.qtdSelecionados = document.querySelectorAll("input.patrimonio-checkbox[name='ids[]']:checked").length;
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
            const ids = Array.from(document.querySelectorAll("input.patrimonio-checkbox[name='ids[]']:checked")).map(cb => cb.value);
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
              const row = document.querySelector(`tr[data-row-id='${id}']`);
              if (row) {
                const cell = row.querySelector('[data-col="codigo-termo"]');
                if (cell) {
                  cell.innerHTML = `<span class=\\"badge-indigo font-mono\\">${this.generatedCode}</span>`;
                }
                const cb = row.querySelector("input.patrimonio-checkbox[name='ids[]']");
                if (cb) {
                  cb.checked = false;
                  cb.disabled = true;
                }
              }
            });
            this.qtdSelecionados = 0;
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
