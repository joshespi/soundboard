/**
 * Audio device discovery and the small slice of settings that belong to this browser.
 *
 * Device IDs are per-origin, per-browser-profile and per-machine, and they rotate when
 * site data is cleared or a driver is reinstalled — so they live in localStorage, not in
 * the database. We store the label next to each ID and fall back to matching on the label,
 * which turns "I reinstalled VB-CABLE" from a support call into a non-event.
 */

import { DEFAULT_PRESET } from './presets.js';

const STORAGE_KEY = 'soundboard.broadcast.v1';

/**
 * On an insecure origin `navigator.mediaDevices` is undefined rather than a partial
 * object, so the optional chaining here is load-bearing.
 */
export const SUPPORTED = typeof window !== 'undefined'
    && typeof HTMLMediaElement !== 'undefined'
    && 'setSinkId' in HTMLMediaElement.prototype
    && !!navigator.mediaDevices?.getUserMedia
    && !!(window.AudioContext || window.webkitAudioContext);

/** getUserMedia needs a secure context; http://localhost counts, http://192.168.x.x does not. */
export const SECURE_CONTEXT = typeof window !== 'undefined' && window.isSecureContext;

const DEFAULTS = {
    enabled: false,
    sinkId: '',
    sinkLabel: '',
    micId: '',
    micLabel: '',
    preset: DEFAULT_PRESET,
    micMonitor: false,
    usingSpeakers: false,
    acknowledgedHeadphones: false,
};

export function loadSettings() {
    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);

        return raw ? { ...DEFAULTS, ...JSON.parse(raw) } : { ...DEFAULTS };
    } catch {
        // Private mode, disabled storage, or corrupt JSON — defaults are always fine.
        return { ...DEFAULTS };
    }
}

export function saveSettings(settings) {
    try {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(settings));
    } catch {
        // Not being able to remember the device choice is survivable; don't break the page.
    }
}

/**
 * Device labels are empty strings until the origin holds a microphone grant, so callers
 * must getUserMedia before this returns anything a human can read.
 */
export async function listDevices() {
    if (!SUPPORTED) {
        return { inputs: [], outputs: [], labelled: false };
    }

    const devices = await navigator.mediaDevices.enumerateDevices();
    const inputs = devices.filter((device) => device.kind === 'audioinput');
    const outputs = devices.filter((device) => device.kind === 'audiooutput');

    return {
        inputs,
        outputs,
        labelled: [...inputs, ...outputs].some((device) => device.label !== ''),
    };
}

/**
 * We write to CABLE *Input* (a fake speaker); Discord listens to CABLE *Output*.
 * Mixing those two up is the classic VB-CABLE mistake, so only ever auto-pick an
 * "input"-side device here and let the setup page spell out the other half.
 */
const CABLE_SINK = /(cable[\s-]*input|vb-?audio|voicemeeter\s+(input|aux\s+input|vaio))/i;

export function guessCableSink(outputs) {
    return outputs.find((device) => CABLE_SINK.test(device.label)) ?? null;
}

/**
 * Resolve a remembered device: exact ID first, then label, then give up so the UI can
 * prompt instead of silently using the wrong device.
 */
export function resolveDevice(devices, id, label) {
    const wanted = label?.toLowerCase();

    return devices.find((device) => id && device.deviceId === id)
        ?? devices.find((device) => wanted && device.label.toLowerCase() === wanted)
        ?? null;
}

/**
 * Effects need the browser's own speech processing out of the way — AEC fights the ring
 * modulator, noise suppression gates the tail of a pitched voice, and AGC pumps against
 * Monster's low shelf. That's what makes headphones load-bearing, hence the speakers
 * escape hatch.
 */
export function micConstraints(micId, usingSpeakers) {
    return {
        audio: {
            ...(micId ? { deviceId: { exact: micId } } : {}),
            echoCancellation: usingSpeakers,
            noiseSuppression: false,
            autoGainControl: false,
            channelCount: 1,
        },
        video: false,
    };
}
