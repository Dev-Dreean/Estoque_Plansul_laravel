@forelse($locais as $local)
	<tr data-local-id="{{ $local['id'] }}" class="hover:bg-gray-50 dark:hover:bg-gray-900">
		<td class="px-4 py-3">
			<input type="checkbox" class="checkbox-local rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 cursor-pointer" data-local-id="{{ $local['id'] }}">
		</td>
		<td class="px-4 py-3 font-mono text-sm">{{ $local['cdlocal'] }}</td>
		<td class="px-4 py-3">{{ $local['delocal'] }}</td>
		<td class="px-4 py-3">{{ $local['projeto_nome'] ?? '' }}</td>
		<td class="px-4 py-3">
			<div class="flex items-center gap-2">
				<a href="{{ route('projetos.edit', $local['id']) }}" class="text-indigo-600 hover:text-indigo-800" title="Editar">
					<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 11l6-6 3 3-6 6H9v-3z"/></svg>
				</a>

				@if(Auth::check() && Auth::user()->isAdmin())
				<button type="button" class="delete-btn text-red-600 hover:text-red-800" data-local-id="{{ $local['id'] }}" data-local-name="{{ $local['delocal'] }}" title="Remover">
					<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
				</button>
				@endif
			</div>
		</td>
	</tr>
@empty
	<tr>
		<td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Nenhum local encontrado.</td>
	</tr>
@endforelse

