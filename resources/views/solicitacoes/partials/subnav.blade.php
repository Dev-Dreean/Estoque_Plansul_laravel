@php
    $canSeeHistoricoSolicitacoes = auth()->user()?->isAdmin() || (auth()->user()?->temAcessoTela(1016) ?? false);
@endphp

<div class="mb-4">
    <div class="inline-flex rounded-lg border border-violet-200 overflow-hidden bg-white shadow-sm dark:border-violet-900 dark:bg-violet-950/40">
        @php
            $isSolicitacoes = request()->routeIs('solicitacoes-bens.index');
            $isHistorico = request()->routeIs('solicitacoes-bens.historico');
        @endphp
        <a href="{{ route('solicitacoes-bens.index') }}"
            class="px-4 py-2 text-sm font-medium transition-colors"
            style="{{ $isSolicitacoes ? 'background-color: #7c3aed; color: #ffffff;' : 'background-color: #ffffff; color: #6d28d9;' }}">
            Solicita&ccedil;&otilde;es
        </a>
        @if($canSeeHistoricoSolicitacoes)
            <a href="{{ route('solicitacoes-bens.historico') }}"
                class="border-l border-violet-200 px-4 py-2 text-sm font-medium transition-colors"
                style="{{ $isHistorico ? 'background-color: #7c3aed; color: #ffffff;' : 'background-color: #ffffff; color: #6d28d9;' }}">
                Hist&oacute;rico
            </a>
        @endif
    </div>
</div>
