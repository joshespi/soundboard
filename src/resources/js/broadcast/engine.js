/**
 * The broadcast engine: one AudioContext that mixes soundboard clips and the live
 * voice-changed microphone, and sends the mix to a chosen output device (VB-CABLE's
 * "CABLE Input") so Discord can pick it up as a microphone.
 *
 *   clip ──► clipBus ─┬─► clipToCable ───┐
 *                     └─► clipToMonitor ─┼──► ctx.destination   (headphones)
 *                                        │
 *   mic ──► highpass ─► chainIn ═╡PRESET╞═ chainOut ─► comp ─┬─► voiceToCable ──┤
 *                                                            └─► voiceToMonitor ┘
 *                                                                        ▼
 *                                              MediaStreamAudioDestinationNode
 *                                                  └─► hidden <audio>.setSinkId(cable)
 *
 * Nothing here is constructed until enable() is called from a user gesture: with
 * broadcast off there is no AudioContext, no microphone prompt, and play() falls back to
 * the plain `new Audio(url).play()` the player used before this existed.
 *
 * This is a module-scope singleton, so it survives wire:navigate for free; only the
 * hidden <audio> element needs re-attaching after Livewire swaps the body.
 *
 * MANUAL TEST CHECKLIST (no browser harness in this repo — see the plan):
 *   1. Broadcast off  -> tiles still play, no mic prompt, zero AudioContext in DevTools.
 *   2. Firefox        -> unsupported card shown, player grid unaffected.
 *   3. LAN IP origin  -> secure-context warning on /voice; localhost -> no warning.
 *   4. Mic prompt appears once; device labels populate only after granting.
 *   5. Installing VB-CABLE with the page open makes "CABLE Input" appear (devicechange).
 *   6. Windows > Sound > Recording > CABLE Output shows a moving level meter.
 *   7. Discord > Voice & Video > Mic Test hears him; every preset audibly differs.
 *   8. Tile press heard in both headphones and Discord, with overlap.
 *   9. Live -> navigate to Manage and back -> still ON AIR, no gap, no doubled voice.
 *  10. Closing the tab while live triggers "Leave site?".
 *  11. Two tabs -> enabling in the second disables the first with a message.
 */

import { DEFAULT_PRESET, findPreset, gainOf } from './presets.js';
import {
    SECURE_CONTEXT,
    SUPPORTED,
    loadSettings,
    micConstraints,
    saveSettings,
} from './devices.js';

const CHANNEL_NAME = 'soundboard-broadcast';
const TAB_ID = Math.random().toString(36).slice(2);

const settings = loadSettings();
let ctx = null;
let graph = null;
let stream = null;
let micSource = null;
let sink = null;
let preset = null;
let active = false;
let starting = false;
let error = '';
let notice = '';
let warmUrls = [];

/**
 * Decoded clips, keyed by URL. Bounded in practice by how many sounds a screen has —
 * roughly 1 MB per decoded 3-second clip, so a 50-tile screen sits around 50 MB. Fine on
 * a desktop; not worth an LRU. Cleared whenever the context is closed, since AudioBuffers
 * belong to the context that decoded them.
 */
const buffers = new Map();
const inFlight = new Map();

const subscribers = new Set();

let channel = null;

export function getState() {
    return {
        supported: SUPPORTED,
        secure: SECURE_CONTEXT,
        active,
        starting,
        error,
        notice,
        preset: settings.preset,
        micMonitor: settings.micMonitor,
        usingSpeakers: settings.usingSpeakers,
        sinkId: settings.sinkId,
        sinkLabel: settings.sinkLabel,
        micId: settings.micId,
        micLabel: settings.micLabel,
        wasEnabled: settings.enabled,
        acknowledgedHeadphones: settings.acknowledgedHeadphones,
    };
}

export function subscribe(callback) {
    subscribers.add(callback);
    callback(getState());

    return () => subscribers.delete(callback);
}

function notify() {
    const state = getState();
    subscribers.forEach((callback) => callback(state));
}

