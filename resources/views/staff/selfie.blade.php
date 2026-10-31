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

            <form method="POST" action="{{ route('staff.selfie.store') }}" x-data="loginSelfie()" class="rounded-xl border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-4 shadow-xl" @submit="submitting = true">
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

                <p class="mt-4 rounded-md border border-[var(--app-border)] bg-[var(--app-bg)] p-3 text-sm text-[var(--app-text)]" x-text="message" role="status"></p>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <button type="button" @click="requestLocation()" class="rounded-md border border-[var(--app-border)] px-4 py-3 text-sm font-semibold text-[var(--app-text)]">Verify Salon Location</button>
                    <button type="button" @click="startCamera()" :disabled="!locationReady" class="rounded-md border border-[var(--app-border)] px-4 py-3 text-sm font-semibold text-[var(--app-text)] disabled:opacity-50">Enable Camera</button>
                    <button type="button" @click="capture()" :disabled="!ready || !locationReady" class="rounded-md bg-[var(--app-primary)] px-4 py-3 text-sm font-semibold text-[var(--on-accent)] disabled:cursor-not-allowed disabled:opacity-50">Capture Selfie</button>
                </div>

                <label class="mt-4 flex items-start gap-3 rounded-lg border border-[var(--app-border)] p-3 text-sm text-[var(--app-text)]"><input type="checkbox" name="consent" value="1" x-model="consentChecked" required class="mt-1 rounded border-[var(--app-border)] text-[var(--app-primary)]"><span>I consent to storing this attendance selfie and GPS check-in details for attendance review by salon administrators. I understand that this camera capture does not perform face matching or liveness detection.</span></label>

                <div x-show="photoData" x-cloak class="mt-3 grid gap-3 sm:grid-cols-2">
                    <button type="button" @click="retake()" class="rounded-md border border-[var(--app-border)] px-4 py-3 text-sm font-semibold text-[var(--app-text)]">Retake</button>
                    <button type="submit" :disabled="submitting || !locationReady || !consentChecked" class="rounded-md bg-[var(--app-primary)] px-4 py-3 text-sm font-semibold text-[var(--on-accent)] disabled:opacity-50">Submit check-in</button>
                </div>
                <p class="mt-3 text-xs leading-5 text-[var(--app-muted)]">A new browser installation is recorded as a device request. Browser IDs do not prove hardware identity. Check-ins remain yellow for administrator review until a suitable face and liveness service is configured.</p>
            </form>
        </div>
    </div>

    <script>
        function loginSelfie() {
            return {
                stream: null,
                ready: false,
                photoData: '',
                submitting: false,
                latitude: '',
                longitude: '',
                accuracy: '',
                locationReady: false,
                deviceKey: '',
                consentChecked: false,
                message: 'Verify your salon location and capture an attendance selfie to continue.',
                init() {
                    try {
                        this.deviceKey = localStorage.getItem('salonos-attendance-device') || '';
                        if (!this.deviceKey) {
                            const bytes = new Uint8Array(16);
                            crypto.getRandomValues(bytes);
                            bytes[6] = (bytes[6] & 0x0f) | 0x40;
                            bytes[8] = (bytes[8] & 0x3f) | 0x80;
                            const hex = [...bytes].map(value => value.toString(16).padStart(2, '0')).join('');
                            this.deviceKey = `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
                            localStorage.setItem('salonos-attendance-device', this.deviceKey);
                        }
                    } catch (error) {
                        this.message = 'This browser cannot save a device registration. Enable site storage or contact an administrator.';
                    }
                },
                requestLocation() {
                    this.locationReady = false;
                    this.message = 'Checking current GPS location…';
                    if (!navigator.geolocation) {
                        this.message = 'GPS location is unavailable in this browser. Check-in cannot continue.';
                        return;
                    }
                    navigator.geolocation.getCurrentPosition((position) => {
                        this.latitude = position.coords.latitude;
                        this.longitude = position.coords.longitude;
                        this.accuracy = position.coords.accuracy;
                        this.locationReady = true;
                        this.message = `Location captured with ${Math.round(this.accuracy)} m accuracy. The server will verify the salon boundary.`;
                    }, (error) => {
                        this.latitude = this.longitude = this.accuracy = '';
                        this.message = error && error.code === 1
                            ? 'Location permission was denied. Allow GPS access and retry; attendance will not be marked without it.'
                            : 'A reliable GPS fix was not available. Move to an open area and try again.';
                    }, {enableHighAccuracy: true, maximumAge: 0, timeout: 20000});
                },
                async startCamera() {
                    if (!this.locationReady) {
                        this.message = 'Verify your GPS location before opening the camera.';
                        return;
                    }
                    this.photoData = '';
                    this.ready = false;
                    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                        this.message = 'This browser does not support camera capture. Please use Chrome or Safari on HTTPS.';
                        return;
                    }
                    try {
                        this.stream = await navigator.mediaDevices.getUserMedia({
                            video: {facingMode: 'user', width: {ideal: 720}, height: {ideal: 960}},
                            audio: false,
                        });
                        this.$refs.video.srcObject = this.stream;
                        this.ready = true;
                        this.message = 'Camera ready. Capture a selfie. A camera capture is not face or liveness verification.';
                    } catch (error) {
                        this.message = error && error.name === 'NotAllowedError'
                            ? 'Camera permission was denied. Allow camera access and tap Enable Camera again.'
                            : 'Camera is unavailable. Check the device camera and try again.';
                    }
                },
                capture() {
                    if (!this.ready) return;
                    const video = this.$refs.video;
                    const canvas = this.$refs.canvas;
                    canvas.width = video.videoWidth || 720;
                    canvas.height = video.videoHeight || 960;
                    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
                    this.photoData = canvas.toDataURL('image/jpeg', 0.82);
                    this.message = 'Selfie captured. Submit check-in for server-side GPS verification and administrator review.';
                    this.stopCamera();
                },
                retake() {
                    this.startCamera();
                },
                stopCamera() {
                    if (this.stream) {
                        this.stream.getTracks().forEach(track => track.stop());
                        this.stream = null;
                    }
                    this.ready = false;
                },
            };
        }
    </script>
</x-app-layout>
