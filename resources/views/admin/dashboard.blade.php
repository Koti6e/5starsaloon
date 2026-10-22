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
        <div class="flex flex-col gap-1">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--app-subtle)]">{{ $greeting }}</p>
            <h1 class="font-serif text-2xl text-[var(--app-primary)] sm:text-3xl">Welcome back, {{ auth()->user()->name }} 👋</h1>
            <p class="text-sm text-[var(--app-muted)]">Here's how your salon is performing today.</p>
        </div>
    </x-slot>

    <div class="py-4 sm:py-5">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
                @foreach ([
                    ['Today\'s Revenue', \App\Support\Money::inr($todaySales), route('admin.reports.index')],
                    ['Bills', $todayBills, route('admin.billing.create')],
                    ['Customers Served', $customersServed, route('admin.customers.index')],
                    ['Average Bill', \App\Support\Money::inr($averageBillValue), route('admin.reports.index')],
                    ['Appointments', $todayAppointments, route('admin.appointments.index')],
                    ['Staff Present', $staffPresentToday, route('admin.attendance.index')],
                ] as [$label, $value, $href])
                    <x-admin.card class="h-full !p-4 transition hover:border-[var(--accent-border)]">
                        <a href="{{ $href }}" class="block">
                            <p class="text-sm text-[var(--app-muted)]">{{ $label }}</p>
                            <p class="mt-2 text-2xl font-semibold text-[var(--app-text)] sm:text-3xl">{{ $value }}</p>
                        </a>
                    </x-admin.card>
                @endforeach
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([['Cash', $cashSales], ['UPI', $upiSales], ['Card', $cardSales], ['Pending Home Visits', $pendingHomeVisits]] as [$label, $value])
                    <x-admin.card class="h-full !p-4">
                        <p class="text-sm text-[var(--app-muted)]">{{ $label }}</p>
                        <p class="mt-2 text-xl font-semibold text-[var(--app-text)]">{{ is_numeric($value) && $label !== 'Pending Home Visits' ? \App\Support\Money::inr($value) : $value }}</p>
                    </x-admin.card>
                @endforeach
            </div>

            <x-admin.card>
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h2 class="font-serif text-2xl text-[var(--app-primary)]">Quick Actions</h2>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-4">
                        <a href="{{ route('admin.billing.create') }}" class="rounded-xl bg-[var(--app-primary)] px-4 py-3 text-center text-sm font-semibold text-[var(--on-accent)]">Quick Billing</a>
                        <a href="{{ route('admin.appointments.index') }}" class="rounded-xl border border-[var(--app-border)] px-4 py-3 text-center text-sm font-semibold text-[var(--app-text)]">Appointments</a>
                        <a href="{{ route('admin.customers.create') }}" class="rounded-xl border border-[var(--app-border)] px-4 py-3 text-center text-sm font-semibold text-[var(--app-text)]">Add Customer</a>
                        <a href="{{ route('admin.attendance.index') }}" class="rounded-xl border border-[var(--app-border)] px-4 py-3 text-center text-sm font-semibold text-[var(--app-text)]">Mark Attendance</a>
                    </div>
                </div>
            </x-admin.card>

            <x-admin.card class="!p-4">
                <div class="flex flex-wrap items-center justify-between gap-2"><div><h2 class="font-serif text-lg text-[var(--app-primary)]">Today's Bills</h2><p class="text-xs text-[var(--app-muted)]">Completed bills issued today · {{ $todayBills }} bills · {{ \App\Support\Money::inr($todaySales) }}</p></div><div class="flex gap-4"><a href="{{ route('admin.reports.sales', ['period' => 'daily', 'date' => now('Asia/Kolkata')->toDateString()]) }}" class="text-sm font-semibold text-[var(--app-primary)]">View all</a><a href="{{ route('admin.billing.create') }}" class="text-sm font-semibold text-[var(--app-primary)]">Quick Billing</a></div></div>
                <div class="mt-3 overflow-x-auto"><table class="w-full min-w-[760px] text-left text-sm"><thead class="text-xs text-[var(--app-muted)]"><tr><th class="py-2">Time / Invoice</th><th>Customer</th><th>Services</th><th>Payment</th><th>WhatsApp</th><th class="text-right">Amount</th><th class="text-right">Actions</th></tr></thead><tbody class="divide-y divide-[var(--app-border)]">
                    @forelse($todayBillRows as $bill)<tr><td class="py-2"><span class="block">{{ $bill->billed_at?->timezone('Asia/Kolkata')->format('H:i') }}</span><span class="text-xs text-[var(--app-subtle)]">{{ $bill->invoice_number }}</span></td><td>{{ $bill->customer?->name }}</td><td>{{ $bill->items->pluck('service_name_snapshot')->filter()->join(', ') }}</td><td>{{ ucfirst($bill->payment_status) }}</td><td><span class="rounded-full border border-[var(--app-border)] px-2 py-1 text-xs">{{ $bill->whatsapp_invoice_status ?: 'pending' }}</span></td><td class="text-right font-semibold">{{ \App\Support\Money::inr($bill->grand_total) }}</td><td class="text-right"><a class="text-[var(--app-primary)]" href="{{ route('admin.billing.show', $bill) }}">View</a> · <a class="text-[var(--app-primary)]" href="{{ route('admin.billing.print', $bill) }}">Print</a> · <a class="text-[var(--app-primary)]" href="{{ route('admin.billing.pdf', $bill) }}">PDF</a></td></tr>@empty<tr><td colspan="7" class="py-5 text-center text-sm text-[var(--app-muted)]">No completed bills issued today.</td></tr>@endforelse
                </tbody></table></div>
            </x-admin.card>

            <x-admin.card>
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <h2 class="font-serif text-2xl text-[var(--app-primary)]">Today's Attendance</h2>
                    <a href="{{ route('admin.attendance.index') }}" class="text-sm font-semibold text-[var(--app-primary)]">Manage attendance</a>
                </div>
                <div class="mt-5 grid gap-3 md:hidden">
                    @foreach ($attendanceRows as $row)
                        @php($status = $row['attendance']?->status ?? 'not_marked')
                        <div class="rounded-xl border border-[var(--app-border)] bg-[var(--app-bg)] p-4">
                            <div class="flex items-center justify-between">
                                <p class="font-semibold text-[var(--app-text)]">{{ $row['staff']->name }}</p>
                                <span class="text-xs uppercase text-[var(--app-muted)]">{{ str_replace('_', ' ', $status) }}</span>
                            </div>
                            <p class="mt-2 text-xs text-[var(--app-muted)]">{{ $row['staff']->shift_start ?: 'Shift not set' }} - {{ $row['staff']->shift_end ?: 'Shift not set' }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="mt-5 hidden overflow-x-auto md:block">
                    <table class="min-w-full divide-y divide-[var(--app-border)] text-sm">
                        <thead class="text-left text-[var(--app-muted)]"><tr><th class="py-3">Staff</th><th>Shift</th><th>Status</th><th>Check in</th><th>Check out</th></tr></thead>
                        <tbody class="divide-y divide-[var(--app-border)] text-[var(--app-text)]">
                            @foreach ($attendanceRows as $row)
                                @php($attendance = $row['attendance'])
                                <tr>
                                    <td class="py-3">{{ $row['staff']->name }}</td>
                                    <td>{{ $row['staff']->shift_start ?: 'Not set' }} - {{ $row['staff']->shift_end ?: 'Not set' }}</td>
                                    <td>{{ str_replace('_', ' ', $attendance?->status ?? 'not_marked') }}</td>
                                    <td>{{ $attendance?->check_in_time ?: '-' }}</td>
                                    <td>{{ $attendance?->check_out_time ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-admin.card>

            <div class="grid gap-5 xl:grid-cols-5">
                <x-admin.card class="xl:col-span-3">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h2 class="font-serif text-2xl text-[var(--app-primary)]">Recent Invoices</h2>
                        <a href="{{ route('admin.reports.index') }}" class="text-sm font-semibold text-[var(--app-primary)]">All reports</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="border-b border-[var(--app-border)] text-[var(--app-muted)]"><tr><th class="py-3 pr-4">Invoice</th><th class="pr-4">Customer</th><th class="pr-4">Total</th><th class="pr-4">Payment</th><th>WhatsApp</th></tr></thead>
                            <tbody class="divide-y divide-[var(--app-border)] text-[var(--app-text)]">
                                @forelse ($recentBills as $bill)
                                    <tr>
                                        <td class="whitespace-nowrap py-3 pr-4"><a class="font-semibold text-[var(--app-primary)] hover:underline" href="{{ route('admin.billing.show', $bill) }}">{{ $bill->invoice_number }}</a></td>
                                        <td class="whitespace-nowrap pr-4">{{ $bill->customer?->name ?? 'Walk-in customer' }}</td>
                                        <td class="whitespace-nowrap pr-4">{{ \App\Support\Money::inr($bill->grand_total) }}</td>
                                        <td class="whitespace-nowrap pr-4"><span class="inline-flex rounded-full border border-emerald-400/30 bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold capitalize text-emerald-700">{{ $bill->payment_status }}</span></td>
                                        @php($deliveryStatus = $bill->whatsapp_invoice_status ?: 'pending')
                                        @php($deliveryTone = in_array($deliveryStatus, ['delivered', 'read'], true) ? 'border-emerald-400/30 bg-emerald-500/10 text-emerald-700' : (in_array($deliveryStatus, ['accepted', 'sent'], true) ? 'border-amber-400/40 bg-amber-500/10 text-amber-800' : ($deliveryStatus === 'failed' ? 'border-rose-400/40 bg-rose-500/10 text-rose-700' : 'border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-muted)]')))
                                        <td class="whitespace-nowrap"><span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $deliveryTone }}">{{ $deliveryStatus }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="py-6 text-center text-[var(--app-muted)]">No invoices yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-admin.card>

                <x-admin.card class="xl:col-span-2">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h2 class="font-serif text-2xl text-[var(--app-primary)]">Top Services Today</h2>
                        <span class="text-xs text-[var(--app-muted)]">By quantity</span>
                    </div>
                    <div class="space-y-3">
                        @forelse ($topServicesToday as $service)
                            <div class="flex items-center justify-between gap-3 border-b border-[var(--app-border)] pb-3 last:border-0">
                                <div class="min-w-0"><p class="truncate font-medium text-[var(--app-text)]">{{ $service->service_name_snapshot }}</p><p class="text-xs text-[var(--app-muted)]">{{ $service->quantity }} served</p></div>
                                <p class="whitespace-nowrap text-sm font-semibold text-[var(--app-text)]">{{ \App\Support\Money::inr($service->total) }}</p>
                            </div>
                        @empty
                            <p class="py-6 text-center text-sm text-[var(--app-muted)]">Service performance will appear after the first bill.</p>
                        @endforelse
                    </div>
                </x-admin.card>
            </div>
        </div>
    </div>
</x-app-layout>
