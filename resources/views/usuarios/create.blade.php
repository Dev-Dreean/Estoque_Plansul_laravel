<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Criar Novo Usuário') }}</h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    {{-- Usando o formulário de USUÁRIO --}}
                    <form method="POST" action="{{ route('usuarios.store') }}">
                        @csrf
                        @include('usuarios.partials.form')
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>