@props(['settings' => []])
@php($promotion = \App\Models\SalonSetting::activePromotion())

@if ($promotion)
    <section class="border-b border-[#c8a24a]/20 bg-[#fff6df] px-4 py-4 theme-promotion">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                @if (! blank($promotion['promotion_image'] ?? null))
                    <img src="{{ asset($promotion['promotion_image']) }}" alt="{{ $promotion['promotion_title'] }}" class="h-16 w-16 rounded-md object-cover">
                @endif
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#8a6616]">Today’s Special</p>
                    <h2 class="font-serif text-2xl text-[#211a11]">{{ $promotion['promotion_title'] }}</h2>
                    @if (! blank($promotion['promotion_subtitle'] ?? null))
                        <p class="text-sm text-[#6f6042]">{{ $promotion['promotion_subtitle'] }}</p>
                    @endif
                    @if (! blank($promotion['promotion_offer_price'] ?? null))
                        <p class="mt-1 font-bold text-[#8a6616]">Only {{ $promotion['promotion_offer_price'] }}</p>
                    @endif
                </div>
            </div>
            <a href="{{ url($promotion['promotion_button_link']) }}" class="rounded-md bg-[#d5a93b] px-5 py-3 text-center text-sm font-bold text-black">
                {{ $promotion['promotion_button_text'] ?? 'Book Now' }}
            </a>
        </div>
    </section>
@endif
