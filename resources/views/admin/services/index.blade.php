<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <h1 class="font-serif text-2xl text-[var(--app-primary)]">Services</h1>
            <a href="{{ route('admin.services.create') }}" class="rounded-md bg-[var(--app-primary)] px-4 py-2 text-sm font-semibold text-[var(--on-accent)]">Add Service</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <p class="mb-5 rounded-md border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-3 text-sm text-[var(--app-primary)]">{{ session('status') }}</p>
            @endif

            <form method="GET" class="mb-6 grid gap-3 rounded-lg border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-4 md:grid-cols-[220px_180px_1fr_auto]">
                <select name="category" class="rounded-md border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-text)]">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="rounded-md border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-text)]">
                    <option value="">All statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
                <label class="inline-flex items-center gap-2 text-sm text-[var(--app-muted)]"><input type="checkbox" name="packages" value="1" @checked(request()->boolean('packages')) class="rounded border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-primary)]"> Packages only</label>
                <button class="rounded-md bg-[var(--app-primary)] px-4 py-2 font-semibold text-[var(--on-accent)]">Filter</button>
            </form>

            <x-admin.card class="overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--app-border)] text-sm">
                        <thead class="bg-[var(--app-bg)] text-left text-[var(--app-primary)]">
                            <tr>
                                <th class="px-4 py-3">Service</th>
                                <th class="px-4 py-3">Category</th>
                                <th class="px-4 py-3">Public Price</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--app-border)] text-[var(--app-text)]">
                            @forelse ($services as $service)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <img src="{{ asset($service->coverImageUrl()) }}" alt="{{ $service->coverImage()?->alt_text ?? $service->name }}" class="h-14 w-16 rounded-md object-cover">
                                            <div>
                                                <div class="font-semibold">{{ $service->name }}</div>
                                                <div class="text-xs text-[var(--app-subtle)]">{{ $service->service_code }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">{{ $service->category->name }}</td>
                                    <td class="px-4 py-3">
                                        <div>{{ $service->displayPrice() }}</div>
                                        <div class="text-xs text-[var(--app-subtle)]">{{ $service->priceBadge() }}</div>
                                    </td>
                                    <td class="px-4 py-3">{{ ucfirst($service->status) }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.services.edit', $service) }}" class="rounded-md border border-[var(--app-border)] px-3 py-2 text-xs font-semibold text-[var(--app-text)]">Edit</a>
                                            <form method="POST" action="{{ route('admin.services.toggle', $service) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="rounded-md border border-[var(--app-border)] px-3 py-2 text-xs font-semibold text-[var(--app-text)]">{{ $service->status === 'active' ? 'Deactivate' : 'Activate' }}</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-8 text-center text-[var(--app-muted)]">No services found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-admin.card>

            <div class="mt-6">{{ $services->links() }}</div>
        </div>
    </div>
</x-app-layout>
