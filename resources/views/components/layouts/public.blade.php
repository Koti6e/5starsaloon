@props(['settings' => []])

@php
    $searchServices = \App\Models\Service::query()
        ->with('category')
        ->publiclyVisible()
        ->orderBy('display_order')
        ->orderBy('name')
        ->get()
        ->map(fn ($service) => [
            'name' => $service->name,
            'category' => $service->publicCategoryName(),
            'description' => $service->short_description,
            'price' => $service->displayPrice(),
            'image' => asset($service->coverImageUrl()),
            'url' => route('services.show', $service),
            'book_url' => route('appointments.book', ['service' => $service->slug]),
            'button' => $service->publicBookingLabel(),
            'haystack' => Str::lower($service->name.' '.$service->publicCategoryName().' '.$service->short_description),
        ]);
    
    $themeNames = [
        'emerald' => 'Neon Emerald',
        'sapphire' => 'Neon Sapphire',
        'crimson' => 'Neon Crimson',
        'gold' => 'Neon Gold',
        'pearl' => 'Neon Pearl',
        'obsidian' => 'Neon Obsidian',
    ];

    // Set default values with fallbacks. Legacy light/dark settings map into the public theme system.
    $storedTheme = $settings['default_theme'] ?? 'emerald';
    $defaultTheme = match ($storedTheme) {
        'light' => 'pearl',
        'dark' => 'obsidian',
        default => array_key_exists($storedTheme, $themeNames) ? $storedTheme : 'emerald',
    };
    $salonName = $settings['salon_name'] ?? '5 Star New Look Salon';
    $tagline = $settings['tagline'] ?? 'Look Good. Feel Great. Be Confident.';
    $metaDescription = $description ?? ($settings['meta_description'] ?? 'Premium salon, spa, hair and grooming services in Chengalpattu by 5 Star New Look Salon.');
    $phoneDigits = preg_replace('/\D+/', '', (string) ($settings['primary_phone'] ?? $settings['whatsapp_number'] ?? '9003866903'));
    $phoneHref = strlen($phoneDigits) >= 10 ? 'tel:+91'.substr($phoneDigits, -10) : route('contact');
    $whatsappDigits = preg_replace('/\D+/', '', (string) ($settings['whatsapp_number'] ?? '9003866903'));
    $whatsappEnabled = ! in_array(strtolower((string) ($settings['whatsapp_floater_enabled'] ?? '1')), ['0', 'false', 'off'], true);
    $whatsappHref = $whatsappEnabled
        ? 'https://wa.me/'.(strlen($whatsappDigits) === 10 ? '91'.$whatsappDigits : $whatsappDigits).'?text='.rawurlencode($settings['whatsapp_default_message'] ?? 'Hi 5 Star New Look Salon, I would like to know more about your services.')
        : route('contact');
    
    $navLinks = [
        ['label' => 'Home', 'route' => 'home'],
        ['label' => 'Services', 'route' => 'services.index'],
        ['label' => 'Elite Home Service', 'route' => 'appointments.book'],
        ['label' => 'Gallery', 'route' => 'gallery'],
        ['label' => 'About', 'route' => 'about'],
        ['label' => 'Contact', 'route' => 'contact'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $defaultTheme }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? $salonName }}</title>
        <meta name="description" content="{{ $metaDescription }}">
        <link rel="canonical" href="{{ url()->current() }}">
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $title ?? $salonName }}">
        <meta property="og:description" content="{{ $metaDescription }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ asset($settings['og_image'] ?? 'images/salon/premium-salon-hero.webp') }}">
        <link rel="icon" href="{{ asset('favicon.ico') }}">
        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
        <meta name="theme-color" content="#d5a93b">
        
        <script>
            // Initialize theme before page render to prevent flash
            (function() {
                const savedTheme = localStorage.getItem('salon-color-theme');
                const defaultTheme = @json($defaultTheme);
                const theme = savedTheme || defaultTheme;
                document.documentElement.dataset.defaultTheme = defaultTheme;
                document.documentElement.setAttribute('data-theme', theme);
            })();
        </script>
        
        <style>
            :root,
            [data-theme="emerald"] {
                --bg-main: #07120d;
                --bg-secondary: #0b1b14;
                --bg-card: #10231a;
                --bg-input: #0d1f17;
                --text-main: #f2fff8;
                --text-secondary: #c7f0da;
                --text-muted: #85aa97;
                --accent: #19c987;
                --accent-light: #7af5bd;
                --accent-dark: #0f9f6d;
                --border-clr: rgba(122, 245, 189, 0.2);
                --border-hover: rgba(122, 245, 189, 0.38);
                --header-bg: rgba(7, 18, 13, 0.92);
                --footer-bg: #08140f;
                --shadow: rgba(25, 201, 135, 0.16);
                --glow: rgba(25, 201, 135, 0.28);
                --overlay: rgba(7, 18, 13, 0.86);
            }

            [data-theme="sapphire"] {
                --bg-main: #07101d;
                --bg-secondary: #0b1728;
                --bg-card: #101f33;
                --bg-input: #0d1b2d;
                --text-main: #f3f8ff;
                --text-secondary: #c8dcf8;
                --text-muted: #879bb8;
                --accent: #47a3ff;
                --accent-light: #93cfff;
                --accent-dark: #237bd5;
                --border-clr: rgba(147, 207, 255, 0.2);
                --border-hover: rgba(147, 207, 255, 0.38);
                --header-bg: rgba(7, 16, 29, 0.92);
                --footer-bg: #080f1b;
                --shadow: rgba(71, 163, 255, 0.15);
                --glow: rgba(71, 163, 255, 0.25);
                --overlay: rgba(7, 16, 29, 0.86);
            }

            [data-theme="crimson"] {
                --bg-main: #17080d;
                --bg-secondary: #210c13;
                --bg-card: #2b121a;
                --bg-input: #251018;
                --text-main: #fff5f7;
                --text-secondary: #ffd0da;
                --text-muted: #b88994;
                --accent: #ff4f7b;
                --accent-light: #ff9ab2;
                --accent-dark: #cf2e58;
                --border-clr: rgba(255, 154, 178, 0.2);
                --border-hover: rgba(255, 154, 178, 0.38);
                --header-bg: rgba(23, 8, 13, 0.92);
                --footer-bg: #14070c;
                --shadow: rgba(255, 79, 123, 0.14);
                --glow: rgba(255, 79, 123, 0.24);
                --overlay: rgba(23, 8, 13, 0.86);
            }

            [data-theme="gold"] {
                --bg-main: #120f08;
                --bg-secondary: #1d170c;
                --bg-card: #261f12;
                --bg-input: #21190e;
                --text-main: #fff9ea;
                --text-secondary: #eadfca;
                --text-muted: #bba980;
                --accent: #d5a93b;
                --accent-light: #f4d27a;
                --accent-dark: #b88a20;
                --border-clr: rgba(244, 210, 122, 0.2);
                --border-hover: rgba(244, 210, 122, 0.38);
                --header-bg: rgba(18, 15, 8, 0.92);
                --footer-bg: #100d07;
                --shadow: rgba(213, 169, 59, 0.14);
                --glow: rgba(213, 169, 59, 0.24);
                --overlay: rgba(18, 15, 8, 0.86);
            }

            [data-theme="pearl"] {
                --bg-main: #fbf7ef;
                --bg-secondary: #f0eadf;
                --bg-card: #ffffff;
                --bg-input: #f7f2ea;
                --text-main: #1f1a14;
                --text-secondary: #53483b;
                --text-muted: #807263;
                --accent: #0aa574;
                --accent-light: #087c58;
                --accent-dark: #07704f;
                --border-clr: rgba(10, 165, 116, 0.22);
                --border-hover: rgba(10, 165, 116, 0.4);
                --header-bg: rgba(251, 247, 239, 0.94);
                --footer-bg: #f0eadf;
                --shadow: rgba(10, 165, 116, 0.12);
                --glow: rgba(10, 165, 116, 0.2);
                --overlay: rgba(255, 255, 255, 0.9);
            }

            [data-theme="obsidian"] {
                --bg-main: #0f0f11;
                --bg-secondary: #161619;
                --bg-card: #1a1a1e;
                --bg-input: #1e1e22;
                --text-main: #f3f4f6;
                --text-secondary: #d1d5db;
                --text-muted: #9ca3af;
                --accent: #e5e7eb;
                --accent-light: #ffffff;
                --accent-dark: #d1d5db;
                --border-clr: rgba(229, 231, 235, 0.15);
                --border-hover: rgba(229, 231, 235, 0.3);
                --header-bg: rgba(15, 15, 17, 0.95);
                --footer-bg: #161619;
                --shadow: rgba(229, 231, 235, 0.1);
                --glow: rgba(229, 231, 235, 0.15);
                --overlay: rgba(0, 0, 0, 0.8);
            }

            /* ============================================
               GLOBAL STYLES
               ============================================ */
            * {
                transition: background-color 0.3s ease, 
                            color 0.3s ease, 
                            border-color 0.3s ease, 
                            box-shadow 0.3s ease,
                            background 0.3s ease;
            }

            body {
                font-family: 'Instrument Sans', -apple-system, BlinkMacSystemFont, sans-serif;
                background-color: var(--bg-main);
                color: var(--text-main);
                min-height: 100vh;
                overflow-x: hidden;
                transition: background-color 0.4s ease, color 0.4s ease;
            }

            /* Theme-aware utilities */
            .theme-bg { background-color: var(--bg-main); }
            .theme-bg-secondary { background-color: var(--bg-secondary); }
            .theme-bg-card { background-color: var(--bg-card); }
            .theme-text { color: var(--text-main); }
            .theme-text-secondary { color: var(--text-secondary); }
            .theme-text-muted { color: var(--text-muted); }
            .theme-border { border-color: var(--border-clr); }
            .theme-accent { color: var(--accent); }
            .theme-accent-light { color: var(--accent-light); }

            [data-theme] [class*="text-[#f4d27a]"],
            [data-theme] [class*="text-[#c8a24a]"] {
                color: var(--accent-light) !important;
            }

            [data-theme] [class*="text-[#d8c8a3]"],
            [data-theme] [class*="text-[#a89567]"] {
                color: var(--text-muted) !important;
            }

            [data-theme] [class*="text-[#fff9ea]"],
            [data-theme] [class*="text-[#f8efd8]"] {
                color: var(--text-main) !important;
            }

            [data-hero] [class*="text-[#fff9ea]"],
            [data-hero] [class*="text-[#f8efd8]"],
            [data-hero] [class*="text-[#eadfca]"] {
                color: #fff9ea !important;
            }

            [data-theme] [class*="bg-[#d5a93b]"] {
                background-color: var(--accent) !important;
            }

            [data-theme] [class*="bg-[#11100d]"],
            [data-theme] [class*="bg-[#0d0b08]"],
            [data-theme] [class*="bg-[#0c0a08]"] {
                background-color: var(--bg-card) !important;
            }

            [data-theme] [class*="bg-black"],
            [data-theme] [class*="bg-[#090806]"] {
                background-color: var(--bg-secondary) !important;
            }

            [data-theme] [class*="border-[#c8a24a]"],
            [data-theme] [class*="border-[#f4d27a]"] {
                border-color: var(--border-clr) !important;
            }

            [data-theme] input,
            [data-theme] select,
            [data-theme] textarea {
                background-color: var(--bg-input);
                border-color: var(--border-clr);
                color: var(--text-main);
            }

            [data-theme] input:focus,
            [data-theme] select:focus,
            [data-theme] textarea:focus,
            [data-theme] button:focus-visible,
            [data-theme] a:focus-visible {
                outline: none;
                box-shadow: 0 0 0 3px var(--glow);
            }

            /* Cards */
            .card-glass {
                background: var(--bg-card);
                border: 1px solid var(--border-clr);
                border-radius: 1rem;
                transition: all 0.3s ease;
            }

            .card-glass:hover {
                border-color: var(--border-hover);
                box-shadow: 0 10px 40px var(--shadow);
                transform: translateY(-2px);
            }

            /* Buttons */
            .btn-primary {
                background: var(--accent);
                color: var(--bg-main);
                transition: all 0.3s ease;
                font-weight: 600;
            }

            .btn-primary:hover {
                opacity: 0.9;
                box-shadow: 0 0 30px var(--glow);
                transform: translateY(-1px);
            }

            .btn-secondary {
                border: 1px solid var(--border-clr);
                color: var(--text-main);
                transition: all 0.3s ease;
            }

            .btn-secondary:hover {
                background: var(--bg-secondary);
                border-color: var(--border-hover);
            }

            /* Inputs */
            .input-theme {
                background: var(--bg-input);
                border: 1px solid var(--border-clr);
                color: var(--text-main);
                transition: all 0.3s ease;
            }

            .input-theme:focus {
                border-color: var(--accent);
                box-shadow: 0 0 0 3px var(--glow);
                outline: none;
            }

            .input-theme::placeholder {
                color: var(--text-muted);
            }

            /* Header */
            .header-theme {
                background: var(--header-bg);
                border-color: var(--border-clr);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
            }

            /* Footer */
            .footer-theme {
                background: var(--footer-bg);
                border-color: var(--border-clr);
                color: var(--text-secondary);
            }

            /* Links */
            .link-theme {
                color: var(--text-secondary);
                transition: color 0.3s ease;
            }

            .link-theme:hover {
                color: var(--accent);
            }

            /* Scrollbar */
            ::-webkit-scrollbar {
                width: 8px;
            }
            ::-webkit-scrollbar-track {
                background: var(--bg-secondary);
            }
            ::-webkit-scrollbar-thumb {
                background: var(--accent);
                border-radius: 4px;
            }
            ::-webkit-scrollbar-thumb:hover {
                background: var(--accent-dark);
            }

            /* Selection */
            ::selection {
                background: var(--accent);
                color: #fff;
            }

            /* Theme transition animation */
            @keyframes themeFadeIn {
                from { opacity: 0.8; }
                to { opacity: 1; }
            }

            .theme-transition {
                animation: themeFadeIn 0.4s ease;
            }

            /* Smooth theme switch */
            .theme-switch {
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .salon-page-transition {
                align-items: center;
                display: none;
                inset: 0;
                justify-content: center;
                position: fixed;
                background: color-mix(in srgb, var(--bg-main) 86%, transparent);
                z-index: 70;
            }

            .salon-page-transition.is-visible {
                display: flex;
            }

            .salon-page-loader {
                align-items: center;
                background: var(--header-bg);
                border: 1px solid var(--border-clr);
                border-radius: 999px;
                box-shadow: 0 18px 50px rgba(0, 0, 0, 0.22), 0 0 22px var(--glow);
                color: var(--accent-light);
                display: inline-flex;
                gap: 0.75rem;
                max-width: calc(100vw - 2rem);
                padding: 0.7rem 1rem;
            }

            .salon-loader-mark {
                color: var(--accent);
                height: 1.25rem;
                width: 1.25rem;
            }

            @media (prefers-reduced-motion: no-preference) {
                .salon-loader-mark {
                    animation: salonToolGlide 420ms ease-in-out infinite alternate;
                }
            }

            @keyframes salonToolGlide {
                from { transform: translateX(-1px) rotate(-5deg); }
                to { transform: translateX(3px) rotate(5deg); }
            }

            /* Responsive */
            @media (max-width: 640px) {
                .container-padding {
                    padding-left: 1rem;
                    padding-right: 1rem;
                }

            }
        </style>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|playfair-display:600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="antialiased theme-transition">
        <div
            x-data="{
                open: false,
                searchOpen: false,
                query: '',
                services: @js($searchServices),
                currentTheme: localStorage.getItem('salon-color-theme') || @js($defaultTheme),
                themeNames: @js($themeNames),
                themeDropdown: false,
                mobileThemeMenu: false,
                
                init() {
                    // Apply saved theme on load
                    document.documentElement.setAttribute('data-theme', this.currentTheme);
                },
                
                setTheme(themeName) {
                    this.currentTheme = themeName;
                    localStorage.setItem('salon-color-theme', themeName);
                    document.documentElement.setAttribute('data-theme', themeName);
                    this.themeDropdown = false;
                    this.mobileThemeMenu = false;
                },
                
                get results() {
                    const q = this.query.trim().toLowerCase();
                    if (!q) return [];
                    return this.services.filter(service => service.haystack.includes(q)).slice(0, 6);
                }
            }"
            x-init="init()"
            x-effect="document.body.classList.toggle('overflow-hidden', open || searchOpen)"
            @click.away="themeDropdown = false; mobileThemeMenu = false"
        >
            <!-- ========================================== -->
            <!-- HEADER                                     -->
            <!-- ========================================== -->
            <header class="sticky top-0 z-50 border-b header-theme shadow-md">
                <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3.5 sm:px-6 lg:px-8">
                    
                    <!-- Brand Logo -->
                    <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-3 group" aria-label="{{ $salonName }} home">
                        <img src="{{ asset('images/brand/logo-small.webp') }}" alt="{{ $salonName }} logo" 
                             class="h-11 w-11 rounded-2xl object-contain ring-1 transition group-hover:ring-2" 
                             style="ring-color: var(--accent);">
                        <div class="hidden sm:block">
                            <p class="font-serif text-base font-semibold tracking-wide" style="color: var(--accent-light);">
                                {{ $salonName }}
                            </p>
                            <p class="text-[10px] uppercase tracking-[0.2em] theme-text-muted">
                                Premium Salon Management
                            </p>
                        </div>
                    </a>

                    <!-- Desktop Navigation -->
                    <nav class="hidden items-center gap-1 xl:gap-2 text-sm font-medium lg:flex" aria-label="Primary navigation">
                        @foreach ($navLinks as $link)
                            <a href="{{ route($link['route']) }}" 
                               class="rounded-full px-4 py-2.5 text-[15px] font-semibold tracking-wide transition-all duration-200 hover:bg-white/10 hover:shadow-lg link-theme">
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                    </nav>

                    <!-- Desktop Right Section -->
                    <div class="hidden shrink-0 lg:flex lg:items-center lg:gap-3">
                        <a href="{{ $phoneHref }}" class="hidden rounded-full border px-4 py-2 text-xs font-semibold transition hover:opacity-80 xl:inline-flex" style="border-color: var(--border-clr); color: var(--text-secondary);">
                            Call {{ strlen($phoneDigits) >= 10 ? substr($phoneDigits, -10) : 'Salon' }}
                        </a>

                        <!-- Theme Selector -->
                        <div class="relative">
                            <button 
                                @click="themeDropdown = !themeDropdown" 
                                type="button" 
                                class="flex items-center gap-2 rounded-full border px-4 py-2 text-xs font-semibold transition hover:opacity-80"
                                style="border-color: var(--border-clr); background: rgba(255,255,255,0.05); color: var(--accent-light);"
                            >
                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: var(--accent);"></span>
                                <span class="capitalize" x-text="themeNames[currentTheme] || currentTheme"></span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            
                            <div x-show="themeDropdown" 
                                 x-transition 
                                 class="absolute right-0 top-full mt-2 w-52 rounded-2xl border p-2 shadow-2xl backdrop-blur-2xl"
                                 style="background: var(--bg-card); border-color: var(--border-clr);"
                                 @click.away="themeDropdown = false">
                                <div class="space-y-1 text-sm">
                                    <button @click="setTheme('emerald')" class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 text-left transition hover:bg-white/10">
                                        <span class="h-3 w-3 rounded-full bg-emerald-500"></span>
                                        <span class="font-medium" style="color: var(--text-main);">Neon Emerald</span>
                                    </button>
                                    <button @click="setTheme('sapphire')" class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 text-left transition hover:bg-white/10">
                                        <span class="h-3 w-3 rounded-full bg-blue-500"></span>
                                        <span class="font-medium" style="color: var(--text-main);">Neon Sapphire</span>
                                    </button>
                                    <button @click="setTheme('crimson')" class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 text-left transition hover:bg-white/10">
                                        <span class="h-3 w-3 rounded-full bg-rose-500"></span>
                                        <span class="font-medium" style="color: var(--text-main);">Neon Crimson</span>
                                    </button>
                                    <button @click="setTheme('gold')" class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 text-left transition hover:bg-white/10">
                                        <span class="h-3 w-3 rounded-full bg-amber-400"></span>
                                        <span class="font-medium" style="color: var(--text-main);">Neon Gold</span>
                                    </button>
                                    <button @click="setTheme('pearl')" class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 text-left transition hover:bg-white/10">
                                        <span class="h-3 w-3 rounded-full bg-stone-100 ring-1 ring-stone-300"></span>
                                        <span class="font-medium" style="color: var(--text-main);">Neon Pearl</span>
                                    </button>
                                    <button @click="setTheme('obsidian')" aria-label="Switch to dark mode with Neon Obsidian" class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 text-left transition hover:bg-white/10">
                                        <span class="h-3 w-3 rounded-full bg-gray-400"></span>
                                        <span class="font-medium" style="color: var(--text-main);">Neon Obsidian</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Search -->
                        <div class="relative w-[200px] xl:w-[240px]">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 theme-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input
                                type="search"
                                x-model.debounce.100ms="query"
                                placeholder="Search services..."
                                class="w-full rounded-full border px-9 py-2 pr-16 text-sm input-theme"
                            >
                            <a
                                :href="query.trim() ? '{{ route('services.index') }}?search=' + encodeURIComponent(query.trim()) : '{{ route('services.index') }}'"
                                class="absolute right-1 top-1/2 -translate-y-1/2 rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-wide transition btn-primary"
                                :class="query.trim() ? 'opacity-100' : 'opacity-50 pointer-events-none'"
                            >
                                Go
                            </a>
                        </div>
                        
                        <!-- Book Button -->
                        <a href="{{ route('appointments.book') }}" 
                           class="inline-flex items-center justify-center rounded-full px-5 py-2.5 text-xs font-bold uppercase tracking-wider shadow-lg transition hover:scale-105 btn-primary">
                            Book Appointment
                        </a>
                    </div>

                    <!-- Mobile Toggles -->
                    <div class="flex items-center gap-2 lg:hidden">
                        <button type="button" 
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full border transition hover:opacity-80"
                                style="border-color: var(--border-clr); color: var(--accent-light);"
                                @click="searchOpen = !searchOpen; if (searchOpen) open = false">
                            <span class="sr-only">Open search</span>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </button>
                        
                        <!-- Mobile Theme -->
                        <div class="relative">
                            <button @click="mobileThemeMenu = !mobileThemeMenu" 
                                    type="button" 
                                    class="flex h-10 w-10 items-center justify-center rounded-full border transition hover:opacity-80"
                                    style="border-color: var(--border-clr); color: var(--accent-light);">
                                <span class="h-3 w-3 rounded-full" style="background-color: var(--accent);"></span>
                            </button>
                            <div x-show="mobileThemeMenu" 
                                 x-transition 
                                 class="absolute right-0 top-full mt-2 w-44 rounded-2xl border p-2 shadow-xl backdrop-blur-lg"
                                 style="background: var(--bg-card); border-color: var(--border-clr);">
                                <div class="space-y-1 text-xs">
                                    <button @click="setTheme('emerald')" class="w-full rounded-xl px-3 py-2 text-left font-medium hover:bg-white/10 text-emerald-400">Emerald</button>
                                    <button @click="setTheme('sapphire')" class="w-full rounded-xl px-3 py-2 text-left font-medium hover:bg-white/10 text-blue-400">Sapphire</button>
                                    <button @click="setTheme('crimson')" class="w-full rounded-xl px-3 py-2 text-left font-medium hover:bg-white/10 text-rose-400">Crimson</button>
                                    <button @click="setTheme('gold')" class="w-full rounded-xl px-3 py-2 text-left font-medium hover:bg-white/10 text-amber-400">Gold</button>
                                    <button @click="setTheme('pearl')" class="w-full rounded-xl px-3 py-2 text-left font-medium hover:bg-white/10 text-stone-300">Pearl</button>
                                    <button @click="setTheme('obsidian')" aria-label="Switch to dark mode with Neon Obsidian" class="w-full rounded-xl px-3 py-2 text-left font-medium hover:bg-white/10 text-gray-200">Obsidian</button>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" 
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full border transition hover:opacity-80"
                                style="border-color: var(--border-clr); color: var(--accent-light);"
                                @click="open = !open; if (open) searchOpen = false">
                            <span class="sr-only">Open navigation</span>
                            <span x-show="!open" aria-hidden="true" class="text-xl leading-none">☰</span>
                            <span x-show="open" aria-hidden="true" class="text-xl leading-none">×</span>
                        </button>
                    </div>

                </div>

                <!-- Desktop Search Results -->
                <div x-show="query.trim()" 
                     x-transition 
                     class="absolute right-6 top-full mt-2 hidden w-96 rounded-3xl border p-3 shadow-2xl backdrop-blur-2xl lg:block"
                     style="background: var(--bg-card); border-color: var(--border-clr);">
                    <template x-if="results.length">
                        <div class="space-y-1">
                            <template x-for="result in results" :key="result.url">
                                <a :href="result.url" class="flex items-center gap-3 rounded-2xl px-3 py-2.5 transition hover:bg-white/10">
                                    <img :src="result.image" :alt="result.name" class="h-10 w-10 rounded-xl object-cover">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold" style="color: var(--text-main);" x-text="result.name"></p>
                                        <p class="truncate text-[10px] uppercase tracking-wider theme-text-muted" x-text="result.category"></p>
                                    </div>
                                    <span class="text-xs font-medium" style="color: var(--accent);" x-text="result.price"></span>
                                </a>
                            </template>
                        </div>
                    </template>
                    <template x-if="!results.length">
                        <div class="px-4 py-3 text-center text-sm theme-text-muted">No matching services found.</div>
                    </template>
                </div>

                <!-- Mobile Menu -->
                <div id="mobile-menu" 
                     x-show="open" 
                     x-transition 
                     class="border-t lg:hidden"
                     style="background: var(--bg-card); border-color: var(--border-clr);">
                    <nav class="mx-auto max-w-7xl px-4 py-4 space-y-2" aria-label="Mobile navigation">
                        @foreach ($navLinks as $link)
                            <a href="{{ route($link['route']) }}" 
                               @click="open = false" 
                               class="block rounded-2xl border px-4 py-3.5 text-base font-semibold transition hover:bg-white/10"
                               style="border-color: var(--border-clr); color: var(--text-main);">
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                        <a href="{{ route('appointments.book') }}" 
                           @click="open = false" 
                           class="block rounded-2xl px-4 py-3.5 text-center text-base font-bold shadow-lg btn-primary">
                            Book Appointment
                        </a>
                        <div class="grid grid-cols-2 gap-2 pt-2">
                            <a href="{{ $phoneHref }}" class="rounded-2xl border px-4 py-3 text-center text-sm font-semibold" style="border-color: var(--border-clr); color: var(--text-main);">Call Salon</a>
                            <a href="{{ $whatsappHref }}" class="rounded-2xl border px-4 py-3 text-center text-sm font-semibold" style="border-color: var(--border-clr); color: var(--text-main);">WhatsApp</a>
                        </div>
                    </nav>
                </div>

                <!-- Mobile Search -->
                <div id="mobile-search-panel" 
                     x-show="searchOpen" 
                     x-transition 
                     class="border-t px-4 py-4 lg:hidden"
                     style="background: var(--bg-card); border-color: var(--border-clr);">
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <p class="text-xs font-bold uppercase tracking-wider" style="color: var(--accent-light);">Search services</p>
                        <button type="button" 
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full border transition"
                                style="border-color: var(--border-clr); color: var(--text-main);"
                                @click="searchOpen = false">
                            <span class="sr-only">Close search</span>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="flex items-center gap-2 rounded-full border px-3 py-1.5 input-theme"
                         style="border-color: var(--border-clr);">
                        <svg class="h-4 w-4 theme-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input
                            type="search"
                            x-model.debounce.100ms="query"
                            placeholder="Type to search..."
                            class="min-w-0 flex-1 border-0 bg-transparent px-2 py-2 text-sm focus:outline-none focus:ring-0"
                            style="color: var(--text-main);"
                        >
                        <a
                            :href="query.trim() ? '{{ route('services.index') }}?search=' + encodeURIComponent(query.trim()) : '{{ route('services.index') }}'"
                            class="inline-flex items-center rounded-full px-3 py-1.5 text-xs font-bold transition btn-primary"
                            :class="query.trim() ? 'opacity-100' : 'opacity-50 pointer-events-none'"
                        >
                            Search
                        </a>
                    </div>
                    
                    <div x-show="query.trim()" 
                         x-transition 
                         class="mt-3 rounded-2xl border p-3 shadow-xl"
                         style="background: var(--bg-card); border-color: var(--border-clr);">
                        <template x-if="results.length">
                            <div class="space-y-2">
                                <template x-for="result in results" :key="result.url">
                                    <a :href="result.url" class="flex items-center gap-3 rounded-xl px-2 py-2 transition hover:bg-white/10">
                                        <img :src="result.image" :alt="result.name" class="h-10 w-10 rounded-lg object-cover">
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-semibold" style="color: var(--text-main);" x-text="result.name"></p>
                                            <p class="truncate text-xs uppercase theme-text-muted" x-text="result.category"></p>
                                        </div>
                                    </a>
                                </template>
                            </div>
                        </template>
                        <template x-if="!results.length">
                            <div class="text-sm text-center py-2 theme-text-muted">No matching services found.</div>
                        </template>
                    </div>
                </div>
            </header>

            <!-- Promo Banner -->
            <x-todays-promotion :settings="$settings" />

            <!-- Main Content -->
            <main class="theme-switch" style="background: var(--bg-main);">
                {{ $slot }}
            </main>

            <!-- ========================================== -->
            <!-- FOOTER                                     -->
            <!-- ========================================== -->
            <footer class="border-t footer-theme pb-24 md:pb-12">
                <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:px-6 lg:grid-cols-[1.4fr_1fr_1fr] lg:px-8">
                    <div class="space-y-4">
                        <img src="{{ asset('images/brand/logo-full.webp') }}" alt="{{ $salonName }} full logo" class="h-24 w-auto object-contain">
                        <p class="max-w-md text-sm leading-6 theme-text-secondary">{{ $tagline }}</p>
                        <a href="{{ route('appointments.book') }}" 
                           class="inline-flex items-center justify-center rounded-full px-5 py-3 text-sm font-semibold shadow-lg transition btn-primary">
                            Book a premium service
                        </a>
                    </div>
                    <div>
                        <h2 class="font-serif text-lg" style="color: var(--accent-light);">Quick Links</h2>
                        <div class="mt-4 grid gap-1.5 text-sm">
                            @foreach ($navLinks as $link)
                                <a href="{{ route($link['route']) }}" 
                                   class="rounded-2xl px-3 py-1.5 transition hover:bg-white/10 link-theme">
                                    {{ $link['label'] }}
                                </a>
                            @endforeach
                            <a href="{{ route('login') }}" 
                               class="rounded-2xl px-3 py-1.5 transition hover:bg-white/10 link-theme">
                                Staff Login
                            </a>
                        </div>
                    </div>
                    <div>
                        <h2 class="font-serif text-lg" style="color: var(--accent-light);">Contact</h2>
                        <dl class="mt-4 space-y-3 text-sm theme-text-secondary">
                            <div>
                                <dt class="font-semibold theme-accent">Phone</dt>
                                <dd>{{ $settings['primary_phone'] ?? 'Available from salon reception' }}</dd>
                            </div>
                            <div>
                                <dt class="font-semibold theme-accent">Location</dt>
                                <dd>{{ $settings['address'] ?? 'Chengalpattu' }}</dd>
                            </div>
                            <div>
                                <dt class="font-semibold theme-accent">Hours</dt>
                                <dd>{{ $settings['working_hours'] ?? 'Open daily by appointment' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
                <div class="border-t px-4 py-5 text-center text-xs theme-text-muted sm:px-6 lg:px-8" style="border-color: var(--border-clr);">
                    <p>&copy; {{ now('Asia/Kolkata')->year }} {{ $salonName }}. Premium salon care in Chengalpattu.</p>
                </div>
            </footer>

            <div
                x-data="{ visible: false }"
                x-init="
                    const show = () => {
                        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                        visible = true;
                        window.setTimeout(() => visible = false, 650);
                    };
                    window.addEventListener('pageshow', () => visible = false);
                    document.addEventListener('click', event => {
                        const link = event.target.closest('a[href]');
                        if (! link || link.target || link.hasAttribute('download') || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
                        const url = new URL(link.href, window.location.href);
                        if (url.origin !== window.location.origin || url.hash && url.pathname === window.location.pathname) return;
                        show();
                    });
                "
                class="salon-page-transition"
                :class="{ 'is-visible': visible }"
                role="status"
                aria-live="polite"
            >
                <div class="salon-page-loader">
                    <svg class="salon-loader-mark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M4 8h12"/>
                        <path d="M4 12h14"/>
                        <path d="M4 16h10"/>
                        <path d="M18.5 7.5 21 10l-2.5 2.5"/>
                    </svg>
                    <span class="text-xs font-bold uppercase tracking-[0.16em]">Preparing your salon experience</span>
                </div>
            </div>

            @include('components.public-whatsapp-floater', ['settings' => $settings])
        </div>
    </body>
</html>
