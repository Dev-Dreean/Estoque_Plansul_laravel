<div class="relative overflow-x-auto shadow-md sm:rounded-lg">
    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                @php
                    $currSort = $sort ?? request('sort', 'DETELA');
                    $currDir = $direction ?? request('direction', 'asc');
                    $nextDir = fn ($col) => ($currSort === $col && $currDir === 'asc') ? 'desc' : 'asc';
                    $isSort = fn ($col) => ($currSort === $col);
                    $sortMark = fn ($col) => $isSort($col) ? ($currDir === 'asc' ? '↑' : '↓') : '↕';
                @endphp
                <th scope="col" class="px-4 py-2">
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'NUSEQTELA', 'direction' => $nextDir('NUSEQTELA'), 'page' => 1]) }}" class="inline-flex items-center gap-1 hover:text-indigo-600">
                        Código <span class="text-[10px]">{{ $sortMark('NUSEQTELA') }}</span>
                    </a>
                </th>
                <th scope="col" class="px-4 py-2">
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'DETELA', 'direction' => $nextDir('DETELA'), 'page' => 1]) }}" class="inline-flex items-center gap-1 hover:text-indigo-600">
                        Nome da Tela <span class="text-[10px]">{{ $sortMark('DETELA') }}</span>
                    </a>
                </th>
                <th scope="col" class="px-4 py-2">
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'NMSISTEMA', 'direction' => $nextDir('NMSISTEMA'), 'page' => 1]) }}" class="inline-flex items-center gap-1 hover:text-indigo-600">
                        Sistema <span class="text-[10px]">{{ $sortMark('NMSISTEMA') }}</span>
                    </a>
                </th>
                <th scope="col" class="px-4 py-2">
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'rota', 'direction' => $nextDir('rota'), 'page' => 1]) }}" class="inline-flex items-center gap-1 hover:text-indigo-600">
                        Rota <span class="text-[10px]">{{ $sortMark('rota') }}</span>
                    </a>
                </th>
                <th scope="col" class="px-4 py-2">
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'cadastrada', 'direction' => $nextDir('cadastrada'), 'page' => 1]) }}" class="inline-flex items-center gap-1 hover:text-indigo-600">
                        Situação <span class="text-[10px]">{{ $sortMark('cadastrada') }}</span>
                    </a>
                </th>
                <th scope="col" class="px-4 py-2">Ações</th>
            </tr>
        </thead>
        <tbody>
            @include('telas._table_rows', ['telasGrid' => $telasGrid])
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $telasGrid->appends(request()->query())->links() }}
</div>
