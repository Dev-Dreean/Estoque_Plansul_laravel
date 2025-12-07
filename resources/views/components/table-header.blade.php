{{--
    Componente: Table Header
    Propósito: Renderizar cabeçalho de tabela reutilizável com suporte a ordenação
    
    @props
    - columns: array de objetos ['label' => 'Nome', 'field' => 'campo', 'sortable' => true]
    - sortField: campo atual de ordenação
    - sortDirection: direção atual (asc|desc)
    
    Uso:
    <x-table-header 
        :columns="$columns" 
        :sortField="$sortField" 
        :sortDirection="$sortDirection" 
    />
--}}

@props([
    'columns' => [],
    'sortField' => null,
    'sortDirection' => 'asc'
])

<thead class="bg-gray-50 dark:bg-gray-700">
    <tr>
        @foreach($columns as $column)
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                @if(isset($column['sortable']) && $column['sortable'])
                    <a href="{{ request()->fullUrlWithQuery([
                        'sort' => $column['field'],
                        'direction' => $sortField === $column['field'] && $sortDirection === 'asc' ? 'desc' : 'asc'
                    ]) }}" class="flex items-center space-x-1 hover:text-gray-700 dark:hover:text-gray-100">
                        <span>{{ $column['label'] }}</span>
                        @if($sortField === $column['field'])
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                @if($sortDirection === 'asc')
                                    <path d="M5 10l5-5 5 5H5z"/>
                                @else
                                    <path d="M5 10l5 5 5-5H5z"/>
                                @endif
                            </svg>
                        @endif
                    </a>
                @else
                    {{ $column['label'] }}
                @endif
            </th>
        @endforeach
    </tr>
</thead>
