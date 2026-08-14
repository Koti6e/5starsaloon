@props(['settings' => [], 'service' => null])
@php
    $whatsappSettings = \App\Models\SalonSetting::query()
        ->whereIn('key', ['whatsapp_number', 'whatsapp_floater_enabled', 'whatsapp_default_message'])
        ->pluck('value', 'key')
        ->all();
    $settings = array_replace($settings, $whatsappSettings);

    $enabledValue = strtolower((string) ($settings['whatsapp_floater_enabled'] ?? '1'));
    $enabled = ! in_array($enabledValue, ['0', 'false', 'off'], true);
    $digits = preg_replace('/\D+/', '', (string) ($settings['whatsapp_number'] ?? '9003866903'));
    if (strlen($digits) < 10) {
        $digits = '9003866903';
    }
    if (strlen($digits) === 10) {
        $digits = '91'.$digits;
    }
    $isPackage = $service?->is_package ?? false;
    $message = match (true) {
        $service && $isPackage => "Hello, I’m interested in {$service->name}. Please share the available appointment timings.",
        $service => "Hello, I’m interested in {$service->name}. Please share the available appointment timings.",
        request()->routeIs('appointments.book') && request('type') === 'home' => 'Hello, I would like to enquire about Elite Home Service availability.',
        default => $settings['whatsapp_default_message'] ?? 'Hi 5 Star New Look Salon, I would like to know more about your services.',
    };
@endphp

@if ($enabled && strlen($digits) >= 11)
    <a
        href="tel:9003866903"
        aria-label="Call 5 Star New Look Salon"
        title="Call 5 Star New Look Salon"
        class="fixed bottom-[calc(env(safe-area-inset-bottom)+5.5rem)] right-4 z-50 flex h-14 w-14 items-center justify-center rounded-full border border-white/60 bg-[var(--accent)] text-white shadow-2xl ring-4 ring-white/70 transition hover:scale-105 focus:outline-none focus:ring-4 focus:ring-[var(--glow)] md:bottom-[6.5rem]"
    >
        <svg viewBox="0 0 24 24" aria-hidden="true" class="h-7 w-7 fill-none stroke-current" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.35 1.9.66 2.8a2 2 0 0 1-.45 2.11L8.05 9.9a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.31 1.84.53 2.8.66A2 2 0 0 1 22 16.92Z"/>
        </svg>
    </a>

    <a
        href="https://wa.me/{{ $digits }}?text={{ rawurlencode($message) }}"
        aria-label="Chat on WhatsApp"
        title="Chat on WhatsApp"
        class="fixed bottom-[calc(env(safe-area-inset-bottom)+1rem)] right-4 z-50 flex h-14 w-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-2xl ring-4 ring-white/70 transition hover:scale-105 focus:outline-none focus:ring-4 focus:ring-[var(--glow)] md:bottom-6"
    >
        <svg viewBox="0 0 32 32" aria-hidden="true" class="h-8 w-8 fill-current">
            <path d="M16.04 3.2A12.72 12.72 0 0 0 5.18 22.5L3.7 28.8l6.45-1.45A12.73 12.73 0 1 0 16.04 3.2Zm0 2.35a10.38 10.38 0 0 1 8.86 15.78 10.35 10.35 0 0 1-13.74 3.66l-.45-.24-3.84.86.88-3.72-.29-.48A10.38 10.38 0 0 1 16.04 5.55Zm-4.2 5.44c-.22 0-.58.08-.89.41-.3.33-1.16 1.14-1.16 2.77s1.19 3.21 1.35 3.43c.17.22 2.3 3.68 5.66 5.01 2.8 1.11 3.37.89 3.98.84.61-.06 1.97-.8 2.25-1.58.28-.78.28-1.44.2-1.58-.08-.14-.31-.22-.64-.39-.33-.17-1.97-.97-2.27-1.08-.31-.11-.53-.17-.75.17-.22.33-.86 1.08-1.05 1.3-.19.22-.39.25-.72.08-.33-.17-1.4-.52-2.67-1.65-.99-.88-1.65-1.96-1.84-2.29-.19-.33-.02-.51.14-.68.14-.14.33-.39.5-.58.17-.19.22-.33.33-.55.11-.22.06-.42-.03-.58-.08-.17-.75-1.81-1.03-2.48-.27-.65-.55-.56-.75-.57h-.61Z"/>
        </svg>
    </a>
@endif
