@php
    $defaultNome = old('solicitante_nome', $user?->NOMEUSER ?? '');
    $defaultMatricula = old('solicitante_matricula', $user?->CDMATRFUNCIONARIO ?? '');
    $defaultUf = old('uf', $user?->UF ?? '');
    $oldItens = old('itens');
    if (!is_array($oldItens) || count($oldItens) === 0) {
        $oldItens = [
            ['descricao' => '', 'quantidade' => 1, 'unidade' => '', 'observacao' => ''],
        ];
    }
    $isModal = request('modal') === '1';
@endphp

@if(!$isModal)
    <x-app-layout>
        <x-slot name="header">
            <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Nova Solicitação de Bens') }}
            </h2>
        </x-slot>

        @include('solicitacoes.partials.form', [
            'defaultNome' => $defaultNome,
            'defaultMatricula' => $defaultMatricula,
            'defaultUf' => $defaultUf,
            'oldItens' => $oldItens,
            'isModal' => $isModal,
            'projetos' => $projetos,
        ])
    </x-app-layout>
@else
    @include('solicitacoes.partials.form', [
        'defaultNome' => $defaultNome,
        'defaultMatricula' => $defaultMatricula,
        'defaultUf' => $defaultUf,
        'oldItens' => $oldItens,
        'isModal' => $isModal,
        'projetos' => $projetos,
    ])
@endif
