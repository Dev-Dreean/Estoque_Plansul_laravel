{{-- 
    Exemplo de implementação do sistema de navegação dinâmica
    Este arquivo demonstra como integrar o sistema de acessos na view
--}}

@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        {{-- Header --}}
        <div class="mb-8 text-center">
            <h1 class="text-4xl font-bold text-white mb-2">
                {{ $greeting }}, {{ Auth::user()->NOMEUSER ?? 'Usuário' }}!
            </h1>
            <p class="text-gray-400">
                Bem-vindo ao sistema Plansul - {{ $location }}
            </p>
        </div>

        {{-- Informações de Perfil --}}
        <div class="bg-gradient-to-r from-blue-900/50 to-purple-900/50 backdrop-blur-sm rounded-xl p-6 mb-8 border border-white/10">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-orange-500 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-white text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-white">{{ Auth::user()->NOMEUSER }}</h3>
                        <p class="text-gray-400 text-sm">{{ $perfilDescricao }}</p>
                        <p class="text-orange-400 text-sm font-medium mt-1">
                            <i class="fas fa-shield-alt mr-1"></i>
                            Perfil: 
                            @if(Auth::user()->isGod())
                                <span class="text-blue-400">ADMINISTRADOR</span>
                            @elseif(Auth::user()->isAdmin())
                                <span class="text-yellow-400">ADMINISTRADOR</span>
                            @else
                                <span class="text-blue-400">USUÁRIO</span>
                            @endif
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-gray-400 text-sm">Acessos disponíveis</p>
                    <p class="text-3xl font-bold text-orange-400">{{ count($telasComAcesso) }}</p>
                </div>
            </div>
        </div>

        {{-- Menu de Navegação Dinâmico --}}
        <div class="bg-gradient-to-br from-slate-900/50 to-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-white/10">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-white">
                    <i class="fas fa-th-large mr-2 text-orange-400"></i>
                    Suas Telas
                </h2>
                @if(Auth::user()->isAdmin() || Auth::user()->isGod())
                    <a href="{{ route('usuarios.index') }}" 
                       class="text-orange-400 hover:text-orange-300 transition-colors text-sm font-medium">
                        <i class="fas fa-users-cog mr-1"></i>
                        Gerenciar Acessos
                    </a>
                @endif
            </div>

            {{-- Component de navegação dinâmica --}}
            @if(count($telasComAcesso) > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($telasMenu as $nuseqtela => $tela)
                        @php
                            $nome = $tela['nome'] ?? 'Sem nome';
                            $descricao = $tela['descricao'] ?? '';
                            $route = $tela['route'] ?? null;
                            $icone = $tela['icone'] ?? 'fa-window';
                            $cor = $tela['cor'] ?? 'blue';
                            $rotaValida = $route && App\Helpers\MenuHelper::rotaExiste($route);
                        @endphp

                        @if($rotaValida)
                            <a href="{{ route($route) }}" 
                               class="group bg-gradient-to-br from-{{ $cor }}-900/30 to-{{ $cor }}-800/20 
                                      hover:from-{{ $cor }}-800/40 hover:to-{{ $cor }}-700/30 
                                      border border-{{ $cor }}-500/20 hover:border-{{ $cor }}-400/50
                                      rounded-xl p-6 transition-all duration-300 hover:scale-105 hover:shadow-xl">
                                <div class="flex items-start gap-4">
                                    <div class="w-14 h-14 bg-gradient-to-br from-{{ $cor }}-500 to-{{ $cor }}-600 
                                                rounded-lg flex items-center justify-center 
                                                group-hover:scale-110 transition-transform">
                                        <i class="fas {{ $icone }} text-white text-2xl"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-white mb-1 group-hover:text-{{ $cor }}-300 transition-colors">
                                            {{ $nome }}
                                        </h3>
                                        <p class="text-sm text-gray-400 line-clamp-2">
                                            {{ $descricao }}
                                        </p>
                                    </div>
                                    <i class="fas fa-arrow-right text-{{ $cor }}-400 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                                </div>
                            </a>
                        @else
                            <div class="bg-gradient-to-br from-gray-900/30 to-gray-800/20 
                                        border border-gray-500/10 rounded-xl p-6 opacity-50 cursor-not-allowed">
                                <div class="flex items-start gap-4">
                                    <div class="w-14 h-14 bg-gray-700 rounded-lg flex items-center justify-center">
                                        <i class="fas {{ $icone }} text-gray-500 text-2xl"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-gray-500 mb-1">
                                            {{ $nome }}
                                        </h3>
                                        <p class="text-sm text-gray-600 line-clamp-2">
                                            Em desenvolvimento
                                        </p>
                                    </div>
                                    <i class="fas fa-lock text-gray-600"></i>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <div class="w-24 h-24 bg-orange-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-lock text-orange-400 text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">Nenhum Acesso Disponível</h3>
                    <p class="text-gray-400 mb-6">Você não tem permissão para acessar nenhuma tela no momento.</p>
                    <p class="text-sm text-gray-500">
                        Entre em contato com um administrador para solicitar permissões de acesso.
                    </p>
                </div>
            @endif
        </div>

        {{-- Informações Adicionais --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
            {{-- Card de Clima --}}
            <div class="bg-gradient-to-br from-cyan-900/50 to-blue-900/50 backdrop-blur-sm rounded-xl p-6 border border-white/10">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Clima Local</p>
                        <p class="text-3xl font-bold text-white" id="weatherInfo">--°C</p>
                    </div>
                    <div class="w-16 h-16 bg-cyan-500/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-cloud-sun text-cyan-400 text-2xl"></i>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-white/10">
                    <p class="text-gray-400 text-sm">
                        <i class="fas fa-map-marker-alt mr-2"></i>
                        {{ $location }}
                    </p>
                </div>
            </div>

            {{-- Card de Ações Rápidas --}}
            <div class="bg-gradient-to-br from-purple-900/50 to-pink-900/50 backdrop-blur-sm rounded-xl p-6 border border-white/10">
                <h3 class="text-lg font-semibold text-white mb-4">
                    <i class="fas fa-bolt mr-2 text-yellow-400"></i>
                    Ações Rápidas
                </h3>
                <div class="space-y-3">
                    @if(App\Helpers\MenuHelper::temAcessoTela('1000'))
                        <a href="{{ route('patrimonios.index') }}" 
                           class="flex items-center gap-3 text-gray-300 hover:text-white transition-colors">
                            <i class="fas fa-cube text-blue-400"></i>
                            <span>Ver Patrimônios</span>
                        </a>
                    @endif
                    @if(App\Helpers\MenuHelper::temAcessoTela('1001'))
                        <a href="{{ route('dashboard') }}" 
                           class="flex items-center gap-3 text-gray-300 hover:text-white transition-colors">
                            <i class="fas fa-chart-line text-indigo-400"></i>
                            <span>Dashboard</span>
                        </a>
                    @endif
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" 
                                class="flex items-center gap-3 text-gray-300 hover:text-red-400 transition-colors">
                            <i class="fas fa-sign-out-alt text-red-400"></i>
                            <span>Sair da Conta</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Carregar clima
async function loadWeather() {
    try {
        const response = await fetch('/api/weather');
        if (response.ok) {
            const data = await response.json();
            if (data.success) {
                document.getElementById('weatherInfo').textContent = `${data.temp}°C`;
            }
        }
    } catch (error) {
        console.warn('Erro ao carregar clima:', error);
    }
}

loadWeather();
</script>
@endsection
