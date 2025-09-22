<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{-- ALTERADO: Usa a variável $local em vez de $projeto --}}
            Editar Local: {{ $local->delocal }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    {{-- Formulário de Edição --}}
                    {{-- ALTERADO: Rota de update agora usa $local --}}
                    <form method="POST" action="{{ route('projetos.update', $local) }}">
                        @csrf
                        @method('PUT') {{-- Importante para formulários de edição --}}

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="cdlocal" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Código do Local</label>
                                <input id="cdlocal" name="cdlocal" type="number" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                    value="{{ old('cdlocal', $local->cdlocal) }}" required autofocus>
                                @error('cdlocal')
                                <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="delocal" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Nome do Local</label>
                                <input id="delocal" name="delocal" type="text" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                    value="{{ old('delocal', $local->delocal) }}" required>
                                @error('delocal')
                                <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label for="tabfant_id" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Projeto Associado</label>
                                <select id="tabfant_id" name="tabfant_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" required>
                                    <option value="">Selecione um projeto</option>
                                    {{-- O controller envia a variável $projetos com a lista --}}
                                    @foreach ($projetos as $projeto_opcao)
                                    <option value="{{ $projeto_opcao->id }}"
                                        {{-- Marca o projeto atual como selecionado --}}
                                        @if(old('tabfant_id', $local->tabfant_id) == $projeto_opcao->id) selected @endif
                                        >
                                        {{ $projeto_opcao->NOMEPROJETO }} (Cód: {{ $projeto_opcao->CDPROJETO }})
                                    </option>
                                    @endforeach
                                </select>
                                @error('tabfant_id')
                                <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('projetos.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mr-4">
                                Cancelar
                            </a>
                            <button type="submit" class="bg-plansul-blue hover:bg-opacity-90 text-white font-bold py-2 px-4 rounded">
                                Salvar Alterações
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>