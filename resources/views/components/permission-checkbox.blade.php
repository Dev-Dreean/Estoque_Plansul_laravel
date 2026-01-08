@props([
    'checked' => false,
    'disabled' => false,
    'value',
    'name' => 'telas[]',
    'title',
    'subtitle' => null,
])

<label class="flex items-start p-3 rounded-lg bg-gray-700 dark:bg-gray-700 border border-gray-600 dark:border-gray-600 hover:bg-gray-600 dark:hover:bg-gray-600 cursor-pointer transition-colors">
    <input
        type="checkbox"
        name="{{ $name }}"
        value="{{ $value }}"
        class="mt-1 rounded border-gray-500 text-plansul-blue focus:ring-plansul-blue focus:ring-offset-0"
        @if($checked) checked @endif
        @if($disabled) disabled @endif>
    <div class="ml-3 flex-1">
        <span class="block text-sm font-medium text-gray-100">
            {{ $title }}
        </span>
        @if($subtitle)
            <span class="text-xs text-gray-400">
                {{ $subtitle }}
            </span>
        @endif
    </div>
</label>
