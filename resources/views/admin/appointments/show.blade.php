<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#a89567]">Appointment</p>
                <h1 class="font-serif text-2xl text-[#f4d27a]">{{ $appointment->booking_number }}</h1>
                <p class="mt-2 text-sm text-[#d8c8a3]">{{ ucfirst(str_replace('_', ' ', $appointment->status)) }} · {{ $appointment->appointment_type === 'home_service' ? 'Home Visit' : 'Salon Visit' }}</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <a href="{{ route('admin.billing.create', ['appointment_id' => $appointment->id]) }}" class="rounded-md bg-[#d5a93b] px-4 py-3 text-sm font-semibold text-black">Start Billing</a>
                @if ($appointment->bills->isNotEmpty())
                    <a href="{{ route('admin.billing.show', $appointment->bills->sortByDesc('id')->first()) }}" class="rounded-md border border-[#c8a24a]/40 px-4 py-3 text-sm font-semibold text-[#f8efd8]">View Latest Bill</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 lg:grid-cols-3">
                <x-admin.card class="lg:col-span-2">
                    <h2 class="font-serif text-xl text-[#f4d27a]">Appointment Details</h2>
                    <div class="mt-5 space-y-4 text-sm text-[#d8c8a3]">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-xs uppercase text-[#a89567]">Customer</p>
                                <p class="mt-1 font-semibold text-[#fff9ea]">{{ $appointment->customer->name }}</p>
                                <p class="text-sm text-[#d8c8a3]">+91 {{ $appointment->customer->mobile }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase text-[#a89567]">Appointment Date</p>
                                <p class="mt-1 font-semibold text-[#fff9ea]">{{ $appointment->date?->format('d M Y') }}</p>
                                <p class="text-sm text-[#d8c8a3]">{{ \Illuminate\Support\Carbon::parse($appointment->start_time)->format('h:i A') }}</p>
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-xs uppercase text-[#a89567]">Assigned Staff</p>
                                <p class="mt-1 font-semibold text-[#fff9ea]">{{ $appointment->assignedStaff?->name ?? 'Not assigned' }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase text-[#a89567]">Service Type</p>
                                <p class="mt-1 font-semibold text-[#fff9ea]">{{ $appointment->appointment_type === 'home_service' ? 'Home Visit' : 'Salon Visit' }}</p>
                            </div>
                        </div>
                        @if ($appointment->appointment_type === 'home_service')
                            <div>
                                <p class="text-xs uppercase text-[#a89567]">Home Service Address</p>
                                <p class="mt-1 text-[#fff9ea]">{{ $appointment->address_line_1 ?: 'Address not provided' }}</p>
                            </div>
                        @endif
                        <div>
                            <p class="text-xs uppercase text-[#a89567]">Notes</p>
                            <p class="mt-1 text-[#fff9ea]">{{ $appointment->customer_notes ?: 'No special instructions' }}</p>
                        </div>
                    </div>
                </x-admin.card>

                <x-admin.card>
                    <div class="space-y-4 text-sm text-[#d8c8a3]">
                        @if ($errors->any())
                            <div class="rounded-md border border-red-400/30 bg-red-500/10 p-3 text-xs text-red-100">
                                @foreach ($errors->all() as $error)
                                    <p>{{ $error }}</p>
                                @endforeach
                            </div>
                        @endif
                        @if (session('status'))
                            <div class="rounded-md border border-[#c8a24a]/30 bg-black p-3 text-xs font-semibold text-[#f4d27a]">{{ session('status') }}</div>
                        @endif
                        <form method="POST" action="{{ route('admin.appointments.assign', $appointment) }}" class="space-y-2">
                            @csrf
                            @method('PATCH')
                            <label class="text-xs uppercase text-[#a89567]" for="assigned_staff_id">Assign Staff</label>
                            <div class="flex gap-2">
                                <select id="assigned_staff_id" name="assigned_staff_id" class="w-full rounded-md border border-[#c8a24a]/30 bg-black px-3 py-2 text-[#fff9ea]">
                                    <option value="">Assign staff</option>
                                    @foreach ($activeStaff as $member)
                                        <option value="{{ $member->id }}" @selected($appointment->assigned_staff_id === $member->id)>{{ $member->name }}</option>
                                    @endforeach
                                </select>
                                <button class="rounded-md bg-[#d5a93b] px-4 py-2 text-xs font-semibold text-black">Save</button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('admin.appointments.status.update', $appointment) }}" class="space-y-2">
                            @csrf
                            @method('PATCH')
                            <label class="text-xs uppercase text-[#a89567]" for="status">Status</label>
                            <div class="flex gap-2">
                                <select id="status" name="status" class="w-full rounded-md border border-[#c8a24a]/30 bg-black px-3 py-2 text-[#fff9ea]">
                                    @foreach ($statuses as $statusOption)
                                        <option value="{{ $statusOption }}" @selected($appointment->status === $statusOption)>{{ ucfirst(str_replace('_', ' ', $statusOption)) }}</option>
                                    @endforeach
                                </select>
                                <button class="rounded-md border border-[#c8a24a]/40 bg-black px-4 py-2 text-xs font-semibold text-[#f4d27a]">Update</button>
                            </div>
                        </form>
                        <div>
                            <p class="text-xs uppercase text-[#a89567]">Status</p>
                            <p class="mt-1 font-semibold text-[#fff9ea]">{{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-[#a89567]">Amount</p>
                            <p class="mt-1 font-semibold text-[#fff9ea]">{{ \App\Support\Money::inr($appointment->total) }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-[#a89567]">Home Visit Charge</p>
                            <p class="mt-1 font-semibold text-[#fff9ea]">{{ \App\Support\Money::inr($appointment->visit_charge) }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-[#a89567]">Appointment Services</p>
                            <ul class="mt-2 space-y-2 text-sm">
                                @foreach ($appointment->appointmentServices as $service)
                                    <li class="rounded-md border border-[#c8a24a]/15 bg-black px-3 py-2 text-[#fff9ea]">{{ $service->service_name_snapshot }} · {{ \App\Support\Money::inr($service->unit_price) }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </x-admin.card>
            </div>
        </div>
    </div>
</x-app-layout>
