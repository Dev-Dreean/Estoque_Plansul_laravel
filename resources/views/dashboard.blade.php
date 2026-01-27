<x-app-layout>
  <div class="py-12">
    <div class="w-full sm:px-6 lg:px-8">
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
                <div class="h-2 rounded-full bg-emerald-500" style="width: {{ (int) ($verificadosStats['percent'] ?? 0) }}%"></div>
              </div>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ (int) ($verificadosStats['percent'] ?? 0) }}% conferidos</p>
            </div>
          </div>
          @endif

          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
              <h3 class="font-semibold text-lg mb-4">Top 5 Usu&aacute;rios com Mais Cadastros</h3>
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
            <div class="mb-8 space-y-5">
              <!-- Seletor de Visão -->
              <div>
                <label class="text-xs uppercase tracking-wide font-semibold text-gray-600 dark:text-gray-400 mb-2 block">Visualizar por</label>
                <div class="inline-flex items-center gap-2 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                  <button data-view="uf" class="view-btn px-4 py-2 rounded-md text-sm font-semibold transition-all duration-200 bg-plansul-orange text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-plansul-orange focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                    Estados
                  </button>
                  <button data-view="cadastros" class="view-btn px-4 py-2 rounded-md text-sm font-semibold transition-all duration-200 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-plansul-orange">
                    Cadastros
                  </button>
                </div>
              </div>

              <!-- Filtros de Período -->
              <div id="periodWrap">
                <label class="text-xs uppercase tracking-wide font-semibold text-gray-600 dark:text-gray-400 mb-3 block">Período</label>
                <div class="inline-flex items-center gap-2 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                  <button data-period="day" class="filter-btn px-4 py-2 rounded-md text-sm font-medium transition-all duration-200 bg-white dark:bg-gray-800 border border-transparent text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-plansul-blue focus:ring-offset-2 dark:focus:ring-offset-gray-800">Dia</button>
                  <button data-period="week" class="filter-btn px-4 py-2 rounded-md text-sm font-medium transition-all duration-200 bg-white dark:bg-gray-800 border border-transparent text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-plansul-blue focus:ring-offset-2 dark:focus:ring-offset-gray-800">Semana</button>
                  <button data-period="month" class="filter-btn px-4 py-2 rounded-md text-sm font-medium transition-all duration-200 bg-white dark:bg-gray-800 border border-transparent text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-plansul-blue focus:ring-offset-2 dark:focus:ring-offset-gray-800">Mês</button>
                  <button data-period="year" class="filter-btn px-4 py-2 rounded-md text-sm font-medium transition-all duration-200 bg-white dark:bg-gray-800 border border-transparent text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-plansul-blue focus:ring-offset-2 dark:focus:ring-offset-gray-800">Ano</button>
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
    </div>
  </div>

  @push('scripts')
  <script>
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
            updateCharts(currentLabels, currentValues, currentView === 'uf' ? 'Lançamentos por UF' : 'Patrimônios Cadastrados');
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
        if (chartTitleEl) chartTitleEl.textContent = currentView === 'uf' ? 'Lançamentos por Estado (UF)' : 'Cadastros';
        if (periodWrapEl) periodWrapEl.style.display = currentView === 'uf' ? 'none' : 'flex';
        if (ufButtonsWrapEl) ufButtonsWrapEl.classList.toggle('hidden', currentView !== 'uf');
      };

      const fetchCadastros = async (period) => {
        const res = await fetch(`/dashboard/data?period=${encodeURIComponent(period)}`, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!res.ok) throw new Error('Erro ao buscar cadastros');
        const json = await res.json();
        return { labels: json.labels || [], data: json.data || [] };
      };

      const fetchUf = async () => {
        const res = await fetch(`/dashboard/uf-data`, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!res.ok) throw new Error('Erro ao buscar UFs');
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
            updateCharts(labels, data, 'Lançamentos por UF');
            if (ufButtonsWrapEl && data.length === 0) {
              ufButtonsWrapEl.classList.add('hidden');
            }
          } else {
            const { labels, data } = await fetchCadastros(currentPeriod);
            updateCharts(labels, data, 'Patrimônios Cadastrados');
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
      const viewButtonStyle = {
        activeClasses: ['bg-plansul-orange', 'text-white', 'shadow-sm'],
        inactiveClasses: ['bg-transparent', 'text-gray-700', 'dark:text-gray-300'],
      };
      const periodButtonStyle = {
        activeClasses: ['bg-plansul-blue', 'text-white', 'shadow-sm'],
        inactiveClasses: ['bg-white', 'dark:bg-gray-800', 'text-gray-700', 'dark:text-gray-300'],
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
          if (currentView === 'cadastros') updateData();
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
              updateCharts(currentLabels, currentValues, 'Lançamentos por UF');
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
      setActive(viewButtons, defaultViewBtn, viewButtonStyle);
      setActive(periodButtons, defaultPeriodBtn, periodButtonStyle);

      updateData();
    });
  </script>
  @endpush
</x-app-layout>
