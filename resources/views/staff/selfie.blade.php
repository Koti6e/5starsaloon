<x-app-layout>
    <x-slot name="header">
        <h1 class="font-serif text-2xl text-[#f4d27a]">Login Selfie</h1>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-5 rounded-lg border border-red-300/30 bg-red-500/10 p-4 text-sm text-red-100">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('staff.selfie.store') }}" x-data="loginSelfie()" class="rounded-lg border border-[#c8a24a]/20 bg-[#11100d] p-4 shadow-xl shadow-black/25" @submit="submitting = true">
                @csrf
                <input type="hidden" name="selfie_image" :value="photoData">

                <div class="overflow-hidden rounded-lg border border-[#c8a24a]/20 bg-black">
                    <video x-ref="video" x-show="!photoData" class="aspect-[3/4] w-full object-cover" autoplay playsinline muted></video>
                    <img x-show="photoData" :src="photoData" alt="Captured login selfie" class="aspect-[3/4] w-full object-cover">
                    <canvas x-ref="canvas" class="hidden"></canvas>
                </div>

                <p class="mt-4 rounded-md border border-[#c8a24a]/20 bg-black/40 p-3 text-sm text-[#d8c8a3]" x-text="message"></p>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <button type="button" @click="startCamera()" class="rounded-md border border-[#c8a24a]/40 px-4 py-3 text-sm font-semibold text-[#f8efd8]">Enable Camera</button>
                    <button type="button" @click="capture()" :disabled="!ready" class="rounded-md bg-[#d5a93b] px-4 py-3 text-sm font-semibold text-[#111] disabled:cursor-not-allowed disabled:opacity-50">Capture Selfie</button>
                </div>

                <div x-show="photoData" x-cloak class="mt-3 grid gap-3 sm:grid-cols-2">
                    <button type="button" @click="retake()" class="rounded-md border border-[#c8a24a]/40 px-4 py-3 text-sm font-semibold text-[#f8efd8]">Retake</button>
                    <button type="submit" :disabled="submitting" class="rounded-md bg-[#d5a93b] px-4 py-3 text-sm font-semibold text-[#111] disabled:opacity-50">Continue</button>
                </div>
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
                message: 'Camera access is required for Staff1 and Staff2 login attendance.',
                init() {
                    this.startCamera();
                },
                async startCamera() {
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
                        this.message = 'Camera ready. Capture a fresh selfie to continue.';
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
                    this.message = 'Selfie captured. Continue to enter the staff dashboard.';
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
