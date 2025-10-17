<x-app-layout>
    <!-- no-page-scroll applied to block body scrolling; wrapper uses full viewport height minus spacing -->
    <div class="py-8 h-[calc(100vh-4rem)] flex flex-col no-page-scroll">
        <div class="w-full sm:px-6 lg:px-8 flex-1 flex flex-col min-h-0">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg flex-1 flex flex-col min-h-0">
                <div class="p-4 flex flex-col flex-1 min-h-0">
                    <h1 class="text-2xl font-bold mb-4 text-gray-900 dark:text-gray-100">Cadastro de Telas</h1>

                    @if(session('success'))
                    <div class="mb-4 p-3 rounded-md bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                        {{ session('success') }}
                    </div>
                    @endif

                    <form action="{{ route('cadastro-tela.store') }}" method="POST" class="mb-4">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                            <div>
                                <input
                                    type="number"
                                    name="NUSEQTELA"
                                    id="NUSEQTELA"
                                    value="{{ old('NUSEQTELA') }}"
                                    class="h-9 px-3 w-full text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md focus:ring-2 focus:ring-plansul-blue focus:border-transparent"
                                    placeholder="Código da Tela" />
                                @error('NUSEQTELA')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <input
                                    type="text"
                                    name="DETELA"
                                    id="DETELA"
                                    value="{{ old('DETELA') }}"
                                    class="h-9 px-3 w-full text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md focus:ring-2 focus:ring-plansul-blue focus:border-transparent"
                                    maxlength="100"
                                    placeholder="Nome da Tela">
                                @error('DETELA')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <input
                                    type="text"
                                    name="NMSISTEMA"
                                    id="NMSISTEMA"
                                    value="{{ old('NMSISTEMA') }}"
                                    class="h-9 px-3 w-full text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md focus:ring-2 focus:ring-plansul-blue focus:border-transparent"
                                    maxlength="60"
                                    placeholder="Sistema">
                                @error('NMSISTEMA')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="bg-plansul-blue hover:bg-opacity-90 text-white font-semibold py-1.5 px-5 rounded-full inline-flex items-center text-sm transition-all duration-200 shadow-sm hover:shadow-md">
                                Salvar
                            </button>
                        </div>
                    </form>

                    <!-- Separador visual -->
                    <div class="my-6 border-t border-gray-300 dark:border-gray-600"></div>

                    <!-- Seção de Listagem de Telas -->
                    <div class="flex-1 flex flex-col min-h-0">
                        <div class="mb-4">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Telas Cadastradas</h2>
                        </div>

                        <div class="relative shadow-md sm:rounded-lg flex-1 min-h-0 overflow-hidden">
                            <div class="telas-grid-wrapper scrollbar-thin">
                                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                            <th scope="col" class="px-4 py-3 sticky top-0 bg-gray-50 dark:bg-gray-700 z-10">Nome da Tela</th>
                                            <th scope="col" class="px-4 py-3 sticky top-0 bg-gray-50 dark:bg-gray-700 z-10">Rota</th>
                                            <th scope="col" class="px-4 py-3 sticky top-0 bg-gray-50 dark:bg-gray-700 z-10">Código</th>
                                            <th scope="col" class="px-4 py-3 sticky top-0 bg-gray-50 dark:bg-gray-700 z-10">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($telasGrid as $row)
                                        <tr class="tr-hover border-b dark:border-gray-700">
                                            <td class="px-4 py-3">
                                                @if(!$row['cadastrada'])
                                                <form action="{{ route('cadastro-tela.showForm', $row['DETELA']) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:underline font-medium">
                                                        {{ $row['DETELA'] }}
                                                    </button>
                                                </form>
                                                @else
                                                <span class="text-gray-900 dark:text-gray-100">{{ $row['DETELA'] }}</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                                {{ $row['rota'] ?? '-' }}
                                            </td>
                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                                {{ $row['NUSEQTELA'] ?? '-' }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-3">
                                                    @if($row['cadastrada'])
                                                    <span class="text-green-600 dark:text-green-400 font-medium inline-block min-w-[120px]">Cadastrada</span>
                                                    @else
                                                    <span class="text-red-600 dark:text-red-400 font-medium inline-block min-w-[120px]">Não vinculada</span>
                                                    <form action="{{ route('cadastro-tela.gerarVincular', $row['DETELA']) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" title="Vincular tela automaticamente" class="bg-plansul-blue hover:bg-blue-700 text-white rounded-full inline-flex items-center justify-center w-8 h-8 transition-all duration-200 shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-plansul-blue focus:ring-offset-2">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                                Nenhuma tela encontrada.
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

@push('scripts')
<script>
    // Aplica no-page-scroll ao carregar a view e remove ao sair
    (function() {
        document.documentElement.classList.add('no-page-scroll');
        document.body.classList.add('no-page-scroll');
        window.addEventListener('beforeunload', function() {
            document.documentElement.classList.remove('no-page-scroll');
            document.body.classList.remove('no-page-scroll');
        });
        // também remove ao navegar via history API (single page nav)
        window.addEventListener('popstate', function() {
            document.documentElement.classList.remove('no-page-scroll');
            document.body.classList.remove('no-page-scroll');
        });
    })();
</script>
@endpush