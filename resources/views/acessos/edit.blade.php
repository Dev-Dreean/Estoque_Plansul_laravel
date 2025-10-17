<x-app-layout>
    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6">
                        <a href="{{ route('acessos.index') }}" class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Voltar para lista
                        </a>
                    </div>

                    <h1 class="text-2xl font-bold mb-2 text-gray-900 dark:text-gray-100">
                        Gerenciar Acessos
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Usuário: <strong>{{ $usuario->NOMEUSER }}</strong> ({{ $usuario->NMLOGIN }})
                        @if($usuario->PERFIL === 'ADM')
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300">
                            Administrador - Acesso Total
                        </span>
                        @endif
                    </p>

                    @if(session('error'))
                    <div class="mb-4 p-4 rounded-md bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400">
                        {{ session('error') }}
                    </div>
                    @endif

                    @if($usuario->PERFIL === 'ADM')
                    <div class="mb-6 p-4 rounded-md bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300">
                        <p class="text-sm">
                            <strong>Atenção:</strong> Este usuário é administrador e tem acesso automático a todas as telas do sistema.
                            As configurações abaixo não afetarão seus acessos.
                        </p>
                    </div>
                    @endif

                    <form action="{{ route('acessos.update', $usuario->CDMATRFUNCIONARIO) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-6">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    Telas Disponíveis
                                </h2>
                                <div class="flex gap-2">
                                    <button type="button" onclick="marcarTodas(true)" class="text-sm px-3 py-1 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded text-gray-700 dark:text-gray-300">
                                        Marcar Todas
                                    </button>
                                    <button type="button" onclick="marcarTodas(false)" class="text-sm px-3 py-1 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded text-gray-700 dark:text-gray-300">
                                        Desmarcar Todas
                                    </button>
                                </div>
                            </div>

                            @if($telas->isEmpty())
                            <p class="text-gray-500 dark:text-gray-400 text-sm">
                                Nenhuma tela cadastrada no sistema.
                            </p>
                            @else
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-96 overflow-y-auto p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-900">
                                @foreach($telas as $tela)
                                <label class="flex items-start p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-white dark:hover:bg-gray-800 cursor-pointer transition-colors">
                                    <input
                                        type="checkbox"
                                        name="telas[]"
                                        value="{{ $tela->NUSEQTELA }}"
                                        class="tela-checkbox mt-1 rounded border-gray-300 text-plansul-blue focus:ring-plansul-blue focus:ring-offset-0"
                                        {{ in_array($tela->NUSEQTELA, $acessosAtuais) ? 'checked' : '' }}>
                                    <div class="ml-3 flex-1">
                                        <span class="block text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $tela->DETELA }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            Código: {{ $tela->NUSEQTELA }}
                                            @if($tela->NMSISTEMA)
                                            | Sistema: {{ $tela->NMSISTEMA }}
                                            @endif
                                        </span>
                                    </div>
                                </label>
                                @endforeach
                            </div>
                            @endif

                            @error('telas')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-end gap-3">
                            <a href="{{ route('acessos.index') }}"
                                class="px-6 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-semibold rounded-full transition-all duration-200">
                                Cancelar
                            </a>
                            <button type="submit"
                                class="px-6 py-2 bg-plansul-blue hover:bg-opacity-90 text-white font-semibold rounded-full transition-all duration-200 shadow-sm hover:shadow-md">
                                Salvar Acessos
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function marcarTodas(marcar) {
            const checkboxes = document.querySelectorAll('.tela-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = marcar;
            });
        }
    </script>
</x-app-layout>