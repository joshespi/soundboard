@props(['wireModel', 'auto' => false])
<div x-data="{ value: $wire.entangle('{{ $wireModel }}') }" role="radiogroup" {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }}>
    @if ($auto)
        <button
            type="button"
            role="radio"
            x-on:click="value = ''"
            :aria-checked="value === ''"
            :class="value === '' ? 'ring-2 ring-offset-2 ring-indigo-500 dark:ring-offset-gray-800' : ''"
            class="h-7 px-2 rounded-full border border-gray-300 dark:border-gray-600 text-xs font-medium text-gray-600 dark:text-gray-300"
        >{{ __('Auto') }}</button>
    @endif
    @foreach (\App\Models\Sound::COLORS as $color)
        <button
            type="button"
            role="radio"
            x-on:click="value = '{{ $color }}'"
            :aria-checked="value === '{{ $color }}'"
            :class="value === '{{ $color }}' ? 'ring-2 ring-offset-2 ring-indigo-500 dark:ring-offset-gray-800' : ''"
            class="w-7 h-7 rounded-full"
            style="background-color: {{ $color }}"
            aria-label="{{ $color }}"
        ></button>
    @endforeach
</div>
