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
                const validThemes = ['emerald', 'sapphire', 'crimson', 'gold', 'pearl', 'obsidian'];
                const storedDefaultTheme = @js(\App\Models\SalonSetting::getValue('default_theme', 'emerald'));
                const normalizedDefault = storedDefaultTheme === 'light' || storedDefaultTheme === 'dark' ? 'emerald' : storedDefaultTheme;
                const savedTheme = localStorage.getItem('salon-theme');
                const normalizedSaved = savedTheme === 'light' || savedTheme === 'dark' ? null : savedTheme;
                const theme = validThemes.includes(normalizedSaved) ? normalizedSaved : (validThemes.includes(normalizedDefault) ? normalizedDefault : 'emerald');
                document.documentElement.dataset.defaultTheme = normalizedDefault;
                document.documentElement.dataset.theme = theme;
                document.documentElement.classList.toggle('theme-dark', theme !== 'pearl');
                document.documentElement.classList.toggle('theme-light', theme === 'pearl');
            })();
        </script>
        <style>
            :root {
                --bg-app: #070807;
                --bg-surface: #101211;
                --bg-surface-elevated: #171a18;
                --bg-input: #0b0d0c;
                --text-primary: #f4f7f5;
                --text-secondary: #c6cec9;
                --text-muted: #8f9a94;
                --text-disabled: #5e6762;
                --border-default: rgba(226, 232, 240, 0.11);
                --border-strong: rgba(226, 232, 240, 0.2);
                --shadow-card: 0 18px 50px rgba(0, 0, 0, 0.28);
                --success: #34d399;
                --success-soft: rgba(52, 211, 153, 0.13);
                --danger: #fb7185;
                --danger-soft: rgba(251, 113, 133, 0.13);
                --warning: #fbbf24;
                --warning-soft: rgba(251, 191, 36, 0.13);
                --info: #38bdf8;
                --info-soft: rgba(56, 189, 248, 0.13);
                --accent: #47e88a;
                --accent-hover: #59f39a;
                --accent-active: #2fd875;
                --accent-soft: rgba(71, 232, 138, 0.1);
                --accent-border: rgba(71, 232, 138, 0.28);
                --accent-glow: rgba(71, 232, 138, 0.14);
                --focus-ring: rgba(71, 232, 138, 0.24);
                --on-accent: #031008;
                --app-bg: var(--bg-app);
                --app-surface: var(--bg-surface);
                --app-surface-elevated: var(--bg-surface-elevated);
                --app-text: var(--text-primary);
                --app-muted: var(--text-secondary);
                --app-subtle: var(--text-muted);
                --app-primary: var(--accent);
                --app-primary-strong: var(--accent-active);
                --app-primary-soft: var(--accent-soft);
                --app-border: var(--border-default);
                --app-focus: var(--focus-ring);
                --app-glow: var(--accent-glow);
                --app-success: var(--success);
                --app-warning: var(--warning);
                --app-danger: var(--danger);
            }

            html[data-theme="sapphire"] {
                --accent: #60a5fa;
                --accent-hover: #7dd3fc;
                --accent-active: #3b82f6;
                --accent-soft: rgba(96, 165, 250, 0.1);
                --accent-border: rgba(96, 165, 250, 0.28);
                --accent-glow: rgba(96, 165, 250, 0.14);
                --focus-ring: rgba(96, 165, 250, 0.24);
            }

            html[data-theme="crimson"] {
                --accent: #f47286;
                --accent-hover: #fb8ea0;
                --accent-active: #e84d68;
                --accent-soft: rgba(244, 114, 134, 0.1);
                --accent-border: rgba(244, 114, 134, 0.28);
                --accent-glow: rgba(244, 114, 134, 0.14);
                --focus-ring: rgba(244, 114, 134, 0.24);
            }

            html[data-theme="gold"] {
                --accent: #e9c46a;
                --accent-hover: #f2d889;
                --accent-active: #d6aa3a;
                --accent-soft: rgba(233, 196, 106, 0.11);
                --accent-border: rgba(233, 196, 106, 0.3);
                --accent-glow: rgba(233, 196, 106, 0.14);
                --focus-ring: rgba(233, 196, 106, 0.24);
            }

            html[data-theme="pearl"] {
                --bg-app: #f4f1eb;
                --bg-surface: #fffdf8;
                --bg-surface-elevated: #ffffff;
                --bg-input: #f8f5ef;
                --text-primary: #1e2420;
                --text-secondary: #4b5750;
                --text-muted: #727d76;
                --text-disabled: #9aa39d;
                --border-default: rgba(30, 36, 32, 0.12);
                --border-strong: rgba(30, 36, 32, 0.22);
                --shadow-card: 0 18px 42px rgba(32, 27, 18, 0.1);
                --accent: #167a4d;
                --accent-hover: #1f9360;
                --accent-active: #11683f;
                --accent-soft: rgba(22, 122, 77, 0.1);
                --accent-border: rgba(22, 122, 77, 0.3);
                --accent-glow: rgba(22, 122, 77, 0.12);
                --focus-ring: rgba(22, 122, 77, 0.22);
                --on-accent: #ffffff;
            }

            html[data-theme="obsidian"] {
                --bg-app: #050506;
                --bg-surface: #0d0e10;
                --bg-surface-elevated: #15171a;
                --bg-input: #090a0c;
                --accent: #cbd5e1;
                --accent-hover: #e2e8f0;
                --accent-active: #94a3b8;
                --accent-soft: rgba(203, 213, 225, 0.09);
                --accent-border: rgba(203, 213, 225, 0.24);
                --accent-glow: rgba(203, 213, 225, 0.08);
                --focus-ring: rgba(203, 213, 225, 0.2);
            }

            :root,
            html[data-theme] {
                --app-bg: var(--bg-app);
                --app-surface: var(--bg-surface);
                --app-surface-elevated: var(--bg-surface-elevated);
                --app-text: var(--text-primary);
                --app-muted: var(--text-secondary);
                --app-subtle: var(--text-muted);
                --app-primary: var(--accent);
                --app-primary-strong: var(--accent-active);
                --app-primary-soft: var(--accent-soft);
                --app-border: var(--border-default);
                --app-focus: var(--focus-ring);
                --app-glow: var(--accent-glow);
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
                box-shadow: var(--shadow-card), 0 0 16px var(--app-glow);
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
            class="min-h-screen overflow-x-hidden bg-[var(--app-bg)]"
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
                    <div class="{{ request()->routeIs('*.billing.create') ? 'rounded-2xl p-0 sm:p-2' : 'rounded-3xl p-3 sm:p-6 lg:rounded-[32px]' }} border border-[var(--app-border)] bg-[var(--app-surface)]/95 shadow-[var(--shadow-card)]">
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
