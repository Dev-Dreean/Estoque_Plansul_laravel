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
            <!-- Filtros -->
            <div x-data="{ open: false }" class="card mb-3">
              <div class="flex items-center w-full">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Filtros</h4>
                <div class="flex-1"></div>
                <button type="button" @click="open = !open" class="inline-flex items-center justify-center w-7 h-7 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </button>
              </div>
              <div x-show="open" x-transition class="mt-4" x-cloak>
                <div class="grid gap-3 sm:gap-4 grid-[repeat(auto-fit,minmax(150px,1fr))]">
                  <div><input type="text" id="filtro_numero" name="filtro_numero" value="{{ request('filtro_numero') }}" placeholder="Nº Patr." class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" /></div>
                  <div><input type="text" id="filtro_descricao" name="filtro_descricao" value="{{ request('filtro_descricao') }}" placeholder="Descrição" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" /></div>
                  <div><input type="text" id="filtro_modelo" name="filtro_modelo" value="{{ request('filtro_modelo') }}" placeholder="Modelo" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" /></div>
                  <div><input type="number" id="filtro_projeto" name="filtro_projeto" value="{{ request('filtro_projeto') }}" placeholder="Cód. Projeto" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" /></div>
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
            <!-- Tabs abaixo do card de filtros -->
            <div class="flex items-center gap-3 mb-6 mt-2" x-data="{ }">
              <div class="flex items-center gap-2">
                <a href="{{ route('patrimonios.atribuir.codigos', array_merge(request()->except('page','status'), ['status'=>'disponivel'])) }}" class="text-[11px] px-3 py-2 rounded-md font-semibold border transition {{ request('status','disponivel')=='disponivel' ? 'bg-green-600 text-white border-green-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-600 hover:bg-green-600/10' }}">Disponíveis</a>
                <a href="{{ route('patrimonios.atribuir.codigos', array_merge(request()->except('page','status'), ['status'=>'indisponivel'])) }}" class="text-[11px] px-3 py-2 rounded-md font-semibold border transition {{ request('status')=='indisponivel' ? 'bg-red-600 text-white border-red-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-600 hover:bg-red-600/10' }}">Atribuídos</a>
              </div>
              <div class="flex-1"></div>
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

          <!-- Tabela de Patrimônios -->
          <div class="mb-6">
            <h3 class="text-lg font-medium mb-4 text-gray-900 dark:text-gray-100">
              Patrimônios
              @if(request('status') == 'disponivel')
              Disponíveis
              @elseif(request('status') == 'indisponivel')
              Indisponíveis (Atribuídos)
              @endif
            </h3>

            <!-- Container com scroll dinÃ¢mico -->
            <div x-ref="tableWrapper" class="overflow-y-auto overflow-x-auto w-full border border-gray-200 dark:border-gray-700 rounded-lg max-h-[var(--table-max-h,70vh)]" :style="tableHeight ? '--table-max-h:'+tableHeight+'px' : ''">
              <table class="w-full text-base text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 sticky top-0 z-10">
                  <tr>
                    <th class="px-4 py-3">
                      @if(!request('status') || request('status')=='disponivel')
                      <input type="checkbox" id="selectAll" @change="toggleAll($event)"
                        class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-600">
                      @endif
                    </th>
                    <th class="px-4 py-3">Nº Pat.</th>
                    <th class="px-4 py-3">Descrição</th>
                    <th class="px-4 py-3">Modelo</th>
                    <th class="px-4 py-3">Situação</th>
                    <th class="px-4 py-3">Código Termo</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($patrimonios as $patrimonio)
                  <tr data-row-id="{{ $patrimonio->NUSEQPATR }}" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 text-sm cursor-pointer transition-colors" :class="{'!border-green-500 bg-green-50 dark:bg-gray-700/40': selectedPatrimonios.includes('{{ $patrimonio->NUSEQPATR }}')}">
                    <td class="px-4 py-3">
                      @if((!request('status') || request('status')=='disponivel') && empty($patrimonio->NMPLANTA))
                      <input class="patrimonio-checkbox h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-600"
                        type="checkbox" name="ids[]" value="{{ $patrimonio->NUSEQPATR }}" @change="updateCounter()">
                      @elseif(request('status')=='indisponivel' && !empty($patrimonio->NMPLANTA))
                      <input class="patrimonio-checkbox h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-600"
                        type="checkbox" name="ids[]" value="{{ $patrimonio->NUSEQPATR }}" @change="updateCounter()">
                      @endif
                    </td>
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                      {{ $patrimonio->NUPATRIMONIO }}
                    </td>
                    <td class="px-4 py-3">
                      {{ Str::limit($patrimonio->DEPATRIMONIO, 50) }}
                    </td>
                    <td class="px-4 py-3">
                      {{ $patrimonio->MODELO ?? 'N/A' }}
                    </td>
                    <td class="px-4 py-3">
                      @if(empty($patrimonio->NMPLANTA))
                      <span class="badge-green">Disponível</span>
                      @else
                      <span class="badge-red">Atribuído</span>
                      @endif
                    </td>
                    <td class="px-4 py-3" data-col="codigo-termo">
                      @if($patrimonio->NMPLANTA)
                      <span class="badge-indigo font-mono">{{ $patrimonio->NMPLANTA }}</span>
                      @else
                      <span class="text-muted">—</span>
                      @endif
                    </td>
                  </tr>
                  @empty
                  <tr>
                    <td colspan="6" class="px-6 py-8 text-center">
                      <div class="text-gray-700 dark:text-gray-300">
                        <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                          <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium">Nenhum patrimônio encontrado</h3>
                        <p class="mt-1 text-sm">
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
            </div>

            <!-- InformaÃ§Ãµes de PaginaÃ§Ã£o (Fora do scroll) -->
            @if($patrimonios->hasPages())
            <div class="flex items-center justify-between border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 sm:px-6 mt-4 rounded-b-lg">
              <div class="flex flex-1 justify-between sm:hidden">
                @if($patrimonios->onFirstPage())
                <span class="relative inline-flex items-center rounded-md border bd-theme bg-surface px-4 py-2 text-sm font-medium text-muted">Anterior</span>
                @else
                <a href="{{ $patrimonios->previousPageUrl() }}" class="relative inline-flex items-center rounded-md border bd-theme bg-surface px-4 py-2 text-sm font-medium text-on-surface hover-surface-alt">Anterior</a>
                @endif

                @if($patrimonios->hasMorePages())
                <a href="{{ $patrimonios->nextPageUrl() }}" class="relative ml-3 inline-flex items-center rounded-md border bd-theme bg-surface px-4 py-2 text-sm font-medium text-on-surface hover-surface-alt">Próximo</a>
                @else
                <span class="relative ml-3 inline-flex items-center rounded-md border bd-theme bg-surface px-4 py-2 text-sm font-medium text-muted">Próximo</span>
                @endif
              </div>
              <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                <div>
                  <p class="text-sm text-gray-700 dark:text-gray-300">
                    Mostrando
                    <span class="font-medium">{{ $patrimonios->firstItem() ?? 0 }}</span>
                    a
                    <span class="font-medium">{{ $patrimonios->lastItem() ?? 0 }}</span>
                    de
                    <span class="font-medium">{{ $patrimonios->total() }}</span>
                    resultados
                  </p>
                </div>
                <div>
                  {{ $patrimonios->appends(request()->query())->links() }}
                </div>
              </div>
            </div>
            @endif
          </div>
          </form>
        </div>
      </div>
    </div><!-- /w-full wrapper -->
  </div><!-- /py-12 wrapper -->

  <!-- Modal de Confirmação de Atribuição (mantido caso necessário futuramente) -->
  <!-- Modal de Confirmação de Atribuição -->
  <div x-show="showConfirmModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
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
                <p class="text-sm text-muted">
                  Você tem certeza que deseja atribuir <span x-text="selectedPatrimonios.length" class="font-semibold"></span> patrimônio(s) ao código de termo <span x-text="codigoTermo" class="font-mono font-semibold"></span>?
                </p>
                <p class="text-xs text-muted mt-1">
                  Esta ação não pode ser desfeita facilmente.
                </p>
              </div>
            </div>
          </div>
        </div>
        <div class="bg-surface-alt px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button type="button" @click="processarAtribuicao()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
            Sim, Atribuir
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
        tableHeight: null,
        atribuindo: false,
        erroCodigo: false,
        gerandoCodigo: false,
        // Estados de listagem de cÃ³digos removidos (modal removido)
        init() {
          this.updateCounter();
          this.calcTableHeight();
          window.addEventListener('resize', () => this.calcTableHeight());
          // ESC para fechar popover e modais leves
          window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
              if (this.showCodigosModal) this.showCodigosModal = false;
            }
          });
        },
        calcTableHeight() {
          this.$nextTick(() => {
            const wrapper = this.$refs.tableWrapper;
            if (!wrapper) return;
            const rect = wrapper.getBoundingClientRect();
            // EspaÃ§o inferior reservado (paginaÃ§Ã£o + padding da pÃ¡gina)
            const bottomPadding = 160; // ajuste fino se necessÃ¡rio
            const available = window.innerHeight - rect.top - bottomPadding;
            this.tableHeight = available > 300 ? available : 300; // mÃ­nimo seguro
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
                  code: this.generatedCode,
                  count: json.updated_ids.length
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
  @endif
  @if(request('status')=='indisponivel')
  <div x-data="footerDesatribuir()" x-init="init()" class="flex items-center justify-end gap-4">
    <div class="flex items-center gap-3 flex-wrap">
      <button type="button" @click="executar()" class="btn-red" :disabled="qtdSelecionados===0 || state==='processing'">
        <span x-show="state==='idle'">Desatribuir</span>
        Processando...
        </span>
      </button>
      <p class="text-xs text-gray-300" x-show="state==='idle'">Selecione os patrimônios e clique em Desatribuir.</p>
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