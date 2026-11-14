import test from 'node:test';
import assert from 'node:assert/strict';
import { createLoginSelfie } from '../../resources/js/attendance.js';

function harness(initialStates = { camera: 'prompt', geolocation: 'prompt' }) {
    const states = { ...initialStates };
    const permissionStatuses = {};
    const listeners = {};
    const storage = new Map();
    const calls = { camera: 0, location: [] };
    const uploads = [];
    const navigatorObject = {
        permissions: {
            async query({ name }) {
                const status = { state: states[name], onchange: null };
                permissionStatuses[name] = status;
                return status;
            },
        },
        mediaDevices: {
            async getUserMedia() {
                calls.camera += 1;
                return { getTracks: () => [{ stop() {} }] };
            },
        },
        geolocation: {
            getCurrentPosition(success, failure, options) {
                calls.location.push(options);
                if (states.geolocation === 'denied') {
                    failure({ code: 1 });
                    return;
                }
                success({ coords: { latitude: 12.8821489, longitude: 80.080876, accuracy: 12 } });
            },
        },
    };
    const windowObject = {
        localStorage: {
            getItem: (key) => storage.get(key) ?? null,
            setItem: (key, value) => storage.set(key, value),
        },
        crypto: { getRandomValues: (bytes) => bytes.fill(3) },
        addEventListener: (name, callback) => { listeners[name] = callback; },
    };
    const submitted = [];
    const component = createLoginSelfie({
        gpsRequired: true,
        navigatorObject,
        windowObject,
        uploadForm: (form, callbacks) => {
            uploads.push(callbacks);
            submitted.push(form);
            callbacks.onUploaded();
        },
    });
    component.$refs = { video: {}, canvas: {} };

    return { component, states, permissionStatuses, listeners, calls, uploads, submitted, windowObject };
}

test('first-time permissions are requested only by the relevant user action', async () => {
    const { component, calls } = harness();
    await component.refreshPermissions();

    assert.equal(component.cameraPermission, 'prompt');
    assert.equal(component.locationPermission, 'prompt');
    assert.equal(calls.camera, 0);
    assert.equal(calls.location.length, 0);

    component.requestLocation();
    assert.equal(calls.location[0].maximumAge, 0);
    assert.equal(component.locationReady, true);

    component.locationReady = true;
    await component.startCamera();
    assert.equal(calls.camera, 1);
    assert.equal(component.cameraPermission, 'granted');
});

test('previously granted permissions are read without prompting and camera opens only on demand', async () => {
    const { component, calls } = harness({ camera: 'granted', geolocation: 'granted' });
    await component.refreshPermissions();

    assert.equal(component.cameraPermission, 'granted');
    assert.equal(component.locationPermission, 'granted');
    assert.equal(calls.camera, 0);
    assert.equal(calls.location.length, 0);

    component.locationReady = true;
    await component.startCamera();
    assert.equal(calls.camera, 1);
    assert.equal(component.ready, true);
});

test('denied camera and location permissions show restoration guidance without invoking device APIs', async () => {
    const { component, calls } = harness({ camera: 'denied', geolocation: 'denied' });
    await component.refreshPermissions();
    await component.startCamera();
    component.requestLocation();

    assert.equal(calls.camera, 0);
    assert.equal(calls.location.length, 0);
    assert.match(component.message, /browser settings/);
    assert.match(component.message, /allow camera and location/);
});

test('permission revocation updates the current page and refresh after reload reads browser state again', async () => {
    const first = harness({ camera: 'granted', geolocation: 'granted' });
    await first.component.refreshPermissions();
    first.states.camera = 'denied';
    first.permissionStatuses.camera.state = 'denied';
    first.permissionStatuses.camera.onchange();
    assert.equal(first.component.cameraPermission, 'denied');
    assert.match(first.component.message, /browser settings/);

    const reloaded = harness(first.states);
    await reloaded.component.refreshPermissions();
    assert.equal(reloaded.component.cameraPermission, 'denied');
    assert.match(reloaded.component.message, /browser settings/);
});

test('attendance submission obtains fresh GPS coordinates and cannot double-submit', async () => {
    const { component, calls, submitted } = harness({ camera: 'granted', geolocation: 'granted' });
    component.photoData = 'data:image/jpeg;base64,test-image';
    component.consentChecked = true;
    const form = {};

    await component.submitCheckIn({ target: form });

    assert.equal(calls.location.length, 1);
    assert.equal(calls.location[0].maximumAge, 0);
    assert.equal(component.latitude, 12.8821489);
    assert.equal(component.longitude, 80.080876);
    assert.deepEqual(submitted, [form]);
    await component.submitCheckIn({ target: form });
    assert.equal(calls.location.length, 1);
    assert.deepEqual(submitted, [form]);
});

test('a network failure gives retry guidance and a retry performs a new fresh GPS lookup', async () => {
    const { component, calls, uploads, submitted } = harness({ camera: 'granted', geolocation: 'granted' });
    component.photoData = 'data:image/jpeg;base64,test-image';
    component.consentChecked = true;
    const form = {};

    await component.submitCheckIn({ target: form });
    assert.equal(uploads.length, 1);
    uploads[0].onNetworkError();
    assert.equal(component.submitting, false);
    assert.match(component.message, /Retry safely/);

    await component.submitCheckIn({ target: form });
    assert.equal(uploads.length, 2);
    assert.equal(calls.location.length, 2);
    assert.deepEqual(submitted, [form, form]);
});

test('device identity persists across page reloads where browser storage is available', async () => {
    const first = harness();
    first.component.init();
    const key = first.component.deviceKey;
    const reload = createLoginSelfie({
        gpsRequired: true,
        navigatorObject: { permissions: { async query({ name }) { return { state: first.states[name] }; } } },
        windowObject: first.windowObject,
        uploadForm() {},
    });
    reload.init();

    assert.match(key, /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/);
    assert.equal(reload.deviceKey, key);
});
