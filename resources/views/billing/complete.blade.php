<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="flex h-14 w-14 items-center justify-center rounded-full border border-emerald-400/30 bg-emerald-500/15 text-3xl font-bold text-emerald-300 shadow-lg shadow-emerald-950/20">✓</div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-300">Billing Completed Successfully</p>
                <h1 class="font-serif text-2xl text-[#fff9ea]">{{ $bill->invoice_number }}</h1>
            </div>
        </div>
    </x-slot>

    @php
        $routeRoot = auth()->user()->isAdmin() ? 'admin' : 'staff';
        $payment = $bill->payments->map(fn ($payment) => ucfirst($payment->payment_method).' '.\App\Support\Money::inr($payment->amount))->join(' + ');
    @endphp

    <div class="py-6">
        <div class="mx-auto max-w-4xl space-y-5 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-400/30 bg-emerald-500/10 p-4 text-sm font-semibold text-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg border border-red-300/30 bg-red-500/10 p-4 text-sm text-red-100">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <section class="overflow-hidden rounded-lg border border-[#c8a24a]/20 bg-[#11100d] shadow-2xl shadow-black/20">
                <div class="border-b border-[#c8a24a]/15 bg-black/30 px-5 py-4">
                    <p class="text-sm font-semibold text-[#f8efd8]">Invoice saved and ready for customer sharing.</p>
                </div>
                <dl class="grid gap-0 divide-y divide-[#c8a24a]/10 sm:grid-cols-2 sm:divide-x sm:divide-y-0">
                    <div class="p-5">
                        <dt class="text-xs font-semibold uppercase text-[#a89567]">Invoice</dt>
                        <dd class="mt-1 font-semibold text-[#fff9ea]">{{ $bill->invoice_number }}</dd>
                    </div>
                    <div class="p-5">
                        <dt class="text-xs font-semibold uppercase text-[#a89567]">Customer Name</dt>
                        <dd class="mt-1 font-semibold text-[#fff9ea]">{{ $bill->customer->name }}</dd>
                    </div>
                    <div class="border-t border-[#c8a24a]/10 p-5">
                        <dt class="text-xs font-semibold uppercase text-[#a89567]">Customer Mobile</dt>
                        <dd class="mt-1 font-semibold text-[#fff9ea]">+91 {{ $bill->customer->mobile }}</dd>
                    </div>
                    <div class="border-t border-[#c8a24a]/10 p-5">
                        <dt class="text-xs font-semibold uppercase text-[#a89567]">Amount Paid</dt>
                        <dd class="mt-1 text-2xl font-bold text-[#f4d27a]">{{ \App\Support\Money::inr($bill->grand_total) }}</dd>
                    </div>
                    <div class="border-t border-[#c8a24a]/10 p-5">
                        <dt class="text-xs font-semibold uppercase text-[#a89567]">Payment Method</dt>
                        <dd class="mt-1 font-semibold text-[#fff9ea]">{{ $payment }}</dd>
                    </div>
                    <div class="border-t border-[#c8a24a]/10 p-5">
                        <dt class="text-xs font-semibold uppercase text-[#a89567]">Billed By</dt>
                        <dd class="mt-1 font-semibold text-[#fff9ea]">{{ $bill->billedBy->name }}</dd>
                    </div>
                    <div class="border-t border-[#c8a24a]/10 p-5">
                        <dt class="text-xs font-semibold uppercase text-[#a89567]">Invoice Date & Time</dt>
                        <dd class="mt-1 font-semibold text-[#fff9ea]">{{ $bill->billed_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</dd>
                    </div>
                    <div class="border-t border-[#c8a24a]/10 p-5">
                        <dt class="text-xs font-semibold uppercase text-[#a89567]">Status</dt>
                        <dd class="mt-2 inline-flex rounded-full border border-emerald-400/40 px-3 py-1 text-xs font-black tracking-[0.2em] text-emerald-300">PAID</dd>
                    </div>
                </dl>
            </section>

            <div class="grid gap-3 sm:grid-cols-5">
                <a href="{{ route($routeRoot.'.billing.show', $bill, false) }}" class="rounded-md border border-[#c8a24a]/40 bg-[#11100d] px-4 py-3 text-center text-sm font-semibold text-[#f8efd8] shadow-sm transition hover:border-[#f4d27a] hover:text-[#f4d27a]">View Invoice</a>
                <a href="{{ route($routeRoot.'.billing.pdf', $bill, false) }}" class="rounded-md bg-[#d5a93b] px-4 py-3 text-center text-sm font-bold text-black shadow-lg shadow-black/20 transition hover:bg-[#f4d27a]">Download PDF</a>
                <a href="{{ route($routeRoot.'.billing.print', $bill, false) }}" target="_blank" class="rounded-md border border-[#c8a24a]/40 bg-[#11100d] px-4 py-3 text-center text-sm font-semibold text-[#f8efd8] shadow-sm transition hover:border-[#f4d27a] hover:text-[#f4d27a]">Print Invoice</a>
                <a href="{{ route($routeRoot.'.billing.whatsapp', $bill, false) }}" class="rounded-md border border-[#c8a24a]/40 bg-[#11100d] px-4 py-3 text-center text-sm font-semibold text-[#f8efd8] shadow-sm transition hover:border-[#f4d27a] hover:text-[#f4d27a]">Share WhatsApp</a>
                <a href="{{ route($routeRoot.'.billing.create', [], false) }}" class="rounded-md border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-center text-sm font-semibold text-emerald-200 shadow-sm transition hover:border-emerald-300">New Bill</a>
            </div>
        </div>
    </div>
</x-app-layout>
