<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
                    Criar Novo Usuário
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Preencha os dados do novo usuário do sistema.
                </p>
            </div>
            <a href="{{ route('usuarios.index') }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 transition hover:bg-gray-50 dark:hover:bg-gray-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Voltar à lista
            </a>
        </div>
    </x-slot>

    @php $canGrantTelas = auth()->user()?->isAdmin() ?? false; @endphp

    <div x-data="userForm({
        existingId: null,
        nomeOld: @js(old('NOMEUSER', '')),
        loginOld: @js(old('NMLOGIN', '')),
        matriculaOld: @js(old('CDMATRFUNCIONARIO', '')),
        perfilOld: @js(old('PERFIL', 'USR')),
        needsIdentityUpdateOld: false,
    })">

        {{-- Sub-navegacao (abaixo das abas Locais/Usuarios/Telas) --}}
        <div class="bg-surface-2 border-b border-app">
            <div class="w-full sm:px-6 lg:px-8">
                <div class="flex items-center overflow-x-auto min-h-[44px]">
                    <button type="button" @click="activeTab = 'dados'"
                        class="px-4 py-2.5 text-sm font-medium whitespace-nowrap border-b-2 transition-colors duration-150"
                        :class="activeTab === 'dados' ? 'accent-text accent-border' : 'text-muted border-transparent hover:text-[var(--text)]'">
                        Identificação
                    </button>
                    <button type="button" @click="activeTab = 'seguranca'"
                        class="px-4 py-2.5 text-sm font-medium whitespace-nowrap border-b-2 transition-colors duration-150"
                        :class="activeTab === 'seguranca' ? 'accent-text accent-border' : 'text-muted border-transparent hover:text-[var(--text)]'">
                        Segurança
                    </button>
                    @if($canGrantTelas)
                    <button type="button" @click="activeTab = 'permissoes'"
                        class="px-4 py-2.5 text-sm font-medium whitespace-nowrap border-b-2 transition-colors duration-150"
                        :class="activeTab === 'permissoes' ? 'accent-text accent-border' : 'text-muted border-transparent hover:text-[var(--text)]'">
                        Permissões
                    </button>
                    <button type="button" @click="activeTab = 'solicitacoes'"
                        class="px-4 py-2.5 text-sm font-medium whitespace-nowrap border-b-2 transition-colors duration-150"
                        :class="activeTab === 'solicitacoes' ? 'accent-text accent-border' : 'text-muted border-transparent hover:text-[var(--text)]'">
                        Solicitações de Bens
                    </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="py-8">
            <div class="w-full max-w-5xl mx-auto sm:px-6 lg:px-8">
                <form method="POST" action="{{ route('usuarios.store') }}">
                    @csrf
                    @include('usuarios.partials.form')
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
