<div x-data="soundboard(@js($sounds->pluck('url')))" x-on:keydown.window="onKey($event)">
    <div class="flex items-center justify-between gap-4 mb-6">
        <x-back-link :href="route('dashboard')">{{ __('All screens') }}</x-back-link>
        <div class="flex items-center gap-2">
            <button
                type="button"
                x-show="anyPlaying"
                x-cloak
                x-on:click="stopAll()"
                class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest transition"
            >{{ __('Stop all') }}</button>
            <x-link-button :href="route('screens.manage', $screen)" variant="secondary" wire:navigate>{{ __('Edit sounds') }}</x-link-button>
        </div>
    </div>

    @if ($sounds->isEmpty())
        <x-empty-state>{{ __('This screen has no sounds yet.') }}</x-empty-state>
    @else
        <x-voice-presets class="mb-6" :heading="__('Your voice')" />

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-4">
            @foreach ($sounds as $sound)
                <button
                    type="button"
                    x-on:click="play(@js($sound->url))"
                    :class="isPlaying(@js($sound->url)) ? 'ring-4 ring-white/70 ring-offset-2 ring-offset-gray-100 dark:ring-offset-gray-900 scale-95' : ''"
                    class="relative aspect-square rounded-2xl shadow-sm flex flex-col items-center justify-center gap-2 text-white font-semibold text-center p-3 active:scale-95 transition-transform select-none [touch-action:manipulation]"
                    style="background-color: {{ $sound->color }}"
                >
                    @if ($loop->iteration <= 9)
                        <span class="hidden md:block absolute top-2 start-2 text-xs font-bold text-white/60" aria-hidden="true">{{ $loop->iteration }}</span>
                    @endif
                    <x-sound-icon :sound="$sound" class="w-12 h-12 sm:w-14 sm:h-14 text-4xl rounded-xl shrink-0" />
                    <span class="text-sm break-words line-clamp-2">{{ $sound->name }}</span>
                </button>
            @endforeach
        </div>
    @endif
</div>
