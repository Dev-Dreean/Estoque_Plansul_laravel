<x-app-layout>
  <x-patrimonio-nav-tabs />

  @php
    $isConsultor = auth()->user()?->PERFIL === \App\Models\User::PERFIL_CONSULTOR;
  @endphp

  <div
    x-data="patrimoniosIndex()"
    @keydown.window="handleCreateKey($event)"
    class="py-4"
  >
    <div class="py-3">
      <div class="w-full px-2 sm:px-4 lg:px-6">
        <div class="bg-white dark:bg-gray-900 shadow-lg sm:rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div class="p-4 sm:p-5 space-y-3">
            @include('patrimonios.partials.flash-messages')
            @include('patrimonios.partials.filter-form')
            @include('patrimonios.partials.action-buttons')

            @unless($isConsultor)
              <div
                id="bulk-status-bar"
                class="hidden mb-3 transition-all duration-200 opacity-0 -translate-y-3 scale-90"
              >
              <div class="bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 shadow-md rounded-lg px-3 py-2.5 flex flex-wrap items-center gap-2.5 text-sm ring-1 ring-blue-100 dark:ring-blue-900/50">
                <div class="font-semibold flex items-center gap-2 text-gray-900 dark:text-gray-100 whitespace-nowrap">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1H3zm0 2h14v9a2 2 0 01-2 2H5a2 2 0 01-2-2V6z" clip-rule="evenodd" />
                  </svg>
                  <span id="bulk-count">0</span>
                  <span>selecionado<span id="bulk-plural">s</span></span>
                </div>
                <div class="h-5 w-px bg-gray-300 dark:bg-gray-600"></div>
                <select id="bulk-situacao" class="h-9 px-3 text-sm border border-gray-200 dark:border-gray-700 rounded-md bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-200 placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-indigo-500 dark:focus:border-indigo-400 transition">
                  <option value="" disabled selected class="text-gray-500 dark:text-gray-400">Situação</option>
                  <option value="EM USO">EM USO</option>
                  <option value="CONSERTO">CONSERTO</option>
                  <option value="BAIXA">BAIXA</option>
                  <option value="A DISPOSICAO">A DISPOSIÇÃO</option>
                </select>
                <button id="bulk-apply" class="h-9 px-4 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-600 dark:hover:bg-indigo-700 text-white text-sm font-medium rounded-md transition duration-150 ease-in-out shadow-sm">
                  Aplicar Situação
                </button>
                <button id="bulk-delete" class="h-9 px-4 bg-red-600 hover:bg-red-700 dark:bg-red-600 dark:hover:bg-red-700 text-white text-sm font-medium rounded-md transition duration-150 ease-in-out shadow-sm">
                  Deletar
                </button>
                <button id="bulk-clear" class="h-9 px-4 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition duration-150">
                  Limpar
                </button>
              </div>
            </div>
            @endunless

            @unless($isConsultor)
              <div
                id="bulk-confirm-modal"
                class="hidden fixed inset-0 z-50 items-center justify-center bg-black/60 dark:bg-black/80 backdrop-blur-sm px-4"
              >
              <div class="w-full max-w-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Confirmar Alteração</h3>
                  <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Revise os patrimônios selecionados</p>
                </div>

                {{-- Conteúdo compacto --}}
                <div class="px-6 py-4 space-y-3 max-h-64 overflow-y-auto">
                  
                  {{-- Nova situação com badge destacado --}}
                  <div id="bulk-confirm-situacao-wrapper" style="display:none;" class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-700 rounded-lg p-3">
                    <p class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-1">Novo Status</p>
                    <div class="flex items-center gap-2">
                      <span id="bulk-confirm-new" class="text-base font-bold text-indigo-700 dark:text-indigo-300"></span>
                    </div>
                  </div>

                  {{-- Lista de patrimônios compacta --}}
                  <div class="space-y-2">
                    <div class="flex items-center justify-between">
                      <p class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">Patrimônios a Alterar:</p>
                      <span class="inline-block px-2.5 py-1 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 text-xs font-bold">
                        <span id="bulk-confirm-count">0</span> / <span id="bulk-confirm-count-header">0</span>
                      </span>
                    </div>
                    <div id="bulk-confirm-list" class="max-h-48 overflow-y-auto space-y-1.5 pr-1">
                      <!-- itens gerados via JS -->
                    </div>
                  </div>
                </div>

                {{-- Footer com ações --}}
                <div class="bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700 px-6 py-4 flex justify-end gap-3">
                  <button id="bulk-confirm-cancel" class="px-4 py-2 rounded-md text-sm font-semibold border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 transition">Cancelar</button>
                  <button id="bulk-confirm-yes" class="px-4 py-2 rounded-md text-sm font-semibold bg-indigo-600 hover:bg-indigo-700 text-white transition">✓ Confirmar</button>
                </div>
              </div>
            </div>
            @endunless

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
                      <path class="opacity-75" fill="currentColor" d="M4 12a 8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
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
                  <path class="opacity-75" fill="currentColor" d="M4 12a 8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
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

    {{-- Modal de create/edit --}}
    <!-- Overlay Background -->
    <div
      x-show="formModalOpen"
      x-transition:enter="transition ease-out duration-300"
      x-transition:enter-start="opacity-0"
      x-transition:enter-end="opacity-100"
      x-transition:leave="transition ease-in duration-200"
      x-transition:leave-start="opacity-100"
      x-transition:leave-end="opacity-0"
      x-cloak
      class="fixed inset-0 z-[60] bg-black/60 dark:bg-black/80 p-3 sm:p-6"
      @click="if(!formModalLoading) closeFormModal()"
    ></div>

    <!-- Loading Screen (Overlay) -->
    <div
      x-show="formModalOpen && formModalLoading"
      x-transition:leave="transition ease-out duration-300"
      x-transition:leave-start="opacity-100 scale-100"
      x-transition:leave-end="opacity-0 scale-95"
      x-cloak
      class="fixed inset-0 z-[70] flex items-center justify-center pointer-events-none"
    >
      <div class="flex flex-col items-center gap-6">
        <!-- Spinner Animado -->
        <div class="relative w-20 h-20">
          <svg class="w-full h-full animate-spin" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" style="color: rgb(209, 213, 219);"></circle>
            <path class="opacity-100" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" style="color: rgb(99, 102, 241);"></path>
          </svg>
          <div class="absolute inset-0 flex items-center justify-center">
            <div class="w-16 h-16 rounded-full bg-gradient-to-r from-indigo-500/20 to-blue-500/20"></div>
          </div>
        </div>
        
        <!-- Texto de Loading -->
        <div class="text-center">
          <h3 class="text-xl font-semibold text-white mb-2">Carregando...</h3>
          <p class="text-gray-300 text-sm">Preparando o formulário para você</p>
        </div>

        <!-- Barra de Progresso Animada -->
        <div class="w-48 h-1 bg-gray-700 rounded-full overflow-hidden">
          <div class="h-full bg-gradient-to-r from-indigo-500 via-blue-500 to-indigo-500 rounded-full animate-pulse" style="animation: shimmer 2s infinite;"></div>
        </div>
      </div>
    </div>

    <!-- Modal Principal -->
    <div
      x-show="formModalOpen && !formModalLoading"
      x-cloak
      class="fixed inset-0 z-[60] flex items-center justify-center p-3 sm:p-6 pointer-events-none"
    >
      <div 
        x-show="formModalOpen && !formModalLoading"
        x-transition:enter="transition ease-out duration-500 delay-300"
        x-transition:enter-start="opacity-0 scale-95 translate-y-4"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-4"
        class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-[calc(100vw-1.5rem)] sm:max-w-[calc(100vw-3rem)] xl:max-w-[1400px] 2xl:max-w-[1600px] h-[calc(100vh-1.5rem)] sm:h-[calc(100vh-3rem)] max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] overflow-hidden border border-gray-200 dark:border-gray-700 flex flex-col min-h-0 pointer-events-auto"
        @click.self="closeFormModal"
      >
        <div class="flex items-center justify-between px-4 sm:px-6 py-4 bg-white dark:bg-gray-900">
          <div>
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white" x-text="formModalTitle"></h3>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400" x-text="formModalSubtitle" x-show="formModalSubtitle"></p>
          </div>
          <button type="button" @click="closeFormModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 text-2xl leading-none">×</button>
        </div>
        <div class="relative flex-1 min-h-0 overflow-hidden">
          <div id="patrimonio-form-modal-body" class="h-full min-h-0 overflow-y-auto overscroll-contain"></div>
        </div>
      </div>
    </div>

    {{-- Modal de confirmacao de exclusao --}}
    <div
      x-show="deleteModalOpen"
      x-transition:enter="transition ease-out duration-300"
      x-transition:enter-start="opacity-0"
      x-transition:enter-end="opacity-100"
      x-transition:leave="transition ease-in duration-200"
      x-transition:leave-start="opacity-100"
      x-transition:leave-end="opacity-0"
      x-cloak
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/60"
    >
      <div 
        x-show="deleteModalOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95 translate-y-4"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-4"
        @click.outside="deleteModalOpen=false" 
        class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md p-6 border border-gray-200 dark:border-gray-700"
      >
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
      function renderPatrimonioModalContent(html, target) {
        if (!target) return;
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const scripts = Array.from(doc.querySelectorAll('script'));
        scripts.forEach((script) => script.remove());
        target.innerHTML = doc.body.innerHTML;
        scripts.forEach((original) => {
          const script = document.createElement('script');
          if (original.type) {
            script.type = original.type;
          }
          if (original.src) {
            script.src = original.src;
            script.async = false;
          } else {
            script.text = original.textContent || '';
          }
          document.body.appendChild(script);
        });
      }

      function bindPatrimonioModalHandlers(root, onClose, onSubmit) {
        if (!root) return;
        root.querySelectorAll('[data-modal-close]').forEach((btn) => {
          btn.addEventListener('click', () => onClose());
        });
        root.querySelectorAll('form[data-modal-form]').forEach((form) => {
          if (form.dataset.modalBound === 'true') return;
          form.dataset.modalBound = 'true';
          form.addEventListener('submit', (event) => {
            event.preventDefault();
            onSubmit(form);
          });
        });
      }

      document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('#patrimonio-filter-form');
        const tableContainer = document.querySelector('#patrimonios-table-container');
        const tableContent = document.querySelector('#patrimonios-table-content');
        const loading = document.querySelector('#patrimonios-loading');
        const loadingTop = document.querySelector('#patrimonios-loading-top');
        const cleanLinks = document.querySelectorAll('[data-ajax-clean]');
        const bulkBar = document.querySelector('#bulk-status-bar');
        const bulkCount = document.querySelector('#bulk-count');
        const bulkPlural = document.querySelector('#bulk-plural');
        const bulkSelect = document.querySelector('#bulk-situacao');
        const bulkApply = document.querySelector('#bulk-apply');
        const bulkClear = document.querySelector('#bulk-clear');
        const bulkConfirmModal = document.querySelector('#bulk-confirm-modal');
        const bulkConfirmList = document.querySelector('#bulk-confirm-list');
        const bulkConfirmNew = document.querySelector('#bulk-confirm-new');
        const bulkConfirmYes = document.querySelector('#bulk-confirm-yes');
        const bulkConfirmCancel = document.querySelector('#bulk-confirm-cancel');
        const bulkConfirmClose = document.querySelector('#bulk-confirm-close');
        const bulkEndpoint = "{{ route('patrimonios.bulk-situacao') }}";
        const csrf = document.querySelector('meta[name=\"csrf-token\"]')?.content || '';
        const selectedIds = new Set();
        let pendingSituacao = null;
        const logTags = (label = 'tags') => {
          const tags = Array.from(document.querySelectorAll('#patrimonios-tags [data-ajax-tag-remove]')).map(t => t.textContent.trim());
          console.log('[PATRI] ' + label, tags);
        };
        console.log('[PATRI] bulk-js init');

        const updateBulkBar = () => {
          if (!bulkBar) return;
          const size = selectedIds.size;
          const active = size > 0;
          bulkCount.textContent = size;
          bulkPlural.textContent = size === 1 ? '' : 's';
          
          if (active) {
            bulkBar.classList.remove('hidden');
            // Forçar reflow para que a transição funcione corretamente
            void bulkBar.offsetWidth;
            bulkBar.classList.remove('opacity-0', '-translate-y-3', 'scale-90');
            bulkBar.classList.add('opacity-100', 'translate-y-0', 'scale-100');
          } else {
            bulkBar.classList.remove('opacity-100', 'translate-y-0', 'scale-100');
            bulkBar.classList.add('opacity-0', '-translate-y-3', 'scale-90');
            setTimeout(() => {
              if (size === 0) {
                bulkBar.classList.add('hidden');
              }
            }, 200);
          }
          console.log('[PATRI] bulk-bar update', {
            size,
            active,
            display: bulkBar.style.display,
            classes: bulkBar.className,
          });
        };

        const toggleId = (id, checked) => {
          if (!id) return;
          if (checked) {
            selectedIds.add(id);
          } else {
            selectedIds.delete(id);
          }
          console.log('[PATRI] toggle', id, checked, 'total', selectedIds.size);
          updateBulkBar();
        };

        const bindCheckboxes = () => {
          if (!tableContent) return;
          const boxes = tableContent.querySelectorAll('.patrimonio-checkbox');
          boxes.forEach((box) => {
            const id = box.value;
            box.checked = selectedIds.has(id);
          });
          updateBulkBar();
        };

        const refreshHeaderCheckbox = () => {};

        const clearSelection = () => {
          selectedIds.clear();
          const boxes = tableContent?.querySelectorAll('.patrimonio-checkbox') || [];
          boxes.forEach(b => { b.checked = false; });
          refreshHeaderCheckbox();
          updateBulkBar();
        };

        const runBulkUpdate = (situacao) => {
          fetch(bulkEndpoint, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({
              ids: Array.from(selectedIds),
              situacao,
            }),
          }).then(async (resp) => {
            const data = await resp.json().catch(() => ({}));
            if (!resp.ok) {
              throw new Error(data.error || `Falha ao aplicar: ${resp.status}`);
            }
            clearSelection();
            if (window.location.href) {
              ajaxFetch(window.location.href);
            }
          }).catch((err) => {
            console.error(err);
            alert(err.message || 'Falha ao aplicar situação.');
          });
        };

        const closeConfirmModal = () => {
          pendingSituacao = null;
          if (bulkConfirmModal) {
            bulkConfirmModal.classList.add('hidden');
            bulkConfirmModal.classList.remove('flex');
          }
        };

        const openConfirmModal = (situacao) => {
          pendingSituacao = situacao;
          if (!bulkConfirmModal || !bulkConfirmList || !bulkConfirmNew) {
            runBulkUpdate(situacao);
            return;
          }
          bulkConfirmNew.textContent = situacao;
          document.getElementById('bulk-confirm-count-header').textContent = selectedIds.size;
          bulkConfirmList.innerHTML = '';
          document.getElementById('bulk-confirm-count').textContent = selectedIds.size;
          selectedIds.forEach((id) => {
            const row = document.querySelector(`[data-row-id=\"${id}\"]`);
            const prev = row?.dataset?.situacao || '—';
            const patr = row?.dataset?.patrimonio || id;
            
            const item = document.createElement('div');
            item.className = 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3 hover:shadow-md hover:border-indigo-400 dark:hover:border-indigo-500 transition-all';
            
            // Número do patrimônio
            const numDiv = document.createElement('div');
            numDiv.className = 'flex items-center gap-2 mb-2';
            const numBadge = document.createElement('span');
            numBadge.className = 'px-2.5 py-1 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 font-bold text-xs';
            numBadge.textContent = `Nº ${patr}`;
            numDiv.appendChild(numBadge);
            item.appendChild(numDiv);
            
            // Transição de status com seta bonita
            const transDiv = document.createElement('div');
            transDiv.className = 'flex items-center gap-2 justify-between';
            
            const badgeFrom = document.createElement('span');
            badgeFrom.className = 'flex-1 px-2.5 py-1.5 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold text-xs text-center';
            badgeFrom.textContent = prev;
            
            const arrowContainer = document.createElement('div');
            arrowContainer.className = 'flex-shrink-0 flex items-center justify-center px-1';
            const arrow = document.createElement('div');
            arrow.innerHTML = `
              <svg class="w-5 h-5 text-indigo-500 dark:text-indigo-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
              </svg>
            `;
            arrowContainer.appendChild(arrow);
            
            const badgeTo = document.createElement('span');
            badgeTo.className = 'flex-1 px-2.5 py-1.5 rounded-md bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/50 dark:to-emerald-900/50 text-green-700 dark:text-green-300 font-bold text-xs text-center';
            badgeTo.textContent = situacao;
            
            transDiv.appendChild(badgeFrom);
            transDiv.appendChild(arrowContainer);
            transDiv.appendChild(badgeTo);
            item.appendChild(transDiv);
            
            bulkConfirmList.appendChild(item);
          });
          bulkConfirmModal.classList.remove('hidden');
          bulkConfirmModal.classList.add('flex');
        };

        const applyBulkSituacao = () => {
          const situacao = bulkSelect?.value || '';
          if (selectedIds.size === 0) {
            alert('Selecione ao menos um patrimônio.');
            return;
          }
          if (!situacao) {
            alert('Escolha a situação para aplicar.');
            return;
          }
          openConfirmModal(situacao);
        };

        const swapTable = (html) => {
          if (!tableContainer) return;
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, 'text/html');
          
          // ✅ Atualizar tabela
          const fresh = doc.querySelector('#patrimonios-table-content');
          if (fresh && tableContent) tableContent.innerHTML = fresh.innerHTML;

          // ✅ Atualizar tags de filtro - procura em todo o documento
          // As tags estão em patrimonios/partials/filter-form dentro de patrimonios-tags div
          const freshTags = doc.querySelector('#patrimonios-tags');
          const tagsContainer = document.querySelector('#patrimonios-tags');
          
          if (freshTags && tagsContainer) {
            // Copiar conteúdo AND atributos x-data
            tagsContainer.innerHTML = freshTags.innerHTML;
            // Copiar os atributos Alpine (x-data, etc)
            Array.from(freshTags.attributes).forEach(attr => {
              if (attr.name.startsWith('x-') || attr.name.startsWith('@')) {
                tagsContainer.setAttribute(attr.name, attr.value);
              }
            });
            // Reinicializar Alpine para as tags
            if (window.Alpine && typeof Alpine.initTree === 'function') {
              Alpine.initTree(tagsContainer);
            }
          }
          
          logTags('after-swap');
          bindCheckboxes();
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

        // ✅ Expor ajaxFetch globalmente para que possa ser chamada após salvar modal
        window.ajaxFetchPatrimonios = ajaxFetch;

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

        const checkboxChangeHandler = (e) => {
          const target = e.target;
          if (target && target.classList && target.classList.contains('patrimonio-checkbox')) {
            toggleId(target.value, target.checked);
          }
        };
        if (tableContent) tableContent.addEventListener('change', checkboxChangeHandler);
        document.addEventListener('change', checkboxChangeHandler);

        bindCheckboxes();
        bulkApply?.addEventListener('click', applyBulkSituacao);
        
        // ✅ Novo: Listener para deletar em massa
        const bulkDelete = document.querySelector('#bulk-delete');
        let pendingDeleteIds = new Set();
        const bulkDeleteEndpoint = "{{ route('patrimonios.bulk-delete') }}";
        
        const applyBulkDelete = () => {
          if (selectedIds.size === 0) {
            alert('Selecione ao menos um patrimônio.');
            return;
          }
          pendingDeleteIds = new Set(selectedIds);
          openConfirmDeleteModal();
        };
        
        const openConfirmDeleteModal = () => {
          const title = document.querySelector('#bulk-confirm-title');
          const desc = document.querySelector('#bulk-confirm-desc');
          const situacaoWrapper = document.querySelector('#bulk-confirm-situacao-wrapper');
          
          if (title) title.textContent = '⚠️ Confirmar exclusão em massa';
          if (desc) desc.textContent = 'Esta ação é irreversível! Os patrimônios serão deletados permanentemente.';
          if (situacaoWrapper) situacaoWrapper.style.display = 'none';
          
          if (!bulkConfirmModal || !bulkConfirmList) return;
          bulkConfirmList.innerHTML = '';
          pendingDeleteIds.forEach((id) => {
            const row = document.querySelector(`[data-row-id="${id}"]`);
            const patr = row?.dataset?.patrimonio || id;
            const item = document.createElement('div');
            item.className = 'flex justify-between gap-2 border-b border-slate-200 dark:border-slate-700 pb-1 last:border-0';
            const left = document.createElement('div');
            left.className = 'font-semibold text-gray-900 dark:text-white';
            left.textContent = `Nº Patrimônio ${patr}`;
            item.appendChild(left);
            bulkConfirmList.appendChild(item);
          });
          bulkConfirmModal.classList.remove('hidden');
          bulkConfirmModal.classList.add('flex');
        };
        
        const runBulkDelete = () => {
          fetch(bulkDeleteEndpoint, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({
              ids: Array.from(pendingDeleteIds),
            }),
          }).then(async (resp) => {
            const data = await resp.json().catch(() => ({}));
            if (!resp.ok) {
              throw new Error(data.error || `Falha ao deletar: ${resp.status}`);
            }
            clearSelection();
            pendingDeleteIds.clear();
            if (window.location.href) {
              ajaxFetch(window.location.href);
            }
          }).catch((err) => {
            console.error(err);
            alert(err.message || 'Falha ao deletar patrimônios.');
          });
        };
        
        bulkDelete?.addEventListener('click', applyBulkDelete);
        
        // ✅ Modificar o listener do botão YES para detectar operação
        const originalConfirmYes = bulkConfirmYes?.onclick;
        bulkConfirmYes?.addEventListener('click', () => {
          if (pendingDeleteIds.size > 0) {
            closeConfirmModal();
            runBulkDelete();
          } else if (pendingSituacao) {
            const situacao = pendingSituacao;
            closeConfirmModal();
            runBulkUpdate(situacao);
          }
        });
        
        bulkClear?.addEventListener('click', clearSelection);
        bulkConfirmYes?.addEventListener('click', () => {
          if (pendingSituacao) {
            const situacao = pendingSituacao;
            closeConfirmModal();
            runBulkUpdate(situacao);
          }
        });
        bulkConfirmCancel?.addEventListener('click', closeConfirmModal);
        bulkConfirmClose?.addEventListener('click', closeConfirmModal);
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
          formModalOpen: false,
          formModalLoading: false,
          formModalTitle: '',
          formModalSubtitle: '',
          formModalMode: null,
          formModalId: null,
          handleCreateKey(event) {
            if (!event || event.defaultPrevented) return;
            if (event.ctrlKey || event.metaKey || event.altKey) return;
            if (event.key !== 'c' && event.key !== 'C') return;
            const target = event.target;
            const tag = target ? target.tagName : '';
            if (target && (target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(tag))) return;
            if (!document.querySelector('[data-create-patrimonio]')) return;
            if (this.formModalOpen) return;
            event.preventDefault();
            this.openCreateModal();
          },
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
            this.$watch('formModalOpen', (open) => {
              document.documentElement.classList.toggle('overflow-hidden', open);
              document.body.classList.toggle('overflow-hidden', open);
            });
            window.addEventListener('patrimonio-modal-create', () => {
              this.openCreateModal();
            });
            window.submitPatrimonioModalForm = (form) => this.submitModalForm(form);
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
          openCreateModal() {
            this.openFormModal('create');
          },
          openEditModal(id) {
            if (!id) return;
            this.openFormModal('edit', id);
          },
          openFormModal(mode, id = null) {
            const modalBody = document.getElementById('patrimonio-form-modal-body');
            if (!modalBody) return;
            if (mode === 'edit' && !id) return;

            this.formModalMode = mode;
            this.formModalId = id;
            this.formModalTitle = mode === 'create' ? 'Cadastrar Patrimônio' : 'Editar Patrimônio';
            this.formModalSubtitle = mode === 'create'
              ? 'Cadastre um novo patrimônio.'
              : 'Atualize os dados do patrimônio.';
            this.formModalOpen = true;
            this.formModalLoading = true;

            const baseUrl = mode === 'create'
              ? "{{ route('patrimonios.create') }}"
              : "{{ url('patrimonios') }}/" + encodeURIComponent(id) + "/edit";
            const url = baseUrl + (baseUrl.includes('?') ? '&' : '?') + 'modal=1';

            fetch(url, {
              headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
              },
            })
              .then((resp) => {
                if (!resp.ok) {
                  throw new Error(`HTTP ${resp.status}`);
                }
                return resp.text();
              })
              .then((html) => {
                this.applyFormModalHtml(html);
              })
              .catch((err) => {
                console.error('[PATRI] Modal fetch error', err);
                modalBody.innerHTML = '<div class="p-6 text-sm text-red-600">Falha ao carregar formulario.</div>';
              })
              .finally(() => {
                this.formModalLoading = false;
              });
          },
          closeFormModal() {
            this.formModalOpen = false;
            this.formModalLoading = false;
            this.formModalTitle = '';
            this.formModalSubtitle = '';
            this.formModalMode = null;
            this.formModalId = null;
            const modalBody = document.getElementById('patrimonio-form-modal-body');
            if (modalBody) {
              modalBody.innerHTML = '';
            }
            if (typeof window.destroyPatrimonioEditForm === 'function') {
              window.destroyPatrimonioEditForm();
            }
          },
          applyFormModalHtml(html) {
            const modalBody = document.getElementById('patrimonio-form-modal-body');
            if (!modalBody) return;
            renderPatrimonioModalContent(html, modalBody);
            if (window.Alpine && typeof window.Alpine.initTree === 'function') {
              window.Alpine.initTree(modalBody);
            }
            if (typeof window.initPatrimonioEditForm === 'function') {
              window.initPatrimonioEditForm(modalBody);
            }
            bindPatrimonioModalHandlers(
              modalBody,
              () => this.closeFormModal(),
              (form) => this.submitModalForm(form)
            );
          },
          async submitModalForm(form) {
            if (!form) return;
            this.formModalLoading = true;
            const formData = new FormData(form);
            const method = (form.getAttribute('method') || 'POST').toUpperCase();

            try {
              const resp = await fetch(form.action, {
                method,
                body: formData,
                headers: {
                  'X-Requested-With': 'XMLHttpRequest',
                  'Accept': 'text/html',
                },
              });
              const contentType = resp.headers.get('content-type') || '';
              if (resp.status === 422) {
                const html = await resp.text();
                this.applyFormModalHtml(html);
                return;
              }
              if (contentType.includes('application/json')) {
                const data = await resp.json().catch(() => ({}));
                if (data.redirect) {
                  window.location.href = data.redirect;
                  return;
                }
              }
              // ✅ SUCESSO: Fechar modal e recarregar grid via AJAX (mantendo filtros e paginação)
              this.closeFormModal();
              const currentUrl = window.location.pathname + window.location.search;
              if (window.ajaxFetchPatrimonios) {
                window.ajaxFetchPatrimonios(currentUrl);
              } else {
                window.location.reload();
              }
            } catch (err) {
              console.error('[PATRI] Modal submit error', err);
              alert('Falha ao salvar patrimonio.');
            } finally {
              this.formModalLoading = false;
            }
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
          openModalConsulta(id) {
            // Fetch dados do patrimônio via API
            fetch(`/api/patrimonios/id/${id}`)
              .then(res => {
                if (!res.ok) {
                  throw new Error(`HTTP ${res.status}: ${res.statusText}`);
                }
                return res.json();
              })
              .then(data => {
                if (data.success && data.patrimonio) {
                  this.showConsultaModal(data.patrimonio);
                } else {
                  console.error('Erro na resposta:', data);
                  alert(data.error || 'Erro ao carregar dados do patrimônio');
                }
              })
              .catch(err => {
                console.error('[PATRI] Erro ao buscar patrimônio:', err);
                alert('Erro ao buscar patrimônio: ' + err.message);
              });
          },
          showConsultaModal(patrimonio) {
            // Preencher modal com dados
            const modal = document.querySelector('#modal-consulta');
            if (!modal) {
              console.error('[PATRI] Modal não encontrado');
              return;
            }
            
            console.log('[PATRI] Preenchendo modal com:', patrimonio);
            
            // Preencher campos com acesso correto às propriedades
            // FONTE DE VERDADE: Sempre usar CDPROJETO direto do patrimônio, não do local
            let projetoTexto = '-';
            if (patrimonio.CDPROJETO && patrimonio.projeto) {
              projetoTexto = `${patrimonio.projeto.CDPROJETO} - ${patrimonio.projeto.NOMEPROJETO || patrimonio.projeto.NMPROJETO || ''}`;
            } else if (patrimonio.CDPROJETO) {
              projetoTexto = patrimonio.CDPROJETO;
            }
            
            const campos = {
              'consulta-nupatrimonio': patrimonio.NUPATRIMONIO || '-',
              'consulta-depatrimonio': patrimonio.DEPATRIMONIO || '-',
              'consulta-codobjeto': patrimonio.CODOBJETO || '-',
              'consulta-modelo': patrimonio.MODELO || '-',
              'consulta-marca': patrimonio.MARCA || '-',
              'consulta-projeto': projetoTexto,
              'consulta-local': patrimonio.local?.delocal || '-',
              'consulta-responsavel': patrimonio.funcionario?.NMFUNCIONARIO || patrimonio.CDMATRFUNCIONARIO || '-',
              'consulta-situacao': patrimonio.SITUACAO || '-',
              'consulta-usuario': patrimonio.USUARIO || '-',
              'consulta-dtaquisicao': patrimonio.DTAQUISICAO ? new Date(patrimonio.DTAQUISICAO).toLocaleDateString('pt-BR') : '-',
              'consulta-dtoperacao': patrimonio.DTOPERACAO ? new Date(patrimonio.DTOPERACAO).toLocaleString('pt-BR') : '-',
            };
            
            // Log detalhado
            console.log('[PATRI] Projeto:', patrimonio.projeto);
            console.log('[PATRI] Local:', patrimonio.local);
            console.log('[PATRI] Funcionário:', patrimonio.funcionario);
            
            for (const [id, valor] of Object.entries(campos)) {
              const el = document.querySelector(`#${id}`);
              if (el) {
                el.textContent = String(valor);
                console.log(`[PATRI] Campo #${id}: ${valor}`);
              } else {
                console.warn(`[PATRI] Elemento #${id} não encontrado`);
              }
            }
            
            // Mostrar modal
            modal.style.display = 'flex';
            console.log('[PATRI] Modal aberto com sucesso');
          },
          fecharConsultaModal() {
            const modal = document.querySelector('#modal-consulta');
            if (modal) modal.style.display = 'none';
          },
        };
      }
    </script>

    {{-- MODAL DE CONSULTA PARA CONSULTORES --}}
    <div id="modal-consulta" class="fixed inset-0 bg-black/50 dark:bg-black/70 z-50 flex items-center justify-center p-2 sm:p-4" style="display: none;">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl w-full h-full max-w-6xl max-h-[95vh] sm:max-h-[90vh] overflow-y-auto">
        {{-- Header --}}
        <div class="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 sm:px-6 py-4 flex justify-between items-center">
          <h3 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white">📋 Consulta de Patrimônio</h3>
          <button onclick="patrimoniosIndex().fecharConsultaModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-2xl leading-none">×</button>
        </div>

        {{-- Content --}}
        <div class="px-4 sm:px-6 py-6 space-y-6">
          {{-- Identificação --}}
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            <div class="bg-white dark:bg-gray-700 p-4 rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm">
              <p class="text-xs font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-3">Nº Patrimônio</p>
              <p id="consulta-nupatrimonio" class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white min-h-7 break-words">-</p>
            </div>
            <div class="bg-white dark:bg-gray-700 p-4 rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm">
              <p class="text-xs font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-3">Código Objeto</p>
              <p id="consulta-codobjeto" class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white min-h-7 break-words">-</p>
            </div>
            <div class="bg-white dark:bg-gray-700 p-4 rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm">
              <p class="text-xs font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-3">Situação</p>
              <p id="consulta-situacao" class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white min-h-7 break-words">-</p>
            </div>
          </div>

          {{-- Descrição --}}
          <div class="bg-white dark:bg-gray-700 p-4 rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm">
            <p class="text-xs font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-3">Descrição</p>
            <p id="consulta-depatrimonio" class="text-base sm:text-lg text-gray-900 dark:text-white break-words min-h-6">-</p>
          </div>

          {{-- Características --}}
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
            <div class="bg-white dark:bg-gray-700 p-4 rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm">
              <p class="text-xs font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-3">Modelo</p>
              <p id="consulta-modelo" class="text-base sm:text-lg text-gray-900 dark:text-white min-h-6 break-words">-</p>
            </div>
            <div class="bg-white dark:bg-gray-700 p-4 rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm">
              <p class="text-xs font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-3">Marca</p>
              <p id="consulta-marca" class="text-base sm:text-lg text-gray-900 dark:text-white min-h-6 break-words">-</p>
            </div>
          </div>

          {{-- Localização --}}
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
            <div class="bg-white dark:bg-gray-700 p-4 rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm">
              <p class="text-xs font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-3">Projeto</p>
              <p id="consulta-projeto" class="text-base sm:text-lg text-gray-900 dark:text-white min-h-6 break-words">-</p>
            </div>
            <div class="bg-white dark:bg-gray-700 p-4 rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm">
              <p class="text-xs font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-3">Local Físico</p>
              <p id="consulta-local" class="text-base sm:text-lg text-gray-900 dark:text-white min-h-6 break-words">-</p>
            </div>
          </div>

          {{-- Responsável --}}
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
            <div class="bg-white dark:bg-gray-700 p-4 rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm">
              <p class="text-xs font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-3">Responsável</p>
              <p id="consulta-responsavel" class="text-base sm:text-lg text-gray-900 dark:text-white min-h-6 break-words">-</p>
            </div>
            <div class="bg-white dark:bg-gray-700 p-4 rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm">
              <p class="text-xs font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-3">Criado por</p>
              <p id="consulta-usuario" class="text-base sm:text-lg text-gray-900 dark:text-white min-h-6 break-words">-</p>
            </div>
          </div>

          {{-- Datas --}}
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
            <div class="bg-white dark:bg-gray-700 p-4 rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm">
              <p class="text-xs font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-3">Data de Aquisição</p>
              <p id="consulta-dtaquisicao" class="text-base sm:text-lg text-gray-900 dark:text-white min-h-6 break-words">-</p>
            </div>
            <div class="bg-white dark:bg-gray-700 p-4 rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm">
              <p class="text-xs font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-3">Data de Operação</p>
              <p id="consulta-dtoperacao" class="text-base sm:text-lg text-gray-900 dark:text-white min-h-6 break-words">-</p>
            </div>
          </div>
        </div>

        {{-- Footer --}}
        <div class="sticky bottom-0 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 px-4 sm:px-6 py-4 flex justify-end gap-2">
          <button onclick="patrimoniosIndex().fecharConsultaModal()" class="px-4 py-2 bg-gray-600 dark:bg-gray-700 text-white rounded-md hover:bg-gray-700 dark:hover:bg-gray-600 transition text-sm sm:text-base">Fechar</button>
        </div>
      </div>
    </div>
    @include('patrimonios.partials.edit-form-script')

    <style>
      @keyframes shimmer {
        0% {
          transform: translateX(-100%);
          opacity: 0;
        }
        50% {
          opacity: 1;
        }
        100% {
          transform: translateX(100%);
          opacity: 0;
        }
      }
    </style>
  @endpush
</x-app-layout>

