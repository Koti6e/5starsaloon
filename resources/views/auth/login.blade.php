<x-guest-layout>
    <div class="text-center">
        <div class="flex items-center justify-center gap-4">
            <span class="h-px w-16 bg-gradient-to-r from-transparent to-[var(--auth-primary-strong)]/70"></span>
            <h1 class="font-serif text-3xl font-bold tracking-tight text-[var(--auth-primary-strong)] sm:text-4xl">Welcome Back</h1>
            <span class="h-px w-16 bg-gradient-to-l from-transparent to-[var(--auth-primary-strong)]/70"></span>
        </div>
        <p class="mt-3 text-base font-medium text-[#fff9ea]/90">Sign in to continue</p>
        <p class="mx-auto mt-3 max-w-sm text-sm leading-6 text-[#fff9ea]/68">Manage appointments, billing, customers and salon operations seamlessly.</p>
    </div>

    <x-auth-session-status class="mt-6 rounded-2xl border border-[var(--auth-primary)]/25 bg-[var(--auth-primary-soft)] px-4 py-3 text-sm text-white" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <x-input-label for="username" :value="__('Username or Email')" class="sr-only" />
            <div class="relative">
                <svg class="pointer-events-none absolute left-5 top-1/2 h-5 w-5 -translate-y-1/2 text-[var(--auth-primary-strong)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M20 21a8 8 0 0 0-16 0"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <x-text-input
                    id="username"
                    class="auth-input block w-full rounded-[1.35rem] py-4 pl-14 pr-5 text-base outline-none transition"
                    type="text"
                    name="username"
                    :value="old('username')"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="Username or Email"
                />
            </div>
            <x-input-error :messages="$errors->get('username')" class="mt-2 rounded-xl border border-red-400/20 bg-red-950/50 px-3 py-2 text-sm text-red-100" />
        </div>

        <div>
            <div class="relative">
                <x-input-label for="password" :value="__('Password')" class="sr-only" />
                <svg class="pointer-events-none absolute left-5 top-1/2 h-5 w-5 -translate-y-1/2 text-[var(--auth-primary-strong)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <rect x="5" y="10" width="14" height="10" rx="2"/>
                    <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                </svg>
                <x-text-input
                    id="password"
                    class="auth-input block w-full rounded-[1.35rem] py-4 pl-14 pr-14 text-base outline-none transition"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Password"
                />
                <button type="button" id="togglePassword" class="absolute right-5 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full text-[var(--auth-primary-strong)] transition hover:bg-white/10 focus:outline-none focus:ring-4 focus:ring-[var(--auth-primary-soft)]" aria-label="Show password">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2 rounded-xl border border-red-400/20 bg-red-950/50 px-3 py-2 text-sm text-red-100" />
        </div>

        <div class="flex items-center justify-between gap-4 pt-1">
            <label for="remember_me" class="inline-flex min-h-11 cursor-pointer select-none items-center gap-3 text-sm font-medium text-[#fff9ea]/78 transition hover:text-[#fff9ea]">
                <input
                    id="remember_me"
                    type="checkbox"
                    class="h-5 w-5 rounded-md border-white/20 bg-black/50 text-[var(--auth-primary)] focus:ring-[var(--auth-primary)] focus:ring-offset-0"
                    name="remember"
                >
                <span>{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="min-h-11 content-center text-sm font-semibold text-[var(--auth-primary-strong)] transition hover:text-[#fff9ea]">
                    Forgot Password?
                </a>
            @endif
        </div>

        <x-primary-button class="auth-button flex min-h-14 w-full items-center justify-center rounded-[1.25rem] border-0 px-8 py-4 text-base font-bold uppercase tracking-wide text-black transition hover:brightness-110 focus:outline-none focus:ring-4 focus:ring-[var(--auth-primary-soft)] active:scale-[0.99]">
            <span>{{ __('Sign In') }}</span>
            <svg class="ml-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0-7 7m7-7H3"/>
            </svg>
        </x-primary-button>

        <div class="flex items-center gap-4 pt-2">
            <span class="h-px flex-1 bg-gradient-to-r from-transparent to-[var(--auth-primary)]/50"></span>
            <svg class="h-5 w-5 text-[var(--auth-primary-strong)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M12 3 5 6v5c0 4.5 2.9 8.5 7 10 4.1-1.5 7-5.5 7-10V6l-7-3Z"/>
                <path d="m9 12 2 2 4-5"/>
            </svg>
            <span class="h-px flex-1 bg-gradient-to-l from-transparent to-[var(--auth-primary)]/50"></span>
        </div>

        <p class="text-center text-sm text-[#fff9ea]/58">
            Powered by
            <a href="https://sushako.in" target="_blank" rel="noopener noreferrer" class="font-semibold text-[var(--auth-primary-strong)] transition hover:text-[#fff9ea]">
                Sushako Tech
            </a>
        </p>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');

            if (! togglePassword || ! passwordInput) return;

            togglePassword.addEventListener('click', function() {
                const isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                togglePassword.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');

                const icon = this.querySelector('svg');
                icon.innerHTML = isPassword
                    ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.9 4.24A10.45 10.45 0 0 1 12 4c4.48 0 8.27 2.94 9.54 7a10.58 10.58 0 0 1-3.14 4.61"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.53 6.53A10.58 10.58 0 0 0 2.46 11c1.27 4.06 5.06 7 9.54 7 1.47 0 2.86-.32 4.1-.89"/>'
                    : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
            });
        });
    </script>
</x-guest-layout>
