<x-app-layout>
    <x-slot name="header">
        <h1 class="font-serif text-2xl text-[#f4d27a]">Settings</h1>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <p class="mb-5 rounded-md border border-[#c8a24a]/30 bg-[#11100d] p-3 text-sm text-[#f4d27a]">{{ session('status') }}</p>
            @endif

            <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                @foreach ([
                    'Business Profile' => [
                        ['salon_name', 'Salon Name', 'text'], ['tagline', 'Tagline', 'text'],
                        ['address', 'Address', 'textarea'], ['area', 'Area', 'text'], ['city', 'City', 'text'], ['state', 'State', 'text'], ['pincode', 'Pincode', 'text'],
                        ['primary_phone', 'Phone Number', 'text'], ['alternate_phone', 'Alternate Phone', 'text'], ['email', 'Email', 'email'],
                        ['google_maps_url', 'Google Maps URL', 'url'], ['working_hours', 'Working Hours', 'text'], ['weekly_holiday', 'Weekly Holiday', 'text'],
                    ],
                    'Social Links' => [
                        ['instagram_url', 'Instagram URL', 'url'], ['facebook_url', 'Facebook URL', 'url'], ['youtube_url', 'YouTube URL', 'url'],
                    ],
                    'WhatsApp' => [
                        ['whatsapp_number', 'WhatsApp Number', 'text'], ['whatsapp_default_message', 'Default WhatsApp Message', 'textarea'],
                    ],
                    'Invoice' => [
                        ['invoice_prefix', 'Invoice Prefix', 'text'], ['invoice_footer_text', 'Invoice Footer', 'textarea'], ['invoice_thank_you_message', 'Thank-you Message', 'textarea'],
                    ],
                    'Today’s Promotion' => [
                        ['promotion_title', 'Promotion Title', 'text'], ['promotion_subtitle', 'Promotion Subtitle', 'text'], ['promotion_offer_price', 'Offer Price', 'text'],
                        ['promotion_start_date', 'Start Date', 'date'], ['promotion_end_date', 'End Date', 'date'], ['promotion_button_text', 'Button Text', 'text'], ['promotion_button_link', 'Button Link', 'text'],
                    ],
                ] as $section => $fields)
                    <section class="rounded-lg border border-[#c8a24a]/20 bg-[#11100d] p-5">
                        <h2 class="font-serif text-xl text-[#f4d27a]">{{ $section }}</h2>
                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            @foreach ($fields as [$key, $label, $type])
                                <label class="block {{ $type === 'textarea' ? 'sm:col-span-2' : '' }}">
                                    <span class="text-sm font-semibold text-[#f8efd8]">{{ $label }}</span>
                                    @if ($type === 'textarea')
                                        <textarea name="{{ $key }}" rows="3" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]">{{ old($key, $settings[$key] ?? '') }}</textarea>
                                    @else
                                        <input name="{{ $key }}" type="{{ $type }}" value="{{ old($key, $settings[$key] ?? '') }}" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]">
                                    @endif
                                    <x-input-error :messages="$errors->get($key)" class="mt-2" />
                                </label>
                            @endforeach
                        </div>
                    </section>
                @endforeach

                <section class="rounded-lg border border-[#c8a24a]/20 bg-[#11100d] p-5">
                    <h2 class="font-serif text-xl text-[#f4d27a]">Appearance & Media</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-semibold text-[#f8efd8]">Default Theme</span>
                            @php($selectedTheme = match ($settings['default_theme'] ?? 'emerald') {
                                'light' => 'pearl',
                                'dark' => 'obsidian',
                                default => $settings['default_theme'] ?? 'emerald',
                            })
                            <select name="default_theme" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]">
                                <option value="emerald" @selected($selectedTheme === 'emerald')>Neon Emerald</option>
                                <option value="sapphire" @selected($selectedTheme === 'sapphire')>Neon Sapphire</option>
                                <option value="crimson" @selected($selectedTheme === 'crimson')>Neon Crimson</option>
                                <option value="gold" @selected($selectedTheme === 'gold')>Neon Gold</option>
                                <option value="pearl" @selected($selectedTheme === 'pearl')>Neon Pearl</option>
                                <option value="obsidian" @selected($selectedTheme === 'obsidian')>Neon Obsidian</option>
                            </select>
                        </label>
                        <label class="flex items-center gap-3 text-sm font-semibold text-[#f8efd8]">
                            <input type="checkbox" name="whatsapp_floater_enabled" value="1" @checked(filter_var($settings['whatsapp_floater_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)) class="rounded border-[#c8a24a]/40 bg-black text-[#d5a93b]">
                            Enable WhatsApp Floater
                        </label>
                        <label class="flex items-center gap-3 text-sm font-semibold text-[#f8efd8]">
                            <input type="checkbox" name="promotion_enabled" value="1" @checked(filter_var($settings['promotion_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)) class="rounded border-[#c8a24a]/40 bg-black text-[#d5a93b]">
                            Enable Promotion
                        </label>
                        @foreach ([['logo', 'Logo'], ['favicon', 'Favicon'], ['promotion_image', 'Promotion Image']] as [$key, $label])
                            <label class="block">
                                <span class="text-sm font-semibold text-[#f8efd8]">{{ $label }}</span>
                                <input name="{{ $key }}" type="file" accept="image/*" class="mt-1 w-full rounded-md border border-[#c8a24a]/30 bg-black p-2 text-[#fff9ea]">
                                <x-input-error :messages="$errors->get($key)" class="mt-2" />
                            </label>
                        @endforeach
                    </div>
                </section>

                <button class="w-full rounded-md bg-[#d5a93b] px-5 py-3 text-sm font-bold uppercase tracking-[0.16em] text-black sm:w-auto">Save Settings</button>
            </form>
        </div>
    </div>
</x-app-layout>