function persist() {
    saveSettings(settings);
}

// --- graph -----------------------------------------------------------------

function buildGraph() {
    ctx = new (window.AudioContext || window.webkitAudioContext)({ latencyHint: 'interactive' });

    const cable = ctx.createMediaStreamDestination();

    const clipBus = gainOf(ctx, 1);
    const clipToCable = gainOf(ctx, 1);
    const clipToMonitor = gainOf(ctx, 1);
    clipBus.connect(clipToCable).connect(cable);
    clipBus.connect(clipToMonitor).connect(ctx.destination);

    // Kill desk rumble and plosives before any pitch maths sees them.
    const highpass = ctx.createBiquadFilter();
    highpass.type = 'highpass';
    highpass.frequency.value = 80;

    // chainIn/chainOut are permanent. Preset swaps only ever rewire between these two,
    // so the mic path can't be left orphaned halfway through a switch.
    const chainIn = gainOf(ctx, 1);
    const chainOut = gainOf(ctx, 1);
    highpass.connect(chainIn);

    // Stops the drive- and pitch-heavy presets clipping Discord's encoder.
    const comp = ctx.createDynamicsCompressor();
    comp.threshold.value = -18;
    comp.ratio.value = 6;
    comp.attack.value = 0.003;
    comp.release.value = 0.15;

    const voiceToCable = gainOf(ctx, 1);
    const voiceToMonitor = gainOf(ctx, settings.micMonitor ? 0.6 : 0);
    chainOut.connect(comp);
    comp.connect(voiceToCable).connect(cable);
    comp.connect(voiceToMonitor).connect(ctx.destination);

    const analyser = ctx.createAnalyser();
    analyser.fftSize = 1024;
    comp.connect(analyser);

    graph = {
        cable,
        clipBus,
        highpass,
        chainIn,
        chainOut,
        voiceToMonitor,
        analyser,
        meter: new Uint8Array(analyser.fftSize),
    };
}

function applyPreset(key) {
    const definition = findPreset(key);

    if (preset) {
        graph.chainIn.disconnect();
        preset.node.output.disconnect();
        preset.node.stop();
    }

    const node = definition.build(ctx);
    graph.chainIn.connect(node.input);
    node.output.connect(graph.chainOut);
    preset = { key: definition.key, node };
}

// --- the sink element ------------------------------------------------------

function ensureSink() {
    if (!sink) {
        sink = document.createElement('audio');
        sink.id = 'broadcast-sink';
        sink.autoplay = true;
        // The stream is the point; nothing about this element should be visible or
        // reachable, and it must never be muted or the cable goes silent.
        sink.style.display = 'none';
    }

    if (!document.body.contains(sink)) {
        document.body.appendChild(sink);
    }

    return sink;
}

async function attachSink() {
    // Without a chosen output device there's nowhere to send the mix, and playing it to
    // the default speakers would just double every clip back at him. Clearing the choice
    // has to tear the element down too, or it keeps feeding whatever it was last pointed
    // at long after the setting says otherwise.
    if (!settings.sinkId) {
        detachSink();

        return;
    }

    const element = ensureSink();
    element.srcObject = graph.cable.stream;

    // play() can reject as well — an unhandled DOMException here would surface as raw
    // browser text in the ON AIR bar, so it gets the same explanation as setSinkId.
    try {
        await element.setSinkId(settings.sinkId);
        await element.play();
    } catch {
        throw new Error(
            `Couldn't send audio to "${settings.sinkLabel || 'that output device'}". `
            + 'It may have been unplugged or uninstalled — pick it again on the Voice page.',
        );
    }
}

function detachSink() {
    if (!sink) {
        return;
    }

    sink.pause();
    sink.srcObject = null;
    sink.remove();
}

// --- clips -----------------------------------------------------------------

