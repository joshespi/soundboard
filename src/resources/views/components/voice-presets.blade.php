@props(['heading' => null, 'always' => false])

{{-- Big touch tiles, same visual language as the soundboard grid. The preset list lives
     in JS (resources/js/broadcast/presets.js) as the single source of truth.
     `always` keeps the row on screen even when he isn't live — the setup page wants that
     so he can audition voices; the player only shows it once it can actually be heard. --}}
<div
    x-data="voicePresets(@js($always))"
    x-show="visible"
    x-cloak
    {{ $attributes->merge(['class' => 'space-y-2']) }}
>
    @if ($heading)
        <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ $heading }}</h3>
    @endif

    <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-7 gap-2 sm:gap-3">
        <template x-for="preset in presets" :key="preset.key">
            <button
                type="button"
                x-on:click="choose(preset.key)"
                :aria-pressed="broadcast.preset === preset.key"
                :title="preset.description"
                :class="broadcast.preset === preset.key
                    ? 'ring-4 ring-indigo-400 bg-indigo-600 text-white'
                    : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 hover:border-indigo-400'"
                class="aspect-square rounded-2xl shadow-sm flex flex-col items-center justify-center gap-1 font-semibold text-center p-2 active:scale-95 transition select-none [touch-action:manipulation]"
            >
                <span class="text-3xl sm:text-4xl leading-none" x-text="preset.emoji"></span>
                <span class="text-xs sm:text-sm" x-text="preset.label"></span>
            </button>
        </template>
    </div>

    <p x-show="!broadcast.active" class="text-sm text-gray-500 dark:text-gray-400">
        {{ __('Your voice only reaches Discord while you are live.') }}
    </p>
</div>
