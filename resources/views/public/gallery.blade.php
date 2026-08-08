<x-layouts.public :settings="$settings" title="Gallery | 5 Star New Look Salon">
    <section class="bg-[#0d0b08] px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <h1 class="font-serif text-4xl text-[#f4d27a]">Inside the 5 Star Experience</h1>
            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($images as $image)
                    <a href="{{ asset($image->image) }}" class="block overflow-hidden rounded-lg border border-[#c8a24a]/20">
                        <img src="{{ asset($image->image) }}" alt="{{ $image->alt_text }}" class="aspect-[4/3] h-full w-full object-cover" loading="lazy">
                    </a>
                @empty
                    <p class="rounded-lg border border-[#c8a24a]/25 bg-[#11100d] p-8 text-[#d8c8a3] sm:col-span-2 lg:col-span-3">Approved gallery images will appear here once uploaded.</p>
                @endforelse
            </div>
            <div class="mt-8">{{ $images->links() }}</div>
        </div>
    </section>
</x-layouts.public>
