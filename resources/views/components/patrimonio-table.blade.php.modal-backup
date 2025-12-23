{{--
  Componente de Tabela de Patrimônios Reutilizável
  
  USO GERAL:
  <x-patrimonio-table :patrimonios="$patrimonios" :columns="['nupatrimonio', 'descricao']" />
  
  COLUNAS PREDEFINIDAS (patrimonios):
  - nupatrimonio, numof, codobjeto, nmplanta, nuserie, projeto, local, modelo, marca, descricao, situacao, dtaquisicao, dtoperacao, responsavel, cadastrador
  
  COLUNAS CUSTOMIZADAS (via slot):
  - Use @props para colunas customizadas em outras models
  
  @props
  - items: Collection - Coleção de items (patrimonios ou outro modelo)
  - patrimonios: Collection - LEGACY: coleção de patrimônios
  - showCheckbox: bool - Mostra checkbox (padrão: false)
  - showActions: bool - Mostra coluna de ações (padrão: true)
  - clickable: bool - Linha clicável (padrão: true)
  - columns: array - Colunas a exibir
  - onRowClick: string - Rota ou JS para click
  - customColumns: array - Colunas customizadas (nome => label)
--}}

@props([
    'items' => null,
    'patrimonios' => null,
    'showCheckbox' => false,
    'showActions' => true,
    'clickable' => true,
    'columns' => [],
    'onRowClick' => null,
    'emptyMessage' => 'Nenhum registro encontrado.',
    'checkboxClass' => '',
    'onCheckboxChange' => '',
    'onSelectAllChange' => '',
    'customColumns' => [],
    'actionsView' => null,
    'density' => 'normal',
    'sortable' => true,
    'showCheckboxHeader' => true,
])

