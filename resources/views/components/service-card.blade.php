@props(['service'])
<article class="group service-card overflow-hidden rounded-lg border border-[#c8a24a]/20 bg-[#11100d] shadow-xl shadow-black/25" data-service-card data-search="{{ Str::lower($service->name.' '.$service->publicCategoryName().' '.$service->short_description) }}">
    <div class="aspect-[4/3] bg-[#1b1711]">
        <img src="{{ asset($service->coverImageUrl()) }}" alt="{{ $service->coverImage()?->alt_text ?? $service->name }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
    </div>
    <div class="space-y-4 p-5">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <p class="text-xs font-semibold uppercase text-[#c8a24a]">{{ $service->publicCategoryName() }}</p>
                @if ($service->is_package)
                    <span class="rounded-sm bg-[#d5a93b] px-2 py-1 text-[10px] font-bold uppercase text-black">{{ $service->packageBadge() }}</span>
                @endif
            </div>
            <h3 class="mt-1 font-serif text-2xl text-[#fff9ea]">{{ $service->name }}</h3>
            <p class="mt-2 line-clamp-3 text-sm leading-6 text-[#d8c8a3]">{{ $service->short_description }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 text-sm text-[#f8efd8]">
            @if ($service->duration_minutes)
                <span>{{ $service->duration_minutes }} min</span>
                <span aria-hidden="true">·</span>
            @endif
            <span class="font-semibold">{{ $service->displayPrice() }}</span>
            <span class="rounded-sm border border-[#c8a24a]/30 px-2 py-1 text-[11px] font-semibold uppercase text-[#f4d27a]">{{ $service->is_package ? $service->packageBadge() : $service->priceBadge() }}</span>
            @if ($service->discounted_price && $service->price && $service->discounted_price < $service->price)
                <span class="text-[#a89567] line-through">₹{{ number_format((float) $service->price, 2) }}</span>
            @endif
        </div>
        @if ($service->pricing_note)
            <p class="text-xs leading-5 text-[#a89567]">{{ $service->pricing_note }}</p>
        @endif
        @if ($service->savings_amount)
            <p class="text-xs font-semibold text-[#f4d27a]">Savings: {{ \App\Support\Money::inr($service->savings_amount) }}</p>
        @endif
        <p class="text-xs font-medium {{ $service->is_home_service_available ? 'text-[#f4d27a]' : 'text-[#a89567]' }}">
            {{ $service->is_home_service_available ? 'Available for Elite Home Service' : 'Salon visit only' }}
        </p>
        <div class="grid grid-cols-2 gap-3">
            <a href="{{ route('appointments.book', ['service' => $service->slug]) }}" class="rounded-md bg-[#d5a93b] px-3 py-2 text-center text-sm font-semibold text-[#111]">{{ $service->publicBookingLabel() }}</a>
            <a href="{{ route('services.show', $service) }}" class="rounded-md border border-[#c8a24a]/40 px-3 py-2 text-center text-sm font-semibold text-[#f8efd8] hover:border-[#f4d27a]">Details</a>
        </div>
    </div>
</article>
