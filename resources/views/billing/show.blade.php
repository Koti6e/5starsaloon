<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#a89567]">View Bill</p>
            <h1 class="font-serif text-2xl text-[#fff9ea]">{{ $bill->invoice_number }}</h1>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            @include('billing.partials.invoice-card')
        </div>
    </div>
</x-app-layout>
