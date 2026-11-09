<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--app-subtle)]">Appointments</p>
                <h1 class="font-serif text-2xl text-[var(--app-primary)]">Appointment Dashboard</h1>
                <p class="mt-2 text-sm text-[var(--app-muted)]">Manage appointment status, billing conversions, and customer follow-up from a dedicated admin dashboard.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
                <a href="{{ route('appointments.book') }}" class="rounded-md bg-[var(--app-primary)] px-4 py-3 text-center text-sm font-semibold text-[var(--on-accent)]">Open Public Booking</a>
                <a href="{{ route('admin.closed-dates.index') }}" class="rounded-md border border-[var(--app-border)] px-4 py-3 text-center text-sm font-semibold text-[var(--app-text)]">Manage Closed Dates</a>
                <a href="{{ route('admin.billing.create') }}" class="rounded-md border border-[var(--app-border)] px-4 py-3 text-center text-sm font-semibold text-[var(--app-text)]">Quick Billing</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-admin.period-filter :period="$period" :action="route('admin.appointments.index')" :preserve="['filter' => $filter, 'q' => $search, 'view' => $viewMode]" />
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface)] p-3">
                <p class="text-sm font-medium text-[var(--app-muted)]">{{ $period->label() }}</p>
                <nav class="flex gap-2" aria-label="Appointment display mode">
                    @foreach (($period->mode === 'custom' ? ['list' => 'List'] : ['list' => 'List', 'calendar' => 'Calendar']) as $mode => $label)
                        <a href="{{ route('admin.appointments.index', [...$period->toQuery(), 'filter' => $filter, 'q' => $search, 'view' => $mode]) }}" @class(['rounded-xl px-4 py-2 text-sm font-semibold', 'bg-[var(--app-primary)] text-[var(--on-accent)]' => $viewMode === $mode, 'border border-[var(--app-border)] text-[var(--app-text)] hover:bg-[var(--app-primary-soft)]' => $viewMode !== $mode]) aria-current="{{ $viewMode === $mode ? 'page' : 'false' }}">{{ $label }}</a>
                    @endforeach
                </nav>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
                @foreach ([
                    ['Total', $counts['total'], 'bg-[var(--app-primary)]'],
                    ['Pending', $counts['pending'], 'bg-amber-500'],
                    ['Ongoing', $counts['ongoing'], 'bg-sky-500'],
                    ['Completed', $counts['completed'], 'bg-emerald-500'],
                    ['Cancelled', $counts['cancelled'], 'bg-rose-500'],
                    ['Home Visits', $counts['home_visits'], 'bg-amber-500'],
                ] as [$label, $value, $color])
                    <x-admin.card class="h-full border-0 bg-[var(--app-surface-elevated)]">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm text-[var(--app-muted)]">{{ $label }}</p>
                                <p class="mt-3 text-3xl font-semibold text-[var(--app-text)]">{{ $value }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-full {{ $color }} bg-opacity-20"></div>
                        </div>
                    </x-admin.card>
                @endforeach
            </div>

            <div class="rounded-lg border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-5">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex flex-wrap gap-2">
                        @foreach (['all' => 'All', 'today' => 'Today', 'upcoming' => 'Upcoming', 'pending' => 'Pending', 'ongoing' => 'Ongoing', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'home_visits' => 'Home Visits'] as $key => $label)
                            <a href="{{ route('admin.appointments.index', array_merge(request()->except('page'), ['filter' => $key])) }}" class="rounded-full border px-4 py-2 text-sm font-semibold {{ $filter === $key ? 'border-[var(--accent-border)] bg-[var(--app-primary)] text-[var(--on-accent)]' : 'border-[var(--app-border)] text-[var(--app-text)]' }}">{{ $label }}</a>
                        @endforeach
                    </div>
                    <form method="GET" action="{{ route('admin.appointments.index') }}" class="flex w-full items-center gap-2 md:w-auto">
                        @foreach ($period->toQuery() as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
                        <input type="hidden" name="filter" value="{{ $filter }}">
                        <input type="hidden" name="view" value="{{ $viewMode }}">
                        <input name="q" value="{{ $search }}" placeholder="Search appointment, customer, mobile" class="w-full rounded-md border-[var(--app-border)] bg-[var(--app-bg)] px-4 py-3 text-[var(--app-text)] focus:border-[var(--accent-border)] focus:ring-[var(--app-focus)]" />
                        <button type="submit" class="rounded-md bg-[var(--app-primary)] px-4 py-3 text-sm font-semibold text-[var(--on-accent)]">Search</button>
                    </form>
                </div>
            </div>

            @if ($viewMode === 'calendar')
                <section aria-label="Appointments calendar" class="grid {{ $period->mode === 'daily' ? 'grid-cols-1' : 'grid-cols-2 sm:grid-cols-7' }} gap-2">
                    @if ($period->mode !== 'daily')
                        @foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $weekday)
                            <h2 class="hidden px-2 py-1 text-xs font-semibold uppercase tracking-wide text-[var(--app-muted)] sm:block">{{ $weekday }}</h2>
                        @endforeach
                    @endif
                    @foreach ($calendarDays as $day)
                        <div @class(['min-h-36 rounded-xl border p-2 sm:min-h-44', 'border-[var(--app-border)] bg-[var(--app-surface)]' => $day['inPeriod'], 'border-transparent bg-[var(--app-bg)]/50' => ! $day['inPeriod']])>
                            <h3 @class(['mb-2 flex items-center justify-between text-sm font-semibold', 'text-[var(--app-text)]' => $day['inPeriod'], 'text-[var(--app-subtle)]' => ! $day['inPeriod']])>
                                <span>{{ $period->mode === 'daily' ? $day['date']->translatedFormat('l, d M Y') : $day['date']->format('j') }}</span>
                                @if ($day['appointments']->isNotEmpty())<span class="rounded-full bg-[var(--app-primary-soft)] px-2 py-0.5 text-xs text-[var(--app-primary)]">{{ $day['appointments']->count() }}</span>@endif
                            </h3>
                            <div class="space-y-2">
                                @foreach ($day['appointments'] as $appointment)
                                    @php
                                        $appointmentTone = match ($appointment->status) {
                                            'pending' => 'warning',
                                            'confirmed', 'in_progress' => 'info',
                                            'completed' => 'success',
                                            'cancelled' => 'danger',
                                            default => 'neutral',
                                        };
                                    @endphp
                                    <a href="{{ route('admin.appointments.show', $appointment) }}" class="block rounded-lg border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-2 transition hover:border-[var(--app-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-[var(--app-focus)]">
                                        <p class="text-xs font-semibold text-[var(--app-primary)]">{{ \Illuminate\Support\Carbon::parse($appointment->start_time)->format('h:i A') }} · {{ $appointment->customer->name }}</p>
                                        <p class="mt-1 line-clamp-2 text-xs text-[var(--app-muted)]">{{ $appointment->appointmentServices->pluck('service_name_snapshot')->filter()->join(', ') ?: 'Service details unavailable' }}</p>
                                        <div class="mt-1 flex flex-wrap items-center gap-1 text-xs"><x-admin.status-badge :tone="$appointmentTone">{{ str_replace('_', ' ', ucfirst($appointment->status)) }}</x-admin.status-badge>@if ($appointment->assignedStaff)<span class="text-[var(--app-subtle)]">{{ $appointment->assignedStaff->name }}</span>@endif</div>
                                    </a>
                                @endforeach
                                @if ($day['appointments']->isEmpty() && $period->mode === 'daily')<p class="py-4 text-sm text-[var(--app-subtle)]">No appointments for this day.</p>@endif
                            </div>
                        </div>
                    @endforeach
                </section>
            @elseif ($appointments->isEmpty())
                <x-admin.card>
                    <div class="space-y-4 text-center">
                        <p class="text-sm uppercase tracking-[0.26em] text-[var(--app-subtle)]">No appointments yet</p>
                        <h2 class="font-serif text-3xl text-[var(--app-primary)]">New appointments will appear here.</h2>
                        <p class="text-sm text-[var(--app-muted)]">New online and admin-created appointments will appear here.</p>
                        <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                            <a href="{{ route('appointments.book') }}" class="rounded-md bg-[var(--app-primary)] px-4 py-3 text-sm font-semibold text-[var(--on-accent)]">Open Public Booking Page</a>
                            <a href="{{ route('admin.billing.create') }}" class="rounded-md border border-[var(--app-border)] px-4 py-3 text-sm font-semibold text-[var(--app-text)]">Quick Billing</a>
                        </div>
                    </div>
                </x-admin.card>
            @else
                <div class="grid gap-4">
                    @foreach ($appointments as $appointment)
                        @php
                            $bill = $appointment->bills->sortByDesc('id')->first();
                            $services = $appointment->appointmentServices->pluck('service_name_snapshot')->filter()->join(', ');
                            $dateLabel = $appointment->date?->format('d M Y');
                            $timeLabel = \Illuminate\Support\Carbon::parse($appointment->start_time)->format('h:i A');
                            $mobile = preg_replace('/\D+/', '', $appointment->customer->mobile);
                            $whatsappMessage = "{$appointment->customer->name}, this is 5 Star New Look Salon regarding your appointment {$appointment->booking_number}.\n\nService: {$services}\nDate: {$dateLabel}\nTime: {$timeLabel}\n\nPlease confirm your attendance or any changes.";
                            $statusTone = match ($appointment->status) {
                                'pending' => 'warning',
                                'confirmed', 'in_progress' => 'info',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                default => 'neutral',
                            };
                        @endphp
                        <article class="rounded-lg border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-4 shadow-lg shadow-black/10 sm:p-5">
                            <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_320px] lg:items-start">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <x-admin.status-badge :tone="$statusTone" class="uppercase tracking-[0.12em]">{{ Str::headline($appointment->status) }}</x-admin.status-badge>
                                        <span class="text-sm font-semibold text-[var(--app-text)]">{{ $appointment->booking_number }}</span>
                                    </div>

                                    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                        <div>
                                            <p class="text-xs uppercase text-[var(--app-subtle)]">Customer</p>
                                            <p class="mt-1 font-semibold text-[var(--app-text)]">{{ $appointment->customer->name }}</p>
                                            <p class="text-sm text-[var(--app-muted)]">+91 {{ $appointment->customer->mobile }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs uppercase text-[var(--app-subtle)]">When</p>
                                            <p class="mt-1 font-semibold text-[var(--app-text)]">{{ $dateLabel }}</p>
                                            <p class="text-sm text-[var(--app-muted)]">{{ $timeLabel }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs uppercase text-[var(--app-subtle)]">Visit Type</p>
                                            <p class="mt-1 font-semibold text-[var(--app-text)]">{{ $appointment->appointment_type === 'home_service' ? 'Home Visit' : 'Salon Visit' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs uppercase text-[var(--app-subtle)]">Assigned Staff</p>
                                            <p class="mt-1 font-semibold text-[var(--app-text)]">{{ $appointment->assignedStaff?->name ?? 'Not assigned' }}</p>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <p class="text-xs uppercase text-[var(--app-subtle)]">Services</p>
                                        <p class="mt-1 text-sm leading-6 text-[var(--app-muted)]">{{ $services ?: 'Salon service' }}</p>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    @if (! in_array($appointment->status, ['completed', 'cancelled'], true))
                                        <form method="POST" action="{{ route('admin.appointments.assign', $appointment) }}" class="grid grid-cols-[minmax(0,1fr)_auto] gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <select name="assigned_staff_id" class="min-w-0 rounded-md border border-[var(--app-border)] bg-[var(--app-bg)] px-3 py-2 text-sm text-[var(--app-text)]">
                                                <option value="">Assign staff</option>
                                                @foreach ($activeStaff as $member)
                                                    <option value="{{ $member->id }}" @selected($appointment->assigned_staff_id === $member->id)>{{ $member->name }}</option>
                                                @endforeach
                                            </select>
                                            <button class="rounded-md border border-[var(--app-border)] px-3 py-2 text-xs font-semibold text-[var(--app-primary)]">{{ $appointment->assigned_staff_id ? 'Change' : 'Assign' }}</button>
                                        </form>
                                    @endif

                                    <div class="grid grid-cols-2 gap-2">
                                        @if ($appointment->status === 'pending')
                                            <form method="POST" action="{{ route('admin.appointments.status.update', $appointment) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="confirmed">
                                                <button class="w-full rounded-md bg-[var(--app-primary)] px-3 py-2 text-xs font-bold text-[var(--on-accent)]">Accept</button>
                                            </form>
                                        @endif
                                        @if ($appointment->status === 'confirmed')
                                            <form method="POST" action="{{ route('admin.appointments.status.update', $appointment) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="in_progress">
                                                <button class="w-full rounded-md bg-[var(--app-primary)] px-3 py-2 text-xs font-bold text-[var(--on-accent)]">Start</button>
                                            </form>
                                        @endif
                                        @if (in_array($appointment->status, ['confirmed', 'in_progress'], true))
                                            <form method="POST" action="{{ route('admin.appointments.status.update', $appointment) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="completed">
                                                <button class="w-full rounded-md bg-[var(--app-primary)] px-3 py-2 text-xs font-bold text-[var(--on-accent)]">Complete</button>
                                            </form>
                                        @endif
                                        @if (! in_array($appointment->status, ['completed', 'cancelled'], true))
                                            <form method="POST" action="{{ route('admin.appointments.status.update', $appointment) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="cancelled">
                                                <button class="w-full rounded-md border border-rose-500/40 px-3 py-2 text-xs font-semibold text-rose-700 dark:text-rose-300">Cancel</button>
                                            </form>
                                        @endif
                                    </div>

                                    <div class="grid grid-cols-2 gap-2">
                                        <a href="{{ route('admin.appointments.show', $appointment) }}" class="rounded-md border border-[var(--app-border)] bg-[var(--app-bg)] px-3 py-2 text-center text-xs font-semibold text-[var(--app-text)]">View Details</a>
                                        @if ($bill)
                                            <a href="{{ route('admin.billing.show', $bill) }}" class="rounded-md border border-[var(--accent-border)] bg-[var(--app-bg)] px-3 py-2 text-center text-xs font-semibold text-[var(--app-primary)]">View Bill</a>
                                        @else
                                            <a href="{{ route('admin.billing.create', ['appointment_id' => $appointment->id]) }}" class="rounded-md border border-[var(--accent-border)] bg-[var(--app-bg)] px-3 py-2 text-center text-xs font-semibold text-[var(--app-primary)]">Billing</a>
                                        @endif
                                        <a href="tel:+91{{ $mobile }}" class="rounded-md border border-[var(--app-border)] bg-[var(--app-bg)] px-3 py-2 text-center text-xs font-semibold text-[var(--app-text)]">Call</a>
                                        <a href="https://wa.me/91{{ $mobile }}?text={{ rawurlencode($whatsappMessage) }}" target="_blank" rel="noopener" class="rounded-md border border-[var(--app-border)] bg-[var(--app-bg)] px-3 py-2 text-center text-xs font-semibold text-[var(--app-text)]">WhatsApp</a>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-4">{{ $appointments->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
