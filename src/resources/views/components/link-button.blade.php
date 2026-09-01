@props(['href', 'variant' => 'primary'])
@php
$classes = match ($variant) {
    'primary' => 'inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-500 active:scale-[0.98] transition',
    'danger' => 'inline-flex items-center justify-center gap-2 px-4 py-2.5 text-red-600 dark:text-red-400 text-sm font-semibold rounded-lg hover:bg-red-50 dark:hover:bg-red-950 active:scale-[0.98] transition',
    default => 'inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 dark:border-gray-600 text-sm font-semibold rounded-lg text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 active:scale-[0.98] transition',
};
@endphp
<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
