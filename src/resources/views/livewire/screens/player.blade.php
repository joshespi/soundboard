{{-- Video tiles never go through the audio engine (it pre-decodes whole files); they open the overlay at the bottom. --}}
<div x-data="soundboard(@js($audioUrls))" x-on:keydown.window="onKey($event)">
    <div class="flex items-center justify-between gap-4 mb-6">
        @auth
            <x-back-link :href="route('dashboard')">{{ __('All screens') }}</x-back-link>
        @else
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $screen->name }}</h1>
        @endauth
        <div class="flex items-center gap-2">
            <button
                type="button"
                x-show="anyPlaying"
                x-cloak
                x-on:click="stopAll()"
                class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest transition"
            >{{ __('Stop all') }}</button>
            @can('update', $screen)
                <x-link-button :href="route('screens.manage', $screen)" variant="secondary" wire:navigate>{{ __('Edit sounds') }}</x-link-button>
            @endcan
        </div>
    </div>

    @if ($sounds->isEmpty())
        <x-empty-state>{{ __('This screen has no sounds yet.') }}</x-empty-state>
    @else
        @auth
            <x-voice-presets class="mb-6" :heading="__('Your voice')" />
        @endauth

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-4">
            @foreach ($sounds as $sound)
                <button
                    type="button"
                    @if ($sound->is_video)
                        x-on:click="stopAll(); $dispatch('play-video', { url: @js($sound->url) })"
                    @else
                        x-on:click="play(@js($sound->url))"
                    @endif
                    :class="isPlaying(@js($sound->url)) ? 'ring-4 ring-white/70 ring-offset-2 ring-offset-gray-100 dark:ring-offset-gray-900 scale-95' : ''"
                    class="relative aspect-square rounded-2xl shadow-sm flex flex-col items-center justify-center gap-2 text-white font-semibold text-center p-3 active:scale-95 transition-transform select-none [touch-action:manipulation]"
                    style="background-color: {{ $sound->color }}"
                >
                    @if ($sound->is_video)
                        <span class="absolute top-2 end-2 text-xs font-bold text-white/70" aria-hidden="true">▶</span>
                    @else
                        @php($shortcut = $audioUrls->search($sound->url) + 1)
                        @if ($shortcut <= 9)
                            <span class="hidden md:block absolute top-2 start-2 text-xs font-bold text-white/60" aria-hidden="true">{{ $shortcut }}</span>
                        @endif
                    @endif
                    <x-sound-icon :sound="$sound" class="w-12 h-12 sm:w-14 sm:h-14 text-4xl rounded-xl shrink-0" />
                    <span class="text-sm break-words line-clamp-2">{{ $sound->name }}</span>
                </button>
            @endforeach
        </div>

        @if ($sounds->contains->is_video)
            <div
                x-data="{ url: null }"
                x-on:play-video.window="url = $event.detail.url"
                x-on:keydown.escape.window="url = null"
                x-show="url"
                x-cloak
                class="fixed inset-0 z-50 bg-black flex items-center justify-center"
            >
                <template x-if="url">
                    <video :src="url" controls autoplay playsinline x-on:ended="url = null" class="w-full h-full"></video>
                </template>
                <button
                    type="button"
                    x-on:click="url = null"
                    class="absolute top-3 end-3 px-5 py-3 rounded-full bg-white/90 text-gray-900 text-lg font-bold shadow [touch-action:manipulation]"
                    aria-label="{{ __('Close video') }}"
                >✕</button>
            </div>
        @endif
    @endif
</div>
