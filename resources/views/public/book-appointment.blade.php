<x-layouts.public :settings="$settings" title="Book Appointment | 5 Star New Look Salon">
    <section class="bg-[#0d0b08] px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-4xl">
            <h1 class="font-serif text-4xl text-[#f4d27a]">Book Appointment</h1>
            <p class="mt-3 text-[#d8c8a3]">Select your preferred service, date, and visit type. Our team will prepare for your session.</p>

            <form method="POST" action="{{ route('appointments.store') }}" x-data="{ appointmentType: '{{ old('appointment_type', request('type') === 'home' ? 'home_service' : 'salon_visit') }}', submitting: false }" @submit="submitting = true" class="mt-8 grid gap-5 rounded-lg border border-[#c8a24a]/20 bg-[#11100d] p-6 sm:grid-cols-2">
                @csrf

                <!-- Appointment Type -->
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-[#f8efd8]">Appointment Type</label>
                    <div class="mt-2 grid gap-3 sm:grid-cols-2 sm:gap-4">
                        <label class="flex cursor-pointer items-center justify-center rounded-md border border-[#c8a24a]/30 p-3 transition" :class="appointmentType === 'salon_visit' ? 'bg-[#d5a93b]/20 border-[#d5a93b] text-[#f4d27a]' : 'bg-black text-[#d8c8a3]'">
                            <input type="radio" name="appointment_type" value="salon_visit" x-model="appointmentType" class="sr-only">
                            <span class="font-semibold">Salon Visit</span>
                        </label>
                        <label class="flex cursor-pointer items-center justify-center rounded-md border border-[#c8a24a]/30 p-3 transition" :class="appointmentType === 'home_service' ? 'bg-[#d5a93b]/20 border-[#d5a93b] text-[#f4d27a]' : 'bg-black text-[#d8c8a3]'">
                            <input type="radio" name="appointment_type" value="home_service" x-model="appointmentType" class="sr-only">
                            <span class="font-semibold">Elite Home Service</span>
                        </label>
                    </div>
                    @error('appointment_type')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Service Selection -->
                <div class="sm:col-span-2">
                    <label for="service_slug" class="block text-sm font-medium text-[#f8efd8]">Select Service</label>
                    <select id="service_slug" name="service_slug" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black px-4 py-2.5 text-[#fff9ea] focus:border-[#d5a93b] focus:ring-[#d5a93b]" required>
                        <option value="">-- Choose a Service --</option>
                        @forelse ($services as $service)
                            <option value="{{ $service->slug }}" @selected(old('service_slug', request('service')) === $service->slug)>
                                {{ $service->name }} — {{ $service->displayPrice() }}
                            </option>
                        @empty
                            <option value="" disabled>No active services available</option>
                        @endforelse
                    </select>
                    @error('service_slug')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Date -->
                <div>
                    <label for="appointment_date" class="block text-sm font-medium text-[#f8efd8]">Preferred Date</label>
                    <input type="date" id="appointment_date" name="appointment_date" value="{{ old('appointment_date', today()->toDateString()) }}" min="{{ today()->toDateString() }}" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black px-4 py-2.5 text-[#fff9ea] focus:border-[#d5a93b] focus:ring-[#d5a93b]" required>
                    @error('appointment_date')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Time -->
                <div>
                    <label for="appointment_time" class="block text-sm font-medium text-[#f8efd8]">Preferred Time</label>
                    <select id="appointment_time" name="appointment_time" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black px-4 py-2.5 text-[#fff9ea] focus:border-[#d5a93b] focus:ring-[#d5a93b]" required>
                        @foreach (['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00'] as $timeSlot)
                            <option value="{{ $timeSlot }}" @selected(old('appointment_time', '11:00') === $timeSlot)>
                                {{ \Illuminate\Support\Carbon::parse($timeSlot)->format('h:i A') }}
                            </option>
                        @endforeach
                    </select>
                    @error('appointment_time')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Full Name -->
                <div>
                    <label for="customer_name" class="block text-sm font-medium text-[#f8efd8]">Full Name</label>
                    <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name') }}" placeholder="e.g. Rahul Sharma" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black px-4 py-2.5 text-[#fff9ea] focus:border-[#d5a93b] focus:ring-[#d5a93b]" required>
                    @error('customer_name')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Mobile -->
                <div>
                    <label for="mobile" class="block text-sm font-medium text-[#f8efd8]">Mobile Number</label>
                    <input type="tel" id="mobile" name="mobile" value="{{ old('mobile') }}" placeholder="10-digit mobile number" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black px-4 py-2.5 text-[#fff9ea] focus:border-[#d5a93b] focus:ring-[#d5a93b]" required>
                    @error('mobile')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div class="sm:col-span-2">
                    <label for="email" class="block text-sm font-medium text-[#f8efd8]">Email Address (Optional)</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="your.email@example.com" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black px-4 py-2.5 text-[#fff9ea] focus:border-[#d5a93b] focus:ring-[#d5a93b]">
                    @error('email')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Home Service Address (Conditional) -->
                <div class="sm:col-span-2" x-show="appointmentType === 'home_service'" x-cloak>
                    <label for="address" class="block text-sm font-medium text-[#f8efd8]">Complete Service Address <span class="text-red-400">*</span></label>
                    <textarea id="address" name="address" rows="3" placeholder="House/Flat No., Building Name, Street, Area, City & Pincode" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black px-4 py-2.5 text-[#fff9ea] focus:border-[#d5a93b] focus:ring-[#d5a93b]">{{ old('address') }}</textarea>
                    @error('address')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Special Notes -->
                <div class="sm:col-span-2">
                    <label for="notes" class="block text-sm font-medium text-[#f8efd8]">Special Requests or Notes (Optional)</label>
                    <textarea id="notes" name="notes" rows="2" placeholder="Any specific requirements or preferred staff member..." class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black px-4 py-2.5 text-[#fff9ea] focus:border-[#d5a93b] focus:ring-[#d5a93b]">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Consent Checkbox -->
                <div class="sm:col-span-2">
                    <label class="flex items-start gap-3 text-sm text-[#d8c8a3]">
                        <input type="checkbox" name="consent" value="1" @checked(old('consent')) class="mt-1 rounded border-[#c8a24a]/40 bg-black text-[#d5a93b] focus:ring-[#d5a93b]" required>
                        <span>I agree to be contacted by 5 Star New Look Salon regarding this appointment booking.</span>
                    </label>
                    @error('consent')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Global Error Banner -->
                @if ($errors->any())
                    <div class="rounded-md border border-red-400/40 bg-red-950/40 p-4 text-sm text-red-200 sm:col-span-2">
                        <p class="font-semibold">Please correct the errors in the form above:</p>
                        <ul class="mt-1 list-inside list-disc text-xs text-red-300">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Submit Button -->
                <div class="sm:col-span-2 mt-2">
                    <button type="submit" :disabled="submitting" class="w-full rounded-md bg-[#d5a93b] px-6 py-3.5 text-center font-bold tracking-wide text-[#111] shadow-lg shadow-[#d5a93b]/20 transition hover:bg-[#f0c75e] disabled:cursor-not-allowed disabled:opacity-70">
                        <span x-show="!submitting">Confirm & Reserve Appointment</span>
                        <span x-show="submitting">Reserving Appointment...</span>
                    </button>
                </div>
            </form>
        </div>
    </section>
</x-layouts.public>
