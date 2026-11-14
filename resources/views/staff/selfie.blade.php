<x-app-layout>
    <x-slot name="header">
        <h1 class="font-serif text-2xl text-[var(--app-primary)]">Attendance Check-in</h1>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-5 rounded-lg border border-red-300/30 bg-red-500/10 p-4 text-sm text-red-100">
                    {{ $errors->first() }}
                </div>
            @endif

            <form x-ref="form" method="POST" action="{{ route('staff.selfie.store') }}" x-data="loginSelfie({gpsRequired: @js($requiresGps)})" class="rounded-xl border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-4 shadow-xl" @submit.prevent="submitCheckIn($event)">
                @csrf
                <input type="hidden" name="selfie_image" :value="photoData">
                <input type="hidden" name="latitude" :value="latitude">
                <input type="hidden" name="longitude" :value="longitude">
                <input type="hidden" name="gps_accuracy_meters" :value="accuracy">
                <input type="hidden" name="device_key" :value="deviceKey">

                <div class="overflow-hidden rounded-lg border border-[#c8a24a]/20 bg-black">
                    <video x-ref="video" x-show="!photoData" class="aspect-[3/4] w-full object-cover" autoplay playsinline muted></video>
                    <img x-show="photoData" :src="photoData" alt="Captured login selfie" class="aspect-[3/4] w-full object-cover">
                    <canvas x-ref="canvas" class="hidden"></canvas>
                </div>

                <p class="mt-4 rounded-md border border-[var(--app-border)] bg-[var(--app-bg)] p-3 text-sm text-[var(--app-text)]" x-text="message" role="status" aria-live="polite"></p>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <button type="button" @click="refreshPermissions()" class="rounded-md border border-[var(--app-border)] px-4 py-3 text-sm font-semibold text-[var(--app-text)]">Refresh permission status</button>
                    @if ($requiresGps)
                        <button type="button" @click="requestLocation()" class="rounded-md border border-[var(--app-border)] px-4 py-3 text-sm font-semibold text-[var(--app-text)]">Verify Salon Location</button>
                    @endif
                    <button type="button" @click="startCamera()" :disabled="gpsRequired && !locationReady" class="rounded-md border border-[var(--app-border)] px-4 py-3 text-sm font-semibold text-[var(--app-text)] disabled:opacity-50">Enable Camera</button>
                    <button type="button" @click="capture()" :disabled="!ready || (gpsRequired && !locationReady)" class="rounded-md bg-[var(--app-primary)] px-4 py-3 text-sm font-semibold text-[var(--on-accent)] disabled:cursor-not-allowed disabled:opacity-50">Capture Selfie</button>
                </div>

                <label class="mt-4 flex items-start gap-3 rounded-lg border border-[var(--app-border)] p-3 text-sm text-[var(--app-text)]"><input type="checkbox" name="consent" value="1" x-model="consentChecked" required class="mt-1 rounded border-[var(--app-border)] text-[var(--app-primary)]"><span>I consent to storing this attendance selfie{{ $requiresGps ? ' and GPS check-in details' : '' }} for attendance review by salon administrators. I understand that this camera capture does not perform face matching or liveness detection.</span></label>

                <div x-show="photoData" x-cloak class="mt-3 grid gap-3 sm:grid-cols-2">
                    <button type="button" @click="retake()" class="rounded-md border border-[var(--app-border)] px-4 py-3 text-sm font-semibold text-[var(--app-text)]">Retake</button>
                    <button type="submit" :disabled="submitting || (gpsRequired && !locationReady) || !consentChecked" class="rounded-md bg-[var(--app-primary)] px-4 py-3 text-sm font-semibold text-[var(--on-accent)] disabled:opacity-50">Submit check-in</button>
                </div>
                <p class="mt-3 text-xs leading-5 text-[var(--app-muted)]">A new browser installation is recorded as a device request. Browser IDs do not prove hardware identity. Check-ins remain yellow for administrator review until a suitable face and liveness service is configured.</p>
            </form>
        </div>
    </div>

</x-app-layout>
