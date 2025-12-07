<x-app-layout>
  <x-patrimonio-nav-tabs />
  
  <div class="py-12">
    <div class="w-full px-2 sm:px-6 lg:px-12 max-w-screen-xl mx-auto">
      
      {{-- Mensagens --}}
      @if(session('success'))
      <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative dark:bg-green-900 dark:border-green-700 dark:text-green-200">
        <strong class="font-bold">Sucesso!</strong>
        <span class="block sm:inline">{{ session('success') }}</span>
      </div>
      @endif
      
      @if(session('error'))
      <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative dark:bg-red-900 dark:border-red-700 dark:text-red-200">
        <strong class="font-bold">Erro!</strong>
        <span class="block sm:inline">{{ session('error') }}</span>
      </div>
      @endif
      
      @if($errors->any())
      <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative dark:bg-red-900 dark:border-red-700 dark:text-red-200">
        <strong class="font-bold">Erro!</strong>
        <span class="block sm:inline">{{ $errors->first() }}</span>
      </div>
      @endif
      
      <div class="section">
        <div class="section-body max-w-full">
          
          {{-- Filtros --}}
          <div x-data="{ open: false }" @click.outside="open = false" class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg mb-6">
            <div class="flex justify-between items-center gap-3">
              <div class="flex items-center gap-3">
                <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100">Filtros de Busca</h3>
                
                <div x-cloak x-show="!open" class="flex items-center gap-2 ml-3">
                  @php
                    $filterKeys = ['nupatrimonio','cdprojeto','descricao','situacao','modelo','nmplanta','matr_responsavel'];
                    $badgeColors = [
                      'nupatrimonio' => 'bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-700',
                      'cdprojeto' => 'bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-700',
                      'descricao' => 'bg-orange-100 dark:bg-orange-900 text-orange-700 dark:text-orange-300 border-orange-200 dark:border-orange-700',
                      'situacao' => 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 border-green-200 dark:border-green-700',
                      'modelo' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-700 dark:text-yellow-300 border-yellow-200 dark:border-yellow-700',
                      'nmplanta' => 'bg-pink-100 dark:bg-pink-900 text-pink-700 dark:text-pink-300 border-pink-200 dark:border-pink-700',
                      'matr_responsavel' => 'bg-cyan-100 dark:bg-cyan-900 text-cyan-700 dark:text-cyan-300 border-cyan-200 dark:border-cyan-700',
                    ];
                  @endphp
                  @foreach($filterKeys as $k)
                    @if(request()->filled($k))
                      @php
                        $labelsMap = [
                          'nupatrimonio' => 'Nº Patr.',
                          'cdprojeto' => 'Cód. Projeto',
                          'descricao' => 'Descrição',
                          'situacao' => 'Situação',
                          'modelo' => 'Modelo',
                          'nmplanta' => 'Cód. Termo',
                          'matr_responsavel' => 'Responsável',
                        ];
                        $label = $labelsMap[$k] ?? str_replace('_',' ',ucfirst($k));
                        $value = request($k);
                      @endphp
                      <a href="{{ route('patrimonios.index', request()->except($k)) }}" class="inline-flex items-center text-xs px-2 py-1 rounded-full border hover:opacity-90 {{ $badgeColors[$k] ?? 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-700' }}">
                        <span class="truncate max-w-[120px]">{{ $label }}: {{ Str::limit((string)$value, 24) }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M6.293 7.293a1 1 0 011.414 0L10 9.586l2.293-2.293a1 1 0 111.414 1.414L11.414 11l2.293 2.293a1 1 0 01-1.414 1.414L10 12.414l-2.293 2.293a1 1 0 01-1.414-1.414L8.586 11 6.293 8.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                      </a>
                    @endif
                  @endforeach
                </div>
              </div>
              
              <button type="button" @click="open = !open" class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform transition-transform text-gray-600 dark:text-gray-400" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>
            </div>
            
            <div x-cloak x-show="open" x-transition class="mt-4">
              <form method="GET" action="{{ route('patrimonios.index') }}" @submit="open=false">
                <div class="grid gap-3 sm:gap-4" style="grid-template-columns: repeat(auto-fit,minmax(150px,1fr));">
                  <div>
                    <input type="text" name="nupatrimonio" placeholder="Nº Patr." value="{{ request('nupatrimonio') }}" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                  </div>
                  <div>
                    <input type="text" name="cdprojeto" placeholder="Cód. Projeto" value="{{ request('cdprojeto') }}" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                  </div>
                  <div class="col-span-full md:col-span-2">
                    <input type="text" name="descricao" placeholder="Descrição" value="{{ request('descricao') }}" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                  </div>
                  <div>
                    <input type="text" name="situacao" placeholder="Situação" value="{{ request('situacao') }}" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                  </div>
                  <div>
                    <input type="text" name="modelo" placeholder="Modelo" value="{{ request('modelo') }}" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                  </div>
                  <div>
                    <input type="number" name="nmplanta" placeholder="Cód. Termo" value="{{ request('nmplanta') }}" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                  </div>
                  <div>
                    <x-employee-autocomplete 
                      id="matr_responsavel_search"
                      name="matr_responsavel"
                      placeholder="Responsável"
                      value="{{ request('matr_responsavel') }}"
                    />
                  </div>
                </div>

                <div class="flex flex-wrap items-center justify-between mt-4 gap-4">
                  <div class="flex items-center gap-3">
                    <x-primary-button class="h-10 px-4">Filtrar</x-primary-button>
                    <a href="{{ route('patrimonios.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">Limpar</a>
                  </div>

                  <label class="flex items-center gap-2 ml-auto shrink-0">
                    <span class="text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">Itens por página</span>
                    <select name="per_page" class="h-10 px-2 sm:px-3 w-24 text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md">
                      @foreach([10,30,50,100,200] as $opt)
                      <option value="{{ $opt }}" @selected(request('per_page', 30)==$opt)>{{ $opt }}</option>
                      @endforeach
                    </select>
                  </label>
                </div>
              </form>
            </div>
          </div>

          {{-- Ações --}}
          <div class="flex items-center gap-3 mb-4">
            <a href="{{ route('patrimonios.create') }}" class="bg-plansul-blue hover:bg-opacity-90 text-white font-semibold py-2 px-4 rounded inline-flex items-center shadow">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
              </svg>
              <span>Cadastrar</span>
            </a>

            <a href="#" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded inline-flex items-center">
              <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"></path>
              </svg>
              <span>Gerar Relatório</span>
            </a>
          </div>

          {{-- Grid Reutilizável --}}
          <x-patrimonio-table 
            :patrimonios="$patrimonios" 
            :columns="['nupatrimonio', 'numof', 'codobjeto', 'nmplanta', 'nuserie', 'projeto', 'local', 'modelo', 'marca', 'descricao', 'situacao', 'dtaquisicao', 'dtoperacao', 'responsavel', 'cadastrador']"
            :showActions="true"
            :clickable="true"
            onRowClick="{{ route('patrimonios.edit', ':id') }}"
            actionsView="patrimonios.partials.table-actions"
          />
          
          {{-- Paginação --}}
          <div class="mt-4">
            {{ $patrimonios->appends(request()->query())->links() }}
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>

@push('scripts')
<script>
  console.log('✅ [JS LOADED] Script de deleção de patrimônios carregado');
  
  document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 [DOMContentLoaded] Inicializando sistema de deleção');

    const boundButtons = new Set();
    let bindingAttempts = 0;

    function bindDeleteButtons() {
      bindingAttempts++;
      const buttons = document.querySelectorAll('.delete-patrimonio-btn');
      console.log(`📌 [BIND ATTEMPT #${bindingAttempts}] Encontrados ${buttons.length} botões com classe .delete-patrimonio-btn`);
      
      if (buttons.length === 0) {
        console.warn('⚠️ [BIND] Nenhum botão encontrado! Verifique se a tabela foi renderizada.');
      }
      
      buttons.forEach((btn, index) => {
        if (boundButtons.has(btn)) {
          console.log(`⏭️ [BIND] Botão ${index} já vinculado, pulando...`);
          return;
        }
        boundButtons.add(btn);

        const id = btn.dataset.patrimonioId;
        const nome = btn.dataset.patrimonioName || 'Patrimônio';
        const url = btn.dataset.deleteUrl;

        console.log(`✅ [BIND] Botão ${index} vinculado:`, { id, nome, url });

        btn.addEventListener('click', async function(e) {
          console.log('⚡ [CLICK EVENT] Clique no botão detectado!', { 
            id: this.dataset.patrimonioId,
            classe: this.className, 
            dataset: this.dataset 
          });
          
          e.preventDefault();
          e.stopPropagation();

          const patrimonio_id = this.dataset.patrimonioId;
          const patrimonio_nome = this.dataset.patrimonioName || 'Patrimônio';
          const delete_url = this.dataset.deleteUrl || `/patrimonio/delete/${patrimonio_id}`;

          console.log('🗑️ [DELETE FLOW] Iniciando fluxo de deleção:', { 
            patrimonio_id, 
            patrimonio_nome, 
            delete_url, 
            btn_element: this 
          });

          if (!patrimonio_id) {
            console.error('❌ [ERROR] ID não encontrado no data-attribute');
            alert('❌ Erro: ID do patrimônio não encontrado');
            return;
          }

          const confirmacao = confirm(`🗑️ Remover "${patrimonio_nome}" (ID: ${patrimonio_id})?\n\n⚠️ Esta ação NÃO pode ser desfeita!`);
          console.log('📋 [CONFIRM] Usuário respondeu:', confirmacao ? 'SIM' : 'NÃO');
          
          if (!confirmacao) {
            console.log('⏸️ [CANCELLED] Deleção cancelada pelo usuário');
            return;
          }

          const btn_original_html = this.innerHTML;
          const btn_original_disabled = this.disabled;
          this.disabled = true;
          this.innerHTML = '<svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';

          try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            
            console.log('📡 [FETCH] Preparando requisição DELETE:', {
              url: delete_url,
              method: 'DELETE',
              csrf_present: !!csrf,
              headers: {
                'X-CSRF-TOKEN': csrf ? '✅ Presente' : '❌ Ausente',
                'Accept': 'application/json'
              }
            });

            const response = await fetch(delete_url, {
              method: 'DELETE',
              headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
              }
            });

            console.log('📊 [RESPONSE] Resposta HTTP recebida:', {
              status: response.status,
              statusText: response.statusText,
              ok: response.ok,
              content_type: response.headers.get('content-type'),
              url_final: response.url
            });

            let data = {};
            try {
              const text = await response.clone().text();
              console.log('📄 [RESPONSE BODY] Conteúdo bruto:', text.substring(0, 200));
              data = JSON.parse(text);
              console.log('✅ [JSON PARSED] Dados parseados:', data);
            } catch (parseErr) {
              console.error('❌ [PARSE ERROR] Erro ao fazer parse do JSON:', parseErr.message);
              data = { message: 'Erro ao processar resposta' };
            }

            if (response.ok && response.status === 200) {
              console.log('✅ [SUCCESS] Patrimônio deletado com sucesso! Response OK (200)');
              console.log('📌 [SUCCESS DATA]', data);
              
              alert(`✅ "${patrimonio_nome}" foi removido com sucesso!`);
              console.log('🔄 [RELOAD] Recarregando página em 500ms...');
              setTimeout(() => {
                console.log('🔄 [RELOAD NOW] Recarregando...');
                window.location.reload();
              }, 500);
            } else if (response.status === 403) {
              console.error('❌ [AUTH ERROR] Permissão negada (403):', data.message);
              alert('❌ Permissão negada!\n\n' + (data.message || 'Você não tem permissão para deletar este patrimônio.'));
              this.disabled = btn_original_disabled;
              this.innerHTML = btn_original_html;
            } else if (response.status === 404) {
              console.error('❌ [NOT FOUND] Patrimônio não encontrado (404):', data.message);
              alert('❌ Erro: Patrimônio não encontrado!');
              this.disabled = btn_original_disabled;
              this.innerHTML = btn_original_html;
            } else {
              console.error('❌ [ERROR] Erro HTTP:', { status: response.status, statusText: response.statusText, data });
              alert('❌ Erro ' + response.status + ': ' + (data.message || response.statusText));
              this.disabled = btn_original_disabled;
              this.innerHTML = btn_original_html;
            }
          } catch (err) {
            console.error('❌ [NETWORK ERROR] Erro na requisição fetch:', {
              message: err.message,
              name: err.name,
              stack: err.stack
            });
            alert('❌ Erro na comunicação com o servidor:\n' + err.message);
            this.disabled = btn_original_disabled;
            this.innerHTML = btn_original_html;
          }
        });
      });
    }

    // Vincular botões pela primeira vez
    bindDeleteButtons();

    // Rebind se houver mudanças no DOM (paginação, filtros, etc)
    console.log('👀 [OBSERVER] Monitorando mudanças no DOM...');
    new MutationObserver((mutations) => {
      const hasRelevantChange = mutations.some(m => {
        const addedNodes = Array.from(m.addedNodes);
        return addedNodes.some(n => 
          n.classList?.contains('delete-patrimonio-btn') ||
          n.querySelector?.('.delete-patrimonio-btn') ||
          n.classList?.contains('patrimonio-row') ||
          n.classList?.contains('pagination')
        );
      });
      
      if (hasRelevantChange) {
        console.log('🔄 [OBSERVER TRIGGERED] Mudanças no DOM detectadas, rebinding botões...');
        bindDeleteButtons();
      }
    }).observe(document.body, { 
      childList: true, 
      subtree: true 
    });

    console.log('✅ [INIT COMPLETE] Sistema de deleção inicializado com sucesso');
  });
</script>
@endpush
