import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import vm from 'node:vm';

async function serviceWorkerHarness(options = {}) {
    const source = await readFile(new URL('../../public/service-worker.js', import.meta.url), 'utf8');
    const handlers = new Map();
    const deletedCaches = [];
    const cachedAssets = [];
    let skipWaitingCalls = 0;
    let claimCalls = 0;
    const cache = { addAll: async assets => cachedAssets.push(...assets) };
    const context = {
        URL,
        console,
        fetch: options.fetch || (async () => ({
            ok: true,
            json: async () => ({
                app: { file: 'assets/app-abc12345.js' },
                css: { file: 'assets/app-def67890.css' },
                unsafe: { file: '../secret.js' },
            }),
        })),
        caches: {
            open: async () => cache,
            keys: async () => ['salonos-pwa-v2', 'salonos-pwa-v3', 'other-cache', 'five-star-salon-static-v1'],
            delete: async name => { deletedCaches.push(name); return true; },
            match: async request => request === '/offline.html' ? { offline: true } : undefined,
        },
        importScripts() {},
        self: {
            location: { origin: 'https://salonos.test' },
            clients: { claim: async () => { claimCalls++; }, matchAll: async () => [] },
            addEventListener: (name, callback) => handlers.set(name, callback),
            skipWaiting: async () => { skipWaitingCalls++; },
        },
    };

    vm.runInNewContext(source, context, { filename: 'service-worker.js' });
    return { handlers, cachedAssets, deletedCaches, get skipWaitingCalls() { return skipWaitingCalls; }, get claimCalls() { return claimCalls; } };
}

test('service worker pre-caches static and versioned assets and removes obsolete caches', async () => {
    const harness = await serviceWorkerHarness();
    let installPromise;
    harness.handlers.get('install')({ waitUntil: promise => { installPromise = promise; } });
    await installPromise;

    assert.ok(harness.cachedAssets.includes('/offline.html'));
    assert.ok(harness.cachedAssets.includes('/build/assets/app-abc12345.js'));
    assert.ok(harness.cachedAssets.includes('/build/assets/app-def67890.css'));
    assert.ok(! harness.cachedAssets.includes('/build/../secret.js'));

    let activationPromise;
    harness.handlers.get('activate')({ waitUntil: promise => { activationPromise = promise; } });
    await activationPromise;
    assert.deepEqual(harness.deletedCaches.sort(), ['five-star-salon-static-v1', 'salonos-pwa-v2']);
    assert.equal(harness.claimCalls, 1);
});

test('service worker waits for a safe client signal before activation and serves offline navigation fallback', async () => {
    const harness = await serviceWorkerHarness({ fetch: async () => { throw new Error('offline'); } });
    harness.handlers.get('message')({ data: { type: 'SKIP_WAITING' } });
    assert.equal(harness.skipWaitingCalls, 1);

    let responsePromise;
    harness.handlers.get('fetch')({
        request: { method: 'GET', url: 'https://salonos.test/staff/dashboard', mode: 'navigate' },
        respondWith: promise => { responsePromise = promise; },
    });
    assert.deepEqual(await responsePromise, { offline: true });
});