function loadBuffer(url) {
    if (buffers.has(url)) {
        return Promise.resolve(buffers.get(url));
    }

    if (inFlight.has(url)) {
        return inFlight.get(url);
    }

    // Goes through the service worker exactly as <audio> did, so the cache-first
    // /storage/ rule still serves these offline.
    const request = fetch(url)
        .then((response) => response.arrayBuffer())
        .then((bytes) => ctx.decodeAudioData(bytes))
        .then((buffer) => {
            // warm() can evict this URL while the decode is still running. Only cache the
            // result if we're still the registered request, or a clip from a screen he
            // has already navigated away from would reinstate itself.
            if (inFlight.get(url) === request) {
                buffers.set(url, buffer);
                inFlight.delete(url);
            }

            return buffer;
        })
        .catch((problem) => {
            if (inFlight.get(url) === request) {
                inFlight.delete(url);
            }

            throw problem;
        });

    inFlight.set(url, request);

    return request;
}

/**
 * resume() rejects on a context a concurrent teardown has already closed, and nothing
 * downstream cares — every caller here is best-effort.
 */
function resumeContext() {
    ctx?.resume().catch(() => {});
}

/** Stop callbacks for every clip currently sounding, so "stop all" can silence them. */
const playingClips = new Set();

/** Bumped by stopAll() so a clip still decoding when it's pressed doesn't start afterwards. */
let stopGeneration = 0;

function trackClip(stop, onEnd) {
    const clip = {
        stop,
        end() {
            if (playingClips.delete(clip)) {
                onEnd();
            }
        },
    };

    playingClips.add(clip);

    return clip;
}

function playFallback(url, onEnd) {
    const audio = new Audio(url);
    const clip = trackClip(() => audio.pause(), onEnd);

    audio.addEventListener('ended', clip.end);
    audio.play().catch(clip.end);
}

/** onEnd fires once, when the clip finishes, is stopped, or fails to play. */
export function play(url, onEnd = () => {}) {
    if (!active || !ctx) {
        playFallback(url, onEnd);

        return;
    }

    resumeContext();

    const generation = stopGeneration;

    loadBuffer(url)
        .then((buffer) => {
            if (generation !== stopGeneration) {
                onEnd();

                return;
            }

            const source = ctx.createBufferSource();
            source.buffer = buffer;
            source.connect(graph.clipBus);

            const clip = trackClip(() => source.stop(), onEnd);
            source.addEventListener('ended', clip.end);

            try {
                source.start();
            } catch {
                clip.end();
            }
        })
        .catch(() => {
            if (generation !== stopGeneration) {
                onEnd();

                return;
            }

            playFallback(url, onEnd);
        });
}

export function stopAll() {
    stopGeneration++;

    for (const clip of [...playingClips]) {
        clip.stop();
        clip.end();
    }
}

/** Pre-decodes a screen's clips so the first tap of each tile is as fast as the rest. */
export function warm(urls) {
    warmUrls = Array.isArray(urls) ? urls : [];

    if (!active || !ctx) {
        return;
    }

    // Each screen he visits decodes its own clips; without this the previous screens'
    // buffers stay resident for the whole live session at roughly 1 MB per clip.
    const keep = new Set(warmUrls);

    for (const url of buffers.keys()) {
        if (!keep.has(url)) {
            buffers.delete(url);
        }
    }

    // Drop pending decodes for the screen he just left too; loadBuffer's identity check
    // makes sure the abandoned ones can't write themselves back into `buffers`.
    for (const url of inFlight.keys()) {
        if (!keep.has(url)) {
            inFlight.delete(url);
        }
    }

    const queue = [...warmUrls];
    const idle = window.requestIdleCallback ?? ((fn) => setTimeout(fn, 50));

    const step = () => {
        const url = queue.shift();

        if (!url) {
            return;
        }

        loadBuffer(url).catch(() => {}).finally(() => idle(step));
    };

    idle(step);
}

export function getLevel() {
    if (!active || !graph) {
        return 0;
    }

    graph.analyser.getByteTimeDomainData(graph.meter);

    let sum = 0;

    for (let i = 0; i < graph.meter.length; i++) {
        const value = (graph.meter[i] - 128) / 128;
        sum += value * value;
    }

    return Math.min(1, Math.sqrt(sum / graph.meter.length) * 3);
}

