@props(['id', 'wireModel'])
@php
    // Curated soundboard set, not a full emoji picker.
    $emojis = ['🔊', '🔔', '📯', '🚨', '🎵', '🎶', '🥁', '📣', '😂', '🤣', '😭', '😱', '😎', '🥳', '🤬', '🤯', '💀', '👻', '🤖', '👽', '💩', '🐱', '🐶', '🐸', '🦆', '🦁', '🐷', '🚀', '💥', '⚡', '🔥', '✨', '🎉', '⚽', '🏀', '🎮', '❤️', '👍', '👎', '🤡'];
@endphp
<div class="relative" x-data="{ open: false }">
    <div class="flex gap-1">
        <input
            wire:model="{{ $wireModel }}"
            id="{{ $id }}"
            type="text"
            maxlength="8"
            placeholder="🔊"
            class="block w-full min-w-0 text-center text-xl rounded-lg shadow-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600"
        >
        <button
            type="button"
            x-on:click="open = !open"
            class="shrink-0 w-9 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center justify-center text-lg"
            aria-label="{{ __('Pick an emoji') }}"
        >🙂</button>
    </div>

    <div
        x-show="open"
        x-cloak
        x-on:click.outside="open = false"
        x-transition.origin.top
        class="absolute z-20 right-0 mt-1 w-56 max-h-52 overflow-y-auto p-2 grid grid-cols-6 gap-1 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg"
    >
        @foreach ($emojis as $emoji)
            <button
                type="button"
                x-on:click="$wire.set('{{ $wireModel }}', '{{ $emoji }}'); open = false"
                class="text-xl leading-none py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
            >{{ $emoji }}</button>
        @endforeach
    </div>
</div>
