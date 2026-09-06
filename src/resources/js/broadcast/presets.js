/**
 * Voice changer presets. Each one builds a small Web Audio subgraph with a stable
 * `input` and `output`; the engine splices it between its permanent chainIn/chainOut
 * gains, so presets never touch the mic source or the outputs.
 *
 * `build(ctx)` returns `{ input, output, stop() }`. `stop()` must halt every oscillator
 * and buffer source it started — the engine calls it on every preset switch, and
 * anything left running would stack up on the next one.
 *
 * All impulse responses and noise beds are synthesized in code, so there's no binary
 * audio checked into the repo (same reasoning as the seeder's WAV tones).
 */

import { createPitchShifter } from './pitch-shift.js';

/** Connects nodes front to back and hands back the ends. */
function series(nodes) {
    nodes.reduce((from, to) => {
        from.connect(to);

        return to;
    });

    return { input: nodes[0], output: nodes[nodes.length - 1] };
}

function biquad(ctx, type, frequency, { q, gain } = {}) {
    const node = ctx.createBiquadFilter();
    node.type = type;
    node.frequency.value = frequency;
    if (q !== undefined) node.Q.value = q;
    if (gain !== undefined) node.gain.value = gain;

    return node;
}

export function gainOf(ctx, value) {
    const node = ctx.createGain();
    node.gain.value = value;

    return node;
}

/** Soft-clipping curve — `amount` 0 is clean, ~30 is thoroughly crunchy. */
function driveCurve(amount) {
    const samples = 1024;
    const curve = new Float32Array(samples);
    const k = amount * 2;

    for (let i = 0; i < samples; i++) {
        const x = (i * 2) / samples - 1;
        curve[i] = ((1 + k) * x) / (1 + k * Math.abs(x));
    }

    return curve;
}

function drive(ctx, amount) {
    const shaper = ctx.createWaveShaper();
    shaper.curve = driveCurve(amount);
    shaper.oversample = '2x';

    return shaper;
}

/**
 * Synthesizing these costs a few hundred thousand samples, and they're rebuilt on every
 * preset tap — which lands inside setPreset's 20ms duck, while the live graph is being
 * rewired. Cache per AudioContext: the entries die with the context that owns them, so
 * there's nothing to invalidate on teardown.
 */
function perContext(build) {
    const cache = new WeakMap();

    return (ctx) => {
        if (!cache.has(ctx)) {
            cache.set(ctx, build(ctx));
        }

        return cache.get(ctx);
    };
}

const noiseBuffer = perContext((ctx, seconds = 2) => {
    const length = Math.floor(ctx.sampleRate * seconds);
    const buffer = ctx.createBuffer(1, length, ctx.sampleRate);
    const data = buffer.getChannelData(0);

    for (let i = 0; i < length; i++) {
        data[i] = Math.random() * 2 - 1;
    }

    return buffer;
});

/** Exponentially decaying noise burst with one early reflection per side. */
const caveImpulse = perContext((ctx, seconds = 2.2, decay = 2.6) => {
    const length = Math.floor(ctx.sampleRate * seconds);
    const buffer = ctx.createBuffer(2, length, ctx.sampleRate);

    for (let channel = 0; channel < 2; channel++) {
        const data = buffer.getChannelData(channel);

        for (let i = 0; i < length; i++) {
            data[i] = (Math.random() * 2 - 1) * (1 - i / length) ** decay;
        }

        data[Math.floor(ctx.sampleRate * (channel ? 0.031 : 0.023))] += 0.5;
    }

    return buffer;
});

function oscillator(ctx, type, frequency) {
    const osc = ctx.createOscillator();
    osc.type = type;
    osc.frequency.value = frequency;

    return osc;
}

/**
 * Ring modulator: `input * carrier`. A GainNode's gain param sums its inputs, so parking
 * gain at 0 and wiring an oscillator into it multiplies the signal by the oscillator.
 */
function ringMod(ctx, frequency) {
    const node = gainOf(ctx, 0);
    const osc = oscillator(ctx, 'sine', frequency);
    osc.connect(node.gain);
    osc.start();

    return { node, osc };
}

/** A pitch shifter, already tuned. Its own `stop()` is what teardown needs. */
function shifted(ctx, octaves) {
    const shifter = createPitchShifter(ctx);
    shifter.setPitchOffset(octaves);

    return shifter;
}

