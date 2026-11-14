import test from 'node:test';
import assert from 'node:assert/strict';
import { createLoginGps } from '../../resources/js/login-gps.js';

function element(value = '') {
    const handlers = {};
    return {
        value,
        textContent: '',
        style: {},
        dataset: {},
        addEventListener(name, callback) { handlers[name] = callback; },
        dispatch(name, event = {}) { return handlers[name]?.(event); },
        handlers,
    };
}

function harness({ permission = 'granted', username = 'staff1', config = {} } = {}) {
    let currentPermission = permission;
    const pending = [];
    const calls = [];
    const permissionStatuses = [];
    const elements = {
        form: element(),
        username: element(username),
        panel: element(),
        status: element(),
        retry: element(),
        latitude: element(),
        longitude: element(),
        accuracy: element(),
        timestamp: element(),
    };
    elements.form.requestSubmit = (submitter) => {
        elements.form.submissions.push(submitter ?? null);
        elements.form.dispatch('submit', { preventDefault() {}, submitter });
    };
    elements.form.submissions = [];

    const navigatorObject = {
        permissions: {
            async query() {
                const status = { state: currentPermission, onchange: null };
                permissionStatuses.push(status);
                return status;
            },
        },
        geolocation: {
            getCurrentPosition(success, failure, options) {
                calls.push(options);
                pending.push({ success, failure });
            },
        },
    };
    const windowObject = { addEventListener() {} };
    const now = 1_800_000_000_000;
    const gps = createLoginGps({ elements, navigatorObject, windowObject, configuration: {
        salon_latitude: 12.8821489,
        salon_longitude: 80.080876,
        radius_meters: 200,
        accuracy_limit_meters: 100,
        ...config,
    }, now: () => now });
    gps.start();

    return {
        gps, elements, pending, calls, permissionStatuses, now,
        setPermission(state) { currentPermission = state; },
    };
}

function submit(elements) {
    return elements.form.dispatch('submit', {
        preventDefault() { this.prevented = true; },
        submitter: { type: 'submit', name: 'login' },
    });
}

function position(now, { latitude = 12.8821489, longitude = 80.080876, accuracy = 20, timestamp = now } = {}) {
    return { coords: { latitude, longitude, accuracy }, timestamp };
}

test('Staff1 and Staff2 request a fresh high-accuracy location only at sign-in', async () => {
    for (const username of ['staff1', 'staff2']) {
        const { gps, elements, pending, calls, now } = harness({ username });
        await Promise.resolve();
        assert.equal(gps.permission, 'granted');
        assert.deepEqual(calls, []);

        await submit(elements);
        assert.equal(calls.length, 1);
        assert.deepEqual(calls[0], { enableHighAccuracy: true, maximumAge: 0, timeout: 20_000 });
        pending[0].success(position(now));

        assert.equal(elements.latitude.value, '12.8821489');
        assert.equal(elements.longitude.value, '80.080876');
        assert.equal(elements.accuracy.value, '20');
        assert.equal(elements.timestamp.value, String(now));
        assert.equal(elements.form.submissions.length, 1);
        assert.equal(gps.gpsSubmitAllowed, false);
    }
});

test('poor accuracy offers a manual retry and accepts a later improved fix', async () => {
    const { gps, elements, pending, calls, now } = harness();
    await Promise.resolve();
    await submit(elements);
    pending[0].success(position(now, { accuracy: 86616.1 }));

    assert.match(elements.status.textContent, /not accurate enough/);
    assert.equal(elements.retry.style.display, 'inline-flex');
    assert.equal(elements.latitude.value, '');

    await elements.retry.dispatch('click');
    assert.equal(calls.length, 2);
    pending[1].success(position(now, { accuracy: 20 }));

    assert.equal(elements.accuracy.value, '20');
    assert.equal(elements.form.submissions.length, 1);
    assert.match(elements.status.textContent, /Location verified/);
});

test('accuracy and salon distance are checked separately', async () => {
    const { gps, elements, pending, now } = harness();
    await Promise.resolve();
    await submit(elements);
    pending[0].success(position(now, { latitude: 12.8851489, accuracy: 20 }));

    assert.match(elements.status.textContent, /metres from the salon/);
    assert.doesNotMatch(elements.status.textContent, /not accurate enough/);
    assert.equal(elements.form.submissions.length, 0);
    assert.equal(gps.attempts, 1);
});

test('stale GPS fixes are discarded and never submitted', async () => {
    const { gps, elements, pending, now } = harness();
    await Promise.resolve();
    await submit(elements);
    pending[0].success(position(now, { timestamp: now - 121_000 }));

    assert.match(elements.status.textContent, /stale/);
    assert.equal(elements.form.submissions.length, 0);
    assert.equal(elements.timestamp.value, '');
});

test('denied location permission shows browser settings guidance without calling geolocation', async () => {
    const { gps, elements, calls } = harness({ permission: 'denied' });
    await Promise.resolve();
    await submit(elements);

    assert.equal(gps.permission, 'denied');
    assert.equal(calls.length, 0);
    assert.match(elements.status.textContent, /browser settings/);
});

test('a denied permission restored in browser settings can be retried without reloading', async () => {
    const { gps, elements, pending, now, setPermission } = harness({ permission: 'denied' });
    await Promise.resolve();
    assert.match(elements.status.textContent, /browser settings/);
    assert.equal(elements.retry.style.display, 'inline-flex');

    setPermission('granted');
    await elements.retry.dispatch('click');
    pending[0].success(position(now));
    assert.equal(gps.permission, 'granted');
    assert.equal(elements.form.submissions.length, 1);
});

test('GPS unavailable and timeout failures allow bounded manual retry', async () => {
    const { gps, elements, pending, calls, now } = harness();
    await Promise.resolve();
    await submit(elements);
    pending[0].failure({ code: 2 });
    assert.match(elements.status.textContent, /GPS is unavailable/);

    await elements.retry.dispatch('click');
    pending[1].failure({ code: 3 });
    assert.match(elements.status.textContent, /timed out/);

    await elements.retry.dispatch('click');
    pending[2].success(position(now, { accuracy: 20 }));
    assert.equal(calls.length, 3);
    assert.equal(elements.form.submissions.length, 1);

    const exhausted = harness();
    await Promise.resolve();
    await submit(exhausted.elements);
    exhausted.pending[0].success(position(exhausted.now, { accuracy: 900 }));
    await exhausted.elements.retry.dispatch('click');
    exhausted.pending[1].success(position(exhausted.now, { accuracy: 900 }));
    await exhausted.elements.retry.dispatch('click');
    exhausted.pending[2].success(position(exhausted.now, { accuracy: 900 }));
    assert.equal(exhausted.elements.retry.style.display, 'none');
    assert.match(exhausted.elements.status.textContent, /after three attempts/);
    assert.equal(exhausted.elements.form.submissions.length, 0);
});

test('non-Staff1 and non-Staff2 logins do not invoke geolocation', async () => {
    const { elements, calls } = harness({ username: 'admin' });
    await Promise.resolve();
    await elements.form.dispatch('submit', { preventDefault() { throw new Error('should not intercept'); } });
    assert.deepEqual(calls, []);
});
