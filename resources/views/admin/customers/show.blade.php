<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="font-serif text-2xl text-[#f4d27a]">{{ $customer->name }}</h1>
                <p class="mt-1 text-sm text-[#d8c8a3]">+91 {{ $customer->mobile }} · {{ $customer->customer_code }}</p>
            </div>
            <a href="https://wa.me/91{{ $customer->mobile }}" target="_blank" rel="noopener" class="rounded-md bg-[#d5a93b] px-4 py-3 text-center text-sm font-semibold text-black">Open WhatsApp</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['Customer Since', $customer->created_at->timezone('Asia/Kolkata')->format('d M Y')],
                    ['Last Visit', $customer->last_visit_at ? $customer->last_visit_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') : '-'],
                    ['Total Visits', $customer->total_visits],
                    ['Total Spent', \App\Support\Money::inr($customer->total_spent)],
                    ['Favourite Service', $favouriteService ?: '-'],
                    ['Last Staff', $lastStaff ?: '-'],
                    ['Birthday', $customer->date_of_birth ? $customer->date_of_birth->format('d M') : '-'],
                    ['Status', Str::title($customer->status)],
                ] as [$label, $value])
                    <div class="rounded-lg border border-[#c8a24a]/20 bg-[#11100d] p-4">
                        <p class="text-xs uppercase text-[#a89567]">{{ $label }}</p>
                        <p class="mt-2 font-semibold text-[#fff9ea]">{{ $value }}</p>
                    </div>
                @endforeach
            </section>

            <section class="rounded-lg border border-[#c8a24a]/20 bg-[#11100d] p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="font-serif text-xl text-[#f4d27a]">Billing History</h2>
                    <a href="{{ route('admin.billing.create') }}" class="rounded-md border border-[#c8a24a]/40 px-4 py-2 text-sm font-semibold text-[#f8efd8]">New Bill</a>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse ($bills as $bill)
                        <div class="grid gap-3 rounded-md border border-[#c8a24a]/15 bg-black p-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                            <div>
                                <p class="font-semibold text-[#fff9ea]">{{ $bill->invoice_number }}</p>
                                <p class="mt-1 text-sm text-[#d8c8a3]">{{ $bill->billed_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }} · {{ Str::title($bill->payments->pluck('payment_method')->join(' + ')) }} · {{ \App\Support\Money::inr($bill->grand_total) }}</p>
                                <p class="mt-1 text-xs text-[#a89567]">Billed by {{ $bill->billedBy->name }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
                                <a href="{{ route('admin.billing.show', $bill) }}" class="rounded-md border border-[#c8a24a]/40 px-3 py-2 text-center text-xs font-semibold text-[#f8efd8]">View Bill</a>
                                <a href="{{ route('admin.billing.pdf', $bill) }}" class="rounded-md bg-[#d5a93b] px-3 py-2 text-center text-xs font-semibold text-black">Download PDF</a>
                                <a href="{{ route('admin.billing.print', $bill) }}" class="rounded-md border border-[#c8a24a]/40 px-3 py-2 text-center text-xs font-semibold text-[#f8efd8]">Print</a>
                                <a href="{{ route('admin.billing.whatsapp', $bill) }}" class="rounded-md border border-[#c8a24a]/40 px-3 py-2 text-center text-xs font-semibold text-[#f4d27a]">WhatsApp</a>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-md border border-[#c8a24a]/15 bg-black p-4 text-[#d8c8a3]">No bills found for this customer.</p>
                    @endforelse
                </div>

                <div class="mt-5">{{ $bills->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
