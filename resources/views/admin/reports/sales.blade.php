<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--app-subtle)]">Business performance</p>
            <h1 class="font-serif text-2xl text-[var(--app-primary)] sm:text-3xl">Monthly Sales Report</h1>
            <p class="text-sm text-[var(--app-muted)]">{{ $monthLabel }} · All amounts in Indian rupees</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        <x-admin.card>
            <form method="GET" action="{{ route('admin.reports.sales') }}" class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <label for="sales-report-month" class="mb-1 block text-sm font-semibold text-[var(--app-muted)]">Report month</label>
                    <input
                        id="sales-report-month"
                        name="month"
                        type="month"
                        value="{{ $month }}"
                        max="{{ now($timezone)->format('Y-m') }}"
                        onchange="this.form.requestSubmit()"
                        class="min-h-11 rounded-xl border border-[var(--app-border)] bg-[var(--app-bg)] px-3 py-2 text-[var(--app-text)]"
                    >
                </div>
                <nav class="flex items-center gap-2" aria-label="Change report month">
                    <a href="{{ route('admin.reports.sales', ['month' => $previousMonth]) }}" class="rounded-xl border border-[var(--app-border)] px-4 py-2.5 text-sm font-semibold text-[var(--app-text)] hover:border-[var(--app-primary)]" aria-label="Previous month">← Previous</a>
                    @if ($nextMonth <= now($timezone)->format('Y-m'))
                        <a href="{{ route('admin.reports.sales', ['month' => $nextMonth]) }}" class="rounded-xl border border-[var(--app-border)] px-4 py-2.5 text-sm font-semibold text-[var(--app-text)] hover:border-[var(--app-primary)]" aria-label="Next month">Next →</a>
                    @endif
                </nav>
            </form>
            @error('month')
                <p class="mt-3 text-sm text-red-400" role="alert">Choose a valid report month.</p>
            @enderror
        </x-admin.card>

        <section class="grid gap-4 sm:grid-cols-3" aria-label="Monthly sales summary">
            <x-admin.card>
                <p class="text-sm font-medium text-[var(--app-muted)]">Total Sales</p>
                <p class="mt-2 text-3xl font-semibold text-[var(--app-primary)]">{{ $moneyFormatter($totalSales) }}</p>
            </x-admin.card>
            <x-admin.card>
                <p class="text-sm font-medium text-[var(--app-muted)]">Total Bills</p>
                <p class="mt-2 text-3xl font-semibold text-[var(--app-text)]">{{ number_format($totalBills) }}</p>
            </x-admin.card>
            <x-admin.card>
                <p class="text-sm font-medium text-[var(--app-muted)]">Active Staff</p>
                <p class="mt-2 text-3xl font-semibold text-[var(--app-text)]">{{ number_format($activeStaffCount) }}</p>
            </x-admin.card>
        </section>

        <x-admin.card>
            <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
                <div>
                    <h2 class="font-serif text-xl text-[var(--app-primary)]">Staff Performance</h2>
                    <p class="mt-1 max-w-3xl text-xs text-[var(--app-subtle)]">{{ $salesAttribution }}</p>
                </div>
                <p class="text-xs text-[var(--app-subtle)]">Period uses {{ $timezone }}</p>
            </div>

            <div class="mt-5 hidden overflow-hidden rounded-xl border border-[var(--app-border)] md:block">
                <table class="w-full divide-y divide-[var(--app-border)] text-left text-sm">
                    <thead class="bg-[var(--app-bg)] text-[var(--app-muted)]">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold">Staff / Biller</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">Bills</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">Sales</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">Monthly sales</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--app-border)]">
                        @forelse ($staffRows as $row)
                            <tr>
                                <th scope="row" class="px-4 py-3 font-medium text-[var(--app-text)]">
                                    {{ $row->staff_name }}
                                    @if ($row->role !== 'staff')
                                        <span class="ml-1 text-xs font-normal text-[var(--app-subtle)]">({{ ucfirst($row->role) }} biller)</span>
                                    @endif
                                </th>
                                <td class="px-4 py-3 text-right tabular-nums">{{ number_format($row->bill_count) }}</td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums">{{ $moneyFormatter($row->total_sales) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ number_format($row->sales_percentage, 1) }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-[var(--app-muted)]">No active staff or completed bills for this month.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="border-t-2 border-[var(--app-border)] bg-[var(--app-bg)] font-bold text-[var(--app-text)]">
                        <tr>
                            <th scope="row" class="px-4 py-3">TOTAL</th>
                            <td class="px-4 py-3 text-right tabular-nums">{{ number_format($totalBills) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $moneyFormatter($totalSales) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $totalSales > 0 ? '100.0%' : '0.0%' }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mt-5 space-y-3 md:hidden">
                @forelse ($staffRows as $row)
                    <article class="rounded-xl border border-[var(--app-border)] bg-[var(--app-bg)] p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="font-semibold text-[var(--app-text)]">{{ $row->staff_name }}</h3>
                                @if ($row->role !== 'staff')
                                    <p class="text-xs text-[var(--app-subtle)]">{{ ucfirst($row->role) }} biller</p>
                                @endif
                            </div>
                            <p class="text-right text-lg font-semibold text-[var(--app-primary)]">{{ $moneyFormatter($row->total_sales) }}</p>
                        </div>
                        <div class="mt-3 flex justify-between border-t border-[var(--app-border)] pt-3 text-sm text-[var(--app-muted)]">
                            <span>{{ number_format($row->bill_count) }} {{ $row->bill_count === 1 ? 'bill' : 'bills' }}</span>
                            <span>{{ number_format($row->sales_percentage, 1) }}% of monthly sales</span>
                        </div>
                    </article>
                @empty
                    <p class="py-6 text-center text-sm text-[var(--app-muted)]">No active staff or completed bills for this month.</p>
                @endforelse
                <article class="rounded-xl border-2 border-[var(--app-border)] p-4 font-bold">
                    <div class="flex justify-between gap-3"><span>TOTAL SALES</span><span>{{ $moneyFormatter($totalSales) }}</span></div>
                    <div class="mt-2 flex justify-between text-sm"><span>{{ number_format($totalBills) }} bills</span><span>{{ $totalSales > 0 ? '100.0%' : '0.0%' }}</span></div>
                </article>
            </div>
        </x-admin.card>
    </div>
</x-app-layout>
