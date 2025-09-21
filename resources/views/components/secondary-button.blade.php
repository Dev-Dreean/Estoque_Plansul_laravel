<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn btn-neutral text-xs uppercase tracking-widest disabled:opacity-50']) }}>
    {{ $slot }}
</button>