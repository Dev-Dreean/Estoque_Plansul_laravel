<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Cadastrar Novo Patrimônio') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="w-full sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <form method="POST" action="{{ route('patrimonios.store') }}">
                        @csrf
                        <x-patrimonio-form />

                        <div class="flex items-center justify-end mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                            <a href="{{ route('patrimonios.index') }}" class="mr-4">Cancelar</a>
                            <x-primary-button>{{ __('Salvar Patrimônio') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>