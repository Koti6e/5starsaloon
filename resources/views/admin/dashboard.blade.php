<x-app-layout>
    <x-slot name="header">
        @php
            $hour = now('Asia/Kolkata')->hour;
            $greeting = match (true) {
                $hour >= 5 && $hour < 12 => 'Good Morning',
                $hour >= 12 && $hour < 17 => 'Good Afternoon',
                $hour >= 17 && $hour < 21 => 'Good Evening',
                default => 'Good Night',
            };
        @endphp

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-[var(--app-subtle)]">
                    {{ $greeting }}
                </p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-[var(--app-primary)] sm:text-3xl">
                    Welcome back, {{ auth()->user()->name }}
                </h1>
                <p class="mt-1 text-sm text-[var(--app-muted)]">Business overview for {{ $period->label() }}.</p>
            </div>

            <div class="text-right text-sm text-[var(--app-muted)]">
                <p>{{ now('Asia/Kolkata')->format('l, d F Y') }} · IST</p>
            </div>
        </div>
    </x-slot>

    @php($rangeQuery = $period->toQuery())
    <div class="space-y-6 px-4 py-5 sm:px-6 lg:px-8"
         x-data="{
            live: @js($period->isCurrentBusinessDay()),
            updatedAt: '',
            metrics: {
                sales: @js((float) $todaySales), bills: @js((int) $todayBills), customers: @js((int) $customersServed),
                average_bill: @js((float) $averageBillValue), appointments: @js((int) $todayAppointments),
                staff_present: @js((int) $staffPresentToday), staff_absent: @js((int) $staffAbsentToday),
                cash: @js((float) $cashSales), upi: @js((float) $upiSales), card: @js((float) $cardSales), other: @js((float) $otherSales),
                pending_home_visits: @js((int) $pendingHomeVisits)
            },
            money(value) { return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 2 }).format(Number(value || 0)); },
            async refresh() {
                if (!this.live) return;
                try {
                    const response = await fetch(@js(route('admin.dashboard.live-metrics')), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                    if (!response.ok) return;
                    this.metrics = await response.json();
                    this.updatedAt = new Intl.DateTimeFormat('en-IN', { hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Kolkata' }).format(new Date(this.metrics.updated_at)) + ' IST';
                } catch (error) { /* Keep the last successful snapshot during a brief network outage. */ }
            },
            init() { if (this.live) { window.setInterval(() => this.refresh(), 60000); } }
         }">
        <x-admin.period-filter :period="$period" :action="route('admin.dashboard')" />
        <p x-show="live" x-cloak class="-mt-3 text-xs text-[var(--app-primary)]">Live dashboard · refreshes every minute<span x-show="updatedAt"> · Updated <span x-text="updatedAt"></span></span></p>

        {{-- BUSINESS OVERVIEW --}}
        <section>
            <div class="mb-3 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-[var(--app-text)]">Business Overview</h2>
                    <p class="text-xs text-[var(--app-muted)]">Sales and operations for the selected reporting period</p>
                </div>
                <span class="rounded-full border border-[var(--app-border)] px-3 py-1 text-xs font-semibold text-[var(--app-muted)]">{{ $period->label() }}</span>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5">
                @foreach ([
                    ['Net Sales', 'sales', true, route('admin.reports.sales', $rangeQuery)],
                    ['Completed Bills', 'bills', false, route('admin.billing.index', $rangeQuery)],
                    ['Customers Served', 'customers', false, route('admin.customers.index', $rangeQuery)],
                    ['Average Bill', 'average_bill', true, route('admin.reports.sales', $rangeQuery)],
                    ['Appointments', 'appointments', false, route('admin.appointments.index', ['date' => $period->start->toDateString()])],
                    ['Staff Present', 'staff_present', false, route('admin.attendance.index', ['date' => $period->start->toDateString()])],
                    ['Staff Absent', 'staff_absent', false, route('admin.attendance.index', ['date' => $period->start->toDateString()])],
                ] as [$label, $key, $money, $href])
                    <a href="{{ $href }}"
                       class="group rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] p-4 transition hover:-translate-y-0.5 hover:border-[var(--accent-border)] hover:shadow-md">
                        <p class="text-xs font-semibold text-[var(--app-muted)]">{{ $label }}</p>
                        <p class="mt-3 break-words text-xl font-bold tracking-tight text-[var(--app-text)] sm:text-2xl" aria-live="polite">
                            <span x-show="live" x-cloak x-text="{{ $money ? 'money(metrics.'.$key.')' : 'metrics.'.$key }}"></span>
                            <span x-show="!live" x-cloak>{{ $money ? \App\Support\Money::inr($key === 'sales' ? $todaySales : $averageBillValue) : match ($key) { 'bills' => $todayBills, 'customers' => $customersServed, 'appointments' => $todayAppointments, 'staff_present' => $staffPresentToday, default => $staffAbsentToday } }}</span>
                        </p>
                        <p class="mt-2 text-[11px] font-semibold text-[var(--app-primary)]">
                            View details →
                        </p>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- PAYMENT OVERVIEW --}}
        <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ([
                ['Cash Collections', 'cash', $cashSales, 'cash'],
                ['UPI Collections', 'upi', $upiSales, 'upi'],
                ['Card Collections', 'card', $cardSales, 'card'],
                ['Other Collections', 'other', $otherSales, 'other'],
                ['Pending Home Visits', 'pending_home_visits', $pendingHomeVisits, null],
            ] as [$label, $key, $value, $method])
                <a href="{{ $method ? route('admin.reports.payments', [...$rangeQuery, 'method' => $method]) : route('admin.appointments.index', ['filter' => 'home_visits', 'date' => $period->start->toDateString()]) }}" class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] p-4 transition hover:border-[var(--accent-border)] hover:bg-[var(--app-primary-soft)]">
                    <p class="text-xs font-semibold text-[var(--app-muted)]">{{ $label }}</p>
                    <p class="mt-2 text-lg font-bold text-[var(--app-text)] sm:text-xl" aria-live="polite">
                        <span x-show="live" x-cloak x-text="{{ $key === 'pending_home_visits' ? 'metrics.pending_home_visits' : 'money(metrics.'.$key.')' }}"></span>
                        <span x-show="!live" x-cloak>{{ $key === 'pending_home_visits' ? $value : \App\Support\Money::inr($value) }}</span>
                    </p>
                    <span class="mt-2 block text-[11px] font-semibold text-[var(--app-primary)]">View details →</span>
                </a>
            @endforeach
        </section>

        {{-- QUICK ACTIONS --}}
        <section class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] p-4 sm:p-5">
            <div class="mb-4">
                <h2 class="text-lg font-bold text-[var(--app-text)]">Quick Actions</h2>
                <p class="text-xs text-[var(--app-muted)]">Frequently used salon operations</p>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                <a href="{{ route('admin.billing.create') }}"
                   class="flex min-h-16 items-center justify-center rounded-xl bg-[var(--app-primary)] px-3 py-3 text-center text-sm font-bold text-[var(--on-accent)] transition hover:opacity-90">
                    + Quick Billing
                </a>

                <a href="{{ route('admin.backdated-billing.create') }}"
                   class="flex min-h-16 items-center justify-center rounded-xl border border-[var(--accent-border)] bg-[var(--app-primary-soft)] px-3 py-3 text-center text-sm font-bold text-[var(--app-primary)] transition hover:opacity-80">
                    + Backdated Bill
                </a>

                <a href="{{ route('admin.billing.index', $rangeQuery) }}"
                   class="flex min-h-16 items-center justify-center rounded-xl border border-[var(--app-border)] px-3 py-3 text-center text-sm font-semibold text-[var(--app-text)] transition hover:bg-[var(--app-bg)]">
                    Bills Register
                </a>

                <a href="{{ route('admin.reports.sales', $rangeQuery) }}"
                   class="flex min-h-16 items-center justify-center rounded-xl border border-[var(--app-border)] px-3 py-3 text-center text-sm font-semibold text-[var(--app-text)] transition hover:bg-[var(--app-bg)]">
                    Sales Report
                </a>

                <a href="{{ route('admin.appointments.index') }}"
                   class="flex min-h-16 items-center justify-center rounded-xl border border-[var(--app-border)] px-3 py-3 text-center text-sm font-semibold text-[var(--app-text)] transition hover:bg-[var(--app-bg)]">
                    Appointments
                </a>

                <a href="{{ route('admin.customers.create') }}"
                   class="flex min-h-16 items-center justify-center rounded-xl border border-[var(--app-border)] px-3 py-3 text-center text-sm font-semibold text-[var(--app-text)] transition hover:bg-[var(--app-bg)]">
                    + Add Customer
                </a>

                <a href="{{ route('admin.attendance.index') }}"
                   class="col-span-2 flex min-h-16 items-center justify-center rounded-xl border border-[var(--app-border)] px-3 py-3 text-center text-sm font-semibold text-[var(--app-text)] transition hover:bg-[var(--app-bg)] sm:col-span-1">
                    Staff Attendance
                </a>
            </div>
        </section>

        {{-- TODAY'S BILLS --}}
        <section class="overflow-hidden rounded-2xl border border-[var(--accent-border)] bg-[var(--app-card)]">
            <div class="flex flex-col gap-3 border-b border-[var(--app-border)] p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-xl font-bold text-[var(--app-text)]">
                            Bills for {{ $period->label() }}
                        </h2>
                        <span class="rounded-full bg-[var(--app-primary-soft)] px-2.5 py-1 text-xs font-bold text-[var(--app-primary)]">
                            {{ $selectedBillCount }} Bills
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-[var(--app-muted)]">
                        Completed invoices in this reporting period
                    </p>
                    <p class="mt-2 text-xl font-bold text-[var(--app-primary)]">
                        {{ \App\Support\Money::inr($selectedBillSales) }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.billing.index', $rangeQuery) }}"
                       class="rounded-lg border border-[var(--app-border)] px-3 py-2 text-xs font-semibold text-[var(--app-text)] hover:bg-[var(--app-bg)]">
                        Open Bills Register
                    </a>

                    <a href="{{ route('admin.billing.create') }}"
                       class="rounded-lg bg-[var(--app-primary)] px-4 py-2 text-xs font-bold text-[var(--on-accent)] hover:opacity-90">
                        + Create Bill
                    </a>
                </div>
            </div>

            {{-- DESKTOP BILL TABLE --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[850px] text-left text-sm">
                    <thead class="bg-[var(--app-bg)] text-xs font-semibold uppercase tracking-wide text-[var(--app-muted)]">
                        <tr>
                            <th class="px-5 py-4">Time / Invoice</th>
                            <th class="px-3 py-4">Customer</th>
                            <th class="px-3 py-4">Services</th>
                            <th class="px-3 py-4">Payment</th>
                            <th class="px-3 py-4">WhatsApp</th>
                            <th class="px-3 py-4 text-right">Amount</th>
                            <th class="px-5 py-4 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-[var(--app-border)]">
                        @forelse($todayBillRows as $bill)
                            <tr class="transition hover:bg-[var(--app-bg)]">
                                <td class="px-5 py-4">
                                    <span class="block font-semibold text-[var(--app-text)]">
                                        {{ $bill->billed_at?->timezone('Asia/Kolkata')->format('h:i A') }}
                                    </span>
                                    <span class="text-xs text-[var(--app-muted)]">
                                        {{ $bill->invoice_number }}
                                    </span>
                                </td>

                                <td class="px-3 py-4 text-[var(--app-text)]">
                                    {{ $bill->customer?->name ?? 'Walk-in customer' }}
                                </td>

                                <td class="max-w-[220px] px-3 py-4 text-xs text-[var(--app-muted)]">
                                    {{ $bill->items->pluck('service_name_snapshot')->filter()->join(', ') ?: '—' }}
                                </td>

                                <td class="px-3 py-4">
                                    <span class="rounded-full border border-[var(--app-border)] px-2.5 py-1 text-xs font-semibold capitalize text-[var(--app-text)]">
                                        {{ $bill->payment_status }}
                                    </span>
                                </td>

                                <td class="px-3 py-4 text-xs text-[var(--app-muted)]">
                                    {{ $bill->whatsapp_invoice_status ?: 'pending' }}
                                </td>

                                <td class="px-3 py-4 text-right font-bold text-[var(--app-text)]">
                                    {{ \App\Support\Money::inr($bill->grand_total) }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-right text-xs font-semibold">
                                    <a class="text-[var(--app-primary)] hover:underline"
                                       href="{{ route('admin.billing.show', $bill) }}">View</a>
                                    <span class="px-1 text-[var(--app-subtle)]">·</span>
                                    <a class="text-[var(--app-primary)] hover:underline"
                                       href="{{ route('admin.billing.print', $bill) }}">Print</a>
                                    <span class="px-1 text-[var(--app-subtle)]">·</span>
                                    <a class="text-[var(--app-primary)] hover:underline"
                                       href="{{ route('admin.billing.pdf', $bill) }}">PDF</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center">
                                    <p class="font-semibold text-[var(--app-text)]">No bills issued today</p>
                            <p class="mt-1 text-xs text-[var(--app-muted)]">No completed bills on this date.</p>
                                    <a href="{{ route('admin.billing.create') }}"
                                       class="mt-4 inline-flex rounded-lg bg-[var(--app-primary)] px-4 py-2 text-xs font-bold text-[var(--on-accent)]">
                                        Create First Bill
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- MOBILE BILL CARDS --}}
            <div class="space-y-3 p-3 md:hidden">
                @forelse($todayBillRows as $bill)
                    <div class="rounded-xl border border-[var(--app-border)] bg-[var(--app-bg)] p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="break-all text-sm font-bold text-[var(--app-text)]">
                                    {{ $bill->invoice_number }}
                                </p>
                                <p class="mt-1 text-xs text-[var(--app-muted)]">
                                    {{ $bill->billed_at?->timezone('Asia/Kolkata')->format('h:i A') }}
                                </p>
                            </div>
                            <p class="whitespace-nowrap text-sm font-bold text-[var(--app-primary)]">
                                {{ \App\Support\Money::inr($bill->grand_total) }}
                            </p>
                        </div>

                        <div class="mt-3 space-y-1 text-xs text-[var(--app-muted)]">
                            <p>Customer: {{ $bill->customer?->name ?? 'Walk-in customer' }}</p>
                            <p>Payment: {{ ucfirst($bill->payment_status) }}</p>
                            <p>WhatsApp: {{ $bill->whatsapp_invoice_status ?: 'pending' }}</p>
                            <p>Services: {{ $bill->items->pluck('service_name_snapshot')->filter()->join(', ') ?: '—' }}</p>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-3 border-t border-[var(--app-border)] pt-3 text-xs font-bold">
                            <a class="text-[var(--app-primary)]" href="{{ route('admin.billing.show', $bill) }}">View Bill</a>
                            <a class="text-[var(--app-primary)]" href="{{ route('admin.billing.print', $bill) }}">Print</a>
                            <a class="text-[var(--app-primary)]" href="{{ route('admin.billing.pdf', $bill) }}">PDF</a>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center">
                        <p class="font-semibold text-[var(--app-text)]">No bills issued today</p>
                        <p class="mt-1 text-xs text-[var(--app-muted)]">Your completed bills will appear here.</p>
                        <a href="{{ route('admin.billing.create') }}"
                           class="mt-3 inline-flex rounded-lg bg-[var(--app-primary)] px-4 py-2 text-xs font-bold text-[var(--on-accent)]">
                            Create First Bill
                        </a>
                    </div>
                @endforelse
            </div>
        </section>

        {{-- ATTENDANCE --}}
        <section class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] p-4 sm:p-5">
            <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                <div>
                    <h2 class="text-lg font-bold text-[var(--app-text)]">Attendance for {{ $period->start->format('d M Y') }}</h2>
                    <p class="text-xs text-[var(--app-muted)]">Staff check-in and attendance overview</p>
                </div>

                <a href="{{ route('admin.attendance.index', ['date' => $period->start->toDateString()]) }}"
                   class="text-sm font-bold text-[var(--app-primary)] hover:underline">
                    Manage Attendance →
                </a>
            </div>

            {{-- MOBILE --}}
            <div class="mt-4 grid gap-3 md:hidden">
                @forelse($attendanceRows as $row)
                    @php($status = $row['attendance']?->status ?? 'not_marked')

                    <div class="rounded-xl border border-[var(--app-border)] bg-[var(--app-bg)] p-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-semibold text-[var(--app-text)]">
                                {{ $row['staff']->name }}
                            </p>
                            <span class="rounded-full border border-[var(--app-border)] px-2 py-1 text-[10px] font-bold uppercase text-[var(--app-muted)]">
                                {{ str_replace('_', ' ', $status) }}
                            </span>
                        </div>
                        <p class="mt-2 text-xs text-[var(--app-muted)]">
                            Shift: {{ $row['staff']->shift_start ?: 'Not set' }} -
                            {{ $row['staff']->shift_end ?: 'Not set' }}
                        </p>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-[var(--app-muted)]">No staff records available.</p>
                @endforelse
            </div>

            {{-- DESKTOP --}}
            <div class="mt-4 hidden overflow-x-auto md:block">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-[var(--app-border)] text-xs uppercase text-[var(--app-muted)]">
                        <tr>
                            <th class="py-3 pr-4">Staff</th>
                            <th class="pr-4">Shift</th>
                            <th class="pr-4">Status</th>
                            <th class="pr-4">Check In</th>
                            <th>Check Out</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--app-border)] text-[var(--app-text)]">
                        @forelse($attendanceRows as $row)
                            @php($attendance = $row['attendance'])
                            <tr>
                                <td class="py-3 pr-4 font-semibold">{{ $row['staff']->name }}</td>
                                <td class="pr-4">
                                    {{ $row['staff']->shift_start ?: 'Not set' }} -
                                    {{ $row['staff']->shift_end ?: 'Not set' }}
                                </td>
                                <td class="pr-4 capitalize">
                                    {{ str_replace('_', ' ', $attendance?->status ?? 'not_marked') }}
                                </td>
                                <td class="pr-4">{{ $attendance?->check_in_time ?: '-' }}</td>
                                <td>{{ $attendance?->check_out_time ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-[var(--app-muted)]">
                                    No staff records available.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- RECENT INVOICES AND TOP SERVICES --}}
        <section class="grid gap-5 xl:grid-cols-5">

            {{-- RECENT INVOICES --}}
            <div class="overflow-hidden rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] xl:col-span-3">
                <div class="flex items-center justify-between gap-3 border-b border-[var(--app-border)] p-4 sm:p-5">
                    <div>
                        <h2 class="text-lg font-bold text-[var(--app-text)]">Recent Invoices</h2>
                        <p class="text-xs text-[var(--app-muted)]">Latest billing activity</p>
                    </div>
                    <a href="{{ route('admin.billing.index', $rangeQuery) }}"
                       class="text-xs font-bold text-[var(--app-primary)] hover:underline">
                        All Reports →
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-[var(--app-bg)] text-xs text-[var(--app-muted)]">
                            <tr>
                                <th class="px-4 py-3">Invoice</th>
                                <th class="px-4 py-3">Customer</th>
                                <th class="px-4 py-3">Total</th>
                                <th class="px-4 py-3">Payment</th>
                                <th class="px-4 py-3">WhatsApp</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--app-border)] text-[var(--app-text)]">
                            @forelse($recentBills as $bill)
                                <tr class="hover:bg-[var(--app-bg)]">
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <a class="font-bold text-[var(--app-primary)] hover:underline"
                                           href="{{ route('admin.billing.show', $bill) }}">
                                            {{ $bill->invoice_number }}
                                        </a>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        {{ $bill->customer?->name ?? 'Walk-in customer' }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold">
                                        {{ \App\Support\Money::inr($bill->grand_total) }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <span class="capitalize">{{ $bill->payment_status }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-xs">
                                        {{ $bill->whatsapp_invoice_status ?: 'pending' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-[var(--app-muted)]">
                                        No invoices yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- TOP SERVICES --}}
            <div class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] p-4 sm:p-5 xl:col-span-2">
                <div class="mb-4">
                    <h2 class="text-lg font-bold text-[var(--app-text)]">Top Services</h2>
                    <p class="text-xs text-[var(--app-muted)]">{{ $period->label() }} · ranked by quantity served</p>
                </div>

                <div class="space-y-4">
                    @forelse($topServicesToday as $service)
                        <div class="flex items-center justify-between gap-3 border-b border-[var(--app-border)] pb-3 last:border-0 last:pb-0">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-[var(--app-text)]">
                                    {{ $service->service_name_snapshot }}
                                </p>
                                <p class="mt-1 text-xs text-[var(--app-muted)]">
                                    {{ $service->quantity }} served
                                </p>
                            </div>
                            <p class="whitespace-nowrap text-sm font-bold text-[var(--app-primary)]">
                                {{ \App\Support\Money::inr($service->total) }}
                            </p>
                        </div>
                    @empty
                        <p class="py-8 text-center text-sm text-[var(--app-muted)]">
                            Service performance will appear after the first bill.
                        </p>
                    @endforelse
                </div>
            </div>
        </section>

        <footer class="border-t border-[var(--app-border)] pt-4 text-center text-xs text-[var(--app-muted)]">
            5 Star New Look Salon · SalonOS Administration
        </footer>

    </div>
</x-app-layout>
