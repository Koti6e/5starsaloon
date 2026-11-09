<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="font-serif text-2xl text-[var(--app-primary)]">Customers</h1>
            <a href="{{ route('admin.customers.create') }}" class="rounded-md bg-[var(--app-primary)] px-4 py-2 text-sm font-semibold text-[var(--on-accent)]">Add Customer</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-2"><p class="text-sm text-[var(--app-muted)]">Choose a reporting period to show customers with at least one completed visit in that date range.</p>@if ($periodSelected)<a class="text-sm font-semibold text-[var(--app-primary)] underline" href="{{ route('admin.customers.index', array_filter(['filter' => request('filter'), 'search' => request('search')])) }}">Clear visit range</a>@endif</div>
            <x-admin.period-filter :period="$period" :action="route('admin.customers.index')" :preserve="['filter' => request('filter'), 'search' => request('search')]" :show-navigation="$periodSelected" />
            <form class="grid gap-2 md:grid-cols-[1fr_220px_auto]">
                @if ($periodSelected)
                    @foreach ($period->toQuery() as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
                @endif
                <input name="search" value="{{ request('search') }}" class="w-full rounded-md border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-text)] px-4 py-3" placeholder="Search name, mobile, or code">
                <select name="filter" class="rounded-md border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-text)] px-4 py-3">
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
                <button class="rounded-md border border-[var(--app-border)] px-4 py-3 text-sm font-semibold text-[var(--app-primary)]">Search</button>
            </form>

            <x-admin.card class="overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="hidden min-w-full divide-y divide-[var(--app-border)] text-sm md:table">
                    <thead class="text-left text-[var(--app-primary)]">
                        <tr><th class="px-4 py-3">Customer Name</th><th>Customer ID</th><th>Mobile</th><th>{{ $periodSelected ? 'Visits in Period' : 'Visits' }}</th><th>{{ $periodSelected ? 'Period Spend' : 'Total Spent' }}</th><th>{{ $periodSelected ? 'Last Visit in Period' : 'Last Visit' }}</th><th>View</th><th>WhatsApp</th></tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--app-border)] text-[var(--app-text)]">
                        @foreach ($customers as $customer)
                            <tr>
                                <td class="px-4 py-3"><p class="font-semibold">{{ $customer->name }}</p></td>
                                <td>{{ $customer->customer_code }}</td>
                                <td>+91 {{ $customer->mobile }}</td>
                                <td>{{ $periodSelected ? $customer->report_visit_count : $customer->total_visits }}</td>
                                <td>{{ \App\Support\Money::inr($periodSelected ? ($customer->report_total_spent ?? 0) : $customer->total_spent) }}</td>
                                <td>{{ ($visitDate = $periodSelected ? $customer->report_last_visit : $customer->last_visit_at) ? \Illuminate\Support\Carbon::parse($visitDate)->timezone('Asia/Kolkata')->format('d M Y') : '-' }}</td>
                                <td class="py-3">
                                    <a href="{{ route('admin.customers.show', $customer) }}" class="rounded-md border border-[var(--app-border)] px-3 py-2 text-xs font-semibold text-[var(--app-text)]">View</a>
                                </td>
                                <td class="py-3">
                                    <a href="{{ route('admin.customers.whatsapp', $customer) }}" target="_blank" rel="noopener" class="rounded-md border border-[var(--app-border)] px-3 py-2 text-xs font-semibold text-[var(--app-primary)]">WhatsApp</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="grid gap-3 p-3 md:hidden">
                    @foreach ($customers as $customer)
                        <div class="rounded-md border border-[var(--app-border)] bg-[var(--app-bg)] p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-[var(--app-text)]">{{ $customer->name }}</p>
                                    <p class="text-xs text-[var(--app-subtle)]">{{ $customer->customer_code }} · +91 {{ $customer->mobile }}</p>
                                </div>
                                <span class="text-xs uppercase text-[var(--app-subtle)]">{{ Str::headline($customer->whatsapp_status ?? 'not_contacted') }}</span>
                            </div>
                            <div class="mt-3 grid grid-cols-3 gap-2 text-xs">
                                <div><span class="block text-[var(--app-subtle)]">{{ $periodSelected ? 'Period Visits' : 'Visits' }}</span><span class="font-semibold text-[var(--app-text)]">{{ $periodSelected ? $customer->report_visit_count : $customer->total_visits }}</span></div>
                                <div><span class="block text-[var(--app-subtle)]">{{ $periodSelected ? 'Period Spend' : 'Spent' }}</span><span class="font-semibold text-[var(--app-text)]">{{ \App\Support\Money::inr($periodSelected ? ($customer->report_total_spent ?? 0) : $customer->total_spent) }}</span></div>
                                <div><span class="block text-[var(--app-subtle)]">{{ $periodSelected ? 'Period Last Visit' : 'Last' }}</span><span class="font-semibold text-[var(--app-text)]">{{ ($visitDate = $periodSelected ? $customer->report_last_visit : $customer->last_visit_at) ? \Illuminate\Support\Carbon::parse($visitDate)->timezone('Asia/Kolkata')->format('d M') : '-' }}</span></div>
                            </div>
                            <div class="mt-3 flex gap-2">
                                <a href="{{ route('admin.customers.show', $customer) }}" class="flex-1 rounded-md border border-[var(--app-border)] px-3 py-2 text-center text-xs font-semibold text-[var(--app-text)]">View</a>
                                <a href="{{ route('admin.customers.whatsapp', $customer) }}" target="_blank" rel="noopener" class="flex-1 rounded-md border border-[var(--app-border)] px-3 py-2 text-center text-xs font-semibold text-[var(--app-primary)]">WhatsApp</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-admin.card>

            {{ $customers->links() }}
        </div>
    </div>
</x-app-layout>
