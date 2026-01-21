@php
    $isModal = request('modal') === '1';
@endphp

@unless($isModal)
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Solicitacao #') }}{{ $solicitacao->id }}
            </h2>
            <a href="{{ route('solicitacoes-bens.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">Voltar</a>
        </div>
    </x-slot>
@endunless

    @include('solicitacoes.partials.show-content', [
        'solicitacao' => $solicitacao,
        'statusOptions' => $statusOptions,
        'isModal' => $isModal,
    ])
@unless($isModal)
</x-app-layout>
@endunless
