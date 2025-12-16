<x-app-layout>
  {{-- Abas de navegação do patrimônio --}}
  <x-patrimonio-nav-tabs />

  <div class="py-12">
    <div class="w-full sm:px-6 lg:px-8">
      <div class="section">
        <div class="section-body" x-data="removidosIndex()" @keydown.escape.window="fecharTudo()" @resize.window="onResize()">
          <div class="mb-4">
            <h2 class="text-xl font-bold text-app">Removidos</h2>
            <p class="text-sm text-muted">
              Conferência de registros excluídos por qualquer usuário.
            </p>
          </div>

          @if(session('success'))
          <div class="mb-6 p-4 rounded-lg bg-surface border border-app border-l-4 text-app" style="border-left-color: var(--ok);">
            {{ session('success') }}
          </div>
          @endif

          @if(session('error'))
          <div class="mb-6 p-4 rounded-lg bg-surface border border-app border-l-4 text-app" style="border-left-color: var(--danger);">
            {{ session('error') }}
          </div>
          @endif

          @if(($setupMissing ?? false) === true)
          <div class="mb-6 p-4 rounded-lg bg-surface border border-app border-l-4 text-app" style="border-left-color: var(--warn);">
            <div class="font-semibold">Auditoria de removidos nao instalada</div>
            <div class="text-sm mt-1">
              A tabela <span class="font-mono">registros_removidos</span> ainda nao existe neste banco. Rode: <span class="font-mono">php artisan migrate --path=database/migrations/2025_12_15_000000_create_registros_removidos_table.php</span>.
            </div>
          </div>
          @endif

          <div x-data="{ open: false }" @click.outside="open = false" class="bg-surface border border-app p-3 rounded-lg mb-3">
            <div class="flex justify-between items-center gap-3">
              <h3 class="font-semibold text-lg text-app">Filtros de Busca</h3>
              <button type="button" @click="open = !open" :aria-expanded="open.toString()" class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-app bg-surface hover:bg-[var(--surface-2)] transition focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
                <span class="sr-only">Expandir filtros</span>
              </button>
            </div>

            <div x-cloak x-show="open" x-transition class="mt-4">
              <form method="GET" action="{{ route('removidos.index') }}" @submit="open=false">
                <div class="flex flex-wrap gap-3 lg:gap-4 overflow-visible pb-2 w-full mt-3 pt-3 border-t border-app">
                  <div class="flex-1 min-w-[200px] basis-[260px]">
                    <label for="q" class="sr-only">Buscar</label>
                    <input id="q" name="q" value="{{ request('q') }}" placeholder="Buscar (id, descrição, usuário...)" class="input-base h-10" />
                  </div>

                  <div class="flex-1 min-w-[160px] max-w-[220px] basis-[190px]">
                    <label for="entity" class="sr-only">Tipo</label>
                    <select id="entity" name="entity" class="input-base h-10">
                      <option value="">Todos os tipos</option>
                      @foreach($entities as $ent)
                      <option value="{{ $ent }}" @selected(request('entity') === $ent)>{{ mb_strtoupper($ent) }}</option>
                      @endforeach
                    </select>
                  </div>

                  <div class="flex-1 min-w-[170px] max-w-[240px] basis-[210px]">
                    <label for="deleted_by" class="sr-only">Excluído por</label>
                    <input id="deleted_by" name="deleted_by" value="{{ request('deleted_by') }}" placeholder="Excluído por" class="input-base h-10" />
                  </div>

                  <div class="flex-1 min-w-[160px] max-w-[200px] basis-[180px]">
                    <label for="data_inicio" class="sr-only">Data início</label>
                    <input type="date" id="data_inicio" name="data_inicio" value="{{ request('data_inicio') }}" class="input-base h-10" />
                  </div>

                  <div class="flex-1 min-w-[160px] max-w-[200px] basis-[180px]">
                    <label for="data_fim" class="sr-only">Data fim</label>
                    <input type="date" id="data_fim" name="data_fim" value="{{ request('data_fim') }}" class="input-base h-10" />
                  </div>

                  <div class="flex-1 min-w-[140px] max-w-[170px] basis-[150px]">
                    <label for="per_page" class="sr-only">Por página</label>
                    <select id="per_page" name="per_page" class="input-base h-10">
                      @foreach([25,50,100,200] as $pp)
                      <option value="{{ $pp }}" @selected((int)request('per_page', 50) === $pp)>{{ $pp }}/pág</option>
                      @endforeach
                    </select>
                  </div>
                </div>

                <div class="flex items-center gap-3 mt-4">
                  <x-primary-button class="h-10 px-4">{{ __('Filtrar') }}</x-primary-button>
                  <a href="{{ route('removidos.index') }}" class="text-sm text-muted hover:text-app rounded-md">Limpar</a>
                </div>
              </form>
            </div>
          </div>

          @php
            $entityLabels = [
              'patrimonios' => 'Patrimônios',
              'locais' => 'Locais',
              'bens' => 'Bens',
              'usuarios' => 'Usuários',
              'outros' => 'Outros',
            ];
          @endphp

          <div class="overflow-x-auto rounded-lg border border-app bg-surface">
            <table class="min-w-full text-sm text-left">
              <thead class="bg-surface-2 text-muted">
                <tr>
                  <th class="px-3 py-3">Tipo</th>
                  <th class="px-3 py-3">Registro</th>
                  <th class="px-3 py-3">Excluído por</th>
                  <th class="px-3 py-3">Data</th>
                  <th class="px-3 py-3 text-right">Ações</th>
                </tr>
              </thead>
              <tbody>
                @forelse($registros as $r)
                <tr class="border-t border-app hover:bg-[var(--surface-2)]">
                  <td class="px-3 py-2">
                    <span class="badge text-app font-semibold">
                      {{ $entityLabels[$r->entity] ?? mb_strtoupper($r->entity) }}
                    </span>
                  </td>
                  <td class="px-3 py-2">
                    <div class="font-medium text-app">
                      {{ $r->model_label ?? ($r->model_type . ' #' . $r->model_id) }}
                    </div>
                    <div class="text-xs text-muted">
                      ID: {{ $r->model_id }}
                    </div>
                  </td>
                  <td class="px-3 py-2">
                    <div class="text-app">{{ $r->deleted_by ?? 'SISTEMA' }}</div>
                    @if(!empty($r->deleted_by_matricula))
                    <div class="text-xs text-muted">Matrícula: {{ $r->deleted_by_matricula }}</div>
                    @endif
                  </td>
                  <td class="px-3 py-2 whitespace-nowrap text-muted">
                    {{ optional($r->deleted_at)->format('d/m/Y H:i') ?? '-' }}
                  </td>
                  <td class="px-3 py-2 text-right">
                    <div class="inline-flex items-center justify-end gap-2">
                      <form id="restore-form-{{ $r->id }}" method="POST" action="{{ route('removidos.restore', $r->id) }}" class="inline">
                        @csrf
                        <button
                          type="button"
                          data-form-id="restore-form-{{ $r->id }}"
                          data-acao="restore"
                          data-label="{{ $r->model_label ?? ($r->model_type . ' #' . $r->model_id) }}"
                          @click="abrirModalConfirmacao($event.currentTarget.dataset.formId, $event.currentTarget.dataset.acao, $event.currentTarget.dataset.label)"
                          class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-md bg-emerald-600 text-white hover:bg-emerald-700">
                          Restaurar
                        </button>
                      </form>

                      <button
                        type="button"
                        data-id="{{ $r->id }}"
                        data-label="{{ $r->model_label ?? ($r->model_type . ' #' . $r->model_id) }}"
                        @click="abrirModalVisualizar($event.currentTarget.dataset.id, $event.currentTarget.dataset.label)"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-md bg-blue-600 text-white hover:bg-blue-700">
                        Visualizar
                      </button>

                      <form id="destroy-form-{{ $r->id }}" method="POST" action="{{ route('removidos.destroy', $r->id) }}" class="inline">
                        @csrf
                        @method('DELETE')
                        <button
                          type="button"
                          data-form-id="destroy-form-{{ $r->id }}"
                          data-acao="destroy"
                          data-label="{{ $r->model_label ?? ($r->model_type . ' #' . $r->model_id) }}"
                          @click="abrirModalConfirmacao($event.currentTarget.dataset.formId, $event.currentTarget.dataset.acao, $event.currentTarget.dataset.label)"
                          class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-md bg-red-600 text-white hover:bg-red-700">
                          Remover definitivamente
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
                @empty
                <tr>
                  <td colspan="5" class="px-3 py-8 text-center text-muted">
                    Nenhum registro removido encontrado.
                  </td>
                </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="mt-4 flex items-center justify-between">
            <div>
              @if($registros->hasPages())
              {{ $registros->links() }}
              @endif
            </div>
            <div class="text-sm text-muted">
              Resultados: <span class="font-semibold">{{ $registros->total() }}</span>
            </div>
          </div>

          {{-- Modal de VisualizaÇõÇœo (estilo consulta) --}}
          <div x-show="mostraModalVisualizar" x-cloak x-transition class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 sm:p-6 lg:p-8" @click.self="fecharModalVisualizar()" style="display: none;">
            <div class="bg-surface text-app rounded-2xl shadow-2xl w-full max-w-[98vw] h-[92vh] flex flex-col border border-app overflow-hidden" @click.stop>
              {{-- Header --}}
              <div class="px-4 sm:px-6 py-4 border-b border-app flex items-start justify-between gap-4">
                <div class="min-w-0">
                  <h3 class="text-lg sm:text-xl font-semibold text-app">Visualizar removido</h3>
                  <p class="text-sm text-muted mt-1 truncate" x-text="visualizarLabel"></p>
                </div>
                <button type="button" @click="fecharModalVisualizar()" class="inline-flex items-center justify-center w-10 h-10 rounded-lg border border-app text-app hover:bg-[var(--surface-2)] transition" aria-label="Fechar">&times;</button>
              </div>

              {{-- Toolbar --}}
              <div class="px-4 sm:px-6 py-3 border-b border-app">
                <div class="flex flex-col lg:flex-row lg:items-center gap-3">
                  <div class="flex-1 flex items-center gap-2">
                    <div class="flex-1">
                      <label for="visualizar_busca" class="sr-only">Buscar</label>
                      <input
                        id="visualizar_busca"
                        type="text"
                        x-model.debounce.200ms="visualizarBusca"
                        placeholder="Buscar campo ou valor..."
                        class="input-base h-10"
                      />
                    </div>

                    <button
                      type="button"
                      x-show="visualizarBusca"
                      x-cloak
                      @click="visualizarBusca = ''"
                      class="h-10 px-3 text-xs sm:text-sm font-semibold rounded-md border border-app bg-surface text-app hover:bg-[var(--surface-2)] transition"
                    >Limpar</button>
                  </div>

                  <label class="inline-flex items-center gap-2 text-sm text-app select-none">
                    <input type="checkbox" x-model="visualizarSomentePreenchidos" class="rounded border-app bg-surface text-blue-600 focus:ring-blue-500" />
                    Somente preenchidos
                  </label>

                  <div class="flex items-center justify-between gap-3 lg:justify-end">
                    <div class="text-xs text-muted whitespace-nowrap">
                      <span class="font-semibold" x-text="visualizarCamposFiltrados.length"></span> campos
                      <span class="mx-1">&middot;</span>
                      Pagina <span class="font-semibold" x-text="visualizarPagina"></span>/<span class="font-semibold" x-text="visualizarTotalPaginas"></span>
                    </div>

                    <div class="inline-flex items-center gap-2">
                      <button
                        type="button"
                        @click="paginaAnterior()"
                        :disabled="visualizarPagina <= 1"
                        class="px-3 py-2 text-xs sm:text-sm font-semibold rounded-md border border-app bg-surface text-app hover:bg-[var(--surface-2)] disabled:opacity-50 disabled:cursor-not-allowed transition"
                      >Anterior</button>
                      <button
                        type="button"
                        @click="proximaPagina()"
                        :disabled="visualizarPagina >= visualizarTotalPaginas"
                        class="px-3 py-2 text-xs sm:text-sm font-semibold rounded-md border border-app bg-surface text-app hover:bg-[var(--surface-2)] disabled:opacity-50 disabled:cursor-not-allowed transition"
                      >Proxima</button>
                    </div>
                  </div>
                </div>
              </div>

              {{-- Content --}}
              <div class="flex-1 p-4 sm:p-6 overflow-hidden bg-surface-2">
                <template x-if="visualizarErro">
                  <div class="mb-4 p-4 rounded-md bg-surface border border-app border-l-4 text-app" style="border-left-color: var(--danger);" x-text="visualizarErro"></div>
                </template>

                <template x-if="visualizarCarregando">
                  <div class="grid gap-3" :style="'grid-template-columns: repeat(' + visualizarColunas + ', minmax(0, 1fr));'">
                    <template x-for="i in 25" :key="i">
                      <div class="rounded-xl border border-app bg-surface p-3">
                        <div class="h-3 w-2/3 bg-surface-2 rounded animate-pulse"></div>
                        <div class="mt-2 h-4 w-full bg-surface-2 rounded animate-pulse"></div>
                      </div>
                    </template>
                  </div>
                </template>

                <template x-if="!visualizarCarregando">
                  <div class="h-full">
                    <template x-if="visualizarCamposFiltrados.length === 0">
                      <div class="h-full flex items-center justify-center">
                        <div class="text-center">
                          <div class="text-sm text-muted">Nenhum campo encontrado.</div>
                          <div class="text-xs text-muted opacity-80 mt-1" x-show="visualizarSomentePreenchidos">Dica: desmarque &quot;Somente preenchidos&quot;.</div>
                          <div class="text-xs text-muted opacity-80 mt-1" x-show="visualizarBusca">Dica: limpe a busca.</div>
                        </div>
                      </div>
                    </template>

                    <template x-if="visualizarCamposFiltrados.length > 0">
                      <div class="grid gap-3" :style="'grid-template-columns: repeat(' + visualizarColunas + ', minmax(0, 1fr));'">
                        <template x-for="campo in visualizarCamposPagina" :key="campo.key">
                          <div class="rounded-xl border border-app bg-surface p-3 shadow-sm">
                            <div class="text-[11px] font-semibold text-muted uppercase tracking-wider truncate" x-text="campo.label || campo.key"></div>

                            <template x-if="campo.kind === 'codeName'">
                              <div class="mt-1 leading-tight">
                                <div class="font-mono text-sm font-semibold" :class="campo.empty ? 'text-muted italic opacity-80' : ''" :style="campo.empty ? '' : ('color: ' + (campo.color || 'var(--accent-500)') + ';')" x-text="campo.code"></div>
                                <div class="text-[11px] font-semibold truncate" :class="campo.name ? '' : 'text-muted italic opacity-80'" :style="campo.name ? ('color: ' + (campo.color || 'var(--accent-500)') + ';') : ''" :title="campo.name || ''" x-text="campo.name || '-'"></div>
                              </div>
                            </template>

                            <template x-if="campo.kind !== 'codeName'">
                              <div
                                class="mt-1 text-sm font-medium overflow-hidden"
                                style="-webkit-box-orient: vertical; -webkit-line-clamp: 2; display: -webkit-box;"
                                :class="campo.empty ? 'text-muted italic opacity-80' : 'text-app'"
                                :title="campo.value"
                                x-text="campo.value"
                              ></div>
                            </template>
                          </div>
                        </template>
                      </div>
                    </template>
                  </div>
                </template>
              </div>
            </div>
          </div>

          {{-- Modal de Confirmação --}}
          <div x-show="mostraModalConfirmacao" x-cloak x-transition class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4" @click.self="mostraModalConfirmacao = false" style="display: none;">
            <div class="bg-surface text-app rounded-lg shadow-lg p-6 max-w-sm w-full border border-app" @click.stop>
              <h3 class="text-lg font-semibold text-app mb-2" x-text="tituloConfirmacao"></h3>

              <p class="text-muted mb-6">
                Tem certeza que deseja
                <span class="font-semibold" x-text="acao === 'destroy' ? 'remover definitivamente' : 'restaurar'"></span>
                o registro "<strong class="text-app" x-text="registroLabel"></strong>"?
                <span x-show="acao === 'destroy'" x-cloak class="block mt-2 text-xs text-red-600">Esta acao nao pode ser desfeita.</span>
              </p>

              <div class="flex gap-3 justify-end">
                <button type="button" @click="mostraModalConfirmacao = false" class="px-4 py-2 text-sm font-semibold rounded-md border border-app bg-surface text-app hover:bg-[var(--surface-2)] transition">
                  Cancelar
                </button>
                <button type="button" @click="confirmarAcao()" class="px-4 py-2 text-sm text-white rounded transition font-semibold"
                  :class="acao === 'destroy' ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700'">
                  <span x-text="acao === 'destroy' ? 'Remover' : 'Restaurar'"></span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

