<x-app-layout>
    {{-- Abas de navegação do patrimônio --}}
    <x-patrimonio-nav-tabs />

    <div class="py-6">
        <div class="w-full sm:px-6 lg:px-8">
            <div class="mb-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                    {{ __('Visualizar Patrimônio') }}: <span class="font-normal text-gray-600 dark:text-gray-400">{{ $patrimonio->DEPATRIMONIO }}</span>
                </h2>
                <p class="text-sm text-blue-600 dark:text-blue-400 mt-1">ℹ️ Modo de visualização (somente leitura)</p>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    {{-- Exibir form em modo read-only --}}
                    <div class="space-y-6">
                        {{-- Identificação --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="NUPATRIMONIO" value="Número do Patrimônio" />
                                <input type="text" id="NUPATRIMONIO" value="{{ $patrimonio->NUPATRIMONIO }}" readonly 
                                    class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-gray-900 dark:text-gray-100">
                            </div>
                            <div>
                                <x-input-label for="DEPATRIMONIO" value="Descrição" />
                                <input type="text" id="DEPATRIMONIO" value="{{ $patrimonio->DEPATRIMONIO }}" readonly 
                                    class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-gray-900 dark:text-gray-100">
                            </div>
                        </div>

                        {{-- Projeto e Local --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="CDPROJETO" value="Projeto" />
                                <input type="text" id="CDPROJETO" value="{{ $patrimonio->projeto?->NOMEPROJETO ?? '-' }}" readonly 
                                    class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-gray-900 dark:text-gray-100">
                            </div>
                            <div>
                                <x-input-label for="CDLOCAL" value="Local" />
                                <input type="text" id="CDLOCAL" value="{{ $patrimonio->local?->delocal ?? '-' }}" readonly 
                                    class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-gray-900 dark:text-gray-100">
                            </div>
                        </div>

                        {{-- Responsável --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="CDMATRFUNCIONARIO" value="Responsável (Matrícula)" />
                                <input type="text" id="CDMATRFUNCIONARIO" value="{{ $patrimonio->CDMATRFUNCIONARIO }}" readonly 
                                    class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-gray-900 dark:text-gray-100">
                            </div>
                            <div>
                                <x-input-label for="NOMEUSER" value="Responsável (Nome)" />
                                <input type="text" id="NOMEUSER" value="{{ $patrimonio->funcionario?->NOMEUSER ?? '-' }}" readonly 
                                    class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-gray-900 dark:text-gray-100">
                            </div>
                        </div>

                        {{-- Datas --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="DTAQUISICAO" value="Data de Aquisição" />
                                <input type="text" id="DTAQUISICAO" value="{{ $patrimonio->DTAQUISICAO ? \Carbon\Carbon::parse($patrimonio->DTAQUISICAO)->format('d/m/Y') : '-' }}" readonly 
                                    class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-gray-900 dark:text-gray-100">
                            </div>
                            <div>
                                <x-input-label for="DTOPERACAO" value="Data de Operação" />
                                <input type="text" id="DTOPERACAO" value="{{ $patrimonio->DTOPERACAO ? \Carbon\Carbon::parse($patrimonio->DTOPERACAO)->format('d/m/Y H:i') : '-' }}" readonly 
                                    class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-gray-900 dark:text-gray-100">
                            </div>
                        </div>

                        {{-- Situação --}}
                        <div>
                            <x-input-label for="SITUACAO" value="Situação" />
                            <input type="text" id="SITUACAO" value="{{ $patrimonio->SITUACAO ?? '-' }}" readonly 
                                class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-gray-900 dark:text-gray-100">
                        </div>

                        {{-- Usuário criador --}}
                        <div>
                            <x-input-label for="USUARIO" value="Criado por" />
                            <input type="text" id="USUARIO" value="{{ $patrimonio->USUARIO ?? '-' }}" readonly 
                                class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-gray-900 dark:text-gray-100">
                        </div>
                    </div>

                    {{-- Botões --}}
                    <div class="flex items-center justify-start mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                        <a href="{{ route('patrimonios.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition">← Voltar para Lista</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
