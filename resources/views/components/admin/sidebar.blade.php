@props(['items', 'user', 'routeRoot'])

<aside
    class="fixed inset-y-0 left-0 z-50 flex w-[min(88vw,22rem)] flex-col border-r border-[var(--app-border)] bg-[var(--app-bg)] bg-gradient-to-b from-[var(--app-surface-elevated)] via-[var(--app-bg)] to-[var(--app-surface)] text-[var(--app-text)] shadow-[0_24px_80px_rgba(0,0,0,0.4)] transition-all duration-200 lg:w-72 lg:translate-x-0"
    :class="{ '-translate-x-full': ! sidebarOpen, 'translate-x-0': sidebarOpen, 'lg:w-24': collapsed, 'lg:w-72': ! collapsed }"
>
    <div class="flex h-16 items-center justify-between border-b border-[var(--app-border)] px-4">
        <a href="{{ route($routeRoot.'.dashboard') }}" class="flex min-w-0 items-center gap-3">
            <img src="{{ asset('images/brand/logo-small.webp') }}" alt="5 Star New Look Salon logo" class="h-11 w-11 rounded-2xl object-contain ring-1 ring-[var(--app-primary-strong)]/40">
            <span class="min-w-0" x-show="! collapsed" x-transition>
                <span class="block truncate font-serif text-lg text-[var(--app-primary)]">SalonOS</span>
                <span class="block truncate text-[11px] uppercase tracking-[0.18em] text-[var(--app-subtle)]">5 Star New Look</span>
            </span>
        </a>
        <button type="button" class="rounded-full border border-[var(--app-border)] p-2 text-[var(--app-text)] lg:hidden" @click="sidebarOpen = false">
            <span class="sr-only">Close navigation</span>
            <x-app-icon name="close" class="h-5 w-5" />
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-4 py-6">
        <div class="space-y-2">
            @foreach ($items as $item)
                @php($active = request()->routeIs(...$item['match']))
                <a
                    href="{{ route($item['route']) }}"
                    class="group flex items-center gap-4 rounded-3xl px-4 py-3 text-sm font-semibold transition duration-200 {{ $active ? 'bg-[var(--app-primary-strong)] text-black shadow-lg shadow-[var(--app-glow)]' : 'text-[var(--app-text)] hover:bg-[var(--app-primary-soft)] hover:text-[var(--app-primary)]' }} focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--app-primary)]"
                    @click="sidebarOpen = false"
                    aria-current="{{ $active ? 'page' : 'false' }}"
                >
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl {{ $active ? 'bg-black/10 text-black' : 'bg-[var(--app-primary-soft)] text-[var(--app-primary)] group-hover:bg-[var(--app-surface-elevated)]' }}">
                        <x-app-icon :name="$item['icon']" />
                    </span>
                    <span class="truncate" x-show="! collapsed" x-transition>{{ $item['label'] }}</span>
                </a>
            @endforeach

            <a
                href="{{ route('home') }}"
                class="group flex items-center gap-4 rounded-3xl px-4 py-3 text-sm font-semibold text-[var(--app-text)] transition duration-200 hover:bg-[var(--app-primary-soft)] hover:text-[var(--app-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--app-primary)]"
                @click="sidebarOpen = false"
            >
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-[var(--app-primary-soft)] text-[var(--app-primary)] group-hover:bg-[var(--app-surface-elevated)]">
                    <x-app-icon name="public" />
                </span>
                <span class="truncate" x-show="! collapsed" x-transition>Public Website</span>
            </a>
        </div>
    </nav>

    <div class="rounded-[28px] border-t border-[var(--app-border)] bg-[var(--app-surface)]/95 p-4">
        <div class="flex items-center gap-3">
            @if ($user->profilePhotoUrl())
                <img src="{{ $user->profilePhotoUrl() }}" alt="{{ $user->name }}" class="h-12 w-12 rounded-2xl border border-[var(--app-border)] object-cover">
            @else
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-[var(--app-border)] bg-[var(--app-primary-strong)] text-sm font-bold text-black">{{ $user->initials() }}</div>
            @endif
            <div class="min-w-0" x-show="! collapsed" x-transition>
                <p class="truncate text-sm font-semibold text-[var(--app-text)]">{{ $user->name }}</p>
                <p class="truncate text-[11px] uppercase tracking-[0.2em] text-[var(--app-subtle)]">{{ $user->isAdmin() ? 'Captain' : 'Staff' }}</p>
            </div>
        </div>

        <div class="mt-4 space-y-1 text-xs text-[var(--app-subtle)]" x-show="! collapsed" x-transition>
            <p x-text="now.toLocaleDateString('en-IN', { weekday: 'long', day: '2-digit', month: 'short', year: 'numeric', timeZone: 'Asia/Kolkata' })"></p>
            <p x-text="now.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', second: '2-digit', timeZone: 'Asia/Kolkata' }) + ' IST'"></p>
            <a href="https://sushako.in" target="_blank" rel="noopener noreferrer" class="inline-flex font-semibold text-[var(--app-primary)] hover:underline">Powered by Sushako Tech</a>
        </div>
    </div>
</aside>
