<x-app-layout>
    <div class="py-12"
        x-data="searchFilter('{{ route('projetos.index') }}')"> {{-- 1. Inicia o Alpine.js --}}
        <div class="w-full sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-4">
                {{-- 2. Campo de busca --}}
                <div class="w-1/3">
                    <input x-model="searchTerm" @input.debounce.500ms="search" type="text" placeholder="Pesquisar por nome ou código..." class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
                </div>

                <a href="{{ route('projetos.create') }}" class="bg-plansul-blue hover:bg-opacity-90 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                    <x-heroicon-o-plus-circle class="w-5 h-5 mr-2" />
                    <span>Incluir Local</span>
                </a>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    {{-- 3. Área da tabela que será atualizada dinamicamente --}}
                    <div id="table-container" x-html="tableHtml">
                        {{-- O conteúdo inicial da tabela é carregado aqui --}}
                        @include('projetos._table_partial', ['locais' => $locais])
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    {{-- 4. Lógica Javascript do Alpine.js --}}
    <script>
        function searchFilter(baseUrl) {
            return {
                searchTerm: '',
                tableHtml: document.getElementById('table-container').innerHTML,
                search() {
                    // Se o campo de busca estiver vazio, não faz nada (ou pode recarregar o original)
                    if (this.searchTerm.length < 1 && this.searchTerm.length !== 0) {
                        return;
                    }

                    // Monta a URL com o parâmetro de busca
                    const url = `${baseUrl}?search=${encodeURIComponent(this.searchTerm)}`;

                    fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        })
                        .then(response => response.text())
                        .then(html => {
                            this.tableHtml = html;
                        })
                        .catch(error => console.error('Erro ao buscar os dados:', error));
                }
            }
        }
    </script>
    @endpush
</x-app-layout>