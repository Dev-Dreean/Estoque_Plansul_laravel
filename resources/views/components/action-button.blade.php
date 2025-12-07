{{--
    Componente: Action Button
    Propósito: Botão de ação reutilizável com ícones e estilos consistentes
    
    @props
    - type: 'edit'|'delete'|'view'|'add'|'export' (define ícone e cor)
    - href: URL do link (opcional, se não informado usa button)
    - onclick: JavaScript inline (opcional)
    - label: Texto alternativo para acessibilidade
    - size: 'sm'|'md'|'lg' (default: md)
    
    Uso:
    <x-action-button type="edit" :href="route('patrimonios.edit', $item->id)" label="Editar" />
    <x-action-button type="delete" onclick="deletarItem(123)" label="Deletar" />
--}}

@props([
    'type' => 'edit',
    'href' => null,
    'onclick' => null,
    'label' => '',
    'size' => 'md'
])

@php
    // Configurações por tipo
    $configs = [
        'edit' => [
            'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
            'color' => 'text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300',
            'defaultLabel' => 'Editar'
        ],
        'delete' => [
            'icon' => 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16',
            'color' => 'text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300',
            'defaultLabel' => 'Deletar'
        ],
        'view' => [
            'icon' => 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z',
            'color' => 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300',
            'defaultLabel' => 'Visualizar'
        ],
        'add' => [
            'icon' => 'M12 4v16m8-8H4',
            'color' => 'text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300',
            'defaultLabel' => 'Adicionar'
        ],
        'export' => [
            'icon' => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4',
            'color' => 'text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-300',
            'defaultLabel' => 'Exportar'
        ]
    ];
    
    $config = $configs[$type] ?? $configs['edit'];
    $finalLabel = $label ?: $config['defaultLabel'];
    
    $sizes = [
        'sm' => 'w-4 h-4',
        'md' => 'w-5 h-5',
        'lg' => 'w-6 h-6'
    ];
    $iconSize = $sizes[$size] ?? $sizes['md'];
@endphp

@php
    $baseClasses = $config['color'] . ' transition-colors duration-200';
@endphp

@if($href)
    <a href="{{ $href }}"
       {{ $attributes->merge(['class' => $baseClasses]) }}
       title="{{ $finalLabel }}"
       aria-label="{{ $finalLabel }}">
        <svg class="{{ $iconSize }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $config['icon'] }}"/>
        </svg>
    </a>
@else
    <button type="button"
            @if($onclick) onclick="{{ $onclick }}" @endif
            {{ $attributes->merge(['class' => $baseClasses]) }}
            title="{{ $finalLabel }}"
            aria-label="{{ $finalLabel }}">
        <svg class="{{ $iconSize }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $config['icon'] }}"/>
        </svg>
    </button>
@endif
