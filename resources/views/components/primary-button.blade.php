<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn btn-accent text-xs uppercase tracking-widest']) }}>
    {{ $slot }}
</button>