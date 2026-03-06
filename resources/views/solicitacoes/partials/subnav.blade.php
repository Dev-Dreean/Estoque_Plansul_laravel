@php
    $canSeeHistoricoSolicitacoes = auth()->user()?->isAdmin() || (auth()->user()?->temAcessoTela(1016) ?? false);
@endphp

<div class="mb-4">
    <div class="inline-flex rounded-lg border border-app overflow-hidden bg-panel">
        <a href="{{ route('solicitacoes-bens.index') }}"
            class="px-4 py-2 text-sm font-medium transition-colors {{ request()->routeIs('solicitacoes-bens.index') ? 'bg-indigo-600 text-white' : 'bg-panel text-app hover:bg-panel-alt' }}">
            Solicita&ccedil;&otilde;es
        </a>
        @if($canSeeHistoricoSolicitacoes)
            <a href="{{ route('solicitacoes-bens.historico') }}"
                class="px-4 py-2 text-sm font-medium transition-colors border-l border-app {{ request()->routeIs('solicitacoes-bens.historico') ? 'bg-indigo-600 text-white' : 'bg-panel text-app hover:bg-panel-alt' }}">
                Hist&oacute;rico
            </a>
        @endif
    </div>
</div>
