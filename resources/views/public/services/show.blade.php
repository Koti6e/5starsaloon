<x-layouts.public :settings="$settings" title="{{ $service->name }} | 5 Star New Look Salon">
    <section class="bg-[#0d0b08] px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-2">
            @php
                $galleryImages = $service->images->isNotEmpty()
                    ? $service->images
                    : collect([(object) ['image_path' => $service->coverImageUrl(), 'thumbnail_path' => $service->coverImageUrl(), 'alt_text' => $service->name]]);
                $firstImage = $galleryImages->first();
            @endphp
            <div x-data="{ mainImage: '{{ asset($firstImage->image_path) }}', mainAlt: @js($firstImage->alt_text) }">
                <img :src="mainImage" :alt="mainAlt" class="aspect-[4/3] w-full rounded-lg border border-[#c8a24a]/20 object-cover">
                <div class="mt-4 grid grid-cols-4 gap-3">
                    @foreach ($galleryImages->take(4) as $image)
                        <button type="button" class="rounded-md border border-[#c8a24a]/25 p-1 focus:border-[#f4d27a] focus:outline-none" @click="mainImage = '{{ asset($image->image_path) }}'; mainAlt = @js($image->alt_text)">
                            <img src="{{ asset($image->thumbnail_path ?: $image->image_path) }}" alt="{{ $image->alt_text }}" class="aspect-square w-full rounded object-cover" loading="lazy">
                        </button>
                    @endforeach
                </div>
            </div>
            <div>
                <p class="text-sm font-semibold uppercase text-[#c8a24a]">{{ $service->publicCategoryName() }}</p>
                <h1 class="mt-3 font-serif text-4xl text-[#fff9ea]">{{ $service->name }}</h1>
                <p class="mt-5 text-lg leading-8 text-[#d8c8a3]">{{ $service->detailed_description ?: $service->short_description }}</p>
                <div class="mt-6 flex flex-wrap gap-4 text-[#f8efd8]">
                    @if ($service->duration_minutes)
                        <span>{{ $service->duration_minutes }} minutes</span>
                    @endif
                    <span>{{ $service->displayPrice() }}</span>
                    <span class="rounded-sm border border-[#c8a24a]/30 px-2 py-1 text-xs font-semibold uppercase text-[#f4d27a]">{{ $service->is_package ? $service->packageBadge() : $service->priceBadge() }}</span>
                    @if ($service->discounted_price && $service->price && $service->discounted_price < $service->price)
                        <span class="text-[#a89567] line-through">{{ \App\Support\Money::inr($service->price) }}</span>
                    @endif
                </div>
                @if ($service->pricing_note)
                    <p class="mt-4 text-sm leading-6 text-[#a89567]">{{ $service->pricing_note }}</p>
                @endif
                @if ($service->is_package && $service->included_services)
                    <div class="mt-6 rounded-lg border border-[#c8a24a]/20 bg-black p-5">
                        <h2 class="font-serif text-xl text-[#f4d27a]">SMART SAVER PACKAGE Includes</h2>
                        <ul class="mt-3 grid gap-2 text-sm text-[#d8c8a3]">
                            @foreach ($service->included_services as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <p class="mt-4 text-sm text-[#f4d27a]">{{ $service->is_home_service_available ? 'Salon visit and Elite Home Service available.' : 'Available for salon visits.' }}</p>
                <a href="{{ route('appointments.book', ['service' => $service->slug]) }}" class="mt-8 inline-block rounded-md bg-[#d5a93b] px-6 py-3 font-semibold text-[#111]">{{ $service->publicBookingLabel() }}</a>
            </div>
        </div>
    </section>
    <section class="bg-black px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <h2 class="font-serif text-3xl text-[#f4d27a]">Related Services</h2>
            <div class="mt-8 grid gap-6 md:grid-cols-3">
                @forelse ($relatedServices as $related)
                    <x-service-card :service="$related" />
                @empty
                    <p class="rounded-lg border border-[#c8a24a]/25 bg-[#11100d] p-8 text-[#d8c8a3] md:col-span-3">Related active services will appear here as the catalogue grows.</p>
                @endforelse
            </div>
        </div>
    </section>
</x-layouts.public>
