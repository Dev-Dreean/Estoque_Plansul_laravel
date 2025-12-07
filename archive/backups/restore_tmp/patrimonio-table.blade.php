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
])

@php
  // Compatibilidade com nome antigo
  $data = $items ?? $patrimonios ?? collect([]);
  
  // Colunas disponíveis para patrimônios
  $availableColumns = [
    'nupatrimonio' => 'Nº Pat.',
    'numof' => 'OF',
    'codobjeto' => 'Cód. Objeto',
    'nmplanta' => 'Cód. Termo',
    'nuserie' => 'Nº Série',
    'projeto' => 'Projeto Associado',
    'local' => 'Código Local',
    'modelo' => 'Modelo',
    'marca' => 'Marca',
    'descricao' => 'Descrição',
    'situacao' => 'Situação',
    'dtaquisicao' => 'Dt. Aquisição',
    'dtoperacao' => 'Dt. Cadastro',
    'responsavel' => 'Responsavel',
    'cadastrador' => 'Cadastrador',
  ];
  
  // Merge com colunas customizadas
  $allColumns = array_merge($availableColumns, $customColumns);
  
  // Se não especificou colunas, mostra todas
  $displayColumns = empty($columns) ? array_keys($availableColumns) : $columns;
@endphp

<div class="relative overflow-x-auto shadow-md sm:rounded-lg z-0 min-w-0">
  <table class="w-full table-fixed text-[11px] text-left rtl:text-right text-gray-500 dark:text-gray-400">
    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
      <tr class="divide-x divide-gray-200 dark:divide-gray-700">
        @if($showCheckbox)
          <th class="px-2 py-2 w-12">
            <input type="checkbox" 
              class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-600" 
              @if($onSelectAllChange) @change="{{ $onSelectAllChange }}" @endif
            >
          </th>
        @endif
        
        @foreach($displayColumns as $col)
          @if(isset($allColumns[$col]))
            <th class="px-2 py-2 {{ $col === 'situacao' ? 'text-xs' : '' }}">{{ $allColumns[$col] }}</th>
          @endif
        @endforeach
        
        @if($showActions)
          <th class="px-2 py-2">Ações</th>
        @endif
      </tr>
    </thead>
    
    <tbody>
      @forelse ($data as $item)
        <tr class="tr-hover text-sm {{ $clickable ? 'cursor-pointer' : '' }}"
          @if($clickable && $onRowClick)
            @click="window.location.href='{{ str_replace(':id', $item->NUSEQPATR ?? $item->id, $onRowClick) }}'"
          @elseif($clickable)
            @click="window.location.href='{{ route('patrimonios.edit', $item) }}'"
          @endif
        >
          @if($showCheckbox)
            <td class="px-2 py-2" @click.stop>
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
              <td class="px-2 py-2">{{ $item->NUPATRIMONIO ?? 'N/A' }}</td>
            
            @elseif($col === 'numof')
              <td class="px-2 py-2">{{ $item->NUMOF ?? '—' }}</td>
            
            @elseif($col === 'codobjeto')
              <td class="px-2 py-2">{{ $item->CODOBJETO ?? '—' }}</td>
            
            @elseif($col === 'nmplanta')
              <td class="px-2 py-2 font-bold">{{ $item->NMPLANTA ?? '—' }}</td>
            
            @elseif($col === 'nuserie')
              <td class="px-2 py-2">{{ $item->NUSERIE ?? '—' }}</td>
            
            @elseif($col === 'projeto')
              <td class="px-2 py-2">
                @php
                  $project = $item->projeto ?? ($item->local->projeto ?? null);
                  $projectName = $project->NMPROJETO ?? $project->NOMEPROJETO ?? null;
                @endphp
                @if($project)
                  <div class="leading-tight">
                    <span class="font-mono text-xs font-semibold text-blue-600 dark:text-blue-400">{{ $project->CDPROJETO }}</span>
                    <div class="text-[10px] text-gray-600 dark:text-gray-400 truncate max-w-[130px]">{{ $projectName }}</div>
                  </div>
                @else
                  <span class="text-gray-400 text-[10px]">??"</span>
                @endif
              </td>
            @elseif($col === 'local')
              <td class="px-2 py-2">
                @if($item->local)
                  <div class="leading-tight">
                    <span class="font-mono text-xs font-semibold text-green-600 dark:text-green-400">{{ $item->local->CDLOCAL ?? $item->local->cdlocal }}</span>
                    <div class="text-[10px] text-gray-600 dark:text-gray-400 truncate max-w-[120px]">{{ $item->local->LOCAL ?? $item->local->delocal }}</div>
                  </div>
                @else
                  <span class="text-gray-400 text-[10px]">??"</span>
                @endif
              </td>
            @elseif($col === 'modelo')
              <td class="px-2 py-2 truncate max-w-[90px]">{{ $item->MODELO ? Str::limit($item->MODELO,12,'...') : '—' }}</td>
            
            @elseif($col === 'marca')
              <td class="px-2 py-2 truncate max-w-[90px]">{{ $item->MARCA ?? '—' }}</td>
            
            @elseif($col === 'descricao')
              @php $desc = trim((string)($item->DEPATRIMONIO ?? $item->DEOBJETO ?? '')); @endphp
              <td class="px-2 py-2 font-medium text-gray-900 dark:text-white max-w-[200px]">
                @if($desc !== '')
                  <div title="{{ $desc }}" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;text-overflow:ellipsis;line-clamp:2;">
                    {{ $desc }}
                  </div>
                @else
                  <span class="text-gray-400">—</span>
                @endif
              </td>
            
            @elseif($col === 'situacao')
              <td class="px-2 py-2">
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
              <td class="px-2 py-2">{{ $item->dtaquisicao_pt_br ?? '—' }}</td>
            
            @elseif($col === 'dtoperacao')
              <td class="px-2 py-2">{{ $item->dtoperacao_pt_br ?? ($item->DTOPERACAO ? \Carbon\Carbon::parse($item->DTOPERACAO)->timezone(config('app.timezone'))->format('d/m/Y H:i') : '—') }}</td>
            
            @elseif($col === 'responsavel')
              <td class="px-2 py-2">
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
              <td class="px-2 py-2 truncate max-w-[100px]">{{ $item->cadastrado_por_nome ?? '—' }}</td>
            
            {{-- CUSTOM COLUMNS: renderiza via slot nomeado --}}
            @else
              <td class="px-2 py-2">
                @if($slot->isNotEmpty())
                  {{ $slot }}
                @else
                  {{ $item->{$col} ?? '—' }}
                @endif
              </td>
            @endif
          @endforeach
          
          @if($showActions)
            <td class="px-2 py-2" @click.stop>
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
