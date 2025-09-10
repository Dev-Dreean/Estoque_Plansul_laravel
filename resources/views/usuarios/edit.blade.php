<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Editar Usuário') }}: {{ $usuario->NOMEUSER }}</h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    {{-- Usando o formulário de USUÁRIO e passando a variável correta --}}
                    <form method="POST" action="{{ route('usuarios.update', $usuario) }}">
                        @csrf
                        @method('PUT')
                        @include('usuarios.partials.form', ['usuario' => $usuario])
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>