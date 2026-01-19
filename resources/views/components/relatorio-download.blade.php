@props(['class' => ''])

<div {{ $attributes->merge(['class' => $class]) }}>
    <!-- Botão Download -->
    <button @click="iniciarDownload()" 
            :disabled="carregando"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
        <span x-show="!carregando" class="text-lg">📥</span>
        <span x-show="carregando" class="text-lg animate-spin">⏳</span>
        <span x-text="carregando ? 'Gerando...' : 'Baixar Relatório'"></span>
    </button>

    <!-- MODAL OVERLAY COM BARRA DE PROGRESSO -->
    <div x-show="carregando" 
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 dark:bg-black/70"
         style="display: none;">
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl p-8 w-96">
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="inline-block text-4xl mb-3 animate-spin">⚙️</div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Gerando Relatório</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Por favor, aguarde...</p>
            </div>

            <!-- Barra de Progresso -->
            <div class="space-y-3">
                <!-- Barra visual -->
                <div class="w-full h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-blue-500 to-blue-600 transition-all duration-300"
                         :style="`width: ${progresso}%`"></div>
                </div>

                <!-- Percentual + Info -->
                <div class="flex justify-between items-center">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        <span x-text="progresso"></span>%
                    </span>
                    <span class="text-xs text-gray-600 dark:text-gray-400" x-text="statusMsg"></span>
                </div>

                <!-- Contador de Registros (quando disponível) -->
                <div x-show="registrosProcessados > 0" class="text-center">
                    <p class="text-xs text-gray-600 dark:text-gray-400">
                        <span x-text="registrosProcessados.toLocaleString('pt-BR')"></span> registros processados
                    </p>
                </div>
            </div>

            <!-- Dicas durante carregamento -->
            <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-600 dark:text-gray-400 text-center">
                    💡 Não feche a aba durante o download
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function relatorioDownload() {
    return {
        carregando: false,
        progresso: 0,
        statusMsg: 'Iniciando...',
        registrosProcessados: 0,
        intervalId: null,

        async iniciarDownload() {
            this.carregando = true;
            this.progresso = 0;
            this.statusMsg = 'Conectando ao servidor...';
            this.registrosProcessados = 0;

            try {
                // Simular progresso enquanto espera resposta
                this.simularProgresso();

                const response = await fetch('{{ route("relatorio.funcionarios.download") }}');
                
                if (!response.ok) {
                    throw new Error(`Erro HTTP: ${response.status}`);
                }

                // Parar simulação e ir para 90%
                clearInterval(this.intervalId);
                this.progresso = 90;
                this.statusMsg = 'Finalizando download...';

                // Processar resposta como blob
                const blob = await response.blob();
                this.progresso = 95;

                // Criar download
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = 'relatorio_funcionarios.csv';
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.URL.revokeObjectURL(url);

                // Completado
                this.progresso = 100;
                this.statusMsg = '✅ Download concluído!';
                
                // Fechar modal após 1 segundo
                setTimeout(() => {
                    this.carregando = false;
                }, 1000);

            } catch (error) {
                console.error('Erro no download:', error);
                this.statusMsg = '❌ Erro ao gerar!';
                this.progresso = 0;
                
                clearInterval(this.intervalId);
                
                setTimeout(() => {
                    this.carregando = false;
                }, 2000);
            }
        },

        simularProgresso() {
            let velocidade = 0.5;
            let ultimoProgresso = 0;

            this.intervalId = setInterval(() => {
                // Progresso não-linear (começa rápido, depois desacelera)
                if (this.progresso < 20) {
                    velocidade = 1.5;
                } else if (this.progresso < 50) {
                    velocidade = 0.8;
                } else {
                    velocidade = 0.3;
                }

                this.progresso = Math.min(this.progresso + velocidade, 88);
                
                // Variar mensagens
                const msgs = [
                    'Conectando ao servidor...',
                    'Consultando banco de dados...',
                    'Gerando arquivo CSV...',
                    'Processando registros...',
                    'Quase lá...'
                ];
                
                const idx = Math.floor(this.progresso / 20);
                this.statusMsg = msgs[Math.min(idx, msgs.length - 1)];
                
                // Simular contagem de registros
                if (this.progresso > 30) {
                    this.registrosProcessados = Math.min(
                        Math.floor((this.progresso / 88) * 92755),
                        92755
                    );
                }
            }, 300);
        }
    };
}
</script>