// --- lifecycle -------------------------------------------------------------

function guardUnload(event) {
    event.preventDefault();
    // Closing this tab takes his microphone away from Discord entirely, which is a
    // genuinely confusing way to go silent mid-call.
    event.returnValue = '';
}

function watchTrack(track) {
    const fail = () => {
        if (!active) {
            return;
        }

        error = 'Your microphone stopped. It may have been unplugged or taken by another app.';
        notify();
    };

    track.addEventListener('ended', fail);
    track.addEventListener('mute', fail);
}

async function openMic() {
    stream = await navigator.mediaDevices.getUserMedia(
        micConstraints(settings.micId, settings.usingSpeakers),
    );
    stream.getAudioTracks().forEach(watchTrack);
    micSource = ctx.createMediaStreamSource(stream);
    micSource.connect(graph.highpass);
}

function closeMic() {
    micSource?.disconnect();
    micSource = null;
    stream?.getTracks().forEach((track) => track.stop());
    stream = null;
}

function describeFailure(problem) {
    if (problem?.name === 'NotAllowedError') {
        return 'Microphone access was blocked. Allow it in the browser address bar, then try again.';
    }

    if (problem?.name === 'NotFoundError') {
        return 'No microphone was found. Plug one in and try again.';
    }

    // micConstraints pins the saved mic with `deviceId: { exact }`, so a microphone that
    // has since been unplugged fails here rather than quietly falling back to another one.
    if (problem?.name === 'OverconstrainedError') {
        return `The microphone you picked${settings.micLabel ? ` ("${settings.micLabel}")` : ''} `
            + 'is not plugged in any more. Choose another one on the Voice page.';
    }

    return problem?.message || 'Something went wrong starting the voice bridge.';
}

export async function enable() {
    if (active || starting) {
        return active;
    }

    if (!SUPPORTED) {
        error = SECURE_CONTEXT
            ? 'This browser can\'t choose an audio output device. Use Chrome or Edge.'
            : 'Microphone access needs a secure page. Open the app at http://localhost instead.';
        notify();

        return false;
    }

    // Going live with no sink chosen would light up the ON AIR bar while the mix went
    // nowhere — the one failure mode that looks exactly like success.
    if (!settings.sinkId) {
        error = 'Choose where to send the sound first — pick CABLE Input on the Voice page.';
        notify();

        return false;
    }

    // Speakers plus an open mic is a feedback loop; one of the two boxes in step 3 has to
    // be ticked before anything is allowed to reach the microphone.
    if (!settings.acknowledgedHeadphones && !settings.usingSpeakers) {
        error = 'Put headphones on and tick the box on the Voice page — or tick the speakers box instead.';
        notify();

        return false;
    }

    starting = true;
    error = '';
    notice = '';
    notify();

    try {
        buildGraph();
        await ctx.resume();
        await openMic();
        applyPreset(settings.preset || DEFAULT_PRESET);
        await attachSink();

        active = true;
        settings.enabled = true;
        persist();

        arm();
        channel?.postMessage({ type: 'claim', tab: TAB_ID });
        warm(warmUrls);
    } catch (problem) {
        error = describeFailure(problem);
        await teardown();
    } finally {
        starting = false;
        notify();
    }

    return active;
}

async function teardown() {
    disarm();
    detachSink();
    closeMic();

    if (preset) {
        preset.node.stop();
        preset = null;
    }

    buffers.clear();
    inFlight.clear();

    if (ctx) {
        try {
            await ctx.close();
        } catch {
            // Already closed.
        }
    }

    ctx = null;
    graph = null;
    active = false;
}

export async function disable({ remember = true } = {}) {
    await teardown();

    if (remember) {
        settings.enabled = false;
        persist();
    }

    notify();
}

