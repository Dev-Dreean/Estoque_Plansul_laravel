<x-app-layout>
    <div class="py-12" x-data="searchTagFilterUsuarios('{{ route('usuarios.index') }}')">
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
                                                <span>Para apagar uma tag, apague todo o texto do input ou clique no <span class="text-red-500 font-bold">×</span> da tag.</span>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <a href="{{ route('usuarios.create') }}" class="bg-plansul-blue hover:bg-opacity-90 text-white font-bold py-2 px-4 rounded">
                            Criar Novo Usuário
                        </a>
                    </div>
                    <div id="usuarios-table-container" x-html="tableHtml">
                        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                            <table class="w-full text-base text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th scope="col" class="px-6 py-3">Nome</th>
                                        <th scope="col" class="px-6 py-3">Login</th>
                                        <th scope="col" class="px-6 py-3">Matrícula</th>
                                        <th scope="col" class="px-6 py-3">UF</th>
                                        <th scope="col" class="px-6 py-3">Perfil</th>
                                        <th scope="col" class="px-6 py-3">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($usuarios as $usuario)
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $usuario->NOMEUSER }}</td>
                                        <td class="px-6 py-4">{{ $usuario->NMLOGIN }}</td>
                                        <td class="px-6 py-4">{{ $usuario->CDMATRFUNCIONARIO }}</td>
                                        <td class="px-6 py-4">{{ $usuario->UF ?? '—' }}</td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $usuario->PERFIL === 'ADM' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                {{ $usuario->PERFIL }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 flex items-center space-x-2">
                                            <a href="{{ route('usuarios.edit', $usuario) }}" class="font-medium text-plansul-orange hover:underline">Editar</a>
                                            @if(Auth::id() !== $usuario->NUSEQUSUARIO)
                                            <form method="POST" action="{{ route('usuarios.destroy', $usuario) }}" onsubmit="return confirm('Tem certeza?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="font-medium text-red-600 hover:underline">Deletar</button>
                                            </form>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center">Nenhum usuário encontrado.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            {{ $usuarios->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function searchTagFilterUsuarios(baseUrl) {
            return {
                tags: [],
                inputValue: '',
                tableHtml: document.getElementById('usuarios-table-container') ? document.getElementById('usuarios-table-container').innerHTML : '',
                addTag() {
                    const val = this.inputValue.trim();
                    if (val && !this.tags.includes(val)) {
                        this.tags.push(val);
                        this.inputValue = '';
                        this.search();
                    }
                },
                removeTag(idx) {
                    this.tags.splice(idx, 1);
                    this.search();
                },
                removeLastTag() {
                    if (!this.inputValue && this.tags.length) {
                        this.tags.pop();
                        this.search();
                    }
                },
                search() {
                    let params = new URLSearchParams();
                    let allTerms = [...this.tags];
                    if (this.inputValue.trim()) {
                        allTerms.push(this.inputValue.trim());
                    }
                    if (allTerms.length) {
                        allTerms.forEach(t => params.append('search[]', t));
                    }
                    const url = `${baseUrl}?${params.toString()}`;
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