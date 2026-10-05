@props(['audience', 'enablePush' => false])

@php
    $firebaseWebConfig = [
        'apiKey' => config('services.firebase.web_api_key'),
        'authDomain' => config('services.firebase.auth_domain'),
        'projectId' => config('services.firebase.project_id'),
        'messagingSenderId' => config('services.firebase.messaging_sender_id'),
        'appId' => config('services.firebase.app_id'),
    ];
    $pushAvailable = $enablePush
        && app(\App\Services\FirebaseCloudMessaging::class)->isWebPushConfigured()
        && collect($firebaseWebConfig)->every(fn ($value) => filled($value));
    $subscribeUrl = $audience === 'staff' ? route('push-device-tokens.store') : route('customer-push-subscriptions.store');
    $unsubscribeUrl = $audience === 'staff' ? route('push-device-tokens.destroy') : route('customer-push-subscriptions.destroy');
@endphp

<section
    data-pwa-install-card
    data-pwa-audience="{{ $audience }}"
    class="fixed inset-x-4 bottom-4 z-[100] mx-auto max-w-lg rounded-2xl border border-amber-300/30 bg-neutral-950/95 p-4 text-sm text-amber-50 shadow-2xl backdrop-blur"
    hidden
    aria-live="polite"
>
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="font-semibold">{{ $audience === 'staff' ? 'Install SalonOS Staff' : 'Install the salon app' }}</h2>
            <p data-pwa-message class="mt-1 text-amber-100/75"></p>
            <p data-pwa-status class="mt-2 text-sm" role="status"></p>
            <button type="button" data-pwa-instructions class="mt-2 underline underline-offset-2" hidden>Show installation steps</button>
        </div>
        <button type="button" data-pwa-dismiss class="rounded px-2 py-1 text-amber-100/70 hover:text-white" aria-label="Dismiss installation invitation">Close</button>
    </div>
    <button type="button" data-pwa-install class="mt-3 rounded-lg bg-amber-300 px-4 py-2 font-semibold text-neutral-950">Install app</button>
</section>

@if ($pushAvailable && $enablePush)
    <script type="application/json" data-pwa-firebase-config data-vapid-key="{{ config('services.firebase.vapid_key') }}">{!! json_encode($firebaseWebConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    <section
        data-pwa-push-card
        data-pwa-audience="{{ $audience }}"
        data-pwa-subscribe-url="{{ $subscribeUrl }}"
        data-pwa-unsubscribe-url="{{ $unsubscribeUrl }}"
        class="fixed inset-x-4 bottom-28 z-[99] mx-auto max-w-lg rounded-2xl border border-emerald-300/30 bg-neutral-950/95 p-4 text-sm text-amber-50 shadow-2xl backdrop-blur"
        hidden
        aria-live="polite"
    >
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="font-semibold">{{ $audience === 'staff' ? 'Salon notifications' : 'Salon updates' }}</h2>
                <p class="mt-1 text-amber-100/75">Choose whether this device may receive salon updates. You can disable them here at any time. See our <a class="underline" href="{{ route('privacy-policy') }}">privacy policy</a>.</p>
                <p data-pwa-status class="mt-2 text-sm" role="status"></p>
            </div>
            <button type="button" data-pwa-dismiss class="rounded px-2 py-1 text-amber-100/70 hover:text-white" aria-label="Dismiss notification options">Close</button>
        </div>
        <button type="button" data-pwa-push-enable class="mt-3 rounded-lg bg-emerald-300 px-4 py-2 font-semibold text-neutral-950">Enable notifications</button>
        <button type="button" data-pwa-push-disable class="mt-3 rounded-lg border border-amber-100/30 px-4 py-2 font-semibold text-amber-50" hidden>Disable on this device</button>
    </section>
@endif
