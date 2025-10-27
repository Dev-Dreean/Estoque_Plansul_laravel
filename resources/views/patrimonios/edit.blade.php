<x-app-layout>
    <x-slot name="header">
        <div style="height:0.8em;line-height:0.8em;padding:0;margin:0;overflow:hidden;background:inherit;">
            <h2 style="font-size:0.95em;font-weight:600;color:#fff;margin:0;padding:0;line-height:0.8em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                {{ __('Editar Patrimônio') }}: <span style="font-weight:400;">{{ $patrimonio->DEPATRIMONIO }}</span>
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="w-full sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route('patrimonios.update', $patrimonio) }}" id="editPatrimonioForm">
                        @csrf
                        @method('PUT')

                        <x-patrimonio-form :patrimonio="$patrimonio" />

                        <div class="flex items-center justify-start mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                            <a href="{{ route('patrimonios.index') }}" class="mr-4">Cancelar</a>
                            <button type="button" id="btnAtualizar" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">Atualizar Patrimônio</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL DE CONFIRMAÇÃO --}}
    <div id="modalConfirmacao" x-data="{ show: false }" x-show="show" x-transition class="fixed inset-0 bg-black/50 dark:bg-black/70 z-50 flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg w-full max-w-md" @click.stop>
            {{-- Header --}}
            <div class="px-6 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Confirmar Alterações</h3>
            </div>

            {{-- Conteúdo --}}
            <div id="alteracoes" class="px-6 py-4 max-h-80 overflow-y-auto">
                <!-- Alterações serão inseridas aqui dinamicamente -->
            </div>

            {{-- Footer --}}
            <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700 flex gap-3 justify-end">
                <button type="button" id="btnCancelarModal" class="px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition">
                    Cancelar
                </button>
                <button type="button" id="btnConfirmarAtualizacao" class="px-3 py-1.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800 rounded transition">
                    Confirmar
                </button>
            </div>
        </div>
    </div>

    {{-- Dados originais como script com JSON puro --}}
</x-app-layout>

@php
// Carregar o nome do projeto
$nomeProjetoOriginal = '';
if ($patrimonio->CDPROJETO) {
try {
$projeto = App\Models\Tabfant::where('CDPROJETO', $patrimonio->CDPROJETO)->first();
// Tentar NMFANTASIA primeiro, depois NOMEPROJETO
$nomeProjetoOriginal = $projeto?->NMFANTASIA ?? $projeto?->NOMEPROJETO ?? '';
} catch (\Exception $e) {
$nomeProjetoOriginal = '';
}
}

// Carregar o nome do local
$nomeLocalOriginal = '';
if ($patrimonio->CDLOCAL) {
try {
// Usar a query com lowercase 'cdlocal' pois a tabela usa lowercase
$local = App\Models\LocalProjeto::where('id', $patrimonio->CDLOCAL)->first();
if (!$local) {
// Tentar também com CDLOCAL em uppercase
$local = App\Models\LocalProjeto::where('cdlocal', $patrimonio->CDLOCAL)->first();
}
$nomeLocalOriginal = $local?->delocal ?? $local?->DELOCAL ?? '';
} catch (\Exception $e) {
$nomeLocalOriginal = '';
}
}

// Carregar o nome do funcionário (responsável)
$nomeFuncionarioOriginal = '';
if ($patrimonio->CDMATRFUNCIONARIO) {
try {
$funcionario = App\Models\Funcionario::where('CDMATRFUNCIONARIO', $patrimonio->CDMATRFUNCIONARIO)->first();
$nomeFuncionarioOriginal = $funcionario?->NMFUNCIONARIO ?? '';
// Truncar a 25 caracteres se for muito grande
if (strlen($nomeFuncionarioOriginal) > 25) {
$nomeFuncionarioOriginal = substr($nomeFuncionarioOriginal, 0, 25) . '...';
}
} catch (\Exception $e) {
$nomeFuncionarioOriginal = '';
}
}

