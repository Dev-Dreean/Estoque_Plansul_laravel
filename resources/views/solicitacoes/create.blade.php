@php
    $defaultNome = old('solicitante_nome', $user?->NOMEUSER ?? '');
    $defaultMatricula = old('solicitante_matricula', $user?->CDMATRFUNCIONARIO ?? '');
    $defaultReceberEuMesmo = old('receber_eu_mesmo', '1');
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

        
        <div class="pt-6 w-full sm:px-6 lg:px-8">
            @include('solicitacoes.partials.subnav')
        </div>
        @include('solicitacoes.partials.form', [
            'defaultNome' => $defaultNome,
            'defaultMatricula' => $defaultMatricula,
            'defaultReceberEuMesmo' => $defaultReceberEuMesmo,
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
        'defaultReceberEuMesmo' => $defaultReceberEuMesmo,
        'defaultUf' => $defaultUf,
        'oldItens' => $oldItens,
        'isModal' => $isModal,
        'projetos' => $projetos,
    ])
@endif
