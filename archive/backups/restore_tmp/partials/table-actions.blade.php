<div class="flex items-center gap-2">
  <x-action-button
    type="edit"
    :href="route('patrimonios.edit', $item)"
    label="Editar patrimônio"
  />

  <x-action-button
    type="delete"
    label="Apagar patrimônio"
    data-delete-patrimonio="{{ $item->NUSEQPATR }}"
    data-delete-nome="{{ $item->DEPATRIMONIO ?? 'Sem nome' }}"
  />
</div>
