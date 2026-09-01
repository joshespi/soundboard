@props(['src'])
<button
    type="button"
    x-data="{ playing: false, audio: null }"
    x-init="audio = new Audio(@js($src)); audio.addEventListener('ended', () => playing = false)"
    x-on:click="playing ? audio.pause() : audio.play(); playing = !playing"
    :aria-label="playing ? '{{ __('Pause') }}' : '{{ __('Play') }}'"
    {{ $attributes->merge(['class' => 'w-9 h-9 shrink-0 rounded-full bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 flex items-center justify-center hover:bg-indigo-100 dark:hover:bg-indigo-900 transition']) }}
>
    <svg x-show="!playing" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 translate-x-px"><path d="M6.3 3.6c-.7-.4-1.5.1-1.5.9v11c0 .8.8 1.3 1.5.9l9.4-5.5c.7-.4.7-1.4 0-1.8L6.3 3.6Z" /></svg>
    <svg x-show="playing" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path d="M5.5 3.5A1.5 1.5 0 0 0 4 5v10a1.5 1.5 0 0 0 3 0V5a1.5 1.5 0 0 0-1.5-1.5ZM14.5 3.5A1.5 1.5 0 0 0 13 5v10a1.5 1.5 0 0 0 3 0V5a1.5 1.5 0 0 0-1.5-1.5Z" /></svg>
</button>
