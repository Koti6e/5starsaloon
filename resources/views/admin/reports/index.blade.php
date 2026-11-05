<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--app-subtle)]">Business reporting</p>
                <h1 class="mt-1 font-serif text-2xl text-[var(--app-primary)]">Reports</h1>
                <p class="mt-1 text-sm text-[var(--app-muted)]">{{ $periodLabel }} · INR · {{ $timezone }}</p>
            </div>
            <a href="{{ route('admin.billing.create') }}" class="rounded-lg bg-[var(--app-primary)] px-4 py-3 text-sm font-semibold text-[var(--on-accent)]">New Bill</a>
        </div>
    </x-slot>

    <div class="space-y-5">
        <x-admin.card class="!p-4">
            <nav class="mb-4 flex flex-wrap gap-2" aria-label="Reports period">
                @foreach (['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $key => $label)
                    <a href="{{ route('admin.reports.index', ['period' => $key, $key === 'daily' ? 'date' : ($key === 'weekly' ? 'week' : 'month') => $key === 'daily' ? now($timezone)->toDateString() : ($key === 'weekly' ? now($timezone)->startOfWeek()->toDateString() : now($timezone)->format('Y-m'))]) }}"
                       @class([
                           'rounded-lg border px-3 py-2 text-sm font-semibold',
                           'border-[var(--accent-border)] bg-[var(--app-primary-soft)] text-[var(--app-primary)]' => $period === $key,
                           'border-[var(--app-border)] text-[var(--app-muted)] hover:bg-[var(--app-bg)]' => $period !== $key,
                       ])>{{ $label }}</a>
                @endforeach
            </nav>
            <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-wrap items-end gap-3">
                <input type="hidden" name="period" value="{{ $period }}">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[var(--app-muted)]" for="reports-period-value">{{ ucfirst($period) }}</label>
                    @if ($period === 'daily')
                        <input id="reports-period-value" type="date" name="date" value="{{ $selectedDate }}" max="{{ now($timezone)->toDateString() }}" class="min-h-11 rounded-lg border border-[var(--app-border)] bg-[var(--app-bg)] px-3 text-[var(--app-text)]">
                    @elseif ($period === 'weekly')
                        <input id="reports-period-value" type="date" name="week" value="{{ $week }}" max="{{ now($timezone)->toDateString() }}" class="min-h-11 rounded-lg border border-[var(--app-border)] bg-[var(--app-bg)] px-3 text-[var(--app-text)]">
                    @else
                        <input id="reports-period-value" type="month" name="month" value="{{ $month }}" max="{{ now($timezone)->format('Y-m') }}" class="min-h-11 rounded-lg border border-[var(--app-border)] bg-[var(--app-bg)] px-3 text-[var(--app-text)]">
                    @endif
                </div>
                <button class="min-h-11 rounded-lg bg-[var(--app-primary)] px-4 text-sm font-semibold text-[var(--on-accent)]">View report</button>
            </form>
        </x-admin.card>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5" aria-label="Selected period totals">
            @foreach ([
                ['Total Sales', $moneyFormatter($totalSales)],
                ['Total Bills', number_format($totalBills)],
                ['Cash Payments', $moneyFormatter($paymentTotals['cash'])],
                ['UPI Payments', $moneyFormatter($paymentTotals['upi'])],
                ['Card Payments', $moneyFormatter($paymentTotals['card'])],
            ] as [$label, $value])
                <x-admin.card class="h-full !p-4">
                    <p class="text-sm text-[var(--app-muted)]">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-semibold tabular-nums text-[var(--app-text)]">{{ $value }}</p>
                </x-admin.card>
            @endforeach
        </section>

        <a href="{{ route('admin.backdated-billing.create') }}" class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-[var(--accent-border)] bg-[var(--app-surface)] p-4 shadow-[var(--shadow-card)] transition hover:border-[var(--app-primary)]">
            <span><span class="block font-semibold text-[var(--app-text)]">Backdated Billing</span><span class="text-sm text-[var(--app-muted)]">Record a completed service for any previous business date. Creation time remains auditable.</span></span>
            <span class="rounded-lg bg-[var(--app-primary)] px-4 py-2 text-sm font-semibold text-[var(--on-accent)]">Create backdated bill →</span>
        </a>

        <div class="grid gap-5 xl:grid-cols-2">
            <x-admin.card>
                <h2 class="font-serif text-xl text-[var(--app-primary)]">Payment Breakdown</h2>
                <p class="mt-1 text-sm text-[var(--app-muted)]">Collections recorded during this period.</p>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--app-border)] text-sm">
                        <thead class="text-left text-xs uppercase tracking-wide text-[var(--app-muted)]"><tr><th class="py-3">Method</th><th>Payments</th><th class="text-right">Amount</th></tr></thead>
                        <tbody class="divide-y divide-[var(--app-border)] text-[var(--app-text)]">
                            @forelse ($paymentBreakdown as $row)
                                <tr><td class="py-3 uppercase">{{ str_replace('_', ' ', $row->payment_method) }}</td><td>{{ $row->count }}</td><td class="text-right font-semibold">{{ $moneyFormatter($row->total) }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="py-4 text-[var(--app-muted)]">No payments recorded during this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-admin.card>

            <x-admin.card>
                <h2 class="font-serif text-xl text-[var(--app-primary)]">Top Services</h2>
                <p class="mt-1 text-sm text-[var(--app-muted)]">Quantities and revenue from completed bills dated in this period.</p>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--app-border)] text-sm">
                        <thead class="text-left text-xs uppercase tracking-wide text-[var(--app-muted)]"><tr><th class="py-3">Service</th><th>Quantity</th><th class="text-right">Revenue</th></tr></thead>
                        <tbody class="divide-y divide-[var(--app-border)] text-[var(--app-text)]">
                            @forelse ($topServices as $row)
                                <tr><td class="py-3">{{ $row->service_name_snapshot }}</td><td>{{ $row->quantity }}</td><td class="text-right font-semibold">{{ $moneyFormatter($row->total) }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="py-4 text-[var(--app-muted)]">No services billed during this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-admin.card>
        </div>

        <x-admin.card>
            <h2 class="font-serif text-xl text-[var(--app-primary)]">Staff Performance</h2>
            <p class="mt-1 text-sm text-[var(--app-muted)]">Completed bills attributed to each biller during this period.</p>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-[var(--app-border)] text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-[var(--app-muted)]"><tr><th class="py-3">Staff / Biller</th><th>Bills</th><th class="text-right">Sales</th></tr></thead>
                    <tbody class="divide-y divide-[var(--app-border)] text-[var(--app-text)]">
                        @forelse ($staffPerformance as $row)
                            <tr><td class="py-3">{{ $row->staff_name }}</td><td>{{ $row->bill_count }}</td><td class="text-right font-semibold">{{ $moneyFormatter($row->total_sales) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="py-4 text-[var(--app-muted)]">No completed bills for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-admin.card>

        <x-admin.card>
            <h2 class="font-serif text-xl text-[var(--app-primary)]">Bills in this period</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-[var(--app-border)] text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-[var(--app-muted)]"><tr><th class="py-3">Date</th><th>Invoice</th><th>Customer</th><th>Billed By</th><th>Payment</th><th class="text-right">Total</th></tr></thead>
                    <tbody class="divide-y divide-[var(--app-border)] text-[var(--app-text)]">
                        @forelse ($recentBills as $bill)
                            <tr><td class="py-3">{{ $bill->billed_at?->timezone($timezone)->format('d M Y') }}</td><td><a href="{{ route('admin.billing.show', $bill) }}" class="font-semibold text-[var(--app-primary)]">{{ $bill->invoice_number }}</a></td><td>{{ $bill->customer?->name }}</td><td>{{ $bill->billedBy?->name }}</td><td>{{ strtoupper($bill->payments->pluck('payment_method')->join(' + ')) }}</td><td class="text-right">{{ $moneyFormatter($bill->grand_total) }}</td></tr>
                        @empty
                            <tr><td colspan="6" class="py-4 text-[var(--app-muted)]">No completed bills in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-admin.card>
    </div>
</x-app-layout>
