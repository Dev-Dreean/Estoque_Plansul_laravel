{{-- 
  Componente: Filtro Multi-Select de Supervisores/Responsáveis
  
  @props: 
  - supervisores: Collection de usuários com campos NUSEQUSUARIO e NOMEUSER
  - currentSelected: Array de IDs selecionados (opcional)
  - formId: ID do formulário pai (para validação e submissão)
  
  Exemplo de uso:
  <x-filtro-supervisores 
    :supervisores="$supervisores" 
    :currentSelected="request('supervisores', [])"
    formId="filtro-form" 
  />
  
  ⚠️ REUTILIZÁVEL: Este componente é usado em:
  - resources/views/patrimonios/index.blade.php
  - resources/views/patrimonios/atribuir.blade.php
  - resources/views/patrimonios/edit.blade.php (se aplicável)
--}}

@props([
    'supervisores' => collect(),
    'currentSelected' => [],
    'formId' => 'filtro-form',
    'label' => '🔍 Filtrar por Supervisor:',
    'placeholder' => '-- Selecione supervisores --'
])

<div class="w-full space-y-2">
  {{-- Label --}}
  <label for="supervisores-filter" class="block text-xs font-medium text-gray-700 dark:text-gray-300">
    {{ $label }}
  </label>

  {{-- Multi-Select Dropdown --}}
  <select 
    id="supervisores-filter" 
    name="supervisores[]" 
    multiple 
    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent min-h-[36px]"
    x-data="{
      selectedCount: {{ count($currentSelected) }},
    }"
    @change="selectedCount = document.querySelectorAll('#supervisores-filter option:checked').length"
  >
    <option value="">{{ $placeholder }}</option>
    @forelse($supervisores as $supervisor)
      <option 
        value="{{ $supervisor->NUSEQUSUARIO }}"
        @if(in_array($supervisor->NUSEQUSUARIO, (array)$currentSelected)) selected @endif
      >
        {{ $supervisor->NOMEUSER ?? $supervisor->NMLOGIN }}
      </option>
    @empty
      <option value="" disabled>
        Nenhum supervisor disponível
      </option>
    @endforelse
  </select>

  {{-- Tags dos selecionados --}}
  @if(!empty($currentSelected))
    <div class="flex flex-wrap gap-2 pt-1">
      @foreach((array)$currentSelected as $selectedId)
        @php
          $selected = $supervisores->where('NUSEQUSUARIO', $selectedId)->first();
        @endphp
        @if($selected)
          <span class="inline-flex items-center gap-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded text-xs font-medium">
            {{ $selected->NOMEUSER ?? $selected->NMLOGIN }}
            <button 
              type="button" 
              class="remove-filter inline-flex items-center text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 hover:font-bold"
              data-value="{{ $selectedId }}"
              title="Remover supervisor do filtro"
              @click.prevent="
                const select = document.getElementById('supervisores-filter');
                const option = select.querySelector('option[value=\"{{ $selectedId }}\"]');
                if (option) option.selected = false;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                const url = new URL(location.href);
                url.searchParams.delete('supervisores');
                Array.from(select.options)
                  .filter(opt => opt.selected && opt.value)
                  .forEach(opt => url.searchParams.append('supervisores[]', opt.value));
                location.href = url.toString();
              "
            >
              ✕
            </button>
          </span>
        @endif
      @endforeach
    </div>
  @endif
</div>

<script>
  // JavaScript para melhorar UX do multi-select
  document.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('supervisores-filter');
    
    if (!select) return;

    // Melhorar apresentação visual
    select.addEventListener('change', function() {
      const count = document.querySelectorAll('#supervisores-filter option:checked').length;
      if (count > 0) {
        select.classList.add('ring-2', 'ring-blue-400', 'ring-opacity-50');
      } else {
        select.classList.remove('ring-2', 'ring-blue-400', 'ring-opacity-50');
      }
    });

    // Trigger inicial
    select.dispatchEvent(new Event('change'));
  });
</script>
