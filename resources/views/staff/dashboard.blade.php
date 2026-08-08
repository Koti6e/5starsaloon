<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm text-[#d8c8a3]">{{ $greeting }},</p>
                <h1 class="font-serif text-2xl text-[#f4d27a]">Staff Dashboard</h1>
            </div>
            <a href="{{ route('staff.billing.create') }}" class="rounded-md bg-[#d5a93b] px-4 py-2 text-sm font-semibold text-black">Create New Bill</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['Shift Status', str_replace('_', ' ', $attendance?->status ?? 'Not marked')],
                    ['Checked In', $attendance?->check_in_time ? \Illuminate\Support\Carbon::parse($attendance->check_in_time)->format('h:i A') : '-'],
                    ['Today’s Date', $today->format('d M Y')],
                    ['Today’s Bills', $todayBills],
                ] as [$label, $value])
                    <div class="rounded-lg border border-[#c8a24a]/20 bg-[#11100d] p-5">
                        <p class="text-sm text-[#d8c8a3]">{{ $label }}</p>
                        <p class="mt-2 text-2xl font-semibold capitalize text-[#fff9ea]">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <section class="rounded-lg border border-[#c8a24a]/20 bg-[#11100d] p-6">
                <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#c8a24a]">ELITE BILLING DESK</p>
                        <h2 class="mt-2 font-serif text-3xl text-[#fff9ea]">{{ auth()->user()->name }}</h2>
                        <p class="mt-3 max-w-2xl text-[#d8c8a3]">Welcome back to 5 Star New Look Salon.</p>
                        <p class="mt-1 max-w-2xl text-[#d8c8a3]">Have a wonderful working day!</p>
                        <a href="{{ route('staff.billing.create') }}" class="mt-6 inline-flex rounded-md bg-[#d5a93b] px-5 py-3 text-sm font-bold uppercase tracking-[0.18em] text-black">Create New Bill</a>
                    </div>
                    <div class="rounded-lg border border-[#c8a24a]/15 bg-black p-5">
                        <p class="text-sm text-[#d8c8a3]">Today’s Sales</p>
                        <p class="mt-2 text-3xl font-semibold text-[#f4d27a]">{{ \App\Support\Money::inr($todaySales) }}</p>
                        <p class="mt-5 text-sm text-[#d8c8a3]">Assigned Appointments</p>
                        <p class="mt-2 text-3xl font-semibold text-[#fff9ea]">{{ $assignedAppointments }}</p>
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-[#c8a24a]/20 bg-[#11100d] p-5">
                <div class="flex items-center justify-between">
                    <h2 class="font-serif text-xl text-[#f4d27a]">Recent Bills</h2>
                    <a href="{{ route('staff.billing.create') }}" class="text-sm font-semibold text-[#f4d27a]">New bill</a>
                </div>
                <div class="mt-5 space-y-3">
                    @forelse ($recentBills as $bill)
                        <a href="{{ route('staff.billing.show', $bill) }}" class="grid gap-2 rounded-md border border-[#c8a24a]/15 bg-black p-4 sm:grid-cols-[1fr_auto_auto] sm:items-center">
                            <div>
                                <p class="font-semibold text-[#fff9ea]">{{ $bill->invoice_number }}</p>
                                <p class="text-sm text-[#d8c8a3]">{{ $bill->customer->name }} - {{ $bill->customer->mobile }}</p>
                            </div>
                            <p class="text-sm text-[#a89567]">{{ $bill->billed_at->timezone('Asia/Kolkata')->format('d M, h:i A') }}</p>
                            <p class="font-semibold text-[#f4d27a]">{{ \App\Support\Money::inr($bill->grand_total) }}</p>
                        </a>
                    @empty
                        <p class="rounded-md border border-[#c8a24a]/15 bg-black p-4 text-[#d8c8a3]">No bills created today.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
