<script>
  function initPatrimonioEditForm(rootElement) {
    const root = rootElement || document;
    const formElement = root.querySelector('#editPatrimonioForm');
    if (!formElement) {
      return;
    }

    if (window.__patrimônioEditController) {
      window.__patrimônioEditController.abort();
    }

    const controller = new AbortController();
    window.__patrimônioEditController = controller;
    const { signal } = controller;

    let dadosOriginais = {};
    try {
      dadosOriginais = JSON.parse(formElement.dataset.dadosOriginais || '{}');
    } catch (e) {
      dadosOriginais = {};
    }

    window.dadosOriginais = dadosOriginais;
    console.log('📋 Dados originais carregados com sucesso:', window.dadosOriginais);

    const btnAtualizar = root.querySelector('#btnAtualizar');
    const btnCancelarModal = root.querySelector('#btnCancelarModal');
    const btnConfirmarAtualizacao = root.querySelector('#btnConfirmarAtualizacao');
    const modalConfirmacao = root.querySelector('#modalConfirmacao');
    const alteracoesDiv = root.querySelector('#alteracoes');

    if (!btnAtualizar || !btnCancelarModal || !btnConfirmarAtualizacao || !modalConfirmacao || !alteracoesDiv) {
      return;
    }
    // Mapas de traducao para campos
    const labelCampos = {
      'NUPATRIMONIO': 'Número do Patrimônio',
      'NUSEQOBJ': 'Código do Objeto',
      'DEPATRIMONIO': 'Descrição do Objeto',
      'CDPROJETO': 'Projeto',
      'CDLOCAL': 'Local Físico',
      'CDMATRFUNCIONARIO': 'Matrícula Responsável',
      'SITUACAO': 'Situação',
      'FLCONFERIDO': 'Conferido',
      'MARCA': 'Marca',
      'MODELO': 'Modelo',
      'DTAQUISICAO': 'Data Aquisição',
      'DTBAIXA': 'Data Baixa',
      'DEHISTORICO': 'Observações',
      'NUMOF': 'Número OC',
      'NMPLANTA': 'Planta',
      'PESO': 'Peso',
      'TAMANHO': 'Dimensões'
    };

    // Cache de nomes de projetos, locais e funcionÇ­rios
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

    // Inicializar com o funcionÇ­rio original
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
        console.log(`ÐY"O Projeto ${cdProjeto}: "${nome}"`);
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

      const cdProjetoAtual = (root.querySelector('[name="CDPROJETO"]')?.value || dadosOriginais.CDPROJETO || '').toString().trim();
      const cacheKey = `${cdProjetoAtual}::${cdLocal}`;

      // Verificar cache primeiro
      if (locaisCache[cacheKey]) {
        return locaisCache[cacheKey];
      }

      // Sem projeto, não conseguimos garantir o escopo correto
      if (!cdProjetoAtual) {
        locaisCache[cacheKey] = '';
        return '';
      }

      try {
        const url = `/api/locais/buscar?cdprojeto=${encodeURIComponent(cdProjetoAtual)}&termo=`;
        const response = await fetch(url, {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

        if (!response.ok) {
          console.warn('API de locais retornou erro:', response.status);
          locaisCache[cacheKey] = '';
          return '';
        }

        const locais = await response.json();
        const local = (locais || []).find(l => String(l.cdlocal) === String(cdLocal)) || (locais || []).find(l => String(l.id) === String(cdLocal));
        const nome = local && (local.delocal || local.LOCAL) ? (local.delocal || local.LOCAL) : '';

        // Guardar no cache mesmo que vazio
        locaisCache[cacheKey] = nome;
        console.log(`📋 Local ${cdLocal} (proj ${cdProjetoAtual}): "${nome}"`);
        return nome;
      } catch (error) {
        console.error('Erro ao buscar local:', error);
        locaisCache[cacheKey] = '';
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
        console.log(`📋 Funcionário ${cdMatricula}: "${nome}"`);
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
      const formRoot = formElement.querySelector('[x-data*="patrimonioForm"]');

      if (!formRoot) {
        console.error('Elemento com Alpine.js não encontrado');
        return {};
      }

      // Acessa os dados do Alpine
      const alpineData = formRoot._x_dataStack?.[0] || formRoot.__x_dataStack?.[0];

      if (!alpineData || !alpineData.formData) {
        console.error('formData do Alpine não encontrado');
        return {};
      }

      const dados = {};
      const camposValidos = Object.keys(labelCampos);

      camposValidos.forEach(campo => {
        // CDLOCAL no Alpine guarda o ID do registro (locais_projeto.id),
        // mas no banco o PatrimÇïnio guarda o cÇüdigo (locais_projeto.cdlocal).
        // Para o modal comparar/mostrar corretamente, usar o cÇüdigo visÇðvel (cdlocal).
        if (campo === 'CDLOCAL') {
          const cdLocalVisivel = String(alpineData.codigoLocalSelecionado || alpineData.codigoLocalDigitado || '').trim();
          if (cdLocalVisivel !== '') {
            dados[campo] = cdLocalVisivel;
            return;
          }
        }
        if (campo in alpineData.formData) {
          dados[campo] = alpineData.formData[campo];
        }
      });

      console.log('📋 Dados capturados do Alpine:', dados);
      return dados;
    }

    // Comparar dados e gerar HTML das alteracoes (assincrona)
    async function gerarAlteracoes(novos) {
      let html = '';
      let temAlteracao = false;

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
          } else if (campo === 'FLCONFERIDO') {
            const toBool = (v) => {
              const u = String(v || '').trim().toUpperCase();
              return ['S', '1', 'SIM', 'TRUE', 'T', 'Y', 'YES', 'ON'].includes(u);
            };

            const anteriorOk = toBool(valorAnterior);
            const novoOk = toBool(valorNovo);

            const badge = (ok) => ok
              ? `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">Verificado</span>`
              : `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Não verificado</span>`;

            html += `
            <div class="mb-3 pb-3 border-b border-gray-200 dark:border-gray-700 last:border-b-0 last:mb-0 last:pb-0">
              <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">${labelCampos[campo]}</p>
              <div class="flex items-center gap-3 text-sm">
                <span class="flex-1">${badge(anteriorOk)}</span>
                <span class="flex-shrink-0 text-blue-500 dark:text-blue-400 font-bold text-lg">→</span>
                <span class="flex-1">${badge(novoOk)}</span>
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

      // DEBUG: Ver o que estÇ­ sendo capturado
      console.log('=== DEBUG MODAL ===');
      console.log('dadosOriginais:', dadosOriginais);
      console.log('dadosNovos capturados:', dadosNovos);

      const html = await gerarAlteracoes(dadosNovos);
      alteracoesDiv.innerHTML = html;
      modalConfirmacao.classList.remove('hidden');
      modalConfirmacao.classList.add('flex');
    }

    // Fechar modal
    function fecharModal() {
      modalConfirmacao.classList.add('hidden');
      modalConfirmacao.classList.remove('flex');
    }

    // Eventos
    btnAtualizar.addEventListener('click', mostrarModal, { signal });
    btnCancelarModal.addEventListener('click', fecharModal, { signal });

    btnConfirmarAtualizacao.addEventListener('click', function() {
      console.log('Submetendo formulario...');

      // Validar se todos os campos obrigatórios estão preenchidos
      const formRoot = formElement.querySelector('[x-data*="patrimonioForm"]');

      if (!formRoot) {
        alert('❌ Erro: Elemento do formulário não encontrado');
        return;
      }

      const alpineData = formRoot._x_dataStack?.[0] || formRoot.__x_dataStack?.[0];
      if (!alpineData || !alpineData.formData) {
        alert('❌ Erro: Dados do formulário não acessíveis');
        return;
      }

      // Verificar campos obrigatorios
      const erros = [];
      if (!alpineData.formData.NUPATRIMONIO) erros.push('Número do Patrimônio é obrigatório');
      const requireCodigoOverride = String(formElement.dataset.requireCodobjeto || '').trim().toLowerCase();
      const requireCodigoObjeto = (requireCodigoOverride === '1' || requireCodigoOverride === 'true')
        ? true
        : (requireCodigoOverride === '0' || requireCodigoOverride === 'false')
          ? false
          : Boolean(dadosOriginais.NUSEQOBJ) || Boolean(alpineData.isNovoCodigo);
      if (requireCodigoObjeto && !alpineData.formData.NUSEQOBJ) erros.push('Código do Objeto é obrigatório');
      if (!alpineData.formData.CDMATRFUNCIONARIO) erros.push('Matrícula do Responsável é obrigatória');
      if (!alpineData.formData.SITUACAO) erros.push('Situação é obrigatória');
      if (erros.length > 0) {
        alert('❌ Erros de validação:\n\n' + erros.join('\n'));
        return;
      }

      fecharModal();
      if (typeof window.submitPatrimonioModalForm === 'function' && formElement.dataset.modalForm) {
        window.submitPatrimonioModalForm(formElement);
        return;
      }

      formElement.submit();
    }, { signal });

    // Fechar modal ao clicar fora
    modalConfirmacao.addEventListener('click', function(e) {
      if (e.target === modalConfirmacao) {
        fecharModal();
      }
    }, { signal });

    // Suporte para tecla ESC
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && !modalConfirmacao.classList.contains('hidden')) {
        fecharModal();
      }
    }, { signal });

    // Capturar o submit do formulario
    formElement.addEventListener('submit', function(e) {
      const formData = new FormData(this);
      const values = {};
      for (let [key, value] of formData.entries()) {
        values[key] = value;
      }
      console.log('\n' + '='.repeat(80));
      console.log('ÐY"Ï [SUBMIT] FormulÇ­rio de EdiÇõÇœo Enviado');
      console.log('='.repeat(80));
      console.log('Valores enviados:', JSON.stringify(values, null, 2));

      // Tentar acessar o formData do Alpine
      const formRoot = this.querySelector('[x-data*="patrimonioForm"]');
      if (formRoot && formRoot.__x_dataStack && formRoot.__x_dataStack.length > 0) {
        const alpineData = formRoot.__x_dataStack[0];
        console.log('\nÐY"O formData do Alpine:', JSON.stringify(alpineData.formData, null, 2));
      }
      console.log('='.repeat(80) + '\n');
    }, { signal });

    // Detectar erros de validacao (Laravel envia redirect com erros)
    const hasErrors = root.querySelector('[data-errors]');
    if (hasErrors) {
      console.error('❌ Erros de validação detectados na página');
    }
  }

  function destroyPatrimonioEditForm() {
    if (window.__patrimônioEditController) {
      window.__patrimônioEditController.abort();
      window.__patrimônioEditController = null;
    }
  }

  window.initPatrimonioEditForm = initPatrimonioEditForm;
  window.destroyPatrimonioEditForm = destroyPatrimonioEditForm;

  // ❌ Removida inicialização automática para evitar dupla chamada
  // A inicialização é feita pelo modal controller (index.blade.php) ao carregar o HTML
</script>

