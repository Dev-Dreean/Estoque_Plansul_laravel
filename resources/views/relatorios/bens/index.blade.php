{{-- resources/views/relatorios/bens/index.blade.php --}}
<x-app-layout>
    {{-- Abas de navegação do patrimônio --}}
    <x-patrimonio-nav-tabs />

    <div
        x-data="{
                    cadBemOpen: {{ (old('modal') === 'cadBem' || request('open') === 'cadBem') ? 'true' : 'false' }}
                }"
        @open-cad-bem.window="cadBemOpen = true"
        class="py-12">
        <div class="w-full sm:px-6 lg:px-8">

            {{-- FLASHES --}}
            @if(session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Sucesso!</strong>
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
            @endif
            @if(session('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Erro!</strong>
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
            @endif
            @if($errors->any())
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Erro de Validação!</strong>
                <span class="block sm:inline">{{ $errors->first() }}</span>
            </div>
            @endif

            <div class="section">
                <div class="section-body">

                    {{-- AÇÕES: o botão principal será exibido junto ao cabeçalho de filtros (aba abaixo) --}}

                    {{-- FILTRO (sempre fechado por padrão) --}}
                    <div x-data="{ open: false }"
                        class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg mb-6"
                        x-id="['filtro-bens']"
                        :aria-expanded="open.toString()"
                        :aria-controls="$id('filtro-bens')">

                        <div class="flex justify-between items-center">
                            <h3 class="font-semibold text-lg">Filtros de Busca</h3>
                            <button type="button" @click="open = !open" aria-expanded="open" aria-controls="$id('filtro-bens')" class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                                <span class="sr-only">Expandir filtros</span>
                            </button>
                        </div>

                        <div x-show="open" x-transition class="mt-4" style="display: none;" :id="$id('filtro-bens')">
                            <form method="GET" action="{{ route('relatorios.bens.index') }}" @submit="open=false">
                                <div class="grid gap-3 sm:gap-4" style="grid-template-columns: repeat(auto-fit,minmax(180px,1fr));">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Descrição</label>
                                        <input type="text" name="descricao" placeholder="Parte da descrição"
                                            value="{{ request('descricao') }}"
                                            class="h-10 px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Tipo (nome)</label>
                                        <input type="text" name="tipo" placeholder="Ex.: APARADOR"
                                            value="{{ request('tipo') }}"
                                            class="h-10 px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Cód. Tipo</label>
                                        <select name="codigo_tipo"
                                            class="h-10 px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md">
                                            <option value="">Todos</option>
                                            @foreach($tipos as $t)
                                            <option value="{{ $t->NUSEQTIPOPATR }}" @selected(request('codigo_tipo')==$t->NUSEQTIPOPATR)>
                                                {{ $t->NUSEQTIPOPATR }} — {{ $t->DETIPOPATR }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Itens por página</label>
                                        <select name="per_page"
                                            class="h-10 px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md">
                                            @foreach([10,30,50,100,200] as $opt)
                                            <option value="{{ $opt }}" @selected(request('per_page', 30)==$opt)>{{ $opt }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between mt-4 gap-4">
                                    <div class="flex items-center gap-3">
                                        <x-primary-button class="h-10 px-4">
                                            {{ __('Filtrar') }}
                                        </x-primary-button>

                                        <a href="{{ route('relatorios.bens.index') }}"
                                            class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md">
                                            Limpar
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- AÇÕES: botão Cadastrar Bem abaixo dos filtros --}}
                    <div class="flex items-start mb-4 mt-2">
                        <button type="button" @click="cadBemOpen = true" class="bg-plansul-blue hover:bg-opacity-90 text-white font-semibold py-2 px-4 rounded inline-flex items-center">
                            <x-heroicon-o-plus-circle class="w-5 h-5 mr-2" />
                            <span>Cadastrar Bem</span>
                        </button>
                    </div>

                    {{-- TABELA (única) --}}
                    <div class="table-wrap">
                        <table class="table-var">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3">Descrição</th>
                                    <th class="px-4 py-3">Tipo</th>
                                    <th class="px-4 py-3">Cód. Tipo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bens as $bem)
                                <tr class="tr-hover text-sm">
                                    <td class="td font-medium">
                                        {{ $bem->DEOBJETO }}
                                    </td>
                                    <td class="td">
                                        {{ $bem->tipo->DETIPOPATR ?? '—' }}
                                    </td>
                                    <td class="td">
                                        {{ $bem->NUSEQTIPOPATR }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="td text-center">Nenhum registro encontrado.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $bens->withQueryString()->links() }}
                    </div>

                </div>
            </div>
        </div>



        {{-- MODAL: Cadastrar Bem (código tipo, tipo e descrição) --}}
        <div x-show="cadBemOpen"
            x-transition
            class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center"
            style="display:none;">
            <div @click.outside="cadBemOpen = false"
                class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Cadastrar Bem</h3>
                    <button type="button" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                        @click="cadBemOpen = false" aria-label="Fechar">✕</button>
                </div>

                <form method="POST" action="{{ route('relatorios.bens.store') }}">
                    @csrf
                    <input type="hidden" name="modal" value="cadBem" />
                    <div class="space-y-4">
                        <div class="grid gap-3" style="grid-template-columns: 140px 1fr;">
                            <div>
                                <label for="NUSEQTIPOPATR" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Cód. Tipo
                                </label>
                                <input id="NUSEQTIPOPATR" name="NUSEQTIPOPATR" type="number" required
                                    class="mt-1 block w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md h-10 px-3"
                                    placeholder="Ex.: 1">
                                @error('NUSEQTIPOPATR')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="DETIPOPATR" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Tipo
                                </label>
                                <input id="DETIPOPATR" name="DETIPOPATR" type="text"
                                    class="mt-1 block w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md h-10 px-3"
                                    placeholder="Ex.: APARADOR DE GRAMA">
                                <p class="mt-1 text-xs text-gray-500">Informe o nome do tipo se o código não existir.</p>
                                @error('DETIPOPATR')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="DEOBJETO" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Descrição do Bem
                            </label>
                            <input id="DEOBJETO" name="DEOBJETO" type="text" required
                                class="mt-1 block w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md h-10 px-3"
                                placeholder="Ex.: APARADOR DE GRAMA TRAMONTINA 127V AP1500T">
                            @error('DEOBJETO')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button type="button"
                            class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500"
                            @click="cadBemOpen = false">Cancelar</button>
                        <button type="submit"
                            class="px-4 py-2 bg-plansul-blue text-white rounded-md hover:bg-opacity-90">
                            Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>