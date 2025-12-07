{{--
    Componente: Status Badge
    Propósito: Badge reutilizável para exibir status com cores padronizadas
    
    @props
    - status: string do status (ex: 'ATIVO', 'BAIXADO', 'MANUTENÇÃO')
    - colorMap: array customizado de status => cor (opcional)
    
    Uso:
    <x-status-badge :status="$patrimonio->DESITUACAO" />
--}}

@props([
    'status' => '',
    'colorMap' => [
        'ATIVO' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        'BAIXADO' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        'MANUTENÇÃO' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        'EMPRESTADO' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    ]
])

@php
    $statusUpper = strtoupper(trim($status));
    $badgeClass = $colorMap[$statusUpper] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
@endphp

<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $badgeClass }}">
    {{ $status }}
</span>
