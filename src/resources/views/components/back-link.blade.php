@props(['href'])
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition']) }} wire:navigate>
    &larr; {{ $slot }}
</a>
