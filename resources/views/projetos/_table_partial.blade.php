{{-- Renderiza a tabela completa com estrutura e controles Alpine --}}

<div class="relative overflow-x-auto shadow-md sm:rounded-lg">
    {{-- Toast de Sucesso/Erro --}}
    <div x-show="toast.show" x-transition class="fixed top-4 right-4 z-[9999] max-w-sm" @click="toast.show = false">
        <div :class="toast.tipo === 'sucesso' ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800'" class="rounded-lg shadow-lg p-4">
            <div class="flex items-start gap-3">
                <div :class="toast.tipo === 'sucesso' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                    <template x-if="toast.tipo === 'sucesso'">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </template>
                    <template x-if="toast.tipo === 'erro'">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 4v2m0 4v2m0 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </template>
                </div>
                <div class="flex-1">
                    <p :class="toast.tipo === 'sucesso' ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200'" class="font-semibold" x-text="toast.mensagem"></p>
                </div>
                <button @click="toast.show = false" :class="toast.tipo === 'sucesso' ? 'text-green-500 hover:text-green-700' : 'text-red-500 hover:text-red-700'" class="text-lg">×</button>
            </div>
        </div>
    </div>

    {{-- Barra de Seleção Múltipla --}}
    <div x-show="selecionados.length > 0" x-transition class="bg-blue-50 dark:bg-blue-900/20 border-b border-blue-200 dark:border-blue-800 px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="text-sm font-semibold text-blue-900 dark:text-blue-200">
                <span x-text="selecionados.length"></span>
                <span x-text="selecionados.length === 1 ? 'local selecionado' : 'locais selecionados'"></span>
            </span>
        </div>
        <div class="flex items-center gap-2">
            <button
                type="button"
                @click="limparSelecao()"
                class="px-3 py-1 text-sm bg-gray-300 hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 rounded transition">
                Desselecionar
            </button>
            @if(Auth::user()->isSuperAdmin())
            <button
                type="button"
                @click="abrirModalDelecao()"
                class="px-3 py-1 text-sm bg-red-600 hover:bg-red-700 text-white rounded transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                Remover Selecionados
            </button>
            @endif
        </div>
    </div>

    <table class="w-full text-base text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th class="px-4 py-3 w-12">
                    <input
                        type="checkbox"
                        id="checkbox-header"
                        @change="toggleTodos($event.target.checked)"
                        class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 cursor-pointer">
                </th>
                <th class="px-4 py-3">Cód. Local</th>
                <th class="px-4 py-3">Nome do Local</th>
                <th class="px-4 py-3">Projeto Associado</th>
                <th class="px-4 py-3">Ações</th>
            </tr>
        </thead>
        <tbody>
            {{-- O conteúdo das linhas será inserido aqui via AJAX/fetch --}}
            @include('projetos._table_rows', ['locais' => $locais])
        </tbody>
    </table>

    {{-- Modal Simples de Confirmação --}}
    <div x-show="mostraModalDelecao" x-transition class="fixed inset-0 bg-black/50 dark:bg-black/70 z-50 flex items-center justify-center" @click.self="mostraModalDelecao = false" style="display: none;">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 max-w-sm mx-4" @click.stop>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Confirmar Remoção</h3>

            <p class="text-gray-600 dark:text-gray-300 mb-6">
                <template x-if="localParaDelecaoIndividual">
                    Tem certeza que deseja remover o local "<strong x-text="localParaDelecaoIndividual.nome"></strong>"?
                </template>
                <template x-if="!localParaDelecaoIndividual">
                    Tem certeza que deseja remover <strong x-text="selecionados.length"></strong>
                    <span x-text="selecionados.length === 1 ? 'local' : 'locais'"></span>?
                </template>
            </p>

            <div class="flex gap-3 justify-end">
                <button
                    type="button"
                    @click="mostraModalDelecao = false"
                    :disabled="carregando"
                    class="px-4 py-2 text-sm bg-gray-300 hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 rounded transition disabled:opacity-50 disabled:cursor-not-allowed">
                    Cancelar
                </button>
                <button
                    type="button"
                    @click="confirmarDelecao()"
                    :disabled="carregando"
                    class="px-4 py-2 text-sm bg-red-600 hover:bg-red-700 text-white rounded transition font-semibold flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                    <template x-if="!carregando">
                        <span>Remover</span>
                    </template>
                    <template x-if="carregando">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Removendo...</span>
                    </template>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="mt-4">
    {{ $locais->links() }}
</div>