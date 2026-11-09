@php
    $businessAddress = $settings['address'] ?? '4/123, Grand Southern Trunk Road, Otteri Main Road';
    $businessArea = $settings['area'] ?? 'Vandalur';
    $businessCity = $settings['city'] ?? 'Chennai';
    $businessState = $settings['state'] ?? 'Tamil Nadu';
    $businessPincode = $settings['pincode'] ?? '600048';
    $mapsUrl = $settings['google_maps_url'] ?? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($businessAddress.', '.$businessArea.', '.$businessCity.', '.$businessState.' '.$businessPincode.', India');
    $whatsappDigits = preg_replace('/\D+/', '', (string) ($settings['whatsapp_number'] ?? ''));
    $whatsappDestination = strlen($whatsappDigits) === 10 ? '91'.$whatsappDigits : $whatsappDigits;
@endphp
<x-layouts.public :settings="$settings" title="Visit 5 Star New Look Salon in Vandalur, Chennai" description="Find 5 Star New Look A/C at Otteri Main Road, Vandalur, Chennai. Get directions, contact the salon or book an appointment.">
    <section class="bg-[#0d0b08] px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[0.9fr_1.1fr]">
            <div>
                <h1 class="font-serif text-4xl text-[#f4d27a]">Contact</h1>
                <dl class="mt-8 space-y-4 text-[#d8c8a3]">
                    <div><dt class="text-[#f4d27a]">Visit us</dt><dd>{{ $businessAddress }}, {{ collect([$businessArea, $businessCity, $businessState, $businessPincode])->filter()->implode(', ') }}</dd></div>
                    @if (filled($settings['primary_phone'] ?? null)) <div><dt class="text-[#f4d27a]">Phone</dt><dd><a href="tel:{{ preg_replace('/[^+\d]/', '', $settings['primary_phone']) }}">{{ $settings['primary_phone'] }}</a></dd></div> @endif
                    @if (filled($whatsappDestination)) <div><dt class="text-[#f4d27a]">WhatsApp</dt><dd><a href="https://wa.me/{{ $whatsappDestination }}">{{ $settings['whatsapp_number'] }}</a></dd></div> @endif
                    @if (filled($settings['email'] ?? null)) <div><dt class="text-[#f4d27a]">Email</dt><dd><a href="mailto:{{ $settings['email'] }}">{{ $settings['email'] }}</a></dd></div> @endif
                    <p class="mt-5"><a class="inline-flex rounded-md bg-[#d5a93b] px-5 py-3 font-semibold text-[#111]" href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer">Get Directions</a></p>
                    <div class="mt-6"><x-public-opening-hours :settings="$settings" /></div>
                    <p class="mt-5"><a class="text-[#f4d27a] underline" href="{{ route('appointments.book') }}">Book an appointment</a></p>
                </dl>
            </div>
            <div class="overflow-hidden rounded-lg border border-[#c8a24a]/20 bg-black lg:order-last">
                <iframe title="Map to 5 Star New Look A/C in Vandalur" src="https://www.google.com/maps?q={{ rawurlencode($businessAddress.', '.$businessArea.', '.$businessCity.', '.$businessState.' '.$businessPincode.', India') }}&output=embed" class="h-full min-h-[320px] w-full border-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
            <form method="POST" action="{{ route('contact.store') }}" class="rounded-lg border border-[#c8a24a]/20 bg-[#11100d] p-6">
                @csrf
                <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off">
                @if (session('status'))
                    <p class="mb-5 rounded-md border border-[#c8a24a]/30 bg-black p-3 text-sm text-[#f4d27a]">{{ session('status') }}</p>
                @endif
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="text-sm text-[#f8efd8]">Name<input name="name" value="{{ old('name') }}" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]"></label>
                    <label class="text-sm text-[#f8efd8]">Mobile<input name="mobile" value="{{ old('mobile') }}" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]"></label>
                    <label class="text-sm text-[#f8efd8] sm:col-span-2">Email<input name="email" value="{{ old('email') }}" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]"></label>
                    <label class="text-sm text-[#f8efd8] sm:col-span-2">Subject<input name="subject" value="{{ old('subject') }}" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]"></label>
                    <label class="text-sm text-[#f8efd8] sm:col-span-2">Message<textarea name="message" rows="5" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]">{{ old('message') }}</textarea></label>
                </div>
                <label class="mt-4 flex gap-3 text-sm text-[#d8c8a3]"><input type="checkbox" name="consent" value="1" class="mt-1 rounded border-[#c8a24a]/40 bg-black text-[#d5a93b]"> I agree to be contacted about this enquiry.</label>
                @if ($errors->any())
                    <div class="mt-4 rounded-md border border-red-400/40 bg-red-950/40 p-3 text-sm text-red-100">{{ $errors->first() }}</div>
                @endif
                <button class="mt-6 rounded-md bg-[#d5a93b] px-6 py-3 font-semibold text-[#111]">Send Enquiry</button>
            </form>
        </div>
    </section>
</x-layouts.public>
