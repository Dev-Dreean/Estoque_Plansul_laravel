@props([
    'checked' => false,
    'disabled' => false,
    'value',
    'name' => 'telas[]',
    'title',
    'subtitle' => null,
    'meta' => null,
])

<label class="flex h-full cursor-pointer items-start rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 transition hover:border-indigo-300 dark:hover:border-indigo-700 hover:bg-indigo-50/40 dark:hover:bg-indigo-900/20 {{ $disabled ? 'opacity-75 cursor-not-allowed bg-gray-50 dark:bg-gray-900/50' : '' }}">
    <input
        type="checkbox"
        name="{{ $name }}"
        value="{{ $value }}"
        class="mt-1 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0 dark:focus:ring-offset-gray-800"
        @if($checked) checked @endif
        @if($disabled) disabled @endif>
    <div class="ml-3 flex-1">
        <span class="block text-sm font-medium text-gray-900 dark:text-gray-100">
            {{ $title }}
        </span>
        @if($subtitle)
            <span class="mt-1 block text-xs leading-5 text-gray-600 dark:text-gray-400">
                {{ $subtitle }}
            </span>
        @endif
        @if($meta)
            <span class="mt-2 block text-[11px] tracking-wide text-gray-400 dark:text-gray-500">
                {{ $meta }}
            </span>
        @endif
    </div>
</label>
