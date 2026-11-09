<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
            <div>
                <h1 class="font-serif text-2xl text-[var(--app-primary)]">About SalonOS</h1>
                <p class="mt-1 text-sm text-[var(--app-muted)]">Production readiness for SalonOS v1.0.0.</p>
            </div>
            <a href="{{ route('admin.settings.edit') }}" class="rounded-md bg-[var(--app-primary)] px-4 py-3 text-sm font-bold text-[var(--on-accent)]">Configure Salon</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-lg border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-6">
                <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr] lg:items-center">
                    <div class="flex items-center gap-4">
                        <img src="{{ asset('images/brand/logo-small.webp') }}" alt="5 Star New Look Salon logo" class="h-16 w-16 rounded-full object-contain">
                        <div>
                            <p class="text-sm uppercase tracking-[0.22em] text-[var(--app-subtle)]">Powered by Sushako Tech</p>
                            <h2 class="mt-1 font-serif text-3xl text-[var(--app-primary)]">SalonOS v1.0.0</h2>
                            <p class="mt-2 text-sm text-[var(--app-muted)]">{{ $settings['salon_name'] ?? '5 Star New Look Salon' }}</p>
                        </div>
                    </div>
                    <div class="rounded-md border border-[var(--app-border)] bg-[var(--app-bg)] p-5">
                        <div class="flex items-end justify-between gap-3">
                            <div>
                                <p class="text-sm text-[var(--app-muted)]">Readiness</p>
                                <p class="mt-1 text-3xl font-bold text-[var(--app-text)]">{{ $completed }}/{{ $total }}</p>
                            </div>
                            <p class="text-4xl font-bold text-[var(--app-primary)]">{{ $percentage }}%</p>
                        </div>
                        <div class="mt-4 h-3 overflow-hidden rounded-full bg-[var(--app-surface-elevated)]">
                            <div class="h-full rounded-full bg-[var(--app-primary)]" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['Product', 'SalonOS'],
                    ['Version', 'v1.0.0'],
                    ['Laravel', app()->version()],
                    ['PHP', PHP_VERSION],
                    ['Database', strtoupper($databaseDriver)],
                    ['Environment', app()->environment()],
                    ['Appointments Table', $hasAppointmentsTable ? 'Available' : 'Missing'],
                    ['Partner', 'Sushako Tech'],
                ] as [$label, $value])
                    <div class="rounded-lg border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-4">
                        <p class="text-xs uppercase tracking-[0.16em] text-[var(--app-subtle)]">{{ $label }}</p>
                        <p class="mt-2 break-words text-lg font-semibold text-[var(--app-text)]">{{ $value }}</p>
                    </div>
                @endforeach
            </section>

            <section class="rounded-lg border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-5">
                <h2 class="font-serif text-xl text-[var(--app-primary)]">Production Readiness Checklist</h2>
                <div class="mt-5 divide-y divide-[var(--app-border)]">
                    @foreach ($checks as $check)
                        <div class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="flex items-center gap-3">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full {{ $check['ready'] ? 'bg-emerald-500/15 text-emerald-300' : 'bg-amber-500/15 text-amber-200' }}">
                                        {{ $check['ready'] ? 'OK' : '!' }}
                                    </span>
                                    <p class="font-semibold text-[var(--app-text)]">{{ $check['label'] }}</p>
                                </div>
                                @if ($check['note'])
                                    <p class="ml-11 mt-1 text-sm text-[var(--app-subtle)]">{{ $check['note'] }}</p>
                                @endif
                            </div>
                            @if (! $check['ready'] && $check['route'] && Route::has($check['route']))
                                <a href="{{ route($check['route']) }}" class="rounded-md border border-[var(--app-border)] px-4 py-2 text-center text-sm font-semibold text-[var(--app-primary)]">Configure Now</a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
