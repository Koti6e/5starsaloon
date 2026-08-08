<x-layouts.public :settings="$settings" title="Appointment Confirmed | 5 Star New Look Salon">
    <section class="bg-[#0d0b08] px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl">

            <!-- Confirmation Card Header -->
            <div class="rounded-t-lg border border-[#c8a24a]/30 bg-[#161410] p-6 text-center sm:p-8">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[#d5a93b]/20 text-[#f4d27a]">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h1 class="mt-4 font-serif text-3xl text-[#f4d27a] sm:text-4xl">Appointment Confirmed!</h1>
                <p class="mt-2 text-sm text-[#d8c8a3]">Your appointment request has been recorded. Our salon team will confirm staff availability.</p>

                <div class="mt-4 inline-block rounded-full border border-[#c8a24a]/40 bg-black/60 px-4 py-1.5 text-xs font-semibold tracking-wider text-[#f4d27a] uppercase">
                    Booking No: {{ $appointment->booking_number }}
                </div>
            </div>

            <!-- Booking Details Body -->
            <div class="border-x border-b border-[#c8a24a]/30 bg-[#11100d] p-6 sm:p-8">
                <h2 class="font-serif text-xl text-[#f4d27a] border-b border-[#c8a24a]/20 pb-2">Booking Details</h2>

                <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-[#a89873]">Customer Name</dt>
                        <dd class="mt-1 font-semibold text-[#fff9ea]">{{ $appointment->customer->name }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs uppercase tracking-wider text-[#a89873]">Mobile Number</dt>
                        <dd class="mt-1 font-semibold text-[#fff9ea]">+91 {{ $appointment->customer->mobile }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs uppercase tracking-wider text-[#a89873]">Service Selected</dt>
                        <dd class="mt-1 font-semibold text-[#fff9ea]">
                            {{ $appointmentService?->service_name_snapshot ?? 'Salon Service' }}
                            <span class="text-xs text-[#d5a93b]">({{ \App\Support\Money::inr($appointment->total) }})</span>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs uppercase tracking-wider text-[#a89873]">Appointment Type</dt>
                        <dd class="mt-1 font-semibold text-[#fff9ea]">
                            @if ($appointment->appointment_type === 'home_service')
                                <span class="rounded bg-[#d5a93b]/20 px-2 py-0.5 text-xs font-semibold text-[#f4d27a]">Elite Home Service</span>
                            @else
                                <span class="rounded bg-stone-800 px-2 py-0.5 text-xs font-semibold text-[#d8c8a3]">Salon Visit</span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs uppercase tracking-wider text-[#a89873]">Appointment Date</dt>
                        <dd class="mt-1 font-semibold text-[#fff9ea]">{{ $appointment->date?->format('d M Y') }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs uppercase tracking-wider text-[#a89873]">Preferred Time</dt>
                        <dd class="mt-1 font-semibold text-[#fff9ea]">{{ \Illuminate\Support\Carbon::parse($appointment->start_time)->format('h:i A') }}</dd>
                    </div>

                    @if ($appointment->appointment_type === 'home_service')
                        <div class="sm:col-span-2">
                            <dt class="text-xs uppercase tracking-wider text-[#a89873]">Service Address</dt>
                            <dd class="mt-1 font-semibold text-[#fff9ea]">{{ $appointment->address_line_1 ?: 'Address provided' }}</dd>
                        </div>
                    @endif

                    @if ($appointment->customer_notes)
                        <div class="sm:col-span-2">
                            <dt class="text-xs uppercase tracking-wider text-[#a89873]">Special Notes</dt>
                            <dd class="mt-1 text-[#d8c8a3]">{{ $appointment->customer_notes }}</dd>
                        </div>
                    @endif

                    <div>
                        <dt class="text-xs uppercase tracking-wider text-[#a89873]">Current Status</dt>
                        <dd class="mt-1">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-950/60 px-3 py-1 text-xs font-semibold text-amber-300 border border-amber-500/30">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                                Pending Salon Confirmation
                            </span>
                        </dd>
                    </div>
                </dl>

                <!-- Salon Contact Details -->
                <div class="mt-8 rounded-md border border-[#c8a24a]/20 bg-[#161410] p-4 text-xs text-[#d8c8a3]">
                    <h3 class="font-semibold text-[#f4d27a] uppercase tracking-wider">Salon Location & Hours</h3>
                    <p class="mt-1">{{ $settings['salon_name'] ?? '5 Star New Look Salon' }}</p>
                    <p class="mt-0.5">{{ $settings['address'] ?? 'Visit salon for location details' }}</p>
                    <p class="mt-0.5">Working Hours: {{ $settings['working_hours'] ?? 'Open daily by appointment' }}</p>
                </div>

                <!-- Action Buttons -->
                <div class="mt-8 grid gap-3 sm:grid-cols-3">
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="flex items-center justify-center gap-2 rounded-md bg-[#25D366] px-4 py-3 text-center text-sm font-bold text-black shadow-md transition hover:bg-[#20bd5a]">
                        <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24">
                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/>
                        </svg>
                        Send to WhatsApp
                    </a>

                    <a href="{{ route('home') }}" class="flex items-center justify-center rounded-md border border-[#c8a24a]/40 bg-black px-4 py-3 text-center text-sm font-semibold text-[#f8efd8] transition hover:border-[#d5a93b]">
                        Back to Home
                    </a>

                    <a href="{{ route('appointments.book') }}" class="flex items-center justify-center rounded-md bg-[#d5a93b] px-4 py-3 text-center text-sm font-bold text-[#111] transition hover:bg-[#f0c75e]">
                        Book Another
                    </a>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
