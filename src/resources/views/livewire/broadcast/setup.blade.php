@php
    $card = 'p-4 sm:p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800';
@endphp

<div x-data="broadcastSetup" class="space-y-6 max-w-3xl">
    <div class="flex items-center justify-between gap-4">
        <x-back-link :href="route('dashboard')">{{ __('All screens') }}</x-back-link>
    </div>

    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('Play sounds into Discord') }}</h2>
        <p class="mt-1 text-gray-600 dark:text-gray-400">
            {{ __('Set this up once and your soundboard and voice changer become your microphone in Discord calls.') }}
        </p>
    </div>

    {{-- Browser can't do this at all --}}
    <div x-show="!broadcast.supported" x-cloak class="p-4 sm:p-5 rounded-xl border border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-900/30">
        <h3 class="font-semibold text-amber-900 dark:text-amber-200">{{ __('This browser can\'t do it') }}</h3>
        <p class="mt-1 text-sm text-amber-800 dark:text-amber-300">
            {{ __('Sending audio to a chosen speaker needs Chrome or Edge on a computer. Your soundboards still work normally here — only the Discord part is unavailable.') }}
        </p>
        <p x-show="!broadcast.secure" class="mt-2 text-sm text-amber-800 dark:text-amber-300">
            {{ __('You are on') }} <code class="font-mono" x-text="origin"></code>.
            {{ __('Microphone access only works on http://localhost or an https:// address — open the app on the same PC as Discord using localhost.') }}
        </p>
    </div>

    <div x-show="broadcast.supported" x-cloak class="space-y-6">

        {{-- How it works --}}
        <div class="{{ $card }}">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('How this works') }}</h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Discord can only listen to a microphone. A browser tab is not a microphone — so we use a free tool called VB-CABLE that adds a fake speaker and a fake microphone to Windows, wired together like a piece of cable.') }}
            </p>
            <div class="mt-3 overflow-x-auto">
                <div class="flex items-center gap-2 text-xs sm:text-sm font-medium whitespace-nowrap">
                    <span class="px-3 py-2 rounded-lg bg-indigo-100 dark:bg-indigo-900/50 text-indigo-800 dark:text-indigo-200">{{ __('This app') }}</span>
                    <span class="text-gray-400">&rarr;</span>
                    <span class="px-3 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">{{ __('CABLE Input') }}<br><span class="text-[10px] font-normal opacity-70">{{ __('fake speaker') }}</span></span>
                    <span class="text-gray-400">&rarr;</span>
                    <span class="px-3 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">{{ __('CABLE Output') }}<br><span class="text-[10px] font-normal opacity-70">{{ __('fake microphone') }}</span></span>
                    <span class="text-gray-400">&rarr;</span>
                    <span class="px-3 py-2 rounded-lg bg-indigo-100 dark:bg-indigo-900/50 text-indigo-800 dark:text-indigo-200">{{ __('Discord') }}</span>
                </div>
            </div>
            <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                {{ __('The two names look almost the same and it is easy to mix them up. Remember: this app talks to CABLE Input, and Discord listens to CABLE Output.') }}
            </p>
        </div>

        <x-setup-step :number="1" :title="__('Install VB-CABLE')">
            <div x-show="cableFound" x-cloak class="mt-2 inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-200 text-sm font-medium">
                &#10003; {{ __('Found it — this step is already done.') }}
            </div>

            <div x-show="!cableFound">
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Free, no account needed. Ask a grown-up if you are not sure about installing things.') }}
                </p>
                <ol class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-300 list-decimal list-inside">
                    <li>{{ __('Go to') }} <a href="https://vb-audio.com/Cable/" target="_blank" rel="noopener noreferrer" class="text-indigo-600 dark:text-indigo-400 underline">vb-audio.com/Cable</a> {{ __('and download the Windows version.') }}</li>
                    <li>{{ __('Unzip the file you downloaded.') }}</li>
                    <li>{{ __('Right-click VBCABLE_Setup_x64.exe and choose "Run as administrator".') }}</li>
                    <li>{{ __('Click "Install Driver", then restart the PC.') }}</li>
                    <li>{{ __('Come back to this page — the green tick above will appear on its own.') }}</li>
                </ol>
            </div>
        </x-setup-step>

        <x-setup-step :number="2" :title="__('Pick your microphone and the cable')">
            <div x-show="!labelled" x-cloak class="mt-3">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('First let the browser see your audio devices. It will ask permission once.') }}
                </p>
                <x-primary-button type="button" x-on:click="grant()" x-bind:disabled="asking" class="mt-3">
                    <span x-show="!asking">{{ __('Turn on my microphone') }}</span>
                    <span x-show="asking" x-cloak>{{ __('Waiting for permission…') }}</span>
                </x-primary-button>
            </div>

            <div x-show="labelled" x-cloak class="mt-3 space-y-4">
                <div>
                    <x-input-label for="mic" :value="__('Your real microphone (the one you talk into)')" />
                    <x-select id="mic" x-on:change="chooseMic($event)" class="mt-1 block w-full">
                        <option value="">{{ __('Default microphone') }}</option>
                        <template x-for="device in inputs" :key="device.deviceId">
                            <option :value="device.deviceId" :data-label="device.label"
                                :selected="device.deviceId === broadcast.micId" x-text="device.label"></option>
                        </template>
                    </x-select>
                </div>

                <div>
                    <x-input-label for="sink" :value="__('Send sounds to (choose CABLE Input)')" />
                    <x-select id="sink" x-on:change="chooseSink($event)" class="mt-1 block w-full">
                        <option value="">{{ __('Not chosen yet') }}</option>
                        <template x-for="device in outputs" :key="device.deviceId">
                            <option :value="device.deviceId" :data-label="device.label"
                                :selected="device.deviceId === broadcast.sinkId" x-text="device.label"></option>
                        </template>
                    </x-select>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Pick CABLE Input — not CABLE Output. If you have no cable installed yet you can pick your headphones to test the voices.') }}
                    </p>
                </div>
            </div>
        </x-setup-step>

        <x-setup-step :number="3" :title="__('Go live and try a voice')">
            <div class="mt-3 p-3 rounded-lg bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700">
                <label class="flex items-start gap-2 text-sm text-amber-900 dark:text-amber-200">
                    <input type="checkbox" x-bind:checked="broadcast.acknowledgedHeadphones" x-on:change="toggleHeadphones($event)"
                        class="mt-0.5 rounded border-amber-400 text-indigo-600 focus:ring-indigo-500">
                    <span>{{ __('I am wearing headphones. (Without them your speakers feed back into your microphone and everything screeches.)') }}</span>
                </label>
                <label class="flex items-start gap-2 mt-2 text-sm text-amber-900 dark:text-amber-200">
                    <input type="checkbox" x-bind:checked="broadcast.usingSpeakers" x-on:change="toggleSpeakers($event)"
                        class="mt-0.5 rounded border-amber-400 text-indigo-600 focus:ring-indigo-500">
                    <span>{{ __('I have to use speakers — turn on echo cancelling. (The voices will not sound as good, but it will not screech.)') }}</span>
                </label>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-3">
                <x-primary-button type="button" x-on:click="toggleLive()" x-bind:disabled="busy || broadcast.starting">
                    <span x-show="!broadcast.active && !broadcast.starting">{{ __('Go live') }}</span>
                    <span x-show="broadcast.starting" x-cloak>{{ __('Starting…') }}</span>
                    <span x-show="broadcast.active" x-cloak>{{ __('Stop') }}</span>
                </x-primary-button>

                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" x-bind:checked="broadcast.micMonitor" x-on:change="toggleMonitor($event)"
                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    {{ __('Let me hear my own voice') }}
                </label>
            </div>

            {{-- enable() refuses on two things that live on this very page (no output
                 device, neither headphones nor speakers ticked), so say so here rather
                 than only in the bar at the top. --}}
            <p x-show="broadcast.error" x-cloak class="mt-3 text-sm font-medium text-red-700 dark:text-red-400" x-text="broadcast.error"></p>

            <div x-show="broadcast.active" x-cloak class="mt-4">
                <x-input-label :value="__('Microphone level — talk and watch this move')" />
                <div class="mt-1 h-3 w-full rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                    <div class="h-full bg-green-500 transition-[width] duration-100" x-bind:style="`width: ${Math.round(level * 100)}%`"></div>
                </div>
            </div>

            <x-voice-presets class="mt-4" :heading="__('Voices')" :always="true" />
        </x-setup-step>

        <x-setup-step :number="4" :title="__('Set up Discord')">
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('In Discord, open the gear icon next to your name, then Voice & Video.') }}
            </p>
            <ul class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-300 list-disc list-inside">
                <li>{{ __('Input Device: choose CABLE Output. (Output Device stays as your headphones.)') }}</li>
                <li>{{ __('Turn OFF Noise Suppression / Krisp — it thinks a robot voice is noise and mutes you.') }}</li>
                <li>{{ __('Turn OFF Echo Cancellation and Automatic Gain Control.') }}</li>
                <li>{{ __('Turn OFF Advanced Voice Activity, and drag Input Sensitivity all the way down — or switch to Push to Talk.') }}</li>
                <li>{{ __('Press "Let\'s Check" under Mic Test and talk. You should hear yourself back.') }}</li>
            </ul>
        </x-setup-step>

        {{-- The catch --}}
        <div class="p-4 sm:p-5 rounded-xl border border-red-300 bg-red-50 dark:border-red-800 dark:bg-red-900/30">
            <h3 class="font-semibold text-red-900 dark:text-red-200">{{ __('Important: this tab is your microphone') }}</h3>
            <p class="mt-1 text-sm text-red-800 dark:text-red-300">
                {{ __('While Discord is listening to CABLE Output, your voice reaches your friends through this tab. If you close it or stop being live, they hear nothing at all — even when you talk normally. Watch the ON AIR bar at the top of the page.') }}
            </p>
            <p class="mt-2 text-sm text-red-800 dark:text-red-300">
                {{ __('When you are finished playing, set Discord\'s Input Device back to your real microphone.') }}
            </p>
        </div>

        {{-- Troubleshooting --}}
        <div class="{{ $card }}">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('If something is not working') }}</h3>
            <dl class="mt-3 space-y-3 text-sm">
                <div>
                    <dt class="font-medium text-gray-900 dark:text-gray-100">{{ __('My friends hear nothing') }}</dt>
                    <dd class="text-gray-600 dark:text-gray-400">{{ __('Check the ON AIR bar says you are live, and that Discord\'s Input Device is CABLE Output (not CABLE Input).') }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-900 dark:text-gray-100">{{ __('Discord\'s mic test bar moves, but nobody hears me') }}</dt>
                    <dd class="text-gray-600 dark:text-gray-400">{{ __('That is Noise Suppression eating the voice effect. Turn Krisp and Advanced Voice Activity off in step 4.') }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-900 dark:text-gray-100">{{ __('Everything screeches') }}</dt>
                    <dd class="text-gray-600 dark:text-gray-400">{{ __('Your microphone is hearing your speakers. Put headphones on, or tick the speakers box in step 3.') }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-900 dark:text-gray-100">{{ __('I hear myself twice, or my voice is delayed') }}</dt>
                    <dd class="text-gray-600 dark:text-gray-400">{{ __('Untick "Let me hear my own voice". The Normal voice has the least delay; the pitch-changing ones are always a bit slower.') }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-900 dark:text-gray-100">{{ __('CABLE Input is not in the list') }}</dt>
                    <dd class="text-gray-600 dark:text-gray-400">{{ __('Restart the PC after installing VB-CABLE, then reload this page.') }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
