<x-app-layout>
    <x-slot name="header">
        <h1 class="font-serif text-2xl text-[#f4d27a]">{{ $service->exists ? 'Edit Service' : 'Add Service' }}</h1>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <p class="mb-5 rounded-md border border-[#c8a24a]/30 bg-[#11100d] p-3 text-sm text-[#f4d27a]">{{ session('status') }}</p>
            @endif

            @if ($errors->any())
                <div class="mb-5 rounded-md border border-red-400/40 bg-red-950/40 p-3 text-sm text-red-100">{{ $errors->first() }}</div>
            @endif

            <form method="POST" enctype="multipart/form-data" action="{{ $service->exists ? route('admin.services.update', $service) : route('admin.services.store') }}" x-data="{ priceType: '{{ old('price_type', $service->price_type ?: 'fixed') }}', isPackage: {{ old('is_package', $service->is_package) ? 'true' : 'false' }} }" class="grid gap-6 md:grid-cols-2">
                <x-admin.card class="md:col-span-2">
                @csrf
                @if ($service->exists)
                    @method('PUT')
                @endif

                <label class="text-sm text-[#f8efd8]">Name<input name="name" value="{{ old('name', $service->name) }}" required class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]"></label>
                <label class="text-sm text-[#f8efd8]">Slug<input name="slug" value="{{ old('slug', $service->slug) }}" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]"></label>
                <label class="text-sm text-[#f8efd8]">Service code<input name="service_code" value="{{ old('service_code', $service->service_code) }}" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]"></label>
                <label class="text-sm text-[#f8efd8]">Category
                    <select name="category_id" required class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]">
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((int) old('category_id', $service->category_id) === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="text-sm text-[#f8efd8] md:col-span-2">Short description<textarea name="short_description" rows="3" required class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]">{{ old('short_description', $service->short_description) }}</textarea></label>
                <label class="text-sm text-[#f8efd8] md:col-span-2">Detailed description<textarea name="detailed_description" rows="4" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]">{{ old('detailed_description', $service->detailed_description) }}</textarea></label>

                <label class="text-sm text-[#f8efd8]">Price type
                    <select name="price_type" x-model="priceType" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]">
                        <option value="fixed">Fixed</option>
                        <option value="starting_from">Starting from</option>
                        <option value="range">Range</option>
                        <option value="contact">Contact</option>
                    </select>
                </label>
                <label class="text-sm text-[#f8efd8]">Duration minutes<input name="duration_minutes" type="number" min="1" value="{{ old('duration_minutes', $service->duration_minutes) }}" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]"></label>

                <label x-show="priceType === 'fixed'" class="text-sm text-[#f8efd8]">Price<input name="price" type="number" min="0" step="0.01" value="{{ old('price', $service->price) }}" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]"></label>
                <label x-show="priceType === 'fixed'" class="text-sm text-[#f8efd8]">Discounted price<input name="discounted_price" type="number" min="0" step="0.01" value="{{ old('discounted_price', $service->discounted_price) }}" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]"></label>
                <label x-show="priceType === 'starting_from'" class="text-sm text-[#f8efd8]">Starting price<input name="minimum_price" type="number" min="0" step="0.01" value="{{ old('minimum_price', $service->minimum_price ?: $service->price) }}" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]"></label>
                <label x-show="priceType === 'range'" class="text-sm text-[#f8efd8]">Minimum price<input name="minimum_price" type="number" min="0" step="0.01" value="{{ old('minimum_price', $service->minimum_price) }}" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]"></label>
                <label x-show="priceType === 'range'" class="text-sm text-[#f8efd8]">Maximum price<input name="maximum_price" type="number" min="0" step="0.01" value="{{ old('maximum_price', $service->maximum_price) }}" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]"></label>

                <label class="text-sm text-[#f8efd8] md:col-span-2">Pricing note<textarea name="pricing_note" rows="2" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]">{{ old('pricing_note', $service->pricing_note) }}</textarea></label>
                <label class="text-sm text-[#f8efd8]">Fallback image path<input name="image" value="{{ old('image', $service->image) }}" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]"></label>
                <div class="text-sm text-[#f8efd8]">
                    <label>Upload service images</label>
                    <input name="service_images[]" type="file" multiple accept=".jpg,.jpeg,.png,.webp" class="mt-1 w-full rounded-md border border-[#c8a24a]/30 bg-black p-2 text-[#fff9ea]">
                    <p class="mt-2 text-xs text-[#a89567]">Optional. Upload only meaningful images. Maximum total per service: 4 images, including the cover.</p>
                    <label class="mt-3 block">Alt text for new images<input name="image_alt_text" value="{{ old('image_alt_text') }}" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]"></label>
                </div>

                @if ($service->exists)
                    <div class="md:col-span-2 rounded-lg border border-[#c8a24a]/20 bg-black p-4">
                        <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                            <h2 class="font-serif text-xl text-[#f4d27a]">Service Images</h2>
                            <p class="text-xs text-[#a89567]">{{ $service->images->count() }}/4 used</p>
                        </div>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            @forelse ($service->images as $image)
                                <div class="rounded-md border border-[#c8a24a]/20 bg-[#11100d] p-3">
                                    <img src="{{ asset($image->thumbnail_path ?: $image->image_path) }}" alt="{{ $image->alt_text }}" class="aspect-[4/3] w-full rounded-md object-cover" loading="lazy">
                                    <label class="mt-3 flex items-center gap-2 text-xs text-[#d8c8a3]">
                                        <input type="radio" name="cover_image_id" value="{{ $image->id }}" @checked($image->is_cover) class="border-[#c8a24a]/40 bg-black text-[#d5a93b]">
                                        Cover image
                                    </label>
                                    <label class="mt-3 block text-xs text-[#d8c8a3]">Sort order<input name="image_sort_order[{{ $image->id }}]" type="number" min="1" max="4" value="{{ old("image_sort_order.$image->id", $image->sort_order) }}" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]"></label>
                                    <label class="mt-3 block text-xs text-[#d8c8a3]">Alt text<input name="image_alt[{{ $image->id }}]" value="{{ old("image_alt.$image->id", $image->alt_text) }}" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]"></label>
                                    <button type="submit" form="delete-service-image-{{ $image->id }}" class="mt-3 w-full rounded-md border border-red-400/40 px-3 py-2 text-xs font-semibold text-red-100">Delete image</button>
                                </div>
                            @empty
                                <p class="rounded-md border border-[#c8a24a]/20 bg-[#11100d] p-4 text-sm text-[#d8c8a3] sm:col-span-2 lg:col-span-4">No service images uploaded. The catalogue will use the fallback/category image.</p>
                            @endforelse
                        </div>
                    </div>
                @endif

                <label class="text-sm text-[#f8efd8]">Regular total<input name="regular_total" type="number" min="0" step="0.01" value="{{ old('regular_total', $service->regular_total) }}" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]"></label>
                <label class="text-sm text-[#f8efd8]">Display order<input name="display_order" type="number" min="0" value="{{ old('display_order', $service->display_order) }}" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]"></label>

                <label class="text-sm text-[#f8efd8] md:col-span-2">Included services, one per line<textarea name="included_services" rows="4" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]">{{ old('included_services', is_array($service->included_services) ? implode("\n", $service->included_services) : '') }}</textarea></label>

                <div class="grid gap-3 text-sm text-[#d8c8a3] md:col-span-2 sm:grid-cols-2 lg:grid-cols-4">
                    <label class="inline-flex items-center gap-2"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $service->is_featured)) class="rounded border-[#c8a24a]/40 bg-black text-[#d5a93b]"> Favourite / Quick Service</label>
                    <label class="inline-flex items-center gap-2"><input type="checkbox" name="is_package" value="1" x-model="isPackage" @checked(old('is_package', $service->is_package)) class="rounded border-[#c8a24a]/40 bg-black text-[#d5a93b]"> Package</label>
                    <label class="inline-flex items-center gap-2"><input type="checkbox" name="is_salon_service_available" value="1" @checked(old('is_salon_service_available', $service->is_salon_service_available ?? true)) class="rounded border-[#c8a24a]/40 bg-black text-[#d5a93b]"> Salon visit</label>
                    <label class="inline-flex items-center gap-2"><input type="checkbox" name="is_home_service_available" value="1" @checked(old('is_home_service_available', $service->is_home_service_available)) class="rounded border-[#c8a24a]/40 bg-black text-[#d5a93b]"> Elite Home Service</label>
                </div>

                <label class="text-sm text-[#f8efd8]">Home-service price<input name="home_service_price" type="number" min="0" step="0.01" value="{{ old('home_service_price', $service->home_service_price) }}" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]"></label>
                <label class="text-sm text-[#f8efd8]">Visit charge<input name="home_service_visit_charge" type="number" min="0" step="0.01" value="{{ old('home_service_visit_charge', $service->home_service_visit_charge) }}" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]"></label>
                <label class="text-sm text-[#f8efd8]">Status<select name="status" class="mt-1 w-full rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]"><option value="active" @selected(old('status', $service->status) === 'active')>Active</option><option value="inactive" @selected(old('status', $service->status) === 'inactive')>Inactive</option></select></label>

                <div class="rounded-lg border border-[#c8a24a]/20 bg-black p-4 text-sm text-[#d8c8a3]">
                    <div class="text-[#f4d27a]">Current public price preview</div>
                    <div class="mt-2 text-lg text-[#fff9ea]">{{ $service->exists ? $service->displayPrice() : 'Save to preview exact price' }}</div>
                </div>

                <div class="flex gap-3 md:col-span-2">
                    <button class="rounded-md bg-[#d5a93b] px-5 py-3 font-semibold text-[#111]">{{ $service->exists ? 'Update Service' : 'Create Service' }}</button>
                    <a href="{{ route('admin.services.index') }}" class="rounded-md border border-[#c8a24a]/40 px-5 py-3 font-semibold text-[#f8efd8]">Cancel</a>
                </div>
                </x-admin.card>
            </form>
            @foreach ($service->images ?? [] as $image)
                <form id="delete-service-image-{{ $image->id }}" method="POST" action="{{ route('admin.services.images.destroy', [$service, $image]) }}" onsubmit="return confirm('Delete this service image?');">
                    @csrf
                    @method('DELETE')
                </form>
            @endforeach
        </div>
    </div>
</x-app-layout>