export const PRESETS = [
    {
        key: 'normal',
        label: 'Normal',
        emoji: '🙂',
        description: 'Your real voice. Lowest delay.',
        build(ctx) {
            // A true passthrough, not an off switch — while Discord's input is CABLE
            // Output this is the only way his own voice reaches the call.
            const node = gainOf(ctx, 1);

            return { input: node, output: node, stop() {} };
        },
    },
    {
        key: 'chipmunk',
        label: 'Chipmunk',
        emoji: '🐿️',
        description: 'Squeaky and fast.',
        build(ctx) {
            const shifter = shifted(ctx, 1);

            const { output } = series([
                shifter.output,
                biquad(ctx, 'highshelf', 3000, { gain: 3 }),
                biquad(ctx, 'peaking', 2600, { q: 1, gain: 4 }),
            ]);

            return { input: shifter.input, output, stop: () => shifter.stop() };
        },
    },
    {
        key: 'monster',
        label: 'Monster',
        emoji: '👹',
        description: 'Deep and growly.',
        build(ctx) {
            const shifter = shifted(ctx, -0.75);

            const { output } = series([
                shifter.output,
                biquad(ctx, 'lowshelf', 200, { gain: 6 }),
                drive(ctx, 12),
                biquad(ctx, 'lowpass', 5000),
            ]);

            return { input: shifter.input, output, stop: () => shifter.stop() };
        },
    },
    {
        key: 'robot',
        label: 'Robot',
        emoji: '🤖',
        description: 'Metallic and buzzy.',
        build(ctx) {
            const input = gainOf(ctx, 1);
            const output = gainOf(ctx, 1);

            const ring = ringMod(ctx, 62);
            const band = biquad(ctx, 'bandpass', 1400, { q: 0.7 });

            // Short comb delay for the metallic tail.
            const comb = ctx.createDelay();
            comb.delayTime.value = 0.028;
            const feedback = gainOf(ctx, 0.32);

            input.connect(ring.node);
            ring.node.connect(band);
            band.connect(output);
            band.connect(comb);
            comb.connect(feedback);
            feedback.connect(comb);
            comb.connect(output);

            return {
                input,
                output,
                stop() {
                    ring.osc.stop();
                    ring.osc.disconnect();
                    feedback.disconnect();
                },
            };
        },
    },
    {
        key: 'alien',
        label: 'Alien',
        emoji: '👽',
        description: 'Wobbly and otherworldly.',
        build(ctx) {
            const shifter = shifted(ctx, 0.45);
            const output = gainOf(ctx, 1);

            // Vibrato: a slow LFO wobbling a very short delay line.
            const vibrato = ctx.createDelay();
            vibrato.delayTime.value = 0.012;
            const lfo = oscillator(ctx, 'sine', 6.5);
            const lfoDepth = gainOf(ctx, 0.0022);
            lfo.connect(lfoDepth);
            lfoDepth.connect(vibrato.delayTime);
            lfo.start();

            const ring = ringMod(ctx, 9);
            const wet = gainOf(ctx, 0.4);
            const dry = gainOf(ctx, 0.6);

            shifter.output.connect(vibrato);
            vibrato.connect(dry);
            vibrato.connect(ring.node);
            ring.node.connect(wet);
            dry.connect(output);
            wet.connect(output);

            return {
                input: shifter.input,
                output,
                stop() {
                    shifter.stop();
                    [lfo, ring.osc].forEach((osc) => {
                        osc.stop();
                        osc.disconnect();
                    });
                },
            };
        },
    },
    {
        key: 'radio',
        label: 'Radio',
        emoji: '📻',
        description: 'Crackly walkie-talkie.',
        build(ctx) {
            const input = gainOf(ctx, 1);
            const output = gainOf(ctx, 1);

            // Two filters in series beat one bandpass here — steeper skirts, more
            // convincingly like a real handset.
            series([
                input,
                biquad(ctx, 'highpass', 520, { q: 0.8 }),
                biquad(ctx, 'lowpass', 3100, { q: 0.8 }),
                drive(ctx, 25),
                output,
            ]);

            const noise = ctx.createBufferSource();
            noise.buffer = noiseBuffer(ctx);
            noise.loop = true;
            const hiss = gainOf(ctx, 0.0126); // ~-38 dB
            noise.connect(biquad(ctx, 'bandpass', 1800, { q: 0.6 })).connect(hiss);
            hiss.connect(output);
            noise.start();

            return {
                input,
                output,
                stop() {
                    noise.stop();
                    noise.disconnect();
                },
            };
        },
    },
    {
        key: 'cave',
        label: 'Cave',
        emoji: '🕳️',
        description: 'Big echoey cavern.',
        build(ctx) {
            const input = gainOf(ctx, 1);
            const output = gainOf(ctx, 1);

            const convolver = ctx.createConvolver();
            convolver.buffer = caveImpulse(ctx);

            const dry = gainOf(ctx, 0.45);
            const wet = gainOf(ctx, 0.55);

            input.connect(dry);
            dry.connect(output);
            input.connect(convolver);
            convolver.connect(biquad(ctx, 'lowpass', 4200)).connect(wet);
            wet.connect(output);

            return { input, output, stop() {} };
        },
    },
];

export const DEFAULT_PRESET = 'normal';

export function findPreset(key) {
    return PRESETS.find((preset) => preset.key === key) ?? PRESETS[0];
}
