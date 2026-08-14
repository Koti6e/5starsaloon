<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#a89567]">Appointments</p>
                <h1 class="font-serif text-2xl text-[#f4d27a]">Appointment Dashboard</h1>
                <p class="mt-2 text-sm text-[#d8c8a3]">Manage appointment status, billing conversions, and customer follow-up from a dedicated admin dashboard.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
                <a href="{{ route('appointments.book') }}" class="rounded-md bg-[#d5a93b] px-4 py-3 text-center text-sm font-semibold text-[#111]">Open Public Booking</a>
                <a href="{{ route('admin.billing.create') }}" class="rounded-md border border-[#c8a24a]/40 px-4 py-3 text-center text-sm font-semibold text-[#f8efd8]">Quick Billing</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
                @foreach ([
                    ['Total', $counts['total'], 'bg-[#f4d27a]'],
                    ['Pending', $counts['pending'], 'bg-[#c8a24a]'],
                    ['Ongoing', $counts['ongoing'], 'bg-[#d5a93b]'],
                    ['Completed', $counts['completed'], 'bg-[#22c55e]'],
                    ['Cancelled', $counts['cancelled'], 'bg-[#ef4444]'],
                    ['Home Visits', $counts['home_visits'], 'bg-[#f59e0b]'],
                ] as [$label, $value, $color])
                    <x-admin.card class="h-full border-0 bg-[#11100d]">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm text-[#d8c8a3]">{{ $label }}</p>
                                <p class="mt-3 text-3xl font-semibold text-[#fff9ea]">{{ $value }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-full {{ $color }} bg-opacity-20"></div>
                        </div>
                    </x-admin.card>
                @endforeach
            </div>

            <div class="rounded-lg border border-[#c8a24a]/20 bg-[#11100d] p-5">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex flex-wrap gap-2">
                        @foreach (['all' => 'All', 'today' => 'Today', 'upcoming' => 'Upcoming', 'pending' => 'Pending', 'ongoing' => 'Ongoing', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'home_visits' => 'Home Visits'] as $key => $label)
                            <a href="{{ route('admin.appointments.index', array_merge(request()->except('page'), ['filter' => $key])) }}" class="rounded-full border px-4 py-2 text-sm font-semibold {{ $filter === $key ? 'border-[#f4d27a] bg-[#d5a93b] text-black' : 'border-[#c8a24a]/30 text-[#f8efd8]' }}">{{ $label }}</a>
                        @endforeach
                    </div>
                    <form method="GET" action="{{ route('admin.appointments.index') }}" class="flex w-full items-center gap-2 md:w-auto">
                        <input name="q" value="{{ $search }}" placeholder="Search appointment, customer, mobile" class="w-full rounded-md border-[#c8a24a]/30 bg-black px-4 py-3 text-[#fff9ea] focus:border-[#f4d27a] focus:ring-[#f4d27a]" />
                        <button type="submit" class="rounded-md bg-[#d5a93b] px-4 py-3 text-sm font-semibold text-black">Search</button>
                    </form>
                </div>
            </div>

            @if ($appointments->isEmpty())
                <x-admin.card>
                    <div class="space-y-4 text-center">
                        <p class="text-sm uppercase tracking-[0.26em] text-[#a89567]">No appointments yet</p>
                        <h2 class="font-serif text-3xl text-[#f4d27a]">New appointments will appear here.</h2>
                        <p class="text-sm text-[#d8c8a3]">New online and admin-created appointments will appear here.</p>
                        <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                            <a href="{{ route('appointments.book') }}" class="rounded-md bg-[#d5a93b] px-4 py-3 text-sm font-semibold text-black">Open Public Booking Page</a>
                            <a href="{{ route('admin.billing.create') }}" class="rounded-md border border-[#c8a24a]/40 px-4 py-3 text-sm font-semibold text-[#f8efd8]">Quick Billing</a>
                        </div>
                    </div>
                </x-admin.card>
            @else
                <div class="overflow-x-auto rounded-lg border border-[#c8a24a]/20 bg-[#11100d]">
                    <table class="min-w-full divide-y divide-[#c8a24a]/15 text-sm text-[#f8efd8]">
                        <thead class="bg-black text-left text-xs uppercase tracking-[0.18em] text-[#a89567]">
                            <tr>
                                <th class="px-4 py-3">Appointment</th>
                                <th class="px-4 py-3">Customer</th>
                                <th class="px-4 py-3">Services</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">When</th>
                                <th class="px-4 py-3">Staff</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Billing</th>
                                <th class="px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#c8a24a]/10">
                            @foreach ($appointments as $appointment)
                                @php
                                    $bill = $appointment->bills->sortByDesc('id')->first();
                                    $billingLabel = $bill ? ucfirst($bill->payment_status) : 'Unbilled';
                                    $whatsappMessage = "Hi {$appointment->customer->name},\n\n".
                                        "This is 5 Star New Look Salon regarding your appointment {$appointment->booking_number}.\n\n".
                                        "Service: ".$appointment->appointmentServices->pluck('service_name_snapshot')->join(', ')."\n".
                                        "Date: ".$appointment->date?->format('d M Y')."\n".
                                        "Time: ".\Illuminate\Support\Carbon::parse($appointment->start_time)->format('h:i A')."\n\n".
                                        "Please confirm your attendance or any changes.";
                                @endphp
                                <tr>
                                    <td class="px-4 py-4">
                                        <div class="font-semibold text-[#fff9ea]">{{ $appointment->booking_number }}</div>
                                        <div class="text-xs text-[#a89567]">{{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-semibold text-[#fff9ea]">{{ $appointment->customer->name }}</div>
                                        <div class="text-xs text-[#d8c8a3]">+91 {{ $appointment->customer->mobile }}</div>
                                    </td>
                                    <td class="px-4 py-4 max-w-[260px]">
                                        <div class="space-y-1 text-xs text-[#d8c8a3]">
                                            @foreach ($appointment->appointmentServices as $service)
                                                <div>{{ $service->service_name_snapshot }}</div>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="rounded-full bg-[#1f1a0f] px-3 py-1 text-xs text-[#f4d27a]">{{ $appointment->appointment_type === 'home_service' ? 'Home Visit' : 'Salon Visit' }}</span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-semibold text-[#fff9ea]">{{ $appointment->date?->format('d M Y') }}</div>
                                        <div class="text-xs text-[#d8c8a3]">{{ \Illuminate\Support\Carbon::parse($appointment->start_time)->format('h:i A') }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-[#d8c8a3]">
                                        <form method="POST" action="{{ route('admin.appointments.assign', $appointment) }}" class="flex min-w-[170px] gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <select name="assigned_staff_id" class="w-full rounded-md border border-[#c8a24a]/30 bg-black px-2 py-2 text-xs text-[#fff9ea]">
                                                <option value="">Assign staff</option>
                                                @foreach ($activeStaff as $member)
                                                    <option value="{{ $member->id }}" @selected($appointment->assigned_staff_id === $member->id)>{{ $member->name }}</option>
                                                @endforeach
                                            </select>
                                            <button class="rounded-md bg-[#d5a93b] px-3 py-2 text-xs font-semibold text-black">Save</button>
                                        </form>
                                    </td>
                                    <td class="px-4 py-4">
                                        <form method="POST" action="{{ route('admin.appointments.status.update', $appointment) }}" class="flex min-w-[160px] gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <select name="status" class="w-full rounded-md border border-[#c8a24a]/30 bg-black px-2 py-2 text-xs text-[#fff9ea]">
                                                @foreach ($statuses as $statusOption)
                                                    <option value="{{ $statusOption }}" @selected($appointment->status === $statusOption)>{{ ucfirst(str_replace('_', ' ', $statusOption)) }}</option>
                                                @endforeach
                                            </select>
                                            <button class="rounded-md border border-[#c8a24a]/40 bg-black px-3 py-2 text-xs font-semibold text-[#f4d27a]">Update</button>
                                        </form>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-[#d8c8a3]">{{ $billingLabel }}</td>
                                    <td class="px-4 py-4 space-y-2">
                                        <a href="{{ route('admin.appointments.show', $appointment) }}" class="inline-flex rounded-md border border-[#c8a24a]/30 bg-black px-3 py-2 text-xs font-semibold text-[#f8efd8]">View</a>
                                        @if ($bill)
                                            <a href="{{ route('admin.billing.show', $bill) }}" class="inline-flex rounded-md border border-[#f4d27a] bg-[#1b1711] px-3 py-2 text-xs font-semibold text-[#f4d27a]">View Bill</a>
                                        @else
                                            <a href="{{ route('admin.billing.create', ['appointment_id' => $appointment->id]) }}" class="inline-flex rounded-md bg-[#d5a93b] px-3 py-2 text-xs font-semibold text-black">Start Billing</a>
                                        @endif
                                        <a href="https://wa.me/91{{ preg_replace('/\D+/', '', $appointment->customer->mobile) }}?text={{ rawurlencode($whatsappMessage) }}" target="_blank" rel="noopener" class="inline-flex rounded-md border border-[#c8a24a]/30 bg-black px-3 py-2 text-xs font-semibold text-[#f8efd8]">WhatsApp</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $appointments->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
