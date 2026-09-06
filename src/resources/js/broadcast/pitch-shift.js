/**
 * Real-time pitch shifter built from stock Web Audio nodes — no AudioWorklet, no npm dep.
 *
 * This is Chris Wilson's "Jungle" algorithm (public domain, from the WebAudio demos):
 * two delay lines whose delayTime is swept by looping ramp buffers, crossfaded by a pair
 * of half-cycle-offset envelopes so the audible line is never the one snapping back to
 * the start of its ramp. Sweeping a delay line resamples what passes through it, which is
 * what shifts the pitch; the crossfade hides the discontinuity.
 *
 * It warbles at large offsets. For Chipmunk/Monster/Alien that reads as character, not
 * as a defect, which is exactly why we're not paying for a granular worklet here.
 *
 * Latency: up to DELAY_TIME * |offset| seconds. Presets that don't call this (Normal,
 * Robot, Radio, Cave) stay on the fast path.
 */

const BUFFER_TIME = 0.1;
const FADE_TIME = 0.05;
const DELAY_TIME = 0.1;

/**
 * Crossfade envelope: ramps up over fadeTime, holds at 1, ramps down, then sits at 0 for
 * a second stretch so the two offset copies of this buffer sum to ~1 at every instant.
 */
function createFadeBuffer(ctx, activeTime, fadeTime) {
    const length1 = Math.floor(activeTime * ctx.sampleRate);
    const length2 = Math.floor((activeTime - 2 * fadeTime) * ctx.sampleRate);
    const buffer = ctx.createBuffer(1, length1 + length2, ctx.sampleRate);
    const p = buffer.getChannelData(0);

    const fadeLength = fadeTime * ctx.sampleRate;
    const fadeIndex2 = length1 - fadeLength;

    for (let i = 0; i < length1; i++) {
        if (i < fadeLength) {
            p[i] = Math.sqrt(i / fadeLength);
        } else if (i >= fadeIndex2) {
            p[i] = Math.sqrt(1 - (i - fadeIndex2) / fadeLength);
        } else {
            p[i] = 1;
        }
    }

    // Remainder stays 0 — this line is silent while its partner carries the signal.

    return buffer;
}

/**
 * The delayTime ramp. Descending shifts up (delay shrinking = playing back faster),
 * ascending shifts down.
 */
function createDelayTimeBuffer(ctx, activeTime, fadeTime, shiftUp) {
    const length1 = Math.floor(activeTime * ctx.sampleRate);
    const length2 = Math.floor((activeTime - 2 * fadeTime) * ctx.sampleRate);
    const length = length1 + length2;
    const buffer = ctx.createBuffer(1, length, ctx.sampleRate);
    const p = buffer.getChannelData(0);

    for (let i = 0; i < length1; i++) {
        p[i] = shiftUp ? (length1 - i) / length : i / length1;
    }

    return buffer;
}

function loopingSource(ctx, buffer) {
    const source = ctx.createBufferSource();
    source.buffer = buffer;
    source.loop = true;

    return source;
}

/**
 * @returns {{input: GainNode, output: GainNode, setPitchOffset: (octaves: number) => void, stop: () => void}}
 */
export function createPitchShifter(ctx) {
    const input = ctx.createGain();
    const output = ctx.createGain();

    const shiftDownBuffer = createDelayTimeBuffer(ctx, BUFFER_TIME, FADE_TIME, false);
    const shiftUpBuffer = createDelayTimeBuffer(ctx, BUFFER_TIME, FADE_TIME, true);

    // mod1/mod2 drive the two delay lines when shifting down, mod3/mod4 when shifting up.
    // Both pairs are always running; setPitchOffset just mutes one pair.
    const mod1 = loopingSource(ctx, shiftDownBuffer);
    const mod2 = loopingSource(ctx, shiftDownBuffer);
    const mod3 = loopingSource(ctx, shiftUpBuffer);
    const mod4 = loopingSource(ctx, shiftUpBuffer);

    const mod1Gain = ctx.createGain();
    const mod2Gain = ctx.createGain();
    const mod3Gain = ctx.createGain();
    const mod4Gain = ctx.createGain();
    mod3Gain.gain.value = 0;
    mod4Gain.gain.value = 0;

    mod1.connect(mod1Gain);
    mod2.connect(mod2Gain);
    mod3.connect(mod3Gain);
    mod4.connect(mod4Gain);

    // How far the ramp actually swings the delay — this is the pitch amount.
    const modGain1 = ctx.createGain();
    const modGain2 = ctx.createGain();
    mod1Gain.connect(modGain1);
    mod3Gain.connect(modGain1);
    mod2Gain.connect(modGain2);
    mod4Gain.connect(modGain2);

    const delay1 = ctx.createDelay();
    const delay2 = ctx.createDelay();
    modGain1.connect(delay1.delayTime);
    modGain2.connect(delay2.delayTime);

    const fadeBuffer = createFadeBuffer(ctx, BUFFER_TIME, FADE_TIME);
    const fade1 = loopingSource(ctx, fadeBuffer);
    const fade2 = loopingSource(ctx, fadeBuffer);

    const mix1 = ctx.createGain();
    const mix2 = ctx.createGain();
    mix1.gain.value = 0;
    mix2.gain.value = 0;
    fade1.connect(mix1.gain);
    fade2.connect(mix2.gain);

    input.connect(delay1);
    input.connect(delay2);
    delay1.connect(mix1);
    delay2.connect(mix2);
    mix1.connect(output);
    mix2.connect(output);

    const sources = [mod1, mod2, mod3, mod4, fade1, fade2];

    // Line 2 starts half a cycle behind line 1 — that offset is the whole trick.
    const start = ctx.currentTime + 0.05;
    const startOffset = start + BUFFER_TIME - FADE_TIME;
    mod1.start(start);
    mod3.start(start);
    fade1.start(start);
    mod2.start(startOffset);
    mod4.start(startOffset);
    fade2.start(startOffset);

    function setDelay(seconds) {
        const now = ctx.currentTime;
        modGain1.gain.setTargetAtTime(0.5 * seconds, now, 0.01);
        modGain2.gain.setTargetAtTime(0.5 * seconds, now, 0.01);
    }

    /** @param octaves +1 = an octave up, -1 = an octave down, 0 = unshifted. */
    function setPitchOffset(octaves) {
        const up = octaves > 0 ? 1 : 0;
        mod1Gain.gain.value = 1 - up;
        mod2Gain.gain.value = 1 - up;
        mod3Gain.gain.value = up;
        mod4Gain.gain.value = up;
        setDelay(DELAY_TIME * Math.abs(octaves));
    }

    function stop() {
        sources.forEach((source) => {
            try {
                source.stop();
            } catch {
                // Already stopped — nothing to do.
            }
            source.disconnect();
        });
    }

    setPitchOffset(0);

    return { input, output, setPitchOffset, stop };
}
