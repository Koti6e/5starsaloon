<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <h1 class="font-serif text-2xl text-[#f4d27a]">Services</h1>
            <a href="{{ route('admin.services.create') }}" class="rounded-md bg-[#d5a93b] px-4 py-2 text-sm font-semibold text-[#111]">Add Service</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <p class="mb-5 rounded-md border border-[#c8a24a]/30 bg-[#11100d] p-3 text-sm text-[#f4d27a]">{{ session('status') }}</p>
            @endif

            <form method="GET" class="mb-6 grid gap-3 rounded-lg border border-[#c8a24a]/20 bg-[#11100d] p-4 md:grid-cols-[220px_180px_1fr_auto]">
                <select name="category" class="rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]">
                    <option value="">All statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
                <label class="inline-flex items-center gap-2 text-sm text-[#d8c8a3]"><input type="checkbox" name="packages" value="1" @checked(request()->boolean('packages')) class="rounded border-[#c8a24a]/40 bg-black text-[#d5a93b]"> Packages only</label>
                <button class="rounded-md bg-[#d5a93b] px-4 py-2 font-semibold text-[#111]">Filter</button>
            </form>

            <x-admin.card class="overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[#c8a24a]/15 text-sm">
                        <thead class="bg-black text-left text-[#f4d27a]">
                            <tr>
                                <th class="px-4 py-3">Service</th>
                                <th class="px-4 py-3">Category</th>
                                <th class="px-4 py-3">Public Price</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#c8a24a]/10 text-[#f8efd8]">
                            @forelse ($services as $service)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <img src="{{ asset($service->coverImageUrl()) }}" alt="{{ $service->coverImage()?->alt_text ?? $service->name }}" class="h-14 w-16 rounded-md object-cover">
                                            <div>
                                                <div class="font-semibold">{{ $service->name }}</div>
                                                <div class="text-xs text-[#a89567]">{{ $service->service_code }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">{{ $service->category->name }}</td>
                                    <td class="px-4 py-3">
                                        <div>{{ $service->displayPrice() }}</div>
                                        <div class="text-xs text-[#a89567]">{{ $service->priceBadge() }}</div>
                                    </td>
                                    <td class="px-4 py-3">{{ ucfirst($service->status) }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.services.edit', $service) }}" class="rounded-md border border-[#c8a24a]/40 px-3 py-2 text-xs font-semibold text-[#f8efd8]">Edit</a>
                                            <form method="POST" action="{{ route('admin.services.toggle', $service) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="rounded-md border border-[#c8a24a]/40 px-3 py-2 text-xs font-semibold text-[#f8efd8]">{{ $service->status === 'active' ? 'Deactivate' : 'Activate' }}</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-8 text-center text-[#d8c8a3]">No services found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-admin.card>

            <div class="mt-6">{{ $services->links() }}</div>
        </div>
    </div>
</x-app-layout>
