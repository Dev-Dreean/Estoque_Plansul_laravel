<x-app-layout>
    <x-slot name="header">
        @php $isFilialHeader = request()->has('filial'); @endphp
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $isFilialHeader ? 'Incluindo filial do ' . $projeto->NOMEPROJETO : 'Editar Projeto: ' . $projeto->NOMEPROJETO }}
            </h2>
            @unless($isFilialHeader)
            <a href="{{ route('projetos.edit', [$projeto, 'filial' => 1]) }}" class="text-sm bg-plansul-blue hover:bg-opacity-90 text-white font-semibold py-1.5 px-3 rounded" title="Adicionar nova filial">
                <x-heroicon-o-plus-circle class="w-5 h-5 inline" />
            </a>
            @endunless
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @php $isFilial = request()->has('filial'); @endphp
                    <form method="POST" action="{{ $isFilial ? route('projetos.store') : route('projetos.update', $projeto) }}">
                        @csrf
                        @if(!$isFilial)
                        @method('PUT')
                        @endif
                        @include('projetos._form', ['projeto' => $isFilial ? (object) array_merge($projeto->toArray(), ['LOCAL' => '']) : $projeto])
                        <div class="flex items-center justify-end mt-4">
                            <a href="{{ route('projetos.index') }}" class="mr-4">Cancelar</a>
                            <x-primary-button>
                                {{ $isFilial ? __('Salvar Nova Filial') : __('Atualizar Projeto') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
@stack('scripts')