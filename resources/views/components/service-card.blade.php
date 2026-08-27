@props(['service'])
@php
    $serviceImage = (string) $service->image;
    $coverPath = filled($serviceImage)
        && Str::endsWith(Str::lower($serviceImage), '.webp')
        && \Illuminate\Support\Facades\File::exists(public_path($serviceImage))
            ? $serviceImage
            : $service->coverImageUrl();
@endphp
<article class="group service-card overflow-hidden rounded-lg border border-[#c8a24a]/20 bg-[#11100d] shadow-lg shadow-black/20 transition duration-300 hover:-translate-y-0.5 hover:border-[#f4d27a]/50 hover:shadow-[#c8a24a]/10" data-service-card data-search="{{ Str::lower($service->name.' '.$service->publicCategoryName().' '.$service->short_description) }}">
    <div class="aspect-square overflow-hidden bg-[#1b1711] sm:aspect-[4/3]">
        <img src="{{ asset($coverPath) }}" alt="{{ $service->coverImage()?->alt_text ?? $service->name }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
    </div>
    <div class="space-y-2.5 p-3">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <p class="truncate text-[10px] font-semibold uppercase tracking-wide text-[#c8a24a]">{{ $service->publicCategoryName() }}</p>
                @if ($service->is_package)
                    <span class="rounded-sm bg-[#d5a93b] px-1.5 py-0.5 text-[9px] font-bold uppercase text-black">{{ $service->packageBadge() }}</span>
                @endif
            </div>
            <h3 class="mt-1 line-clamp-2 min-h-[2.5rem] text-sm font-bold leading-5 text-[#fff9ea] sm:text-base">{{ $service->name }}</h3>
        </div>
        <div class="flex flex-wrap items-center gap-2 text-sm text-[#f8efd8]">
            <span class="text-base font-extrabold text-[#f4d27a]">{{ $service->displayPrice() }}</span>
            @if ($service->discounted_price && $service->price && $service->discounted_price < $service->price)
                <span class="text-[#a89567] line-through">₹{{ number_format((float) $service->price, 2) }}</span>
            @endif
        </div>
        <div class="grid grid-cols-2 gap-2">
            <a href="{{ route('appointments.book', ['service' => $service->slug]) }}" class="rounded-md bg-[#d5a93b] px-2 py-2 text-center text-xs font-semibold text-[#111]">{{ $service->is_package ? 'Book' : 'Book' }}</a>
            <a href="{{ route('services.show', $service) }}" class="rounded-md border border-[#c8a24a]/40 px-2 py-2 text-center text-xs font-semibold text-[#f8efd8] hover:border-[#f4d27a]">Details</a>
        </div>
    </div>
</article>
