<x-app-layout>
    <x-slot name="header">
        <div style="height:0.8em;line-height:0.8em;padding:0;margin:0;overflow:hidden;background:inherit;">
            <h2 style="font-size:0.95em;font-weight:600;color:#fff;margin:0;padding:0;line-height:0.8em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                {{ __('Editar Patrimônio') }}: <span style="font-weight:400;">{{ $patrimonio->DEPATRIMONIO }}</span>
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="w-full sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route('patrimonios.update', $patrimonio) }}">
                        @csrf
                        @method('PUT')

                        <x-patrimonio-form :patrimonio="$patrimonio" />

                        <div class="flex items-center justify-start mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                            <a href="{{ route('patrimonios.index') }}" class="mr-4">Cancelar</a>
                            <x-primary-button>{{ __('Atualizar Patrimônio') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>