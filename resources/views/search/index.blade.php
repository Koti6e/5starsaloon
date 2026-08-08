<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="font-serif text-2xl text-[#f4d27a]">Search SalonOS</h1>
            <p class="mt-1 text-sm text-[#d8c8a3]">Find saved customers, invoices, and service records.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <form action="{{ route($routeRoot.'.search') }}" method="GET" class="rounded-lg border border-[#c8a24a]/20 bg-[#11100d] p-4">
                <label for="search-page-q" class="text-sm font-semibold text-[#f8efd8]">Search</label>
                <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                    <input id="search-page-q" name="q" value="{{ $query }}" type="search" class="min-h-11 flex-1 rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]" placeholder="Customer mobile, invoice number, service name">
                    <button type="submit" class="rounded-md bg-[#d5a93b] px-5 py-3 text-sm font-bold text-black">Search</button>
                </div>
            </form>

            @if ($query === '')
                <div class="rounded-lg border border-[#c8a24a]/20 bg-[#11100d] p-6 text-[#d8c8a3]">Enter a search term to begin.</div>
            @else
                <div class="grid gap-6 lg:grid-cols-3">
                    @if (auth()->user()->isAdmin())
                        <section class="rounded-lg border border-[#c8a24a]/20 bg-[#11100d] p-5">
                            <h2 class="font-serif text-xl text-[#f4d27a]">Customers</h2>
                            <div class="mt-4 space-y-3">
                                @forelse ($customers as $customer)
                                    <a href="{{ route('admin.customers.show', $customer) }}" class="block rounded-md border border-[#c8a24a]/15 bg-black p-3 transition hover:border-[#f4d27a]">
                                        <span class="block font-semibold text-[#fff9ea]">{{ $customer->name }}</span>
                                        <span class="text-sm text-[#d8c8a3]">{{ $customer->mobile }} · {{ $customer->customer_code }}</span>
                                    </a>
                                @empty
                                    <p class="text-sm text-[#a89567]">No matching customers.</p>
                                @endforelse
                            </div>
                        </section>
                    @endif

                    <section class="rounded-lg border border-[#c8a24a]/20 bg-[#11100d] p-5">
                        <h2 class="font-serif text-xl text-[#f4d27a]">Invoices</h2>
                        <div class="mt-4 space-y-3">
                            @forelse ($bills as $bill)
                                <a href="{{ route($routeRoot.'.billing.show', $bill) }}" class="block rounded-md border border-[#c8a24a]/15 bg-black p-3 transition hover:border-[#f4d27a]">
                                    <span class="block font-semibold text-[#fff9ea]">{{ $bill->invoice_number }}</span>
                                    <span class="text-sm text-[#d8c8a3]">{{ $bill->customer?->name }} · {{ \App\Support\Money::inr($bill->grand_total) }}</span>
                                </a>
                            @empty
                                <p class="text-sm text-[#a89567]">No matching invoices.</p>
                            @endforelse
                        </div>
                    </section>

                    <section class="rounded-lg border border-[#c8a24a]/20 bg-[#11100d] p-5">
                        <h2 class="font-serif text-xl text-[#f4d27a]">Services</h2>
                        <div class="mt-4 space-y-3">
                            @forelse ($services as $service)
                                <a href="{{ auth()->user()->isAdmin() ? route('admin.services.edit', $service) : route($routeRoot.'.billing.create') }}" class="block rounded-md border border-[#c8a24a]/15 bg-black p-3 transition hover:border-[#f4d27a]">
                                    <span class="block font-semibold text-[#fff9ea]">{{ $service->name }}</span>
                                    <span class="text-sm text-[#d8c8a3]">{{ $service->service_code ?: 'Service' }} · {{ $service->displayPrice() }}</span>
                                </a>
                            @empty
                                <p class="text-sm text-[#a89567]">No matching services.</p>
                            @endforelse
                        </div>
                    </section>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
