@if ($sound->is_video)
    {{-- A show: the video starts straight away (installed apps may autoplay with sound) and
         has its own controls; if the browser blocks autoplay, one tap on play does it. --}}
    <div class="min-h-[85vh] flex items-center justify-center">
        <video src="{{ $sound->url }}" controls autoplay playsinline class="w-full max-w-5xl rounded-2xl bg-black"></video>
    </div>
@else
    {{-- One giant tile that starts playing as soon as the page opens (installed apps are allowed to
         autoplay); if the browser blocks that, one tap plays. Tapping while it plays stops it. --}}
    <div
        x-data="soundboard(@js([$sound->url]))"
        x-init="$nextTick(() => play(@js($sound->url)))"
        class="min-h-[85vh] flex items-center justify-center"
    >
        <button
            type="button"
            x-on:click="isPlaying(@js($sound->url)) ? stopAll() : play(@js($sound->url))"
            :class="isPlaying(@js($sound->url)) ? 'scale-95 ring-8 ring-white/70' : ''"
            class="w-full max-w-lg aspect-square rounded-3xl shadow-lg flex flex-col items-center justify-center gap-4 p-6 text-white text-center select-none transition-transform [touch-action:manipulation]"
            style="background-color: {{ $sound->color ?: '#6366f1' }}"
        >
            <x-sound-icon :sound="$sound" class="w-3/4 h-3/4 text-[9rem] rounded-2xl" />
            <span class="text-2xl font-bold break-words line-clamp-2">{{ $sound->name }}</span>
        </button>
    </div>
@endif
