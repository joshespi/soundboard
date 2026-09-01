@props(['variant' => 'default', 'label'])
@php
$classes = match ($variant) {
    'danger' => 'p-2 rounded-lg text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-950',
    default => 'p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-gray-100 dark:hover:bg-gray-700',
};
@endphp
<button type="button" {{ $attributes->merge(['class' => $classes, 'aria-label' => $label]) }}>
    {{ $slot }}
</button>
