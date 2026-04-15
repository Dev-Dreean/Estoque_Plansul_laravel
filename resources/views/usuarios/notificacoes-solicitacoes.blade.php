<x-app-layout>
    @php
        $totalUsuarios = $matriz->count();
        $totalComEmail = $matriz->filter(fn ($linha) => filled($linha['usuario']->email))->count();
        $totalSemEmail = $totalUsuarios - $totalComEmail;
        $totalPapeisAtivos = $matriz->sum(fn ($linha) => count($linha['papeis']));
    @endphp

    <x-slot name="header">
        <div class="usuarios-notificacoes__hero">
            <div class="usuarios-notificacoes__hero-copy">
                <span class="usuarios-notificacoes__eyebrow">Solicitações de bens</span>
                <h2 class="usuarios-notificacoes__title">Notificações operacionais por etapa</h2>
                <p class="usuarios-notificacoes__subtitle">
                    Configure quem recebe avisos em cada fase do fluxo e identifique rapidamente onde ainda falta cobertura.
                </p>
            </div>

            <a href="{{ route('usuarios.index') }}" class="usuarios-notificacoes__back-link">
                Voltar para usuários
            </a>
        </div>
    </x-slot>

    <div class="usuarios-notificacoes">
        <div class="usuarios-notificacoes__shell">
            <section class="usuarios-notificacoes__summary">
                <article class="usuarios-notificacoes__metric usuarios-notificacoes__metric--primary">
                    <span class="usuarios-notificacoes__metric-label">Usuários monitorados</span>
                    <strong class="usuarios-notificacoes__metric-value">{{ $totalUsuarios }}</strong>
                    <span class="usuarios-notificacoes__metric-helper">Base total disponível para receber notificações</span>
                </article>

                <article class="usuarios-notificacoes__metric">
                    <span class="usuarios-notificacoes__metric-label">Com e-mail ativo</span>
                    <strong class="usuarios-notificacoes__metric-value">{{ $totalComEmail }}</strong>
                    <span class="usuarios-notificacoes__metric-helper">Prontos para receber avisos automáticos</span>
                </article>

                <article class="usuarios-notificacoes__metric">
                    <span class="usuarios-notificacoes__metric-label">Sem e-mail cadastrado</span>
                    <strong class="usuarios-notificacoes__metric-value">{{ $totalSemEmail }}</strong>
                    <span class="usuarios-notificacoes__metric-helper">Precisam de ajuste antes do envio</span>
                </article>

                <article class="usuarios-notificacoes__metric">
                    <span class="usuarios-notificacoes__metric-label">Coberturas ativas</span>
                    <strong class="usuarios-notificacoes__metric-value">{{ $totalPapeisAtivos }}</strong>
                    <span class="usuarios-notificacoes__metric-helper">Papéis atualmente marcados na operação</span>
                </article>
            </section>

            <section class="usuarios-notificacoes__panel">
                <div class="usuarios-notificacoes__panel-header">
                    <div>
                        <span class="usuarios-notificacoes__panel-chip">Como funciona</span>
                        <h3 class="usuarios-notificacoes__panel-title">Quem recebe cada aviso</h3>
                        <p class="usuarios-notificacoes__panel-text">
                            Na criação de uma solicitação, o sistema avisa os responsáveis marcados abaixo e mantém o solicitante informado nas etapas seguintes.
                        </p>
                    </div>

                    <div class="usuarios-notificacoes__legend">
                        <span class="usuarios-notificacoes__legend-item">
                            <span class="usuarios-notificacoes__legend-dot usuarios-notificacoes__legend-dot--on"></span>
                            Recebe
                        </span>
                        <span class="usuarios-notificacoes__legend-item">
                            <span class="usuarios-notificacoes__legend-dot usuarios-notificacoes__legend-dot--off"></span>
                            Não recebe
                        </span>
                    </div>
                </div>

                <div class="usuarios-notificacoes__cards">
                    @forelse($matriz as $linha)
                        @php
                            /** @var \App\Models\User $usuario */
                            $usuario = $linha['usuario'];
                            $papeis = $linha['papeis'];
                            $temEmail = filled($usuario->email);
                        @endphp

                        <article class="usuarios-notificacoes__user-card">
                            <div class="usuarios-notificacoes__user-main">
                                <div class="usuarios-notificacoes__user-head">
                                    <div>
                                        <h4 class="usuarios-notificacoes__user-name">{{ $usuario->NOMEUSER }}</h4>
                                        <div class="usuarios-notificacoes__user-login">{{ $usuario->NMLOGIN }}</div>
                                    </div>

                                    @if($temEmail)
                                        <span class="usuarios-notificacoes__email-badge usuarios-notificacoes__email-badge--ok">
                                            E-mail pronto
                                        </span>
                                    @else
                                        <span class="usuarios-notificacoes__email-badge usuarios-notificacoes__email-badge--missing">
                                            Sem e-mail cadastrado
                                        </span>
                                    @endif
                                </div>

                                <div class="usuarios-notificacoes__user-email">
                                    {{ $temEmail ? $usuario->email : 'Cadastre um e-mail para liberar os disparos automáticos.' }}
                                </div>
                            </div>

                            <div class="usuarios-notificacoes__roles">
                                @foreach($papeisNotificacaoDisponiveis as $papel => $dadosPapel)
                                    @php $ativo = in_array($papel, $papeis, true); @endphp
                                    <div class="usuarios-notificacoes__role {{ $ativo ? 'usuarios-notificacoes__role--on' : 'usuarios-notificacoes__role--off' }}">
                                        <div class="usuarios-notificacoes__role-top">
                                            <span class="usuarios-notificacoes__role-title">{{ $dadosPapel['titulo'] }}</span>
                                            <span class="usuarios-notificacoes__role-status">{{ $ativo ? 'Recebe' : 'Não recebe' }}</span>
                                        </div>
                                        <p class="usuarios-notificacoes__role-description">{{ $dadosPapel['descricao'] }}</p>
                                    </div>
                                @endforeach
                            </div>

                            <div class="usuarios-notificacoes__actions">
                                <a href="{{ route('usuarios.edit', $usuario) }}" class="usuarios-notificacoes__edit-link">
                                    Editar usuário
                                </a>
                            </div>
                        </article>
                    @empty
                        <div class="usuarios-notificacoes__empty">
                            Nenhum usuário foi encontrado para configurar as notificações de solicitações.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