$dadosOriginais = [
'NUPATRIMONIO' => $patrimonio->NUPATRIMONIO ?? '',
'NUSEQOBJ' => $patrimonio->CODOBJETO ?? '',
'DEPATRIMONIO' => $patrimonio->DEPATRIMONIO ?? '',
'CDPROJETO' => $patrimonio->CDPROJETO ?? '',
'NMPROJETOORIGINAL' => $nomeProjetoOriginal,
'CDLOCAL' => $patrimonio->CDLOCAL ?? '',
'DENOMELOCAL' => $nomeLocalOriginal,
'CDMATRFUNCIONARIO' => $patrimonio->CDMATRFUNCIONARIO ?? '',
'NOMEFUNCIONARIOORIGINAL' => $nomeFuncionarioOriginal,
'SITUACAO' => $patrimonio->SITUACAO ?? '',
'MARCA' => $patrimonio->MARCA ?? '',
'MODELO' => $patrimonio->MODELO ?? '',
'DTAQUISICAO' => $patrimonio->DTAQUISICAO ?? '',
'DTBAIXA' => $patrimonio->DTBAIXA ?? '',
'DEHISTORICO' => $patrimonio->DEHISTORICO ?? '',
'NUMOF' => $patrimonio->NUMOF ?? '',
'NMPLANTA' => $patrimonio->NMPLANTA ?? '',
];
$jsonDados = json_encode($dadosOriginais, JSON_UNESCAPED_SLASHES);
@endphp

