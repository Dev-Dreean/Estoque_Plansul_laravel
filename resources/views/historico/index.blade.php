<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Histórico de Movimentações
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="w-full px-2 sm:px-4 lg:px-8 2xl:px-12">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-clock class="w-5 h-5 text-indigo-500" />
                            <h3 class="font-semibold">Histórico recente</h3>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-300">
                            Resultados: <span class="font-semibold">{{ $historicos->total() }}</span>
                        </div>
                    </div>
                    <div x-data="{ open: true }" class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg mb-6">
                        <div @click="open = !open" class="flex justify-between items-center cursor-pointer">
                            <h3 class="font-semibold text-lg">Filtros de Busca</h3>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                        <div x-show="open" x-transition class="mt-4" style="display: none;">
                            <form method="GET" action="{{ route('historico.index') }}">
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
                                    <div class="min-w-0">
                                        <input type="number" name="nupatr" value="{{ request('nupatr') }}" placeholder="Nº Patr." class="h-10 px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                                    </div>
                                    <div class="min-w-0">
                                        <input type="number" name="codproj" value="{{ request('codproj') }}" placeholder="Cód. Projeto" class="h-10 px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                                    </div>
                                    <div class="min-w-0">
                                        <select name="tipo" class="h-10 px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md">
                                            <option value="">Tipo de evento</option>
                                            @foreach(['projeto','situacao','termo'] as $tp)
                                            <option value="{{ $tp }}" @selected(request('tipo')==$tp)>{{ ucfirst($tp) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div x-data="{query: '{{ request('usuario') }}', open: false, items: @js($usuarios), get filtered(){ const q=this.query?.toLowerCase()||''; return this.items.filter(u=>u.toLowerCase().includes(q)).slice(0,10); }, select(v){ this.query=v; this.open=false; }}" class="relative min-w-0">
                                        <input type="text" name="usuario" x-model="query" @focus="open=true" @input="open=true" @keydown.escape.prevent="open=false" placeholder="Usuário" class="h-10 px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" autocomplete="off" />
                                        <div x-show="open && filtered.length" @mousedown.prevent class="absolute z-20 mt-1 w-full max-h-60 overflow-y-auto rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg">
                                            <ul class="py-1 text-sm text-gray-700 dark:text-gray-200">
                                                <template x-for="item in filtered" :key="item">
                                                    <li>
                                                        <button type="button" class="w-full text-left px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700" @click="select(item)" x-text="item"></button>
                                                    </li>
                                                </template>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="min-w-0">
                                        <input type="date" name="data_inicio" value="{{ request('data_inicio') }}" class="h-10 px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                                    </div>
                                    <div class="min-w-0">
                                        <input type="date" name="data_fim" value="{{ request('data_fim') }}" class="h-10 px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center justify-between mt-4 gap-4">
                                    <div class="flex items-center gap-3">
                                        <x-primary-button class="h-10 px-4">{{ __('Filtrar') }}</x-primary-button>
                                        <a href="{{ route('historico.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md">Limpar</a>
                                    </div>

                                    <label class="flex items-center gap-2 ml-auto shrink-0 w-full sm:w-auto">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">Itens por página</span>
                                        <select name="per_page" class="h-10 pl-3 pr-8 w-24 text-center border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm">
                                            @foreach([10,30,50,100,200] as $opt)
                                            <option value="{{ $opt }}" @selected(request('per_page', 30)==$opt)>{{ $opt }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg w-full z-0">
                        <table class="w-full text-base text-left rtl:text-right text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3">Nº Pat.</th>
                                    <th class="px-4 py-3">Cód. Projeto</th>
                                    <th class="px-4 py-3">Detalhe</th>
                                    <th class="px-4 py-3">Usuário</th>
                                    <th class="px-4 py-3">Data Operação</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($historicos as $h)
                                @php
                                $tipo = strtolower($h->TIPO ?? '');
                                $de = $h->VALOR_ANTIGO ?? null;
                                $para = $h->VALOR_NOVO ?? null;
                                $deU = \Illuminate\Support\Str::upper($de ?? '—');
                                $paraU = \Illuminate\Support\Str::upper($para ?? '—');
                                $toneClasses = function($val) {
                                $v = \Illuminate\Support\Str::upper($val ?? '');
                                if (\Illuminate\Support\Str::contains($v, 'BAIXA')) return 'red';
                                if (\Illuminate\Support\Str::contains($v, 'CONSERTO')) return 'amber';
                                if (\Illuminate\Support\Str::contains($v, 'DISPOSI')) return 'green';
                                if (\Illuminate\Support\Str::contains($v, 'USO')) return 'amber';
                                return 'gray';
                                };
                                $border = match($toneClasses($para)) {
                                'red' => 'border-red-500/70',
                                'amber' => 'border-amber-500/70',
                                'green' => 'border-green-500/70',
                                default => 'border-gray-500/50'
                                };
                                $badge = function($val) use ($toneClasses) {
                                return match($toneClasses($val)) {
                                'red' => 'bg-red-100 text-red-800 ring-red-600/20 dark:bg-red-900/30 dark:text-red-300',
                                'amber' => 'bg-amber-100 text-amber-800 ring-amber-600/20 dark:bg-amber-900/30 dark:text-amber-300',
                                'green' => 'bg-green-100 text-green-800 ring-green-600/20 dark:bg-green-900/30 dark:text-green-300',
                                default => 'bg-gray-100 text-gray-800 ring-gray-600/20 dark:bg-gray-900/30 dark:text-gray-300',
                                };
                                };
                                @endphp
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 text-sm border-l-4 {{ $border }}">
                                    <td class="px-4 py-2">{{ $h->NUPATR }}</td>
                                    <td class="px-4 py-2">{{ $h->CODPROJ }}</td>
                                    <td class="px-4 py-2">
                                        @if($tipo === 'termo')
                                        @if(is_null($de) && !is_null($para))
                                        Atribuído: <span class="font-mono">{{ $para }}</span>
                                        @elseif(!is_null($de) && is_null($para))
                                        Removido: <span class="font-mono">{{ $de }}</span>
                                        @else
                                        <span class="font-mono">{{ $de ?? '—' }}</span>
                                        <x-heroicon-o-arrow-right class="w-4 h-4 mx-2 inline text-gray-400" />
                                        <span class="font-mono">{{ $para ?? '—' }}</span>
                                        @endif
                                        @elseif($tipo === 'situacao' || $tipo === 'projeto')
                                        @php
                                        $deFmt = $tipo==='situacao' ? $deU : ($de ?? '—');
                                        $paraFmt = $tipo==='situacao' ? $paraU : ($para ?? '—');
                                        @endphp
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-amber-100 text-amber-800 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-900/30 dark:text-amber-200">{{ $deFmt }}</span>
                                            <x-heroicon-o-arrow-right class="w-4 h-4 mx-2 inline text-gray-400" />
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-green-100 text-green-800 ring-1 ring-inset ring-green-600/20 dark:bg-green-900/30 dark:text-green-200">{{ $paraFmt }}</span>
                                        @else
                                        —
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $h->USUARIO }}</td>
                                    <td class="px-4 py-2 font-semibold">{{ \Carbon\Carbon::parse($h->DTOPERACAO)->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">Nenhum registro encontrado.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($historicos->hasPages())
                    <div class="mt-4">
                        {{ $historicos->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>