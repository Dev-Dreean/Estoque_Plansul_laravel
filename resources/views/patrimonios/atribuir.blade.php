<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-900 dark:text-gray-200 leading-tight">
            {{ __('Atribuir Código de Termo') }}
        </h2>
    </x-slot>



    <div class="py-12">
        <div class="w-full px-2 sm:px-4 lg:px-8 2xl:px-12" x-data="atribuirPage()" x-init="init()">
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

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <!-- Filtros no mesmo padrão da página de Patrimônios -->
                    <div x-data="{ open: true }" class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg mb-6">
                        <div @click="open = !open" class="flex justify-between items-center cursor-pointer">
                            <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100">Filtros de Busca</h3>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                        <div x-show="open" x-transition class="mt-4" style="display: none;">
                            <div class="grid gap-3 sm:gap-4" style="grid-template-columns: repeat(auto-fit,minmax(150px,1fr));">
                                <div>
                                    <input type="text" id="filtro_numero" name="filtro_numero" value="{{ request('filtro_numero') }}" placeholder="Nº Patr." class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                                </div>
                                <div>
                                    <input type="text" id="filtro_descricao" name="filtro_descricao" value="{{ request('filtro_descricao') }}" placeholder="Descrição" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                                </div>
                                <div>
                                    <input type="text" id="filtro_modelo" name="filtro_modelo" value="{{ request('filtro_modelo') }}" placeholder="Modelo" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                                </div>
                                <div>
                                    <input type="number" id="filtro_projeto" name="filtro_projeto" value="{{ request('filtro_projeto') }}" placeholder="Cód. Projeto" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                                </div>
                                <div>
                                    <select id="status" name="status" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md">
                                        <option value="">Todos</option>
                                        <option value="disponivel" {{ request('status') == 'disponivel' ? 'selected' : '' }}>Disponíveis</option>
                                        <option value="indisponivel" {{ request('status') == 'indisponivel' ? 'selected' : '' }}>Indisponíveis</option>
                                    </select>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center justify-between mt-4 gap-4">
                                <div class="flex items-center gap-3">
                                    <x-primary-button type="button" class="h-10 px-4" @click="aplicarFiltros()">{{ __('Filtrar') }}</x-primary-button>
                                    <a href="{{ route('patrimonios.atribuir') }}" class="text-sm text-gray-700 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md">Limpar</a>
                                </div>
                                <label class="flex items-center gap-2 ml-auto shrink-0">
                                    <span class="text-sm text-gray-900 dark:text-gray-300">Itens por página</span>
                                    <select id="per_page" name="per_page" class="h-10 px-10 pr-8 w-20 border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm">
                                        @foreach([15,30,50,100] as $opt)
                                        <option value="{{ $opt }}" @selected(request('per_page',15)==$opt)>{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('patrimonios.atribuir.processar') }}" id="formAtribuir">
                        @csrf

                        <!-- filtros foram movidos para o card acima, mantendo a lógica de aplicarFiltros() -->

                        <!-- Barra de Ação / Alternância -->
                        <div class="sticky top-0 z-40 px-4 py-3 mb-4 bg-gray-800 dark:bg-gray-900 border-b border-gray-700 shadow">
                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <!-- Esquerda: Código + Atribuir + contador -->
                                <div class="flex flex-col gap-2">
                                    @if(!request('status') || request('status')=='disponivel')
                                    <div class="flex items-center gap-2">
                                        <label for="codigo_termo_header" class="text-sm font-medium text-white dark:text-gray-300">Código</label>
                                        <input type="number" id="codigo_termo_header" x-model="codigoTermo" placeholder="Termo" class="w-32 border-gray-600 dark:border-gray-700 bg-gray-700 dark:bg-gray-900 text-gray-200 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                                        <button type="button" @click="confirmarAtribuicao()" :disabled="selectedPatrimonios.length===0" :class="selectedPatrimonios.length===0 ? 'opacity-40 cursor-not-allowed' : 'hover:bg-green-700'" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md text-xs font-semibold tracking-widest focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                            Atribuir
                                        </button>
                                        <button type="button" @click="processarGerarCodigo()" :disabled="selectedPatrimonios.length===0" :class="selectedPatrimonios.length===0 ? 'opacity-40 cursor-not-allowed' : 'hover:bg-indigo-700'" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md text-xs font-semibold tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            Gerar Código
                                        </button>
                                    </div>
                                    <span id="contador-selecionados-header" class="text-xs text-gray-100 dark:text-gray-400">Selecionados: 0</span>
                                    @else
                                    <span class="text-xs text-gray-100 dark:text-gray-400">Lista de patrimônios atribuídos</span>
                                    @endif
                                </div>
                                <!-- Direita: Toggle -->
                                <div class="flex items-center gap-2 flex-wrap justify-end">
                                    <div class="flex items-center gap-1">
                                        <a href="{{ route('patrimonios.atribuir', array_merge(request()->except('page'), ['status'=>'disponivel'])) }}" class="text-[11px] px-3 py-2 rounded-md font-semibold border transition {{ request('status','disponivel')=='disponivel' ? 'bg-green-600 text-white border-green-600' : 'bg-gray-700 dark:bg-gray-800 text-gray-200 dark:text-gray-300 border-gray-600 dark:border-gray-700 hover:bg-green-50 dark:hover:bg-gray-700' }}">Disponíveis</a>
                                        <a href="{{ route('patrimonios.atribuir', array_merge(request()->except('page'), ['status'=>'indisponivel'])) }}" class="text-[11px] px-3 py-2 rounded-md font-semibold border transition {{ request('status')=='indisponivel' ? 'bg-red-600 text-white border-red-600' : 'bg-gray-700 dark:bg-gray-800 text-gray-200 dark:text-gray-300 border-gray-600 dark:border-gray-700 hover:bg-red-50 dark:hover:bg-gray-700' }}">Atribuídos</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabela de Patrimônios -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                Patrimônios
                                @if(request('status') == 'disponivel')
                                Disponíveis
                                @elseif(request('status') == 'indisponivel')
                                Indisponíveis (Atribuídos)
                                @endif
                            </h3>

                            <!-- Container com scroll dinâmico -->
                            <div x-ref="tableWrapper"
                                class="overflow-y-auto overflow-x-auto w-full border border-gray-200 dark:border-gray-700 rounded-lg"
                                x-bind:style="tableHeight ? 'max-height:'+tableHeight+'px' : ''">
                                <table class="min-w-full w-full text-base text-left rtl:text-right text-gray-700 dark:text-gray-400">
                                    <thead class="text-xs text-gray-900 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-200 sticky top-0 z-10">
                                        <tr>
                                            <th class="px-4 py-3 bg-gray-50 dark:bg-gray-700">
                                                @if(!request('status') || request('status')=='disponivel')
                                                <input type="checkbox" id="selectAll" @change="toggleAll($event)"
                                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                                                @endif
                                            </th>
                                            <th class="px-4 py-3 bg-gray-50 dark:bg-gray-700">Nº Pat.</th>
                                            <th class="px-4 py-3 bg-gray-50 dark:bg-gray-700">Descrição</th>
                                            <th class="px-4 py-3 bg-gray-50 dark:bg-gray-700">Modelo</th>
                                            <th class="px-4 py-3 bg-gray-50 dark:bg-gray-700">Status</th>
                                            <th class="px-4 py-3 bg-gray-50 dark:bg-gray-700">Código Termo</th>
                                            <th class="px-4 py-3 bg-gray-50 dark:bg-gray-700">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($patrimonios as $patrimonio)
                                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 text-sm">
                                            <td class="px-4 py-2">
                                                @if(empty($patrimonio->NMPLANTA) && (!request('status') || request('status')=='disponivel'))
                                                <input class="patrimonio-checkbox h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                                                    type="checkbox" name="patrimonios[]" value="{{ $patrimonio->NUSEQPATR }}" @change="updateCounter()">
                                                @endif
                                            </td>
                                            <td class="px-4 py-2 font-medium text-gray-900 dark:text-white">
                                                {{ $patrimonio->NUPATRIMONIO }}
                                            </td>
                                            <td class="px-4 py-2">
                                                {{ Str::limit($patrimonio->DEPATRIMONIO, 50) }}
                                            </td>
                                            <td class="px-4 py-2">
                                                {{ $patrimonio->MODELO ?? 'N/A' }}
                                            </td>
                                            <td class="px-4 py-2">
                                                @if(empty($patrimonio->NMPLANTA))
                                                <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                                    Disponível
                                                </span>
                                                @else
                                                <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20">
                                                    Atribuído
                                                </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2">
                                                <span class="font-mono text-sm">{{ $patrimonio->NMPLANTA ?? '—' }}</span>
                                            </td>
                                            <td class="px-4 py-2">
                                                @if(!empty($patrimonio->NMPLANTA))
                                                <button type="button"
                                                    @click="desatribuirItem = { id: '{{ $patrimonio->NUSEQPATR }}', numero: '{{ $patrimonio->NUPATRIMONIO }}', codigo: '{{ $patrimonio->NMPLANTA }}' }; showDesatribuirModal = true"
                                                    class="inline-flex items-center px-2 py-1 bg-red-100 hover:bg-red-200 text-red-700 text-xs rounded-md border border-red-300 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    Desatribuir
                                                </button>
                                                @else
                                                <span class="text-xs text-gray-400">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="{{ request('status') == 'indisponivel' ? '7' : '5' }}" class="px-6 py-8 text-center">
                                                <div class="text-gray-700 dark:text-gray-400">
                                                    <svg class="mx-auto h-12 w-12 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                        <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                                                    </svg>
                                                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Nenhum patrimônio encontrado</h3>
                                                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-400">
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

                            <!-- Informações de Paginação (Fora do scroll) -->
                            @if($patrimonios->hasPages())
                            <div class="flex items-center justify-between border-t border-gray-200 bg-white dark:bg-gray-800 px-4 py-3 sm:px-6 mt-4 rounded-b-lg">
                                <div class="flex flex-1 justify-between sm:hidden">
                                    @if($patrimonios->onFirstPage())
                                    <span class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-500">Anterior</span>
                                    @else
                                    <a href="{{ $patrimonios->previousPageUrl() }}" class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Anterior</a>
                                    @endif

                                    @if($patrimonios->hasMorePages())
                                    <a href="{{ $patrimonios->nextPageUrl() }}" class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Próximo</a>
                                    @else
                                    <span class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-500">Próximo</span>
                                    @endif
                                </div>
                                <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm text-gray-900 dark:text-gray-300">
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

            <!-- Modal de Confirmação de Atribuição -->
            <div x-show="showConfirmModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div x-show="showConfirmModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div x-show="showConfirmModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 19.5c-.77.833.192 2.5 1.732 2.5z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                        Confirmar Atribuição
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">
                                            Você tem certeza que deseja atribuir <span x-text="selectedPatrimonios.length" class="font-semibold"></span> patrimônio(s) ao código de termo <span x-text="codigoTermo" class="font-mono font-semibold"></span>?
                                        </p>
                                        <p class="text-xs text-gray-400 mt-1">
                                            Esta ação não pode ser desfeita facilmente.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="button" @click="processarAtribuicao()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Sim, Atribuir
                            </button>
                            <button type="button" @click="showConfirmModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal de Confirmação de Desatribuição -->
            <div x-show="showDesatribuirModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div x-show="showDesatribuirModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div x-show="showDesatribuirModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 19.5c-.77.833.192 2.5 1.732 2.5z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                        Confirmar Desatribuição
                                    </h3>
                                    <div class="mt-2" x-show="desatribuirItem">
                                        <p class="text-sm text-gray-500">
                                            Você tem certeza que deseja desatribuir o patrimônio <span x-text="desatribuirItem?.numero" class="font-semibold"></span> do código de termo <span x-text="desatribuirItem?.codigo" class="font-mono font-semibold"></span>?
                                        </p>
                                        <p class="text-xs text-gray-400 mt-1">
                                            O patrimônio ficará disponível para nova atribuição.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="button" @click="processarDesatribuicao()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Sim, Desatribuir
                            </button>
                            <button type="button" @click="showDesatribuirModal = false; desatribuirItem = null" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function atribuirPage() {
            return {
                showFilters: false,
                showConfirmModal: false,
                showDesatribuirModal: false,
                selectedPatrimonios: [],
                codigoTermo: '',
                desatribuirItem: null,
                tableHeight: null,
                init() {
                    this.updateCounter();
                    this.calcTableHeight();
                    window.addEventListener('resize', () => this.calcTableHeight());
                },
                calcTableHeight() {
                    this.$nextTick(() => {
                        const wrapper = this.$refs.tableWrapper;
                        if (!wrapper) return;
                        const rect = wrapper.getBoundingClientRect();
                        // Espaço inferior reservado (paginação + padding da página)
                        const bottomPadding = 160; // ajuste fino se necessário
                        const available = window.innerHeight - rect.top - bottomPadding;
                        this.tableHeight = available > 300 ? available : 300; // mínimo seguro
                    });
                },
                syncCodigoTermo(value) {
                    this.codigoTermo = value;
                },
                aplicarFiltros() {
                    const params = new URLSearchParams();
                    const numero = document.getElementById('filtro_numero')?.value;
                    const descricao = document.getElementById('filtro_descricao')?.value;
                    const modelo = document.getElementById('filtro_modelo')?.value;
                    const projeto = document.getElementById('filtro_projeto')?.value;
                    const status = document.getElementById('status')?.value;
                    const perPage = document.getElementById('per_page')?.value;
                    if (numero) params.append('filtro_numero', numero);
                    if (descricao) params.append('filtro_descricao', descricao);
                    if (modelo) params.append('filtro_modelo', modelo);
                    if (projeto) params.append('filtro_projeto', projeto);
                    if (status) params.append('status', status);
                    if (perPage) params.append('per_page', perPage);
                    window.location.href = '{{ route("patrimonios.atribuir") }}?' + params.toString();
                },
                toggleAll(event) {
                    const source = event.target;
                    const checkboxes = document.querySelectorAll('.patrimonio-checkbox');
                    checkboxes.forEach(cb => cb.checked = source.checked);
                    this.updateCounter();
                },
                updateCounter() {
                    const checkboxes = document.querySelectorAll('.patrimonio-checkbox:checked');
                    const counter = document.getElementById('contador-selecionados-header');
                    const count = checkboxes.length;
                    if (counter) {
                        if (count === 0) {
                            counter.textContent = '0 patrimônios selecionados';
                            counter.className = 'text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap';
                        } else {
                            counter.textContent = `${count} patrimônio${count > 1 ? 's' : ''} selecionado${count > 1 ? 's' : ''}`;
                            counter.className = 'text-sm text-green-600 dark:text-green-400 font-medium whitespace-nowrap';
                        }
                    }
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
                        const foco = document.getElementById('codigo_termo_header');
                        if (foco) foco.focus();
                        alert('Informe o código do termo antes de confirmar.');
                        return;
                    }
                    this.selectedPatrimonios = Array.from(checkboxes).map(cb => cb.value);
                    this.showConfirmModal = true;
                },
                processarAtribuicao() {
                    if (!this.codigoTermo) {
                        alert('Código do termo obrigatório.');
                        return;
                    }
                    const form = document.getElementById('formAtribuir');
                    // Limpa codigo termo anterior
                    Array.from(form.querySelectorAll('input[name="codigo_termo"]')).forEach(e => e.remove());
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'codigo_termo';
                    hiddenInput.value = this.codigoTermo;
                    form.appendChild(hiddenInput);
                    form.submit();
                },
                processarGerarCodigo() {
                    const checkboxes = document.querySelectorAll('.patrimonio-checkbox:checked');
                    if (checkboxes.length === 0) {
                        alert('Selecione pelo menos um patrimônio para gerar o código.');
                        return;
                    }
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = "{{ route('termos.atribuir.store') }}";
                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = '{{ csrf_token() }}';
                    form.appendChild(csrfToken);
                    Array.from(checkboxes).forEach(cb => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'patrimonio_ids[]';
                        input.value = cb.value;
                        form.appendChild(input);
                    });
                    document.body.appendChild(form);
                    form.submit();
                },
                processarDesatribuicao() {
                    if (!this.desatribuirItem) return;
                    let ids = [];
                    if (this.desatribuirItem.id.includes(',')) {
                        // Lote
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
                        patrimonioInput.name = 'patrimonios[]';
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
                }
            }
        }
    </script>
</x-app-layout>