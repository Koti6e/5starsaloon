<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="font-serif text-2xl text-[var(--app-primary)]">Edit Customer</h1>
                <p class="mt-1 text-sm text-[var(--app-muted)]">Update the existing customer record. Billing history stays linked.</p>
            </div>
            <a href="{{ route('admin.customers.show', $customer) }}" class="rounded-md border border-[var(--app-border)] px-4 py-2 text-sm font-semibold text-[var(--app-text)]">Cancel</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.customers.update', $customer) }}" class="space-y-5 rounded-lg border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-5">
                @csrf
                @method('PUT')
                <label class="block">
                    <span class="text-sm font-semibold text-[var(--app-text)]">Customer Name</span>
                    <input name="name" value="{{ old('name', $customer->name) }}" required maxlength="50" pattern="[A-Za-z]+( [A-Za-z]+)*" class="mt-2 w-full rounded-md border-[var(--app-border)] bg-[var(--app-bg)] px-4 py-3 text-[var(--app-text)]">
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </label>
                <label class="block">
                    <span class="text-sm font-semibold text-[var(--app-text)]">Mobile Number</span>
                    <div class="mt-2 flex rounded-md border border-[var(--app-border)] bg-[var(--app-bg)]">
                        <span class="border-r border-[var(--app-border)] px-4 py-3 text-[var(--app-muted)]">+91</span>
                        <input name="mobile" value="{{ old('mobile', $customer->mobile) }}" type="tel" inputmode="numeric" required maxlength="30" class="w-full border-0 bg-transparent px-4 py-3 text-[var(--app-text)] focus:ring-0">
                    </div>
                    <x-input-error :messages="$errors->get('mobile')" class="mt-2" />
                </label>
                <button class="w-full rounded-md bg-[var(--app-primary)] px-4 py-3 font-semibold text-[var(--on-accent)]">Save Customer</button>
            </form>
        </div>
    </div>
</x-app-layout>
