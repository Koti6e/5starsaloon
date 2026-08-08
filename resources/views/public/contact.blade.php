<x-layouts.public :settings="$settings" title="Contact | 5 Star New Look Salon">
    <section class="bg-[#0d0b08] px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[0.9fr_1.1fr]">
            <div>
                <h1 class="font-serif text-4xl text-[#f4d27a]">Contact</h1>
                <dl class="mt-8 space-y-4 text-[#d8c8a3]">
                    <div><dt class="text-[#f4d27a]">Address</dt><dd>{{ $settings['address'] ?? 'Visit the salon for location details.' }}</dd></div>
                    <div><dt class="text-[#f4d27a]">Phone</dt><dd>{{ filled($settings['primary_phone'] ?? null) ? $settings['primary_phone'] : 'Available from salon reception.' }}</dd></div>
                    <div><dt class="text-[#f4d27a]">WhatsApp</dt><dd>{{ filled($settings['whatsapp_number'] ?? null) ? $settings['whatsapp_number'] : 'Available after reception confirms the booking channel.' }}</dd></div>
                    <div><dt class="text-[#f4d27a]">Email</dt><dd>{{ filled($settings['email'] ?? null) ? $settings['email'] : 'Contact details available from reception.' }}</dd></div>
                    <div><dt class="text-[#f4d27a]">Working Hours</dt><dd>{{ $settings['working_hours'] ?? 'Open daily by appointment.' }}</dd></div>
                </dl>
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
