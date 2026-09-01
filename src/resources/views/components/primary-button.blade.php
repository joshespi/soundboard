@props(['loadingLabel' => null, 'loadingTarget' => null])
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 active:scale-[0.98] disabled:opacity-50 disabled:pointer-events-none transition']) }}>
    @if ($loadingLabel)
        <span wire:loading.remove @if ($loadingTarget) wire:target="{{ $loadingTarget }}" @endif>{{ $slot }}</span>
        <span wire:loading @if ($loadingTarget) wire:target="{{ $loadingTarget }}" @endif class="inline-flex items-center gap-2">
            <x-icon.spinner />
            {{ $loadingLabel }}
        </span>
    @else
        {{ $slot }}
    @endif
</button>
