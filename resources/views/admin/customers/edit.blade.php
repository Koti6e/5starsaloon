<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="font-serif text-2xl text-[#f4d27a]">Edit Customer</h1>
                <p class="mt-1 text-sm text-[#d8c8a3]">Update the existing customer record. Billing history stays linked.</p>
            </div>
            <a href="{{ route('admin.customers.show', $customer) }}" class="rounded-md border border-[#c8a24a]/40 px-4 py-2 text-sm font-semibold text-[#f8efd8]">Cancel</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.customers.update', $customer) }}" class="space-y-5 rounded-lg border border-[#c8a24a]/20 bg-[#11100d] p-5">
                @csrf
                @method('PUT')
                <label class="block">
                    <span class="text-sm font-semibold text-[#f8efd8]">Customer Name</span>
                    <input name="name" value="{{ old('name', $customer->name) }}" required maxlength="50" pattern="[A-Za-z]+( [A-Za-z]+)*" class="mt-2 w-full rounded-md border-[#c8a24a]/30 bg-black px-4 py-3 text-[#fff9ea]">
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </label>
                <label class="block">
                    <span class="text-sm font-semibold text-[#f8efd8]">Mobile Number</span>
                    <div class="mt-2 flex rounded-md border border-[#c8a24a]/30 bg-black">
                        <span class="border-r border-[#c8a24a]/20 px-4 py-3 text-[#d8c8a3]">+91</span>
                        <input name="mobile" value="{{ old('mobile', $customer->mobile) }}" type="tel" inputmode="numeric" required maxlength="30" class="w-full border-0 bg-transparent px-4 py-3 text-[#fff9ea] focus:ring-0">
                    </div>
                    <x-input-error :messages="$errors->get('mobile')" class="mt-2" />
                </label>
                <button class="w-full rounded-md bg-[#d5a93b] px-4 py-3 font-semibold text-black">Save Customer</button>
            </form>
        </div>
    </div>
</x-app-layout>
