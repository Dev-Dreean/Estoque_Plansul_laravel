<x-app-layout>
  {{-- Abas de navegação do patrimônio --}}
  <x-patrimonio-nav-tabs />

  <div class="py-12">
    <div class="w-full sm:px-6 lg:px-8">
      <div class="section">
        <div class="section-body">
          <div x-data="{ open: false }" class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg mb-6">
            <div class="flex justify-between items-center">
              <h3 class="font-semibold text-lg">Filtros de Busca</h3>
              <button type="button" @click="open = !open" aria-expanded="open" aria-controls="filtros-historico" class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
                <span class="sr-only">Expandir filtros</span>
              </button>
            </div>
            <div x-show="open" x-transition class="mt-4" style="display: none;">
              <form method="GET" action="{{ route('historico.index') }}" id="filtros-historico">
                <div class="flex flex-row gap-3 sm:gap-4">
                  <div class="flex-1 min-w-[120px]">
                    <label for="nupatr" class="sr-only">Nº Patrimônio</label>
                    <input type="number" id="nupatr" name="nupatr" value="{{ request('nupatr') }}" placeholder="Nº Patr." class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                  </div>
                  <div class="flex-1 min-w-[120px]">
                    <label for="codproj" class="sr-only">Cód. Projeto</label>
                    <input type="number" id="codproj" name="codproj" value="{{ request('codproj') }}" placeholder="Cód. Projeto" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                  </div>
                  <div class="flex-1 min-w-[120px]">
                    <label for="tipo" class="sr-only">Tipo de evento</label>
                    <select id="tipo" name="tipo" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md">
                      <option value="">Tipo de evento</option>
                      @foreach(['local','projeto','situacao','termo','conferido'] as $tp)
                      <option value="{{ $tp }}" @selected(request('tipo')==$tp)>{{ ucfirst($tp) }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="flex-1 min-w-[120px]">
                    <label for="usuario" class="sr-only">Usuário</label>
                    <input list="usuariosList" id="usuario" name="usuario" value="{{ request('usuario') }}" placeholder="Usuário" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                    <datalist id="usuariosList">
                      @foreach($usuarios as $u)
                      <option value="{{ $u }}" />
                      @endforeach
                    </datalist>
                  </div>
                  <div class="flex-1 min-w-[120px]">
                    <label for="data_inicio" class="sr-only">Data início</label>
                    <input type="date" id="data_inicio" name="data_inicio" value="{{ request('data_inicio') }}" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                  </div>
                  <div class="flex-1 min-w-[120px]">
                    <label for="data_fim" class="sr-only">Data fim</label>
                    <input type="date" id="data_fim" name="data_fim" value="{{ request('data_fim') }}" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
                  </div>
                </div>

                <div class="flex flex-wrap items-center justify-between mt-4 gap-4">
                  <div class="flex items-center gap-3">
                    <x-primary-button class="h-10 px-4">{{ __('Filtrar') }}</x-primary-button>
                    <a href="{{ route('historico.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md">Limpar</a>
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

          <div class="relative overflow-x-auto shadow-md sm:rounded-lg w-full z-0">
            <table class="w-full text-xs sm:text-[13px] md:text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
              <thead class="text-[13px] sm:text-sm md:text-[15px] lg:text-base text-gray-900 dark:text-gray-100 uppercase bg-gray-50 dark:bg-gray-700 font-bold">
                <tr class="font-semibold">
                  <th class="px-2 py-2 w-[90px] sm:w-[110px]">Nº Pat.</th>
                  <th class="px-2 py-2 w-[100px] sm:w-[120px]">Projeto</th>
                  <th class="px-2 py-2">Detalhe</th>
                  <th class="px-2 py-2 text-center w-[140px] sm:w-[180px]">Usuário</th>
                  <th class="px-2 py-2 w-[120px] sm:w-[140px]">Data</th>
                </tr>
              </thead>
              <tbody class="font-semibold text-[13px] sm:text-sm md:text-[15px] lg:text-base">
                @forelse($historicos as $h)
                @php
                $tipo = strtolower($h->TIPO ?? '');
                $de = $h->VALOR_ANTIGO ?? null;
                $para = $h->VALOR_NOVO ?? null;
                $deU = \Illuminate\Support\Str::upper($de ?? '—');
                $paraU = \Illuminate\Support\Str::upper($para ?? '—');
                $toneClasses = function($val) use ($tipo) {
                if ($tipo === 'conferido') {
                  $v = \Illuminate\Support\Str::upper(trim((string) ($val ?? '')));
                  if (in_array($v, ['S', '1', 'SIM', 'TRUE', 'T', 'Y', 'YES', 'ON'], true)) return 'green';
                  if (in_array($v, ['N', '0', 'NAO', 'NO', 'FALSE', 'F', 'OFF'], true)) return 'red';
                  return 'gray';
                }
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
                <tr class="tr-hover text-sm border-b dark:border-gray-700 border-l-4 {{ $border }}">
                  <td class="px-2 py-2 whitespace-nowrap truncate font-semibold" title="{{ $h->NUPATR }}">{{ $h->NUPATR }}</td>
                  <td class="px-2 py-2 whitespace-nowrap truncate font-medium" title="{{ $h->CODPROJ }}">{{ $h->CODPROJ }}</td>
                  <td class="px-2 py-2">
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
                    @elseif($tipo === 'local')
                    {{-- Mostrar nomes de locais em vez de apenas códigos --}}
                    <div class="flex items-center gap-2 text-xs">
                      <span class="px-2 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 rounded">
                        @if(!empty($h->LOC_ANTIGO_NOME))
                          {{ $de }} - {{ $h->LOC_ANTIGO_NOME }}
                        @else
                          {{ $de ?? '—' }}
                        @endif
                      </span>
                      <x-heroicon-o-arrow-right class="w-4 h-4 text-gray-400" />
                      <span class="px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 rounded">
                        @if(!empty($h->LOC_NOVO_NOME))
                          {{ $para }} - {{ $h->LOC_NOVO_NOME }}
                        @else
                          {{ $para ?? '—' }}
                        @endif
                      </span>
                    </div>
                    
                    @elseif($tipo === 'projeto')
                    {{-- Mostrar nomes de projetos em vez de apenas códigos --}}
                    <div class="flex items-center gap-2 text-xs">
                      <span class="px-2 py-1 bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300 rounded">
                        @if(!empty($h->PROJ_ANTIGO_NOME))
                          {{ $de }} - {{ $h->PROJ_ANTIGO_NOME }}
                        @else
                          {{ $de ?? '—' }}
                        @endif
                      </span>
                      <x-heroicon-o-arrow-right class="w-4 h-4 text-gray-400" />
                      <span class="px-2 py-1 bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300 rounded">
                        @if(!empty($h->PROJ_NOVO_NOME))
                          {{ $para }} - {{ $h->PROJ_NOVO_NOME }}
                        @else
                          {{ $para ?? '—' }}
                        @endif
                      </span>
                    </div>
                    
                    @elseif($tipo === 'situacao')
                    @php
                    $deFmt = $tipo==='situacao' ? $deU : ($de ?? '—');
                    $paraFmt = $tipo==='situacao' ? $paraU : ($para ?? '—');
                    @endphp
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $badge($de) }}">{{ $deFmt }}</span>
                    <x-heroicon-o-arrow-right class="w-4 h-4 mx-2 inline text-gray-400" />
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $badge($para) }}">{{ $paraFmt }}</span>
                    
                    @elseif($tipo === 'conferido')
                    @php
                      $toBool = function ($v) {
                        $vv = \Illuminate\Support\Str::upper(trim((string) ($v ?? '')));
                        if (in_array($vv, ['S', '1', 'SIM', 'TRUE', 'T', 'Y', 'YES', 'ON'], true)) return true;
                        if (in_array($vv, ['N', '0', 'NAO', 'NO', 'FALSE', 'F', 'OFF'], true)) return false;
                        return null;
                      };
                      $fromOk = $toBool($de);
                      $toOk = $toBool($para);
                      $fmt = fn ($b) => $b === true ? 'Verificado' : ($b === false ? 'Nao verificado' : '—');
                      $badgeClass = fn ($b) => $b === true
                        ? 'bg-emerald-100 text-emerald-800 ring-emerald-600/20 dark:bg-emerald-900/30 dark:text-emerald-300'
                        : ($b === false
                          ? 'bg-red-100 text-red-800 ring-red-600/20 dark:bg-red-900/30 dark:text-red-300'
                          : 'bg-gray-100 text-gray-800 ring-gray-600/20 dark:bg-gray-900/30 dark:text-gray-300');
                    @endphp
                    <div class="flex flex-col gap-1">
                      <div class="flex items-center gap-2 text-xs">
                        <span class="text-gray-500 italic text-xs">CONFERIDO:</span>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $badgeClass($fromOk) }}">{{ $fmt($fromOk) }}</span>
                        <x-heroicon-o-arrow-right class="w-4 h-4 text-gray-400" />
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $badgeClass($toOk) }}">{{ $fmt($toOk) }}</span>
                      </div>
                      <div class="text-[12px] text-gray-600 dark:text-gray-300">
                        Por: <span class="font-semibold">{{ $h->NM_USUARIO ?? $h->USUARIO }}</span>
                      </div>
                    </div>

                    @else
                    {{-- Tipo desconhecido ou genérico --}}
                    <span class="text-gray-500 italic text-xs">{{ strtoupper($tipo ?? '') }}:</span>
                    <span class="font-mono text-xs">{{ $de ?? '—' }}</span>
                    @if(!is_null($de) && !is_null($para))
                      <x-heroicon-o-arrow-right class="w-3 h-3 mx-1 inline text-gray-400" />
                    @endif
                    <span class="font-mono text-xs">{{ $para ?? '—' }}</span>
                    @endif
                  </td>
                  <td class="px-2 py-2 text-gray-800 dark:text-gray-200">
                    <div class="leading-tight">
                      <div class="font-semibold truncate max-w-[160px] sm:max-w-[220px]" title="{{ $h->NM_USUARIO ?? $h->USUARIO }}">{{ $h->NM_USUARIO ?? $h->USUARIO }}</div>
                      @if(!empty($h->MAT_USUARIO))
                      <div class="text-[11px] text-gray-500">Matrícula: {{ $h->MAT_USUARIO }}</div>
                      @endif
                    </div>
                    @if(!empty($h->CO_AUTOR) && $h->CO_AUTOR !== $h->USUARIO)
                    <div class="mt-1 leading-tight">
                      <div class="text-[12px] text-gray-700 dark:text-gray-300">Co-autor: {{ $h->NM_CO_AUTOR ?? $h->CO_AUTOR }}</div>
                      @if(!empty($h->MAT_CO_AUTOR))
                      <div class="text-[11px] text-gray-500">Matrícula: {{ $h->MAT_CO_AUTOR }}</div>
                      @endif
                    </div>
                    @endif
                  </td>
                  <td class="px-2 py-2 font-medium whitespace-nowrap">{{ \Carbon\Carbon::parse($h->DTOPERACAO)->timezone(config('app.timezone'))->format('d/m H:i') }}</td>
                </tr>
                @empty
                <tr>
                  <td colspan="5" class="px-2 py-6 text-center text-gray-500 dark:text-gray-400">Nenhum registro encontrado.</td>
                </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="mt-4 flex items-center justify-between">
            <div>
              @if($historicos->hasPages())
              <div>
                {{ $historicos->links() }}
              </div>
              @endif
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-300">
              Resultados: <span class="font-semibold">{{ $historicos->total() }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
