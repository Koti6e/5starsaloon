<x-layouts.public :settings="$settings" title="5 Star New Look Salon | Best Unisex Salon & Spa in Chengalpattu">
    <!-- Hero Section -->
    <section class="relative min-h-[calc(100vh-72px)] overflow-hidden" data-hero>
        <img 
            src="{{ asset('images/salon/premium-salon-hero.webp') }}" 
            alt="Luxury interior of 5 Star New Look Salon featuring modern styling chairs and ambient lighting in Chengalpattu" 
            class="absolute inset-0 h-full w-full object-cover"
        >
        <div class="absolute inset-0 bg-gradient-to-r from-black via-black/85 to-black/30" aria-hidden="true"></div>
        
        <div class="relative mx-auto flex min-h-[calc(100vh-72px)] max-w-7xl items-center px-4 py-16 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <img 
                    src="{{ asset('images/brand/logo-mark.webp') }}" 
                    alt="5 Star New Look Salon official logo mark" 
                    class="mb-6 h-24 w-24 rounded-full object-contain"
                >
                <p class="text-sm font-semibold uppercase tracking-wider text-[#f4d27a]">Premier Salon, Hair & Spa Experience</p>
                <h1 class="mt-4 font-serif text-4xl font-bold leading-tight text-[#fff9ea] sm:text-6xl">
                    Define Your Look. Elevate Your Confidence.
                </h1>
                <p class="mt-6 max-w-2xl text-lg leading-relaxed text-[#eadfca]">
                    Welcome to Chengalpattu's premier destination for professional hair styling, advanced facial care, rejuvenating spa treatments, and elite grooming. Crafted with precision and personal care.
                </p>
                
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a 
                        href="{{ route('appointments.book') }}" 
                        class="rounded-md bg-[#d5a93b] px-6 py-3 text-center font-semibold text-[#111] transition-colors hover:bg-[#e0b749]"
                    >
                        Book Appointment
                    </a>
                    <a 
                        href="{{ route('services.index') }}" 
                        class="rounded-md border border-[#c8a24a]/50 px-6 py-3 text-center font-semibold text-[#fff9ea] transition-colors hover:bg-white/5"
                    >
                        Explore Services
                    </a>
                </div>

                <div class="mt-10 grid grid-cols-2 gap-3 text-sm text-[#f8efd8] sm:grid-cols-4">
                    @foreach (['Professional Care', 'Hygienic Service', 'Transparent Pricing', 'Elite Home Visits'] as $highlight)
                        <div class="rounded-md border border-[#c8a24a]/25 bg-black/35 px-4 py-3 text-center font-medium">
                            {{ $highlight }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <!-- Signature Services Section -->
    <section id="services" class="bg-[#0d0b08] px-4 py-20 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
                <div>
                    <span class="inline-block text-xs font-semibold uppercase tracking-wider text-[#c8a24a]">Premium Selection</span>
                    <h2 class="mt-2 font-serif text-4xl text-[#f4d27a]">Our Signature Services</h2>
                    <p class="mt-4 max-w-2xl text-lg text-[#d8c8a3]">Carefully selected hair, beauty, spa, and grooming treatments designed around your unique style and ultimate comfort.</p>
                </div>
                <a href="{{ route('services.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-[#f4d27a] transition-all hover:gap-3">
                    View all services
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            <div class="mt-12 grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($featuredServices as $service)
                    <x-service-card :service="$service" />
                @empty
                    <div class="rounded-xl border border-[#c8a24a]/25 bg-[#11100d] p-12 text-center text-[#d8c8a3] md:col-span-2 lg:col-span-3">
                        <p class="text-lg">Featured services will appear here once active services are added from the management area.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Smart Saver Packages Section -->
    <section class="bg-black px-4 py-20 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
                <div>
                    <span class="inline-block text-xs font-semibold uppercase tracking-wider text-[#c8a24a]">Smart Savings</span>
                    <h2 class="mt-2 font-serif text-3xl text-[#f4d27a] sm:text-4xl">Premium Grooming Packages</h2>
                    <p class="mt-4 max-w-2xl text-lg text-[#d8c8a3]">Enjoy complete makeover combinations together and save more with our exclusive value-packed grooming packages.</p>
                </div>
                <a href="{{ route('services.index', ['packages' => 1]) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-[#f4d27a] transition-all hover:gap-3">
                    View Packages
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            <div class="mt-12 grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($packageServices as $service)
                    <x-service-card :service="$service" />
                @empty
                    <div class="rounded-xl border border-[#c8a24a]/25 bg-[#11100d] p-12 text-center text-[#d8c8a3] md:col-span-2 lg:col-span-3">
                        <span class="inline-flex rounded-full border border-[#c8a24a]/30 px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] text-[#f4d27a]">Coming Soon</span>
                        <h3 class="mt-4 font-serif text-2xl text-[#fff9ea]">Premium Grooming Packages</h3>
                        <p class="mx-auto mt-3 max-w-2xl text-base leading-7">Discover exclusive grooming experiences carefully crafted for every style and occasion.</p>
                        <p class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-[#bfae88]">Our signature grooming packages are launching soon. Stay tuned for premium combinations designed to deliver exceptional value, luxury, and complete grooming care.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Professional Service Categories Section -->
    <section class="bg-[#0d0b08] px-4 py-20 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto mb-16 max-w-3xl text-center">
                <span class="inline-block text-xs font-semibold uppercase tracking-wider text-[#c8a24a]">Our Expertise</span>
                <h2 class="mt-2 font-serif text-4xl text-[#f4d27a]">Professional Service Categories</h2>
                <p class="mt-4 text-lg text-[#d8c8a3]">Explore our comprehensive range of luxury salon services tailored across specialized categories.</p>
            </div>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                @foreach ($categories as $category)
                    <a 
                        href="{{ route('services.index', ['category' => $category->slug]) }}" 
                        class="group relative overflow-hidden rounded-2xl border border-[#c8a24a]/20 bg-gradient-to-br from-[#11100d] to-[#0a0907] p-8 transition-all duration-300 hover:-translate-y-1 hover:border-[#f4d27a] hover:shadow-xl hover:shadow-[#d5a93b]/10"
                    >
                        <div class="absolute right-0 top-0 h-32 w-32 rounded-full bg-[#d5a93b]/5 blur-2xl transition-all group-hover:bg-[#d5a93b]/10" aria-hidden="true"></div>
                        <div class="relative">
                            <span class="mb-4 inline-block text-4xl">{{ $category->icon ?? '✦' }}</span>
                            <h3 class="font-serif text-2xl text-[#fff9ea] transition-colors group-hover:text-[#f4d27a]">{{ $category->name }}</h3>
                            <p class="mt-3 text-sm leading-relaxed text-[#d8c8a3]">{{ $category->description ?? 'Professional services tailored to your needs.' }}</p>
                            <span class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-[#f4d27a] opacity-0 transition-all group-hover:opacity-100">
                                Explore
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Featured Services by Category Loops -->
    @foreach ([
        'Hair & Grooming' => [$hairServices, 'haircuts-grooming', '✂️'],
        'Facial Care' => [$facialServices, 'facial-cleanup', '✨'],
        'Hair Colouring' => [$colourServices, 'hair-colouring', '🎨'],
        'Oil Massage' => [$oilServices, 'oil-massage', '💆'],
    ] as $heading => [$collection, $categorySlug, $icon])
        <section class="{{ $loop->odd ? 'bg-black' : 'bg-[#0d0b08]' }} px-4 py-16 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="flex flex-col items-start justify-between gap-4 border-b border-[#c8a24a]/10 pb-6 sm:flex-row sm:items-end sm:gap-6">
                    <div>
                        <span class="mr-3 text-2xl" aria-hidden="true">{{ $icon }}</span>
                        <h2 class="inline font-serif text-3xl text-[#f4d27a]">{{ $heading }}</h2>
                        <p class="mt-2 text-sm text-[#d8c8a3]">Top-tier treatments crafted for your individual aesthetic</p>
                    </div>
                    <a href="{{ route('services.index', ['category' => $categorySlug]) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-[#f4d27a] transition-all hover:gap-3">
                        View All
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>

                <div class="mt-10 grid gap-8 md:grid-cols-2 lg:grid-cols-4">
                    @forelse ($collection as $service)
                        <x-service-card :service="$service" />
                    @empty
                        <div class="rounded-xl border border-[#c8a24a]/25 bg-[#11100d] p-12 text-center text-[#d8c8a3] md:col-span-2 lg:col-span-4">
                            <span class="block text-lg">This service collection is being curated and will be available soon.</span>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    @endforeach

    <!-- Elite Home Service Section -->
    <section class="bg-black px-4 py-24 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-12 lg:grid-cols-2 lg:items-center lg:gap-16">
                <div>
                    <span class="inline-block text-xs font-semibold uppercase tracking-wider text-[#c8a24a]">Premium Convenience</span>
                    <h2 class="mt-3 font-serif text-5xl leading-tight text-[#fff9ea]">
                        Salon Excellence,<br>Reserved for Your Space.
                    </h2>
                    <p class="mt-6 text-lg leading-relaxed text-[#d8c8a3]">
                        Experience ultimate relaxation and expert grooming in the comfort of your home. We bring professional salon care, complete hygiene protocols, and personalized attention straight to your doorstep.
                    </p>
                    <div class="mt-10 flex flex-col gap-4 sm:flex-row">
                        <a 
                            href="{{ route('appointments.book', ['type' => 'home']) }}" 
                            class="inline-flex items-center justify-center rounded-md bg-[#d5a93b] px-8 py-4 text-center font-semibold text-[#111] transition-colors hover:bg-[#e0b749]"
                        >
                            Reserve Elite Home Visit
                        </a>
                        <a 
                            href="{{ route('services.index', ['home_service' => 1]) }}" 
                            class="inline-flex items-center justify-center rounded-md border border-[#c8a24a]/40 px-8 py-4 text-center font-semibold text-[#f8efd8] transition-colors hover:bg-white/5"
                        >
                            Explore Home Services
                        </a>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ([
                        'Selected services at home',
                        'Preferred date and time',
                        'Address-based booking',
                        'Transparent visit charges',
                        'Dedicated staff assignment',
                        'Booking confirmation'
                    ] as $item)
                        <div class="flex items-start gap-3 rounded-xl border border-[#c8a24a]/20 bg-[#11100d] p-5 text-[#f8efd8] transition-all hover:border-[#f4d27a]/40">
                            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-[#f4d27a]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-sm font-medium">{{ $item }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <!-- The 5 Star Experience Section -->
    <section class="bg-[#0d0b08] px-4 py-20 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto mb-16 max-w-3xl text-center">
                <span class="inline-block text-xs font-semibold uppercase tracking-wider text-[#c8a24a]">Why Choose Us</span>
                <h2 class="mt-2 font-serif text-4xl text-[#f4d27a]">The 5 Star Experience</h2>
                <p class="mt-4 text-lg text-[#d8c8a3]">Every visit is thoughtfully curated to provide an exceptional beauty and grooming journey.</p>
            </div>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    'Skilled Professionals' => 'Trained expert stylists dedicated to superior technique, modern trends, and flawless finishes.',
                    'Hygiene First' => 'Strict sanitization protocols, sterilized equipment, and immaculate stations for absolute peace of mind.',
                    'Personalised Attention' => 'Consultation-first approach to align treatments closely with your unique skin type, hair texture, and style preferences.',
                    'Honest Service Pricing' => 'Absolute clarity with upfront pricing and zero hidden costs before confirming any service.',
                ] as $title => $copy)
                    <article class="group rounded-xl border border-[#c8a24a]/20 bg-gradient-to-br from-[#11100d] to-[#0a0907] p-8 transition-all duration-300 hover:-translate-y-1 hover:border-[#f4d27a] hover:shadow-xl hover:shadow-[#d5a93b]/10">
                        <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-full bg-[#d5a93b]/10 transition-colors group-hover:bg-[#d5a93b]/20">
                            <svg class="h-6 w-6 text-[#f4d27a]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <h3 class="font-serif text-xl text-[#fff9ea] transition-colors group-hover:text-[#f4d27a]">{{ $title }}</h3>
                        <p class="mt-3 text-sm leading-relaxed text-[#d8c8a3]">{{ $copy }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Visual Gallery Section -->
    <section class="bg-black px-4 py-20 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="flex items-end justify-between gap-6 border-b border-[#c8a24a]/10 pb-6">
                <div>
                    <span class="inline-block text-xs font-semibold uppercase tracking-wider text-[#c8a24a]">Visual Tour</span>
                    <h2 class="mt-1 font-serif text-4xl text-[#f4d27a]">Inside the 5 Star Experience</h2>
                </div>
                <a href="{{ route('gallery') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-[#f4d27a] transition-all hover:gap-3">
                    View Full Gallery
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                @forelse ($galleryImages as $image)
                    <a href="{{ asset($image->image) }}" class="group relative overflow-hidden rounded-xl border border-[#c8a24a]/20 transition-all duration-300 hover:border-[#f4d27a] hover:shadow-xl hover:shadow-[#d5a93b]/10">
                        <img 
                            src="{{ asset($image->image) }}" 
                            alt="{{ $image->alt_text }}" 
                            class="aspect-[4/5] h-full w-full object-cover transition-transform duration-500 group-hover:scale-110" 
                            loading="lazy"
                        >
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 transition-opacity group-hover:opacity-100" aria-hidden="true"></div>
                    </a>
                @empty
                    <div class="rounded-xl border border-[#c8a24a]/25 bg-[#11100d] p-12 text-center text-[#d8c8a3] sm:col-span-2 lg:col-span-5">
                        <span class="block text-lg">Approved gallery images will appear here once uploaded.</span>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Contact & Location Section -->
    <section class="bg-[#0d0b08] px-4 py-20 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-12 lg:grid-cols-2 lg:gap-16">
                <div>
                    <span class="inline-block text-xs font-semibold uppercase tracking-wider text-[#c8a24a]">Visit Us</span>
                    <h2 class="mt-2 font-serif text-4xl text-[#f4d27a]">Confidence Begins with the Right Care</h2>
                    <p class="mt-5 text-lg leading-relaxed text-[#d8c8a3]">
                        At 5 Star New Look Salon in Chengalpattu, every service is approached with extreme care, meticulous hygiene standards, and a deep respect for your personal style. Step into a tranquil, welcoming environment designed for complete relaxation.
                    </p>
                    <a href="{{ route('about') }}" class="mt-8 inline-flex items-center gap-2 rounded-md border border-[#c8a24a]/40 px-8 py-4 font-semibold text-[#f8efd8] transition-colors hover:bg-white/5">
                        Discover Our Story
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>

                <div class="rounded-2xl border border-[#c8a24a]/25 bg-gradient-to-br from-[#11100d] to-[#0a0907] p-10">
                    <h3 class="font-serif text-2xl text-[#fff9ea]">Contact and Location</h3>
                    <dl class="mt-6 space-y-4 text-sm">
                        <div class="flex flex-col gap-1">
                            <dt class="text-xs font-semibold uppercase tracking-wider text-[#c8a24a]">Address</dt>
                            <dd class="text-[#d8c8a3]">{{ $settings['address'] ?? 'Visit the salon for location details.' }}</dd>
                        </div>
                        <div class="flex flex-col gap-1">
                            <dt class="text-xs font-semibold uppercase tracking-wider text-[#c8a24a]">Phone</dt>
                            <dd class="text-[#d8c8a3]">{{ $settings['primary_phone'] ?? 'Available from salon reception.' }}</dd>
                        </div>
                        <div class="flex flex-col gap-1">
                            <dt class="text-xs font-semibold uppercase tracking-wider text-[#c8a24a]">Working Hours</dt>
                            <dd class="text-[#d8c8a3]">{{ $settings['working_hours'] ?? 'Confirmed by the salon team.' }}</dd>
                        </div>
                        <div class="flex flex-col gap-1">
                            <dt class="text-xs font-semibold uppercase tracking-wider text-[#c8a24a]">Weekly Holiday</dt>
                            <dd class="text-[#d8c8a3]">{{ $settings['weekly_holiday'] ?? 'Confirmed by the salon team.' }}</dd>
                        </div>
                    </dl>

                    <div class="mt-8 grid gap-3 sm:grid-cols-3">
                        <a 
                            href="{{ filled($settings['primary_phone'] ?? null) && preg_match('/\d/', $settings['primary_phone']) ? 'tel:'.$settings['primary_phone'] : route('contact') }}" 
                            class="rounded-md bg-[#d5a93b] px-4 py-3.5 text-center text-sm font-semibold text-[#111] transition-colors hover:bg-[#e0b749]"
                        >
                            Call Now
                        </a>
                        <a 
                            href="{{ route('contact') }}" 
                            class="rounded-md border border-[#c8a24a]/40 px-4 py-3.5 text-center text-sm font-semibold text-[#f8efd8] transition-colors hover:bg-white/5"
                        >
                            Message Us
                        </a>
                        <a 
                            href="{{ $settings['google_maps_url'] ?? route('contact') }}" 
                            class="rounded-md border border-[#c8a24a]/40 px-4 py-3.5 text-center text-sm font-semibold text-[#f8efd8] transition-colors hover:bg-white/5"
                        >
                            Get Directions
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
