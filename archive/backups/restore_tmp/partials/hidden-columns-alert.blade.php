@php
  $hiddenColumns = $hiddenColumns ?? [];
  $showEmpty = $showEmptyColumns ?? false;
@endphp

@if(!empty($hiddenColumns) && !$showEmpty)
  <div class="mb-4 p-3 rounded-md bg-yellow-50 border border-yellow-200 text-yellow-800">
    <strong>Colunas ocultas:</strong>
    <span>{{ implode(', ', $hiddenColumns) }}</span>
    <span class="ml-3">(ocultas porque não há informações nesta página)</span>
    <a href="{{ request()->fullUrlWithQuery(['show_empty_columns' => 1]) }}" class="ml-4 underline font-semibold">Mostrar colunas vazias</a>
  </div>
@elseif(!empty($hiddenColumns) && $showEmpty)
  <div class="mb-4 p-3 rounded-md bg-blue-50 border border-blue-200 text-blue-800">
    <strong>Exibindo colunas vazias:</strong>
    <span>{{ implode(', ', $hiddenColumns) }}</span>
    <a href="{{ request()->fullUrlWithQuery(['show_empty_columns' => 0]) }}" class="ml-4 underline font-semibold">Ocultar novamente</a>
  </div>
@endif
