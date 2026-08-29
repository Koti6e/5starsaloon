@props(['service'])

@php
    $serviceImage = (string) $service->image;

    $coverPath = filled($serviceImage)
        && \Illuminate\Support\Str::endsWith(
            \Illuminate\Support\Str::lower($serviceImage),
            '.webp'
        )
        && \Illuminate\Support\Facades\File::exists(public_path($serviceImage))
            ? $serviceImage
            : $service->coverImageUrl();
@endphp

<article
    class="group service-card overflow-hidden rounded-lg border border-[#c8a24a]/20 bg-[#11100d] transition duration-300 hover:-translate-y-1 hover:border-[#f4d27a]/50"
    data-service-card
    data-search="{{ \Illuminate\Support\Str::lower($service->name.' '.$service->publicCategoryName().' '.$service->short_description) }}"
>
    {{-- Compact service image --}}
    <a href="{{ route('services.show', $service) }}" class="block overflow-hidden bg-black">
        <div class="flex aspect-[4/3] w-full items-center justify-center overflow-hidden">
            <img
                src="{{ asset($coverPath) }}"
                alt="{{ $service->coverImage()?->alt_text ?? $service->name }}"
                class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]"
                loading="lazy"
            >
        </div>
    </a>

    <div class="p-2.5 sm:p-3">
        <div class="flex items-center gap-1.5">
            <p class="min-w-0 truncate text-[9px] font-semibold uppercase tracking-wide text-[#c8a24a] sm:text-[10px]">
                {{ $service->publicCategoryName() }}
            </p>

            @if ($service->is_package)
                <span class="shrink-0 rounded bg-[#d5a93b] px-1.5 py-0.5 text-[8px] font-bold uppercase text-black">
                    {{ $service->packageBadge() }}
                </span>
            @endif
        </div>

        <h3 class="mt-1 line-clamp-2 min-h-[2.25rem] text-xs font-bold leading-[1.1rem] text-[#fff9ea] sm:text-sm">
            {{ $service->name }}
        </h3>

        <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
            <span class="text-sm font-extrabold text-[#f4d27a] sm:text-base">
                {{ $service->displayPrice() }}
            </span>

            @if ($service->discounted_price && $service->price && $service->discounted_price < $service->price)
                <span class="text-[10px] text-[#a89567] line-through">
                    ₹{{ number_format((float) $service->price, 0) }}
                </span>
            @endif
        </div>

        <div class="mt-2 grid grid-cols-2 gap-1.5">
            <a
                href="{{ route('appointments.book', ['service' => $service->slug]) }}"
                class="rounded-md bg-[#d5a93b] px-2 py-2 text-center text-[10px] font-bold text-[#111] transition hover:bg-[#f4d27a] sm:text-xs"
            >
                Book
            </a>

            <a
                href="{{ route('services.show', $service) }}"
                class="rounded-md border border-[#c8a24a]/40 px-2 py-2 text-center text-[10px] font-semibold text-[#f8efd8] transition hover:border-[#f4d27a] sm:text-xs"
            >
                Details
            </a>
        </div>
    </div>
</article>
