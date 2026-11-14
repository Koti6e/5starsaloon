export function createLoginSelfie(options = {}) {
    const navigatorObject = options.navigatorObject ?? globalThis.navigator;
    const windowObject = options.windowObject ?? globalThis.window;
    const uploadForm = options.uploadForm ?? ((form, callbacks) => {
        const request = new XMLHttpRequest();
        request.open('POST', form.action);
        request.upload.addEventListener('progress', (event) => {
            if (event.lengthComputable) callbacks.onProgress(Math.round((event.loaded / event.total) * 100));
        });
        request.upload.addEventListener('load', callbacks.onUploaded);
        request.addEventListener('load', () => {
            if (request.status >= 400) {
                callbacks.onError(request.status);
                return;
            }
            if (request.responseURL) windowObject.location.assign(request.responseURL);
            else callbacks.onError(request.status);
        });
        request.addEventListener('error', callbacks.onNetworkError);
        request.send(new FormData(form));
    });

    return {
        gpsRequired: Boolean(options.gpsRequired),
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
        cameraPermission: 'unknown',
        locationPermission: 'unknown',
        message: '',
        init() {
            try {
                this.deviceKey = windowObject.localStorage.getItem('salonos-attendance-device') || '';
                if (!this.deviceKey) {
                    const bytes = new Uint8Array(16);
                    windowObject.crypto.getRandomValues(bytes);
                    bytes[6] = (bytes[6] & 0x0f) | 0x40;
                    bytes[8] = (bytes[8] & 0x3f) | 0x80;
                    const hex = [...bytes].map((value) => value.toString(16).padStart(2, '0')).join('');
                    this.deviceKey = `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
                    windowObject.localStorage.setItem('salonos-attendance-device', this.deviceKey);
                }
            } catch (error) {
                this.message = 'This browser cannot save a device registration. Enable site storage or contact an administrator.';
            }

            this.refreshPermissions();
            windowObject.addEventListener('pageshow', () => this.refreshPermissions());
        },
        async readPermission(name) {
            if (!navigatorObject.permissions?.query) return 'unknown';
            try {
                const status = await navigatorObject.permissions.query({ name });
                status.onchange = () => {
                    if (name === 'camera') this.cameraPermission = status.state;
                    if (name === 'geolocation') this.locationPermission = status.state;
                    if (status.state === 'denied') this.message = this.permissionInstructions();
                };
                return status.state;
            } catch (error) {
                // Safari does not expose every permission descriptor. Native APIs remain
                // authoritative when a browser does not support the Permissions API query.
                return 'unknown';
            }
        },
        async refreshPermissions() {
            this.message = 'Checking camera and location permissions…';
            [this.cameraPermission, this.locationPermission] = await Promise.all([
                this.readPermission('camera'),
                this.readPermission('geolocation'),
            ]);
            this.message = this.hasDeniedPermission()
                ? this.permissionInstructions()
                : (this.gpsRequired
                    ? 'Permissions checked. Verify your current salon location, then capture a selfie.'
                    : 'Permissions checked. Enable the camera when you are ready to capture a selfie.');
        },
        hasDeniedPermission() {
            return this.cameraPermission === 'denied'
                || (this.gpsRequired && this.locationPermission === 'denied');
        },
        permissionInstructions() {
            const blocked = [
                ...(this.cameraPermission === 'denied' ? ['camera'] : []),
                ...(this.gpsRequired && this.locationPermission === 'denied' ? ['location'] : []),
            ].join(' and ');
            return `${blocked || 'Required'} permission is blocked. Open this site’s browser settings, allow ${blocked || 'the required device access'}, then refresh permission status. On iPhone, check Settings > Safari > Camera and Location.`;
        },
        requestLocation() {
            this.locationReady = false;
            this.message = 'Checking location permission…';
            if (this.locationPermission === 'denied') {
                this.message = this.permissionInstructions();
                return;
            }
            if (!navigatorObject.geolocation) {
                this.message = 'GPS location is unavailable in this browser. Check-in cannot continue.';
                return;
            }

            this.message = 'Getting a fresh GPS location…';
            navigatorObject.geolocation.getCurrentPosition((position) => {
                this.latitude = position.coords.latitude;
                this.longitude = position.coords.longitude;
                this.accuracy = position.coords.accuracy;
                this.locationReady = true;
                this.message = `Location captured with ${Math.round(this.accuracy)} m accuracy. The server will verify the salon boundary.`;
            }, (error) => {
                this.latitude = this.longitude = this.accuracy = '';
                this.locationPermission = error?.code === 1 ? 'denied' : this.locationPermission;
                this.message = error?.code === 1
                    ? this.permissionInstructions()
                    : 'A reliable GPS fix was not available. Move to an open area and try again.';
            }, { enableHighAccuracy: true, maximumAge: 0, timeout: 20000 });
        },
        async startCamera() {
            if (this.gpsRequired && !this.locationReady) {
                this.message = 'Verify your GPS location before opening the camera.';
                return;
            }
            if (this.cameraPermission === 'denied') {
                this.message = this.permissionInstructions();
                return;
            }
            this.photoData = '';
            this.ready = false;
            if (!navigatorObject.mediaDevices?.getUserMedia) {
                this.message = 'This browser does not support camera capture. Please use Chrome or Safari on HTTPS.';
                return;
            }

            try {
                this.message = 'Checking camera permission and opening the camera…';
                // Called directly from the user action so Safari retains its camera gesture.
                this.stream = await navigatorObject.mediaDevices.getUserMedia({
                    video: { facingMode: 'user', width: { ideal: 720 }, height: { ideal: 960 } },
                    audio: false,
                });
                this.cameraPermission = 'granted';
                this.$refs.video.srcObject = this.stream;
                this.ready = true;
                this.message = 'Camera ready. Capture a selfie. A camera capture is not face or liveness verification.';
            } catch (error) {
                this.cameraPermission = error?.name === 'NotAllowedError' ? 'denied' : this.cameraPermission;
                this.message = error?.name === 'NotAllowedError'
                    ? this.permissionInstructions()
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
            this.message = 'Selfie captured. Submit check-in to upload it for server verification.';
            this.stopCamera();
        },
        async submitCheckIn(event) {
            if (this.submitting) return;
            if (!this.photoData || !this.consentChecked) {
                this.message = 'Capture a selfie and provide consent before submitting attendance.';
                return;
            }
            this.submitting = true;

            if (this.gpsRequired) {
                this.message = 'Checking location permission…';
                if (this.locationPermission === 'denied') {
                    this.submitting = false;
                    this.message = this.permissionInstructions();
                    return;
                }
                if (!navigatorObject.geolocation) {
                    this.submitting = false;
                    this.message = 'GPS location is unavailable in this browser. Check-in cannot continue.';
                    return;
                }

                this.message = 'Getting a fresh GPS location for this attendance submission…';
                try {
                    const position = await new Promise((resolve, reject) => navigatorObject.geolocation.getCurrentPosition(resolve, reject, {
                        enableHighAccuracy: true, maximumAge: 0, timeout: 20000,
                    }));
                    this.latitude = position.coords.latitude;
                    this.longitude = position.coords.longitude;
                    this.accuracy = position.coords.accuracy;
                    this.locationReady = true;
                } catch (error) {
                    this.submitting = false;
                    this.locationReady = false;
                    this.latitude = this.longitude = this.accuracy = '';
                    this.locationPermission = error?.code === 1 ? 'denied' : this.locationPermission;
                    this.message = error?.code === 1
                        ? this.permissionInstructions()
                        : 'A fresh GPS fix was unavailable. Move to an open area and try again.';
                    return;
                }
            }

            this.message = 'Uploading selfie…';
            uploadForm(event.target, {
                onProgress: (percent) => { this.message = `Uploading selfie (${percent}%)…`; },
                onUploaded: () => { this.message = 'Upload complete. Verifying attendance and saving your check-in…'; },
                onError: (status) => {
                    this.submitting = false;
                    this.message = status === 413
                        ? 'The selfie is too large. Retake it and try again.'
                        : 'Attendance could not be confirmed by the server. No success was reported; check your connection and retry.';
                },
                onNetworkError: () => {
                    this.submitting = false;
                    this.message = 'The connection was interrupted before confirmation. Retry safely; duplicate check-ins are prevented.';
                },
            });
        },
        retake() {
            this.startCamera();
        },
        stopCamera() {
            if (this.stream) {
                this.stream.getTracks().forEach((track) => track.stop());
                this.stream = null;
            }
            this.ready = false;
        },
    };
}
