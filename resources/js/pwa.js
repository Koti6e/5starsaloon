const installEvents = new WeakMap();
const foregroundMessaging = new WeakSet();

function reportStatus(element, message, isError = false) {
    const status = element?.querySelector('[data-pwa-status]');
    if (! status) return;

    status.textContent = message;
    status.classList.toggle('text-red-300', isError);
    status.classList.toggle('text-emerald-300', ! isError);
}

function dismissInstallCard(card) {
    localStorage.setItem(`salonos-pwa-install-dismissed-${card.dataset.pwaAudience}`, String(Date.now()));
    card.hidden = true;
}

function dismissPushCard(card) {
    if (localStorage.getItem(`salonos-pwa-push-enabled-${card.dataset.pwaAudience}`) !== '1') {
        localStorage.setItem(`salonos-pwa-push-dismissed-${card.dataset.pwaAudience}`, String(Date.now()));
    }
    card.hidden = true;
}

function canShowInstallCard(card) {
    const dismissedAt = Number(localStorage.getItem(`salonos-pwa-install-dismissed-${card.dataset.pwaAudience}`) || 0);
    return Date.now() - dismissedAt > 30 * 24 * 60 * 60 * 1000
        && ! window.matchMedia('(display-mode: standalone)').matches
        && ! navigator.standalone;
}

function canShowPushCard(card) {
    if (localStorage.getItem(`salonos-pwa-push-enabled-${card.dataset.pwaAudience}`) === '1') return true;

    const dismissedAt = Number(localStorage.getItem(`salonos-pwa-push-dismissed-${card.dataset.pwaAudience}`) || 0);
    return Date.now() - dismissedAt > 30 * 24 * 60 * 60 * 1000;
}

function isAllowedNotificationUrl(target) {
    const publicPaths = ['/', '/services', '/gallery', '/about', '/contact', '/book-appointment', '/privacy-policy'];
    return target.origin === window.location.origin
        && (publicPaths.includes(target.pathname) || /^\/admin\/appointments(?:\/\d+)?$/.test(target.pathname));
}

function showInstallCard(card, message) {
    if (! canShowInstallCard(card)) return;

    const promptButton = card.querySelector('[data-pwa-install]');
    promptButton.hidden = ! installEvents.has(card);
    if (! promptButton.hidden) {
        card.querySelector('[data-pwa-instructions]').hidden = true;
    } else {
        card.querySelector('[data-pwa-instructions]').hidden = false;
    }
    card.querySelector('[data-pwa-message]').textContent = message;
    card.hidden = false;
}

async function firebaseMessagingModules() {
    const [app, messaging] = await Promise.all([
        import('https://www.gstatic.com/firebasejs/10.13.2/firebase-app.js'),
        import('https://www.gstatic.com/firebasejs/10.13.2/firebase-messaging.js'),
    ]);

    return { ...app, ...messaging };
}

function listenForForegroundMessages(messaging, onMessage) {
    if (foregroundMessaging.has(messaging)) return;

    onMessage(messaging, async payload => {
        const notification = payload.data || {};
        if (! notification.title) return;

        const target = new URL(notification.url || '/', window.location.origin);
        const url = isAllowedNotificationUrl(target)
            ? `${target.pathname}${target.search}${target.hash}`
            : '/';
        (await navigator.serviceWorker.ready).showNotification(notification.title, {
            body: notification.body || '',
            icon: '/images/brand/logo-small.webp',
            data: { url },
        });
    });

    foregroundMessaging.add(messaging);
}

async function submitSubscription(element, remove = false) {
    const configElement = document.querySelector('[data-pwa-firebase-config]');
    const firebaseConfig = configElement ? JSON.parse(configElement.textContent) : null;
    const vapidKey = configElement?.dataset.vapidKey;

    if (! firebaseConfig || ! vapidKey || ! ('Notification' in window) || ! ('serviceWorker' in navigator)) {
        reportStatus(element, 'Push notifications are not available in this browser.', true);
        return;
    }

    if (! remove) {
        const permission = Notification.permission === 'granted'
            ? 'granted'
            : await Notification.requestPermission();
        if (permission !== 'granted') {
            reportStatus(element, 'Notifications were not enabled. You can change this in browser settings.');
            return;
        }
    }

    const { initializeApp, getApps, getMessaging, getToken, deleteToken, onMessage } = await firebaseMessagingModules();
    const registration = await navigator.serviceWorker.ready;
    const firebaseApp = getApps().find(app => app.name === '[DEFAULT]') || initializeApp(firebaseConfig);
    const messaging = getMessaging(firebaseApp);

    if (remove) {
        const token = await getToken(messaging, { vapidKey, serviceWorkerRegistration: registration });
        if (token) {
            const response = await fetch(element.dataset.pwaUnsubscribeUrl, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ token }),
            });
            if (! response.ok) throw new Error(`Subscription removal failed (${response.status}).`);
            await deleteToken(messaging);
        }
        localStorage.removeItem(`salonos-pwa-push-enabled-${element.dataset.pwaAudience}`);
        element.querySelector('[data-pwa-push-enable]').hidden = false;
        element.querySelector('[data-pwa-push-disable]').hidden = true;
        reportStatus(element, 'Notifications are disabled for this device.');
        return;
    }

    const token = await getToken(messaging, { vapidKey, serviceWorkerRegistration: registration });
    if (! token) throw new Error('Firebase did not return a device subscription token.');

    const response = await fetch(element.dataset.pwaSubscribeUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        body: JSON.stringify({ token, platform: 'web' }),
    });
    if (! response.ok) throw new Error(`Subscription registration failed (${response.status}).`);

    localStorage.setItem(`salonos-pwa-push-enabled-${element.dataset.pwaAudience}`, '1');
    element.querySelector('[data-pwa-push-enable]').hidden = true;
    element.querySelector('[data-pwa-push-disable]').hidden = false;
    listenForForegroundMessages(messaging, onMessage);

    reportStatus(element, 'Notifications are enabled on this device.');
}

