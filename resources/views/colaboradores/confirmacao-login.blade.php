<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
                Login Criado com Sucesso
            </h2>
            <a href="{{ route('colaboradores.index') }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 transition hover:bg-gray-50 dark:hover:bg-gray-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Voltar
            </a>
        </div>
    </x-slot>

    <div class="py-10 px-4 sm:px-6 lg:px-8 max-w-lg mx-auto">
        <div class="rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 p-6 shadow-sm mb-6 flex items-start gap-3">
            <svg class="h-6 w-6 text-green-600 dark:text-green-400 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            <div>
                <p class="font-semibold text-green-800 dark:text-green-200">Login criado com sucesso!</p>
                <p class="text-sm text-green-700 dark:text-green-300 mt-1">
                    O colaborador <strong>{{ $funcionario->NMFUNCIONARIO }}</strong> agora tem acesso ao sistema.
                    Repasse as credenciais abaixo para ele.
                </p>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm space-y-4">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <svg class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                Credenciais de Acesso
            </h3>

            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Colaborador</label>
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $funcionario->NMFUNCIONARIO }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Matrícula: {{ $funcionario->CDMATRFUNCIONARIO }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Login</label>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 rounded bg-gray-100 dark:bg-gray-900 px-3 py-2 text-sm font-mono text-gray-900 dark:text-gray-100 select-all">{{ $nmLogin }}</code>
                        <button onclick="navigator.clipboard.writeText('{{ $nmLogin }}').then(() => this.textContent = '✔').catch(()=>{}); setTimeout(() => this.textContent = 'Copiar', 1500)"
                            class="rounded border border-gray-300 dark:border-gray-600 px-3 py-2 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            Copiar
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Senha Provisória</label>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 rounded bg-gray-100 dark:bg-gray-900 px-3 py-2 text-sm font-mono text-gray-900 dark:text-gray-100 select-all">{{ $senhaProvisoria }}</code>
                        <button onclick="navigator.clipboard.writeText('{{ $senhaProvisoria }}').then(() => this.textContent = '✔').catch(()=>{}); setTimeout(() => this.textContent = 'Copiar', 1500)"
                            class="rounded border border-gray-300 dark:border-gray-600 px-3 py-2 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            Copiar
                        </button>
                    </div>
                </div>
            </div>

            <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                <p class="text-xs text-amber-600 dark:text-amber-400 flex items-start gap-1">
                    <svg class="h-4 w-4 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M12 3a9 9 0 110 18A9 9 0 0112 3z"/></svg>
                    O colaborador será solicitado a trocar a senha no primeiro acesso. Guarde estas credenciais antes de sair desta tela.
                </p>
            </div>
        </div>

        <div class="mt-6 flex justify-center">
            <a href="{{ route('colaboradores.index') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                Voltar à lista de colaboradores
            </a>
        </div>
    </div>

</x-app-layout>