@php
  $compact = ($density === 'compact');
  $cellPadding = $compact ? 'px-2 py-1.5' : 'px-2 py-2';
  $headerPadding = $cellPadding;
  $tableText = $compact ? 'text-[10px]' : 'text-[11px]';
  // Compatibilidade com nome antigo
  $data = $items ?? $patrimonios ?? collect([]);
  
  // Colunas disponíveis para patrimônios (ordem compacta e rótulos curtos)
  $availableColumns = [
    'nupatrimonio' => 'Nº Pat.',
    'conferido' => 'Conf.',
    'numof' => 'OF',
    'codobjeto' => 'Obj.',
    'projeto' => 'Proj.',
    'local' => 'Local',
    'modelo' => 'Mod.',
    'marca' => 'Marca',
    'descricao' => 'Desc.',
    'situacao' => 'Status',
    'dtaquisicao' => 'Dt. Aquis.',
    'dtoperacao' => 'Dt. Cad.',
    'responsavel' => 'Resp.',
    'cadastrador' => 'Cad. Por',
    // Colunas auxiliares (mantidas caso sejam usadas)
    'nuserie' => 'Série',
    'nmplanta' => 'Termo',
  ];
  
  // Merge com colunas customizadas
  $allColumns = array_merge($availableColumns, $customColumns);
  
  // Se não especificou colunas, mostra todas
  $displayColumns = empty($columns) ? array_keys($availableColumns) : $columns;
  $currentSort = request('sort');
  $currentDirection = strtolower(request('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
  $baseQuery = request()->except(['page']);
@endphp

<div class="relative overflow-x-auto shadow-md sm:rounded-lg z-0 min-w-0">
  <table class="w-full text-[11px] sm:text-xs md:text-[13px] lg:text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
    <thead class="text-[11px] sm:text-xs md:text-[13px] lg:text-sm uppercase bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 font-bold shadow-sm">
      <tr class="divide-x divide-gray-200 dark:divide-gray-700">
        @if($showCheckbox)
          <th class="{{ $headerPadding }} w-12">
            @if($showCheckboxHeader)
              <input type="checkbox" 
                class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-600" 
                @if($onSelectAllChange) @change="{{ $onSelectAllChange }}" @endif
              >
            @endif
          </th>
        @endif
        
        @foreach($displayColumns as $col)
          @if(isset($allColumns[$col]))
            @php
              $isCurrent = $sortable && $currentSort === $col;
              $nextDir = ($isCurrent && $currentDirection === 'asc') ? 'desc' : 'asc';
              $sortUrl = $sortable
                ? request()->fullUrlWithQuery(array_merge($baseQuery, ['sort' => $col, 'direction' => $nextDir]))
                : null;
            @endphp
            <th class="{{ $headerPadding }} {{ $col === 'situacao' ? 'text-[10px] sm:text-[11px] md:text-xs' : '' }} {{ $col === 'conferido' ? 'text-center w-12' : '' }} whitespace-nowrap">
              @if($sortable)
                <a href="{{ $sortUrl }}"
                  data-ajax-sort
                  class="inline-flex items-center gap-1 font-semibold hover:text-indigo-600 dark:hover:text-indigo-300"
                >
                  <span>{{ $allColumns[$col] }}</span>
                  @if($isCurrent)
                    <span class="text-[10px]">{{ $currentDirection === 'asc' ? '▲' : '▼' }}</span>
                  @endif
                </a>
              @else
                {{ $allColumns[$col] }}
              @endif
            </th>
          @endif
        @endforeach
        
        @if($showActions && auth()->user()->PERFIL !== 'C')
          <th class="{{ $headerPadding }}">Ações</th>
        @endif
      </tr>
    </thead>
    
    <tbody class="text-[13px] sm:text-sm md:text-[15px] lg:text-base font-semibold">
      @forelse ($data as $item)
        @php
          $rowSituacao = trim(preg_replace('/[\r\n]+/', ' ', (string)($item->SITUACAO ?? '')));
          $rowPatrimonio = $item->NUPATRIMONIO ?? $item->NUSEQPATR ?? $item->id;
          $isConsultor = auth()->user()->PERFIL === 'C';
        @endphp
        <tr data-row-id="{{ $item->NUSEQPATR ?? $item->id }}" data-situacao="{{ $rowSituacao }}" data-patrimonio="{{ $rowPatrimonio }}" class="tr-hover {{ $clickable ? 'cursor-pointer' : '' }}"
          @if($clickable && $onRowClick && !$isConsultor)
            @click="window.location.href='{{ str_replace(':id', $item->NUSEQPATR ?? $item->id, $onRowClick) }}'"
          @elseif($clickable && !$isConsultor)
            @click="window.location.href='{{ route('patrimonios.edit', $item) }}'"
          @elseif($clickable && $isConsultor)
            @click="openModalConsulta({{ $item->NUSEQPATR ?? $item->id }})"
          @endif
        >
          @if($showCheckbox)
            <td class="{{ $headerPadding }}" @click.stop>
              <div class="flex items-center justify-center">
                <input 
                  type="checkbox" 
                  name="ids[]" 
                  value="{{ $item->NUSEQPATR ?? $item->id }}" 
                  class="patrimonio-checkbox h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-600 {{ $checkboxClass }}"
                  @if($onCheckboxChange) @change="{{ $onCheckboxChange }}" @endif
                >
              </div>
            </td>
          @endif
          
          @foreach($displayColumns as $col)
            {{-- PATRIMÔNIO COLUMNS --}}
            @if($col === 'nupatrimonio')
              <td class="{{ $headerPadding }} whitespace-nowrap truncate font-semibold" title="{{ $item->NUPATRIMONIO ?? 'N/A' }}">{{ $item->NUPATRIMONIO ?? 'N/A' }}</td>
            
            @elseif($col === 'conferido')
              @php
                $flag = $item->FLCONFERIDO ?? null;
                $flag = is_string($flag) ? strtoupper(trim($flag)) : ($flag !== null ? (string) $flag : '');
                $isConferido = in_array($flag, ['S', '1', 'T', 'Y'], true);
              @endphp
              <td class="{{ $headerPadding }} text-center">
                @if($isConferido)
                  <span class="inline-flex items-center justify-center w-5 h-5 text-emerald-500 dark:text-emerald-400" style="color: #059669;" title="Verificado" aria-label="Verificado">
                    <svg viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 00-1.06 1.06l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                    </svg>
                  </span>
                @else
                  <span class="inline-flex items-center justify-center w-5 h-5 text-rose-500 dark:text-rose-400" style="color: #f43f5e;" title="Não verificado" aria-label="Não verificado">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="w-5 h-5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="12" cy="12" r="9" />
                      <path d="M8 12h8" />
                    </svg>
                  </span>
                @endif
              </td>

            @elseif($col === 'numof')
              <td class="{{ $headerPadding }} whitespace-nowrap truncate" title="{{ $item->NUMOF ?? '—' }}">{{ $item->NUMOF ?? '—' }}</td>
            
            @elseif($col === 'codobjeto')
              <td class="{{ $headerPadding }} whitespace-nowrap truncate font-medium" title="{{ $item->CODOBJETO ?? '—' }}">{{ $item->CODOBJETO ?? '—' }}</td>
            
            @elseif($col === 'projeto')
              <td class="{{ $headerPadding }}">
                @php
                  $project = $item->projeto ?? ($item->local->projeto ?? null);
                  $projectName = $project->NMPROJETO ?? $project->NOMEPROJETO ?? null;
                @endphp
                @if($project)
                  <div class="leading-tight max-w-[100px] sm:max-w-[120px]">
                    <span class="font-mono text-xs font-semibold text-blue-600 dark:text-blue-400 truncate block" title="{{ $project->CDPROJETO }}">{{ $project->CDPROJETO }}</span>
                    <div class="text-[10px] text-gray-600 dark:text-gray-400 truncate" title="{{ $projectName }}">{{ Str::limit($projectName, 12, '...') }}</div>
                  </div>
                @else
                  <span class="text-gray-400 text-[10px]">—</span>
                @endif
              </td>
            @elseif($col === 'local')
              <td class="{{ $headerPadding }}">
                @if($item->local)
                  <div class="leading-tight">
                    <span class="font-mono text-xs font-semibold text-green-600 dark:text-green-400">{{ $item->local->CDLOCAL ?? $item->local->cdlocal }}</span>
                    <div class="text-[10px] text-gray-600 dark:text-gray-400 truncate" title="{{ $item->local->LOCAL ?? $item->local->delocal }}">{{ Str::limit($item->local->LOCAL ?? $item->local->delocal, 10, '...') }}</div>
                  </div>
                @else
                  <span class="text-gray-400 text-xs">—</span>
                @endif
              </td>
            @elseif($col === 'modelo')
              <td class="{{ $headerPadding }} truncate max-w-[120px]" title="{{ $item->MODELO }}">{{ $item->MODELO ? Str::limit($item->MODELO, 18, '...') : '—' }}</td>
            
            @elseif($col === 'marca')
              <td class="{{ $headerPadding }} truncate max-w-[120px]" title="{{ $item->MARCA }}">{{ $item->MARCA ? Str::limit($item->MARCA, 18, '...') : '—' }}</td>
            
            @elseif($col === 'descricao')
              @php 
                // Prioridade: Descrição > Marca > Modelo > "-"
                $desc = trim((string)($item->DEPATRIMONIO ?? $item->DEOBJETO ?? ''));
                $marca = trim((string)($item->MARCA ?? ''));
                $modelo = trim((string)($item->MODELO ?? ''));
                
                if ($desc !== '') {
                  $displayText = $desc;
                } elseif ($marca !== '') {
                  $displayText = $marca;
                } elseif ($modelo !== '') {
                  $displayText = $modelo;
                } else {
                  $displayText = '—';
                }
              @endphp
              <td class="{{ $headerPadding }} font-semibold text-gray-900 dark:text-white truncate max-w-[140px] sm:max-w-[180px]" title="{{ $item->DEPATRIMONIO }}">
                @if($displayText !== '—')
                  <span title="{{ $displayText }}">
                    {{ Str::limit($displayText, 10, '...') }}
                  </span>
                @else
                  <span class="text-gray-400">—</span>
                @endif
              </td>
            
            @elseif($col === 'situacao')
              <td class="{{ $headerPadding }}">
                @php
                  $situacao = $item->SITUACAO ?? '';
                  $raw = preg_replace('/[\r\n]+/', ' ', trim($situacao));
                  $norm = strtoupper(Illuminate\Support\Str::ascii($raw));
                  $norm = preg_replace('/\s+/', ' ', $norm);

                  $situationBadgeMap = [
                    'EM USO' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                    'BAIXA' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300',
                    'CONSERTO' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
                    'A DISPOSICAO' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                    'DISPONIVEL' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                    'LAVOR' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                  ];

                  $badgeClasses = $situationBadgeMap[$norm] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';

                  if(in_array($norm, ['A DISPOSICAO', 'DISPONIVEL'])){
                    $displaySituacao = 'Disponivel';
                  } else {
                    $displaySituacao = $raw !== '' ? $raw : null;
                  }
                @endphp
                @if($displaySituacao)
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-[11px] font-semibold {{ $badgeClasses }} shadow-sm">
                    {{ $displaySituacao }}
                  </span>
                @else
                  <span class="text-gray-400">—</span>
                @endif
              </td>
            
            @elseif($col === 'dtaquisicao')
              <td class="{{ $headerPadding }} whitespace-nowrap">{{ $item->dtaquisicao_pt_br ?? '—' }}</td>
            
            @elseif($col === 'dtoperacao')
              <td class="{{ $headerPadding }} whitespace-nowrap font-medium">{{ $item->dtoperacao_pt_br ?? ($item->DTOPERACAO ? \Carbon\Carbon::parse($item->DTOPERACAO)->timezone(config('app.timezone'))->format('d/m H:i') : '—') }}</td>
            
            @elseif($col === 'responsavel')
              <td class="{{ $headerPadding }}">
                @if($item->responsavel)
                  <div class="leading-tight">
                    <span class="font-mono text-xs">{{ $item->responsavel->CDMATRFUNCIONARIO }}</span>
                    <div class="text-[10px] text-gray-500 dark:text-gray-400 truncate max-w-[110px]">{{ $item->responsavel->NMFUNCIONARIO }}</div>
                  </div>
                @else
                  <span class="text-gray-400 text-[10px]">—</span>
                @endif
              </td>
            
            @elseif($col === 'cadastrador')
              <td class="{{ $headerPadding }} truncate max-w-[100px]">{{ $item->cadastrado_por_nome ?? '—' }}</td>
            
            {{-- CUSTOM COLUMNS: renderiza via slot nomeado --}}
            @else
              <td class="{{ $headerPadding }}">
                @if($slot->isNotEmpty())
                  {{ $slot }}
                @else
                  {{ $item->{$col} ?? '—' }}
                @endif
              </td>
            @endif
          @endforeach
          
          @if($showActions && auth()->user()->PERFIL !== 'C')
            <td class="{{ $headerPadding }}" @click.stop>
              @if($actionsView)
                @include($actionsView, ['item' => $item])
              @else
                {{ $slot }}
              @endif
            </td>
          @endif
        </tr>
      @empty
        <tr>
          <td colspan="{{ count($displayColumns) + ($showCheckbox ? 1 : 0) + ($showActions ? 1 : 0) }}" class="px-6 py-12 text-center">
            <div class="flex flex-col items-center justify-center text-gray-600 dark:text-gray-400">
              <svg class="w-12 h-12 mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
              </svg>
              <h3 class="text-base font-semibold mb-1">{{ $emptyMessage }}</h3>
            </div>
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>