window.addEventListener('beforeinstallprompt', event => {
    event.preventDefault();
    document.querySelectorAll('[data-pwa-install-card]').forEach(card => {
        installEvents.set(card, event);
        showInstallCard(card, 'Install the salon app for a convenient, app-like experience.');
    });
});

window.addEventListener('appinstalled', () => {
    document.querySelectorAll('[data-pwa-install-card]').forEach(card => {
        card.hidden = true;
        installEvents.delete(card);
    });
});

document.addEventListener('click', async event => {
    const dismissButton = event.target.closest('[data-pwa-dismiss]');
    if (dismissButton) {
        const installCard = dismissButton.closest('[data-pwa-install-card]');
        if (installCard) {
            dismissInstallCard(installCard);
        } else {
            dismissPushCard(dismissButton.closest('[data-pwa-push-card]'));
        }
        return;
    }

    const installButton = event.target.closest('[data-pwa-install]');
    if (installButton) {
        const card = installButton.closest('[data-pwa-install-card]');
        const installEvent = installEvents.get(card);
        if (! installEvent) return;

        installEvent.prompt();
        const choice = await installEvent.userChoice;
        if (choice.outcome === 'accepted') {
            card.hidden = true;
        } else {
            installButton.hidden = true;
            card.querySelector('[data-pwa-instructions]').hidden = false;
            card.querySelector('[data-pwa-message]').textContent = 'Use your browser menu to install the app whenever you are ready.';
        }
        installEvents.delete(card);
        return;
    }

    const pushButton = event.target.closest('[data-pwa-push-enable], [data-pwa-push-disable]');
    if (pushButton) {
        const card = pushButton.closest('[data-pwa-push-card]');
        pushButton.disabled = true;
        reportStatus(card, 'Updating this device…');
        try {
            await submitSubscription(card, pushButton.hasAttribute('data-pwa-push-disable'));
        } catch (error) {
            console.error('SalonOS push notification setup failed.', error);
            reportStatus(card, 'Could not update notifications. Please try again.', true);
        } finally {
            pushButton.disabled = false;
        }
    }
});

document.addEventListener('DOMContentLoaded', async () => {
    const isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent);
    document.querySelectorAll('[data-pwa-install-card]').forEach(card => {
        window.setTimeout(() => {
            if (isIOS) {
                showInstallCard(card, 'On iPhone or iPad, tap Share, then Add to Home Screen.');
            } else if (! installEvents.has(card)) {
                showInstallCard(card, 'Open your browser menu and choose “Install app” or “Add to Home Screen”.');
            }
        }, 10000);

        card.querySelector('[data-pwa-instructions]')?.addEventListener('click', () => {
            card.querySelector('[data-pwa-message]').textContent = 'Open your browser menu and choose “Install app” or “Add to Home Screen”.';
        });
    });

    document.querySelectorAll('[data-pwa-push-card]').forEach(card => {
        const available = 'Notification' in window && 'serviceWorker' in navigator;
        const subscribed = localStorage.getItem(`salonos-pwa-push-enabled-${card.dataset.pwaAudience}`) === '1';
        card.hidden = ! available || Notification.permission === 'denied' || ! canShowPushCard(card)
            || card.dataset.pwaAudience === 'customer';
        if (available) {
            card.querySelector('[data-pwa-push-enable]').hidden = subscribed;
            card.querySelector('[data-pwa-push-disable]').hidden = ! subscribed;
        }
        if (subscribed) {
            reportStatus(card, 'You can manage notification permission for this device here.');
        }
        if (available && Notification.permission === 'granted') {
            firebaseMessagingModules()
                .then(({ initializeApp, getApps, getMessaging, onMessage }) => {
                    const firebaseConfig = JSON.parse(document.querySelector('[data-pwa-firebase-config]').textContent);
                    const app = getApps().find(firebaseApp => firebaseApp.name === '[DEFAULT]') || initializeApp(firebaseConfig);
                    listenForForegroundMessages(getMessaging(app), onMessage);
                })
                .catch(error => {
                    console.error('SalonOS foreground notifications could not be initialized.', error);
                    reportStatus(card, 'Background notifications remain enabled, but this page could not prepare in-page alerts.', true);
                });
        }
    });

    const revealCustomerPushCards = () => {
        document.querySelectorAll('[data-pwa-push-card][data-pwa-audience="customer"]').forEach(card => {
            if ('Notification' in window && Notification.permission !== 'denied' && canShowPushCard(card)) {
                card.hidden = false;
            }
        });
    };
    let customerEngaged = false;
    const scheduleCustomerPushInvite = () => {
        if (customerEngaged) return;
        customerEngaged = true;
        window.setTimeout(revealCustomerPushCards, 8000);
    };
    ['click', 'keydown', 'scroll', 'touchstart'].forEach(name => {
        window.addEventListener(name, scheduleCustomerPushInvite, { once: true, passive: true });
    });
    window.addEventListener('appinstalled', () => window.setTimeout(revealCustomerPushCards, 3000), { once: true });

    if ('serviceWorker' in navigator && window.isSecureContext) {
        try {
            await navigator.serviceWorker.register('/service-worker.js', { scope: '/' });
        } catch (error) {
            console.error('SalonOS service worker registration failed.', error);
            document.querySelectorAll('[data-pwa-install-card]').forEach(card => {
                reportStatus(card, 'App installation is currently unavailable.', true);
            });
        }
    }
});
