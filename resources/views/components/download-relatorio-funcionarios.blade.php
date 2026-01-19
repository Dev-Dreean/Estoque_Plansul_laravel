{{-- 
  Componente de Download de Relatório de Funcionários
  
  Uso:
  <x-download-relatorio-funcionarios />
  
  Características:
  • Download instantâneo (arquivo em cache)
  • Se arquivo não existir, gera sob demanda
  • Mostra data do último relatório gerado
--}}

<div class="mt-6 p-4 bg-blue-50 dark:bg-gray-800 border border-blue-200 dark:border-blue-700 rounded-lg">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">📊 Relatório de Funcionários</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                92.755 funcionários com dados completos
            </p>
            <p id="relatorio-info" class="text-xs text-gray-500 dark:text-gray-500 mt-2">
                ⏳ Carregando informações...
            </p>
        </div>

        <button
            onclick="baixarRelatorio()"
            id="btn-download-relatorio"
            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-700 text-white font-semibold rounded-lg transition"
        >
            📥 Download
        </button>
    </div>
</div>

<script>
async function carregarInfoRelatorio() {
    try {
        const response = await fetch('/dashboard/relatorio/funcionarios/cache');
        const relatorios = await response.json();

        const info = document.getElementById('relatorio-info');
        
        if (relatorios.length > 0) {
            const ultimo = relatorios[0];
            info.innerHTML = `✅ Relatório em cache (${ultimo.tamanho})<br>📅 Gerado em ${ultimo.data}`;
        } else {
            info.innerHTML = '⚠️ Nenhum relatório em cache - será gerado sob demanda';
        }
    } catch (error) {
        console.error('Erro ao carregar info:', error);
        document.getElementById('relatorio-info').innerHTML = '❌ Erro ao carregar informações';
    }
}

async function baixarRelatorio() {
    const btn = document.getElementById('btn-download-relatorio');
    const textoOriginal = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '⏳ Processando...';
    
    try {
        // Fazer GET request para download
        const response = await fetch('/dashboard/relatorio/funcionarios/download');
        
        if (!response.ok) {
            throw new Error('Erro ao baixar: ' + response.status);
        }

        // Converter response para blob
        const blob = await response.blob();
        
        // Criar URL temporária e fazer download
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'relatorio_funcionarios_' + new Date().toLocaleDateString('pt-BR').replace(/\//g, '-') + '.csv';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        a.remove();

        // Sucesso
        btn.innerHTML = '✅ Download concluído!';
        setTimeout(() => {
            btn.innerHTML = textoOriginal;
            btn.disabled = false;
        }, 2000);

        // Recarregar info
        carregarInfoRelatorio();

    } catch (error) {
        console.error('Erro:', error);
        btn.innerHTML = '❌ Erro ao baixar';
        setTimeout(() => {
            btn.innerHTML = textoOriginal;
            btn.disabled = false;
        }, 2000);
    }
}

// Carregar informações ao carregar página
document.addEventListener('DOMContentLoaded', carregarInfoRelatorio);

// Recarregar a cada 60 segundos
setInterval(carregarInfoRelatorio, 60000);
</script>
