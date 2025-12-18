@props([
    'id' => 'confirmationModal',
    'title' => 'Confirmação',
    'message' => 'Tem certeza?',
    'cancelText' => 'Cancelar',
    'confirmText' => 'Confirmar',
    'confirmVariant' => 'indigo', // 'indigo', 'red', 'emerald'
])

@php
    $variantClasses = [
        'indigo' => 'bg-indigo-600 hover:bg-indigo-700',
        'red' => 'bg-red-600 hover:bg-red-700',
        'emerald' => 'bg-emerald-600 hover:bg-emerald-700',
    ];
    
    $bgClass = $variantClasses[$confirmVariant] ?? $variantClasses['indigo'];
@endphp

<div x-show="modalConfirmacao?.show" x-cloak x-transition 
     class="fixed inset-0 bg-black/60 dark:bg-black/80 z-50 flex items-center justify-center p-4" 
     @keydown.escape.window="modalConfirmacao.show = false" 
     @click.self="modalConfirmacao.show = false">
    
    <div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg shadow-2xl p-6 max-w-sm w-full border border-gray-200 dark:border-gray-700" @click.stop>
        
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ $title }}</h3>
        
        <p class="text-gray-600 dark:text-gray-300 mb-6">
            {!! $message !!}
        </p>
        
        <div class="flex gap-3 justify-end">
            <button
                type="button"
                class="px-4 py-2 text-sm font-semibold rounded-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 transition"
                @click="modalConfirmacao.show = false"
            >
                {{ $cancelText }}
            </button>
            <button
                type="button"
                class="px-4 py-2 text-sm font-semibold rounded-md {{ $bgClass }} text-white transition"
                @click="modalConfirmacao.confirmAction(); modalConfirmacao.show = false;"
            >
                {{ $confirmText }}
            </button>
        </div>
    </div>
</div>
