<x-app-layout>
    {{-- Abas de navegação do patrimônio --}}
    <x-patrimonio-nav-tabs />

    <div class="py-12">
        <div class="w-full sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                <div class="p-6"> {{-- GARANTA QUE ESTE p-6 ESTÁ AQUI --}}
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