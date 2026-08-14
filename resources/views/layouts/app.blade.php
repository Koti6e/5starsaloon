<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', '5 Star New Look Salon') }}</title>
        <link rel="icon" href="{{ asset('favicon.ico') }}">
        <script>
            (() => {
                const storedDefaultTheme = @js(\App\Models\SalonSetting::getValue('default_theme', 'dark'));
                document.documentElement.dataset.defaultTheme = storedDefaultTheme === 'light' ? 'dark' : storedDefaultTheme;
                const theme = localStorage.getItem('salon-theme') || document.documentElement.dataset.defaultTheme || 'light';
                document.documentElement.classList.toggle('theme-dark', theme === 'dark');
                document.documentElement.classList.toggle('theme-light', theme !== 'dark');
            })();
        </script>
        <style>
            :root {
                --app-bg: #060503;
                --app-surface: #0d0b08;
                --app-surface-elevated: #11100d;
                --app-text: #fff9ea;
                --app-muted: #d8c8a3;
                --app-subtle: #a89567;
                --app-primary: #39ff88;
                --app-primary-strong: #32e875;
                --app-primary-soft: rgba(57, 255, 136, 0.12);
                --app-border: rgba(57, 255, 136, 0.18);
                --app-focus: rgba(57, 255, 136, 0.28);
                --app-glow: rgba(57, 255, 136, 0.2);
                --app-success: #34d399;
                --app-warning: #fbbf24;
                --app-danger: #f87171;
            }

            html.theme-light {
                --app-bg: #060806;
                --app-surface: #0d120f;
                --app-surface-elevated: #121914;
                --app-text: #f4fff8;
                --app-muted: #b9c7bd;
                --app-subtle: #839388;
                --app-primary: #39ff88;
                --app-primary-strong: #32e875;
                --app-primary-soft: rgba(57, 255, 136, 0.12);
                --app-border: rgba(57, 255, 136, 0.18);
                --app-focus: rgba(57, 255, 136, 0.28);
                --app-glow: rgba(57, 255, 136, 0.2);
            }

            .app-transition-mask {
                align-items: center;
                background: color-mix(in srgb, var(--app-bg) 88%, transparent);
                display: none;
                inset: 0;
                justify-content: center;
                position: fixed;
                z-index: 80;
            }

            .app-transition-mask.is-visible {
                display: flex;
            }

            .app-transition-pill {
                align-items: center;
                background: var(--app-surface);
                border: 1px solid var(--app-border);
                border-radius: 999px;
                box-shadow: 0 18px 50px rgba(0, 0, 0, 0.26), 0 0 22px var(--app-glow);
                color: var(--app-primary);
                display: inline-flex;
                gap: 0.7rem;
                max-width: calc(100vw - 2rem);
                padding: 0.7rem 1rem;
            }

            @media (prefers-reduced-motion: no-preference) {
                .app-transition-icon {
                    animation: appToolGlide 420ms ease-in-out infinite alternate;
                }
            }

            @keyframes appToolGlide {
                from { transform: translateX(-1px) rotate(-5deg); }
                to { transform: translateX(3px) rotate(5deg); }
            }
        </style>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[var(--app-bg)] font-sans text-[var(--app-text)] antialiased">
        <div
            x-data="{
                sidebarOpen: false,
                collapsed: localStorage.getItem('salonos-sidebar-collapsed') === '1',
                navigating: false,
                now: new Date(),
                init() {
                    setInterval(() => this.now = new Date(), 1000);
                    window.addEventListener('pageshow', () => this.navigating = false);
                    document.addEventListener('click', event => {
                        const link = event.target.closest('a[href]');
                        if (! link || link.target || link.hasAttribute('download') || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
                        const url = new URL(link.href, window.location.href);
                        if (url.origin !== window.location.origin || (url.hash && url.pathname === window.location.pathname)) return;
                        this.navigating = true;
                    });
                },
                toggleSidebar() {
                    this.collapsed = ! this.collapsed;
                    localStorage.setItem('salonos-sidebar-collapsed', this.collapsed ? '1' : '0');
                }
            }"
            class="min-h-screen overflow-x-hidden bg-[radial-gradient(circle_at_top,_var(--app-primary-soft),_transparent_35%),var(--app-bg)]"
        >
            @include('layouts.navigation')

            <div class="min-h-screen pb-28 pt-14 transition-all duration-200 sm:pt-16 lg:pb-0 lg:pl-72" :class="{ 'lg:pl-24': collapsed, 'lg:pl-72': ! collapsed }">
                @isset($header)
                    <x-admin.page-title>
                        {{ $header }}
                    </x-admin.page-title>
                @endisset

                <x-admin.flash />

                <main class="mx-auto max-w-7xl pb-6 pt-2 sm:pb-10 sm:pt-4 {{ request()->routeIs('*.billing.create') ? 'px-2 sm:px-3 lg:px-4' : 'px-3 sm:px-6 lg:px-8' }}">
                    <div class="{{ request()->routeIs('*.billing.create') ? 'rounded-2xl p-0 sm:p-2' : 'rounded-3xl p-3 sm:p-6 lg:rounded-[32px]' }} border border-[var(--app-border)] bg-[var(--app-surface)]/95 shadow-2xl shadow-black/30">
                        {{ $slot }}
                    </div>
                </main>
            </div>

            <div class="app-transition-mask" :class="{ 'is-visible': navigating }" role="status" aria-live="polite">
                <div class="app-transition-pill">
                    <svg class="app-transition-icon h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M4 8h12"/>
                        <path d="M4 12h14"/>
                        <path d="M4 16h10"/>
                        <path d="M18.5 7.5 21 10l-2.5 2.5"/>
                    </svg>
                    <span class="text-xs font-bold uppercase tracking-[0.16em]">Opening screen</span>
                </div>
            </div>
        </div>
    </body>
</html>
