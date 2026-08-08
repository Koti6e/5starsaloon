<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="font-serif text-2xl text-[#f4d27a]">Customers</h1>
            <a href="{{ route('admin.customers.create') }}" class="rounded-md bg-[#d5a93b] px-4 py-2 text-sm font-semibold text-black">Add Customer</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
            <form class="grid gap-2 md:grid-cols-[1fr_220px_auto]">
                <input name="search" value="{{ request('search') }}" class="w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea] px-4 py-3" placeholder="Search name, mobile, or code">
                <select name="filter" class="rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea] px-4 py-3">
                    <option value="">All Customers</option>
                    @foreach ([
                        'new' => 'New Customers',
                        'regular' => 'Regular Customers',
                        'vip' => 'VIP Customers',
                        'birthdays' => 'Birthdays This Month',
                        'not_visited_30' => 'Not Visited in 30 Days',
                        'not_visited_60' => 'Not Visited in 60 Days',
                        'not_visited_90' => 'Not Visited in 90 Days',
                        'top_spending' => 'Top Spending Customers',
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ] as $value => $label)
                        <option value="{{ $value }}" @selected(request('filter') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="rounded-md border border-[#c8a24a]/40 px-4 py-3 text-sm font-semibold text-[#f4d27a]">Search</button>
            </form>

            <x-admin.card class="overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="hidden min-w-full divide-y divide-[#c8a24a]/15 text-sm md:table">
                    <thead class="text-left text-[#f4d27a]">
                        <tr><th class="px-4 py-3">Customer</th><th>Mobile</th><th>Visits</th><th>Spent</th><th>Last visit</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody class="divide-y divide-[#c8a24a]/10 text-[#f8efd8]">
                        @foreach ($customers as $customer)
                            <tr>
                                <td class="px-4 py-3"><p class="font-semibold">{{ $customer->name }}</p><p class="text-xs text-[#a89567]">{{ $customer->customer_code }}</p></td>
                                <td>+91 {{ $customer->mobile }}</td>
                                <td>{{ $customer->total_visits }}</td>
                                <td>{{ \App\Support\Money::inr($customer->total_spent) }}</td>
                                <td>{{ $customer->last_visit_at ? \Illuminate\Support\Carbon::parse($customer->last_visit_at)->format('d M Y') : '-' }}</td>
                                <td>{{ Str::title($customer->status) }}</td>
                                <td class="py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('admin.customers.show', $customer) }}" class="rounded-md border border-[#c8a24a]/40 px-3 py-2 text-xs font-semibold text-[#f8efd8]">View</a>
                                        <a href="https://wa.me/91{{ $customer->mobile }}" target="_blank" rel="noopener" class="rounded-md border border-[#c8a24a]/40 px-3 py-2 text-xs font-semibold text-[#f4d27a]">WhatsApp</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="grid gap-3 p-3 md:hidden">
                    @foreach ($customers as $customer)
                        <div class="rounded-md border border-[#c8a24a]/15 bg-black p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-[#fff9ea]">{{ $customer->name }}</p>
                                    <p class="text-xs text-[#a89567]">{{ $customer->customer_code }} · +91 {{ $customer->mobile }}</p>
                                </div>
                                <span class="text-xs uppercase text-[#a89567]">{{ $customer->status }}</span>
                            </div>
                            <div class="mt-3 grid grid-cols-3 gap-2 text-xs">
                                <div><span class="block text-[#a89567]">Visits</span><span class="font-semibold text-[#fff9ea]">{{ $customer->total_visits }}</span></div>
                                <div><span class="block text-[#a89567]">Spent</span><span class="font-semibold text-[#fff9ea]">{{ \App\Support\Money::inr($customer->total_spent) }}</span></div>
                                <div><span class="block text-[#a89567]">Last</span><span class="font-semibold text-[#fff9ea]">{{ $customer->last_visit_at ? \Illuminate\Support\Carbon::parse($customer->last_visit_at)->format('d M') : '-' }}</span></div>
                            </div>
                            <div class="mt-3 flex gap-2">
                                <a href="{{ route('admin.customers.show', $customer) }}" class="flex-1 rounded-md border border-[#c8a24a]/40 px-3 py-2 text-center text-xs font-semibold text-[#f8efd8]">View</a>
                                <a href="https://wa.me/91{{ $customer->mobile }}" target="_blank" rel="noopener" class="flex-1 rounded-md border border-[#c8a24a]/40 px-3 py-2 text-center text-xs font-semibold text-[#f4d27a]">WhatsApp</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-admin.card>

            {{ $customers->links() }}
        </div>
    </div>
</x-app-layout>
