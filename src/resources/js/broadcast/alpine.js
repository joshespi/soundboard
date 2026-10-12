/**
 * Alpine glue for the broadcast feature. Nothing here owns audio state — the engine is
 * the single source of truth and these components only mirror what it publishes, then
 * push user intent back into it.
 */

import * as engine from './engine.js';
import { PRESETS } from './presets.js';
import { guessCableSink, listDevices, resolveDevice } from './devices.js';

/** One physical plug or unplug fires `devicechange` several times in a burst. */
const DEVICE_SETTLE_MS = 300;

/** Bridges engine state into an Alpine component and cleans up on teardown. */
const bound = (component) => ({
    broadcast: engine.getState(),
    _unsubscribe: null,
    init() {
        this._unsubscribe = engine.subscribe((state) => {
            this.broadcast = state;
        });
        this.onInit?.();
    },
    destroy() {
        this._unsubscribe?.();
        this.onDestroy?.();
    },
    ...component,
});

/** Shared by every control that can start or stop the broadcast. */
const liveToggle = {
    busy: false,
    async toggleLive() {
        this.busy = true;
        await (this.broadcast.active ? engine.disable() : engine.enable());
        this.busy = false;
    },
};

export function registerBroadcast(Alpine) {
    Alpine.data('soundboard', (urls = []) => bound({
        /** url -> how many overlapping copies of that clip are sounding right now. */
        playing: {},
        onInit() {
            engine.warm(urls);
        },
        onDestroy() {
            engine.stopAll();
        },
        play(url) {
            this.playing[url] = (this.playing[url] ?? 0) + 1;

            // Falls back to plain `new Audio()` whenever broadcast is off.
            engine.play(url, () => {
                this.playing[url]--;
            });
        },
        isPlaying(url) {
            return (this.playing[url] ?? 0) > 0;
        },
        get anyPlaying() {
            return Object.values(this.playing).some((count) => count > 0);
        },
        stopAll() {
            engine.stopAll();
        },
        /** 1-9 play the first nine tiles in order; Escape silences everything. */
        onKey(event) {
            if (event.repeat || event.ctrlKey || event.metaKey || event.altKey || event.target.closest?.('input, textarea, select, [contenteditable]')) {
                return;
            }

            if (event.key === 'Escape') {
                this.stopAll();
            } else if (/^[1-9]$/.test(event.key) && urls[Number(event.key) - 1]) {
                this.play(urls[Number(event.key) - 1]);
            }
        },
    }));

    Alpine.data('voicePresets', (always = false) => bound({
        presets: PRESETS,
        always,
        /** The setup page auditions voices offline; the player only offers them live. */
        get visible() {
            return this.broadcast.supported && (this.always || this.broadcast.active);
        },
        choose(key) {
            engine.setPreset(key);
        },
    }));

    Alpine.data('broadcastBar', () => bound({ ...liveToggle }));

    Alpine.data('broadcastSetup', () => bound({
        ...liveToggle,
        inputs: [],
        outputs: [],
        labelled: false,
        asking: false,
        level: 0,
        _meter: null,
        _settle: null,
        _onDeviceChange: null,

        onInit() {
            // Nothing awaits refresh(), so its own failures have to stop here — an
            // enumerateDevices() rejection otherwise surfaces as an unhandled rejection.
            this.refresh().catch(() => {});

            if (navigator.mediaDevices?.addEventListener) {
                this._onDeviceChange = () => {
                    window.clearTimeout(this._settle);
                    this._settle = window.setTimeout(() => this.refresh().catch(() => {}), DEVICE_SETTLE_MS);
                };
                navigator.mediaDevices.addEventListener('devicechange', this._onDeviceChange);
            }

            this._meter = window.setInterval(() => {
                // While live he is alt-tabbed to Discord almost all of the time, and the
                // meter is the only thing this reading feeds.
                if (!document.hidden) {
                    this.level = engine.getLevel();
                }
            }, 100);
        },

        onDestroy() {
            window.clearInterval(this._meter);
            window.clearTimeout(this._settle);

            if (this._onDeviceChange) {
                navigator.mediaDevices.removeEventListener('devicechange', this._onDeviceChange);
            }
        },

        get origin() {
            return window.location.origin;
        },

        get cableFound() {
            return !!guessCableSink(this.outputs);
        },

        async refresh() {
            const { inputs, outputs, labelled } = await listDevices();
            this.inputs = inputs;
            this.outputs = outputs;
            this.labelled = labelled;

            if (!labelled) {
                return;
            }

            // Re-resolve remembered devices against what's actually plugged in now, so a
            // driver reinstall (which reissues every device id) recovers by label.
            const sink = resolveDevice(outputs, this.broadcast.sinkId, this.broadcast.sinkLabel)
                ?? guessCableSink(outputs);

            if (sink && sink.deviceId !== this.broadcast.sinkId) {
                await engine.setSink(sink.deviceId, sink.label);
            }

            const mic = resolveDevice(inputs, this.broadcast.micId, this.broadcast.micLabel);

            if (mic && mic.deviceId !== this.broadcast.micId) {
                await engine.setMic(mic.deviceId, mic.label);
            }
        },

        /** Device labels stay blank until the origin holds a mic grant. */
        async grant() {
            this.asking = true;

            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                stream.getTracks().forEach((track) => track.stop());
                await this.refresh();
            } catch {
                // The engine surfaces the real error when he tries to go live.
            } finally {
                this.asking = false;
            }
        },

        /** Both <select>s carry the human-readable label in a data attribute. */
        _picked(event) {
            const option = event.target.selectedOptions[0];

            return [event.target.value, option?.dataset.label ?? ''];
        },

        async chooseSink(event) {
            await engine.setSink(...this._picked(event));
        },

        async chooseMic(event) {
            await engine.setMic(...this._picked(event));
        },

        toggleHeadphones(event) {
            engine.setHeadphones(event.target.checked);
        },

        toggleMonitor(event) {
            engine.setMicMonitor(event.target.checked);
        },

        toggleSpeakers(event) {
            engine.setUsingSpeakers(event.target.checked);
        },
    }));
}
