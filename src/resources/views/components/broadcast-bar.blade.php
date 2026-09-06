{{-- Lives inside @persist in layouts.app so it survives wire:navigate without flicker.
     Hidden entirely until broadcast has been used at least once, so it never clutters
     the app for people who only tap sounds. --}}
<div
    x-data="broadcastBar"
    x-show="broadcast.supported && (broadcast.active || broadcast.wasEnabled || broadcast.error || broadcast.notice)"
    x-cloak
    class="relative z-30"
>
    <div
        :class="broadcast.error
            ? 'bg-red-600'
            : (broadcast.active ? 'bg-green-600' : 'bg-gray-700')"
        class="text-white"
    >
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 flex items-center gap-3 flex-wrap">
            <span class="flex items-center gap-2 font-semibold text-sm">
                <span
                    :class="broadcast.active && !broadcast.error ? 'bg-white animate-pulse' : 'bg-white/50'"
                    class="w-2.5 h-2.5 rounded-full"
                ></span>
                <span x-show="broadcast.error" x-cloak>{{ __('Voice interrupted') }}</span>
                <span x-show="!broadcast.error && broadcast.active" x-cloak>{{ __('ON AIR — Discord hears you') }}</span>
                <span x-show="!broadcast.error && !broadcast.active" x-cloak>{{ __('Voice off — Discord hears nothing') }}</span>
            </span>

            <span x-show="broadcast.error" x-cloak class="text-sm text-white/90" x-text="broadcast.error"></span>
            <span x-show="broadcast.notice && !broadcast.error" x-cloak class="text-sm text-white/90" x-text="broadcast.notice"></span>

            <div class="ms-auto flex items-center gap-2">
                <button
                    type="button"
                    x-on:click="toggleLive()"
                    x-bind:disabled="busy || broadcast.starting"
                    class="px-3 py-1 rounded-md bg-white/20 hover:bg-white/30 text-sm font-semibold transition disabled:opacity-50"
                >
                    <span x-show="broadcast.starting" x-cloak>{{ __('Starting…') }}</span>
                    <span x-show="!broadcast.starting && broadcast.active" x-cloak>{{ __('Stop') }}</span>
                    <span x-show="!broadcast.starting && !broadcast.active" x-cloak>{{ __('Go live') }}</span>
                </button>

                <a href="{{ route('voice.setup') }}" wire:navigate class="px-3 py-1 rounded-md bg-white/20 hover:bg-white/30 text-sm font-semibold transition">
                    {{ __('Setup') }}
                </a>
            </div>
        </div>
    </div>
</div>
