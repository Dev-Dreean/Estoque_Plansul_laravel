<div class="relative overflow-x-auto shadow-md sm:rounded-lg">
    <div x-show="selectedLocalIds.length > 0" x-transition class="bg-blue-50 dark:bg-blue-900/20 border-b border-blue-200 dark:border-blue-800 px-4 py-3 flex items-center justify-between">
        <span class="text-sm font-semibold text-blue-900 dark:text-blue-200">
            <span x-text="selectedLocalIds.length"></span>
            <span x-text="selectedLocalIds.length === 1 ? 'local selecionado' : 'locais selecionados'"></span>
        </span>
        <div class="flex items-center gap-2">
            <button
                type="button"
                @click="clearSelection()"
                class="px-3 py-1 text-sm bg-gray-300 hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 rounded transition">
                Desselecionar
            </button>
            @if(Auth::check() && Auth::user()->isAdmin())
            <button
                type="button"
                @click="openMultipleDeleteModal()"
                class="px-3 py-1 text-sm bg-red-600 hover:bg-red-700 text-white rounded transition">
                Remover Selecionados
            </button>
            @endif
        </div>
    </div>

    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                @php
                    $currSort = $sort ?? request('sort', 'delocal');
                    $currDir = $direction ?? request('direction', 'asc');
                    $nextDir = fn ($col) => ($currSort === $col && $currDir === 'asc') ? 'desc' : 'asc';
                    $isSort = fn ($col) => ($currSort === $col);
                    $sortMark = fn ($col) => $isSort($col) ? ($currDir === 'asc' ? '↑' : '↓') : '↕';
                @endphp
                <th scope="col" class="px-4 py-2 w-10">
                    <input
                        type="checkbox"
                        id="checkbox-header"
                        @change="toggleAll($event.target.checked)"
                        class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 cursor-pointer">
                </th>
                <th scope="col" class="px-4 py-2">
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'cdlocal', 'direction' => $nextDir('cdlocal'), 'page' => 1]) }}" class="inline-flex items-center gap-1 hover:text-indigo-600">
                        Cód. Local <span class="text-[10px]">{{ $sortMark('cdlocal') }}</span>
                    </a>
                </th>
                <th scope="col" class="px-4 py-2">
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'delocal', 'direction' => $nextDir('delocal'), 'page' => 1]) }}" class="inline-flex items-center gap-1 hover:text-indigo-600">
                        Nome do Local <span class="text-[10px]">{{ $sortMark('delocal') }}</span>
                    </a>
                </th>
                <th scope="col" class="px-4 py-2">
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'tipo_local_label', 'direction' => $nextDir('tipo_local_label'), 'page' => 1]) }}" class="inline-flex items-center gap-1 hover:text-indigo-600">
                        Tipo <span class="text-[10px]">{{ $sortMark('tipo_local_label') }}</span>
                    </a>
                </th>
                <th scope="col" class="px-4 py-2">
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'fluxo_responsavel_label', 'direction' => $nextDir('fluxo_responsavel_label'), 'page' => 1]) }}" class="inline-flex items-center gap-1 hover:text-indigo-600">
                        Fluxo <span class="text-[10px]">{{ $sortMark('fluxo_responsavel_label') }}</span>
                    </a>
                </th>
                <th scope="col" class="px-4 py-2">
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'projeto_nome', 'direction' => $nextDir('projeto_nome'), 'page' => 1]) }}" class="inline-flex items-center gap-1 hover:text-indigo-600">
                        Projeto Associado <span class="text-[10px]">{{ $sortMark('projeto_nome') }}</span>
                    </a>
                </th>
                <th scope="col" class="px-4 py-2">Ações</th>
            </tr>
        </thead>
        <tbody>
            @include('projetos._table_rows', ['locais' => $locais])
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $locais->appends(request()->query())->links() }}
</div>
