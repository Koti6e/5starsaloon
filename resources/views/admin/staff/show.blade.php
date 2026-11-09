<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--app-subtle)]">Staff profile</p>
                <h1 class="mt-1 font-serif text-2xl text-[var(--app-primary)]">{{ $staffMember->name }}</h1>
                <p class="mt-1 text-sm text-[var(--app-muted)]">{{ $staffMember->employee_code ?: $staffMember->username }} · {{ ucfirst($staffMember->status) }}</p>
            </div>
            <a href="{{ route('admin.staff.index') }}" class="rounded-xl border border-[var(--app-border)] px-4 py-2 text-sm font-semibold text-[var(--app-text)]">Back to staff</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        <x-admin.period-filter :period="$period" :action="route('admin.staff.show', $staffMember)" />

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Staff profile details">
            @foreach ([
                ['Role', 'Staff'],
                ['Specialization', $staffMember->specialization ?: 'Not recorded'],
                ['Employment', $staffMember->employment_type ?: 'Not recorded'],
                ['Joined', $staffMember->joining_date?->format('d M Y') ?: 'Not recorded'],
                ['Contact', $staffMember->mobile ?: 'Not recorded'],
                ['Email', $staffMember->email ?: 'Not recorded'],
                ['Shift', $staffMember->shift_start && $staffMember->shift_end ? $staffMember->shift_start.'–'.$staffMember->shift_end : 'Not recorded'],
                ['Weekly off', $staffMember->weekly_off ?: 'Not recorded'],
            ] as [$label, $value])
                <x-admin.card class="!p-4"><p class="text-xs text-[var(--app-muted)]">{{ $label }}</p><p class="mt-1 break-words font-semibold text-[var(--app-text)]">{{ $value }}</p></x-admin.card>
            @endforeach
        </section>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Staff performance summary">
            @foreach ([
                ['Bills issued', number_format((int) $billSummary->bill_count)],
                ['Billed sales', '₹'.number_format((float) $billSummary->total_sales, 2)],
                ['Average bill', '₹'.number_format((float) $billSummary->average_bill, 2)],
                ['Assigned appointments', number_format($appointmentCount)],
            ] as [$label, $value])
                <x-admin.card class="!p-4"><p class="text-xs text-[var(--app-muted)]">{{ $label }}</p><p class="mt-1 text-xl font-semibold tabular-nums text-[var(--app-text)]">{{ $value }}</p></x-admin.card>
            @endforeach
        </section>

        <div class="grid gap-5 lg:grid-cols-2">
            <x-admin.card class="overflow-hidden !p-0">
                <div class="border-b border-[var(--app-border)] p-4"><h2 class="font-serif text-lg text-[var(--app-primary)]">Attendance history</h2><p class="mt-1 text-sm text-[var(--app-muted)]">Present {{ $attendanceCounts->get('present', 0) }} · Late {{ $attendanceCounts->get('late', 0) }} · Missing checkout {{ $attendanceRows->whereNull('check_out_time')->whereNotNull('check_in_time')->count() }}</p></div>
                <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="bg-[var(--app-bg)] text-xs text-[var(--app-muted)]"><tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Check-in / out</th><th class="px-4 py-3">Duration</th></tr></thead><tbody class="divide-y divide-[var(--app-border)]">
                    @forelse ($attendanceRows as $row)
                        <tr><td class="px-4 py-3 text-[var(--app-text)]">{{ $row->attendance_date->format('d M Y') }}</td><td class="px-4 py-3"><x-admin.status-badge :tone="in_array($row->status, ['present', 'late'], true) ? ($row->status === 'late' ? 'warning' : 'success') : 'neutral'">{{ str_replace('_', ' ', ucfirst($row->status)) }}</x-admin.status-badge></td><td class="px-4 py-3 text-[var(--app-muted)]">{{ $row->check_in_time ?: '—' }} / {{ $row->check_out_time ?: '—' }}</td><td class="px-4 py-3 text-[var(--app-muted)]">@if ($row->worked_minutes !== null){{ intdiv($row->worked_minutes, 60) }}h {{ $row->worked_minutes % 60 }}m @else—@endif</td></tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-[var(--app-muted)]">No attendance records in this period.</td></tr>
                    @endforelse
                </tbody></table></div>
            </x-admin.card>

            <x-admin.card class="overflow-hidden !p-0">
                <div class="border-b border-[var(--app-border)] p-4"><h2 class="font-serif text-lg text-[var(--app-primary)]">Services performed</h2><p class="mt-1 text-sm text-[var(--app-muted)]">Line totals before bill-level discounts, from completed bills.</p></div>
                <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="bg-[var(--app-bg)] text-xs text-[var(--app-muted)]"><tr><th class="px-4 py-3">Service</th><th class="px-4 py-3">Quantity</th><th class="px-4 py-3 text-right">Line total</th></tr></thead><tbody class="divide-y divide-[var(--app-border)]">
                    @forelse ($serviceRows as $service)
                        <tr><td class="px-4 py-3 text-[var(--app-text)]">{{ $service->service_name_snapshot }}</td><td class="px-4 py-3 text-[var(--app-muted)]">{{ number_format((int) $service->quantity) }}</td><td class="px-4 py-3 text-right font-medium text-[var(--app-text)]">₹{{ number_format((float) $service->line_total_before_bill_discount, 2) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-6 text-center text-[var(--app-muted)]">No completed service allocations in this period.</td></tr>
                    @endforelse
                </tbody></table></div>
            </x-admin.card>
        </div>

        <x-admin.card class="overflow-hidden !p-0">
            <div class="border-b border-[var(--app-border)] p-4"><h2 class="font-serif text-lg text-[var(--app-primary)]">Assigned appointments</h2><p class="mt-1 text-sm text-[var(--app-muted)]">{{ $period->label() }} · @if ($appointmentCount > $appointments->count()) showing the latest {{ $appointments->count() }} of {{ $appointmentCount }} @else {{ $appointmentCount }} total @endif</p></div>
            <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="bg-[var(--app-bg)] text-xs text-[var(--app-muted)]"><tr><th class="px-4 py-3">Date / time</th><th class="px-4 py-3">Customer</th><th class="px-4 py-3">Services</th><th class="px-4 py-3">Status</th></tr></thead><tbody class="divide-y divide-[var(--app-border)]">
                @forelse ($appointments as $appointment)
                    <tr><td class="px-4 py-3 text-[var(--app-text)]">{{ $appointment->date->format('d M Y') }} · {{ \Illuminate\Support\Carbon::parse($appointment->start_time)->format('h:i A') }}</td><td class="px-4 py-3 text-[var(--app-text)]">{{ $appointment->customer?->name ?: 'Customer unavailable' }}</td><td class="px-4 py-3 text-[var(--app-muted)]">{{ $appointment->appointmentServices->pluck('service_name_snapshot')->filter()->join(', ') ?: 'No services listed' }}</td><td class="px-4 py-3"><x-admin.status-badge :tone="match ($appointment->status) { 'completed' => 'success', 'cancelled' => 'danger', 'pending' => 'warning', default => 'info' }">{{ str_replace('_', ' ', ucfirst($appointment->status)) }}</x-admin.status-badge></td></tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-[var(--app-muted)]">No assigned appointments in this period.</td></tr>
                @endforelse
            </tbody></table></div>
        </x-admin.card>
    </div>
</x-app-layout>
