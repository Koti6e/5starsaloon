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
                <div class="grid gap-4">
                    @foreach ($appointments as $appointment)
                        @php
                            $bill = $appointment->bills->sortByDesc('id')->first();
                            $services = $appointment->appointmentServices->pluck('service_name_snapshot')->filter()->join(', ');
                            $dateLabel = $appointment->date?->format('d M Y');
                            $timeLabel = \Illuminate\Support\Carbon::parse($appointment->start_time)->format('h:i A');
                            $mobile = preg_replace('/\D+/', '', $appointment->customer->mobile);
                            $whatsappMessage = "{$appointment->customer->name}, this is 5 Star New Look Salon regarding your appointment {$appointment->booking_number}.\n\nService: {$services}\nDate: {$dateLabel}\nTime: {$timeLabel}\n\nPlease confirm your attendance or any changes.";
                            $statusTone = match ($appointment->status) {
                                'pending' => 'border-[#f4d27a]/40 text-[#f4d27a]',
                                'confirmed', 'in_progress' => 'border-[#38bdf8]/40 text-[#7dd3fc]',
                                'completed' => 'border-[#22c55e]/40 text-[#86efac]',
                                'cancelled' => 'border-[#ef4444]/40 text-[#fca5a5]',
                                default => 'border-[#c8a24a]/30 text-[#f8efd8]',
                            };
                        @endphp
                        <article class="rounded-lg border border-[#c8a24a]/20 bg-[#11100d] p-4 shadow-lg shadow-black/10 sm:p-5">
                            <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_320px] lg:items-start">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-md border px-3 py-1 text-xs font-bold uppercase tracking-[0.12em] {{ $statusTone }}">{{ Str::headline($appointment->status) }}</span>
                                        <span class="text-sm font-semibold text-[#fff9ea]">{{ $appointment->booking_number }}</span>
                                    </div>

                                    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                        <div>
                                            <p class="text-xs uppercase text-[#a89567]">Customer</p>
                                            <p class="mt-1 font-semibold text-[#fff9ea]">{{ $appointment->customer->name }}</p>
                                            <p class="text-sm text-[#d8c8a3]">+91 {{ $appointment->customer->mobile }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs uppercase text-[#a89567]">When</p>
                                            <p class="mt-1 font-semibold text-[#fff9ea]">{{ $dateLabel }}</p>
                                            <p class="text-sm text-[#d8c8a3]">{{ $timeLabel }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs uppercase text-[#a89567]">Visit Type</p>
                                            <p class="mt-1 font-semibold text-[#fff9ea]">{{ $appointment->appointment_type === 'home_service' ? 'Home Visit' : 'Salon Visit' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs uppercase text-[#a89567]">Assigned Staff</p>
                                            <p class="mt-1 font-semibold text-[#fff9ea]">{{ $appointment->assignedStaff?->name ?? 'Not assigned' }}</p>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <p class="text-xs uppercase text-[#a89567]">Services</p>
                                        <p class="mt-1 text-sm leading-6 text-[#d8c8a3]">{{ $services ?: 'Salon service' }}</p>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    @if (! in_array($appointment->status, ['completed', 'cancelled'], true))
                                        <form method="POST" action="{{ route('admin.appointments.assign', $appointment) }}" class="grid grid-cols-[minmax(0,1fr)_auto] gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <select name="assigned_staff_id" class="min-w-0 rounded-md border border-[#c8a24a]/30 bg-black px-3 py-2 text-sm text-[#fff9ea]">
                                                <option value="">Assign staff</option>
                                                @foreach ($activeStaff as $member)
                                                    <option value="{{ $member->id }}" @selected($appointment->assigned_staff_id === $member->id)>{{ $member->name }}</option>
                                                @endforeach
                                            </select>
                                            <button class="rounded-md border border-[#c8a24a]/40 px-3 py-2 text-xs font-semibold text-[#f4d27a]">{{ $appointment->assigned_staff_id ? 'Change' : 'Assign' }}</button>
                                        </form>
                                    @endif

                                    <div class="grid grid-cols-2 gap-2">
                                        @if ($appointment->status === 'pending')
                                            <form method="POST" action="{{ route('admin.appointments.status.update', $appointment) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="confirmed">
                                                <button class="w-full rounded-md bg-[#d5a93b] px-3 py-2 text-xs font-bold text-black">Accept</button>
                                            </form>
                                        @endif
                                        @if ($appointment->status === 'confirmed')
                                            <form method="POST" action="{{ route('admin.appointments.status.update', $appointment) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="in_progress">
                                                <button class="w-full rounded-md bg-[#d5a93b] px-3 py-2 text-xs font-bold text-black">Start</button>
                                            </form>
                                        @endif
                                        @if (in_array($appointment->status, ['confirmed', 'in_progress'], true))
                                            <form method="POST" action="{{ route('admin.appointments.status.update', $appointment) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="completed">
                                                <button class="w-full rounded-md bg-[#d5a93b] px-3 py-2 text-xs font-bold text-black">Complete</button>
                                            </form>
                                        @endif
                                        @if (! in_array($appointment->status, ['completed', 'cancelled'], true))
                                            <form method="POST" action="{{ route('admin.appointments.status.update', $appointment) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="cancelled">
                                                <button class="w-full rounded-md border border-[#ef4444]/40 px-3 py-2 text-xs font-semibold text-[#fca5a5]">Cancel</button>
                                            </form>
                                        @endif
                                    </div>

                                    <div class="grid grid-cols-2 gap-2">
                                        <a href="{{ route('admin.appointments.show', $appointment) }}" class="rounded-md border border-[#c8a24a]/30 bg-black px-3 py-2 text-center text-xs font-semibold text-[#f8efd8]">View Details</a>
                                        @if ($bill)
                                            <a href="{{ route('admin.billing.show', $bill) }}" class="rounded-md border border-[#f4d27a]/40 bg-black px-3 py-2 text-center text-xs font-semibold text-[#f4d27a]">View Bill</a>
                                        @else
                                            <a href="{{ route('admin.billing.create', ['appointment_id' => $appointment->id]) }}" class="rounded-md border border-[#f4d27a]/40 bg-black px-3 py-2 text-center text-xs font-semibold text-[#f4d27a]">Billing</a>
                                        @endif
                                        <a href="tel:+91{{ $mobile }}" class="rounded-md border border-[#c8a24a]/30 bg-black px-3 py-2 text-center text-xs font-semibold text-[#f8efd8]">Call</a>
                                        <a href="https://wa.me/91{{ $mobile }}?text={{ rawurlencode($whatsappMessage) }}" target="_blank" rel="noopener" class="rounded-md border border-[#c8a24a]/30 bg-black px-3 py-2 text-center text-xs font-semibold text-[#f8efd8]">WhatsApp</a>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-4">{{ $appointments->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
