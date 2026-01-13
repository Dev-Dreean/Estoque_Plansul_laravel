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
                                <div id="bulk-conferido-group" class="flex items-center gap-1">
                  <button id="bulk-conferido-yes" type="button" class="h-9 w-9 rounded-md border border-green-600 bg-green-600 text-white transition flex items-center justify-center" aria-pressed="false" aria-label="Verificado" title="Verificado">
                    <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 00-1.06 1.06l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                    </svg>
                  </button>
                  <button id="bulk-conferido-no" type="button" class="h-9 w-9 rounded-md border border-red-600 bg-red-600 text-white transition flex items-center justify-center" aria-pressed="false" aria-label="Nao verificado" title="Nao verificado">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                      <circle cx="12" cy="12" r="9" />
                      <path d="M8 12h8" />
                    </svg>
                  </button>
                </div>                <select id="bulk-situacao" class="h-9 px-3 text-sm border border-gray-200 dark:border-gray-700 rounded-md bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-200 placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-indigo-500 dark:focus:border-indigo-400 transition">
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
                <div class="ml-auto">
                  <button id="bulk-clear" class="h-9 px-4 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition duration-150">
                    Limpar
                  </button>
                </div>
              </div>
            </div>
            @endunless

            @unless($isConsultor)
              <div
                id="bulk-confirm-modal"
                class="hidden fixed inset-0 z-50 items-center justify-center bg-black/60 dark:bg-black/80 backdrop-blur-sm px-4"
              >
              <div class="w-full max-w-6xl max-h-[90vh] bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col">
                
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Confirmar Alteração</h3>
                  <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Revise os patrimônios selecionados</p>
                </div>

                {{-- Conteúdo compacto --}}
                <div class="px-6 py-4 space-y-4 overflow-y-auto bg-white dark:bg-gray-800">
                  
                  <div id="bulk-confirm-situacao-wrapper" style="display:none;" class="bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-700 rounded-lg p-3">
                    <p class="text-xs font-semibold text-indigo-700 dark:text-indigo-300 uppercase tracking-wider mb-1">Novo Status</p>
                    <div class="flex items-center gap-2">
                      <span id="bulk-confirm-new" class="text-base font-bold text-indigo-700 dark:text-indigo-200"></span>
                    </div>
                  </div>

                  {{-- Lista de patrimônios compacta --}}
                  <div class="space-y-2">
                    <div class="flex items-center justify-between">
                      <p class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">Patrimônios a Alterar:</p>
                      <span class="inline-block px-2.5 py-1 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-200 text-xs font-bold border border-indigo-200 dark:border-indigo-700/60">
                        <span id="bulk-confirm-count">0</span> / <span id="bulk-confirm-count-header">0</span>
                      </span>
                    </div>
                    <div id="bulk-confirm-list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 max-h-[55vh] overflow-y-auto pr-1">
                      <!-- itens gerados via JS -->
                    </div>
                  </div>
                </div>

                {{-- Footer com ações --}}
                <div class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 px-6 py-4 flex justify-end gap-3">
                  <button id="bulk-confirm-cancel" class="px-4 py-2 rounded-md text-sm font-semibold border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition">Cancelar</button>
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

    @unless($isConsultor)
      <style>
        /* Bulk modal isolated theme (safe to remove) */
        html[data-theme='light'] .bulk-modal-theme {
          --bulk-modal-bg-list: #fffaf5;
          --bulk-modal-bg-import: #f4fff9;
          --bulk-guide-bg: #ffffff;
        }
        html[data-theme='dark'] .bulk-modal-theme {
          --bulk-modal-bg-list: #111827;
          --bulk-modal-bg-import: #111827;
          --bulk-guide-bg: #111827;
        }
        .bulk-modal-theme.bulk-modal--list {
          background-color: var(--bulk-modal-bg-list);
        }
        .bulk-modal-theme.bulk-modal--import {
          background-color: var(--bulk-modal-bg-import);
        }
        .bulk-guide-theme {
          background-color: var(--bulk-guide-bg);
        }
      </style>
      <div
        x-show="bulkImportModalOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        class="fixed inset-0 z-[65] bg-black/60 dark:bg-black/80 backdrop-blur-sm p-3 sm:p-6"
        @click="closeBulkImportModal()"
      ></div>

      <div
        x-show="bulkImportModalOpen"
        x-cloak
        class="fixed inset-0 z-[70] flex items-center justify-center p-3 sm:p-6 pointer-events-none"
      >
          <div
          x-show="bulkImportModalOpen"
          x-transition:enter="transition ease-out duration-300"
          x-transition:enter-start="opacity-0 scale-95 translate-y-3"
          x-transition:enter-end="opacity-100 scale-100 translate-y-0"
          x-transition:leave="transition ease-in duration-200"
          x-transition:leave-start="opacity-100 scale-100 translate-y-0"
          x-transition:leave-end="opacity-0 scale-95 translate-y-3"
          class="bulk-modal-theme rounded-lg shadow-xl w-full max-w-3xl max-h-[90vh] overflow-hidden border border-gray-200 dark:border-gray-700 flex flex-col pointer-events-auto p-6 transition-colors duration-200 opacity-100"
          :class="bulkModalMode === 'list' ? 'bulk-modal--list' : 'bulk-modal--import'"
          @click.stop
        >
          <div class="flex flex-col gap-3 pb-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-start justify-between gap-4">
              <div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white" x-text="bulkModalMode === 'list' ? 'Buscar patrimonios' : 'Atualizacao em massa'"></h3>
                <p class="text-sm text-gray-500 dark:text-gray-400" x-text="bulkModalMode === 'list' ? 'Envie apenas os numeros e receba a planilha completa pronta para editar.' : 'Envie a planilha preenchida para atualizar varios patrimonios de uma vez.'"></p>
              </div>
              <button type="button" @click="closeBulkImportModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 text-2xl leading-none">x</button>
            </div>
            <div class="flex items-center justify-center">
              <div class="inline-flex rounded-md border border-gray-200 dark:border-gray-700 overflow-hidden">
                <button type="button" @click="bulkModalMode = 'list'" class="px-3 py-1.5 text-xs font-semibold transition-colors duration-200" :class="bulkModalMode === 'list' ? 'bg-orange-500 text-white' : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 hover:bg-orange-50 dark:hover:bg-gray-800'">Buscar patrimonios</button>
                <button type="button" @click="bulkModalMode = 'import'" class="px-3 py-1.5 text-xs font-semibold transition-colors duration-200" :class="bulkModalMode === 'import' ? 'bg-emerald-600 text-white' : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 hover:bg-emerald-50 dark:hover:bg-gray-800'">Atualizacao em massa</button>
              </div>
            </div>

          </div>
          <div class="pt-6 space-y-7 overflow-y-auto">
            <div
              x-show="bulkModalMode === 'import'"
              x-transition:enter="transition ease-out duration-300"
              x-transition:enter-start="opacity-0 translate-y-2"
              x-transition:enter-end="opacity-100 translate-y-0"
              x-transition:leave="transition ease-in duration-200"
              x-transition:leave-start="opacity-100 translate-y-0"
              x-transition:leave-end="opacity-0 translate-y-2"
              class="space-y-6"
              :class="bulkModalMode === 'import' ? '' : 'hidden'"
            >
            <div x-data="{ open: false }" class="bulk-guide-theme border border-emerald-200 dark:border-emerald-700 rounded-lg p-4">
              <button
                type="button"
                class="w-full flex items-center justify-between text-sm font-semibold text-emerald-800 dark:text-emerald-200"
                @click="open = !open"
                :aria-expanded="open.toString()"
              >
                <span>Guia de como usar (clique aqui)</span>
                <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : 'rotate-0'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                  <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.7a.75.75 0 1 1 1.06 1.06l-4.24 4.25a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08z" clip-rule="evenodd" />
                </svg>
              </button>
              <div
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-1"
                class="mt-3"
              >
                <p class="text-sm text-emerald-900 dark:text-emerald-100">
                  A <span class="font-bold text-orange-600 dark:text-orange-300 text-[15px]">atualizacao em massa</span> permite mudar varios patrimonios de uma vez.
                </p>
                <div class="mt-4 grid grid-cols-3 gap-4 min-w-[720px]" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem;">
                  <div class="rounded-lg border border-emerald-200 dark:border-emerald-700 bg-transparent p-4">
                    <div class="flex items-center gap-2 text-emerald-600 dark:text-emerald-300">
                      <span class="inline-flex h-7 min-w-[1.75rem] items-center justify-center rounded-full text-sm font-semibold">1</span>
                      <p class="text-sm font-semibold">Baixe o modelo</p>
                    </div>
                    <p class="text-sm text-gray-700 dark:text-gray-100 mt-2">Use <span class="font-bold text-orange-600 dark:text-orange-300 text-[15px]">Baixar modelo</span>.</p>
                  </div>
                  <div class="rounded-lg border border-emerald-200 dark:border-emerald-700 bg-transparent p-4">
                    <div class="flex items-center gap-2 text-emerald-600 dark:text-emerald-300">
                      <span class="inline-flex h-7 min-w-[1.75rem] items-center justify-center rounded-full text-sm font-semibold">2</span>
                      <p class="text-sm font-semibold">Preencha o que quiser</p>
                    </div>
                    <p class="text-sm text-gray-700 dark:text-gray-100 mt-2">Coloque o <span class="font-bold text-orange-600 dark:text-orange-300 text-[15px]">numero do patrimonio</span> e altere so o necessario.</p>
                  </div>
                  <div class="rounded-lg border border-emerald-200 dark:border-emerald-700 bg-transparent p-4">
                    <div class="flex items-center gap-2 text-emerald-600 dark:text-emerald-300">
                      <span class="inline-flex h-7 min-w-[1.75rem] items-center justify-center rounded-full text-sm font-semibold">3</span>
                      <p class="text-sm font-semibold">Envie e simule</p>
                    </div>
                    <p class="text-sm text-gray-700 dark:text-gray-100 mt-2">Envie a planilha e use <span class="font-bold text-orange-600 dark:text-orange-300 text-[15px]">Simular</span> se quiser testar.</p>
                  </div>
                </div>
              </div>
            </div>

            <div class="flex flex-wrap items-center gap-4">
              <a
                href="{{ asset('templates/patrimonios_bulk_update_template.xlsx') }}"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold"
                download
              >
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                  <path d="M12 3a1 1 0 0 1 1 1v9.59l2.3-2.3a1 1 0 1 1 1.4 1.42l-4 4a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.42L11 13.59V4a1 1 0 0 1 1-1z"></path>
                  <path d="M5 19a1 1 0 0 1 1-1h12a1 1 0 0 1 0 2H6a1 1 0 0 1-1-1z"></path>
                </svg>
                Baixar modelo
              </a>
              <span class="text-xs text-gray-500 dark:text-gray-400">Formato: XLSX</span>
            </div>

            <form class="space-y-5" @submit.prevent="submitBulkImport($event)" enctype="multipart/form-data">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Planilha</label>
                <div
                  class="relative border-2 border-dashed rounded-lg p-4 text-center transition
                    border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800
                    hover:border-emerald-400 dark:hover:border-emerald-500"
                  @dragover.prevent="bulkImportDragging = true"
                  @dragleave.prevent="bulkImportDragging = false"
                  @drop.prevent="handleBulkDrop($event)"
                  :class="bulkImportDragging ? 'border-emerald-500 bg-emerald-50/40 dark:bg-emerald-900/20' : ''"
                >
                  <input id="bulk-import-file" type="file" name="arquivo" accept=".xlsx" class="absolute inset-0 opacity-0 cursor-pointer" @change="onBulkFileSelected($event)" required>
                  <div class="space-y-1 pointer-events-none">
                    <p class="text-sm text-gray-700 dark:text-gray-200 font-medium">Clique para selecionar ou arraste o arquivo aqui</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">XLSX</p>
                    <p class="text-xs text-gray-600 dark:text-gray-300" x-show="bulkImportFileName" x-text="`Arquivo: ${bulkImportFileName}`"></p>
                  </div>
                </div>
              </div>
              <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="dry_run" value="1" class="rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500">
                Simular (nao grava no banco)
              </label>
              <div class="flex justify-end gap-3 pt-1">
                <button type="button" @click="closeBulkImportModal()" class="px-4 py-2 rounded-md text-sm font-semibold border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">Cancelar</button>
                <button type="submit" :disabled="bulkImportLoading" class="px-4 py-2 rounded-md text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 text-white transition disabled:opacity-70">
                  <span x-show="!bulkImportLoading">Enviar e processar</span>
                  <span x-show="bulkImportLoading">Processando...</span>
                </button>
              </div>
            </form>

            <template x-if="bulkImportOutput">
              <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                <p class="text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide mb-2">Resultado</p>
                <pre class="text-xs whitespace-pre-wrap text-gray-800 dark:text-gray-100" x-text="bulkImportOutput"></pre>
              </div>
            </template>

            <template x-if="bulkImportError">
              <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg p-3 text-sm text-red-700 dark:text-red-200" x-text="bulkImportError"></div>
            </template>
            </div>
            <div
              x-show="bulkModalMode === 'list'"
              x-transition:enter="transition ease-out duration-300"
              x-transition:enter-start="opacity-0 translate-y-2"
              x-transition:enter-end="opacity-100 translate-y-0"
              x-transition:leave="transition ease-in duration-200"
              x-transition:leave-start="opacity-100 translate-y-0"
              x-transition:leave-end="opacity-0 translate-y-2"
              class="space-y-6"
              :class="bulkModalMode === 'list' ? '' : 'hidden'"
            >
              <div x-data="{ open: false }" class="bulk-guide-theme border border-orange-200 dark:border-orange-700 rounded-lg p-4 space-y-4">
                <button
                  type="button"
                  class="w-full flex items-center justify-between text-sm font-semibold text-orange-700 dark:text-orange-200"
                  @click="open = !open"
                  :aria-expanded="open.toString()"
                >
                  <span>Guia de como usar (clique aqui)</span>
                  <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : 'rotate-0'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.7a.75.75 0 1 1 1.06 1.06l-4.24 4.25a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08z" clip-rule="evenodd" />
                  </svg>
                </button>
                <div
                  x-show="open"
                  x-transition:enter="transition ease-out duration-200"
                  x-transition:enter-start="opacity-0 -translate-y-1"
                  x-transition:enter-end="opacity-100 translate-y-0"
                  x-transition:leave="transition ease-in duration-150"
                  x-transition:leave-start="opacity-100 translate-y-0"
                  x-transition:leave-end="opacity-0 -translate-y-1"
                >
                  <p class="text-sm text-orange-900 dark:text-orange-100">
                    <span class="font-bold text-orange-600 dark:text-orange-300 text-[15px]">Buscar patrimonios</span> preenche os dados para voce. Assim, voce so edita o que precisar.
                  </p>
                  <div class="mt-4 grid grid-cols-3 gap-4 min-w-[720px]" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem;">
                  <div class="rounded-lg border border-orange-200 dark:border-orange-700 bg-transparent p-4">
                    <div class="flex items-center gap-2 text-orange-600 dark:text-orange-300">
                      <span class="inline-flex h-7 min-w-[1.75rem] items-center justify-center rounded-full text-sm font-semibold">1</span>
                      <p class="text-sm font-semibold">Baixe a planilha</p>
                    </div>
                    <p class="text-sm text-gray-700 dark:text-gray-100 mt-2">Clique em <span class="font-bold text-orange-600 dark:text-orange-300 text-[15px]">Baixar planilha de numeros</span>.</p>
                  </div>
                  <div class="rounded-lg border border-orange-200 dark:border-orange-700 bg-transparent p-4">
                    <div class="flex items-center gap-2 text-orange-600 dark:text-orange-300">
                      <span class="inline-flex h-7 min-w-[1.75rem] items-center justify-center rounded-full text-sm font-semibold">2</span>
                      <p class="text-sm font-semibold">Preencha os numeros</p>
                    </div>
                    <p class="text-sm text-gray-700 dark:text-gray-100 mt-2">Coloque um <span class="font-bold text-orange-600 dark:text-orange-300 text-[15px]">numero de patrimonio</span> por linha.</p>
                  </div>
                  <div class="rounded-lg border border-orange-200 dark:border-orange-700 bg-transparent p-4">
                    <div class="flex items-center gap-2 text-orange-600 dark:text-orange-300">
                      <span class="inline-flex h-7 min-w-[1.75rem] items-center justify-center rounded-full text-sm font-semibold">3</span>
                      <p class="text-sm font-semibold">Envie aqui</p>
                    </div>
                    <p class="text-sm text-gray-700 dark:text-gray-100 mt-2">Anexe a planilha no campo abaixo e clique em <span class="font-bold text-orange-600 dark:text-orange-300 text-[15px]">Gerar planilha completa</span>.</p>
                  </div>
                  </div>
                </div>
              </div>
              <div class="flex flex-wrap items-center gap-4">
                <a
                  href="{{ asset('templates/patrimonios_lista_template.xlsx') }}"
                  class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold"
                  download
                >
                  <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 3a1 1 0 0 1 1 1v9.59l2.3-2.3a1 1 0 1 1 1.4 1.42l-4 4a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.42L11 13.59V4a1 1 0 0 1 1-1z"></path>
                    <path d="M5 19a1 1 0 0 1 1-1h12a1 1 0 0 1 0 2H6a1 1 0 0 1-1-1z"></path>
                  </svg>
                  Baixar planilha de numeros
                </a>
                <span class="text-xs text-gray-500 dark:text-gray-400">Formato: XLSX</span>
              </div>

              <form class="space-y-5" @submit.prevent="submitBulkExportList($event)" enctype="multipart/form-data">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Planilha com numeros</label>
                  <div
                    class="relative border-2 border-dashed rounded-lg p-4 text-center transition
                      border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800
                      hover:border-orange-400 dark:hover:border-orange-500"
                    @dragover.prevent="bulkListDragging = true"
                    @dragleave.prevent="bulkListDragging = false"
                    @drop.prevent="handleBulkListDrop($event)"
                    :class="bulkListDragging ? 'border-orange-500 bg-orange-50/40 dark:bg-orange-900/20' : ''"
                  >
                    <input id="bulk-list-file" type="file" name="arquivo_lista" accept=".xlsx" class="absolute inset-0 opacity-0 cursor-pointer" @change="onBulkListSelected($event)" required>
                    <div class="space-y-1 pointer-events-none">
                      <p class="text-sm text-gray-700 dark:text-gray-200 font-medium">Clique para selecionar ou arraste o arquivo aqui</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">XLSX</p>
                      <p class="text-xs text-gray-600 dark:text-gray-300" x-show="bulkListFileName" x-text="`Arquivo: ${bulkListFileName}`"></p>
                    </div>
                  </div>
                </div>
                <div class="flex justify-end gap-3 pt-1">
                  <button type="button" @click="closeBulkImportModal()" class="px-4 py-2 rounded-md text-sm font-semibold border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">Cancelar</button>
                  <button type="submit" class="px-4 py-2 rounded-md text-sm font-semibold bg-orange-500 hover:bg-orange-600 text-white transition">Gerar planilha completa</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <div
        x-show="bulkImportLoading"
        x-transition:leave="transition ease-out duration-300"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        x-cloak
        class="fixed inset-0 z-[80] flex items-center justify-center pointer-events-none"
      >
        <div class="flex flex-col items-center gap-6">
          <div class="relative w-20 h-20">
            <svg class="w-full h-full animate-spin" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" style="color: rgb(209, 213, 219);"></circle>
              <path class="opacity-100" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 0 0-4 4H4z" style="color: rgb(99, 102, 241);"></path>
            </svg>
            <div class="absolute inset-0 flex items-center justify-center">
              <div class="w-16 h-16 rounded-full bg-gradient-to-r from-indigo-500/20 to-blue-500/20"></div>
            </div>
          </div>
          <div class="text-center">
            <h3 class="text-xl font-semibold text-white mb-2">Processando...</h3>
            <p class="text-gray-300 text-sm">Estamos aplicando as alteracoes.</p>
          </div>
          <div class="w-48 h-1 bg-gray-700 rounded-full overflow-hidden">
            <div class="h-full bg-gradient-to-r from-indigo-500 via-blue-500 to-indigo-500 rounded-full animate-pulse"></div>
          </div>
        </div>
      </div>
    @endunless

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
        const bulkConferidoYes = document.querySelector('#bulk-conferido-yes');
        const bulkConferidoNo = document.querySelector('#bulk-conferido-no');
        const bulkApply = document.querySelector('#bulk-apply');
        const bulkClear = document.querySelector('#bulk-clear');
        const bulkConfirmModal = document.querySelector('#bulk-confirm-modal');
        const bulkConfirmList = document.querySelector('#bulk-confirm-list');
        const bulkConfirmNew = document.querySelector('#bulk-confirm-new');
        const bulkConfirmYes = document.querySelector('#bulk-confirm-yes');
        const bulkConfirmCancel = document.querySelector('#bulk-confirm-cancel');
        const bulkConfirmClose = document.querySelector('#bulk-confirm-close');
        const bulkEndpoint = "{{ route('patrimonios.bulk-situacao') }}";
        const filterEndpoint = "{{ route('patrimonios.ajax-filter') }}";
        const csrf = document.querySelector('meta[name=\"csrf-token\"]')?.content || '';
        const selectedIds = new Set();
        const selectedMeta = new Map();
        let pendingSituacao = null;
        let pendingConferido = null;
        const logTags = (label = 'tags') => {
          const tags = Array.from(document.querySelectorAll('#patrimonios-tags [data-ajax-tag-remove]')).map(t => t.textContent.trim());
          console.log('[PATRI] ' + label, tags);
        };

        const normalizeSituacaoValue = (value) => {
          if (!value) return '';
          return value
            .toString()
            .trim()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/\s+/g, ' ')
            .toUpperCase();
        };

        const situationStyles = {
          'EM USO': 'bg-yellow-500 text-white border-yellow-500 dark:bg-yellow-800 dark:border-yellow-700',
          'CONSERTO': 'bg-orange-500 text-white border-orange-500 dark:bg-orange-800 dark:border-orange-700',
          'BAIXA': 'bg-slate-900 text-white border-slate-900 dark:bg-slate-800 dark:border-slate-700',
          'A DISPOSICAO': 'bg-emerald-500 text-white border-emerald-500 dark:bg-emerald-800 dark:border-emerald-700',
          'DISPONIVEL': 'bg-emerald-500 text-white border-emerald-500 dark:bg-emerald-800 dark:border-emerald-700',
        };

        const getSituacaoClasses = (value) => {
          const normalized = normalizeSituacaoValue(value);
          return situationStyles[normalized] ?? 'bg-gray-200 text-gray-900 border-gray-200 dark:bg-gray-700 dark:text-white';
        };
        console.log('[PATRI] bulk-js init');
        const storageKey = 'patrimonios.bulk.selection';
        const saveSelection = () => {
          try {
            const ids = Array.from(selectedIds);
            const meta = {};
            selectedMeta.forEach((value, key) => {
              if (selectedIds.has(key)) {
                meta[key] = value;
              }
            });
            sessionStorage.setItem(storageKey, JSON.stringify({ ids, meta }));
          } catch (_) {
          }
        };

        const loadSelection = () => {
          try {
            const raw = sessionStorage.getItem(storageKey);
            if (!raw) return;
            const data = JSON.parse(raw);
            selectedIds.clear();
            selectedMeta.clear();
            (data.ids || []).forEach((id) => {
              selectedIds.add(String(id));
            });
            Object.entries(data.meta || {}).forEach(([id, meta]) => {
              selectedMeta.set(String(id), meta);
            });
          } catch (_) {
          }
        };

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
          updateConferidoToggle();
          console.log('[PATRI] bulk-bar update', {
            size,
            active,
            display: bulkBar.style.display,
            classes: bulkBar.className,
          });
        };

        const readRowMeta = (id) => {
          const row = document.querySelector(`[data-row-id="${id}"]`);
          if (!row) return null;
          return {
            conferido: row.dataset.conferido || 'N',
            situacao: row.dataset.situacao || '-',
            patrimonio: row.dataset.patrimonio || id,
          };
        };

        const syncSelectedMeta = () => {
          selectedIds.forEach((id) => {
            const meta = readRowMeta(id);
            if (meta) {
              selectedMeta.set(id, meta);
            }
          });
        };

        const summarizeConferido = () => {
          let verificados = 0;
          let naoVerificados = 0;
          selectedMeta.forEach((meta, id) => {
            if (!selectedIds.has(id)) return;
            if ((meta.conferido || 'N') === 'S') {
              verificados += 1;
            } else {
              naoVerificados += 1;
            }
          });
          return { verificados, naoVerificados };
        };
        const updateConferidoVisibility = () => {
          const summary = summarizeConferido();
          const hasSelection = selectedIds.size > 0;
          if (!hasSelection) {
            if (bulkConferidoYes) bulkConferidoYes.style.display = 'none';
            if (bulkConferidoNo) bulkConferidoNo.style.display = 'none';
            pendingConferido = null;
            return;
          }
          if (summary.verificados > 0 && summary.naoVerificados === 0) {
            if (bulkConferidoYes) bulkConferidoYes.style.display = 'none';
            if (bulkConferidoNo) bulkConferidoNo.style.display = '';
            if (pendingConferido === 'S') pendingConferido = null;
            return;
          }
          if (summary.naoVerificados > 0 && summary.verificados === 0) {
            if (bulkConferidoYes) bulkConferidoYes.style.display = '';
            if (bulkConferidoNo) bulkConferidoNo.style.display = 'none';
            if (pendingConferido === 'N') pendingConferido = null;
            return;
          }
          if (bulkConferidoYes) bulkConferidoYes.style.display = '';
          if (bulkConferidoNo) bulkConferidoNo.style.display = '';
        };

        const updateConferidoToggle = () => {
          updateConferidoVisibility();
          const hasSelection = selectedIds.size > 0;
          const disable = !hasSelection;
          [bulkConferidoYes, bulkConferidoNo].forEach((btn) => {
            if (!btn) return;
            btn.disabled = disable;
            btn.classList.toggle('opacity-50', disable);
            btn.classList.toggle('cursor-not-allowed', disable);
            btn.classList.remove('ring-2', 'ring-offset-2', 'ring-green-600', 'ring-red-600');
          });
          if (pendingConferido === 'S' && bulkConferidoYes && bulkConferidoYes.style.display !== 'none') {
            bulkConferidoYes.classList.add('ring-2', 'ring-offset-2', 'ring-green-600');
            bulkConferidoYes.setAttribute('aria-pressed', 'true');
            if (bulkConferidoNo) bulkConferidoNo.setAttribute('aria-pressed', 'false');
          } else if (pendingConferido === 'N' && bulkConferidoNo && bulkConferidoNo.style.display !== 'none') {
            bulkConferidoNo.classList.add('ring-2', 'ring-offset-2', 'ring-red-600');
            bulkConferidoNo.setAttribute('aria-pressed', 'true');
            if (bulkConferidoYes) bulkConferidoYes.setAttribute('aria-pressed', 'false');
          } else {
            if (bulkConferidoYes) bulkConferidoYes.setAttribute('aria-pressed', 'false');
            if (bulkConferidoNo) bulkConferidoNo.setAttribute('aria-pressed', 'false');
          }
        };

        const inferConferidoAction = () => {
          const summary = summarizeConferido();
          if (summary.verificados > 0 && summary.naoVerificados === 0) return 'N';
          if (summary.naoVerificados > 0 && summary.verificados === 0) return 'S';
          return 'S';
        };
                        const toggleId = (id, checked) => {
          if (!id) return;
          const key = String(id);
          if (checked) {
            selectedIds.add(key);
            const meta = readRowMeta(key);
            if (meta) selectedMeta.set(key, meta);
          } else {
            selectedIds.delete(key);
            selectedMeta.delete(key);
          }
          console.log('[PATRI] toggle', key, checked, 'total', selectedIds.size);
          saveSelection();
          updateBulkBar();
        };

                        const bindCheckboxes = () => {
          if (!tableContent) return;
          const boxes = tableContent.querySelectorAll('.patrimonio-checkbox');
          boxes.forEach((box) => {
            const id = box.value;
            box.checked = selectedIds.has(String(id));
          });
          syncSelectedMeta();
          saveSelection();
          updateBulkBar();
        };

        const refreshHeaderCheckbox = () => {};

                        const clearSelection = () => {
          selectedIds.clear();
          selectedMeta.clear();
          pendingConferido = null;
          const boxes = tableContent?.querySelectorAll('.patrimonio-checkbox') || [];
          boxes.forEach(b => { b.checked = false; });
          refreshHeaderCheckbox();
          saveSelection();
          updateBulkBar();
        };

                const runBulkUpdate = (situacao) => {
          const payload = {
            ids: Array.from(selectedIds),
          };
          if (situacao) {
            payload.situacao = situacao;
          }
          if (pendingConferido) {
            payload.conferido = pendingConferido;
          }
          fetch(bulkEndpoint, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify(payload),
          }).then(async (resp) => {
            const data = await resp.json().catch(() => ({}));
            if (!resp.ok) {
              throw new Error(data.error || `Falha ao aplicar: ${resp.status}`);
            }
            clearSelection();
            if (window.location.href) {
              ajaxFetchParams(buildParamsFromForm());
            }
          }).catch((err) => {
            console.error(err);
            alert(err.message || 'Falha ao aplicar alteracoes.');
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
          pendingSituacao = situacao || null;
          const hasSituacao = !!pendingSituacao;
          const hasConferido = pendingConferido !== null;
          if (!bulkConfirmModal || !bulkConfirmList || !bulkConfirmNew) {
            runBulkUpdate(pendingSituacao);
            return;
          }
          if (bulkConfirmNew) {
            bulkConfirmNew.textContent = hasSituacao ? pendingSituacao : '';
            bulkConfirmNew.className = hasSituacao ? 'text-base font-bold rounded px-3 py-1 ' + getSituacaoClasses(pendingSituacao) : 'text-base font-bold';
          }
          const createArrowIcon = () => {
            const wrapper = document.createElement('div');
            wrapper.innerHTML = `
              <svg class="w-5 h-5 text-black dark:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
              </svg>
            `;
            return wrapper;
          };
          document.getElementById('bulk-confirm-count-header').textContent = selectedIds.size;
          bulkConfirmList.innerHTML = '';
          document.getElementById('bulk-confirm-count').textContent = selectedIds.size;
          selectedIds.forEach((id) => {
            const meta = selectedMeta.get(id) || readRowMeta(id) || {};
            const prevSituacao = meta.situacao || '-';
            const prevConferido = (meta.conferido || 'N') === 'S' ? 'Verificado' : 'Nao verificado';
            const patr = meta.patrimonio || id;

            const item = document.createElement('div');
            item.className = 'bg-gray-50 dark:bg-gray-800/60 border border-black/20 dark:border-black rounded-lg p-3 transition-all shadow-[0_20px_35px_-25px_rgba(0,0,0,0.6)] hover:shadow-[0_25px_45px_-25px_rgba(0,0,0,0.9)] hover:border-indigo-400 dark:hover:border-indigo-500';

            const numDiv = document.createElement('div');
            numDiv.className = 'flex items-center gap-2 mb-2';
            const numBadge = document.createElement('span');
            numBadge.className = 'px-2.5 py-1 rounded-full bg-orange-100 dark:bg-orange-900/50 text-orange-700 dark:text-orange-200 font-bold text-xs border border-orange-200 dark:border-orange-700/60';
            numBadge.textContent = 'N ' + patr;
            numDiv.appendChild(numBadge);
            item.appendChild(numDiv);

            if (hasSituacao) {
              const transDiv = document.createElement('div');
              transDiv.className = 'flex items-center gap-2 justify-between';

              const badgeFrom = document.createElement('span');
              badgeFrom.className = 'flex-1 px-2.5 py-1.5 rounded-md text-xs font-semibold text-center border ' + getSituacaoClasses(prevSituacao);
              badgeFrom.textContent = prevSituacao === '-' ? '-' : prevSituacao;

              const arrowContainer = document.createElement('div');
              arrowContainer.className = 'flex-shrink-0 flex items-center justify-center px-1';
              const arrow = createArrowIcon();
              arrowContainer.appendChild(arrow);

              const badgeTo = document.createElement('span');
              badgeTo.className = 'flex-1 px-2.5 py-1.5 rounded-md text-xs font-bold text-center border ' + getSituacaoClasses(pendingSituacao);
              badgeTo.textContent = pendingSituacao;

              transDiv.appendChild(badgeFrom);
              transDiv.appendChild(arrowContainer);
              transDiv.appendChild(badgeTo);
              item.appendChild(transDiv);
            }

            if (hasConferido) {
              const conferidoDiv = document.createElement('div');
              conferidoDiv.className = hasSituacao ? 'flex items-center gap-2 justify-between mt-2' : 'flex items-center gap-2 justify-between';

              const badgeFrom = document.createElement('span');
              badgeFrom.className = 'flex-1 px-2.5 py-1.5 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-semibold text-xs text-center border border-gray-200 dark:border-gray-600';
              badgeFrom.textContent = prevConferido;

              const arrow = createArrowIcon();

              const badgeTo = document.createElement('span');
              badgeTo.className = pendingConferido === 'S'
                ? 'flex-1 px-2.5 py-1.5 rounded-md bg-green-600 text-white font-bold text-xs text-center border border-green-700'
                : 'flex-1 px-2.5 py-1.5 rounded-md bg-red-600 text-white font-bold text-xs text-center border border-red-700';
              badgeTo.textContent = pendingConferido === 'S' ? 'Verificado' : 'Nao verificado';

              conferidoDiv.appendChild(badgeFrom);
              conferidoDiv.appendChild(arrow);
              conferidoDiv.appendChild(badgeTo);
              item.appendChild(conferidoDiv);
            }

            bulkConfirmList.appendChild(item);
          });
          bulkConfirmModal.classList.remove('hidden');
          bulkConfirmModal.classList.add('flex');
        };

                const applyBulkSituacao = () => {
          const situacao = bulkSelect?.value || '';
          if (selectedIds.size === 0) {
            alert('Selecione ao menos um patrimonio.');
            return;
          }
          if (!situacao && !pendingConferido) {
            alert('Escolha a situacao ou verificacao para aplicar.');
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
            tagsContainer.replaceWith(freshTags);
            // Reinicializar Alpine para as tags
            if (window.Alpine && typeof Alpine.initTree === 'function') {
              Alpine.initTree(freshTags);
            }
          }
          
          logTags('after-swap');
            bindCheckboxes();
        };

                const buildParamsFromForm = () => {
          if (!form) return new URLSearchParams();
          return new URLSearchParams(new FormData(form));
        };

        const buildParamsWithOverrides = (overrides) => {
          const params = buildParamsFromForm();
          Object.entries(overrides || {}).forEach(([key, value]) => {
            if (value === null || value === '' || typeof value === 'undefined') {
              params.delete(key);
            } else {
              params.set(key, value);
            }
          });
          return params;
        };

        const parseHrefParams = (href) => {
          try {
            const url = new URL(href, window.location.origin);
            return url.searchParams;
          } catch (_) {
            const idx = href.indexOf('?');
            return new URLSearchParams(idx >= 0 ? href.slice(idx + 1) : '');
          }
        };

        const scrollToTopSmooth = () => {
          try {
            window.scrollTo({ top: 0, behavior: 'smooth' });
          } catch (_) {
            window.scrollTo(0, 0);
          }
        };

        const stripQueryFromUrl = () => {
          if (!window.history || !window.location.search) return;
          const url = window.location.pathname + window.location.hash;
          window.history.replaceState(null, '', url);
        };

        const ajaxFetchParams = (params) => {
          if (!tableContainer) return;
          scrollToTopSmooth();
          if (loading) loading.classList.remove('hidden');
          if (loadingTop) loadingTop.classList.remove('hidden');
          tableContainer.classList.add('opacity-60');
          const body = params instanceof URLSearchParams ? params.toString() : '';
          fetch(filterEndpoint, {
            method: 'POST',
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
              'X-CSRF-TOKEN': csrf,
              'Accept': 'text/html',
              'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            },
            body,
          })
            .then(resp => resp.text())
            .then(swapTable)
            .then(stripQueryFromUrl)
            .catch((err) => {
              console.error('[PATRI] ajax fetch error', err);
              alert('Falha ao atualizar a listagem. Tente novamente.');
            })
            .finally(() => {
              tableContainer.classList.remove('opacity-60');
              if (loading) loading.classList.add('hidden');
              if (loadingTop) loadingTop.classList.add('hidden');
            });
        };

        // ?o. Expor ajaxFetch globalmente para que possa ser chamada ap??s salvar modal
        window.ajaxFetchPatrimonios = () => ajaxFetchParams(buildParamsFromForm());

        if (form) {
          form.addEventListener('submit', (e) => {
            e.preventDefault();
            ajaxFetchParams(buildParamsFromForm());
            window.scrollTo({ top: 0, behavior: 'smooth' });
          });
        }

        cleanLinks.forEach(link => {
          link.addEventListener('click', (e) => {
            e.preventDefault();
            if (form) form.reset();
            ajaxFetchParams(buildParamsFromForm());
            window.scrollTo({ top: 0, behavior: 'smooth' });
          });
        });

        document.addEventListener('click', (e) => {
          const tagLink = e.target.closest('[data-ajax-tag-remove]');
          if (tagLink) {
            e.preventDefault();
            const href = tagLink.getAttribute('href');
            if (href) {
              ajaxFetchParams(parseHrefParams(href));
              window.scrollTo({ top: 0, behavior: 'smooth' });
            }
            return;
          }

          const sortLink = e.target.closest('[data-ajax-sort]');
          if (sortLink) {
            e.preventDefault();
            const href = sortLink.getAttribute('href');
            if (href) {
              const hrefParams = parseHrefParams(href);
              ajaxFetchParams(buildParamsWithOverrides({
                sort: hrefParams.get('sort'),
                direction: hrefParams.get('direction'),
              }));
              window.scrollTo({ top: 0, behavior: 'smooth' });
            }
            return;
          }

          const pagLink = e.target.closest('[data-ajax-page], #patrimonios-pagination a');
          if (pagLink) {
            e.preventDefault();
            const href = pagLink.getAttribute('href');
            if (href) {
              const hrefParams = parseHrefParams(href);
              ajaxFetchParams(buildParamsWithOverrides({
                page: hrefParams.get('page'),
                per_page: hrefParams.get('per_page'),
              }));
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

        stripQueryFromUrl();
        loadSelection();
        bindCheckboxes();
        bulkApply?.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          applyBulkSituacao();
        });
        bulkConferidoYes?.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          if (selectedIds.size === 0) return;
          pendingConferido = pendingConferido === 'S' ? null : 'S';
          updateConferidoToggle();
        });
        bulkConferidoNo?.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          if (selectedIds.size === 0) return;
          pendingConferido = pendingConferido === 'N' ? null : 'N';
          updateConferidoToggle();
        });
        
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
          
          if (title) title.textContent = '⚠️ Confirmar exclusão em massa';
          if (desc) desc.textContent = 'Esta ação é irreversível! Os patrimônios serão deletados permanentemente.';
          
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
              ajaxFetchParams(buildParamsFromForm());
            }
          }).catch((err) => {
            console.error(err);
            alert(err.message || 'Falha ao deletar patrimônios.');
          });
        };
        
        bulkDelete?.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          applyBulkDelete();
        });
        
        // ✅ Modificar o listener do botão YES para detectar operação
        const originalConfirmYes = bulkConfirmYes?.onclick;
                bulkConfirmYes?.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          if (pendingDeleteIds.size > 0) {
            closeConfirmModal();
            runBulkDelete();
          } else {
            const situacao = pendingSituacao;
            closeConfirmModal();
            runBulkUpdate(situacao);
          }
        });
        
        bulkClear?.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          clearSelection();
        });
        bulkConfirmCancel?.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          closeConfirmModal();
        });
        bulkConfirmClose?.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          closeConfirmModal();
        });
        logTags('initial');
      });
      function patrimoniosIndex() {
        return {
          relatorioModalOpen: false,
          termoModalOpen: false,
          bulkImportModalOpen: false,
          bulkModalMode: 'import',
          bulkImportLoading: false,
          bulkImportOutput: '',
          bulkImportError: '',
          bulkImportDragging: false,
          bulkImportFileName: '',
          bulkListDragging: false,
          bulkListFileName: '',
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
            this.$watch('bulkImportModalOpen', (open) => {
              document.documentElement.classList.toggle('overflow-hidden', open);
              document.body.classList.toggle('overflow-hidden', open);
              if (!open) {
                this.bulkImportLoading = false;
                this.bulkImportError = '';
                this.bulkImportFileName = '';
                this.bulkImportDragging = false;
                this.bulkListDragging = false;
                this.bulkListFileName = '';
              }
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
          openBulkModal(mode = 'import') {
            this.bulkModalMode = mode;
            this.bulkImportModalOpen = true;
            this.bulkImportOutput = '';
            this.bulkImportError = '';
            this.bulkImportLoading = false;
            this.bulkImportFileName = '';
            this.bulkImportDragging = false;
            this.bulkListDragging = false;
            this.bulkListFileName = '';
          },
          openBulkImportModal() {
            this.openBulkModal('import');
          },
          closeBulkImportModal() {
            this.bulkImportModalOpen = false;
            this.bulkModalMode = 'import';
            this.bulkImportOutput = '';
            this.bulkImportError = '';
            this.bulkImportDragging = false;
            this.bulkImportFileName = '';
            this.bulkListDragging = false;
            this.bulkListFileName = '';
          },
          handleBulkListDrop(event) {
            this.bulkListDragging = false;
            const fileInput = document.getElementById('bulk-list-file');
            const files = event?.dataTransfer?.files;
            if (fileInput && files && files.length > 0) {
              fileInput.files = files;
              this.onBulkListSelected({ target: fileInput });
            }
          },
          onBulkListSelected(event) {
            const files = event?.target?.files || event?.dataTransfer?.files;
            if (!files || files.length === 0) return;
            this.bulkListFileName = files[0]?.name || '';
          },
          async submitBulkExportList(event) {
            const form = event?.target;
            if (!form) return;
            const formData = new FormData(form);
            try {
              const resp = await fetch("{{ route('patrimonios.bulk-update.export') }}", {
                method: 'POST',
                body: formData,
                headers: {
                  'X-CSRF-TOKEN': this.csrf(),
                },
              });
              if (!resp.ok) {
                const data = await resp.json().catch(() => ({}));
                alert(data.message || 'Falha ao gerar planilha.');
                return;
              }
              const blob = await resp.blob();
              const url = URL.createObjectURL(blob);
              const a = document.createElement('a');
              a.href = url;
              const filename = resp.headers.get('content-disposition')?.split('filename=')[1]?.replace(/\"/g, '') || 'planilha.csv';
              a.download = filename;
              document.body.appendChild(a);
              a.click();
              a.remove();
              URL.revokeObjectURL(url);
            } catch (err) {
              console.error('[PATRI] Export list error', err);
              alert('Falha ao gerar planilha.');
            }
          },
          handleBulkDrop(event) {
            this.bulkImportDragging = false;
            const fileInput = document.getElementById('bulk-import-file');
            const files = event?.dataTransfer?.files;
            if (fileInput && files && files.length > 0) {
              fileInput.files = files;
              this.onBulkFileSelected({ target: fileInput });
            }
          },
          onBulkFileSelected(event) {
            const files = event?.target?.files || event?.dataTransfer?.files;
            if (!files || files.length === 0) return;
            this.bulkImportFileName = files[0]?.name || '';
          },
          async submitBulkImport(event) {
            const form = event?.target;
            if (!form) return;
            this.bulkImportLoading = true;
            this.bulkImportError = '';
            this.bulkImportOutput = '';

            const formData = new FormData(form);

            try {
              const resp = await fetch("{{ route('patrimonios.bulk-update.import') }}", {
                method: 'POST',
                body: formData,
                headers: {
                  'X-CSRF-TOKEN': this.csrf(),
                  'Accept': 'application/json',
                },
              });
              const data = await resp.json().catch(() => ({}));
              if (!resp.ok) {
                if (resp.status === 422 && data.errors) {
                  this.bulkImportError = Object.values(data.errors).flat().join(' ');
                } else {
                  this.bulkImportError = data.message || 'Falha ao processar planilha.';
                }
                return;
              }
              this.bulkImportOutput = data.output || 'Processamento concluido.';
              if (window.ajaxFetchPatrimonios) {
                window.ajaxFetchPatrimonios();
              }
            } catch (err) {
              console.error('[PATRI] Bulk import error', err);
              this.bulkImportError = 'Falha ao enviar planilha.';
            } finally {
              this.bulkImportLoading = false;
            }
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
              if (window.ajaxFetchPatrimonios) {
                window.ajaxFetchPatrimonios();
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






































