<x-layouts.public :settings="$settings" title="Book a Salon Appointment | 5 Star New Look A/C" description="Choose a salon service and request an appointment at 5 Star New Look A/C.">
    <section class="bg-[#0d0b08] px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-4xl">
            <h1 class="font-serif text-4xl text-[#f4d27a]">Book Appointment</h1>
            <p class="mt-3 text-[#d8c8a3]">Select your preferred service, date, and visit type. Our team will prepare for your session.</p>

            <form method="POST" action="{{ route('appointments.store') }}" x-data="bookingForm()" x-init="init()" @submit="submitting = true" class="mt-8 grid gap-5 rounded-lg border border-[#c8a24a]/20 bg-[#11100d] p-6 sm:grid-cols-2">
                @csrf

                <!-- Appointment Type -->
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-[#f8efd8]">Appointment Type</label>
                    <div class="mt-2 grid gap-3 sm:grid-cols-2 sm:gap-4">
                        <label class="flex cursor-pointer items-center justify-center rounded-md border border-[#c8a24a]/30 p-3 transition" :class="appointmentType === 'salon_visit' ? 'bg-[#d5a93b]/20 border-[#d5a93b] text-[#f4d27a]' : 'bg-black text-[#d8c8a3]'">
                            <input type="radio" name="appointment_type" value="salon_visit" x-model="appointmentType" @change="loadSlots()" class="sr-only">
                            <span class="font-semibold">Salon Visit</span>
                        </label>
                        <label class="flex cursor-pointer items-center justify-center rounded-md border border-[#c8a24a]/30 p-3 transition" :class="appointmentType === 'home_service' ? 'bg-[#d5a93b]/20 border-[#d5a93b] text-[#f4d27a]' : 'bg-black text-[#d8c8a3]'">
                            <input type="radio" name="appointment_type" value="home_service" x-model="appointmentType" @change="loadSlots()" class="sr-only">
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
                        <option value="{{ $service->slug }}" data-price="{{ $service->discounted_price ?: $service->price ?: $service->minimum_price ?: 0 }}" data-home-visit-charge="{{ $service->home_service_visit_charge ?? 0 }}" @selected(old('service_slug', request('service', $services->first()?->slug)) === $service->slug)>
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
                    <select id="appointment_date" name="appointment_date" x-model="selectedDate" @change="loadSlots()" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black px-4 py-2.5 text-[#fff9ea] focus:border-[#d5a93b] focus:ring-[#d5a93b]" required>
                        <option value="" x-text="loading ? 'Finding available dates…' : (dates.length ? 'Choose an available date' : 'No available dates')"></option>
                        <template x-for="day in dates" :key="day.value"><option :value="day.value" x-text="day.label"></option></template>
                    </select>
                    @error('appointment_date')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Time -->
                <div>
                    <label for="appointment_time" class="block text-sm font-medium text-[#f8efd8]">Preferred Time</label>
                    <select id="appointment_time" name="appointment_time" x-model="selectedTime" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black px-4 py-2.5 text-[#fff9ea] focus:border-[#d5a93b] focus:ring-[#d5a93b]" required>
                        <option value="" x-text="loading ? 'Loading available times…' : (slots.length ? 'Choose an available time' : 'No available times')"></option>
                        <template x-for="slot in slots" :key="slot.value"><option :value="slot.value" x-text="slot.label"></option></template>
                    </select>
                    <p x-show="!slots.length && !loading" class="mt-1 text-xs text-amber-200">No valid appointments are available on this date.</p>
                    @error('appointment_time')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2 rounded-md border border-[#c8a24a]/20 bg-black/50 p-4 text-sm text-[#f8efd8]">
                    <div class="flex justify-between"><span>Service price</span><span x-text="money(originalPrice)"></span></div>
                    <div x-show="appointmentType === 'home_service' && visitCharge > 0" class="mt-1 flex justify-between"><span>Home visit charge</span><span x-text="money(visitCharge)"></span></div>
                    <div class="mt-1 flex justify-between"><span>Original amount</span><span x-text="money(originalPrice + visitCharge)"></span></div>
                    <div class="mt-1 flex justify-between text-[#f4d27a]"><span>Online Booking Discount (10%)</span><span x-text="'-' + money(discount)"></span></div>
                    <div class="mt-2 flex justify-between border-t border-[#c8a24a]/20 pt-2 text-base font-bold"><span>Booking amount</span><span x-text="money(originalPrice + visitCharge - discount)"></span></div>
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
    <script>
        function bookingForm() {
            return {
                appointmentType: '{{ old('appointment_type', request('type') === 'home' ? 'home_service' : 'salon_visit') }}', submitting: false, slots: [], dates: [], loading: false, selectedTime: @js(old('appointment_time', '')), selectedDate: @js(old('appointment_date', $defaultDate)),
                get originalPrice() { return Number(document.querySelector('#service_slug option:checked')?.dataset.price || 0); },
                get visitCharge() { return this.appointmentType === 'home_service' ? Number(document.querySelector('#service_slug option:checked')?.dataset.homeVisitCharge || 0) : 0; },
                get discount() { return Math.round(this.originalPrice * 10) / 100; },
                money(value) { return new Intl.NumberFormat('en-IN', {style:'currency', currency:'INR', maximumFractionDigits:2}).format(value || 0); },
                async init() { document.querySelector('#service_slug').addEventListener('change', () => this.loadSlots()); await this.loadSlots(); },
                async loadSlots() {
                    const service = document.querySelector('#service_slug').value, date = this.selectedDate;
                    this.slots = []; this.selectedTime = ''; if (!service) return;
                    this.loading = true;
                    try {
                        const url = new URL(@js(route('appointments.availability')), window.location.origin); url.search = new URLSearchParams({service_slug:service, appointment_type:this.appointmentType, ...(date ? {date} : {})});
                        const response = await fetch(url); const data = await response.json();
                        this.dates = data.dates || [];
                        if (!this.dates.some(day => day.value === date)) this.selectedDate = data.selected_date || this.dates[0]?.value || '';
                        this.slots = this.selectedDate === data.selected_date ? (data.slots || []) : [];
                        if (this.selectedDate && !this.slots.length && this.dates.some(day => day.value === this.selectedDate)) {
                            const selectedUrl = new URL(@js(route('appointments.availability')), window.location.origin); selectedUrl.search = new URLSearchParams({service_slug:service,appointment_type:this.appointmentType,date:this.selectedDate});
                            const selectedResponse = await fetch(selectedUrl); const selectedData = await selectedResponse.json(); this.slots = selectedData.slots || [];
                        }
                    }
                    finally { this.loading = false; }
                }
            }
        }
    </script>
</x-layouts.public>
