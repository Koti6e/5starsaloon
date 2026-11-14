const pendingUpdateKey = 'salonos-pwa-update-pending';
const activatedUpdateKey = 'salonos-pwa-update-activated';

function isSensitiveRoute(pathname) {
    return /^\/(?:admin|staff)\/(?:billing|payments|attendance)(?:\/|$)/.test(pathname)
        || pathname === '/staff/login-selfie';
}

export function installPwaUpdateHandling(registration, windowRef = window, navigatorRef = navigator, documentRef = document) {
    const serviceWorker = navigatorRef.serviceWorker;
    const hadController = Boolean(serviceWorker.controller);
    let submissionPending = false;
    let reloadStarted = false;

    const hasUnsavedForm = () => Boolean(documentRef.querySelector('form[data-pwa-dirty="true"]'));
    const canApplyUpdate = () => documentRef.visibilityState === 'visible'
        && ! submissionPending
        && ! hasUnsavedForm()
        && ! isSensitiveRoute(windowRef.location.pathname);

    const reloadWhenSafe = () => {
        if (! canApplyUpdate() || reloadStarted) return false;

        windowRef.sessionStorage.removeItem(pendingUpdateKey);
        windowRef.sessionStorage.removeItem(activatedUpdateKey);
        reloadStarted = true;
        windowRef.location.reload();
        return true;
    };

    const activateWhenSafe = () => {
        const waitingWorker = registration.waiting;
        if (! waitingWorker) return;

        windowRef.sessionStorage.setItem(pendingUpdateKey, '1');
        if (canApplyUpdate()) waitingWorker.postMessage({ type: 'SKIP_WAITING' });
    };

    documentRef.addEventListener('input', event => {
        event.target.closest?.('form')?.setAttribute('data-pwa-dirty', 'true');
    }, true);
    documentRef.addEventListener('change', event => {
        event.target.closest?.('form')?.setAttribute('data-pwa-dirty', 'true');
    }, true);
    documentRef.addEventListener('reset', event => {
        queueMicrotask(() => event.target.removeAttribute('data-pwa-dirty'));
    }, true);
    documentRef.addEventListener('submit', () => {
        submissionPending = true;
    }, true);

    registration.addEventListener('updatefound', () => {
        const installingWorker = registration.installing;
        if (! installingWorker) return;

        installingWorker.addEventListener('statechange', () => {
            if (hadController && installingWorker.state === 'installed') activateWhenSafe();
        });
    });

    serviceWorker.addEventListener('controllerchange', () => {
        if (! hadController || reloadStarted) return;
        windowRef.sessionStorage.setItem(activatedUpdateKey, '1');
        windowRef.sessionStorage.setItem(pendingUpdateKey, '1');
        reloadWhenSafe();
    });

    const checkPendingUpdate = () => {
        if (windowRef.sessionStorage.getItem(pendingUpdateKey) !== '1'
            || windowRef.sessionStorage.getItem(activatedUpdateKey) !== '1') return;
        if (canApplyUpdate()) reloadWhenSafe();
    };

    const checkForUpdates = () => {
        if (documentRef.visibilityState !== 'visible') return;
        registration.update().catch(error => {
            console.warn('SalonOS could not check for a service worker update.', error);
        });
    };

    documentRef.addEventListener('visibilitychange', () => {
        if (documentRef.visibilityState !== 'visible') return;
        activateWhenSafe();
        checkPendingUpdate();
    });
    windowRef.addEventListener('pageshow', () => {
        submissionPending = false;
        activateWhenSafe();
        checkPendingUpdate();
        checkForUpdates();
    });

    activateWhenSafe();
    checkPendingUpdate();
    checkForUpdates();
    windowRef.setInterval?.(checkForUpdates, 60 * 60 * 1000);
}
