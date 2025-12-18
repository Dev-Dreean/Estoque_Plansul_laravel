@props([
    'id' => 'confirmationModal',
    'title' => 'Confirmação',
    'message' => 'Tem certeza?',
    'cancelText' => 'Cancelar',
    'confirmText' => 'Confirmar',
    'confirmVariant' => 'indigo', // 'indigo', 'red', 'emerald'
])

<div x-show="modalConfirmacao?.show" x-cloak x-transition 
     class="fixed inset-0 bg-black/60 dark:bg-black/80 z-50 flex items-center justify-center p-4" 
     @keydown.escape.window="modalConfirmacao.show = false" 
     @click.self="modalConfirmacao.show = false">
    
    <div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg shadow-2xl p-6 max-w-sm w-full border border-gray-200 dark:border-gray-700" @click.stop>
        
        <!-- Ícone de aviso -->
        <div class="flex items-center justify-center mb-4">
            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900/30">
                <svg class="h-6 w-6 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c.866 1.5 2.537 2.912 4.583 2.912h10.84c2.046 0 3.717-1.412 4.583-2.912M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        <h3 class="text-center text-lg font-semibold text-gray-900 dark:text-white mb-3">{{ $title }}</h3>
        
        <p class="text-center text-sm text-gray-600 dark:text-gray-400 mb-6">
            {!! $message !!}
        </p>
        
        <div class="flex gap-3 justify-center">
            <button
                type="button"
                class="px-4 py-2 text-sm font-medium rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 dark:focus:ring-offset-gray-800"
                @click="modalConfirmacao.show = false"
            >
                {{ $cancelText }}
            </button>
            <button
                type="button"
                class="px-4 py-2 text-sm font-medium rounded-md bg-indigo-600 text-white hover:bg-indigo-700 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
                @click="modalConfirmacao.confirmAction(); modalConfirmacao.show = false;"
            >
                {{ $confirmText }}
            </button>
        </div>
    </div>
</div>
