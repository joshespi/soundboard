import { registerBroadcast } from './broadcast/alpine.js';

/**
 * This file is loaded by @vite from <head> as a deferred module, while Livewire injects
 * its own bundle (and Alpine with it) at the end of <body> — so this listener is always
 * registered before Alpine boots. Keep @vite above Livewire's scripts in layouts/app.
 */
document.addEventListener('alpine:init', () => {
    registerBroadcast(Alpine);
});