@push('scripts')
<script>
  function removidosIndex() {
    const showUrlTemplate = @json(route('removidos.show', ['removido' => 0]));

    return {
      mostraModalConfirmacao: false,
      acao: 'restore',
      tituloConfirmacao: '',
      registroLabel: '',
      formIdConfirmacao: null,

      mostraModalVisualizar: false,
      visualizarCarregando: false,
      visualizarLabel: '',
      visualizarErro: '',
      visualizarEntity: '',
      visualizarResolved: {},
      visualizarCampos: [],
      visualizarCamposFiltrados: [],
      visualizarCamposPagina: [],
      visualizarBusca: '',
      visualizarSomentePreenchidos: true,
      visualizarPagina: 1,
      visualizarColunas: 5,
      visualizarPorPagina: 50,
      visualizarTotalPaginas: 1,
      _resizeTimer: null,

      init() {
        this.$watch('mostraModalConfirmacao', () => this.atualizarScrollLock());
        this.$watch('mostraModalVisualizar', () => this.atualizarScrollLock());
        this.$watch('visualizarBusca', () => this.aplicarFiltroVisualizar(true));
        this.$watch('visualizarSomentePreenchidos', () => this.recalcularLayoutVisualizar(true));
      },

      atualizarScrollLock() {
        const lock = this.mostraModalConfirmacao || this.mostraModalVisualizar;
        document.documentElement.classList.toggle('overflow-hidden', lock);
        document.body.classList.toggle('overflow-hidden', lock);
      },

      abrirModalConfirmacao(formId, acao, label) {
        this.formIdConfirmacao = formId || null;
        this.acao = acao || 'restore';
        this.registroLabel = label || '';
        this.tituloConfirmacao = this.acao === 'destroy' ? 'Remover definitivamente' : 'Restaurar registro';
        this.mostraModalConfirmacao = true;
      },

      confirmarAcao() {
        const id = this.formIdConfirmacao;
        this.mostraModalConfirmacao = false;
        if (!id) return;
        const form = document.getElementById(id);
        if (!form) return;
        form.submit();
      },

      abrirModalVisualizar(id, label) {
        const parsedId = parseInt(id, 10);
        if (!parsedId) return;

        this.visualizarLabel = label || '';
        this.visualizarErro = '';
        this.visualizarEntity = '';
        this.visualizarResolved = {};
        this.visualizarBusca = '';
        this.visualizarSomentePreenchidos = true;
        this.visualizarPagina = 1;
        this.visualizarColunas = this.definirColunas();
        this.visualizarPorPagina = this.definirPorPagina(this.visualizarColunas);
        this.visualizarTotalPaginas = 1;
        this.visualizarCampos = [];
        this.visualizarCamposFiltrados = [];
        this.visualizarCamposPagina = [];
        this.visualizarCarregando = true;
        this.mostraModalVisualizar = true;

        this.$nextTick(() => {
          const input = document.getElementById('visualizar_busca');
          if (input) input.focus();
        });

        const url = showUrlTemplate.replace(/\/0$/, '/' + parsedId);

        fetch(url, {
          headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
        })
          .then((resp) => {
            if (!resp.ok) {
              throw new Error('Falha ao carregar (' + resp.status + ').');
            }
            return resp.json();
          })
          .then((data) => {
            const payload = data && typeof data.payload === 'object' && data.payload !== null ? data.payload : {};
            const labelFromServer = data && typeof data.label === 'string' ? data.label : '';
            const entityFromServer = data && typeof data.entity === 'string' ? data.entity : '';
            const resolvedFromServer = data && typeof data.resolved === 'object' && data.resolved !== null ? data.resolved : {};
            if (labelFromServer) {
              this.visualizarLabel = labelFromServer;
            }
            console.log('[Removidos] Payload completo:', payload);
            this.visualizarEntity = entityFromServer;
            this.visualizarResolved = resolvedFromServer;
            this.visualizarCampos = this.normalizarCampos(payload, this.visualizarEntity, this.visualizarResolved);
            this.aplicarFiltroVisualizar(true);
          })
          .catch((e) => {
            this.visualizarErro = e?.message || 'Falha ao carregar dados.';
          })
          .finally(() => {
            this.visualizarCarregando = false;
          });
      },

      definirColunas() {
        const w = window.innerWidth || 1024;
        const mostrarVazios = !this.visualizarSomentePreenchidos;

        if (w < 640) return 1;
        if (w < 768) return 2;
        if (w < 1024) return 3;
        if (w < 1280) return mostrarVazios ? 6 : 5;
        if (w < 1536) return mostrarVazios ? 7 : 6;
        return mostrarVazios ? 8 : 7;
      },

      definirPorPagina(cols) {
        const h = window.innerHeight || 900;
        const columns = Math.max(1, Number(cols) || 5);

        const modalHeight = Math.floor(h * 0.92);
        const reserved = 210; // header + toolbar
        const rowHeight = 92; // card height + gap (estimado)
        const rows = Math.max(3, Math.floor((modalHeight - reserved) / rowHeight));

        const perPage = rows * columns;
        return Math.max(10, Math.min(perPage, 80));
      },

      recalcularLayoutVisualizar(resetPage) {
        if (!this.mostraModalVisualizar) return;
        this.visualizarColunas = this.definirColunas();
        this.visualizarPorPagina = this.definirPorPagina(this.visualizarColunas);
        this.aplicarFiltroVisualizar(!!resetPage);
      },

      onResize() {
        if (!this.mostraModalVisualizar) return;
        clearTimeout(this._resizeTimer);
        this._resizeTimer = setTimeout(() => this.recalcularLayoutVisualizar(false), 120);
      },

      aplicarFiltroVisualizar(resetPage) {
        const term = (this.visualizarBusca || '').trim().toLowerCase();
        let campos = Array.isArray(this.visualizarCampos) ? this.visualizarCampos : [];

        if (this.visualizarSomentePreenchidos) {
          campos = campos.filter((c) => !c.empty);
        }

        if (term) {
          campos = campos.filter((c) => (c.keyLower || '').includes(term) || (c.valueLower || '').includes(term));
        }

        this.visualizarCamposFiltrados = campos;
        this.visualizarTotalPaginas = Math.max(1, Math.ceil(campos.length / this.visualizarPorPagina));

        if (resetPage) {
          this.visualizarPagina = 1;
        }

        if (this.visualizarPagina > this.visualizarTotalPaginas) {
          this.visualizarPagina = this.visualizarTotalPaginas;
        }

        this.atualizarPaginaVisualizar();
      },

      atualizarPaginaVisualizar() {
        const start = (this.visualizarPagina - 1) * this.visualizarPorPagina;
        this.visualizarCamposPagina = this.visualizarCamposFiltrados.slice(start, start + this.visualizarPorPagina);
      },

      paginaAnterior() {
        if (this.visualizarPagina <= 1) return;
        this.visualizarPagina -= 1;
        this.atualizarPaginaVisualizar();
      },

      proximaPagina() {
        if (this.visualizarPagina >= this.visualizarTotalPaginas) return;
        this.visualizarPagina += 1;
        this.atualizarPaginaVisualizar();
      },

      normalizarCampos(payload, entity, resolved) {
        try {
          const rawPayload = payload && typeof payload === 'object' ? payload : {};
          const entityKey = (entity || '').toString().toLowerCase();

          const labelsPatrimonios = {
            NUSEQPATR: 'ID',
            NUPATRIMONIO: 'Patrimônio',
            DEPATRIMONIO: 'Descrição',
            SITUACAO: 'Situação',
            TIPO: 'Tipo',
            CODOBJETO: 'Objeto',
            CDPROJETO: 'Projeto',
            CDLOCAL: 'Local',
            CDMATRFUNCIONARIO: 'Responsável',
            CDLOCALINTERNO: 'Local interno',
            NUMOF: 'OF',
            NMPLANTA: 'Termo',
            MARCA: 'Marca',
            MODELO: 'Modelo',
            NUSERIE: 'Nº série',
            COR: 'Cor',
            DIMENSAO: 'Dimensão',
            CARACTERISTICAS: 'Características',
            PESO: 'Peso',
            TAMANHO: 'Tamanho',
            DTAQUISICAO: 'Data aquisição',
            DTBAIXA: 'Data baixa',
            DTGARANTIA: 'Data garantia',
            DTLAUDO: 'Data laudo',
            DTOPERACAO: 'Data operação',
            DEHISTORICO: 'Histórico',
            UF: 'UF',
            USUARIO: 'Cadastrado por',
            FLCONFERIDO: 'Conferido',
            PROJETO_CORRETO: 'Projeto correto',
          };

          const labels = entityKey === 'patrimonios' ? labelsPatrimonios : {};

          const ordemPatrimonios = [
            'NUPATRIMONIO',
            'DEPATRIMONIO',
            'SITUACAO',
            'TIPO',
            'CODOBJETO',
            'CDPROJETO',
            'PROJETO_CORRETO',
            'CDLOCAL',
            'CDLOCALINTERNO',
            'CDMATRFUNCIONARIO',
            'NUMOF',
            'NMPLANTA',
            'MARCA',
            'MODELO',
            'NUSERIE',
            'COR',
            'DIMENSAO',
            'PESO',
            'TAMANHO',
            'CARACTERISTICAS',
            'DEHISTORICO',
            'DTAQUISICAO',
            'DTOPERACAO',
            'DTGARANTIA',
            'DTLAUDO',
            'DTBAIXA',
            'UF',
            'USUARIO',
            'NUSEQPATR',
            'FLCONFERIDO',
          ];

          const ordem = entityKey === 'patrimonios' ? ordemPatrimonios : [];
          const ordemPos = new Map();
          ordem.forEach((k, i) => ordemPos.set(String(k).toUpperCase(), i));

          const resolvedObj = resolved && typeof resolved === 'object' ? resolved : {};
          const resolvedUpper = {};
          for (const k in resolvedObj) {
            if (!Object.prototype.hasOwnProperty.call(resolvedObj, k)) continue;
            resolvedUpper[String(k).toUpperCase()] = resolvedObj[k];
          }

          const toDisplay = (value) => {
            let display = '-';

            if (value === null || typeof value === 'undefined') {
              display = '-';
            } else if (typeof value === 'boolean') {
              display = value ? 'true' : 'false';
            } else if (typeof value === 'number') {
              display = String(value);
            } else if (typeof value === 'string') {
              const trimmed = value.trim();
              display = trimmed === '' ? '-' : value;
            } else {
              try {
                display = JSON.stringify(value);
              } catch (err) {
                display = String(value);
              }
            }

            return String(display);
          };

          const keys = Object.keys(rawPayload || {});
          keys.sort((a, b) => {
            const ua = String(a).toUpperCase();
            const ub = String(b).toUpperCase();
            const wa = ordemPos.has(ua) ? ordemPos.get(ua) : 9999;
            const wb = ordemPos.has(ub) ? ordemPos.get(ub) : 9999;
            if (wa !== wb) return wa - wb;
            return ua.localeCompare(ub, 'pt-BR', { numeric: true, sensitivity: 'base' });
          });

          const specialKeys = new Set(['CDLOCAL', 'CDPROJETO', 'CDMATRFUNCIONARIO', 'CODOBJETO']);

          return keys.map((key) => {
            const keyString = String(key);
            const keyNorm = keyString.toUpperCase();
            const value = rawPayload[key];
            const display = toDisplay(value);
            const label = labels[keyNorm] || keyString;

            const keyLower = (label + ' ' + keyString).toLowerCase();

            if (specialKeys.has(keyNorm)) {
              const info = resolvedUpper[keyNorm];
              const name =
                info && typeof info === 'object' && info !== null && typeof info.nome === 'string' ? info.nome : '';

              const codeTrimmed = (display || '').toString().trim();
              const empty = codeTrimmed === '' || codeTrimmed === '-';

              const color = keyNorm === 'CDLOCAL' || keyNorm === 'CDMATRFUNCIONARIO' ? 'var(--ok)' : 'var(--accent-500)';
              const valueLower = (codeTrimmed + ' ' + (name || '')).toLowerCase();

              return {
                key: keyString,
                label,
                kind: 'codeName',
                code: empty ? '-' : codeTrimmed,
                name: name ? String(name) : '',
                color,
                empty,
                keyLower,
                valueLower,
              };
            }

            const empty = display === '-';
            return {
              key: keyString,
              label,
              kind: 'text',
              value: display,
              empty,
              keyLower,
              valueLower: display.toLowerCase(),
            };
          });
        } catch (err) {
          return [];
        }
      },

      fecharModalVisualizar() {
        this.mostraModalVisualizar = false;
        this.visualizarErro = '';
        this.visualizarEntity = '';
        this.visualizarResolved = {};
        this.visualizarCampos = [];
        this.visualizarCamposFiltrados = [];
        this.visualizarCamposPagina = [];
        this.visualizarBusca = '';
        this.visualizarPagina = 1;
        this.visualizarTotalPaginas = 1;
        this.visualizarCarregando = false;
      },

      fecharTudo() {
        this.mostraModalConfirmacao = false;
        this.fecharModalVisualizar();
      },
    };
  }
</script>
@endpush

</x-app-layout>
