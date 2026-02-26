<x-app-layout>
    @php
        $formTela = session('formTela', []);
    @endphp

    <script>
        function telasPage(baseUrl) {
            return {
                tags: [],
                inputValue: '',
                showCreateForm: false,

                init() {
                    const params = new URLSearchParams(window.location.search);
                    const multiSearch = params.getAll('search[]');
                    if (multiSearch.length > 0) {
                        this.tags = multiSearch.map((t) => t.trim()).filter(Boolean);
                    } else {
                        const singleSearch = params.get('search');
                        if (singleSearch) {
                            this.tags = singleSearch.split(',').map((t) => t.trim()).filter(Boolean);
                        }
                    }
                },

                addTag() {
                    const value = this.inputValue.trim();
                    if (value && !this.tags.includes(value)) {
                        this.tags.push(value);
                        this.inputValue = '';
                        this.search();
                    }
                },

                removeTag(index) {
                    this.tags.splice(index, 1);
                    this.search();
                },

                removeLastTag() {
                    if (!this.inputValue && this.tags.length > 0) {
                        this.tags.pop();
                        this.search();
                    }
                },

                search() {
                    const params = new URLSearchParams();
                    const currentUrlParams = new URLSearchParams(window.location.search);
                    ['sort', 'direction'].forEach((key) => {
                        const value = currentUrlParams.get(key);
                        if (value) params.set(key, value);
                    });

                    const terms = [...this.tags];
                    if (this.inputValue.trim()) {
                        terms.push(this.inputValue.trim());
                    }
                    if (terms.length) {
                        terms.forEach((term) => params.append('search[]', term));
                    }
                    params.set('page', '1');

                    const nextUrl = `${baseUrl}?${params.toString()}`;
                    window.history.replaceState({}, '', nextUrl);

                    fetch(`${nextUrl}&api=1`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    })
                        .then((response) => response.json())
                        .then((data) => {
                            const tbody = document.querySelector('tbody');
                            if (tbody) {
                                tbody.innerHTML = data.html || '<tr><td colspan="5" class="px-4 py-3 text-center text-sm">Nenhuma tela encontrada.</td></tr>';
                            }
                        })
                        .catch((error) => console.error('Erro ao buscar os dados:', error));
                }
            };
        }
    </script>

    <div class="py-12" x-data="telasPage('{{ route('cadastro-tela.index') }}')" x-init="init()">
        <div class="w-full sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex justify-between items-center mb-4">
                        <div class="w-1/2">
                            <div class="flex flex-col gap-1">
                                <template x-if="tags.length > 0">
                                    <div class="flex flex-wrap items-center gap-2 mb-3">
                                        <template x-for="(tag, idx) in tags" :key="tag">
                                            <span class="inline-flex items-center px-2 py-1 bg-indigo-100 text-indigo-700 rounded-full text-xs font-semibold mr-1">
                                                <span x-text="tag"></span>
                                                <button type="button" @click="removeTag(idx)" class="ml-1 text-indigo-500 hover:text-red-500 focus:outline-none">&times;</button>
                                            </span>
                                        </template>
                                    </div>
                                </template>
                                <input
                                    x-model="inputValue"
                                    @keydown.enter.prevent="addTag()"
                                    @keydown.tab.prevent="addTag()"
                                    @keydown.backspace="removeLastTag()"
                                    @input.debounce.500ms="search"
                                    type="text"
                                    placeholder="Buscar..."
                                    :style="'width:' + Math.max(120, inputValue.length * 10) + 'px'"
                                    class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm transition-all duration-200">
                                <template x-if="inputValue.length > 0">
                                    <div class="w-full mt-1">
                                        <div class="text-[11px] text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 rounded px-2 py-1 shadow-sm text-left">
                                            <template x-if="tags.length === 0">
                                                <span>Pressione <kbd class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded border border-gray-300 dark:border-gray-600 text-[10px]">Enter</kbd> para criar uma <span class="font-semibold text-indigo-600">tag</span> e refinar a busca.</span>
                                            </template>
                                            <template x-if="tags.length > 0">
                                                <span>Para apagar uma tag, apague todo o texto do input ou clique no <span class="text-red-500 font-bold">&times;</span> da tag.</span>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <button type="button" @click="showCreateForm = !showCreateForm" class="bg-plansul-blue hover:bg-opacity-90 text-white font-bold py-2 px-4 rounded">
                            <span x-text="showCreateForm ? 'Fechar Cadastro' : 'Cadastrar Nova Tela'"></span>
                        </button>
                    </div>

                    @if(session('success'))
                    <div class="mb-4 p-3 rounded-md bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                        {{ session('success') }}
                    </div>
                    @endif
                    @if(session('warning'))
                    <div class="mb-4 p-3 rounded-md bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300">
                        {{ session('warning') }}
                    </div>
                    @endif
                    @if(session('error'))
                    <div class="mb-4 p-3 rounded-md bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300">
                        {{ session('error') }}
                    </div>
                    @endif
                    @if(session('info'))
                    <div class="mb-4 p-3 rounded-md bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                        {{ session('info') }}
                    </div>
                    @endif

                    <div x-show="showCreateForm" x-transition class="mb-4 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <form action="{{ route('cadastro-tela.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-12 gap-3">
                            @csrf
                            <div class="md:col-span-2">
                                <label for="NUSEQTELA" class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Código</label>
                                <input
                                    type="number"
                                    name="NUSEQTELA"
                                    id="NUSEQTELA"
                                    value="{{ old('NUSEQTELA', $formTela['NUSEQTELA'] ?? '') }}"
                                    class="h-9 px-2 w-full text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md"
                                    placeholder="Código da Tela" />
                                @error('NUSEQTELA')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="md:col-span-5">
                                <label for="DETELA" class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Nome da Tela</label>
                                <input
                                    type="text"
                                    name="DETELA"
                                    id="DETELA"
                                    value="{{ old('DETELA', $formTela['DETELA'] ?? '') }}"
                                    class="h-9 px-2 w-full text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md"
                                    maxlength="100"
                                    placeholder="Nome da Tela">
                                @error('DETELA')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="md:col-span-3">
                                <label for="NMSISTEMA" class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Sistema</label>
                                <input
                                    type="text"
                                    name="NMSISTEMA"
                                    id="NMSISTEMA"
                                    value="{{ old('NMSISTEMA', $formTela['NMSISTEMA'] ?? '') }}"
                                    class="h-9 px-2 w-full text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md"
                                    maxlength="60"
                                    placeholder="Sistema">
                                @error('NMSISTEMA')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="md:col-span-2 flex items-end">
                                <button type="submit" class="h-9 w-full bg-plansul-blue hover:bg-opacity-90 text-white font-semibold rounded-md">
                                    Salvar
                                </button>
                            </div>
                        </form>
                    </div>

                    @include('telas._table_partial', [
                        'telasGrid' => $telasGrid,
                        'sort' => $sort ?? request('sort', 'DETELA'),
                        'direction' => $direction ?? request('direction', 'asc')
                    ])
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
