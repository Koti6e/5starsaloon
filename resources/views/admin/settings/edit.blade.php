<x-app-layout>
    <x-slot name="header">
        <h1 class="font-serif text-2xl text-[var(--app-text)]">Settings</h1>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <p class="mb-5 rounded-md border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-3 text-sm text-[var(--app-text)]">{{ session('status') }}</p>
            @endif

            <p class="mb-5 rounded-md border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-4 text-sm leading-6 text-[var(--app-text-muted)]">
                Store verified business details from the exact “5 Star New Look A/C” Google Maps listing here. The listing’s full address, Maps URL or Place ID, coordinates, phone and opening hours have not been independently verified yet. Public contact details and structured data use only values saved in these settings.
            </p>

            <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                @foreach ([
                    'Business Profile' => [
                        ['salon_name', 'Salon Name', 'text'], ['tagline', 'Tagline', 'text'],
                        ['address', 'Address', 'textarea'], ['area', 'Area', 'text'], ['city', 'City', 'text'], ['state', 'State', 'text'], ['pincode', 'Pincode', 'text'],
                        ['primary_phone', 'Phone Number', 'text'], ['alternate_phone', 'Alternate Phone', 'text'], ['email', 'Email', 'email'],
                        ['google_maps_url', 'Google Maps URL', 'url'], ['google_place_id', 'Google Maps Place ID (verified)', 'text'], ['latitude', 'Latitude (verified)', 'text'], ['longitude', 'Longitude (verified)', 'text'],
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
                    <section class="rounded-lg border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-5">
                        <h2 class="font-serif text-xl text-[var(--app-text)]">{{ $section }}</h2>
                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            @foreach ($fields as [$key, $label, $type])
                                <label class="block {{ $type === 'textarea' ? 'sm:col-span-2' : '' }}">
                                    <span class="text-sm font-semibold text-[var(--app-text)]">{{ $label }}</span>
                                    @if ($type === 'textarea')
                                        <textarea name="{{ $key }}" rows="3" class="mt-1 w-full rounded-md border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-text)]">{{ old($key, $settings[$key] ?? '') }}</textarea>
                                    @else
                                        <input name="{{ $key }}" type="{{ $type }}" value="{{ old($key, $settings[$key] ?? '') }}" class="mt-1 w-full rounded-md border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-text)]">
                                    @endif
                                    <x-input-error :messages="$errors->get($key)" class="mt-2" />
                                </label>
                            @endforeach
                        </div>
                    </section>
                @endforeach

                @php
                    $weeklyHours = \App\Support\WorkingHours::schedule($settings);
                @endphp
                <section class="rounded-lg border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-5">
                    <h2 class="font-serif text-xl text-[var(--app-text)]">Working Hours</h2>
                    <p class="mt-1 text-sm text-[var(--app-text-muted)]">Set the public opening schedule. Closed days are excluded automatically.</p>
                    <div class="mt-5 space-y-3">
                        @foreach (\App\Support\WorkingHours::DAYS as $day)
                            @php
                                $hours = $weeklyHours[$day];
                            @endphp
                            <div class="grid items-end gap-3 rounded-md border border-[var(--app-border)] p-3 sm:grid-cols-[1fr_auto_1fr_1fr]">
                                <span class="font-semibold text-[var(--app-text)]">{{ $day }}</span>
                                <label class="flex items-center gap-2 text-sm text-[var(--app-text)]"><input type="checkbox" name="weekly_working_hours[{{ $day }}][open]" value="1" @checked($hours['open'])> Open</label>
                                <label class="text-sm text-[var(--app-text)]">Opens<input type="time" name="weekly_working_hours[{{ $day }}][opens]" value="{{ old("weekly_working_hours.$day.opens", $hours['opens']) }}" class="mt-1 w-full rounded-md border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-text)]"></label>
                                <label class="text-sm text-[var(--app-text)]">Closes<input type="time" name="weekly_working_hours[{{ $day }}][closes]" value="{{ old("weekly_working_hours.$day.closes", $hours['closes']) }}" class="mt-1 w-full rounded-md border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-text)]"></label>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-lg border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-5">
                    <h2 class="font-serif text-xl text-[var(--app-text)]">Appearance & Media</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-semibold text-[var(--app-text)]">Default Theme</span>
                            @php
                                $selectedTheme = match ($settings['default_theme'] ?? 'emerald') {
                                    'light' => 'pearl',
                                    'dark' => 'obsidian',
                                    default => $settings['default_theme'] ?? 'emerald',
                                };
                            @endphp
                            <select name="default_theme" class="mt-1 w-full rounded-md border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-text)]">
                                <option value="emerald" @selected($selectedTheme === 'emerald')>Neon Emerald</option>
                                <option value="sapphire" @selected($selectedTheme === 'sapphire')>Neon Sapphire</option>
                                <option value="crimson" @selected($selectedTheme === 'crimson')>Neon Crimson</option>
                                <option value="gold" @selected($selectedTheme === 'gold')>Neon Gold</option>
                                <option value="pearl" @selected($selectedTheme === 'pearl')>Neon Pearl</option>
                                <option value="obsidian" @selected($selectedTheme === 'obsidian')>Neon Obsidian</option>
                            </select>
                        </label>
                        <label class="flex items-center gap-3 text-sm font-semibold text-[var(--app-text)]">
                            <input type="checkbox" name="whatsapp_floater_enabled" value="1" @checked(filter_var($settings['whatsapp_floater_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)) class="rounded border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-primary)]">
                            Enable WhatsApp Floater
                        </label>
                        <label class="flex items-center gap-3 text-sm font-semibold text-[var(--app-text)]">
                            <input type="checkbox" name="promotion_enabled" value="1" @checked(filter_var($settings['promotion_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)) class="rounded border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-primary)]">
                            Enable Promotion
                        </label>
                        @foreach ([['logo', 'Logo'], ['favicon', 'Favicon'], ['promotion_image', 'Promotion Image']] as [$key, $label])
                            <label class="block">
                                <span class="text-sm font-semibold text-[var(--app-text)]">{{ $label }}</span>
                                <input name="{{ $key }}" type="file" accept="image/*" class="mt-1 w-full rounded-md border border-[var(--app-border)] bg-[var(--app-bg)] p-2 text-[var(--app-text)]">
                                <x-input-error :messages="$errors->get($key)" class="mt-2" />
                            </label>
                        @endforeach
                    </div>
                </section>

                <button class="w-full rounded-md bg-[var(--app-primary-strong)] px-5 py-3 text-sm font-bold uppercase tracking-[0.16em] text-black sm:w-auto">Save Settings</button>
            </form>
        </div>
    </div>
</x-app-layout>
