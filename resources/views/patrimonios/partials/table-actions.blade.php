<div class="flex items-center gap-2">
  @php
    $podeGerarTermo = trim((string) ($item->CDPROJETO ?? '')) !== '' && trim((string) ($item->CDMATRFUNCIONARIO ?? '')) !== '';
    $termoGerado = strtoupper(trim((string) ($item->FLTERMORESPONSABILIDADE ?? 'N'))) === 'S';
  @endphp

  @if($podeGerarTermo)
  <a
    href="{{ route('termos.responsabilidade.patrimonio.docx', $item->NUSEQPATR) }}"
    title="{{ $termoGerado ? 'Baixar último termo de responsabilidade salvo em PDF' : 'Gerar termo de responsabilidade em PDF' }}"
    class="{{ $termoGerado ? 'text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300' : 'text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300' }} transition-colors duration-200 p-1"
  >
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true">
      <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.75h6l4.5 4.5v11.25A2.25 2.25 0 0 1 15.75 21h-8.25A2.25 2.25 0 0 1 5.25 18.75V6A2.25 2.25 0 0 1 7.5 3.75Z" />
      <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 3.75V9h4.5" />
      <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 13.5h7.5M8.25 17.25h4.5" />
    </svg>
  </a>
  @else
  <span
    title="Informe projeto e responsável para gerar o termo"
    class="text-gray-400 dark:text-gray-500 p-1 cursor-not-allowed"
  >
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true">
      <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.75h6l4.5 4.5v11.25A2.25 2.25 0 0 1 15.75 21h-8.25A2.25 2.25 0 0 1 5.25 18.75V6A2.25 2.25 0 0 1 7.5 3.75Z" />
      <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 3.75V9h4.5" />
      <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 13.5h7.5" />
    </svg>
  </span>
  @endif

  @if(auth()->user()->PERFIL === 'ADM')
  <button
    type="button"
    @click.stop="openDelete('{{ $item->NUSEQPATR }}', @js($item->DEPATRIMONIO ?? 'patrimônio'))"
    title="Excluir patrimônio"
    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 transition-colors duration-200 p-1"
  >
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
    </svg>
  </button>
  @endif
</div>
