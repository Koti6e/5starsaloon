<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--app-subtle)]">Billing history</p>
            <h1 class="mt-1 font-serif text-2xl text-[var(--app-primary)]">Bills Register</h1>
            <p class="mt-1 text-sm text-[var(--app-muted)]">Search invoices and review recorded services, payments, and status.</p>
        </div>
    </x-slot>

    <div class="space-y-4">
        <x-admin.period-filter :period="$period" :action="route('admin.billing.index')" :preserve="['q' => $search, 'payment_method' => $paymentMethod, 'bill_status' => $billStatus]" />

        <x-admin.card>
            <form method="GET" action="{{ route('admin.billing.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(16rem,1fr)_12rem_12rem_auto]">
                @foreach ($period->toQuery() as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
                <div>
                    <label for="bill-search" class="mb-1 block text-xs font-semibold text-[var(--app-muted)]">Find a bill</label>
                    <input id="bill-search" name="q" type="search" value="{{ $search }}" placeholder="Invoice, customer name, or mobile" class="min-h-11 w-full rounded-xl border border-[var(--app-border)] bg-[var(--app-bg)] px-3 text-sm text-[var(--app-text)]">
                </div>
                <div>
                    <label for="bill-payment-method" class="mb-1 block text-xs font-semibold text-[var(--app-muted)]">Payment method</label>
                    <select id="bill-payment-method" name="payment_method" class="min-h-11 w-full rounded-xl border border-[var(--app-border)] bg-[var(--app-bg)] px-3 text-sm text-[var(--app-text)]">
                        <option value="">All methods</option>
                        @foreach (['cash' => 'Cash', 'upi' => 'UPI', 'card' => 'Card', 'other' => 'Other'] as $key => $label)
                            <option value="{{ $key }}" @selected($paymentMethod === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="bill-status" class="mb-1 block text-xs font-semibold text-[var(--app-muted)]">Bill status</label>
                    <select id="bill-status" name="bill_status" class="min-h-11 w-full rounded-xl border border-[var(--app-border)] bg-[var(--app-bg)] px-3 text-sm text-[var(--app-text)]">
                        @foreach (['all' => 'All statuses', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'voided' => 'Voided'] as $key => $label)
                            <option value="{{ $key }}" @selected($billStatus === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button class="min-h-11 rounded-xl bg-[var(--app-primary)] px-4 text-sm font-semibold text-[var(--on-accent)]">Search</button>
                    <a href="{{ route('admin.billing.index', $period->toQuery()) }}" class="inline-flex min-h-11 items-center rounded-xl border border-[var(--app-border)] px-3 text-sm font-semibold text-[var(--app-muted)]">Clear</a>
                </div>
            </form>
        </x-admin.card>

        <x-admin.card class="!p-0">
            <div class="flex items-center justify-between gap-3 border-b border-[var(--app-border)] px-4 py-3 sm:px-5">
                <div><h2 class="font-serif text-lg text-[var(--app-primary)]">{{ $period->label() }}</h2><p class="text-xs text-[var(--app-muted)]">{{ number_format($bills->total()) }} matching bills</p></div>
                <a href="{{ route('admin.billing.create') }}" class="inline-flex min-h-10 items-center rounded-xl bg-[var(--app-primary)] px-4 text-sm font-semibold text-[var(--on-accent)]">New bill</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-[950px] text-left text-sm">
                    <thead class="bg-[var(--app-bg)] text-xs uppercase tracking-wide text-[var(--app-muted)]">
                        <tr><th class="px-4 py-3">Invoice</th><th class="px-3 py-3">Customer</th><th class="px-3 py-3">Services</th><th class="px-3 py-3 text-right">Subtotal</th><th class="px-3 py-3 text-right">Discount</th><th class="px-3 py-3">Payments</th><th class="px-3 py-3">Status</th><th class="px-4 py-3 text-right">Actions</th></tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--app-border)]">
                        @forelse ($bills as $bill)
                            <tr class="hover:bg-[var(--app-primary-soft)]/40">
                                <td class="px-4 py-3"><a href="{{ route('admin.billing.show', $bill) }}" class="font-semibold text-[var(--app-primary)] hover:underline">{{ $bill->invoice_number }}</a><span class="mt-1 block text-xs text-[var(--app-muted)]">{{ $bill->billed_at?->timezone('Asia/Kolkata')->format('d M Y · h:i A') }}</span></td>
                                <td class="px-3 py-3">{{ $bill->customer?->name ?? 'Walk-in customer' }}<span class="mt-1 block text-xs text-[var(--app-muted)]">{{ $bill->customer?->mobile }}</span></td>
                                <td class="max-w-64 px-3 py-3 text-xs text-[var(--app-muted)]">{{ $bill->items->map(fn ($item) => $item->service_name_snapshot.' × '.$item->quantity)->join(', ') ?: '—' }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ \App\Support\Money::inr($bill->subtotal + $bill->home_visit_charge) }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ \App\Support\Money::inr($bill->discount_amount) }}</td>
                                <td class="px-3 py-3"><span class="block font-semibold">{{ \App\Support\Money::inr($bill->grand_total) }}</span><span class="text-xs text-[var(--app-muted)]">{{ $bill->payments->map(fn ($payment) => ucfirst($payment->payment_method))->unique()->join(' + ') ?: 'No payment' }}</span></td>
                                <td class="px-3 py-3"><x-admin.status-badge :tone="$bill->status === 'completed' ? 'success' : ($bill->status === 'cancelled' ? 'danger' : 'neutral')">{{ str_replace('_', ' ', $bill->status) }}</x-admin.status-badge><span class="mt-1 block text-xs capitalize text-[var(--app-muted)]">{{ $bill->payment_status }}</span></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-xs font-semibold"><a class="text-[var(--app-primary)] hover:underline" href="{{ route('admin.billing.show', $bill) }}">View</a><span class="px-1 text-[var(--app-subtle)]">·</span><a class="text-[var(--app-primary)] hover:underline" href="{{ route('admin.billing.print', $bill) }}">Print</a><span class="px-1 text-[var(--app-subtle)]">·</span><a class="text-[var(--app-primary)] hover:underline" href="{{ route('admin.billing.pdf', $bill) }}">PDF</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-14 text-center"><p class="font-semibold">No bills match these filters.</p><p class="mt-1 text-sm text-[var(--app-muted)]">Try another date range or clear a filter.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-[var(--app-border)] px-4 py-3">{{ $bills->links() }}</div>
        </x-admin.card>
    </div>
</x-app-layout>
