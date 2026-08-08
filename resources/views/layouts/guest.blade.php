@php
    $isLogin = request()->routeIs('login');
    $themeNames = [
        'emerald' => 'Neon Emerald',
        'sapphire' => 'Neon Sapphire',
        'crimson' => 'Neon Crimson',
        'gold' => 'Neon Gold',
        'pearl' => 'Neon Pearl',
        'obsidian' => 'Neon Obsidian',
    ];
    $storedTheme = \App\Models\SalonSetting::getValue('default_theme', 'emerald');
    $defaultTheme = match ($storedTheme) {
        'light' => 'pearl',
        'dark' => 'obsidian',
        default => array_key_exists($storedTheme, $themeNames) ? $storedTheme : 'emerald',
    };
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $defaultTheme }}" @if($isLogin) data-auth-login="true" @endif>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', '5 Star New Look Salon') }}</title>
        <link rel="icon" href="{{ asset('favicon.ico') }}">
        <script>
            (() => {
                const defaultTheme = @json($defaultTheme);
                const savedTheme = localStorage.getItem('salon-color-theme') || localStorage.getItem('salon-theme');
                const legacyTheme = savedTheme === 'light' ? 'pearl' : savedTheme === 'dark' ? 'obsidian' : savedTheme;
                const theme = legacyTheme || defaultTheme || 'emerald';
                document.documentElement.dataset.defaultTheme = defaultTheme;
                document.documentElement.setAttribute('data-theme', theme);
                const isLogin = @json($isLogin);
                document.documentElement.classList.toggle('theme-dark', ! isLogin && theme === 'obsidian');
                document.documentElement.classList.toggle('theme-light', ! isLogin && theme !== 'obsidian');
            })();
        </script>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700|playfair-display:600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @if ($isLogin)
            <style>
                [data-theme="emerald"] {
                    --auth-primary: #20d893;
                    --auth-primary-strong: #8af7c6;
                    --auth-primary-soft: rgba(32, 216, 147, 0.18);
                    --auth-glow: rgba(32, 216, 147, 0.34);
                }

                [data-theme="sapphire"] {
                    --auth-primary: #4aa3ff;
                    --auth-primary-strong: #a7d7ff;
                    --auth-primary-soft: rgba(74, 163, 255, 0.18);
                    --auth-glow: rgba(74, 163, 255, 0.34);
                }

                [data-theme="crimson"] {
                    --auth-primary: #ff527d;
                    --auth-primary-strong: #ffb0c4;
                    --auth-primary-soft: rgba(255, 82, 125, 0.18);
                    --auth-glow: rgba(255, 82, 125, 0.32);
                }

                [data-theme="gold"] {
                    --auth-primary: #d5a93b;
                    --auth-primary-strong: #f4d27a;
                    --auth-primary-soft: rgba(213, 169, 59, 0.2);
                    --auth-glow: rgba(213, 169, 59, 0.34);
                }

                [data-theme="pearl"] {
                    --auth-primary: #0aa574;
                    --auth-primary-strong: #82f0c4;
                    --auth-primary-soft: rgba(10, 165, 116, 0.18);
                    --auth-glow: rgba(10, 165, 116, 0.32);
                }

                [data-theme="obsidian"] {
                    --auth-primary: #d8dce3;
                    --auth-primary-strong: #ffffff;
                    --auth-primary-soft: rgba(229, 231, 235, 0.14);
                    --auth-glow: rgba(229, 231, 235, 0.2);
                }

                html[data-auth-login="true"] body.auth-cinema {
                    background-color: #050403 !important;
                    background-image:
                        radial-gradient(circle at 50% 12%, var(--auth-glow), transparent 24rem),
                        linear-gradient(90deg, rgba(0, 0, 0, 0.48), rgba(0, 0, 0, 0.18) 48%, rgba(0, 0, 0, 0.5)),
                        linear-gradient(180deg, rgba(0, 0, 0, 0.2), rgba(0, 0, 0, 0.74)),
                        url("{{ asset('images/auth/salon-login-bg.webp') }}") !important;
                    background-position: center !important;
                    background-size: cover !important;
                    color: #fff9ea !important;
                }

                .auth-emblem {
                    box-shadow: 0 0 0 1px color-mix(in srgb, var(--auth-primary-strong) 62%, transparent), 0 0 44px var(--auth-glow);
                }

                .auth-emblem::before {
                    background: conic-gradient(from 140deg, transparent, var(--auth-primary-strong), transparent 34%, transparent);
                    content: "";
                    inset: -0.55rem;
                    opacity: 0.78;
                    position: absolute;
                    border-radius: 999px;
                    z-index: -1;
                }

                html[data-auth-login="true"] .auth-card {
                    animation: authCardIn 340ms ease-out both;
                    background: linear-gradient(180deg, rgba(13, 12, 10, 0.82), rgba(8, 7, 6, 0.92));
                    border-color: color-mix(in srgb, var(--auth-primary-strong) 42%, transparent);
                    box-shadow: 0 28px 90px rgba(0, 0, 0, 0.54), 0 0 42px color-mix(in srgb, var(--auth-glow) 70%, transparent);
                    color: #fff9ea;
                }

                html[data-auth-login="true"] input.auth-input {
                    background: rgba(10, 10, 10, 0.72) !important;
                    border: 1px solid rgba(255, 255, 255, 0.14) !important;
                    color: #fff9ea !important;
                    min-height: 4rem;
                }

                html[data-auth-login="true"] input.auth-input::placeholder {
                    color: rgba(255, 249, 234, 0.58) !important;
                }

                html[data-auth-login="true"] input.auth-input:focus {
                    background: rgba(10, 10, 10, 0.84) !important;
                    border-color: var(--auth-primary-strong) !important;
                    box-shadow: 0 0 0 4px var(--auth-primary-soft), 0 0 24px var(--auth-glow) !important;
                }

                html[data-auth-login="true"] input.auth-input:-webkit-autofill,
                html[data-auth-login="true"] input.auth-input:-webkit-autofill:hover,
                html[data-auth-login="true"] input.auth-input:-webkit-autofill:focus {
                    -webkit-box-shadow: 0 0 0 1000px rgba(10, 10, 10, 0.92) inset !important;
                    -webkit-text-fill-color: #fff9ea !important;
                    caret-color: #fff9ea !important;
                }

                html[data-auth-login="true"] input[type="checkbox"] {
                    background-color: rgba(10, 10, 10, 0.7) !important;
                    border-color: color-mix(in srgb, var(--auth-primary-strong) 45%, transparent) !important;
                    color: var(--auth-primary) !important;
                }

                .auth-button {
                    background: linear-gradient(135deg, var(--auth-primary-strong), var(--auth-primary));
                    box-shadow: 0 14px 38px color-mix(in srgb, var(--auth-glow) 80%, transparent);
                }

                .auth-theme-panel {
                    background: rgba(9, 8, 7, 0.72);
                    border-color: rgba(255, 255, 255, 0.14);
                    backdrop-filter: blur(18px);
                }

                @keyframes authCardIn {
                    from { opacity: 0; transform: translateY(14px) scale(0.985); }
                    to { opacity: 1; transform: translateY(0) scale(1); }
                }

                @keyframes authGlowBreathe {
                    0%, 100% { box-shadow: 0 0 0 1px color-mix(in srgb, var(--auth-primary-strong) 54%, transparent), 0 0 34px var(--auth-glow); }
                    50% { box-shadow: 0 0 0 1px color-mix(in srgb, var(--auth-primary-strong) 80%, transparent), 0 0 58px var(--auth-glow); }
                }

                @media (prefers-reduced-motion: no-preference) {
                    .auth-emblem {
                        animation: authGlowBreathe 4.8s ease-in-out infinite;
                    }
                }

                @media (max-width: 640px) {
                    html[data-auth-login="true"] body.auth-cinema {
                        background-position: 58% center !important;
                    }
                }
            </style>
        @endif
    </head>
    @if ($isLogin)
        <body class="auth-cinema min-h-screen overflow-x-hidden font-sans text-[#fff9ea] antialiased">
            <div
                x-data="{
                    theme: document.documentElement.getAttribute('data-theme') || @js($defaultTheme),
                    themeOpen: false,
                    names: @js($themeNames),
                    setTheme(value) {
                        this.theme = value;
                        localStorage.setItem('salon-color-theme', value);
                        localStorage.setItem('salon-theme', value === 'obsidian' ? 'dark' : 'light');
                        document.documentElement.setAttribute('data-theme', value);
                        document.documentElement.classList.remove('theme-dark', 'theme-light');
                        this.themeOpen = false;
                    }
                }"
                class="relative flex min-h-screen flex-col px-4 py-5 sm:px-6 lg:px-10"
            >
                <div class="pointer-events-none absolute inset-0 bg-black/30"></div>
                <div class="pointer-events-none absolute inset-x-0 bottom-0 h-44 bg-gradient-to-t from-black/88 to-transparent"></div>

                <div class="relative z-20 flex justify-end">
                    <div class="relative">
                        <button
                            type="button"
                            class="auth-theme-panel inline-flex min-h-11 items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold text-[#fff9ea] transition hover:border-[var(--auth-primary-strong)] focus:outline-none focus:ring-4 focus:ring-[var(--auth-primary-soft)]"
                            @click="themeOpen = ! themeOpen"
                            :aria-expanded="themeOpen.toString()"
                        >
                            <svg class="h-4 w-4 text-[var(--auth-primary-strong)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.36-6.36-1.42 1.42M7.06 16.94l-1.42 1.42m12.72 0-1.42-1.42M7.06 7.06 5.64 5.64"/>
                                <circle cx="12" cy="12" r="4"/>
                            </svg>
                            <span x-text="names[theme] || 'Theme'"></span>
                        </button>
                        <div
                            x-show="themeOpen"
                            x-transition
                            @click.away="themeOpen = false"
                            class="auth-theme-panel absolute right-0 mt-2 w-56 rounded-2xl border p-2 shadow-2xl"
                        >
                            <template x-for="(label, key) in names" :key="key">
                                <button
                                    type="button"
                                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm text-[#fff9ea] transition hover:bg-white/10"
                                    @click="setTheme(key)"
                                >
                                    <span class="h-3 w-3 rounded-full" :class="{
                                        'bg-emerald-400': key === 'emerald',
                                        'bg-blue-400': key === 'sapphire',
                                        'bg-rose-400': key === 'crimson',
                                        'bg-amber-300': key === 'gold',
                                        'bg-stone-100': key === 'pearl',
                                        'bg-slate-300': key === 'obsidian'
                                    }"></span>
                                    <span x-text="label"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <main class="relative z-10 flex flex-1 items-center justify-center py-4 sm:py-8">
                    <div class="w-full max-w-[35rem]">
                        <a href="/" class="auth-emblem relative z-10 mx-auto -mb-14 flex h-32 w-32 items-center justify-center rounded-full bg-black/76 p-3 backdrop-blur-md sm:-mb-16 sm:h-40 sm:w-40">
                            <img src="{{ asset('images/brand/logo-mark.webp') }}" alt="5 Star New Look Salon logo" class="h-full w-full rounded-full object-contain">
                        </a>

                        <section class="auth-card rounded-[2rem] border px-6 pb-7 pt-20 backdrop-blur-2xl sm:px-10 sm:pb-9 sm:pt-24">
                            {{ $slot }}
                        </section>
                    </div>
                </main>

                <footer class="relative z-10 flex flex-col gap-3 pb-[env(safe-area-inset-bottom)] text-xs text-white/62 sm:flex-row sm:items-center sm:justify-between">
                    <p>&copy; {{ now('Asia/Kolkata')->year }} 5 Star New Look Salon. All rights reserved.</p>
                    <p class="inline-flex items-center gap-2 text-[var(--auth-primary-strong)]">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M12 3 5 6v5c0 4.5 2.9 8.5 7 10 4.1-1.5 7-5.5 7-10V6l-7-3Z"/>
                            <path d="m9 12 2 2 4-5"/>
                        </svg>
                        Secure Access
                    </p>
                </footer>
            </div>
        </body>
    @else
        <body class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(213,169,59,0.12),_transparent_40%),#080705] font-sans text-[#fff9ea] antialiased">
            <div class="flex min-h-screen flex-col items-center justify-center px-4 py-12">
                <div class="fixed right-4 top-4"><x-theme-toggle /></div>
                <div class="mb-8 text-center">
                    <a href="/">
                        <img src="{{ asset('images/brand/logo-full.webp') }}" alt="5 Star New Look Salon logo" class="mx-auto h-36 w-auto object-contain">
                    </a>
                </div>

                <div class="w-full max-w-md overflow-hidden rounded-[32px] border border-[#c8a24a]/15 bg-[#0c0a08]/95 px-8 py-8 shadow-2xl shadow-black/40">
                    {{ $slot }}
                </div>
            </div>
        </body>
    @endif
</html>
