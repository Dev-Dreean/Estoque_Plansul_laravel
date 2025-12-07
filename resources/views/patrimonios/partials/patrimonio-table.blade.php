@php
  $visibility = $visibleColumns ?? [];
  $showEmpty = $showEmptyColumns ?? false;

  $mapBeforeDescricao = [
    'NUMOF' => 'numof',
    'CODOBJETO' => 'codobjeto',
    'NMPLANTA' => 'nmplanta',
    'NUSERIE' => 'nuserie',
    'PROJETO' => 'projeto',
    'CDLOCAL' => 'local',
    'MODELO' => 'modelo',
    'MARCA' => 'marca',
  ];

  $mapAfterDescricao = [
    'DTAQUISICAO' => 'dtaquisicao',
    'DTOPERACAO' => 'dtoperacao',
    'CDMATRFUNCIONARIO' => 'responsavel',
    'CADASTRADOR' => 'cadastrador',
  ];

  $columns = ['nupatrimonio'];
  foreach ($mapBeforeDescricao as $key => $col) {
    if (($visibility[$key] ?? true) || $showEmpty) {
      $columns[] = $col;
    }
  }

  $columns[] = 'descricao';
  $columns[] = 'situacao';

  foreach ($mapAfterDescricao as $key => $col) {
    if (($visibility[$key] ?? true) || $showEmpty) {
      $columns[] = $col;
    }
  }
@endphp

<x-patrimonio-table
  :patrimonios="$patrimonios"
  :columns="$columns"
  :show-actions="true"
  actions-view="patrimonios.partials.table-actions"
  empty-message="Nenhum patrimônio encontrado"
/>

<div class="mt-4">
  {{ $patrimonios->appends(request()->query())->links('pagination::tailwind') }}
</div>
