@php
  // Mantemos todas as colunas principais, exceto Cód. Termo e Nº Série que foram solicitados para sair
  $mapBeforeDescricao = [
    'NUMOF' => 'numof',
    'CODOBJETO' => 'codobjeto',
    'PROJETO' => 'projeto',
    'CDLOCAL' => 'local',
  ];

  $mapAfterDescricao = [
    'DTAQUISICAO' => 'dtaquisicao',
    'DTOPERACAO' => 'dtoperacao',
    'CDMATRFUNCIONARIO' => 'responsavel',
    'CADASTRADOR' => 'cadastrador',
  ];

  $columns = array_merge(
    ['nupatrimonio'],
    array_values($mapBeforeDescricao),
    ['descricao', 'situacao'],
    array_values($mapAfterDescricao)
  );
@endphp

<x-patrimonio-table
  :patrimonios="$patrimonios"
  :columns="$columns"
  :show-actions="true"
  actions-view="patrimonios.partials.table-actions"
  empty-message="Nenhum patrimônio encontrado"
  density="compact"
/>

<div id="patrimonios-pagination" class="mt-3">
  {{ $patrimonios->appends(request()->query())->onEachSide(1)->links('pagination::tailwind', ['attributes' => ['data-ajax-page' => true]]) }}
</div>
