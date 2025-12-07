<div x-data="{ open: false }" @click.outside="open = false" class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg mb-6" x-id="['filtro-patrimonios']" :aria-expanded="open.toString()" :aria-controls="$id('filtro-patrimonios')">
  <div class="flex justify-between items-center gap-3">
    <div class="flex items-center gap-3">
      <h3 class="font-semibold text-lg">Filtros de Busca</h3>
      {{-- Badges que mostram filtros ativos quando o painel está recolhido --}}
      <div x-cloak x-show="!open" class="flex items-center gap-2 ml-3">
        @php
          $filterKeys = ['nupatrimonio','cdprojeto','descricao','situacao','modelo','nmplanta','matr_responsavel','cadastrado_por'];
          $badgeColors = [
            'nupatrimonio' => 'bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-700',
            'cdprojeto' => 'bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-700',
            'descricao' => 'bg-orange-100 dark:bg-orange-900 text-orange-700 dark:text-orange-300 border-orange-200 dark:border-orange-700',
            'situacao' => 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 border-green-200 dark:border-green-700',
            'modelo' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-700 dark:text-yellow-300 border-yellow-200 dark:border-yellow-700',
            'nmplanta' => 'bg-pink-100 dark:bg-pink-900 text-pink-700 dark:text-pink-300 border-pink-200 dark:border-pink-700',
            'matr_responsavel' => 'bg-cyan-100 dark:bg-cyan-900 text-cyan-700 dark:text-cyan-300 border-cyan-200 dark:border-cyan-700',
            'cadastrado_por' => 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-700',
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
                'cadastrado_por' => 'Cadastrador',
              ];
              $label = $labelsMap[$k] ?? str_replace('_',' ',ucfirst($k));
              $value = request($k);
            @endphp
            <a href="{{ route('patrimonios.index', request()->except($k)) }}" class="inline-flex items-center text-xs px-2 py-1 rounded-full border hover:opacity-90 {{ $badgeColors[$k] ?? 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-700' }}">
              <span class="truncate max-w-[120px]">{{ $label }}: {{ Str::limit((string)$value, 24) }}</span>
              <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6.293 7.293a1 1 0 011.414 0L10 9.586l2.293-2.293a1 1 0 111.414 1.414L11.414 11l2.293 2.293a1 1 0 01-1.414 1.414L10 12.414l-2.293 2.293a1 1 0 01-1.414-1.414L8.586 11 6.293 8.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </a>
          @endif
        @endforeach
      </div>
    </div>
    <button type="button" @click="open = !open" :aria-expanded="open.toString()" :aria-controls="$id('filtro-patrimonios')" class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition focus:outline-none focus:ring-2 focus:ring-indigo-500">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
      <span class="sr-only">Expandir filtros</span>
    </button>
  </div>
  <div x-cloak x-show="open" x-transition class="mt-4" :id="$id('filtro-patrimonios')">
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
            placeholder="Responsável (matrícula ou nome)"
            value="{{ request('matr_responsavel') }}"
          />
        </div>
        <div>
          <select name="cadastrado_por" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md">
            <option value="">Usuário</option>
            @foreach ($cadastradores as $cadastrador)
              <option value="{{ $cadastrador->NMLOGIN }}" @selected(request('cadastrado_por')==$cadastrador->NMLOGIN)>
                {{ Str::limit($cadastrador->NOMEUSER,18) }}
              </option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-between mt-4 gap-4">
        <div class="flex items-center gap-3">
          <x-primary-button class="h-10 px-4">
            {{ __('Filtrar') }}
          </x-primary-button>

          <a href="{{ route('patrimonios.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md">
            Limpar
          </a>
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
