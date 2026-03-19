{{-- Componente: Status Badge --}}
@props([
    'status' => '',
    'styleMap' => [
        'PENDENTE' => 'background-color:#facc15;border-color:#eab308;color:#000;',
        'AGUARDANDO_CONFIRMACAO' => 'background-color:#60a5fa;border-color:#3b82f6;color:#000;',
        'LIBERACAO' => 'background-color:#c4b5fd;border-color:#8b5cf6;color:#000;',
        'CONFIRMADO' => 'background-color:#a78bfa;border-color:#7c3aed;color:#000;',
        'ENVIADO' => 'background-color:#a78bfa;border-color:#7c3aed;color:#000;',
        'RECEBIDO' => 'background-color:#4ade80;border-color:#22c55e;color:#000;',
        'NAO_ENVIADO' => 'background-color:#fb923c;border-color:#f97316;color:#000;',
        'NAO_RECEBIDO' => 'background-color:#fda4af;border-color:#f43f5e;color:#000;',
        'CANCELADO' => 'background-color:#f87171;border-color:#ef4444;color:#000;',
    ],
    'colorMap' => [
        'ATIVO' => 'bg-green-100 text-green-800 border border-green-200 dark:bg-green-500/20 dark:text-green-200 dark:border-green-400/40',
        'BAIXADO' => 'bg-red-100 text-red-800 border border-red-200 dark:bg-red-500/20 dark:text-red-200 dark:border-red-400/40',
        'MANUTENCAO' => 'bg-yellow-100 text-yellow-800 border border-yellow-200 dark:bg-yellow-500/20 dark:text-yellow-200 dark:border-yellow-400/40',
        'EMPRESTADO' => 'bg-blue-100 text-blue-800 border border-blue-200 dark:bg-blue-500/20 dark:text-blue-200 dark:border-blue-400/40',
        'AGUARDANDO_CONFIRMACAO' => 'bg-blue-100 text-blue-800 border border-blue-200 dark:bg-blue-500/20 dark:text-blue-200 dark:border-blue-400/40',
        'LIBERACAO' => 'bg-violet-100 text-violet-800 border border-violet-200 dark:bg-violet-500/20 dark:text-violet-200 dark:border-violet-400/40',
        'CONFIRMADO' => 'bg-green-100 text-green-800 border border-green-200 dark:bg-emerald-500/20 dark:text-emerald-200 dark:border-emerald-400/40',
        'ENVIADO' => 'bg-green-100 text-green-800 border border-green-200 dark:bg-emerald-500/20 dark:text-emerald-200 dark:border-emerald-400/40',
        'NAO_ENVIADO' => 'bg-amber-100 text-amber-800 border border-amber-200 dark:bg-amber-500/20 dark:text-amber-200 dark:border-amber-400/40',
        'NAO_RECEBIDO' => 'bg-rose-100 text-rose-800 border border-rose-200 dark:bg-rose-500/20 dark:text-rose-200 dark:border-rose-400/40',
        'RECEBIDO' => 'bg-cyan-100 text-cyan-800 border border-cyan-200 dark:bg-cyan-500/20 dark:text-cyan-200 dark:border-cyan-400/40',
        'CANCELADO' => 'bg-red-100 text-red-800 border border-red-200 dark:bg-red-500/20 dark:text-red-200 dark:border-red-400/40',
        'PENDENTE' => 'bg-yellow-100 text-yellow-800 border border-yellow-200 dark:bg-yellow-500/20 dark:text-yellow-200 dark:border-yellow-400/40',
    ],
])

@php
    $statusRaw = trim((string) $status);
    $statusUpper = mb_strtoupper($statusRaw, 'UTF-8');
    $statusKey = str_replace(['-', ' '], '_', $statusUpper);
    $badgeClass = $colorMap[$statusKey] ?? $colorMap[$statusUpper] ?? 'bg-gray-100 text-gray-800 border border-gray-200 dark:bg-gray-500/20 dark:text-gray-200 dark:border-gray-400/40';
    $badgeStyle = $styleMap[$statusKey] ?? $styleMap[$statusUpper] ?? '';

    $statusLabelMap = [
        'CRIADO' => 'SOLICITADO',
        'AGUARDANDO_CONFIRMACAO' => 'AGUARDANDO CONFIRMACAO',
        'LIBERACAO' => 'LIBERACAO',
        'CONFIRMADO' => 'ENVIO',
        'ENVIADO' => 'ENVIADO',
        'NAO_ENVIADO' => 'SEM ESTOQUE',
        'NAO_RECEBIDO' => 'NAO RECEBIDO',
    ];
    $statusDisplay = $statusLabelMap[$statusKey] ?? $statusRaw;
@endphp

<span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full {{ $badgeClass }}" @if($badgeStyle !== '') style="{{ $badgeStyle }}" @endif>
    {{ $statusDisplay }}
</span>
