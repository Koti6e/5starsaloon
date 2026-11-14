import test from 'node:test';
import assert from 'node:assert/strict';
import { installPwaUpdateHandling } from '../../resources/js/pwa-updates.js';

class EventHub {
    listeners = new Map();

    addEventListener(name, callback) {
        const callbacks = this.listeners.get(name) || [];
        callbacks.push(callback);
        this.listeners.set(name, callbacks);
    }

    dispatch(name, event = {}) {
        for (const callback of this.listeners.get(name) || []) callback(event);
    }
}

function createUpdateHarness(pathname = '/admin/dashboard', initialDirty = false) {
    const serviceWorker = new EventHub();
    serviceWorker.controller = {};
    const document = new EventHub();
    document.visibilityState = 'visible';
    let dirty = initialDirty;
    document.querySelector = () => dirty ? {} : null;

    const storage = new Map();
    const window = new EventHub();
    window.location = { pathname, reloadCount: 0, reload() { this.reloadCount++; } };
    window.setInterval = (callback, delay) => { window.updateCheck = callback; window.updateCheckDelay = delay; };
    window.sessionStorage = {
        getItem: key => storage.get(key) ?? null,
        setItem: (key, value) => storage.set(key, value),
        removeItem: key => storage.delete(key),
    };

    let messages = 0;
    const waiting = { postMessage: message => { if (message.type === 'SKIP_WAITING') messages++; } };
    const registration = new EventHub();
    registration.waiting = waiting;
    registration.updateCalls = 0;
    registration.update = async () => { registration.updateCalls++; };

    installPwaUpdateHandling(registration, window, { serviceWorker }, document);

    return {
        document,
        registration,
        serviceWorker,
        window,
        setDirty(value) { dirty = value; },
        get messages() { return messages; },
    };
}

test('activates an available update and reloads once after controller change', () => {
    const harness = createUpdateHarness();

    assert.equal(harness.messages, 1);
    harness.serviceWorker.dispatch('controllerchange');
    harness.serviceWorker.dispatch('controllerchange');

    assert.equal(harness.window.location.reloadCount, 1);
    assert.equal(harness.window.sessionStorage.getItem('salonos-pwa-update-pending'), null);
    assert.equal(harness.registration.updateCalls, 1);

    harness.window.updateCheck();
    assert.equal(harness.window.updateCheckDelay, 60 * 60 * 1000);
    assert.equal(harness.registration.updateCalls, 2);
});

test('defers activation and reload while a form has unsaved input', () => {
    const harness = createUpdateHarness('/admin/dashboard', true);

    assert.equal(harness.messages, 0);
    harness.serviceWorker.dispatch('controllerchange');

    assert.equal(harness.window.location.reloadCount, 0);
    assert.equal(harness.window.sessionStorage.getItem('salonos-pwa-update-pending'), '1');

    harness.setDirty(false);
    harness.window.dispatch('pageshow');
    assert.equal(harness.window.location.reloadCount, 1);
});

test('defers updates on billing pages and applies them after leaving the workflow', () => {
    const harness = createUpdateHarness('/staff/billing/create');

    assert.equal(harness.messages, 0);
    assert.equal(harness.window.sessionStorage.getItem('salonos-pwa-update-pending'), '1');

    harness.window.location.pathname = '/staff/dashboard';
    harness.window.dispatch('pageshow');
    harness.serviceWorker.dispatch('controllerchange');

    assert.equal(harness.messages, 1);
    assert.equal(harness.window.location.reloadCount, 1);
});
