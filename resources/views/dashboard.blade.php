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

          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
              <h3 class="font-semibold text-lg mb-4">Top 5 Usuários com Mais Cadastros</h3>
              <ul class="space-y-2">
                @forelse($topCadastradores as $cadastrador)
                <li class="flex justify-between items-center text-sm">
                  <span>{{ $cadastrador->NOMEUSER }}</span>
                  <span
                    class="font-bold bg-plansul-orange text-white px-2 py-1 rounded-full">{{ $cadastrador->total }}</span>
                </li>
                @empty
                <li>Nenhum cadastro encontrado.</li>
                @endforelse
              </ul>
            </div>
          </div>
        </div>

        <div class="lg:col-span-2 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900 dark:text-gray-100">
            <div class="flex items-center justify-between mb-4">
              <h3 class="font-semibold text-lg">Cadastros</h3>
              <div class="flex items-center space-x-2">
                <div class="space-x-2">
                  <button data-period="week" class="filter-btn bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 px-3 py-1 rounded">Semana</button>
                  <button data-period="month" class="filter-btn bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 px-3 py-1 rounded">Mês</button>
                  <button data-period="year" class="filter-btn bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 px-3 py-1 rounded">Ano</button>
                </div>
                <div class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                  Total: <span id="filterTotal" class="font-bold">0</span>
                </div>
              </div>
            </div>

            <canvas id="cadastrosChart"
              data-labels='{!! json_encode($cadastrosSemanaLabels) !!}'
              data-values='{!! json_encode($cadastrosSemanaData) !!}'></canvas>
            <p id="filterStatus" x-cloak class="text-sm text-gray-500 dark:text-gray-400 mt-2 hidden">Nenhum movimento no período selecionado.</p>
          </div>
        </div>

      </div>
    </div>
  </div>

  {{-- Slot para o JavaScript do Gráfico --}}
  @push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const canvas = document.getElementById('cadastrosChart');
      if (!canvas) return;
      const ctx = canvas.getContext('2d');

      // Util: parse JSON safely
      const safeParse = (str) => {
        try {
          return JSON.parse(str);
        } catch (e) {
          return [];
        }
      };

      // Dados iniciais do canvas
      const initialLabels = safeParse(canvas.getAttribute('data-labels') || '[]');
      const initialValues = safeParse(canvas.getAttribute('data-values') || '[]');

      // Cria o chart e o mantém para atualizações
      const chart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: initialLabels,
          datasets: [{
            label: 'Patrimônios Cadastrados',
            data: initialValues,
            backgroundColor: 'rgba(0, 82, 155, 0.6)',
            borderColor: 'rgba(0, 82, 155, 1)',
            borderWidth: 1
          }]
        },
        options: {
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

      const filterTotalEl = document.getElementById('filterTotal');
      const filterStatusEl = document.getElementById('filterStatus');

      // Função que busca dados filtrados e atualiza o chart
      const fetchAndUpdate = async (period) => {
        try {
          const res = await fetch(`/dashboard/data?period=${encodeURIComponent(period)}`, {
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            }
          });
          if (!res.ok) throw new Error('Network response was not ok');
          const json = await res.json();
          const labels = json.labels || [];
          const data = json.data || [];
          chart.data.labels = labels;
          chart.data.datasets[0].data = data;
          chart.update();

          // Atualiza total e estado vazio
          const total = data.reduce((s, v) => s + Number(v || 0), 0);
          if (filterTotalEl) filterTotalEl.textContent = total;
          if (filterStatusEl) {
            if (data.length === 0) {
              filterStatusEl.style.display = 'block';
            } else {
              filterStatusEl.style.display = 'none';
            }
          }
        } catch (err) {
          console.error('Erro ao buscar dados do dashboard:', err);
        }
      };

      // Listeners dos botões de filtro
      const filterBtns = Array.from(document.querySelectorAll('.filter-btn'));
      const setActiveBtn = (active) => {
        filterBtns.forEach(b => {
          b.classList.remove('bg-plansul-blue', 'text-white');
          b.classList.add('bg-white', 'dark:bg-gray-800', 'text-gray-800', 'dark:text-gray-200');
        });
        if (active) {
          active.classList.remove('bg-white', 'dark:bg-gray-800', 'text-gray-800', 'dark:text-gray-200');
          active.classList.add('bg-plansul-blue', 'text-white');
        }
      };

      filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
          const period = btn.getAttribute('data-period') || 'week';
          setActiveBtn(btn);
          fetchAndUpdate(period);
        });
      });

      // Ativa 'week' por padrão e atualiza total
      const defaultBtn = document.querySelector('.filter-btn[data-period="week"]');
      if (defaultBtn) {
        setActiveBtn(defaultBtn);
        fetchAndUpdate('week');
      }
    });
  </script>
  @endpush
</x-app-layout>