export function setPreset(key) {
    const next = findPreset(key).key;

    // Re-tapping the tile he's already on would duck the mic and rebuild the whole
    // subgraph — including Cave's 845 KB impulse — for no audible change.
    if (next === settings.preset && (!active || preset?.key === next)) {
        return;
    }

    settings.preset = next;
    persist();

    if (active) {
        // Duck briefly so the rewire doesn't click.
        const now = ctx.currentTime;
        graph.chainOut.gain.setTargetAtTime(0, now, 0.005);

        window.setTimeout(() => {
            if (!active) {
                return;
            }

            applyPreset(settings.preset);
            graph.chainOut.gain.setTargetAtTime(1, ctx.currentTime, 0.005);
            notify();
        }, 20);
    }

    notify();
}

export function setMicMonitor(on) {
    settings.micMonitor = !!on;
    persist();

    if (active) {
        graph.voiceToMonitor.gain.setTargetAtTime(
            settings.micMonitor ? 0.6 : 0,
            ctx.currentTime,
            0.02,
        );
    }

    notify();
}

export async function setSink(deviceId, label = '') {
    settings.sinkId = deviceId;
    settings.sinkLabel = label;
    persist();

    if (active) {
        try {
            await attachSink();
            error = '';
        } catch (problem) {
            error = problem.message;
        }
    }

    notify();
}

/** Retakes the stream under the current constraints. Nothing to do while he's not live. */
async function restartMic() {
    if (!active) {
        return;
    }

    closeMic();

    try {
        await openMic();
        error = '';
    } catch (problem) {
        error = describeFailure(problem);
    }
}

export async function setMic(deviceId, label = '') {
    settings.micId = deviceId;
    settings.micLabel = label;
    persist();

    await restartMic();
    notify();
}

export async function setUsingSpeakers(on) {
    settings.usingSpeakers = !!on;
    persist();

    // Echo cancellation is a capture-time constraint, so the stream has to be retaken.
    await restartMic();
    notify();
}

/** Gates enable() alongside usingSpeakers — see the feedback-loop guard there. */
export function setHeadphones(on) {
    settings.acknowledgedHeadphones = !!on;
    persist();
    notify();
}

// --- cross-tab and navigation ----------------------------------------------

function onClaim(event) {
    if (event.data?.type !== 'claim' || event.data.tab === TAB_ID) {
        return;
    }

    // Two live tabs would sum two copies of his voice into the cable.
    disable({ remember: false }).then(() => {
        notice = 'Voice moved to another Soundboard tab.';
        notify();
    });
}

// Livewire's SPA navigation swaps the body, which takes the hidden sink element with it.
// The engine itself is module scope and survives untouched.
function onNavigated() {
    resumeContext();

    if (sink && !document.body.contains(sink)) {
        ensureSink();
        attachSink().catch((problem) => {
            error = problem.message;
            notify();
        });
    }
}

// Only ever resume — suspending on hidden would mute him the moment he alt-tabs to
// Discord, which is always.
function onVisibilityChange() {
    if (ctx?.state === 'suspended') {
        resumeContext();
    }
}

/**
 * Every listener here is a no-op unless he's live, so they're armed by enable() and
 * disarmed by teardown() alongside the beforeunload guard. Importing this module — which
 * app.js does on every page, including login — must not open a BroadcastChannel or
 * install document listeners for a feature that may never be switched on.
 */
function arm() {
    window.addEventListener('beforeunload', guardUnload);
    document.addEventListener('livewire:navigated', onNavigated);
    document.addEventListener('visibilitychange', onVisibilityChange);

    try {
        channel = 'BroadcastChannel' in window ? new BroadcastChannel(CHANNEL_NAME) : null;
    } catch {
        channel = null;
    }

    channel?.addEventListener('message', onClaim);
}

function disarm() {
    window.removeEventListener('beforeunload', guardUnload);
    document.removeEventListener('livewire:navigated', onNavigated);
    document.removeEventListener('visibilitychange', onVisibilityChange);

    channel?.close();
    channel = null;
}
