<x-app-layout>
  <x-patrimonio-nav-tabs />

  <div
    x-data="patrimoniosIndex()"
    class="py-4"
  >
    <div class="py-3">
      <div class="w-full px-2 sm:px-4 lg:px-6">
        <div class="bg-white dark:bg-gray-900 shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
          <div class="p-4 sm:p-5 space-y-3">
            @include('patrimonios.partials.flash-messages')
            @include('patrimonios.partials.filter-form')
            @include('patrimonios.partials.action-buttons')
            <div id="patrimonios-table-container" class="relative">
              <div id="patrimonios-table-content">
                @include('patrimonios.partials.patrimonio-table')
              </div>
              <div
                id="patrimonios-loading"
                class="absolute inset-0 z-30 pointer-events-none flex flex-col"
                x-show="false"
              >
                <div class="absolute inset-0 rounded-lg bg-white/85 dark:bg-slate-900/85 backdrop-blur-md"></div>
                <div class="relative h-full w-full flex flex-col gap-4 px-4 py-5 justify-center">
                  <div class="flex items-center gap-3 text-sm text-slate-800 dark:text-white drop-shadow">
                    <svg class="animate-spin h-6 w-6 text-indigo-500" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span class="text-base font-semibold">Atualizando grid...</span>
                  </div>
                  <div class="flex-1 space-y-3">
                    <div class="h-10 w-full rounded-md bg-gradient-to-r from-slate-200 via-indigo-100 to-slate-200 dark:from-slate-700 dark:via-slate-600 dark:to-slate-700 animate-pulse"></div>
                    <div class="h-10 w-full rounded-md bg-gradient-to-r from-slate-200 via-indigo-100 to-slate-200 dark:from-slate-700 dark:via-slate-600 dark:to-slate-700 animate-pulse"></div>
                    <div class="h-10 w-full rounded-md bg-gradient-to-r from-slate-200 via-indigo-100 to-slate-200 dark:from-slate-700 dark:via-slate-600 dark:to-slate-700 animate-pulse"></div>
                    <div class="h-10 w-5/6 rounded-md bg-gradient-to-r from-slate-200 via-indigo-100 to-slate-200 dark:from-slate-700 dark:via-slate-600 dark:to-slate-700 animate-pulse"></div>
                  </div>
                </div>
              </div>
              <div
                id="patrimonios-loading-top"
                class="fixed top-3 left-1/2 -translate-x-1/2 z-50 px-4 py-2 rounded-md bg-white/95 dark:bg-slate-900/95 shadow-lg border border-indigo-200/60 dark:border-indigo-700/50 text-sm text-slate-800 dark:text-white flex items-center gap-3 pointer-events-none"
                x-show="false"
              >
                <svg class="animate-spin h-4 w-4 text-indigo-600" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span class="font-medium">Carregando grid...</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    @include('patrimonios.partials.modals.relatorio')
    @include('patrimonios.partials.modals.termo')

    {{-- Modal de confirmacao de exclusao --}}
    <div
      x-show="deleteModalOpen"
      x-cloak
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/60"
    >
      <div @click.outside="deleteModalOpen=false" class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md p-6 border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Confirmar exclusão</h3>
        <p class="text-sm text-gray-700 dark:text-gray-300 mb-4">Tem certeza que deseja excluir <strong x-text="deleteItemName"></strong>? Esta ação não pode ser desfeita.</p>
        <div class="flex justify-end gap-3">
          <button type="button" @click="deleteModalOpen=false" class="px-4 py-2 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600">Cancelar</button>
          <button type="button" @click="confirmDelete" :disabled="deleting" class="px-4 py-2 rounded-md bg-red-600 hover:bg-red-700 text-white disabled:opacity-70">
            <span x-show="!deleting">Excluir</span>
            <span x-show="deleting">Excluindo...</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('#patrimonio-filter-form');
        const tableContainer = document.querySelector('#patrimonios-table-container');
        const tableContent = document.querySelector('#patrimonios-table-content');
        const loading = document.querySelector('#patrimonios-loading');
        const loadingTop = document.querySelector('#patrimonios-loading-top');
        const cleanLinks = document.querySelectorAll('[data-ajax-clean]');
        const logTags = (label = 'tags') => {
          const tags = Array.from(document.querySelectorAll('#patrimonios-tags [data-ajax-tag-remove]')).map(t => t.textContent.trim());
          console.log('[PATRI] ' + label, tags);
        };

        const swapTable = (html) => {
          if (!tableContainer) return;
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, 'text/html');
          const fresh = doc.querySelector('#patrimonios-table-content');
          if (fresh && tableContent) tableContent.innerHTML = fresh.innerHTML;

          const freshTags = doc.querySelector('#patrimonios-tags');
          const tags = document.querySelector('#patrimonios-tags');
          if (freshTags && tags) {
            tags.innerHTML = freshTags.innerHTML;
            if (window.Alpine && typeof Alpine.initTree === 'function') {
              Alpine.initTree(tags);
            }
          }
          logTags('after-swap');
        };

        const buildUrlFromForm = () => {
          if (!form) return null;
          const params = new URLSearchParams(new FormData(form));
          return form.action + (params.toString() ? '?' + params.toString() : '');
        };

        const scrollToTopSmooth = () => {
          try {
            window.scrollTo({ top: 0, behavior: 'smooth' });
          } catch (_) {
            window.scrollTo(0, 0);
          }
        };

        const ajaxFetch = (url) => {
          if (!tableContainer) return;
          scrollToTopSmooth();
          if (loading) loading.classList.remove('hidden');
          if (loadingTop) loadingTop.classList.remove('hidden');
          tableContainer.classList.add('opacity-60');
          fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(resp => resp.text())
            .then(swapTable)
            .catch(() => window.location.href = url)
            .finally(() => {
              tableContainer.classList.remove('opacity-60');
              if (loading) loading.classList.add('hidden');
              if (loadingTop) loadingTop.classList.add('hidden');
            });
        };

        if (form) {
          form.addEventListener('submit', (e) => {
            e.preventDefault();
            const url = buildUrlFromForm();
            if (url) {
              ajaxFetch(url);
              window.scrollTo({ top: 0, behavior: 'smooth' });
            }
          });
        }

        cleanLinks.forEach(link => {
          link.addEventListener('click', (e) => {
            e.preventDefault();
            const href = link.getAttribute('href');
            if (href) {
              ajaxFetch(href);
              if (form) form.reset();
              window.scrollTo({ top: 0, behavior: 'smooth' });
            }
          });
        });

        document.addEventListener('click', (e) => {
          const sortLink = e.target.closest('[data-ajax-sort]');
          if (sortLink) {
            e.preventDefault();
            const href = sortLink.getAttribute('href');
            if (href) {
              ajaxFetch(href);
              window.scrollTo({ top: 0, behavior: 'smooth' });
            }
            return;
          }

          const pagLink = e.target.closest('[data-ajax-page], #patrimonios-pagination a');
          if (pagLink) {
            e.preventDefault();
            const href = pagLink.getAttribute('href');
            if (href) {
              ajaxFetch(href);
              window.scrollTo({ top: 0, behavior: 'smooth' });
            }
            return;
          }
        });
        logTags('initial');
      });
      function patrimoniosIndex() {
        return {
          relatorioModalOpen: false,
          termoModalOpen: false,
          atribuirTermoModalOpen: false,
          desatribuirTermoModalOpen: false,
          resultadosModalOpen: false,
          deleteModalOpen: false,
          deleteItemId: null,
          deleteItemName: '',
          deleting: false,
          isLoading: false,
          reportData: [],
          reportFilters: {},
          tipoRelatorio: 'numero',
          relatorioErrors: {},
          relatorioGlobalError: null,
          init() {
            if (window.location.hash === '#atribuir-termo') {
              this.atribuirTermoModalOpen = true;
            }
            this.$watch('atribuirTermoModalOpen', (open) => {
              document.documentElement.classList.toggle('overflow-hidden', open);
              document.body.classList.toggle('overflow-hidden', open);
              if (open) {
                window.location.hash = 'atribuir-termo';
              } else if (window.location.hash === '#atribuir-termo') {
                history.replaceState(null, '', window.location.pathname + window.location.search);
              }
            });
            this.$watch('tipoRelatorio', () => {
              this.relatorioErrors = {};
              this.relatorioGlobalError = null;
            });
          },
          csrf() {
            return document.querySelector('meta[name=csrf-token]')?.content || '';
          },
          abrirRelatorio() {
            this.relatorioModalOpen = true;
          },
          abrirTermo() {
            this.termoModalOpen = true;
          },
          abrirAtribuir() {
            this.atribuirTermoModalOpen = true;
            window.location.hash = 'atribuir-termo';
          },
          gerarRelatorio(event) {
            event?.preventDefault?.();
            this.isLoading = true;
            this.relatorioErrors = {};
            this.relatorioGlobalError = null;
            const formData = new FormData(event.target);

            fetch("{{ route('relatorios.patrimonios.gerar') }}", {
              method: 'POST',
              body: formData,
              headers: {
                'X-CSRF-TOKEN': this.csrf(),
                'Accept': 'application/json',
              },
            })
              .then(async (resp) => {
                const data = await resp.json().catch(() => ({}));
                if (!resp.ok) {
                  if (resp.status === 422 && data.errors) {
                    this.relatorioErrors = data.errors;
                    this.relatorioGlobalError = data.message || 'Erros de validacao.';
                    throw new Error('validation');
                  }
                  this.relatorioGlobalError = data.message || 'Falha ao gerar relatorio.';
                  throw new Error('erro');
                }
                return data;
              })
              .then((data) => {
                this.reportData = data.resultados || [];
                this.reportFilters = data.filtros || {};
                this.relatorioModalOpen = false;
                this.$nextTick(() => { this.resultadosModalOpen = true; });
              })
              .catch((err) => {
                if (err.message !== 'validation') {
                  console.error('Erro ao gerar relatorio', err);
                }
              })
              .finally(() => { this.isLoading = false; });
          },
          exportarRelatorio(formato) {
            const actions = {
              excel: "{{ route('relatorios.patrimonios.exportar.excel') }}",
              csv: "{{ route('relatorios.patrimonios.exportar.csv') }}",
              ods: "{{ route('relatorios.patrimonios.exportar.ods') }}",
              pdf: "{{ route('relatorios.patrimonios.exportar.pdf') }}",
            };
            const action = actions[formato] || actions.excel;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = action;

            const token = document.createElement('input');
            token.type = 'hidden';
            token.name = '_token';
            token.value = this.csrf();
            form.appendChild(token);

            Object.entries(this.reportFilters || {}).forEach(([key, value]) => {
              if (value !== null && value !== undefined) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
              }
            });

            document.body.appendChild(form);
            form.submit();
            form.remove();
          },
          getFilterLabel(tipo) {
            const labels = {
              numero: 'Relatorio por Numero de Patrimonio',
              descricao: 'Relatorio por Descricao',
              aquisicao: 'Relatorio por Periodo de Aquisicao',
              cadastro: 'Relatorio por Periodo de Cadastro',
              projeto: 'Relatorio por Projeto',
              oc: 'Relatorio por OC',
              uf: 'Relatorio por UF',
              situacao: 'Relatorio por Situacao',
            };
            return labels[tipo || this.tipoRelatorio] || 'Relatorio';
          },
          getColumnColor(tipo) {
            const colors = {
              numero: 'bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300',
              descricao: 'bg-orange-100 dark:bg-orange-900 text-orange-700 dark:text-orange-300',
              aquisicao: 'bg-yellow-100 dark:bg-yellow-900 text-yellow-700 dark:text-yellow-300',
              cadastro: 'bg-cyan-100 dark:bg-cyan-900 text-cyan-700 dark:text-cyan-300',
              projeto: 'bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300',
              oc: 'bg-pink-100 dark:bg-pink-900 text-pink-700 dark:text-pink-300',
              uf: 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300',
              situacao: 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300',
            };
            return colors[tipo || this.tipoRelatorio] || 'bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300';
          },
          openDelete(id, name) {
            this.deleteItemId = id;
            this.deleteItemName = name || 'este patrimonio';
            this.deleteModalOpen = true;
          },
          confirmDelete() {
            if (!this.deleteItemId) return;
            this.deleting = true;
            fetch("{{ url('patrimonios') }}/" + encodeURIComponent(this.deleteItemId), {
              method: 'DELETE',
              headers: {
                'X-CSRF-TOKEN': this.csrf(),
                'Accept': 'application/json',
              },
            })
              .then(async (resp) => {
                if (resp.ok || resp.status === 204) {
                  return;
                }
                const data = await resp.json().catch(() => ({}));
                throw new Error(data.message || 'Erro ' + resp.status);
              })
              .then(() => {
                this.removeRow(this.deleteItemId);
                this.deleteModalOpen = false;
                this.deleteItemId = null;
                this.deleteItemName = '';
              })
              .catch((err) => {
                alert(err.message || 'Erro ao excluir.');
              })
              .finally(() => {
                this.deleting = false;
              });
          },
          removeRow(id) {
            const row = document.querySelector('[data-row-id=\"' + id + '\"]');
            if (!row) return;
            row.style.transition = 'opacity 260ms ease, transform 260ms ease';
            row.style.opacity = '0';
            row.style.transform = 'scale(0.985)';
            setTimeout(() => row.remove(), 320);
          },
        };
      }
    </script>
  @endpush
</x-app-layout>
