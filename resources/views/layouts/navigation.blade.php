@php
    $user = Auth::user();
    $routeRoot = $user->isAdmin() ? 'admin' : 'staff';
    $searchRoute = Route::has($routeRoot.'.search') ? route($routeRoot.'.search') : route($routeRoot.'.dashboard');

    $baseItems = [
        ['label' => 'Dashboard', 'short' => 'Home', 'route' => $routeRoot.'.dashboard', 'match' => [$routeRoot.'.dashboard'], 'icon' => 'dashboard'],
        ['label' => 'Quick Billing', 'short' => 'Billing', 'route' => $routeRoot.'.billing.create', 'match' => [$routeRoot.'.billing.*'], 'icon' => 'billing'],
    ];

    $adminItems = [
        ['label' => 'Appointments', 'short' => 'Appts', 'route' => 'admin.appointments.index', 'match' => ['admin.appointments.*'], 'icon' => 'appointments'],
        ['label' => 'Customers', 'short' => 'Customers', 'route' => 'admin.customers.index', 'match' => ['admin.customers.*'], 'icon' => 'customers'],
        ['label' => 'Services', 'short' => 'Services', 'route' => 'admin.services.index', 'match' => ['admin.services.*'], 'icon' => 'services'],
        ['label' => 'Attendance', 'short' => 'Day', 'route' => 'admin.attendance.index', 'match' => ['admin.attendance.*'], 'icon' => 'attendance'],
        ['label' => 'Staff', 'short' => 'Staff', 'route' => 'admin.staff.index', 'match' => ['admin.staff.*'], 'icon' => 'staff'],
        ['label' => 'Reports', 'short' => 'Reports', 'route' => 'admin.reports.index', 'match' => ['admin.reports.*'], 'icon' => 'reports'],
        ['label' => 'Settings', 'short' => 'Settings', 'route' => 'admin.settings.edit', 'match' => ['admin.settings.*'], 'icon' => 'settings'],
        ['label' => 'About SalonOS', 'short' => 'About', 'route' => 'admin.about-salonos', 'match' => ['admin.about-salonos'], 'icon' => 'about'],
    ];

    $items = collect($user->isAdmin() ? array_merge($baseItems, $adminItems) : $baseItems)
        ->filter(fn ($item) => Route::has($item['route']))
        ->values();

    $bottomItems = $user->isAdmin()
        ? collect([
            $items->firstWhere('route', 'admin.dashboard'),
            $items->firstWhere('route', 'admin.billing.create'),
            $items->firstWhere('route', 'admin.appointments.index'),
            $items->firstWhere('route', 'admin.customers.index'),
        ])->filter()->values()
        : collect([
            $items->firstWhere('route', 'staff.dashboard'),
            $items->firstWhere('route', 'staff.billing.create'),
            ['label' => 'Profile', 'short' => 'Profile', 'route' => 'profile.edit', 'match' => ['profile.*'], 'icon' => 'staff'],
        ])->filter(fn ($item) => Route::has($item['route']))->values();
@endphp

<div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-black/70 lg:hidden" @click="sidebarOpen = false" style="display: none;"></div>

@include('components.admin.sidebar', ['items' => $items, 'user' => $user, 'routeRoot' => $routeRoot])

@include('components.admin.header', ['user' => $user, 'routeRoot' => $routeRoot, 'searchRoute' => $searchRoute])

<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-[var(--app-border)] bg-[var(--app-surface)]/95 px-2 pb-[calc(env(safe-area-inset-bottom)+0.45rem)] pt-2 shadow-[0_-18px_40px_rgba(0,0,0,0.28)] backdrop-blur-xl lg:hidden" aria-label="Primary mobile navigation">
    <div class="grid grid-cols-5 gap-1">
        @foreach ($bottomItems as $item)
            @php($active = request()->routeIs(...$item['match']))
            <a
                href="{{ route($item['route']) }}"
                class="flex min-w-0 flex-col items-center justify-center gap-1 rounded-2xl px-1 py-2 text-[11px] font-semibold transition {{ $active ? 'bg-[var(--app-primary-soft)] text-[var(--app-primary)] shadow-[0_0_20px_var(--app-glow)]' : 'text-[var(--app-muted)] hover:text-[var(--app-text)]' }}"
                aria-current="{{ $active ? 'page' : 'false' }}"
            >
                <x-app-icon :name="$item['icon']" class="h-5 w-5" />
                <span class="max-w-full truncate">{{ $item['short'] ?? $item['label'] }}</span>
            </a>
        @endforeach
        <button
            type="button"
            class="flex min-w-0 flex-col items-center justify-center gap-1 rounded-2xl px-1 py-2 text-[11px] font-semibold text-[var(--app-muted)] transition hover:text-[var(--app-text)]"
            @click="sidebarOpen = true"
        >
            <x-app-icon name="menu" class="h-5 w-5" />
            <span>More</span>
        </button>
    </div>
</nav>
