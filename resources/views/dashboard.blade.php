<x-app-layout>
  @php
    $deslocamentoDashboard = $deslocamentoDashboard ?? [
      'ano_selecionado' => now()->year,
      'anos_disponiveis' => [now()->year],
      'resumo' => ['total_envios' => 0, 'custo_total' => 0, 'ticket_medio' => 0, 'total_projetos' => 0],
      'mensal' => ['labels' => [], 'values' => [], 'rows' => []],
      'projetos' => ['labels' => [], 'values' => [], 'rows' => []],
    ];
    $deslocamentoResumo = $deslocamentoDashboard['resumo'] ?? [];
    $deslocamentoMensal = $deslocamentoDashboard['mensal'] ?? ['labels' => [], 'values' => [], 'rows' => []];
    $deslocamentoProjetos = $deslocamentoDashboard['projetos'] ?? ['labels' => [], 'values' => [], 'rows' => []];
  @endphp
  <div class="py-12">
    <div class="w-full sm:px-6 lg:px-8">

      {{-- Seletor de aba do dashboard --}}
      <div class="mb-6 flex items-center gap-2">
        <button id="tabPatrimonio"
          onclick="switchTab('patrimonio')"
          class="dashboard-tab inline-flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold transition-all duration-200 bg-plansul-blue text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-plansul-blue focus:ring-offset-2 dark:focus:ring-offset-gray-900">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
          Patrimônios
        </button>
        <button id="tabDeslocamento"
          onclick="switchTab('deslocamento')"
          class="dashboard-tab inline-flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold transition-all duration-200 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:border-plansul-blue dark:hover:border-blue-400 focus:outline-none focus:ring-2 focus:ring-plansul-blue focus:ring-offset-2 dark:focus:ring-offset-gray-900">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
          Custos de Deslocamento
        </button>
      </div>

      {{-- Seção: Patrimônios --}}
      <div id="secaoPatrimonio">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-1 space-y-6">
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
              <h3 class="font-semibold text-lg mb-2">Patrimônios Cadastrados Hoje</h3>
              <p class="text-5xl font-bold text-plansul-blue">{{ $cadastrosHoje }}</p>
            </div>
          </div>

          @if(!empty($verificadosStats))
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
              <p class="text-xs uppercase tracking-wide font-semibold text-gray-600 dark:text-gray-400 mb-2">Verificados</p>
              <p class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ number_format($verificadosStats['verificados'] ?? 0, 0, ',', '.') }} /
                {{ number_format($verificadosStats['total'] ?? 0, 0, ',', '.') }}
              </p>
              <div class="mt-3 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                <div id="progressBar" class="h-2 rounded-full bg-emerald-500" data-percent="{{ (int) ($verificadosStats['percent'] ?? 0) }}"></div>
              </div>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ (int) ($verificadosStats['percent'] ?? 0) }}% conferidos</p>
            </div>
          </div>
          @endif

          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
              <h3 class="font-semibold text-lg mb-4">Top 5 Usuários com Mais Cadastros</h3>
              <ul class="space-y-2">
                @forelse($topCadastradores as $cadastrador)
                <li class="flex justify-between items-center text-sm">
                  <span>{{ $cadastrador->NOMEUSER }}</span>
                  <span class="font-bold bg-plansul-orange text-white px-2 py-1 rounded-full">{{ $cadastrador->total }}</span>
                </li>
                @empty
                <li>Nenhum cadastro encontrado.</li>
                @endforelse
              </ul>
            </div>
          </div>

          

          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
              <div class="space-y-3">
                <div class="bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                  <p class="text-xs uppercase tracking-wide font-semibold text-gray-600 dark:text-gray-400 mb-2">Total Geral</p>
                  <p class="text-3xl font-bold text-gray-900 dark:text-white"><span id="filterTotal">0</span></p>
                </div>
                <div id="selectedWrap" class="hidden bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                  <p class="text-xs uppercase tracking-wide font-semibold text-gray-600 dark:text-gray-400 mb-2">Selecionado: <span id="selectedLabel" class="text-plansul-blue dark:text-blue-400">—</span></p>
                  <p class="text-3xl font-bold text-gray-900 dark:text-white"><span id="selectedTotal">0</span></p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="lg:col-span-2 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900 dark:text-gray-100">
            <!-- Header -->
            <div class="mb-8">
              <p class="text-xs uppercase tracking-widest font-semibold text-gray-500 dark:text-gray-400 mb-2">Dashboard de Estatísticas</p>
              <h3 id="chartTitle" class="font-bold text-4xl text-gray-900 dark:text-white">Cadastros</h3>
            </div>

            <!-- Controles principais -->
            <div class="mb-8">
              <div class="flex flex-wrap items-end justify-between gap-4 mb-4">
                <div>
                  <label class="text-xs uppercase tracking-wide font-semibold text-gray-600 dark:text-gray-400 mb-2 block">Visualizar por</label>
                  <div class="inline-flex items-center gap-2 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                    <button data-view="uf" class="view-btn px-4 py-2 rounded-md text-sm font-semibold transition-all duration-200 bg-plansul-orange text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-plansul-orange focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                      Estados
                    </button>
                    <button data-view="cadastros" class="view-btn px-4 py-2 rounded-md text-sm font-semibold transition-all duration-200 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-plansul-orange">
                      Cadastros
                    </button>
                    <button data-view="total" class="view-btn px-4 py-2 rounded-md text-sm font-semibold transition-all duration-200 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-plansul-orange">
                      Total
                    </button>
                  </div>
                </div>

                <div id="periodWrap" class="hidden">
                  <label class="text-xs uppercase tracking-wide font-semibold text-gray-600 dark:text-gray-400 mb-2 block">Período</label>
                  <div class="inline-flex items-center gap-2 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                    <button data-period="day" class="filter-btn px-4 py-2 rounded-md text-sm font-medium transition-all duration-200 bg-white dark:bg-gray-800 border border-transparent text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-plansul-blue focus:ring-offset-2 dark:focus:ring-offset-gray-800">Dia</button>
                    <button data-period="week" class="filter-btn px-4 py-2 rounded-md text-sm font-medium transition-all duration-200 bg-white dark:bg-gray-800 border border-transparent text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-plansul-blue focus:ring-offset-2 dark:focus:ring-offset-gray-800">Semana</button>
                    <button data-period="month" class="filter-btn px-4 py-2 rounded-md text-sm font-medium transition-all duration-200 bg-white dark:bg-gray-800 border border-transparent text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-plansul-blue focus:ring-offset-2 dark:focus:ring-offset-gray-800">Mês</button>
                    <button data-period="year" class="filter-btn px-4 py-2 rounded-md text-sm font-medium transition-all duration-200 bg-white dark:bg-gray-800 border border-transparent text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-plansul-blue focus:ring-offset-2 dark:focus:ring-offset-gray-800">Ano</button>
                  </div>
                </div>
              </div>

              <div>
                <label class="text-xs uppercase tracking-wide font-semibold text-gray-600 dark:text-gray-400 mb-2 block">Status</label>
                <div class="flex flex-wrap items-center gap-2">
                  <button data-status="ativos" class="status-btn px-3 py-2 rounded-md text-sm font-medium transition-all duration-200 border bg-plansul-blue text-white border-plansul-blue shadow-sm">
                    Ativos (Padrão)
                  </button>
                  <button data-status="all" class="status-btn px-3 py-2 rounded-md text-sm font-medium transition-all duration-200 border bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-plansul-blue dark:hover:border-blue-500">
                    Incluir baixados
                  </button>
                  <button data-status="baixa" class="status-btn px-3 py-2 rounded-md text-sm font-medium transition-all duration-200 border bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-plansul-blue dark:hover:border-blue-500">
                    Apenas baixados
                  </button>
                  <button data-status="em_uso" class="status-btn px-3 py-2 rounded-md text-sm font-medium transition-all duration-200 border bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-plansul-blue dark:hover:border-blue-500">
                    Apenas em uso
                  </button>
                  <button data-status="a_disposicao" class="status-btn px-3 py-2 rounded-md text-sm font-medium transition-all duration-200 border bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-plansul-blue dark:hover:border-blue-500">
                    Apenas à disposição
                  </button>
                  <button data-status="conserto" class="status-btn px-3 py-2 rounded-md text-sm font-medium transition-all duration-200 border bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-plansul-blue dark:hover:border-blue-500">
                    Apenas em conserto
                  </button>
                </div>
              </div>
            </div>
            <!-- Gráfico -->
            <div class="mb-6">
              <canvas id="cadastrosChart" class="max-h-96"
                data-labels='{!! json_encode($cadastrosSemanaLabels) !!}'
                data-values='{!! json_encode($cadastrosSemanaData) !!}'></canvas>
            </div>

            <!-- Status e Botões de UF -->
            <p id="filterStatus" x-cloak class="text-center text-sm text-gray-600 dark:text-gray-400 py-4 hidden">
              Nenhum movimento no período selecionado.
            </p>

            <div id="ufButtonsWrap" class="mt-6 hidden">
              <div class="mb-2">
                <p class="text-xs uppercase tracking-wide font-semibold text-gray-600 dark:text-gray-400 mb-3">Selecionar estado</p>
              </div>
              <div id="ufButtons" class="flex flex-wrap gap-2"></div>
            </div>
          </div>
        </div>

      </div>
      </div>{{-- /secaoPatrimonio --}}

      {{-- Seção: Custos de Deslocamento --}}
      <div id="secaoDeslocamento" style="display:none">
      <div class="mt-0 space-y-6">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900 dark:text-gray-100">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
              <div>
                <p class="text-xs uppercase tracking-widest font-semibold text-gray-500 dark:text-gray-400 mb-2">Custos de Deslocamento</p>
                <h3 class="font-bold text-3xl text-gray-900 dark:text-white">Envios para projetos</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Painel consolidado com base em solicitações enviadas para projeto, com cotação aprovada e envio confirmado.</p>
              </div>

              <form method="GET" action="{{ route('dashboard') }}" class="flex flex-col gap-2 sm:flex-row sm:items-end">
                <div>
                  <label for="frete_year" class="block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400 mb-2">Ano de análise</label>
                  <select id="frete_year" name="frete_year" class="block w-full h-10 min-w-40 rounded-md border border-gray-200 bg-white px-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500">
                    @foreach(($deslocamentoDashboard['anos_disponiveis'] ?? []) as $anoDisponivel)
                      <option value="{{ $anoDisponivel }}" @selected((int) $anoDisponivel === (int) ($deslocamentoDashboard['ano_selecionado'] ?? now()->year))>{{ $anoDisponivel }}</option>
                    @endforeach
                  </select>
                </div>
                <button type="submit" class="inline-flex h-10 items-center justify-center rounded-md bg-plansul-blue px-4 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                  Atualizar painel
                </button>
              </form>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
              <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/60">
                <p class="text-xs uppercase tracking-wide font-semibold text-gray-600 dark:text-gray-400 mb-2">Custo total</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">R$ {{ number_format((float) ($deslocamentoResumo['custo_total'] ?? 0), 2, ',', '.') }}</p>
              </div>
              <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/60">
                <p class="text-xs uppercase tracking-wide font-semibold text-gray-600 dark:text-gray-400 mb-2">Envios confirmados</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format((int) ($deslocamentoResumo['total_envios'] ?? 0), 0, ',', '.') }}</p>
              </div>
              <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/60">
                <p class="text-xs uppercase tracking-wide font-semibold text-gray-600 dark:text-gray-400 mb-2">Ticket médio</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">R$ {{ number_format((float) ($deslocamentoResumo['ticket_medio'] ?? 0), 2, ',', '.') }}</p>
              </div>
              <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/60">
                <p class="text-xs uppercase tracking-wide font-semibold text-gray-600 dark:text-gray-400 mb-2">Projetos com custo</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format((int) ($deslocamentoResumo['total_projetos'] ?? 0), 0, ',', '.') }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
              <div class="mb-4">
                <p class="text-xs uppercase tracking-widest font-semibold text-gray-500 dark:text-gray-400 mb-2">Custo Mensal</p>
                <h4 class="text-2xl font-bold text-gray-900 dark:text-white">Evolução ao longo de {{ $deslocamentoDashboard['ano_selecionado'] ?? now()->year }}</h4>
              </div>
              @if(collect($deslocamentoMensal['values'] ?? [])->sum() > 0)
                <canvas id="deslocamentoMensalChart" class="max-h-96"
                  data-labels='@json($deslocamentoMensal['labels'] ?? [])'
                  data-values='@json($deslocamentoMensal['values'] ?? [])'></canvas>
              @else
                <p class="rounded-lg border border-dashed border-gray-300 p-6 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-400">Nenhum envio com custo registrado para o ano selecionado.</p>
              @endif
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
              <div class="mb-4">
                <p class="text-xs uppercase tracking-widest font-semibold text-gray-500 dark:text-gray-400 mb-2">Custo por Projeto</p>
                <h4 class="text-2xl font-bold text-gray-900 dark:text-white">Top projetos com maior gasto</h4>
              </div>
              @if(!empty($deslocamentoProjetos['labels'] ?? []))
                <canvas id="deslocamentoProjetosChart" class="max-h-[30rem]"
                  data-labels='@json($deslocamentoProjetos['labels'] ?? [])'
                  data-values='@json($deslocamentoProjetos['values'] ?? [])'></canvas>
              @else
                <p class="rounded-lg border border-dashed border-gray-300 p-6 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-400">Ainda não há projetos com custo de deslocamento consolidado.</p>
              @endif
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
              <div class="mb-4">
                <p class="text-xs uppercase tracking-widest font-semibold text-gray-500 dark:text-gray-400 mb-2">Resumo Mensal</p>
                <h4 class="text-xl font-bold text-gray-900 dark:text-white">Totais por mês</h4>
              </div>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                  <thead>
                    <tr>
                      <th class="px-3 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Mês</th>
                      <th class="px-3 py-2 text-right font-semibold text-gray-600 dark:text-gray-300">Envios</th>
                      <th class="px-3 py-2 text-right font-semibold text-gray-600 dark:text-gray-300">Custo</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach(($deslocamentoMensal['rows'] ?? []) as $linhaMensal)
                      <tr>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ $linhaMensal['label'] }}</td>
                        <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200">{{ number_format((int) ($linhaMensal['envios'] ?? 0), 0, ',', '.') }}</td>
                        <td class="px-3 py-2 text-right font-semibold text-gray-900 dark:text-white">R$ {{ number_format((float) ($linhaMensal['custo_total'] ?? 0), 2, ',', '.') }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="xl:col-span-2 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
              <div class="mb-4">
                <p class="text-xs uppercase tracking-widest font-semibold text-gray-500 dark:text-gray-400 mb-2">Detalhamento por Projeto</p>
                <h4 class="text-xl font-bold text-gray-900 dark:text-white">Custos consolidados do ano</h4>
              </div>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                  <thead>
                    <tr>
                      <th class="px-3 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Projeto</th>
                      <th class="px-3 py-2 text-right font-semibold text-gray-600 dark:text-gray-300">Envios</th>
                      <th class="px-3 py-2 text-right font-semibold text-gray-600 dark:text-gray-300">Custo total</th>
                      <th class="px-3 py-2 text-right font-semibold text-gray-600 dark:text-gray-300">Ticket médio</th>
                      <th class="px-3 py-2 text-right font-semibold text-gray-600 dark:text-gray-300">Último envio</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse(($deslocamentoProjetos['rows'] ?? []) as $linhaProjeto)
                      <tr>
                        <td class="px-3 py-3 text-gray-700 dark:text-gray-200">
                          <div class="font-semibold text-gray-900 dark:text-white">{{ $linhaProjeto['projeto_nome'] }}</div>
                          @if(!empty($linhaProjeto['projeto_codigo']))
                            <div class="text-xs text-gray-500 dark:text-gray-400">Código {{ $linhaProjeto['projeto_codigo'] }}</div>
                          @endif
                        </td>
                        <td class="px-3 py-3 text-right text-gray-700 dark:text-gray-200">{{ number_format((int) ($linhaProjeto['total_envios'] ?? 0), 0, ',', '.') }}</td>
                        <td class="px-3 py-3 text-right font-semibold text-gray-900 dark:text-white">R$ {{ number_format((float) ($linhaProjeto['custo_total'] ?? 0), 2, ',', '.') }}</td>
                        <td class="px-3 py-3 text-right text-gray-700 dark:text-gray-200">R$ {{ number_format((float) ($linhaProjeto['ticket_medio'] ?? 0), 2, ',', '.') }}</td>
                        <td class="px-3 py-3 text-right text-gray-700 dark:text-gray-200">{{ !empty($linhaProjeto['ultimo_envio']) ? \Carbon\Carbon::parse($linhaProjeto['ultimo_envio'])->format('d/m/Y') : '—' }}</td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="5" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">Nenhum projeto com custo de deslocamento encontrado no período.</td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
      </div>{{-- /secaoDeslocamento --}}
    </div>
  </div>

  @push('scripts')
  <script>
    // Configurar barra de progresso
    const progressBar = document.getElementById('progressBar');
    if (progressBar) {
      const percent = progressBar.getAttribute('data-percent') || 0;
      progressBar.style.width = percent + '%';
    }

    document.addEventListener('DOMContentLoaded', function() {
      const barCanvas = document.getElementById('cadastrosChart');
      if (!barCanvas) return;
      const barCtx = barCanvas.getContext('2d');

      const safeParse = (str) => {
        try {
          return JSON.parse(str);
        } catch (e) {
          return [];
        }
      };

      const chartTitleEl = document.getElementById('chartTitle');
      const periodWrapEl = document.getElementById('periodWrap');
      const filterTotalEl = document.getElementById('filterTotal');
      const filterStatusEl = document.getElementById('filterStatus');
      const selectedWrapEl = document.getElementById('selectedWrap');
      const selectedLabelEl = document.getElementById('selectedLabel');
      const selectedTotalEl = document.getElementById('selectedTotal');
      const ufButtonsWrapEl = document.getElementById('ufButtonsWrap');
      const ufButtonsEl = document.getElementById('ufButtons');

      const initialLabels = safeParse(barCanvas.getAttribute('data-labels') || '[]');
      const initialValues = safeParse(barCanvas.getAttribute('data-values') || '[]');

      let currentPeriod = 'week';
      let currentView = 'uf';
      let currentStatusMode = @json($statusMode ?? 'ativos');
      let selectedUf = 'ALL';
      let currentLabels = initialLabels;
      let currentValues = initialValues;

      const palette = [
        { bg: 'rgba(59, 130, 246, 0.76)', border: 'rgba(59, 130, 246, 1)' },
        { bg: 'rgba(16, 185, 129, 0.76)', border: 'rgba(16, 185, 129, 1)' },
        { bg: 'rgba(168, 85, 247, 0.76)', border: 'rgba(168, 85, 247, 1)' },
        { bg: 'rgba(245, 158, 11, 0.76)', border: 'rgba(245, 158, 11, 1)' },
        { bg: 'rgba(239, 68, 68, 0.76)', border: 'rgba(239, 68, 68, 1)' },
        { bg: 'rgba(20, 184, 166, 0.76)', border: 'rgba(20, 184, 166, 1)' },
        { bg: 'rgba(99, 102, 241, 0.76)', border: 'rgba(99, 102, 241, 1)' },
        { bg: 'rgba(236, 72, 153, 0.76)', border: 'rgba(236, 72, 153, 1)' },
      ];
      const highlight = { bg: 'rgba(249, 115, 22, 0.85)', border: 'rgba(249, 115, 22, 1)' };

      const formatValue = (value) => {
        const num = Number(value || 0);
        return num.toLocaleString('pt-BR');
      };

      const dataLabelPlugin = {
        id: 'value-labels',
        afterDatasetsDraw(chart) {
          const { ctx, data, chartArea } = chart;
          ctx.save();
          ctx.textAlign = 'center';
          ctx.textBaseline = 'middle';
          ctx.font = '500 12px "Inter", system-ui, sans-serif';
          ctx.fillStyle = document.documentElement.classList.contains('dark') ? '#e2e8f0' : '#0f172a';

          data.datasets.forEach((dataset, datasetIndex) => {
            const meta = chart.getDatasetMeta(datasetIndex);
            meta.data.forEach((element, index) => {
              const value = dataset.data[index];
              if (value === null || value === undefined) {
                return;
              }
              const label = formatValue(value);
              const { x, y } = element.tooltipPosition ? element.tooltipPosition() : element.getCenterPoint();
              const offsetY = element.height ? -Math.max(element.height, 20) / 2 : -10;
              ctx.fillText(label, x, y + offsetY);
            });
          });

          ctx.restore();
        }
      };

      const getColorSet = (labels, highlightUf) => {
        if (currentView === 'total') {
          const bg = [];
          const border = [];
          labels.forEach(label => {
            const upper = String(label || '').toUpperCase();
            if (upper.includes('NAO VERIFICADOS') || upper.includes('NÃO VERIFICADOS')) {
              bg.push('rgba(234, 179, 8, 0.76)');
              border.push('rgba(234, 179, 8, 1)');
            } else if (upper.includes('VERIFICADOS')) {
              bg.push('rgba(16, 185, 129, 0.76)');
              border.push('rgba(16, 185, 129, 1)');
            } else {
              bg.push('rgba(59, 130, 246, 0.76)');
              border.push('rgba(59, 130, 246, 1)');
            }
          });
          return { bg, border };
        }

        const bg = [];
        const border = [];
        const normalized = (highlightUf || 'ALL').toUpperCase();

        labels.forEach((label, idx) => {
          const base = palette[idx % palette.length];
          const labelValue = String(label || '').toUpperCase();
          if (normalized !== 'ALL' && labelValue === normalized) {
            bg.push(highlight.bg);
            border.push(highlight.border);
          } else {
            bg.push(base.bg);
            border.push(base.border);
          }
        });

        return { bg, border };
      };

      const setTotals = (overall, selectedLabel, selectedValue) => {
        if (filterTotalEl) filterTotalEl.textContent = overall;
        const showSelected = currentView === 'uf' && selectedLabel && selectedLabel !== 'ALL';
        if (selectedWrapEl) selectedWrapEl.classList.toggle('hidden', !showSelected);
        if (selectedLabelEl) selectedLabelEl.textContent = showSelected ? selectedLabel : '-';
        if (selectedTotalEl) selectedTotalEl.textContent = showSelected ? selectedValue : 0;
      };

      const setActive = (buttons, active, options = {}) => {
        const activeClasses = options.activeClasses || ['bg-plansul-blue', 'text-white'];
        const inactiveClasses = options.inactiveClasses || ['bg-white', 'dark:bg-gray-800', 'text-gray-800', 'dark:text-gray-200'];

        buttons.forEach(btn => {
          activeClasses.forEach(cls => btn.classList.remove(cls));
          inactiveClasses.forEach(cls => btn.classList.remove(cls));
          btn.classList.add(...inactiveClasses);
        });

        if (active) {
          inactiveClasses.forEach(cls => active.classList.remove(cls));
          activeClasses.forEach(cls => active.classList.add(cls));
        }
      };

      const getViewLabels = () => {
        if (currentView === 'uf') {
          return {
            title: 'Lançamentos por Estado (UF)',
            dataset: 'Lançamentos por UF',
          };
        }

        if (currentView === 'total') {
          return {
            title: 'Totais (Verificados x Não verificados)',
            dataset: 'Verificados x Não verificados',
          };
        }

        return {
          title: 'Cadastros',
          dataset: 'Patrimônios Cadastrados',
        };
      };

      const updateCharts = (labels, data, labelText) => {
        currentLabels = labels;
        currentValues = data;

        barChart.data.labels = labels;
        barChart.data.datasets[0].data = data;
        barChart.data.datasets[0].label = labelText;

        const { bg, border } = getColorSet(labels, selectedUf);
        barChart.data.datasets[0].backgroundColor = bg;
        barChart.data.datasets[0].borderColor = border;
        barChart.update();

        const overall = data.reduce((sum, val) => sum + Number(val || 0), 0);
        let selectedValue = overall;
        if (currentView === 'uf' && selectedUf !== 'ALL') {
          const idx = labels.findIndex(l => String(l).toUpperCase() === String(selectedUf).toUpperCase());
          selectedValue = idx >= 0 ? Number(data[idx] || 0) : 0;
        }

        setTotals(overall, selectedUf, selectedValue);
      };

      const renderUfButtons = (labels) => {
        if (!ufButtonsEl) return;
        ufButtonsEl.innerHTML = '';

        const makeButton = (label, isActive) => {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.textContent = label;
          btn.className = [
            'px-3', 'py-2', 'rounded-md', 'text-sm', 'font-medium', 'border', 'transition-all', 'duration-200',
            isActive 
              ? 'bg-plansul-blue text-white border-plansul-blue shadow-sm' 
              : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-plansul-blue dark:hover:border-blue-500 hover:shadow-sm'
          ].join(' ');
          btn.addEventListener('click', () => {
            selectedUf = label === 'Todos' ? 'ALL' : label;
            updateCharts(currentLabels, currentValues, getViewLabels().dataset);
            renderUfButtons(labels);
          });
          return btn;
        };

        ufButtonsEl.appendChild(makeButton('Todos', selectedUf === 'ALL'));
        labels.forEach(label => {
          const isActive = selectedUf.toUpperCase() === String(label).toUpperCase();
          ufButtonsEl.appendChild(makeButton(label, isActive));
        });
      };

      const applyViewUi = () => {
        if (chartTitleEl) chartTitleEl.textContent = getViewLabels().title;
        if (periodWrapEl) periodWrapEl.style.display = currentView === 'cadastros' ? 'block' : 'none';
        if (ufButtonsWrapEl) ufButtonsWrapEl.classList.toggle('hidden', currentView !== 'uf');
      };

      const fetchCadastros = async (period) => {
        const res = await fetch(`/dashboard/data?period=${encodeURIComponent(period)}&status_mode=${encodeURIComponent(currentStatusMode)}`, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!res.ok) throw new Error('Erro ao buscar cadastros');
        const json = await res.json();
        return { labels: json.labels || [], data: json.data || [] };
      };

      const fetchUf = async () => {
        const res = await fetch(`/dashboard/uf-data?status_mode=${encodeURIComponent(currentStatusMode)}`, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!res.ok) throw new Error('Erro ao buscar UFs');
        const json = await res.json();
        return { labels: json.labels || [], data: json.data || [] };
      };

      const fetchTotal = async () => {
        const res = await fetch(`/dashboard/total-data?status_mode=${encodeURIComponent(currentStatusMode)}`, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!res.ok) throw new Error('Erro ao buscar totais');
        const json = await res.json();
        return { labels: json.labels || [], data: json.data || [] };
      };

      const updateData = async () => {
        try {
          applyViewUi();
          if (currentView === 'uf') {
            selectedUf = 'ALL';
            const { labels, data } = await fetchUf();
            renderUfButtons(labels);
            updateCharts(labels, data, getViewLabels().dataset);
            if (ufButtonsWrapEl && data.length === 0) {
              ufButtonsWrapEl.classList.add('hidden');
            }
          } else if (currentView === 'total') {
            selectedUf = 'ALL';
            const { labels, data } = await fetchTotal();
            updateCharts(labels, data, getViewLabels().dataset);
            if (ufButtonsEl) ufButtonsEl.innerHTML = '';
          } else {
            const { labels, data } = await fetchCadastros(currentPeriod);
            updateCharts(labels, data, getViewLabels().dataset);
            if (ufButtonsEl) ufButtonsEl.innerHTML = '';
          }

          const isEmpty = currentValues.length === 0;
          if (filterStatusEl) filterStatusEl.style.display = isEmpty ? 'block' : 'none';
        } catch (err) {
          console.error(err);
        }
      };

      const viewButtons = Array.from(document.querySelectorAll('.view-btn'));
      const periodButtons = Array.from(document.querySelectorAll('.filter-btn'));
      const statusButtons = Array.from(document.querySelectorAll('.status-btn'));
      const viewButtonStyle = {
        activeClasses: ['bg-plansul-orange', 'text-white', 'shadow-sm'],
        inactiveClasses: ['bg-transparent', 'text-gray-700', 'dark:text-gray-300'],
      };
      const periodButtonStyle = {
        activeClasses: ['bg-plansul-blue', 'text-white', 'shadow-sm'],
        inactiveClasses: ['bg-white', 'dark:bg-gray-800', 'text-gray-700', 'dark:text-gray-300'],
      };
      const statusButtonStyle = {
        activeClasses: ['bg-plansul-blue', 'text-white', 'border-plansul-blue', 'shadow-sm'],
        inactiveClasses: ['bg-white', 'dark:bg-gray-800', 'text-gray-700', 'dark:text-gray-300', 'border-gray-300', 'dark:border-gray-600'],
      };

      viewButtons.forEach(btn => {
        btn.addEventListener('click', () => {
          currentView = btn.getAttribute('data-view') || 'cadastros';
          setActive(viewButtons, btn, viewButtonStyle);
          updateData();
        });
      });

      periodButtons.forEach(btn => {
        btn.addEventListener('click', () => {
          currentPeriod = btn.getAttribute('data-period') || 'week';
          setActive(periodButtons, btn, periodButtonStyle);
          if (currentView !== 'uf') updateData();
        });
      });

      statusButtons.forEach(btn => {
        btn.addEventListener('click', () => {
          currentStatusMode = btn.getAttribute('data-status') || 'ativos';
          setActive(statusButtons, btn, statusButtonStyle);
          updateData();
        });
      });


      const barChart = new Chart(barCtx, {
        type: 'bar',
        data: {
          labels: initialLabels,
          datasets: [{
            label: 'Patrimônios Cadastrados',
            data: initialValues,
            backgroundColor: initialLabels.map((_, idx) => palette[idx % palette.length].bg),
            borderColor: initialLabels.map((_, idx) => palette[idx % palette.length].border),
            borderWidth: 1
          }]
        },
        plugins: [dataLabelPlugin],
        options: {
          onClick: (_e, items) => {
            if (currentView !== 'uf' || !items.length) return;
            const idx = items[0].index;
            const label = barChart.data.labels?.[idx];
            if (label) {
              selectedUf = String(label);
              renderUfButtons(currentLabels);
              updateCharts(currentLabels, currentValues, getViewLabels().dataset);
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                color: document.documentElement.classList.contains('dark') ? '#cbd5e1' : '#6b7280'
              }
            },
            x: {
              ticks: {
                color: document.documentElement.classList.contains('dark') ? '#cbd5e1' : '#6b7280'
              }
            }
          },
          plugins: {
            legend: {
              labels: {
                color: document.documentElement.classList.contains('dark') ? '#cbd5e1' : '#6b7280'
              }
            }
          }
        }
      });

      const defaultViewBtn = document.querySelector('.view-btn[data-view="uf"]');
      const defaultPeriodBtn = document.querySelector('.filter-btn[data-period="week"]');
      const defaultStatusBtn = document.querySelector(`.status-btn[data-status="${currentStatusMode}"]`) || document.querySelector('.status-btn[data-status="ativos"]');
      setActive(viewButtons, defaultViewBtn, viewButtonStyle);
      setActive(periodButtons, defaultPeriodBtn, periodButtonStyle);
      setActive(statusButtons, defaultStatusBtn, statusButtonStyle);

      updateData();
    });

    document.addEventListener('DOMContentLoaded', function() {
      const formatCurrency = (value) => Number(value || 0).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL'
      });

      const createCurrencyChart = (canvasId, options = {}) => {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;

        let labels = [];
        let values = [];

        try {
          labels = JSON.parse(canvas.getAttribute('data-labels') || '[]');
          values = JSON.parse(canvas.getAttribute('data-values') || '[]');
        } catch (error) {
          console.error('Falha ao carregar dados do gráfico de deslocamento.', error);
          return;
        }

        if (!labels.length || !values.length) {
          return;
        }

        const ctx = canvas.getContext('2d');
        const dark = document.documentElement.classList.contains('dark');
        const horizontal = options.indexAxis === 'y';

        new Chart(ctx, {
          type: 'bar',
          data: {
            labels,
            datasets: [{
              label: options.label || 'Custo',
              data: values,
              backgroundColor: options.backgroundColor || 'rgba(249, 115, 22, 0.78)',
              borderColor: options.borderColor || 'rgba(249, 115, 22, 1)',
              borderWidth: 1,
              borderRadius: 6,
            }]
          },
          options: {
            indexAxis: options.indexAxis || 'x',
            maintainAspectRatio: false,
            scales: {
              y: {
                beginAtZero: !horizontal,
                ticks: {
                  color: dark ? '#cbd5e1' : '#6b7280',
                  callback: horizontal ? undefined : (value) => formatCurrency(value),
                },
                grid: {
                  color: dark ? 'rgba(71, 85, 105, 0.35)' : 'rgba(203, 213, 225, 0.6)',
                }
              },
              x: {
                beginAtZero: horizontal,
                ticks: {
                  color: dark ? '#cbd5e1' : '#6b7280',
                  callback: horizontal ? (value) => formatCurrency(value) : undefined,
                },
                grid: {
                  display: horizontal,
                  color: dark ? 'rgba(71, 85, 105, 0.2)' : 'rgba(203, 213, 225, 0.4)',
                }
              }
            },
            plugins: {
              legend: {
                display: false,
              },
              tooltip: {
                callbacks: {
                  label: (context) => formatCurrency(context.parsed.x ?? context.parsed.y ?? 0),
                }
              }
            }
          }
        });
      };

      // Os gráficos de deslocamento são inicializados de forma lazy em switchTab()
      // para evitar renderização com canvas oculto (tamanho zero).
    });

    // Switch de abas do dashboard
    let deslocamentoChartsInit = false;

    function switchTab(tab) {
      const secaoPatrimonio   = document.getElementById('secaoPatrimonio');
      const secaoDeslocamento = document.getElementById('secaoDeslocamento');
      const btnPatrimonio     = document.getElementById('tabPatrimonio');
      const btnDeslocamento   = document.getElementById('tabDeslocamento');

      const activeClasses   = ['bg-plansul-blue', 'text-white', 'shadow-sm', 'border-transparent'];
      const inactiveClasses = ['bg-white', 'dark:bg-gray-800', 'text-gray-700', 'dark:text-gray-300', 'border', 'border-gray-300', 'dark:border-gray-600'];

      if (tab === 'patrimonio') {
        secaoPatrimonio.style.display   = '';
        secaoDeslocamento.style.display = 'none';
        activeClasses.forEach(c => btnPatrimonio.classList.add(c));
        inactiveClasses.forEach(c => btnPatrimonio.classList.remove(c));
        inactiveClasses.forEach(c => btnDeslocamento.classList.add(c));
        activeClasses.forEach(c => btnDeslocamento.classList.remove(c));
      } else {
        secaoPatrimonio.style.display   = 'none';
        secaoDeslocamento.style.display = '';
        activeClasses.forEach(c => btnDeslocamento.classList.add(c));
        inactiveClasses.forEach(c => btnDeslocamento.classList.remove(c));
        inactiveClasses.forEach(c => btnPatrimonio.classList.add(c));
        activeClasses.forEach(c => btnPatrimonio.classList.remove(c));

        // Inicializa gráficos de deslocamento apenas na primeira vez que a aba abre
        if (!deslocamentoChartsInit) {
          deslocamentoChartsInit = true;
          const formatCurrencyLazy = (value) => Number(value || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
          const createCurrencyChartLazy = (canvasId, options = {}) => {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;
            let labels = [], values = [];
            try {
              labels = JSON.parse(canvas.getAttribute('data-labels') || '[]');
              values = JSON.parse(canvas.getAttribute('data-values') || '[]');
            } catch (e) { return; }
            if (!labels.length || !values.length) return;
            const ctx = canvas.getContext('2d');
            const dark = document.documentElement.classList.contains('dark');
            const horizontal = options.indexAxis === 'y';
            new Chart(ctx, {
              type: 'bar',
              data: {
                labels,
                datasets: [{
                  label: options.label || 'Custo',
                  data: values,
                  backgroundColor: options.backgroundColor || 'rgba(249, 115, 22, 0.78)',
                  borderColor: options.borderColor || 'rgba(249, 115, 22, 1)',
                  borderWidth: 1,
                  borderRadius: 6,
                }]
              },
              options: {
                indexAxis: options.indexAxis || 'x',
                maintainAspectRatio: false,
                scales: {
                  y: {
                    beginAtZero: !horizontal,
                    ticks: { color: dark ? '#cbd5e1' : '#6b7280', callback: horizontal ? undefined : (v) => formatCurrencyLazy(v) },
                    grid: { color: dark ? 'rgba(71,85,105,0.35)' : 'rgba(203,213,225,0.6)' }
                  },
                  x: {
                    beginAtZero: horizontal,
                    ticks: { color: dark ? '#cbd5e1' : '#6b7280', callback: horizontal ? (v) => formatCurrencyLazy(v) : undefined },
                    grid: { display: horizontal, color: dark ? 'rgba(71,85,105,0.2)' : 'rgba(203,213,225,0.4)' }
                  }
                },
                plugins: {
                  legend: { display: false },
                  tooltip: { callbacks: { label: (ctx) => formatCurrencyLazy(ctx.parsed.x ?? ctx.parsed.y ?? 0) } }
                }
              }
            });
          };
          createCurrencyChartLazy('deslocamentoMensalChart', {
            label: 'Custo mensal de deslocamento',
            backgroundColor: 'rgba(59, 130, 246, 0.78)',
            borderColor: 'rgba(59, 130, 246, 1)',
          });
          createCurrencyChartLazy('deslocamentoProjetosChart', {
            label: 'Custo por projeto',
            backgroundColor: 'rgba(16, 185, 129, 0.78)',
            borderColor: 'rgba(16, 185, 129, 1)',
            indexAxis: 'y',
          });
        }
      }
    }

    // Se a URL tem ?frete_year=, abrir aba de deslocamento automaticamente
    document.addEventListener('DOMContentLoaded', function () {
      if (new URLSearchParams(window.location.search).has('frete_year')) {
        switchTab('deslocamento');
      }
    });
  </script>
  @endpush
</x-app-layout>
