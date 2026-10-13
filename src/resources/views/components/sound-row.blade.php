@props(['sound'])
<div {{ $attributes->merge(['class' => 'flex items-center gap-3 p-3']) }}>
    {{ $handle ?? '' }}
    <x-sound-icon :sound="$sound" class="w-12 h-12 text-2xl rounded-lg bg-gray-100 dark:bg-gray-700 shrink-0" />
    @unless ($sound->is_video)
        <x-play-button :src="$sound->url" />
    @endunless
    <div class="flex-1 min-w-0">
        <p class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ $sound->name }}@if ($sound->is_video) <span class="ms-1 text-xs font-normal text-gray-500 dark:text-gray-400">🎬 {{ __('Video') }}</span>@endif</p>
        {{ $subtitle ?? '' }}
    </div>
    <div class="flex items-center gap-0.5 shrink-0">
        {{ $actions ?? '' }}
    </div>
</div>
