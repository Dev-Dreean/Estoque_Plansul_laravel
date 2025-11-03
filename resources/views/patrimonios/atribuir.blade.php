<x-app-layout>
  <div x-data="atribuirPage()" x-init="init()">
    <div class="py-12">
      <div class="w-full sm:px-6 lg:px-8">
        <!-- Mensagens de Feedback -->
        @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded relative" role="alert">
          <strong class="font-bold">Sucesso!</strong>
          <span class="block sm:inline">{{ session('success') }}</span>
        </div>
        
        @if(session('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded relative" role="alert">
          <strong class="font-bold">Erro!</strong>
          <span class="block sm:inline">{{ session('error') }}</span>
        </div>
        
        @if(session('warning'))
        <div class="mb-4 bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded relative" role="alert">
          <strong class="font-bold">Atenção!</strong>
          <span class="block sm:inline">{{ session('warning') }}</span>
        </div>
        
        @if($errors->any())
        <div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded relative" role="alert">
          <strong class="font-bold">Erro de Validação!</strong>
          <span class="block sm:inline">{{ $errors->first() }}</span>
        </div>
        

        <div class="section">
          <div class="section-body">
            <div class="space-y-6">
              <!-- Filtros (layout replicado do index) -->
              <div x-data="{ open: false }" class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg mb-6" x-id="['filtro-atribuir']" :aria-expanded="open.toString()" :aria-controls="$id('filtro-atribuir')">
                <div class="flex justify-between items-center">
                  <h3 class="font-semibold text-lg">Filtros de Busca</h3>
                  <button type="button" @click="open = !open" aria-expanded="open" aria-controls="$id('filtro-atribuir')" class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                    <span class="sr-only">Expandir filtros</span>
                  </button>
                </div>
                <div x-show="open" x-transition class="mt-4" x-cloak :id="$id('filtro-atribuir')">
                  <div class="flex flex-row gap-3 sm:gap-4">
                    <div class="flex-1 min-w-[150px]">
                      <input type="text" id="filtro_numero" name="filtro_numero" value="{{ request('filtro_numero') }}" placeholder="Nº Patr." class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                    </div>
                    <div class="flex-1 min-w-[150px]">
                      <input type="text" id="filtro_descricao" name="filtro_descricao" value="{{ request('filtro_descricao') }}" placeholder="Descrição" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                    </div>
                    <div class="flex-1 min-w-[150px]">
                      <input type="text" id="filtro_modelo" name="filtro_modelo" value="{{ request('filtro_modelo') }}" placeholder="Modelo" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                    </div>
                    <div class="flex-1 min-w-[150px]">
                      <input type="number" id="filtro_projeto" name="filtro_projeto" value="{{ request('filtro_projeto') }}" placeholder="Cód. Projeto" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                    </div>
                  </div>
                  <div class="flex flex-wrap items-center justify-between mt-4 gap-4">
                    <div class="flex items-center gap-3">
                      <button type="button" @click="aplicarFiltros()" class="btn-accent h-10">Filtrar</button>
                      <a href="{{ route('patrimonios.atribuir.codigos') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md">Ir para nova página</a>
                    </div>
                    <label class="flex items-center gap-2 ml-auto shrink-0">
                      <span class="text-sm text-gray-700 dark:text-gray-300">Itens por página</span>
                      <select id="per_page" name="per_page" class="h-10 px-2 pr-8 w-24 border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm">
                        @foreach([15,30,50,100] as $opt)
                        <option value="{{ $opt }}" @selected(request('per_page',15)==$opt)>{{ $opt }}</option>
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
                  <a href="{{ route('patrimonios.atribuir.codigos', array_merge(request()->except('page','status'), ['status'=>'disponivel'])) }}" class="text-[11px] px-3 py-2 rounded-md font-semibold border transition {{ request('status','disponivel')=='disponivel' ? 'bg-green-600 text-white border-green-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-600 hover:bg-green-600/10' }}">Disponíveis</a>
                  <a href="{{ route('patrimonios.atribuir.codigos', array_merge(request()->except('page','status'), ['status'=>'indisponivel'])) }}" class="text-[11px] px-3 py-2 rounded-md font-semibold border transition {{ request('status')=='indisponivel' ? 'bg-red-600 text-white border-red-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-600 hover:bg-red-600/10' }}">Atribuídos</a>
                </div>

                <!-- Centro: contador quando houver seleção -->
                <div class="flex-1 min-w-[1rem]"></div>
                <template x-if="selectedPatrimonios.length > 0">
                  <span id="contador-selecionados-tabs" class="text-[11px] text-muted" x-text="contadorTexto"></span>
                </template>

                <!-- Direita: Gerar Planilha Termo e Termo DOCX -->
                <div class="ml-auto flex gap-2">
                  <button type="button" @click="$dispatch('open-termo-modal')" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded inline-flex items-center" title="Gerar Planilha Termo">
                    <x-heroicon-o-printer class="w-5 h-5 mr-2" />
                    <span>Gerar Planilha Termo</span>
                  </button>
                  <button type="button" @click="downloadTermoDocx()" x-show="selectedPatrimonios.length > 0" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded inline-flex items-center" title="Baixar Termo de Responsabilidade (DOCX)">
                    <x-heroicon-o-document-text class="w-5 h-5 mr-2" />
                    <span>Termo DOCX</span>
                  </button>
                </div>
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
              <table class="w-full text-base text-left text-gray-500 dark:text-gray-400">
                {{-- MODO DISPONÍVEIS: Tabela Normal --}}
                @if(!request('status') || request('status')=='disponivel')
                <thead class="text-xs text-gray-100 uppercase bg-gray-700 dark:bg-gray-700 dark:text-gray-100 border-b border-gray-600 dark:border-gray-600">
                  <tr>
                    <th class="px-4 py-3" style="width: 50px;">
                      <input type="checkbox" class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-600" @change="toggleAllCheckboxes($event)">
                    </th>
                    <th class="px-4 py-3">Nº Pat.</th>
                    <th class="px-4 py-3">Itens</th>
                    <th class="px-4 py-3">Modelo</th>
                    <th class="px-4 py-3">Situação</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($patrimonios as $patrimonio)
                  <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/30 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                    data-row-id="{{ $patrimonio->NUSEQPATR }}">
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                      <div class="flex items-center justify-center">
                        @if(empty($patrimonio->NMPLANTA))
                        <input class="patrimonio-checkbox h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-600"
                          type="checkbox" name="ids[]" value="{{ $patrimonio->NUSEQPATR }}" @change="updateCounter()">
                        
                      </div>
                    </td>
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">
                      {{ $patrimonio->NUPATRIMONIO }}
                    </td>
                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300 max-w-xs truncate" :title="'{{ $patrimonio->DEPATRIMONIO }}'">
                      {{ Str::limit($patrimonio->DEPATRIMONIO, 50) }}
                    </td>
                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                      {{ $patrimonio->MODELO ?? '—' }}
                    </td>
                    <td class="px-4 py-3">
                      @if(empty($patrimonio->NMPLANTA))
                      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-100 text-xs font-medium">
                        <span class="w-2 h-2 rounded-full bg-green-600 dark:bg-green-400"></span>
                        Disponível
                      </span>
                      
                    </td>
                  </tr>
                  @empty
                  <tr>
                    <td colspan="5" class="px-6 py-12 text-center">
                      <div class="flex flex-col items-center justify-center text-gray-600 dark:text-gray-400">
                        <svg class="w-12 h-12 mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                        </svg>
                        <h3 class="text-base font-semibold mb-1">Nenhum patrimônio encontrado</h3>
                        <p class="text-sm">Não há patrimônios disponíveis para atribuição ou nenhum atende aos filtros aplicados.</p>
                      </div>
                    </td>
                  </tr>
                  @endforelse
                </tbody>
                {{-- MODO ATRIBUÍDOS: Tabela Agrupada por Termo --}}
                @else
                <tbody>
                  @forelse($patrimonios_grouped as $grupo_codigo => $grupo_patrimonios)
                  @php
                  $grupo_id = 'grupo_' . ($grupo_codigo === '__sem_termo__' ? 'sem_termo' : $grupo_codigo);
                  $item_count = $grupo_patrimonios->count();
                  $is_sem_termo = $grupo_codigo === '__sem_termo__';
                  @endphp

                  {{-- Cabeçalho Colapsável do Grupo --}}
                  <tr class="group-header border-b-2 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition cursor-pointer"
                    data-group-id="{{ $grupo_id }}"
                    @click="toggleGroup('{{ $grupo_id }}')"
                    :data-expanded="groupState['{{ $grupo_id }}'] === true ? 'true' : 'false'">
                    <td colspan="5" class="px-4 py-4">
                      <div class="flex items-center justify-between gap-4">
                        {{-- Ícone de Expandir + Info do Grupo --}}
                        <div class="flex items-center gap-4 flex-1 min-w-0">
                          <button type="button"
                            class="flex-shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition transform"
                            :class="{ 'rotate-180': groupState['{{ $grupo_id }}'] === true }"
                            @click.stop="toggleGroup('{{ $grupo_id }}')">
                            <svg class="w-4 h-4 text-gray-700 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                          </button>

                          <div class="flex items-center gap-3 flex-1 min-w-0">
                            @if(!$is_sem_termo)
                            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-gray-700 dark:bg-gray-700 border border-gray-600 dark:border-gray-600 flex-shrink-0">
                              <span class="text-sm font-semibold text-gray-100 dark:text-gray-100">Termo {{ $grupo_codigo }}</span>
                            </span>
                            @else
                            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-amber-100 dark:bg-amber-900 border border-amber-300 dark:border-amber-700 flex-shrink-0">
                              <span class="text-sm font-semibold text-amber-900 dark:text-amber-100">Sem Termo</span>
                            </span>
                            

                            {{-- Lista de itens como badges individuais --}}
                            <div class="flex flex-wrap gap-2 flex-shrink">
                              @foreach($grupo_patrimonios->pluck('DEPATRIMONIO')->take(5) as $item)
                              <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-purple-900/40 dark:bg-purple-900/40 text-purple-200 dark:text-purple-200 border border-purple-600/50 dark:border-purple-600/50 whitespace-nowrap">
                                {{ Str::limit($item, 30) }}
                              </span>
                              @endforeach
                              @if($item_count > 5)
                              <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-gray-700 dark:bg-gray-700 text-gray-200 dark:text-gray-200 border border-gray-600/50 dark:border-gray-600/50">
                                +{{ $item_count - 5 }} mais
                              </span>
                              
                            </div>
                          </div>
                        </div>

                        {{-- Botões de Ação (Baixar e Desatribuir) --}}
                        <div class="flex-shrink-0 flex gap-2 items-center">
                          {{-- Badge de Quantidade --}}
                          <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-transparent dark:bg-transparent border-2 border-white dark:border-white">
                            <span class="text-sm font-bold text-purple-300 dark:text-purple-300">{{ $item_count }}</span>
                            <span class="text-xs font-semibold text-purple-300 dark:text-purple-300">{{ $item_count === 1 ? 'item' : 'itens' }}</span>
                          </span>

                          @if(!$is_sem_termo && $grupo_patrimonios->first()?->CDMATRFUNCIONARIO)
                          {{-- Botão Baixar --}}
                          <form method="POST" action="{{ route('termos.docx.batch') }}" style="display: inline;" @click.stop>
                            @csrf
                            @foreach($grupo_patrimonios as $p)
                            <input type="hidden" name="ids[]" value="{{ $p->NUSEQPATR }}">
                            @endforeach
                            <button type="submit"
                              class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-gray-100 dark:text-gray-100 bg-blue-700 dark:bg-blue-800 rounded-lg border border-blue-600 dark:border-blue-700 hover:bg-blue-800 dark:hover:bg-blue-900 transition whitespace-nowrap"
                              title="Baixar Termo DOCX para todo o grupo">
                              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                              </svg>
                              <span>Baixar</span>
                            </button>
                          </form>

                          {{-- Botão Desatribuir --}}
                          <form method="POST" action="{{ route('patrimonios.atribuir.processar') }}" style="display: inline;" @click.stop>
                            @csrf
                            @foreach($grupo_patrimonios as $p)
                            <input type="hidden" name="ids[]" value="{{ $p->NUSEQPATR }}">
                            @endforeach
                            <input type="hidden" name="desatribuir" value="1">
                            <button type="submit"
                              class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-gray-100 dark:text-gray-100 bg-red-700 dark:bg-red-800 rounded-lg border border-red-600 dark:border-red-700 hover:bg-red-800 dark:hover:bg-red-900 transition whitespace-nowrap"
                              title="Desatribuir todos os itens do termo"
                              onclick="return confirm('Tem certeza que deseja desatribuir todos os itens deste termo?')">
                              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                              </svg>
                              <span>Desatribuir</span>
                            </button>
                          </form>
                          
                        </div>
                      </div>
                    </td>
                  </tr>

                  {{-- Header do Grupo (Colunas) --}}
                  <tr class="text-xs text-gray-100 uppercase bg-gray-700 dark:bg-gray-700 dark:text-gray-100 border-b border-gray-600 dark:border-gray-600"
                    x-show="groupState['{{ $grupo_id }}'] === true"
                    style="display: none;">
                    <th class="px-4 py-3">
                      @if(!request('status') || request('status')=='disponivel')
                      <input type="checkbox" class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-600" @change="toggleGroupCheckboxes('{{ $grupo_id }}', $event)">
                      
                    </th>
                    <th class="px-4 py-3">Nº Pat.</th>
                    <th class="px-4 py-3">Itens</th>
                    <th class="px-4 py-3">Modelo</th>
                    <th class="px-4 py-3">Situação</th>
                  </tr>

                  {{-- Detalhes do Grupo (Linhas dos Itens) --}}
                  @foreach($grupo_patrimonios as $patrimonio)
                  <tr class="group-details border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/30 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                    data-group-id="{{ $grupo_id }}"
                    data-row-id="{{ $patrimonio->NUSEQPATR }}"
                    x-show="groupState['{{ $grupo_id }}'] === true"
                    style="display: none;">
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                      <div class="flex items-center justify-center">
                        @if((!request('status') || request('status')=='disponivel') && empty($patrimonio->NMPLANTA))
                        <input class="patrimonio-checkbox h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-600"
                          type="checkbox" name="ids[]" value="{{ $patrimonio->NUSEQPATR }}" @change="updateCounter()">
                        @elseif(request('status')=='indisponivel' && !empty($patrimonio->NMPLANTA))
                        <input class="patrimonio-checkbox h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-600"
                          type="checkbox" name="ids[]" value="{{ $patrimonio->NUSEQPATR }}" @change="updateCounter()">
                        
                      </div>
                    </td>
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">
                      {{ $patrimonio->NUPATRIMONIO }}
                    </td>
                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300 max-w-xs truncate" :title="'{{ $patrimonio->DEPATRIMONIO }}'">
                      {{ Str::limit($patrimonio->DEPATRIMONIO, 50) }}
                    </td>
                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                      {{ $patrimonio->MODELO ?? '—' }}
                    </td>
                    <td class="px-4 py-3">
                      @if(empty($patrimonio->NMPLANTA))
                      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-100 text-xs font-medium">
                        <span class="w-2 h-2 rounded-full bg-green-600 dark:bg-green-400"></span>
                        Disponível
                      </span>
                      @else
                      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-100 text-xs font-medium">
                        <span class="w-2 h-2 rounded-full bg-red-600 dark:bg-red-400"></span>
                        Atribuído
                      </span>
                      
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
                          
                        </p>
                      </div>
                    </td>
                  </tr>
                  @endforelse
                </tbody>
                
              </table>
            </div>
            <div class="mt-4">
              {{ $patrimonios->appends(request()->query())->links() }}
            </div>
          </div> <!-- /mb-6 flex flex-col gap-6 -->
        </div> <!-- /space-y-6 -->
        </form>
      </div>
    </div>
  </div><!-- /w-full wrapper -->
  </div><!-- /py-12 wrapper -->

  {{-- Modal: Gerar Planilha por Termo --}}
  <div x-data="{ open:false }" @open-termo-modal.window="open=true" x-show="open" x-transition class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center" style="display: none;">
    <div @click.outside="open = false" class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md p-6">
      <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Gerar Planilha por Termo</h3>
      <form action="{{ route('termos.exportar.excel') }}" method="POST" @submit="open=false">
        @csrf
        <div class="mb-4">
          <label for="cod_termo" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Cód Termo:</label>
          <input type="number" id="cod_termo" name="cod_termo" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm" required>
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" @click="open = false" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500">Sair</button>
          <x-primary-button type="submit">Gerar</x-primary-button>
        </div>
      </form>
    </div>
  </div>

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
    function atribuirPage() {
      return {
        // AnimaÃ§Ã£o custom: classe usada: animate-fadeInScale
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
        // Estados de listagem de cÃ³digos removidos (modal removido)
        init() {
          this.updateCounter();
          // ESC para fechar popover e modais leves
          window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
              if (this.showCodigosModal) this.showCodigosModal = false;
            }
          });
        },
        syncCodigoTermo(value) {
          this.codigoTermo = value;
        },
        aplicarFiltros() {
          const params = new URLSearchParams(window.location.search);
          // Limpa filtros antigos para reconstruir
          ['filtro_numero', 'filtro_descricao', 'filtro_modelo', 'filtro_projeto', 'per_page'].forEach(k => params.delete(k));
          const numero = document.getElementById('filtro_numero')?.value;
          const descricao = document.getElementById('filtro_descricao')?.value;
          const modelo = document.getElementById('filtro_modelo')?.value;
          const projeto = document.getElementById('filtro_projeto')?.value;
          const perPage = document.getElementById('per_page')?.value;
          if (numero) params.set('filtro_numero', numero);
          if (descricao) params.set('filtro_descricao', descricao);
          if (modelo) params.set('filtro_modelo', modelo);
          if (projeto) params.set('filtro_projeto', projeto);
          if (perPage) params.set('per_page', perPage);
          else params.delete('per_page');
          window.location.href = '{{ route("patrimonios.atribuir.codigos") }}?' + params.toString();
        },
        toggleAll(event) {
          const source = event.target;
          const checkboxes = document.querySelectorAll('.patrimonio-checkbox');
          checkboxes.forEach(cb => cb.checked = source.checked);
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
            form.action = '{{ route("termos.docx.batch") }}';
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
        confirmarAtribuicao() {
          const checkboxes = document.querySelectorAll('.patrimonio-checkbox:checked');
          if (checkboxes.length === 0) {
            alert('Selecione pelo menos um patrimônio para atribuir.');
            return;
          }
          if (!this.codigoTermo) {
            this.erroCodigo = true;
            return;
          }
          this.erroCodigo = false;
          this.selectedPatrimonios = Array.from(checkboxes).map(cb => cb.value);
          this.processarAtribuicao();
        },
        async processarAtribuicao() {
          if (!this.codigoTermo || this.selectedPatrimonios.length === 0) return;
          this.atribuindo = true;
          try {
            const res = await fetch("{{ route('patrimonios.atribuir.processar') }}", {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
              },
              body: JSON.stringify({
                patrimonios: this.selectedPatrimonios,
                codigo_termo: this.codigoTermo
              })
            });
            if (res.ok) {
              const json = await res.json().catch(() => ({
                updated_ids: this.selectedPatrimonios
              }));
              // Atualiza linhas afetadas inline
              this.selectedPatrimonios.forEach(id => {
                const row = document.querySelector(`tr[data-row-id='${id}']`);
                if (row) {
                  // Status chip
                  const statusTd = row.children[4];
                  if (statusTd) {
                    statusTd.innerHTML = '<span class="inline-flex items-center rounded-full bg-red-600/15 px-2 py-0.5 text-[11px] font-medium text-red-400 ring-1 ring-inset ring-red-500/30">Atribuído</span>';
                  }
                  // Código termo
                  const codigoTd = row.children[5];
                  if (codigoTd) {
                    codigoTd.innerHTML = `<span class=\"inline-flex items-center h-6 px-2 rounded bg-indigo-600/20 text-indigo-300 text-[11px] font-medium border border-indigo-500/30 font-mono\">${this.codigoTermo}</span>`;
                  }
                  // Checkbox (remove)
                  const cb = row.querySelector('input.patrimonio-checkbox');
                  cb?.remove();
                  row.classList.add('row-just-updated');
                  setTimeout(() => row.classList.remove('row-just-updated'), 3000);
                }
              });
              window.dispatchEvent(new CustomEvent('toast', {
                detail: {
                  type: 'success',
                  message: 'Código atribuído com sucesso',
                  code: this.codigoTermo,
                  count: (json.updated_ids || this.selectedPatrimonios).length
                }
              }));
              this.updatedIds = [...this.selectedPatrimonios];
              setTimeout(() => {
                this.updatedIds = [];
                document.querySelectorAll('.row-just-updated').forEach(r => r.classList.remove('row-just-updated'));
              }, 3000);
              this.selectedPatrimonios = [];
              this.updateCounter();
            } else {
              alert('Falha ao atribuir.');
            }
          } catch (e) {
            console.error(e);
            alert('Erro inesperado.');
          } finally {
            this.atribuindo = false;
          }
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
        processarDesatribuicao() {
          if (!this.desatribuirItem) return;
          let ids = [];
          if (this.desatribuirItem.id.includes(',')) {
            // Lote (caso futuro) - usa helper
            ids = this.selectedPatrimoniosAtribuidos();
          } else {
            ids = [this.desatribuirItem.id];
          }
          const form = document.createElement('form');
          form.method = 'POST';
          form.action = '{{ route("patrimonios.atribuir.processar") }}';
          const csrfToken = document.createElement('input');
          csrfToken.type = 'hidden';
          csrfToken.name = '_token';
          csrfToken.value = '{{ csrf_token() }}';
          form.appendChild(csrfToken);
          ids.forEach(id => {
            const patrimonioInput = document.createElement('input');
            patrimonioInput.type = 'hidden';
            patrimonioInput.name = 'ids[]';
            patrimonioInput.value = id;
            form.appendChild(patrimonioInput);
          });
          const desatribuirInput = document.createElement('input');
          desatribuirInput.type = 'hidden';
          desatribuirInput.name = 'desatribuir';
          desatribuirInput.value = '1';
          form.appendChild(desatribuirInput);
          document.body.appendChild(form);
          form.submit();
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
  <!-- Modal de cÃ³digos ativo (popover legado removido) -->
  @section('footer-actions')
  @if(!request('status') || request('status')=='disponivel')
  <div x-data="footerAcoes()" x-init="init()" class="flex items-center justify-end gap-4">
    <template x-if="state==='idle'">
      <div class="flex items-center gap-3 flex-wrap">
        <div class="flex items-center gap-3">
          <button type="button" @click="gerar()" class="btn-accent" :disabled="loading">
            <span x-show="!loading">+ Gerar Código</span>
            <span x-show="loading" class="inline-flex items-center gap-2">
              <svg class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
              </svg>
              Gerando...
            </span>
          </button>
          <p class="text-xs text-gray-300">Clique em Gerar para criar ou reutilizar um código disponível.</p>
        </div>
      </div>
    </template>

    <template x-if="state!=='idle'">
      <div class="flex items-center gap-3 flex-wrap">
        <span class="code-badge" x-text="generatedCode"></span>
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
          <p class="text-xs text-gray-300" x-show="state==='generated'">Selecione os patrimônios e clique em Atribuir.</p>
        </div>
      </div>
    </template>
  </div>
  
  @if(request('status')=='indisponivel')
  <div x-data="footerDesatribuir()" x-init="init()" class="flex items-center justify-end gap-4">
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
      <p class="text-xs text-gray-300" x-show="state==='idle'">Selecione os patrimônios e clique em Desatribuir.</p>
    </div>
  </div>
  

  <script>
    function footerAcoes() {
      return {
        state: 'idle',
        loading: false,
        generatedCode: null,
        qtdSelecionados: 0,
        init() {
          this.wireCheckboxListener();
        },
        wireCheckboxListener() {
          const update = () => {
            this.qtdSelecionados = document.querySelectorAll("input.patrimonio-checkbox[name='ids[]']:checked").length;
          };
          document.addEventListener('change', e => {
            if (e.target.matches("input.patrimonio-checkbox[name='ids[]']")) update();
          });
          update();
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
            this.state = 'generated';
          } catch (e) {
            console.error(e);
            alert('Erro inesperado');
            this.state = 'generated';
          }
        },
        cancelar() {
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
            this.qtdSelecionados = document.querySelectorAll("input.patrimonio-checkbox[name='ids[]']:checked").length;
          };
          document.addEventListener('change', e => {
            if (e.target.matches("input.patrimonio-checkbox[name='ids[]']")) u();
          });
          u();
        },
        async executar() {
          if (this.qtdSelecionados === 0) return;
          this.state = 'processing';
          try {
            const ids = Array.from(document.querySelectorAll("input.patrimonio-checkbox[name='ids[]']:checked")).map(cb => cb.value);
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
            json.updated_ids.forEach(id => {
              const row = document.querySelector(`tr[data-row-id='${id}']`);
              if (row) {
                const status = row.children[4];
                if (status) {
                  status.innerHTML = '<span class="badge-green">Disponível</span>';
                }
                const codigo = row.children[5];
                if (codigo) {
                  codigo.innerHTML = '<span class="text-muted">—</span>';
                }
                row.classList.add('row-just-updated');
                setTimeout(() => row.classList.remove('row-just-updated'), 3000);
                const cbCell = row.children[0];
                if (cbCell && !cbCell.querySelector('input')) {
                  const input = document.createElement('input');
                  input.type = 'checkbox';
                  input.className = 'patrimonio-checkbox h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-600';
                  input.name = 'ids[]';
                  input.value = id;
                  cbCell.appendChild(input);
                }
                // Se estamos na aba de itens atribuídos (indisponivel) removemos a linha para que suma imediatamente
                if (new URLSearchParams(window.location.search).get('status') === 'indisponivel') {
                  const tbody = row.parentElement;
                  row.remove();
                  // Se ficou vazio, insere linha de "nenhum"
                  if (tbody && tbody.children.length === 0) {
                    const tr = document.createElement('tr');
                    tr.innerHTML = '<td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-300">Nenhum patrimônio atribuído restante.</td>';
                    tbody.appendChild(tr);
                  }
                }
              }
            });
            this.limparSelecao();
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
