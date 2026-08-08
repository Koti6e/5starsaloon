<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
            <div>
                <h1 class="font-serif text-2xl text-[#f4d27a]">Reports</h1>
                <p class="mt-1 text-sm text-[#d8c8a3]">Production sales, payments, and service movement.</p>
            </div>
            <a href="{{ route('admin.billing.create') }}" class="rounded-md bg-[#d5a93b] px-4 py-3 text-sm font-bold text-black">New Bill</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['Today Sales', $moneyFormatter($todaySales)],
                    ['Today Bills', $todayBills],
                    ['Month Sales', $moneyFormatter($monthSales)],
                    ['Month Bills', $monthBills],
                ] as [$label, $value])
                    <x-admin.card class="h-full">
                        <p class="text-sm text-[#d8c8a3]">{{ $label }}</p>
                        <p class="mt-2 text-3xl font-semibold text-[#fff9ea]">{{ $value }}</p>
                    </x-admin.card>
                @endforeach
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="rounded-lg border border-[#c8a24a]/20 bg-[#11100d] p-5">
                    <h2 class="font-serif text-xl text-[#f4d27a]">Payment Breakdown</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-[#c8a24a]/15 text-sm">
                            <thead class="text-left text-[#f4d27a]"><tr><th class="py-3">Method</th><th>Payments</th><th class="text-right">Amount</th></tr></thead>
                            <tbody class="divide-y divide-[#c8a24a]/10 text-[#f8efd8]">
                                @forelse ($paymentBreakdown as $row)
                                    <tr>
                                        <td class="py-3 uppercase">{{ str_replace('_', ' ', $row->payment_method) }}</td>
                                        <td>{{ $row->count }}</td>
                                        <td class="text-right">{{ $moneyFormatter($row->total) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="py-4 text-[#a89567]">No payments recorded this month.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="rounded-lg border border-[#c8a24a]/20 bg-[#11100d] p-5">
                    <h2 class="font-serif text-xl text-[#f4d27a]">Top Services</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-[#c8a24a]/15 text-sm">
                            <thead class="text-left text-[#f4d27a]"><tr><th class="py-3">Service</th><th>Qty</th><th class="text-right">Sales</th></tr></thead>
                            <tbody class="divide-y divide-[#c8a24a]/10 text-[#f8efd8]">
                                @forelse ($topServices as $row)
                                    <tr>
                                        <td class="py-3">{{ $row->service_name_snapshot }}</td>
                                        <td>{{ $row->quantity }}</td>
                                        <td class="text-right">{{ $moneyFormatter($row->total) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="py-4 text-[#a89567]">No services billed this month.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <section class="rounded-lg border border-[#c8a24a]/20 bg-[#11100d] p-5">
                <h2 class="font-serif text-xl text-[#f4d27a]">Recent Bills</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-[#c8a24a]/15 text-sm">
                        <thead class="text-left text-[#f4d27a]"><tr><th class="py-3">Invoice</th><th>Customer</th><th>Billed By</th><th>Payment</th><th class="text-right">Total</th></tr></thead>
                        <tbody class="divide-y divide-[#c8a24a]/10 text-[#f8efd8]">
                            @forelse ($recentBills as $bill)
                                <tr>
                                    <td class="py-3"><a href="{{ route('admin.billing.show', $bill) }}" class="font-semibold text-[#f4d27a]">{{ $bill->invoice_number }}</a></td>
                                    <td>{{ $bill->customer?->name }}</td>
                                    <td>{{ $bill->billedBy?->name }}</td>
                                    <td>{{ strtoupper($bill->payments->pluck('payment_method')->join(' + ')) }}</td>
                                    <td class="text-right">{{ $moneyFormatter($bill->grand_total) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-4 text-[#a89567]">No bills have been created yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
