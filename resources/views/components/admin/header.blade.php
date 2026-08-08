@props(['user', 'routeRoot', 'searchRoute'])

<div
    class="fixed left-0 right-0 top-0 z-30 h-14 border-b border-[var(--app-border)] bg-[var(--app-surface)]/95 px-3 shadow-xl shadow-black/20 backdrop-blur transition-all duration-200 sm:h-16 sm:px-4 lg:left-72"
    :class="{ 'lg:left-24': collapsed, 'lg:left-72': ! collapsed }"
>
    <div class="flex h-full items-center gap-3">
        <button type="button" class="rounded-full border border-[var(--app-border)] p-2 text-[var(--app-text)] transition hover:border-[var(--app-primary)] hover:text-[var(--app-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--app-primary)] lg:hidden" @click="sidebarOpen = true">
            <span class="sr-only">Open navigation</span>
            <x-app-icon name="menu" class="h-5 w-5" />
        </button>

        <button type="button" class="hidden rounded-full border border-[var(--app-border)] p-2 text-[var(--app-text)] transition hover:border-[var(--app-primary)] hover:text-[var(--app-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--app-primary)] lg:inline-flex" @click="toggleSidebar()">
            <span class="sr-only">Toggle sidebar</span>
            <x-app-icon name="menu" class="h-5 w-5" />
        </button>

        <a href="{{ route($routeRoot.'.dashboard') }}" class="flex min-w-0 items-center gap-2 lg:hidden">
            <img src="{{ asset('images/brand/logo-small.webp') }}" alt="5 Star New Look Salon" class="h-8 w-8 rounded-xl object-contain">
            <span class="min-w-0">
                <span class="block truncate text-sm font-bold text-[var(--app-text)]">SalonOS</span>
                <span class="block truncate text-[10px] uppercase tracking-[0.14em] text-[var(--app-subtle)]">{{ $user->isAdmin() ? 'Captain' : 'Staff' }}</span>
            </span>
        </a>

        <form action="{{ $searchRoute }}" method="GET" class="hidden min-w-0 flex-1 sm:block">
            <label for="global-search" class="sr-only">Search SalonOS</label>
            <div class="relative max-w-xl">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-[var(--app-subtle)]">
                    <x-app-icon name="search" class="h-4 w-4" />
                </span>
                <input
                    id="global-search"
                    name="q"
                    value="{{ request('q') }}"
                    type="search"
                    placeholder="Search customers, invoices, services"
                    class="h-11 w-full rounded-full border border-[var(--app-border)] bg-[var(--app-bg)] pl-11 pr-3 text-sm text-[var(--app-text)] placeholder:text-[var(--app-subtle)] focus:border-[var(--app-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--app-focus)]"
                >
            </div>
        </form>

        <div class="ml-auto flex items-center gap-3">
            <div class="hidden text-right text-xs text-[var(--app-subtle)] md:block">
                <p x-text="now.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'Asia/Kolkata' })"></p>
                <p x-text="now.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Kolkata' })"></p>
            </div>

            <x-theme-toggle />

            <x-dropdown align="right" width="56" contentClasses="py-1 bg-[#11100d] border border-[#c8a24a]/20">
                <x-slot name="trigger">
                    <button type="button" class="flex items-center gap-2 rounded-full border border-[var(--app-border)] bg-[var(--app-surface-elevated)] px-2 py-1.5 text-sm font-medium text-[var(--app-text)] transition hover:text-[var(--app-primary)] sm:px-3 sm:py-2">
                        @if ($user->profilePhotoUrl())
                            <img src="{{ $user->profilePhotoUrl() }}" alt="{{ $user->name }}" class="h-8 w-8 rounded-full object-cover">
                        @else
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[var(--app-primary-strong)] text-xs font-bold text-black">{{ $user->initials() }}</span>
                        @endif
                        <span class="hidden max-w-36 truncate md:block">{{ $user->name }}</span>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <div class="border-b border-[#c8a24a]/15 px-4 py-3">
                        <p class="truncate text-sm font-semibold text-[#fff9ea]">{{ $user->name }}</p>
                        <p class="truncate text-xs text-[#a89567]">{{ $user->username }}</p>
                    </div>
                    <x-dropdown-link :href="route('profile.edit')" class="text-[#f8efd8] hover:bg-[#1a150e]">Profile</x-dropdown-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" class="text-[#f8efd8] hover:bg-[#1a150e]">Log Out</x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        </div>
    </div>
</div>
