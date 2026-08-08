@php
    $printMode = $printMode ?? false;
    $statusLabel = strtoupper($bill->status === 'cancelled' ? 'cancelled' : $bill->payment_status);
    $statusClasses = $statusLabel === 'PAID'
        ? ($printMode ? 'border-emerald-700 text-emerald-800' : 'border-emerald-400/50 text-emerald-300')
        : ($printMode ? 'border-red-700 text-red-800' : 'border-red-400/50 text-red-300');
    $note = $bill->payments->firstWhere('method_note')?->method_note;
    $phone = $settings['primary_phone'] ?? '';
    $whatsapp = $settings['whatsapp_number'] ?? '';
    $website = $settings['website'] ?? '';
@endphp
<article class="{{ $printMode ? 'bg-white text-black' : 'rounded-lg border border-[#c8a24a]/20 bg-[#11100d] text-[#fff9ea] shadow-2xl shadow-black/20' }} overflow-hidden">
    <div class="relative p-5 sm:p-7">
        <div class="absolute right-5 top-5 rounded-full border px-4 py-1 text-xs font-black tracking-[0.24em] {{ $statusClasses }}">{{ $statusLabel }}</div>

    <div class="flex flex-col gap-5 border-b {{ $printMode ? 'border-gray-200' : 'border-[#c8a24a]/20' }} pb-5 pr-20 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-center gap-4">
            <img src="{{ $printMode && isset($logoDataUri) && $logoDataUri ? $logoDataUri : asset('images/brand/logo-small.webp') }}" alt="5 Star New Look Salon logo" class="h-16 w-16 rounded-full object-contain ring-1 {{ $printMode ? 'ring-gray-200' : 'ring-[#c8a24a]/25' }}">
            <div>
                <h2 class="font-serif text-2xl {{ $printMode ? 'text-[#8a6616]' : 'text-[#f4d27a]' }}">5 Star New Look Salon</h2>
                <p class="{{ $printMode ? 'text-gray-600' : 'text-[#d8c8a3]' }}">Look Good. Feel Great. Be Confident.</p>
                <p class="mt-1 max-w-md text-sm {{ $printMode ? 'text-gray-600' : 'text-[#a89567]' }}">{{ $settings['address'] ?? '' }}</p>
            </div>
        </div>
        <div class="text-left sm:text-right">
            <p class="text-sm {{ $printMode ? 'text-gray-600' : 'text-[#a89567]' }}">Invoice</p>
            <p class="text-xl font-bold">{{ $bill->invoice_number }}</p>
            <p class="text-sm {{ $printMode ? 'text-gray-600' : 'text-[#d8c8a3]' }}">{{ $bill->billed_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</p>
        </div>
    </div>

    <div class="grid gap-4 py-5 sm:grid-cols-2">
        <div class="rounded-md border {{ $printMode ? 'border-gray-200 bg-gray-50' : 'border-[#c8a24a]/15 bg-black/40' }} p-4">
            <p class="text-xs font-semibold uppercase {{ $printMode ? 'text-gray-500' : 'text-[#a89567]' }}">Customer</p>
            <p class="mt-1 font-semibold">{{ $bill->customer->name }}</p>
            <p class="{{ $printMode ? 'text-gray-600' : 'text-[#d8c8a3]' }}">+91 {{ $bill->customer->mobile }}</p>
        </div>
        <div class="rounded-md border {{ $printMode ? 'border-gray-200 bg-gray-50' : 'border-[#c8a24a]/15 bg-black/40' }} p-4 sm:text-right">
            <p class="text-xs font-semibold uppercase {{ $printMode ? 'text-gray-500' : 'text-[#a89567]' }}">Invoice Details</p>
            <p class="mt-1 font-semibold">{{ $bill->billedBy->name }}</p>
            <p class="{{ $printMode ? 'text-gray-600' : 'text-[#d8c8a3]' }}">Billed by</p>
        </div>
    </div>

    <div class="overflow-x-auto rounded-md border {{ $printMode ? 'border-gray-200' : 'border-[#c8a24a]/15' }}">
        <table class="min-w-full border-collapse text-sm">
            <thead class="{{ $printMode ? 'bg-gray-100 text-gray-700' : 'bg-black/50 text-[#f4d27a]' }}">
                <tr>
                    <th class="px-4 py-3 text-left">Service</th>
                    <th class="px-4 py-3 text-right">Qty</th>
                    <th class="px-4 py-3 text-right">Unit Price</th>
                    <th class="px-4 py-3 text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y {{ $printMode ? 'divide-gray-200' : 'divide-[#c8a24a]/10' }}">
                @foreach ($bill->items as $item)
                    <tr>
                        <td class="px-4 py-3 align-top">
                            <p class="font-semibold">{{ $item->service_name_snapshot }}</p>
                            <p class="text-xs {{ $printMode ? 'text-gray-600' : 'text-[#a89567]' }}">{{ $item->category_name_snapshot }}{{ $item->is_package_snapshot ? ' Package' : '' }}</p>
                        </td>
                        <td class="px-4 py-3 text-right align-top">{{ $item->quantity }}</td>
                        <td class="px-4 py-3 text-right align-top">{{ \App\Support\Money::inr($item->unit_price) }}</td>
                        <td class="px-4 py-3 text-right align-top font-semibold">{{ \App\Support\Money::inr($item->line_total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6 grid gap-5 sm:grid-cols-[minmax(0,1fr)_320px]">
        <div class="rounded-md border {{ $printMode ? 'border-gray-200 bg-gray-50' : 'border-[#c8a24a]/15 bg-black/40' }} p-4">
            <p class="text-xs font-semibold uppercase {{ $printMode ? 'text-gray-500' : 'text-[#a89567]' }}">Payment</p>
            <div class="mt-2 space-y-1">
                @foreach ($bill->payments as $payment)
                    <p>{{ ucfirst($payment->payment_method) }} - {{ \App\Support\Money::inr($payment->amount) }}</p>
                @endforeach
            </div>
            @if ($note)
                <p class="mt-3 text-sm {{ $printMode ? 'text-gray-600' : 'text-[#d8c8a3]' }}">Notes: {{ $note }}</p>
            @endif
        </div>
        <dl class="rounded-md border {{ $printMode ? 'border-gray-200' : 'border-[#c8a24a]/15 bg-black/40' }} p-4 text-sm">
            <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ \App\Support\Money::inr($bill->subtotal) }}</dd></div>
            @if ((float) $bill->discount_amount > 0)
                <div class="mt-2 flex justify-between"><dt>Discount</dt><dd>-{{ \App\Support\Money::inr($bill->discount_amount) }}</dd></div>
            @endif
            @if ((float) $bill->home_visit_charge > 0)
                <div class="mt-2 flex justify-between"><dt>Home Service Charge</dt><dd>{{ \App\Support\Money::inr($bill->home_visit_charge) }}</dd></div>
            @endif
            <div class="flex justify-between border-t {{ $printMode ? 'border-gray-300' : 'border-[#c8a24a]/20' }} pt-3 text-lg font-bold {{ $printMode ? 'text-[#8a6616]' : 'text-[#f4d27a]' }}"><dt>Grand total</dt><dd>{{ \App\Support\Money::inr($bill->grand_total) }}</dd></div>
        </dl>
    </div>

    <div class="mt-8 grid gap-5 border-t {{ $printMode ? 'border-gray-200' : 'border-[#c8a24a]/15' }} pt-5 sm:grid-cols-[minmax(0,1fr)_240px]">
        <div class="text-sm {{ $printMode ? 'text-gray-600' : 'text-[#d8c8a3]' }}">
            <p class="font-serif text-lg {{ $printMode ? 'text-[#8a6616]' : 'text-[#f4d27a]' }}">Thank you for choosing 5 Star New Look Salon</p>
            <p class="mt-1">We appreciate your visit. Follow us for latest offers.</p>
            <p class="mt-3">
                @if ($phone) Phone: {{ $phone }} @endif
                @if ($whatsapp) {{ $phone ? ' · ' : '' }}WhatsApp: {{ $whatsapp }} @endif
                @if ($website) {{ ($phone || $whatsapp) ? ' · ' : '' }}{{ $website }} @endif
            </p>
            @if (! blank($settings['address'] ?? null))
                <p class="mt-1">{{ $settings['address'] }}</p>
            @endif
        </div>
        <div class="text-left sm:text-right">
            <div class="font-serif text-2xl {{ $printMode ? 'text-[#8a6616]' : 'text-[#f4d27a]' }}">5 Star New Look</div>
            <p class="mt-1 text-xs font-semibold uppercase {{ $printMode ? 'text-gray-500' : 'text-[#a89567]' }}">Authorised Signature</p>
            <p class="mt-3 text-xs {{ $printMode ? 'text-gray-500' : 'text-[#a89567]' }}">This is a system-generated invoice. No handwritten signature required.</p>
        </div>
    </div>
    </div>
</article>
