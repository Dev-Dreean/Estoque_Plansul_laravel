{{-- Modal: Gerar Planilha do Termo (controlado por 'termoModalOpen') --}}
    <div x-show="termoModalOpen" x-transition class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center" style="display: none;">
      <div @click.outside="termoModalOpen = false" class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md p-6">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Gerar Planilha por Termo</h3>
        <form action="{{ route('termos.exportar.excel') }}" method="POST">
          @csrf
          <div>
            <label for="cod_termo" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Cód Termo:</label>
            <input type="number" id="cod_termo" name="cod_termo" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm" required>
          </div>
          <div class="mt-6 flex justify-end space-x-4">
            <button type="button" @click="termoModalOpen = false" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500">Sair</button>
            <button type="submit" class="px-4 py-2 bg-plansul-blue text-white rounded-md hover:bg-opacity-90">Gerar Planilha Excel</button>
          </div>
        </form>
      </div>
    </div>

    {{-- Modal: Atribuir Código de Termo (controlado por 'atribuirTermoModalOpen') --}}
    <template x-teleport="body">
      <div x-show="atribuirTermoModalOpen" x-cloak>
        <!-- Overlay separado para garantir escurecimento imediato -->
        <div x-show="atribuirTermoModalOpen" x-transition.opacity class="fixed inset-0 bg-black/80 z-[2147483600]" aria-hidden="true" @click="atribuirTermoModalOpen=false; history.replaceState(null,'',window.location.pathname+window.location.search)"></div>
        <!-- Wrapper de posicionamento do modal -->
        <div class="fixed inset-0 z-[2147483647] flex items-center justify-center pointer-events-none">
          <div x-show="atribuirTermoModalOpen" x-transition.opacity.scale @click.outside="atribuirTermoModalOpen = false" class="relative pointer-events-auto bg-white dark:bg-gray-800 rounded-lg shadow-2xl w-full max-w-4xl p-6 max-h-[calc(100vh-80px)] flex flex-col border border-gray-300 dark:border-gray-700 overflow-hidden focus:outline-none" role="dialog" aria-modal="true" aria-label="Atribuir Código de Termo" tabindex="-1">
            <button type="button" @click="atribuirTermoModalOpen=false; history.replaceState(null,'',window.location.pathname+window.location.search)" class="absolute top-2 right-2 text-gray-500 hover:text-gray-800 dark:hover:text-gray-200" aria-label="Fechar">✕</button>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Atribuir Código de Termo</h3>
            <form action="{{ route('termos.atribuir.store') }}" method="POST" class="flex-1 flex flex-col min-h-0">
              @csrf

              {{-- CABEÇALHO COM O BOTÃO GERAR --}}
              <div class="flex flex-wrap gap-3 justify-between items-center mb-4 px-1">
                <p class="text-gray-600 dark:text-gray-400 flex-1 min-w-[220px]">Selecione os patrimônios para agrupar em um novo Termo.</p>
                <div class="flex items-center gap-2">
                  <button type="button" @click="desatribuirTermoModalOpen = true; atribuirTermoModalOpen=false;" class="bg-orange-600 hover:bg-orange-700 text-white font-semibold py-2 px-4 rounded inline-flex items-center" title="Desatribuir códigos de termo">
                    <x-heroicon-o-minus-circle class="w-5 h-5 mr-2" />
                    <span>Desatribuir</span>
                  </button>
                  <button type="submit" class="bg-plansul-blue hover:bg-opacity-90 text-white font-semibold py-2 px-4 rounded inline-flex items-center">
                    <x-heroicon-o-plus-circle class="w-5 h-5 mr-2" />
                    <span>Gerar e Atribuir</span>
                  </button>
                </div>
              </div>

              {{-- TABELA SIMPLIFICADA MESMO ESTILO DO MODAL GERAR PLANILHA --}}
              <div class="overflow-y-auto border dark:border-gray-700 rounded mb-4" style="max-height:400px;" id="atribuir-modal-content">
                <table class="w-full table-fixed text-[11px] text-left text-gray-500 dark:text-gray-400">
                  <thead class="text-[10px] text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr class="divide-x divide-gray-200 dark:divide-gray-700">
                      <th class="p-4 w-4"></th>
                      <th class="px-2 py-3">Nº Pat.</th>
                      <th class="px-2 py-3">Descrição</th>
                      <th class="px-2 py-3">Cód. Termo</th>
                      <th class="px-2 py-3">Modelo</th>
                    </tr>
                  </thead>
                  <tbody id="atribuir-table-body">
                    @forelse ($patrimoniosDisponiveis as $patrimonio)
                    <tr class="border-b dark:border-gray-700">
                      <td class="p-4"><input type="checkbox" name="patrimonio_ids[]" value="{{ $patrimonio->NUSEQPATR }}" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"></td>
                      <td class="px-2 py-2">{{ $patrimonio->NUPATRIMONIO ?? 'N/A' }}</td>
                      <td class="px-2 py-2">{{ $patrimonio->DEPATRIMONIO }}</td>
                      <td class="px-2 py-2 font-bold">{{ $patrimonio->NMPLANTA }}</td>
                      <td class="px-2 py-2">{{ $patrimonio->MODELO }}</td>
                    </tr>
                    @empty
                    <tr>
                      <td colspan="5" class="py-4 text-center">Nenhum patrimônio disponível.</td>
                    </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
              <div class="mt-4" id="atribuir-pagination">
                {{ $patrimoniosDisponiveis->appends(request()->except('page', 'disponiveisPage'))->links('pagination::tailwind') }}
              </div>
              <div class="mt-6 flex justify-end space-x-4 border-t border-gray-200 dark:border-gray-700 pt-6">
                <button type="button" @click="atribuirTermoModalOpen=false; history.replaceState(null,'',window.location.pathname+window.location.search)" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500">Fechar</button>
                <button type="submit" class="px-4 py-2 bg-plansul-blue text-white rounded-md hover:bg-opacity-90 flex items-center">
                  <x-heroicon-o-plus-circle class="w-5 h-5 mr-2" />
                  <span>Gerar e Atribuir Termo</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </template>

    {{-- Modal: Desatribuir Código de Termo --}}
    <template x-teleport="body">
      <div x-show="desatribuirTermoModalOpen" x-cloak>
        <div x-show="desatribuirTermoModalOpen" x-transition.opacity class="fixed inset-0 bg-black/70 z-[2147483600]" aria-hidden="true" @click="desatribuirTermoModalOpen=false"></div>
        <div class="fixed inset-0 z-[2147483647] flex items-center justify-center pointer-events-none">
          <div x-show="desatribuirTermoModalOpen" x-transition.opacity.scale @click.outside="desatribuirTermoModalOpen = false" class="relative pointer-events-auto bg-white dark:bg-gray-800 rounded-lg shadow-2xl w-full max-w-3xl p-6 max-h-[calc(100vh-80px)] flex flex-col border border-gray-300 dark:border-gray-700 overflow-hidden" role="dialog" aria-modal="true" aria-label="Desatribuir Código de Termo">
            <button type="button" @click="desatribuirTermoModalOpen=false" class="absolute top-2 right-2 text-gray-500 hover:text-gray-800 dark:hover:text-gray-200" aria-label="Fechar">✕</button>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Desatribuir Código de Termo</h3>
            <form action="{{ route('termos.desatribuir') }}" method="POST" class="flex-1 flex flex-col min-h-0">
              @csrf
              <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Selecione os patrimônios que terão o código de termo removido. Apenas itens com código atribuído são listados.</p>
              <div class="overflow-y-auto border dark:border-gray-700 rounded mb-4" style="max-height:400px;">
                <table class="w-full table-fixed text-sm text-left text-gray-500 dark:text-gray-400">
                  <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr class="divide-x divide-gray-200 dark:divide-gray-700">
                      <th class="p-3 w-4"></th>
                      <th class="px-2 py-3">Nº Pat.</th>
                      <th class="px-2 py-3">Descrição</th>
                      <th class="px-2 py-3">Cód. Termo</th>
                      <th class="px-2 py-3">Modelo</th>
                    </tr>
                  </thead>
                  <tbody>
                    @php $patrimoniosComTermo = $patrimonios->filter(fn($p)=> !blank($p->NMPLANTA)); @endphp
                    @forelse ($patrimoniosComTermo as $pat)
                    <tr class="border-b dark:border-gray-700">
                      <td class="p-3"><input type="checkbox" name="patrimonio_ids[]" value="{{ $pat->NUSEQPATR }}" class="w-4 h-4 text-orange-600 bg-gray-100 border-gray-300 rounded focus:ring-orange-500"></td>
                      <td class="px-2 py-2">{{ $pat->NUPATRIMONIO ?? 'N/A' }}</td>
                      <td class="px-2 py-2">{{ $pat->DEPATRIMONIO }}</td>
                      <td class="px-2 py-2 font-bold">{{ $pat->NMPLANTA }}</td>
                      <td class="px-2 py-2">{{ $pat->MODELO }}</td>
                    </tr>
                    @empty
                    <tr>
                      <td colspan="5" class="py-4 text-center">Nenhum patrimônio com código de termo nesta página.</td>
                    </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
              <div class="mt-6 flex justify-end space-x-4 border-t border-gray-200 dark:border-gray-700 pt-6">
                <button type="button" @click="desatribuirTermoModalOpen=false" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500">Fechar</button>
                <button type="submit" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-md font-semibold flex items-center">
                  <x-heroicon-o-minus-circle class="w-5 h-5 mr-2" />
                  <span>Remover Códigos Selecionados</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </template>
