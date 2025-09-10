<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl md:text-3xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

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
                        <h3 class="font-semibold text-lg mb-4">Cadastros na Última Semana</h3>
                        <canvas id="cadastrosChart"></canvas>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Slot para o JavaScript do Gráfico --}}
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const ctx = document.getElementById('cadastrosChart').getContext('2d');
                const cadastrosChart = new Chart(ctx, {
                    type: 'bar', // Tipo de gráfico: barra
                    data: {
                        labels: {!! $cadastrosSemanaLabels !!},
                        datasets: [{
                            label: 'Patrimônios Cadastrados',
                            data: {!! $cadastrosSemanaData !!},
                            backgroundColor: 'rgba(0, 82, 155, 0.6)', // Cor plansul-blue com transparência
                            borderColor: 'rgba(0, 82, 155, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: document.documentElement.classList.contains('dark') ? '#cbd5e1' :
                                        '#6b7280'
                                }
                            },
                            x: {
                                ticks: {
                                    color: document.documentElement.classList.contains('dark') ? '#cbd5e1' :
                                        '#6b7280'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                labels: {
                                    color: document.documentElement.classList.contains('dark') ? '#cbd5e1' :
                                        '#6b7280'
                                }
                            }
                        }
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>