<script>
    // Dados originais - injetar JSON diretamente no objeto global
    let jsonData = '{!! addslashes(json_encode($dadosOriginais, JSON_UNESCAPED_SLASHES)) !!}';
    window.dadosOriginais = JSON.parse(jsonData);
    console.log('✅ Dados originais carregados com sucesso:', window.dadosOriginais);

    document.addEventListener('DOMContentLoaded', function() {
        // Referência local para os dados
        const dadosOriginais = window.dadosOriginais;
        const btnAtualizar = document.getElementById('btnAtualizar');
        const btnCancelarModal = document.getElementById('btnCancelarModal');
        const btnConfirmarAtualizacao = document.getElementById('btnConfirmarAtualizacao');
        const modalConfirmacao = document.getElementById('modalConfirmacao');
        const alteracoesDiv = document.getElementById('alteracoes');

        // Mapas de tradução para campos
        const labelCampos = {
            'NUPATRIMONIO': 'Número do Patrimônio',
            'NUSEQOBJ': 'Código do Objeto',
            'DEPATRIMONIO': 'Descrição do Objeto',
            'CDPROJETO': 'Projeto',
            'CDLOCAL': 'Local',
            'CDMATRFUNCIONARIO': 'Matrícula Responsável',
            'SITUACAO': 'Situação',
            'MARCA': 'Marca',
            'MODELO': 'Modelo',
            'DTAQUISICAO': 'Data Aquisição',
            'DTBAIXA': 'Data Baixa',
            'DEHISTORICO': 'Observações',
            'NUMOF': 'Número OC',
            'NMPLANTA': 'Planta'
        };

        // Cache de nomes de projetos, locais e funcionários
        const projetosCache = {};
        const locaisCache = {};
        const funcionariosCache = {};

        // Inicializar com o projeto original
        const cdProjetoOriginal = dadosOriginais.CDPROJETO;
        const nomeProjetoOriginal = dadosOriginais.NMPROJETOORIGINAL;
        if (cdProjetoOriginal) {
            projetosCache[cdProjetoOriginal] = nomeProjetoOriginal;
        }

        // Inicializar com o local original
        const cdLocalOriginal = dadosOriginais.CDLOCAL;
        const nomeLocalOriginal = dadosOriginais.DENOMELOCAL;
        if (cdLocalOriginal) {
            locaisCache[cdLocalOriginal] = nomeLocalOriginal;
        }

        // Inicializar com o funcionário original
        const cdFuncionarioOriginal = dadosOriginais.CDMATRFUNCIONARIO;
        const nomeFuncionarioOriginal = dadosOriginais.NOMEFUNCIONARIOORIGINAL;
        if (cdFuncionarioOriginal) {
            funcionariosCache[cdFuncionarioOriginal] = nomeFuncionarioOriginal;
        }

        // Buscar nome do projeto via AJAX
        async function obterNomeProjeto(cdProjeto) {
            if (!cdProjeto) return '';

            // Verificar cache primeiro
            if (projetosCache[cdProjeto]) {
                return projetosCache[cdProjeto];
            }

            try {
                const response = await fetch('/api/projetos/buscar/' + encodeURIComponent(cdProjeto), {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    console.warn('API de projetos retornou erro:', response.status);
                    return '';
                }

                const data = await response.json();
                const nome = (data && (data.NMFANTASIA || data.NOMEPROJETO)) ? (data.NMFANTASIA || data.NOMEPROJETO) : '';

                // Guardar no cache mesmo que vazio
                projetosCache[cdProjeto] = nome;
                console.log(`📌 Projeto ${cdProjeto}: "${nome}"`);
                return nome;
            } catch (error) {
                console.error('Erro ao buscar projeto:', error);
                projetosCache[cdProjeto] = '';
                return '';
            }
        }

        // Buscar nome do local via AJAX
        async function obterNomeLocal(cdLocal) {
            if (!cdLocal) return '';

            // Verificar cache primeiro
            if (locaisCache[cdLocal]) {
                return locaisCache[cdLocal];
            }

            try {
                const response = await fetch('/api/locais/' + encodeURIComponent(cdLocal), {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    console.warn('API de locais retornou erro:', response.status);
                    return '';
                }

                const data = await response.json();
                const nome = data && (data.delocal || data.LOCAL) ? (data.delocal || data.LOCAL) : '';

                // Guardar no cache mesmo que vazio
                locaisCache[cdLocal] = nome;
                console.log(`📌 Local ${cdLocal}: "${nome}"`);
                return nome;
            } catch (error) {
                console.error('Erro ao buscar local:', error);
                locaisCache[cdLocal] = '';
                return '';
            }
        }

        // Buscar nome do funcionário via AJAX
        async function obterNomeFuncionario(cdMatricula) {
            if (!cdMatricula) return '';

            // Verificar cache primeiro
            if (funcionariosCache[cdMatricula]) {
                return funcionariosCache[cdMatricula];
            }

            try {
                const response = await fetch('/api/funcionarios/pesquisar?q=' + encodeURIComponent(cdMatricula), {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    console.warn('API retornou erro:', response.status);
                    return '';
                }

                const data = await response.json();
                let nome = '';

                if (Array.isArray(data) && data.length > 0) {
                    // A API retorna NMFUNCIONARIO (conforme FuncionarioController)
                    nome = data[0].NMFUNCIONARIO || data[0].NOMEABREVIADO || data[0].NOMEFUNCIONARIO || '';
                } else if (data && typeof data === 'object') {
                    nome = data.NMFUNCIONARIO || data.NOMEABREVIADO || data.NOMEFUNCIONARIO || '';
                }

                // Truncar a 25 caracteres se necessário
                if (nome && nome.length > 25) {
                    nome = nome.substring(0, 25) + '...';
                }

                // Guardar no cache mesmo que vazio
                funcionariosCache[cdMatricula] = nome;
                console.log(`📌 Funcionário ${cdMatricula}: "${nome}"`);
                return nome;
            } catch (error) {
                console.error('Erro ao buscar funcionário:', error);
                // Retornar vazio mas marcar no cache para não tentar de novo
                funcionariosCache[cdMatricula] = '';
                return '';
            }
        }

        // Capturar dados do formulário - usando Alpine.js formData diretamente
        function capturarDadosFormulario() {
            const formElement = document.getElementById('editPatrimonioForm').querySelector('[x-data*="patrimonioForm"]');

            if (!formElement) {
                console.error('Elemento com Alpine.js não encontrado');
                return {};
            }

            // Acessa os dados do Alpine
            const alpineData = formElement._x_dataStack?.[0] || formElement.__x_dataStack?.[0];

            if (!alpineData || !alpineData.formData) {
                console.error('formData do Alpine não encontrado');
                return {};
            }

            const dados = {};
            const camposValidos = Object.keys(labelCampos);

            camposValidos.forEach(campo => {
                if (campo in alpineData.formData) {
                    dados[campo] = alpineData.formData[campo];
                }
            });

            console.log('✅ Dados capturados do Alpine:', dados);
            return dados;
        }

        // Comparar dados e gerar HTML das alterações (ASSÍNCRONA)
        async function gerarAlteracoes(novos) {
            let html = '';
            let temAlteracao = false;
            const alteracoes = [];

            // Processar cada campo
            for (const campo of Object.keys(labelCampos)) {
                let valorAnterior = dadosOriginais[campo];
                let valorNovo = novos[campo];

                // Normalizar datas ISO para YYYY-MM-DD
                if (campo.includes('DTA') || campo.includes('DT')) {
                    if (valorAnterior && typeof valorAnterior === 'string' && valorAnterior.includes('T')) {
                        valorAnterior = valorAnterior.split('T')[0];
                    }
                    if (valorNovo && typeof valorNovo === 'string' && valorNovo.includes('T')) {
                        valorNovo = valorNovo.split('T')[0];
                    }
                }

                valorAnterior = valorAnterior == null ? '' : String(valorAnterior).trim();
                valorNovo = valorNovo == null ? '' : String(valorNovo).trim();

                if (valorAnterior !== valorNovo) {
                    temAlteracao = true;

                    // Tratamento especial para CDPROJETO - mostrar com nome bem destacado
                    if (campo === 'CDPROJETO') {
                        const nomeAnterior = projetosCache[valorAnterior] || '';
                        const nomeNovo = await obterNomeProjeto(valorNovo);

                        const displayAnterior = valorAnterior ? `<span class="font-semibold text-gray-900 dark:text-white">${nomeAnterior}</span><br><span class="text-xs text-gray-500 dark:text-gray-400">(${valorAnterior})</span>` : '—';
                        const displayNovo = valorNovo ? `<span class="font-semibold text-gray-900 dark:text-white">${nomeNovo}</span><br><span class="text-xs text-gray-500 dark:text-gray-400">(${valorNovo})</span>` : '—';

                        html += `
                        <div class="mb-3 pb-3 border-b border-gray-200 dark:border-gray-700 last:border-b-0 last:mb-0 last:pb-0">
                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">${labelCampos[campo]}</p>
                            <div class="flex items-start gap-3 text-sm">
                                <span class="text-gray-700 dark:text-gray-300 flex-1 break-words">${displayAnterior}</span>
                                <span class="flex-shrink-0 text-blue-500 dark:text-blue-400 font-bold text-lg mt-1">→</span>
                                <span class="text-gray-800 dark:text-gray-100 font-medium flex-1 break-words">${displayNovo}</span>
                            </div>
                        </div>
                    `;
                    } else if (campo === 'CDLOCAL') {
                        // Tratamento especial para CDLOCAL - mostrar com nome bem destacado
                        const nomeAnterior = locaisCache[valorAnterior] || '';
                        const nomeNovo = await obterNomeLocal(valorNovo);

                        const displayAnterior = valorAnterior ? `<span class="font-semibold text-gray-900 dark:text-white">${nomeAnterior}</span><br><span class="text-xs text-gray-500 dark:text-gray-400">(${valorAnterior})</span>` : '—';
                        const displayNovo = valorNovo ? `<span class="font-semibold text-gray-900 dark:text-white">${nomeNovo}</span><br><span class="text-xs text-gray-500 dark:text-gray-400">(${valorNovo})</span>` : '—';

                        html += `
                        <div class="mb-3 pb-3 border-b border-gray-200 dark:border-gray-700 last:border-b-0 last:mb-0 last:pb-0">
                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">${labelCampos[campo]}</p>
                            <div class="flex items-start gap-3 text-sm">
                                <span class="text-gray-700 dark:text-gray-300 flex-1 break-words">${displayAnterior}</span>
                                <span class="flex-shrink-0 text-blue-500 dark:text-blue-400 font-bold text-lg mt-1">→</span>
                                <span class="text-gray-800 dark:text-gray-100 font-medium flex-1 break-words">${displayNovo}</span>
                            </div>
                        </div>
                    `;
                    } else if (campo === 'CDMATRFUNCIONARIO') {
                        // Tratamento especial para CDMATRFUNCIONARIO - mostrar com nome bem destacado
                        const nomeAnterior = funcionariosCache[valorAnterior] || '';
                        const nomeNovo = await obterNomeFuncionario(valorNovo);

                        const displayAnterior = valorAnterior ? `<span class="font-semibold text-gray-900 dark:text-white">${nomeAnterior}</span><br><span class="text-xs text-gray-500 dark:text-gray-400">(${valorAnterior})</span>` : '—';
                        const displayNovo = valorNovo ? `<span class="font-semibold text-gray-900 dark:text-white">${nomeNovo}</span><br><span class="text-xs text-gray-500 dark:text-gray-400">(${valorNovo})</span>` : '—';

                        html += `
                        <div class="mb-3 pb-3 border-b border-gray-200 dark:border-gray-700 last:border-b-0 last:mb-0 last:pb-0">
                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">${labelCampos[campo]}</p>
                            <div class="flex items-start gap-3 text-sm">
                                <span class="text-gray-700 dark:text-gray-300 flex-1 break-words">${displayAnterior}</span>
                                <span class="flex-shrink-0 text-blue-500 dark:text-blue-400 font-bold text-lg mt-1">→</span>
                                <span class="text-gray-800 dark:text-gray-100 font-medium flex-1 break-words">${displayNovo}</span>
                            </div>
                        </div>
                    `;
                    } else {
                        html += `
                        <div class="mb-3 pb-3 border-b border-gray-200 dark:border-gray-700 last:border-b-0 last:mb-0 last:pb-0">
                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">${labelCampos[campo]}</p>
                            <div class="flex items-center gap-3 text-sm">
                                <span class="text-gray-700 dark:text-gray-300 flex-1 break-words">${valorAnterior || '—'}</span>
                                <span class="flex-shrink-0 text-blue-500 dark:text-blue-400 font-bold text-lg">→</span>
                                <span class="text-gray-800 dark:text-gray-100 font-medium flex-1 break-words">${valorNovo || '—'}</span>
                            </div>
                        </div>
                    `;
                    }
                }
            }

            if (!temAlteracao) {
                html = `<div class="text-center py-8">
                <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma alteração detectada</p>
            </div>`;
            }

            return html;
        }

        // Mostrar modal
        async function mostrarModal() {
            const dadosNovos = capturarDadosFormulario();

            // DEBUG: Ver o que está sendo capturado
            console.log('=== DEBUG MODAL ===');
            console.log('dadosOriginais:', dadosOriginais);
            console.log('dadosNovos capturados:', dadosNovos);

            const html = await gerarAlteracoes(dadosNovos);
            alteracoesDiv.innerHTML = html;
            modalConfirmacao.style.display = 'flex';
        }

        // Fechar modal
        function fecharModal() {
            modalConfirmacao.style.display = 'none';
        }

        // Eventos
        btnAtualizar.addEventListener('click', mostrarModal);
        btnCancelarModal.addEventListener('click', fecharModal);

        btnConfirmarAtualizacao.addEventListener('click', function() {
            console.log('🔷 Submetendo formulário...');

            // Validar se todos os campos obrigatórios estão preenchidos
            const formElement = document.getElementById('editPatrimonioForm');
            const alpineDiv = formElement.querySelector('[x-data*="patrimonioForm"]');

            if (!alpineDiv) {
                alert('❌ Erro: Elemento do formulário não encontrado');
                return;
            }

            const alpineData = alpineDiv._x_dataStack?.[0] || alpineDiv.__x_dataStack?.[0];
            if (!alpineData || !alpineData.formData) {
                alert('❌ Erro: Dados do formulário não acessíveis');
                return;
            }

            // Verificar campos obrigatórios
            const erros = [];
            if (!alpineData.formData.NUPATRIMONIO) erros.push('Número do Patrimônio é obrigatório');
            if (!alpineData.formData.NUSEQOBJ) erros.push('Código do Objeto é obrigatório');
            if (!alpineData.formData.CDMATRFUNCIONARIO) erros.push('Matrícula do Responsável é obrigatória');
            if (!alpineData.formData.SITUACAO) erros.push('Situação é obrigatória');

            if (erros.length > 0) {
                alert('❌ Erros de validação:\n\n' + erros.join('\n'));
                return;
            }

            fecharModal();
            formElement.submit();
        });

        // Fechar modal ao clicar fora
        modalConfirmacao.addEventListener('click', function(e) {
            if (e.target === modalConfirmacao) {
                fecharModal();
            }
        });

        // Suporte para tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modalConfirmacao.style.display === 'flex') {
                fecharModal();
            }
        });
    });

    // Capturar o submit do formulário
    document.getElementById('editPatrimonioForm')?.addEventListener('submit', function(e) {
        const formData = new FormData(this);
        const values = {};
        for (let [key, value] of formData.entries()) {
            values[key] = value;
        }
        console.log('\n' + '='.repeat(80));
        console.log('📤 [SUBMIT] Formulário de Edição Enviado');
        console.log('='.repeat(80));
        console.log('Valores enviados:', JSON.stringify(values, null, 2));

        // Tentar acessar o formData do Alpine
        const formElement = this.querySelector('[x-data*="patrimonioForm"]');
        if (formElement && formElement.__x_dataStack && formElement.__x_dataStack.length > 0) {
            const alpineData = formElement.__x_dataStack[0];
            console.log('\n📌 formData do Alpine:', JSON.stringify(alpineData.formData, null, 2));
        }
        console.log('='.repeat(80) + '\n');
    });

    // Detectar erros de validação (Laravel envia redirect com erros)
    window.addEventListener('load', function() {
        // Se houver erros salvos em localStorage ou sessão, mostrar
        const urlParams = new URLSearchParams(window.location.search);
        if (document.querySelector('[data-errors]')) {
            console.error('❌ Erros de validação detectados na página');
        }
    });
</script>