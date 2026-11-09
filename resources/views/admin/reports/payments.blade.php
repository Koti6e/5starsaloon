<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--app-subtle)]">Collections</p>
            <h1 class="mt-1 font-serif text-2xl text-[var(--app-primary)]">Payment Report</h1>
            <p class="mt-1 text-sm text-[var(--app-muted)]">Recorded collections by payment date · {{ $period->label() }} · {{ $period->timezone }}</p>
        </div>
    </x-slot>

    <div class="space-y-4">
        <x-admin.period-filter :period="$period" :action="route('admin.reports.payments')" :preserve="['method' => $method]" />

        <x-admin.card>
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <label for="payment-method-filter" class="mb-1 block text-xs font-semibold text-[var(--app-muted)]">Payment method</label>
                    <form id="payment-method-form" method="GET" action="{{ route('admin.reports.payments') }}" class="flex gap-2">
                        @foreach ($period->toQuery() as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
                        <select id="payment-method-filter" name="method" class="min-h-11 rounded-xl border border-[var(--app-border)] bg-[var(--app-bg)] px-3 text-sm text-[var(--app-text)]">
                            <option value="">All methods</option>
                            @foreach (['cash' => 'Cash', 'upi' => 'UPI', 'card' => 'Card', 'other' => 'Other'] as $key => $label)<option value="{{ $key }}" @selected($method === $key)>{{ $label }}</option>@endforeach
                        </select>
                        <button class="min-h-11 rounded-xl bg-[var(--app-primary)] px-4 text-sm font-semibold text-[var(--on-accent)]">Apply</button>
                    </form>
                </div>
                <div class="min-w-52 rounded-xl border border-[var(--accent-border)] bg-[var(--app-primary-soft)] p-4">
                    <p class="text-xs font-semibold text-[var(--app-muted)]">{{ $method ? strtoupper($method).' COLLECTIONS' : 'TOTAL COLLECTIONS' }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-[var(--app-primary)]">{{ \App\Support\Money::inr($selectedTotal) }}</p>
                    <p class="text-xs text-[var(--app-muted)]">{{ number_format($selectedCount) }} payment records</p>
                </div>
            </div>
        </x-admin.card>

        <x-admin.card>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach (['cash' => 'Cash', 'upi' => 'UPI', 'card' => 'Card', 'other' => 'Other'] as $key => $label)
                    @php($collection = $summary['collections']->firstWhere('payment_method', $key))
                    <a href="{{ route('admin.reports.payments', [...$period->toQuery(), 'method' => $key]) }}" class="rounded-xl border border-[var(--app-border)] p-4 transition hover:border-[var(--accent-border)] hover:bg-[var(--app-primary-soft)]">
                        <span class="text-xs font-semibold text-[var(--app-muted)]">{{ $label }}</span>
                        <span class="mt-1 block text-xl font-bold tabular-nums text-[var(--app-text)]">{{ \App\Support\Money::inr($collection->total ?? 0) }}</span>
                        <span class="text-xs text-[var(--app-muted)]">{{ number_format($collection->count ?? 0) }} transactions</span>
                    </a>
                @endforeach
            </div>
            <p class="mt-4 rounded-xl border border-[var(--app-border)] bg-[var(--app-bg)] p-3 text-xs leading-5 text-[var(--app-muted)]">Collections are payment records attached to completed bills, grouped by payment timestamp. The current database has no refund or reversal records, so this report does not claim to net refunds.</p>
        </x-admin.card>

        <x-admin.card class="!p-0">
            <div class="border-b border-[var(--app-border)] px-4 py-3"><h2 class="font-serif text-lg text-[var(--app-primary)]">Payment transactions</h2><p class="text-xs text-[var(--app-muted)]">{{ number_format($payments->total()) }} records in this view</p></div>
            <div class="overflow-x-auto"><table class="min-w-[780px] text-left text-sm">
                <thead class="bg-[var(--app-bg)] text-xs uppercase tracking-wide text-[var(--app-muted)]"><tr><th class="px-4 py-3">Paid at</th><th class="px-3 py-3">Invoice</th><th class="px-3 py-3">Customer</th><th class="px-3 py-3">Method</th><th class="px-3 py-3">Reference / note</th><th class="px-4 py-3 text-right">Amount</th></tr></thead>
                <tbody class="divide-y divide-[var(--app-border)]">
                    @forelse ($payments as $payment)
                        <tr><td class="px-4 py-3">{{ $payment->paid_at?->timezone($period->timezone)->format('d M Y · h:i A') }}</td><td class="px-3 py-3"><a href="{{ route('admin.billing.show', $payment->bill) }}" class="font-semibold text-[var(--app-primary)] hover:underline">{{ $payment->bill?->invoice_number }}</a></td><td class="px-3 py-3">{{ $payment->bill?->customer?->name ?? 'Walk-in customer' }}</td><td class="px-3 py-3 capitalize">{{ $payment->payment_method }}</td><td class="px-3 py-3 text-[var(--app-muted)]">{{ $payment->transaction_reference ?: ($payment->method_note ?: '—') }}</td><td class="px-4 py-3 text-right font-semibold tabular-nums">{{ \App\Support\Money::inr($payment->amount) }}</td></tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-[var(--app-muted)]">No payment records for this period.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
            <div class="border-t border-[var(--app-border)] px-4 py-3">{{ $payments->links() }}</div>
        </x-admin.card>
    </div>
</x-app-layout>
