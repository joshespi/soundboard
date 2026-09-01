<div>
    <div class="flex items-center justify-between gap-4 mb-6">
        <x-back-link :href="route('dashboard')">{{ __('All screens') }}</x-back-link>
        <x-link-button :href="route('screens.manage', $screen)" variant="secondary" wire:navigate>{{ __('Edit sounds') }}</x-link-button>
    </div>

    @if ($sounds->isEmpty())
        <x-empty-state>{{ __('This screen has no sounds yet.') }}</x-empty-state>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-4">
            @foreach ($sounds as $sound)
                <button
                    type="button"
                    x-data
                    x-on:click="new Audio(@js($sound->url)).play()"
                    class="aspect-square rounded-2xl shadow-sm flex flex-col items-center justify-center gap-2 text-white font-semibold text-center p-3 active:scale-95 transition-transform select-none [touch-action:manipulation]"
                    style="background-color: {{ $sound->color }}"
                >
                    <x-sound-icon :sound="$sound" class="w-12 h-12 sm:w-14 sm:h-14 text-4xl rounded-xl shrink-0" />
                    <span class="text-sm break-words line-clamp-2">{{ $sound->name }}</span>
                </button>
            @endforeach
        </div>
    @endif
</div>
