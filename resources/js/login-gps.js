const MAX_GPS_ATTEMPTS = 3;
const MAX_GPS_AGE_MS = 120_000;
const MAX_FUTURE_CLOCK_SKEW_MS = 30_000;

export function createLoginGps(options = {}) {
    const navigatorObject = options.navigatorObject ?? globalThis.navigator;
    const windowObject = options.windowObject ?? globalThis.window;
    const elements = options.elements;
    const configuration = options.configuration ?? {};
    const now = options.now ?? (() => Date.now());

    return {
        permission: 'unknown',
        attempts: 0,
        locating: false,
        submitAfterLocation: false,
        gpsSubmitAllowed: false,
        lastSubmitter: null,
        status: '',
        start() {
            elements.form.addEventListener('submit', (event) => this.submit(event));
            elements.retry.addEventListener('click', () => this.requestLocation(false));
            elements.username.addEventListener('input', () => this.updateVisibility());
            windowObject.addEventListener('pageshow', () => this.refreshPermission());
            this.updateVisibility();
            this.refreshPermission();
        },
        isProtectedStaff() {
            return ['staff1', 'staff2'].includes(elements.username.value.trim().toLowerCase());
        },
        updateVisibility() {
            const visible = this.isProtectedStaff();
            elements.panel.style.display = visible ? 'block' : 'none';
            if (visible && this.permission === 'denied') {
                this.showPermissionInstructions();
            } else if (visible && !this.status) {
                this.setStatus('Location verifies that Staff1 and Staff2 are within the salon boundary. Camera access is requested only if selfie verification is required after sign-in.');
            }
        },
        setStatus(message) {
            this.status = message;
            elements.status.textContent = message;
        },
        async refreshPermission() {
            if (!navigatorObject.permissions?.query) {
                this.permission = 'unknown';
                return this.permission;
            }
            try {
                const permission = await navigatorObject.permissions.query({ name: 'geolocation' });
                this.permission = permission.state;
                permission.onchange = () => {
                    this.permission = permission.state;
                    if (this.permission === 'denied' && this.isProtectedStaff()) {
                        this.showPermissionInstructions();
                    }
                };
            } catch (error) {
                this.permission = 'unknown';
            }
            if (this.permission === 'denied' && this.isProtectedStaff()) {
                this.showPermissionInstructions();
            }
            return this.permission;
        },
        showPermissionInstructions() {
            this.setStatus('Location permission is blocked. Open this site’s browser settings, allow location access, then tap Retry GPS. On iPhone, check Settings > Safari > Location.');
            this.showRetry(true);
        },
        showRetry(show) {
            elements.retry.style.display = show ? 'inline-flex' : 'none';
            elements.retry.disabled = this.locating || this.attempts >= MAX_GPS_ATTEMPTS;
        },
        async submit(event) {
            if (!this.isProtectedStaff()) return;
            if (this.gpsSubmitAllowed) {
                this.gpsSubmitAllowed = false;
                return;
            }
            event.preventDefault();
            if (this.locating || this.submitAfterLocation) return;
            this.lastSubmitter = event.submitter?.type === 'submit' ? event.submitter : null;
            this.submitAfterLocation = true;
            await this.requestLocation(true);
        },
        async requestLocation(submitAfterLocation = false) {
            if (!this.isProtectedStaff() || this.locating) return;
            this.submitAfterLocation = submitAfterLocation || this.submitAfterLocation;
            this.clearCoordinates();

            if (this.attempts >= MAX_GPS_ATTEMPTS) {
                this.showRetry(false);
                this.setStatus('GPS could not get a reliable fix after three attempts. Turn on device location, move near a window or outdoors, and try signing in again.');
                this.submitAfterLocation = false;
                return;
            }

            if (!navigatorObject.geolocation) {
                this.setStatus('GPS is unavailable in this browser. Enable device location or use a browser that supports location, then retry.');
                this.showRetry(this.attempts < MAX_GPS_ATTEMPTS);
                this.submitAfterLocation = false;
                return;
            }

            this.setStatus('Checking location permission…');
            this.permission = await this.refreshPermission();
            if (this.permission === 'denied') {
                this.showPermissionInstructions();
                this.submitAfterLocation = false;
                return;
            }

            this.attempts += 1;
            this.locating = true;
            this.showRetry(true);
            this.setStatus(this.attempts === 1 ? 'Getting your current GPS location…' : 'Improving GPS accuracy. Keep location enabled while the device gets a fresh fix…');

            navigatorObject.geolocation.getCurrentPosition((position) => {
                this.locating = false;
                const coords = position?.coords ?? {};
                const latitude = Number(coords.latitude);
                const longitude = Number(coords.longitude);
                const accuracy = Number(coords.accuracy);
                const timestamp = Number(position.timestamp);
                const age = now() - timestamp;
                this.permission = 'granted';

                if (!Number.isFinite(latitude) || latitude < -90 || latitude > 90
                    || !Number.isFinite(longitude) || longitude < -180 || longitude > 180
                    || !Number.isFinite(accuracy) || accuracy < 0) {
                    this.handleRetryMessage('GPS returned invalid coordinates. Keep location enabled and retry GPS.');
                    return;
                }
                if (!Number.isFinite(timestamp) || age > MAX_GPS_AGE_MS || age < -MAX_FUTURE_CLOCK_SKEW_MS) {
                    this.handleRetryMessage('The GPS fix was stale or had an invalid timestamp. Tap Retry GPS to get a fresh location.');
                    return;
                }

                const accuracyLimit = Number(configuration.accuracy_limit_meters);
                const distance = this.distanceFromSalon(latitude, longitude);
                if (Number.isFinite(accuracyLimit) && accuracy > accuracyLimit) {
                    this.handleRetryMessage(`GPS accuracy: ${Math.round(accuracy)} metres. Your GPS signal is not accurate enough. Please keep location enabled and try again.`);
                    return;
                }

                const radius = Number(configuration.radius_meters);
                if (Number.isFinite(radius) && distance > radius) {
                    this.handleRetryMessage(`GPS accuracy: ${Math.round(accuracy)} metres. Location is approximately ${Math.round(distance)} metres from the salon; sign-in requires a location within ${Math.round(radius)} metres.`);
                    return;
                }

                elements.latitude.value = String(latitude);
                elements.longitude.value = String(longitude);
                elements.accuracy.value = String(accuracy);
                elements.timestamp.value = String(Math.trunc(timestamp));
                this.showRetry(false);
                this.setStatus(`GPS accuracy: ${Math.round(accuracy)} metres. Location verified within the salon boundary. Signing in…`);
                this.submitAfterLocation = false;
                this.gpsSubmitAllowed = true;
                elements.form.requestSubmit(this.lastSubmitter ?? undefined);
            }, (error) => {
                this.locating = false;
                this.clearCoordinates();
                if (error?.code === 1) {
                    this.permission = 'denied';
                    this.showPermissionInstructions();
                } else if (error?.code === 3) {
                    this.handleRetryMessage('GPS timed out before getting a reliable fix. Keep location enabled and tap Retry GPS.');
                } else {
                    this.handleRetryMessage('GPS is unavailable. Turn on device location, move near a window or outdoors, and tap Retry GPS.');
                }
                this.submitAfterLocation = false;
            }, { enableHighAccuracy: true, maximumAge: 0, timeout: 20_000 });
        },
        handleRetryMessage(message) {
            this.submitAfterLocation = false;
            const exhausted = this.attempts >= MAX_GPS_ATTEMPTS;
            const finalMessage = exhausted && message.includes('from the salon')
                ? `${message} After three fresh checks, sign-in is still outside the salon boundary. Move within 200 metres and retry.`
                : exhausted
                    ? `${message} GPS could not get a reliable fix after three attempts. Keep location enabled and move near a window or outdoors before trying again.`
                    : message;
            this.setStatus(finalMessage);
            this.showRetry(this.attempts < MAX_GPS_ATTEMPTS);
        },
        clearCoordinates() {
            elements.latitude.value = '';
            elements.longitude.value = '';
            elements.accuracy.value = '';
            elements.timestamp.value = '';
        },
        distanceFromSalon(latitude, longitude) {
            const salonLatitude = Number(configuration.salon_latitude);
            const salonLongitude = Number(configuration.salon_longitude);
            if (!Number.isFinite(salonLatitude) || !Number.isFinite(salonLongitude)) return 0;
            const radians = (degrees) => degrees * Math.PI / 180;
            const deltaLatitude = radians(latitude - salonLatitude);
            const deltaLongitude = radians(longitude - salonLongitude);
            const haversine = Math.sin(deltaLatitude / 2) ** 2
                + Math.cos(radians(salonLatitude)) * Math.cos(radians(latitude)) * Math.sin(deltaLongitude / 2) ** 2;
            return 6_371_000 * 2 * Math.asin(Math.min(1, Math.sqrt(haversine)));
        },
    };
}

export function initializeLoginGps(options = {}) {
    const documentObject = options.documentObject ?? globalThis.document;
    const windowObject = options.windowObject ?? globalThis.window;
    const navigatorObject = options.navigatorObject ?? globalThis.navigator;
    if (!documentObject) return null;

    const initialize = () => {
        const form = documentObject.getElementById('login-form');
        if (!form) return null;
        const elements = {
            form,
            username: documentObject.getElementById('username'),
            panel: documentObject.getElementById('login-gps-panel'),
            status: documentObject.getElementById('login-gps-status'),
            retry: documentObject.getElementById('login-gps-retry'),
            latitude: documentObject.getElementById('login-latitude'),
            longitude: documentObject.getElementById('login-longitude'),
            accuracy: documentObject.getElementById('login-gps-accuracy'),
            timestamp: documentObject.getElementById('login-gps-timestamp'),
        };
        const component = createLoginGps({ ...options, elements, navigatorObject, windowObject, configuration: form.dataset });
        component.start();
        return component;
    };

    if (documentObject.readyState === 'loading') {
        documentObject.addEventListener('DOMContentLoaded', initialize, { once: true });
        return null;
    }
    return initialize();
}
