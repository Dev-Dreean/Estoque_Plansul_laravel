<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{-- ALTERADO: Texto do cabeçalho --}}
            {{ __('Cadastrar Novo Local') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if(session('duplicating_from'))
                    <div class="mb-4 p-3 rounded bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 text-sm">
                        Duplicando a partir do local ID #{{ session('duplicating_from') }}. Código e Nome foram mantidos (apenas leitura). Escolha o <strong>novo Projeto Associado</strong> e salve.
                    </div>
                    @endif
                    <form method="POST" action="{{ route('projetos.store') }}">
                        @csrf
                        {{-- O arquivo _form conterá os campos corretos --}}
                        @include('projetos._form')

                        <div class="flex items-center justify-end mt-4">
                            <a href="{{ route('projetos.index') }}" class="mr-4 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900">Cancelar</a>
                            <x-primary-button>
                                {{-- ALTERADO: Texto do botão --}}
                                {{ __('Salvar Local') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>