@php
    $canSeeHistoricoSolicitacoes = auth()->user()?->isAdmin() || (auth()->user()?->temAcessoTela(1016) ?? false);
@endphp

<div class="mb-4">
    <div class="sol-subnav">
        @php
            $isSolicitacoes = request()->routeIs('solicitacoes-bens.index');
            $isHistorico = request()->routeIs('solicitacoes-bens.historico');
        @endphp
        <a href="{{ route('solicitacoes-bens.index') }}"
            class="sol-subnav__tab {{ $isSolicitacoes ? 'sol-subnav__tab--active' : '' }}">
            Solicita&ccedil;&otilde;es
        </a>
        @if($canSeeHistoricoSolicitacoes)
            <a href="{{ route('solicitacoes-bens.historico') }}"
                class="sol-subnav__tab {{ $isHistorico ? 'sol-subnav__tab--active' : '' }}">
                Hist&oacute;rico
            </a>
        @endif
    </div>
</div>
