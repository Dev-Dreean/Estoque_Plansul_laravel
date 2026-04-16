@php
  $isConsultor = auth()->user()?->PERFIL === \App\Models\User::PERFIL_CONSULTOR;
  $usuarioAtual = auth()->user();

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

  $defaultColumns = array_merge(
    ['nupatrimonio', 'conferido'],
    array_values($mapBeforeDescricao),
    ['descricao', 'marca', 'modelo'],
    ['situacao'],
    array_values($mapAfterDescricao)
  );

  $savedColumnsOrder = is_array($usuarioAtual?->patrimonio_columns_order)
    ? $usuarioAtual->patrimonio_columns_order
    : [];

  $normalizedSavedOrder = array_values(array_filter(
    $savedColumnsOrder,
    static fn ($column) => in_array($column, $defaultColumns, true)
  ));

  $columns = empty($normalizedSavedOrder)
    ? $defaultColumns
    : array_values(array_unique(array_merge($normalizedSavedOrder, $defaultColumns)));
@endphp

<x-patrimonio-table
  :patrimonios="$patrimonios"
  :columns="$columns"
  :show-checkbox="!$isConsultor"
  :show-checkbox-header="false"
  :show-actions="!$isConsultor"
  actions-view="patrimonios.partials.table-actions"
  empty-message="Nenhum patrimônio encontrado"
  density="compact"
/>

<div id="patrimonios-pagination" class="mt-3">
  {{ $patrimonios->appends(request()->query())->onEachSide(1)->links('pagination::tailwind', ['attributes' => ['data-ajax-page' => true]]) }}
</div>




