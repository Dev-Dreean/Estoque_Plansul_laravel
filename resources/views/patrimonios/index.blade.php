<x-app-layout>
    <div x-data="{
    relatorioModalOpen: false,
    termoModalOpen: false,
    atribuirTermoModalOpen: false,
    desatribuirTermoModalOpen: false,
    resultadosModalOpen: false,
    isLoading: false,
    reportData: [],
    reportFilters: {},
    tipoRelatorio: 'numero', // <-- A variável agora vive aqui, no lugar certo.
    viewMode: 'simple',
    init() {
        if (window.location.hash === '#atribuir-termo') {
            this.atribuirTermoModalOpen = true;
        }
        this.$watch('atribuirTermoModalOpen', v => {
            document.documentElement.classList.toggle('overflow-hidden', v);
            document.body.classList.toggle('overflow-hidden', v);
        });
    },

    gerarRelatorio: function(event) {
        this.isLoading = true;
        const formData = new FormData(event.target);

        fetch('{{ route('relatorios.patrimonios.gerar') }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                }
            })
            .then(response => {
                if (!response.ok) { throw new Error('Erro na rede ou servidor'); }
                return response.json();
            })
            .then(data => {
                this.reportData = data.resultados;
                this.reportFilters = data.filtros;
                this.relatorioModalOpen = false;
                this.$nextTick(() => {
                    this.resultadosModalOpen = true;
                });
            })
            .catch(error => {
                console.error('Erro ao gerar relatório:', error);
                alert('Ocorreu um erro ao gerar o relatório. Verifique o console para mais detalhes.');
            })
            .finally(() => {
                this.isLoading = false;
            });
    },

    exportarRelatorio: function(format) {
        const form = document.createElement('form');
        form.method = 'POST';

        switch (format) {
            case 'excel':
                form.action = '{{ route('relatorios.patrimonios.exportar.excel') }}';
                break;
            case 'csv':
                form.action = '{{ route('relatorios.patrimonios.exportar.csv') }}';
                break;
            case 'ods':
                form.action = '{{ route('relatorios.patrimonios.exportar.ods') }}';
                break;
            case 'pdf':
                form.action = '{{ route('relatorios.patrimonios.exportar.pdf') }}';
                break;
        }

        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = '{{ csrf_token() }}';
        form.appendChild(csrf);

        for (const key in this.reportFilters) {
            if (this.reportFilters[key] !== null) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = this.reportFilters[key];
                form.appendChild(input);
            }
        }

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }
}">
        <div class="py-12">
            <div class="w-full sm:px-6 lg:px-8">
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
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">

                        {{-- Formulário de Filtro --}}
                        <div x-data="{ open: false }" class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg mb-6">
                            <div @click="open = !open" class="flex justify-between items-center cursor-pointer">
                                <h3 class="font-semibold text-lg">Filtros de Busca</h3>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform transition-transform"
                                    :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                            <div x-show="open" x-transition class="mt-4" style="display: none;">
                                <form method="GET" action="{{ route('patrimonios.index') }}" @submit="open=false">
                                    <div class="grid gap-3 sm:gap-4" style="grid-template-columns: repeat(auto-fit,minmax(150px,1fr));">
                                        <div>
                                            <input type="text" name="nupatrimonio" placeholder="Nº Patr." value="{{ request('nupatrimonio') }}" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                                        </div>
                                        <div>
                                            <input type="text" name="cdprojeto" placeholder="Cód. Projeto" value="{{ request('cdprojeto') }}" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                                        </div>
                                        <div class="col-span-full md:col-span-2">
                                            <input type="text" name="descricao" placeholder="Descrição" value="{{ request('descricao') }}" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                                        </div>
                                        <div>
                                            <input type="text" name="situacao" placeholder="Situação" value="{{ request('situacao') }}" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                                        </div>
                                        <div>
                                            <input type="text" name="modelo" placeholder="Modelo" value="{{ request('modelo') }}" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                                        </div>
                                        <div>
                                            <input type="number" name="nmplanta" placeholder="Cód. Termo" value="{{ request('nmplanta') }}" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                                        </div>
                                        @if (Auth::user()->PERFIL === 'ADM')
                                        <div>
                                            <select name="cadastrado_por" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md">
                                                <option value="">Usuário</option>
                                                <option value="SISTEMA" @selected(request('cadastrado_por')==='SISTEMA' )>Sistema</option>
                                                @foreach ($cadastradores as $cadastrador)
                                                <option value="{{ $cadastrador->CDMATRFUNCIONARIO }}" @selected(request('cadastrado_por')==$cadastrador->CDMATRFUNCIONARIO)>
                                                    {{ Str::limit($cadastrador->NOMEUSER,18) }}
                                                </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @endif
                                    </div>

                                    <div class="flex flex-wrap items-center justify-between mt-4 gap-4">
                                        <div class="flex items-center gap-3">
                                            <x-primary-button class="h-10 px-4">
                                                {{ __('Filtrar') }}
                                            </x-primary-button>

                                            <a href="{{ route('patrimonios.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md">
                                                Limpar
                                            </a>
                                        </div>

                                        <label class="flex items-center gap-2 ml-auto shrink-0">
                                            <span class="text-sm text-gray-700 dark:text-gray-300">Itens por página</span>
                                            <select name="per_page" class="h-10 px-10 pr-8 w-20 border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm">
                                                @foreach([10,30,50,100,200] as $opt)
                                                <option value="{{ $opt }}" @selected(request('per_page', 30)==$opt)>{{ $opt }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="flex justify-end items-center mb-4 space-x-4">
                            <button @click="desatribuirTermoModalOpen = true" class="bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                                <x-heroicon-o-minus-circle class="w-5 h-5 mr-2" />
                                <span>Desatribuir Cód. Termo</span>
                            </button>
                            <button @click="atribuirTermoModalOpen = true" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                                <x-heroicon-o-document-plus class="w-5 h-5 mr-2" />
                                <span>Atribuir Cód. Termo</span>
                            </button>

                            <button @click="termoModalOpen = true" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                                <x-heroicon-o-printer class="w-5 h-5 mr-2" />
                                <span>Gerar Planilha do Termo</span>
                            </button>

                            <button @click="relatorioModalOpen = true" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                                <x-heroicon-o-chart-bar class="w-5 h-5 mr-2" />
                                <span>Gerar Relatório</span>
                            </button>

                            <a href="{{ route('patrimonios.create') }}" class="bg-plansul-blue hover:bg-opacity-90 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                                <x-heroicon-o-plus-circle class="w-5 h-5 mr-2" />
                                <span>Cadastrar Novo</span>
                            </a>
                        </div>

                        @php
                        // Determina se cada coluna (entre as que podem ficar vazias com frequência) está vazia nesta página de resultados
                        $colVazia = [
                        'NUMOF' => $patrimonios->every(fn($p)=> blank($p->NUMOF)),
                        'CODOBJETO' => $patrimonios->every(fn($p)=> blank($p->CODOBJETO)),
                        'NMPLANTA' => $patrimonios->every(fn($p)=> blank($p->NMPLANTA)),
                        'NUSERIE' => $patrimonios->every(fn($p)=> blank($p->NUSERIE)),
                        'CDPROJETO' => $patrimonios->every(fn($p)=> blank($p->CDPROJETO)),
                        'MODELO' => $patrimonios->every(fn($p)=> blank($p->MODELO)),
                        'MARCA' => $patrimonios->every(fn($p)=> blank($p->MARCA)),
                        'COR' => $patrimonios->every(fn($p)=> blank($p->COR)),
                        'DTAQUISICAO' => $patrimonios->every(fn($p)=> blank($p->DTAQUISICAO)),
                        'DTOPERACAO' => $patrimonios->every(fn($p)=> blank($p->DTOPERACAO)),
                        'USUARIO' => $patrimonios->every(fn($p)=> blank($p->usuario?->NOMEUSER)),
                        ];
                        $shrink = fn($key) => $colVazia[$key] ? 'w-px px-0 text-[0] overflow-hidden' : 'px-4';
                        @endphp
                        <div class="mb-4 flex items-center gap-2">
                            <span class="text-sm font-medium">Visualização:</span>
                            <button type="button" @click="viewMode='simple'" :class="viewMode==='simple' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 dark:text-gray-200'" class="px-3 py-1 rounded text-sm">Simples</button>
                            <button type="button" @click="viewMode='detailed'" :class="viewMode==='detailed' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 dark:text-gray-200'" class="px-3 py-1 rounded text-sm">Detalhada</button>
                        </div>

                        <!-- Tabela Simples -->
                        <template x-if="viewMode==='simple'">
                            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                                <table class="w-full text-base text-left rtl:text-right text-gray-500 dark:text-gray-400">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                            <th class="px-4 py-3">Nº Pat.</th>
                                            <th class="px-4 py-3">Cód. Objeto</th>
                                            <th class="px-4 py-3">Cód. Projeto</th>
                                            <th class="px-4 py-3">Modelo</th>
                                            <th class="px-4 py-3">Descrição</th>
                                            <th class="px-4 py-3">Situação</th>
                                            <th class="px-4 py-3">Dt. Aquisição</th>
                                            <th class="px-4 py-3">Dt. Cadastro</th>
                                            <th class="px-4 py-3">Cadastrado Por</th>
                                            @if(Auth::user()->PERFIL === 'ADM')
                                            <th class="px-4 py-3">Ações</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($patrimonios as $patrimonio)
                                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 text-sm cursor-pointer" @click="window.location.href='{{ route('patrimonios.edit', $patrimonio) }}'">
                                            <td class="px-4 py-2">{{ $patrimonio->NUPATRIMONIO ?? 'N/A' }}</td>
                                            <td class="px-4 py-2">{{ $patrimonio->CODOBJETO ?? '' }}</td>
                                            <td class="px-4 py-2">{{ $patrimonio->CDPROJETO ?? '' }}</td>
                                            <td class="px-4 py-2">{{ $patrimonio->MODELO ? Str::limit($patrimonio->MODELO,10,'...') : '' }}</td>
                                            <td class="px-4 py-2 font-medium text-gray-900 dark:text-white">{{ Str::limit($patrimonio->DEPATRIMONIO,10,'...') }}</td>
                                            <td class="px-4 py-2 whitespace-nowrap overflow-hidden text-ellipsis truncate">{{ $patrimonio->SITUACAO }}</td>
                                            <td class="px-4 py-2">{{ $patrimonio->DTAQUISICAO ? \Carbon\Carbon::parse($patrimonio->DTAQUISICAO)->format('d/m/Y') : '' }}</td>
                                            <td class="px-4 py-2">{{ $patrimonio->DTOPERACAO ? \Carbon\Carbon::parse($patrimonio->DTOPERACAO)->format('d/m/Y') : '' }}</td>
                                            <td class="px-4 py-2">{{ $patrimonio->usuario?->NOMEUSER ?? 'SISTEMA' }}</td>
                                            @if(Auth::user()->PERFIL === 'ADM')
                                            <td class="px-4 py-2" @click.stop>
                                                <div class="flex items-center gap-2">
                                                    @can('delete', $patrimonio)
                                                    <form method="POST" action="{{ route('patrimonios.destroy', $patrimonio) }}" onsubmit="return confirm('Tem certeza que deseja deletar este item?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 dark:text-red-500 hover:text-red-700" title="Excluir" aria-label="Excluir patrimônio">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                                                                <polyline points="3 6 5 6 21 6" />
                                                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                                                                <path d="M10 11v6" />
                                                                <path d="M14 11v6" />
                                                                <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                    @endcan
                                                </div>
                                            </td>
                                            @endif
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="{{ Auth::user()->PERFIL === 'ADM' ? 10 : 9 }}" class="px-6 py-4 text-center">Nenhum patrimônio encontrado.</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </template>

                        <!-- Tabela Detalhada -->
                        <template x-if="viewMode==='detailed'">
                            <div class="relative overflow-x-auto shadow-md sm:rounded-lg z-0">
                                <table class="w-full text-base text-left rtl:text-right text-gray-500 dark:text-gray-400">
                                    <thead
                                        class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        @php
                                        function sortable_link($column, $label)
                                        {
                                        $direction =
                                        request('sort') === $column && request('direction') === 'asc'
                                        ? 'desc'
                                        : 'asc';
                                        return '<a href="' .
                                                route(
                                                    'patrimonios.index',
                                                    array_merge(request()->query(), [
                                                        'sort' => $column,
                                                        'direction' => $direction,
                                                    ]),
                                                ) .
                                                '">' .
                                            $label .
                                            '</a>';
                                        }
                                        @endphp
                                        <tr>
                                            <th class="px-4 py-3">Nº Pat.</th>
                                            <th class="{{ $shrink('NUMOF') }} py-3">OF</th>
                                            <th class="{{ $shrink('CODOBJETO') }} py-3">Cód. Objeto</th>
                                            <th class="{{ $shrink('NMPLANTA') }} py-3">Cód. Termo</th>
                                            <th class="{{ $shrink('NUSERIE') }} py-3">Nº Série</th>
                                            <th class="{{ $shrink('CDPROJETO') }} py-3">Cód. Projeto</th>
                                            <th class="{{ $shrink('MODELO') }} py-3">Modelo</th>
                                            <th class="{{ $shrink('MARCA') }} py-3">Marca</th>
                                            <th class="{{ $shrink('COR') }} py-3">Cor</th>
                                            <th class="px-4 py-3">Descrição</th>
                                            <th class="px-4 py-3">Situação</th>
                                            <th class="{{ $shrink('DTAQUISICAO') }} py-3">Dt. Aquisição</th>
                                            <th class="{{ $shrink('DTOPERACAO') }} py-3">Dt. Cadastro</th>
                                            <th class="{{ $shrink('USUARIO') }} py-3">Cadastrado Por</th>
                                            @if(Auth::user()->PERFIL === 'ADM')
                                            <th class="px-4 py-3">Ações</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    @forelse ($patrimonios as $patrimonio)
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 text-sm cursor-pointer"
                                        @click="window.location.href='{{ route('patrimonios.edit', $patrimonio) }}'">

                                        {{-- A ordem agora está 100% correta para corresponder ao seu thead --}}
                                        <td class="px-4 py-2">{{ $patrimonio->NUPATRIMONIO ?? 'N/A' }}</td>
                                        <td class="{{ $shrink('NUMOF') }} py-2">{{ $patrimonio->NUMOF ?? ($colVazia['NUMOF'] ? '' : '') }}</td>
                                        <td class="{{ $shrink('CODOBJETO') }} py-2">{{ $patrimonio->CODOBJETO ?? ($colVazia['CODOBJETO'] ? '' : '') }}</td>
                                        <td class="{{ $shrink('NMPLANTA') }} py-2 font-bold">{{ $patrimonio->NMPLANTA ?? ($colVazia['NMPLANTA'] ? '' : '') }}</td>
                                        <td class="{{ $shrink('NUSERIE') }} py-2">{{ $patrimonio->NUSERIE ?? ($colVazia['NUSERIE'] ? '' : '') }}</td>
                                        <td class="{{ $shrink('CDPROJETO') }} py-2">{{ $patrimonio->CDPROJETO ?? ($colVazia['CDPROJETO'] ? '' : '') }}</td>
                                        <td class="{{ $shrink('MODELO') }} py-2">{{ $patrimonio->MODELO ? Str::limit($patrimonio->MODELO,10,'...') : ($colVazia['MODELO'] ? '' : '') }}</td>
                                        <td class="{{ $shrink('MARCA') }} py-2">{{ $patrimonio->MARCA ?? ($colVazia['MARCA'] ? '' : '') }}</td>
                                        <td class="{{ $shrink('COR') }} py-2">{{ $patrimonio->COR ?? ($colVazia['COR'] ? '' : '') }}</td>
                                        <td class="px-4 py-2 font-medium text-gray-900 dark:text-white">{{ Str::limit($patrimonio->DEPATRIMONIO,10,'...') }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap overflow-hidden text-ellipsis truncate">{{ $patrimonio->SITUACAO }}</td>
                                        <td class="{{ $shrink('DTAQUISICAO') }} py-2">{{ $patrimonio->DTAQUISICAO ? \Carbon\Carbon::parse($patrimonio->DTAQUISICAO)->format('d/m/Y') : ($colVazia['DTAQUISICAO'] ? '' : '') }}</td>
                                        <td class="{{ $shrink('DTOPERACAO') }} py-2">{{ $patrimonio->DTOPERACAO ? \Carbon\Carbon::parse($patrimonio->DTOPERACAO)->format('d/m/Y') : ($colVazia['DTOPERACAO'] ? '' : '') }}</td>
                                        <td class="{{ $shrink('USUARIO') }} py-2">{{ $patrimonio->usuario?->NOMEUSER ?? 'SISTEMA' }}</td>

                                        @if(Auth::user()->PERFIL === 'ADM')
                                        <td class="px-2 py-2">
                                            <div class="flex items-center gap-2">
                                                @can('delete', $patrimonio)
                                                <form method="POST" action="{{ route('patrimonios.destroy', $patrimonio) }}" onsubmit="return confirm('Tem certeza que deseja deletar este item?');" @click.stop>
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 dark:text-red-500 hover:text-red-700" title="Excluir" aria-label="Excluir patrimônio">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                                                            <polyline points="3 6 5 6 21 6" />
                                                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                                                            <path d="M10 11v6" />
                                                            <path d="M14 11v6" />
                                                            <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                                                        </svg>
                                                    </button>
                                                </form>
                                                @endcan
                                            </div>
                                        </td>
                                        @endif
                                    </tr>
                                    @empty
                                    <tr>
                                        {{-- Corrigindo o colspan para o número correto de colunas --}}
                                        <td colspan="{{ Auth::user()->PERFIL === 'ADM' ? 15 : 14 }}"
                                            class="px-6 py-4 text-center">Nenhum patrimônio encontrado para os
                                            filtros atuais.</td>
                                    </tr>
                                    @endforelse
                                </table>
                            </div>
                        </template>
                        <div class="mt-4">
                            {{ $patrimonios->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div x-show="relatorioModalOpen" x-transition
            class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center" style="display: none;">
            <div @click.outside="relatorioModalOpen = false"
                class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl p-6">
                <div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Relatório Geral de Bens
                    </h3>
                    <form @submit.prevent="gerarRelatorio">
                        @csrf
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-x-6 gap-y-4">
                                <label class="flex items-center space-x-2 cursor-pointer"><input type="radio"
                                        name="tipo_relatorio" value="numero" x-model="tipoRelatorio"
                                        class="form-radio text-indigo-600"><span
                                        class="text-gray-700 dark:text-gray-300">Por Número</span></label>
                                <label class="flex items-center space-x-2 cursor-pointer"><input type="radio"
                                        name="tipo_relatorio" value="descricao" x-model="tipoRelatorio"
                                        class="form-radio text-indigo-600"><span
                                        class="text-gray-700 dark:text-gray-300">Por Descrição</span></label>
                                <label class="flex items-center space-x-2 cursor-pointer"><input type="radio"
                                        name="tipo_relatorio" value="aquisicao" x-model="tipoRelatorio"
                                        class="form-radio text-indigo-600"><span
                                        class="text-gray-700 dark:text-gray-300">Por Período de
                                        Aquisição</span></label>
                                <label class="flex items-center space-x-2 cursor-pointer"><input type="radio"
                                        name="tipo_relatorio" value="cadastro" x-model="tipoRelatorio"
                                        class="form-radio text-indigo-600"><span
                                        class="text-gray-700 dark:text-gray-300">Por Período Cadastro</span></label>
                                <label class="flex items-center space-x-2 cursor-pointer"><input type="radio"
                                        name="tipo_relatorio" value="projeto" x-model="tipoRelatorio"
                                        class="form-radio text-indigo-600"><span
                                        class="text-gray-700 dark:text-gray-300">Por Projeto</span></label>
                                <label class="flex items-center space-x-2 cursor-pointer"><input type="radio"
                                        name="tipo_relatorio" value="oc" x-model="tipoRelatorio"
                                        class="form-radio text-indigo-600"><span
                                        class="text-gray-700 dark:text-gray-300">Por OC</span></label>
                            </div>
                            <!-- Campo de busca de descrição quando tipo descricao -->
                            <div x-show="tipoRelatorio === 'descricao'" class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg" style="display:none;">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="md:col-span-2">
                                        <label for="descricao_busca" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Descrição contém</label>
                                        <input type="text" id="descricao_busca" name="descricao_busca" placeholder="Parte da descrição" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm" />
                                    </div>
                                    <div>
                                        <label for="sort_direction" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Ordem</label>
                                        <select id="sort_direction" name="sort_direction" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                                            <option value="asc">Crescente (A-Z)</option>
                                            <option value="desc">Decrescente (Z-A)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <!-- Campo projeto múltiplos códigos -->
                            <div x-show="tipoRelatorio === 'projeto'" class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg" style="display:none;">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="md:col-span-2">
                                        <label for="projeto_busca" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Códigos de Projeto (separar por vírgula)</label>
                                        <input type="text" id="projeto_busca" name="projeto_busca" placeholder="Ex: 101, 202, 303" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm" />
                                    </div>
                                    <div>
                                        <label for="sort_direction_proj" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Ordem Nº Patr.</label>
                                        <select id="sort_direction_proj" name="sort_direction" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                                            <option value="asc">Crescente</option>
                                            <option value="desc">Decrescente</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <hr class="dark:border-gray-600 my-4">
                            <div x-show="tipoRelatorio === 'aquisicao'"
                                class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div><label for="data_inicio_aquisicao"
                                            class="block font-medium text-sm text-gray-700 dark:text-gray-300">Data
                                            Início</label><input type="date" id="data_inicio_aquisicao"
                                            name="data_inicio_aquisicao"
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                                    </div>
                                    <div><label for="data_fim_aquisicao"
                                            class="block font-medium text-sm text-gray-700 dark:text-gray-300">Data
                                            Fim</label><input type="date" id="data_fim_aquisicao"
                                            name="data_fim_aquisicao"
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                                    </div>
                                </div>
                            </div>
                            <div x-show="tipoRelatorio === 'cadastro'"
                                class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div><label for="data_inicio_cadastro"
                                            class="block font-medium text-sm text-gray-700 dark:text-gray-300">Data
                                            Início</label><input type="date" id="data_inicio_cadastro"
                                            name="data_inicio_cadastro"
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                                    </div>
                                    <div><label for="data_fim_cadastro"
                                            class="block font-medium text-sm text-gray-700 dark:text-gray-300">Data
                                            Fim</label><input type="date" id="data_fim_cadastro"
                                            name="data_fim_cadastro"
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                                    </div>
                                </div>
                            </div>
                            <div x-show="tipoRelatorio === 'numero'" class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <div>
                                    <label for="numero_busca" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Número do Patrimônio</label>
                                    <input type="number" id="numero_busca" name="numero_busca" placeholder="Digite o número" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                                </div>
                            </div>

                            <div x-show="tipoRelatorio === 'aquisicao'" class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg" style="display: none;">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="data_inicio_aquisicao" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Data Início</label>
                                        <input type="date" id="data_inicio_aquisicao" name="data_inicio_aquisicao" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                                    </div>
                                    <div>
                                        <label for="data_fim_aquisicao" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Data Fim</label>
                                        <input type="date" id="data_fim_aquisicao" name="data_fim_aquisicao" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                                    </div>
                                </div>
                            </div>
                            <div x-show="tipoRelatorio === 'oc'"
                                class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div><label for="oc_busca"
                                            class="block font-medium text-sm text-gray-700 dark:text-gray-300">OC</label><input
                                            type="text" id="oc_busca" name="oc_busca" placeholder="Digite a OC"
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                                    </div>
                                    <div><label
                                            class="block font-medium text-sm text-gray-700 dark:text-gray-300">Combo
                                            OC</label><select
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm"
                                            disabled>
                                            <option>A definir</option>
                                        </select></div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end space-x-4">
                            <button type="button" @click="relatorioModalOpen = false"
                                class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500"
                                :disabled="isLoading">Sair</button>

                            <button type="submit"
                                class="px-4 py-2 bg-plansul-blue text-white rounded-md hover:bg-opacity-90 flex items-center min-w-[100px] justify-center"
                                :disabled="isLoading">
                                <span x-show="!isLoading">Gerar</span>
                                <span x-show="isLoading">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    Gerando...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        {{-- INÍCIO DO NOVO MODAL DE RESULTADOS --}}
        <div x-show="resultadosModalOpen" x-transition
            class="fixed inset-0 z-50 bg-black bg-opacity-75 flex items-center justify-center" style="display: none;">
            <div @click.outside="resultadosModalOpen = false" class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-7xl p-6 max-h-[90vh] flex flex-col">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Resultado do Relatório</h3>
                <div class="flex-grow overflow-y-auto">
                    <table class="w-full text-base text-left text-gray-500 dark:text-gray-400">
                        <thead
                            class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 sticky top-0">
                            <tr>
                                <th scope="col" class="px-6 py-3">Nº Patrimônio</th>
                                <th scope="col" class="px-6 py-3">Descrição</th>
                                <th scope="col" class="px-6 py-3">Modelo</th>
                                <th scope="col" class="px-6 py-3">Situação</th>
                                <th scope="col" class="px-6 py-3">Local</th>
                                <th scope="col" class="px-6 py-3">Cadastrado por</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="reportData.length === 0">
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-lg">
                                        Nenhum patrimônio encontrado para os filtros aplicados.
                                    </td>
                                </tr>
                            </template>
                            <template x-for="patrimonio in reportData" :key="patrimonio.NUSEQPATR">
                                <tr
                                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td class="px-6 py-4" x-text="patrimonio.NUPATRIMONIO || 'N/A'"></td>
                                    <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white"
                                        x-text="patrimonio.DEPATRIMONIO"></td>
                                    <td class="px-6 py-4" x-text="patrimonio.MODELO"></td>
                                    <td class="px-6 py-4" x-text="patrimonio.SITUACAO"></td>
                                    <td class="px-6 py-4"
                                        x-text="patrimonio.local ? patrimonio.local.LOCAL : 'SISTEMA'"></td>
                                    <td class="px-6 py-4"
                                        x-text="patrimonio.usuario ? patrimonio.usuario.NOMEUSER : 'SISTEMA'">
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 pt-4 border-t dark:border-gray-700 flex justify-between items-center">
                    <span class="text-sm text-gray-500">
                        Total de registros encontrados: <strong x-text="reportData.length"></strong>
                    </span>

                    <div class="flex items-center space-x-2">
                        {{-- Botão PDF (vermelho) --}}
                        <button @click="exportarRelatorio('pdf')" title="Exportar para PDF"
                            class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-3 rounded inline-flex items-center">
                            <x-heroicon-o-document-text class="w-5 h-5 mr-2" />
                            <span>PDF</span>
                        </button>
                        {{-- Botão Excel (verde) --}}
                        <button @click="exportarRelatorio('excel')" title="Exportar para Excel"
                            class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-3 rounded inline-flex items-center">
                            <x-heroicon-o-table-cells class="w-5 h-5 mr-2" />
                            <span>Excel</span>
                        </button>
                        {{-- Botão CSV (verde-escuro) --}}
                        <button @click="exportarRelatorio('csv')" title="Exportar para CSV"
                            class="bg-green-800 hover:bg-green-900 text-white font-bold py-2 px-3 rounded inline-flex items-center">
                            <x-heroicon-o-document-chart-bar class="w-5 h-5 mr-2" />
                            <span>CSV</span>
                        </button>
                        {{-- Botão ODS (azul) --}}
                        <button @click="exportarRelatorio('ods')" title="Exportar para LibreOffice/OpenOffice"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-3 rounded inline-flex items-center">
                            <x-heroicon-o-document-duplicate class="w-5 h-5 mr-2" />
                            <span>ODS</span>
                        </button>
                        {{-- Botão Fechar --}}
                        <button @click="resultadosModalOpen = false"
                            class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-bold rounded">
                            Fechar
                        </button>
                    </div>
                </div>
            </div>
        </div>

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
                            <div class="flex justify-between items-center mb-4 px-1">
                                <p class="text-gray-600 dark:text-gray-400">Selecione os patrimônios para agrupar em um novo Termo.</p>
                                <button type="submit" class="bg-plansul-blue hover:bg-opacity-90 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                                    <x-heroicon-o-plus-circle class="w-5 h-5 mr-2" />
                                    <span>Gerar e Atribuir Termo</span>
                                </button>
                            </div>

                            {{-- TABELA SIMPLIFICADA MESMO ESTILO DO MODAL GERAR PLANILHA --}}
                            <div class="overflow-y-auto border dark:border-gray-700 rounded mb-4" style="max-height:400px;">
                                <table class="w-full text-base text-left text-gray-500 dark:text-gray-400">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                            <th class="p-4 w-4"></th>
                                            <th class="px-2 py-3">Nº Pat.</th>
                                            <th class="px-2 py-3">Descrição</th>
                                            <th class="px-2 py-3">Cód. Termo</th>
                                            <th class="px-2 py-3">Modelo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
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
                            <div class="mt-4">
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
                                <table class="w-full text-base text-left text-gray-500 dark:text-gray-400">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
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
    </div>
    </div>
</x-app-layout>