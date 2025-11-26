@props(['class' => ''])

@php
use App\Helpers\MenuHelper;

$telasMenu = MenuHelper::getTelasParaMenu();
@endphp

@if(count($telasMenu) > 0)
<div class="navigation-menu {{ $class }}">
    @foreach($telasMenu as $nuseqtela => $tela)
        @php
            $nome = $tela['nome'] ?? 'Sem nome';
            $descricao = $tela['descricao'] ?? '';
            $route = $tela['route'] ?? null;
            $icone = $tela['icone'] ?? 'fa-window';
            $cor = $tela['cor'] ?? 'blue';
            
            // Verifica se a rota existe
            $rotaValida = $route && MenuHelper::rotaExiste($route);
        @endphp

        @if($rotaValida)
            <a href="{{ route($route) }}" 
               class="nav-item nav-item-{{ $cor }}" 
               title="{{ $descricao }}">
                <i class="fas {{ $icone }}"></i>
                <span>{{ $nome }}</span>
            </a>
        @else
            <div class="nav-item nav-item-disabled" 
                 title="Esta tela está em desenvolvimento">
                <i class="fas {{ $icone }}"></i>
                <span>{{ $nome }}</span>
            </div>
        @endif
    @endforeach
</div>
@else
    <div class="no-access-message">
        <i class="fas fa-lock"></i>
        <p>Você não tem acesso a nenhuma tela no momento.</p>
        <p class="text-sm">Entre em contato com o administrador para solicitar permissões.</p>
    </div>
@endif

<style>
.navigation-menu {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    padding: 1rem;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    border-radius: 0.5rem;
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: white;
    text-decoration: none;
    transition: all 0.3s ease;
    font-weight: 600;
}

.nav-item:hover:not(.nav-item-disabled) {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    border-color: rgba(251, 146, 60, 0.5);
    background: rgba(251, 146, 60, 0.1);
}

.nav-item i {
    font-size: 1.25rem;
}

.nav-item-disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.no-access-message {
    text-align: center;
    padding: 3rem 2rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 1rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.8);
}

.no-access-message i {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: rgba(251, 146, 60, 0.5);
}

.no-access-message p {
    margin: 0.5rem 0;
}

.no-access-message .text-sm {
    font-size: 0.875rem;
    opacity: 0.7;
}

/* Cores específicas para cada tipo de tela */
.nav-item-blue:hover { border-color: rgba(37, 99, 235, 0.6); background: rgba(37, 99, 235, 0.1); }
.nav-item-indigo:hover { border-color: rgba(99, 102, 241, 0.6); background: rgba(99, 102, 241, 0.1); }
.nav-item-orange:hover { border-color: rgba(251, 146, 60, 0.6); background: rgba(251, 146, 60, 0.1); }
.nav-item-cyan:hover { border-color: rgba(6, 182, 212, 0.6); background: rgba(6, 182, 212, 0.1); }
.nav-item-slate:hover { border-color: rgba(100, 116, 139, 0.6); background: rgba(100, 116, 139, 0.1); }
.nav-item-emerald:hover { border-color: rgba(16, 185, 129, 0.6); background: rgba(16, 185, 129, 0.1); }
.nav-item-amber:hover { border-color: rgba(245, 158, 11, 0.6); background: rgba(245, 158, 11, 0.1); }
.nav-item-violet:hover { border-color: rgba(139, 92, 246, 0.6); background: rgba(139, 92, 246, 0.1); }
.nav-item-teal:hover { border-color: rgba(20, 184, 166, 0.6); background: rgba(20, 184, 166, 0.1); }
</style>
