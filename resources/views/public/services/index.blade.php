<x-layouts.public :settings="$settings" title="Services | 5 Star New Look Salon">
    <section class="bg-[#0d0b08] px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <h1 class="font-serif text-4xl text-[#f4d27a]">Services</h1>
            <p class="mt-3 max-w-2xl text-[#d8c8a3]">Browse active salon services and SMART SAVER PACKAGES with clear pricing and service details.</p>
            <form method="GET" class="mt-8 grid gap-3 rounded-lg border border-[#c8a24a]/20 bg-[#11100d] p-4 md:grid-cols-[1fr_220px_180px_180px_auto]">
                <label class="sr-only" for="search">Search services</label>
                <input id="search" name="search" value="{{ request('search') }}" placeholder="Filter haircut, facial, beard, spa or packages..." class="rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea] placeholder:text-[#a89567] focus:border-[#f4d27a] focus:ring-[#f4d27a]" oninput="window.filterServiceCards && window.filterServiceCards(this.value)">
                <label class="sr-only" for="category">Category</label>
                <select id="category" name="category" class="rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea] focus:border-[#f4d27a] focus:ring-[#f4d27a]">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <label class="sr-only" for="sort">Sort</label>
                <select id="sort" name="sort" class="rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea] focus:border-[#f4d27a] focus:ring-[#f4d27a]">
                    <option value="">Featured order</option>
                    <option value="price_asc" @selected(request('sort') === 'price_asc')>Price low to high</option>
                    <option value="price_desc" @selected(request('sort') === 'price_desc')>Price high to low</option>
                </select>
                <label class="sr-only" for="price_type">Price type</label>
                <select id="price_type" name="price_type" class="rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea] focus:border-[#f4d27a] focus:ring-[#f4d27a]">
                    <option value="">All price types</option>
                    <option value="fixed" @selected(request('price_type') === 'fixed')>Fixed price</option>
                    <option value="starting_from" @selected(request('price_type') === 'starting_from')>Starting price</option>
                    <option value="range" @selected(request('price_type') === 'range')>Price range</option>
                    <option value="contact" @selected(request('price_type') === 'contact')>Contact for price</option>
                </select>
                <button class="rounded-md bg-[#d5a93b] px-5 py-2 font-semibold text-[#111]">Apply</button>
                <div class="flex flex-wrap gap-3 text-sm text-[#d8c8a3] md:col-span-5">
                    <label class="inline-flex items-center gap-2"><input type="checkbox" name="packages" value="1" @checked(request()->boolean('packages')) class="rounded border-[#c8a24a]/40 bg-black text-[#d5a93b]"> SMART SAVER PACKAGES</label>
                    <label class="inline-flex items-center gap-2"><input type="checkbox" name="home_service" value="1" @checked(request()->boolean('home_service')) class="rounded border-[#c8a24a]/40 bg-black text-[#d5a93b]"> Home-service available</label>
                    <a href="{{ route('services.index') }}" class="text-[#f4d27a]">Clear filters</a>
                </div>
            </form>
            <div class="mt-10 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($services as $service)
                    <x-service-card :service="$service" />
                @empty
                    <div class="rounded-lg border border-[#c8a24a]/25 bg-[#11100d] p-8 text-[#d8c8a3] md:col-span-2 xl:col-span-3">
                        No active services match your filters.
                    </div>
                @endforelse
            </div>
            <div class="mt-8">{{ $services->links() }}</div>
        </div>
    </section>
    <script>
        window.filterServiceCards = function (value) {
            const query = String(value || '').trim().toLowerCase();
            document.querySelectorAll('[data-service-card]').forEach((card) => {
                card.hidden = query !== '' && !card.dataset.search.includes(query);
            });
        };
    </script>
</x-layouts.public>
