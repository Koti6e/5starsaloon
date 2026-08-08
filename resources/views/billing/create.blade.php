<x-app-layout>
    <x-slot name="header">
        <div class="flex items-end justify-between gap-3">
            <div>
                <h1 class="font-serif text-2xl text-[#f4d27a]">Quick Billing</h1>
                <p class="mt-1 text-sm text-[#d8c8a3]">Logged in: {{ $biller->name }}</p>
            </div>
        </div>
    </x-slot>

    @php
        $routeRoot = auth()->user()->isAdmin() ? 'admin' : 'staff';
        $servicePayload = $services->map(fn ($service) => [
            'id' => $service->id,
            'category_id' => $service->category_id,
            'category_slug' => $service->category?->slug,
            'label' => $service->name,
            'name' => $service->name,
            'category' => $service->publicCategoryName(),
            'price' => $service->discounted_price ?: $service->price,
            'display_price' => $service->displayPrice(),
            'duration' => $service->duration_minutes,
            'estimated' => $service->hasEstimatedPrice(),
            'is_package' => $service->is_package,
            'is_favourite' => $service->is_featured,
            'search' => Str::lower($service->name.' '.$service->publicCategoryName()),
        ])->values();

        $categoryPayload = $categories->map(fn ($category) => [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
        ])->values();

        $appointmentItems = collect();
        if (isset($appointment)) {
            $appointmentItems = $appointment->appointmentServices->map(function ($appointmentService) use ($services) {
                $service = $services->firstWhere('id', $appointmentService->service_id);

                if (! $service) {
                    return null;
                }

                return [
                    'id' => $service->id,
                    'key' => 'appointment-'.$service->id,
                    'label' => $service->name,
                    'name' => $service->name,
                    'category_id' => $service->category_id,
                    'category_slug' => $service->category?->slug,
                    'category' => $service->publicCategoryName(),
                    'price' => $service->discounted_price ?: $service->price,
                    'display_price' => $service->displayPrice(),
                    'duration' => $service->duration_minutes,
                    'estimated' => $service->hasEstimatedPrice(),
                    'is_package' => $service->is_package,
                    'is_favourite' => $service->is_featured,
                    'search' => Str::lower($service->name.' '.$service->publicCategoryName()),
                    'quantity' => 1,
                    'confirmed_price' => $service->hasEstimatedPrice() ? '' : ($service->discounted_price ?: $service->price),
                    'service_performed_by' => auth()->id(),
                ];
            })->filter()->values();
        }

        $initialItems = collect(old('items', []))->map(function ($item, $index) use ($services) {
            $service = $services->firstWhere('id', (int) ($item['service_id'] ?? 0));

            if (! $service) {
                return null;
            }

            return [
                'id' => $service->id,
                'key' => 'old-'.$index,
                'label' => $service->name,
                'name' => $service->name,
                'category_id' => $service->category_id,
                'category_slug' => $service->category?->slug,
                'category' => $service->publicCategoryName(),
                'price' => $service->discounted_price ?: $service->price,
                'display_price' => $service->displayPrice(),
                'duration' => $service->duration_minutes,
                'estimated' => $service->hasEstimatedPrice(),
                'is_package' => $service->is_package,
                'is_favourite' => $service->is_featured,
                'search' => Str::lower($service->name.' '.$service->publicCategoryName()),
                'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
                'confirmed_price' => $item['confirmed_price'] ?? ($service->hasEstimatedPrice() ? '' : ($service->discounted_price ?: $service->price)),
                'service_performed_by' => $item['service_performed_by'] ?? auth()->id(),
            ];
        })->filter()->values();

        if ($initialItems->isEmpty()) {
            $initialItems = $appointmentItems;
        }
    @endphp
    <div x-data="billingDesk({
            services: @js($servicePayload),
            categories: @js($categoryPayload),
            initialItems: @js($initialItems),
            initialMobile: @js(old('customer_mobile', '')),
            initialCustomerName: @js(old('customer_name', '')),
            initialCustomerId: @js(old('customer_id', '')),
            initialActiveCategory: @js(request()->query('category')),
            initialPaymentMethod: @js(old('payment_method', 'cash')),
            initialPaymentNote: @js(old('payment_note', '')),
            initialSplitPayments: @js(old('split_payments', [['method' => 'cash', 'amount' => 0], ['method' => 'upi', 'amount' => 0]])),
            lookupUrl: '{{ route($routeRoot.'.billing.customer-lookup', [], false) }}',
            favoriteToggleBase: '{{ auth()->user()->isAdmin() ? url('admin/services') : '' }}',
            isAdmin: @js(auth()->user()->isAdmin()),
        })" class="mx-auto grid max-w-7xl gap-5 px-3 sm:px-6 lg:grid-cols-[minmax(0,1fr)_340px] lg:px-8">
            <form method="POST" action="{{ route($routeRoot.'.billing.store', [], false) }}" class="space-y-5" @submit.prevent="submitBilling($event)">
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">

                <div x-show="submitError" x-cloak class="rounded-lg border border-red-300/30 bg-red-500/10 p-4 text-sm text-red-100">
                    <p class="font-semibold">Billing could not be completed.</p>
                    <p class="mt-2" x-text="submitError"></p>
                </div>

                @if ($errors->any())
                    <div class="rounded-lg border border-red-300/30 bg-red-500/10 p-4 text-sm text-red-100">
                        <p class="font-semibold">Billing could not be completed.</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <section class="rounded-[32px] border border-[#c8a24a]/15 bg-[#0c0a08]/95 p-5 shadow-[0_24px_80px_rgba(0,0,0,0.35)]">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-[#a89567]">Customer Details</p>
                            <p class="mt-1 text-sm text-[#d8c8a3]">Search existing profiles and confirm customer information quickly.</p>
                        </div>
                        <span class="rounded-full border border-[#c8a24a]/20 bg-[#11100d] px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-[#f4d27a]">Customer Lookup</span>
                    </div>

                    <label class="block">
                        <span class="text-sm font-semibold text-[#fff9ea]">Mobile Number</span>
                        <div class="mt-3">
                            <x-input-group prefix="+91" class="w-full">
                                <input x-model="mobile" @input.debounce.400ms="sanitizeMobile(); lookupCustomer()" name="customer_mobile" type="tel" inputmode="numeric" pattern="[6-9][0-9]{9}" maxlength="10" minlength="10" required class="elite-input flex-1" placeholder="9876543210">
                                <span x-show="lookupLoading" class="mr-3 h-5 w-5 animate-spin rounded-full border-2 border-[#c8a24a]/30 border-t-[#f4d27a]"></span>
                            </x-input-group>
                        </div>
                        <input type="hidden" name="customer_id" :value="customerId">
                        <x-input-error :messages="$errors->get('customer_mobile')" class="mt-2" />
                    </label>

                    <label class="mt-5 block">
                        <span class="text-sm font-semibold text-[#fff9ea]">Customer Name</span>
                        <input x-model="customerName" @input="sanitizeCustomerName" name="customer_name" type="text" maxlength="50" pattern="[A-Za-z]+( [A-Za-z]+)*" required class="mt-3 elite-input w-full" placeholder="Customer name">
                        <x-input-error :messages="$errors->get('customer_name')" class="mt-2" />
                    </label>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <p class="inline-flex rounded-full px-3 py-1 text-xs font-bold uppercase" :class="customerFound ? 'bg-emerald-500/15 text-emerald-300' : 'bg-[#c8a24a]/15 text-[#f4d27a]'" x-text="customerStatus"></p>
                        <p x-show="lastVisit" class="text-xs text-[#d8c8a3]">Last visit: <span x-text="lastVisit"></span></p>
                    </div>
                </section>

                <section class="rounded-[32px] border border-[#c8a24a]/15 bg-[#0c0a08]/95 p-5 shadow-[0_24px_80px_rgba(0,0,0,0.35)]">
                    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-[#a89567]">Service Selection</p>
                            <p class="mt-1 text-sm text-[#d8c8a3]">Pick services, packages, or create a fast bill for returning guests.</p>
                        </div>
                        <span class="inline-flex items-center rounded-full border border-[#c8a24a]/20 bg-[#11100d] px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-[#f4d27a]" x-text="`${services.length} services`"></span>
                    </div>

                    <div x-show="isAdmin || favouriteServices.length" class="space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#a89567]">Quick Services</p>
                            <p x-show="!favouriteServices.length" class="text-xs text-[#a89567]">Use the star beside any service to add it here for faster billing.</p>
                        </div>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <template x-if="favouriteServices.length">
                                <template x-for="service in favouriteServices" :key="service.id">
                                    <button type="button" @click="quickAdd(service)" class="group min-h-14 rounded-[28px] border border-[#c8a24a]/25 bg-[#090806] px-4 py-3 text-left transition hover:border-[#f4d27a]" :class="isAdded(service) ? 'border-emerald-400/50 bg-[#11100d]' : ''">
                                        <span class="block text-sm font-semibold text-[#fff9ea]" x-text="service.name"></span>
                                        <span class="mt-1 text-xs font-semibold" :class="isAdded(service) ? 'text-emerald-300' : 'text-[#f4d27a]'" x-text="isAdded(service) ? 'Added – use + for qty' : service.display_price"></span>
                                    </button>
                                </template>
                            </template>
                            <div x-show="!favouriteServices.length" class="rounded-[28px] border border-[#c8a24a]/20 bg-[#090806] p-4 text-sm text-[#d8c8a3]">
                                <p class="font-semibold text-[#fff9ea]">No favorite services yet</p>
                                <p class="mt-1">Use the star beside any service to add it here for faster billing.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 overflow-x-auto pb-1">
                        <div class="flex min-w-max gap-2">
                            <button type="button" @click="activeCategory = null" class="rounded-full border px-4 py-2 text-sm font-semibold transition" :class="activeCategory === null ? 'border-[#f4d27a] bg-[#d5a93b] text-black' : 'border-[#c8a24a]/30 text-[#f8efd8] hover:border-[#f4d27a] hover:text-[#f4d27a]'">All Services</button>
                            <template x-for="category in categories" :key="category.id">
                                <button type="button" @click="activeCategory = category.id" class="rounded-full border px-4 py-2 text-sm font-semibold transition" :class="activeCategory === category.id ? 'border-[#f4d27a] bg-[#d5a93b] text-black' : 'border-[#c8a24a]/30 text-[#f8efd8] hover:border-[#f4d27a] hover:text-[#f4d27a]'"><span x-text="category.name"></span></button>
                            </template>
                        </div>
                    </div>

                    <input x-model="serviceQuery" type="search" class="mt-5 w-full rounded-3xl border border-[#c8a24a]/25 bg-[#090806] px-4 py-4 text-sm text-[#fff9ea] placeholder:text-[#8f7d5a] focus:border-[#f4d27a] focus:ring-2 focus:ring-[#f4d27a]/20 focus:outline-none" placeholder="Search services, packages or categories">

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <template x-for="service in visibleServices" :key="service.id">
                            <label class="flex min-h-18 cursor-pointer flex-col justify-between gap-3 rounded-[28px] border p-4 transition" :class="isSelected(service) || isAdded(service) ? 'border-[#f4d27a] bg-[#11100d]' : 'border-[#c8a24a]/20 bg-[#090806] hover:border-[#f4d27a]'">
                                <div class="min-w-0">
                                    <p class="font-semibold text-[#fff9ea]" x-text="service.name"></p>
                                    <p class="mt-1 text-xs text-[#a89567]">
                                        <span x-text="service.category"></span>
                                        <span x-show="service.is_package" class="ml-2 rounded-full bg-[#d5a93b] px-2 py-0.5 text-[10px] font-bold text-black">Smart Saver</span>
                                        <span x-show="isAdded(service)" class="ml-2 text-emerald-300">Added</span>
                                    </p>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                                    <span class="font-semibold text-[#f4d27a]" x-text="service.display_price"></span>
                                    <div class="flex items-center gap-2">
                                        <button type="button" x-show="isAdmin" @click.stop="toggleFavorite(service)" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-[#c8a24a]/25 bg-[#090806] text-[#f4d27a] transition hover:border-[#f4d27a]">
                                            <span x-text="service.is_favourite ? '★' : '☆'"></span>
                                        </button>
                                        <input type="checkbox" class="h-5 w-5 rounded border-[#c8a24a]/40 bg-[#090806] text-[#d5a93b]" :checked="isSelected(service)" :disabled="isAdded(service)" @change="toggleSelected(service)">
                                    </div>
                                </div>
                            </label>
                        </template>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <template x-for="service in selectedServices" :key="service.id">
                            <button type="button" @click="toggleSelected(service)" class="rounded-full border border-[#c8a24a]/25 bg-[#090806] px-4 py-2 text-sm font-semibold text-[#f8efd8] transition hover:border-[#f4d27a]"> <span x-text="service.name"></span> ×</button>
                        </template>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-[1fr_auto]">
                        <button type="button" @click="addSelectedServices" class="w-full rounded-[28px] bg-[#d5a93b] px-5 py-4 text-sm font-bold uppercase tracking-[0.14em] text-black transition hover:bg-[#f0c75e]">Add Selected Services</button>
                        <button type="button" @click="selectedServices = []" class="rounded-[28px] border border-[#c8a24a]/25 px-4 py-4 text-sm font-semibold text-[#f8efd8] transition hover:border-[#f4d27a]">Clear</button>
                    </div>
                    <x-input-error :messages="$errors->get('items')" class="mt-3" />
                </section>

                <section class="rounded-[32px] border border-[#c8a24a]/15 bg-[#0c0a08]/95 p-5 shadow-[0_24px_80px_rgba(0,0,0,0.35)]">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-[#a89567]">Billing Items</p>
                            <p class="mt-1 text-sm text-[#d8c8a3]">Manage line items, prices, quantity and staff assignments with fast actions.</p>
                        </div>
                        <p class="text-sm font-semibold text-[#f8efd8]" x-text="`${items.length} item${items.length === 1 ? '' : 's'}`"></p>
                    </div>

                    <div class="space-y-4">
                        <div x-show="undoItem" class="rounded-3xl border border-[#c8a24a]/20 bg-[#11100d] p-4 text-sm text-[#f8efd8] shadow-inner shadow-black/20">
                            Removed <span x-text="undoItem?.name"></span>.
                            <button type="button" @click="restoreItem" class="ml-2 font-semibold text-[#f4d27a]">Undo</button>
                        </div>

                        <template x-for="(item, index) in items" :key="item.key">
                            <div class="rounded-[28px] border border-[#c8a24a]/15 bg-[#090806] p-5 shadow-[0_18px_40px_rgba(0,0,0,0.18)]">
                                <input type="hidden" :name="`items[${index}][service_id]`" :value="item.id">
                                <input type="hidden" :name="`items[${index}][quantity]`" :value="item.quantity">
                                @if (! auth()->user()->isAdmin())
                                    <input type="hidden" :name="`items[${index}][service_performed_by]`" value="{{ auth()->id() }}">
                                @endif

                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0 space-y-2">
                                        <p class="truncate text-lg font-semibold text-[#fff9ea]" x-text="item.name"></p>
                                        <p class="text-xs uppercase tracking-[0.16em] text-[#a89567]">Staff: {{ auth()->user()->name }}</p>
                                    </div>
                                    <button type="button" @click="removeItem(index)" class="rounded-full border border-red-300/40 px-4 py-2 text-sm font-semibold text-red-200 transition hover:bg-red-500/10">Remove</button>
                                </div>

                                @if (auth()->user()->isAdmin())
                                    <select :name="`items[${index}][service_performed_by]`" x-model="item.service_performed_by" class="mt-4 w-full rounded-3xl border border-[#c8a24a]/25 bg-[#11100d] px-4 py-4 text-sm text-[#fff9ea] focus:border-[#f4d27a] focus:ring-2 focus:ring-[#f4d27a]/20 focus:outline-none">
                                        <option value="{{ auth()->id() }}">{{ auth()->user()->name }}</option>
                                        @foreach ($staff as $staffMember)
                                            <option value="{{ $staffMember->id }}">{{ $staffMember->name }}</option>
                                        @endforeach
                                    </select>
                                @endif

                                <label class="mt-4 block" x-show="item.estimated">
                                    <span class="text-xs font-semibold uppercase tracking-[0.16em] text-[#a89567]">Confirmed Price</span>
                                    <input type="number" min="0" step="0.01" x-model.number="item.confirmed_price" :name="`items[${index}][confirmed_price]`" class="mt-2 w-full rounded-3xl border border-[#c8a24a]/25 bg-[#11100d] px-4 py-4 text-sm text-[#fff9ea] focus:border-[#f4d27a] focus:ring-2 focus:ring-[#f4d27a]/20 focus:outline-none">
                                </label>

                                <div class="mt-5 grid grid-cols-[auto_1fr_auto_auto] items-center gap-3">
                                    <button type="button" @click="decrease(item, index)" :aria-label="`Decrease quantity for ${item.name}`" class="h-12 w-12 rounded-full border border-[#c8a24a]/30 text-2xl font-semibold text-[#f4d27a] transition hover:border-[#f4d27a]">−</button>
                                    <p class="text-center text-3xl font-bold text-[#fff9ea]" x-text="item.quantity"></p>
                                    <button type="button" @click="increase(item)" :aria-label="`Increase quantity for ${item.name}`" class="h-12 w-12 rounded-full bg-[#d5a93b] text-2xl font-semibold text-black transition hover:bg-[#f0c75e]">+</button>
                                    <p class="min-w-[90px] text-right text-xl font-bold text-[#f4d27a]" x-text="money(lineTotal(item))"></p>
                                </div>
                            </div>
                        </template>

                        <p x-show="items.length === 0" class="rounded-[28px] border border-[#c8a24a]/20 bg-[#090806] p-5 text-sm text-[#d8c8a3]">No services added yet. Use the service selector above to build the bill.</p>
                    </div>
                </section>

                <section class="rounded-[32px] border border-[#c8a24a]/15 bg-[#0c0a08]/95 p-5 shadow-[0_24px_80px_rgba(0,0,0,0.35)]">
                    <div class="rounded-[28px] border border-[#c8a24a]/20 bg-[#090806] p-5">
                        <p class="text-sm text-[#d8c8a3]">Grand Total</p>
                        <p class="mt-3 text-4xl font-bold text-[#f4d27a]" x-text="money(grandTotal())"></p>
                    </div>

                    <div class="mt-6">
                        <p class="text-sm font-semibold uppercase tracking-[0.14em] text-[#f8efd8]">Payment Method</p>
                        <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-5">
                            <template x-for="method in ['cash', 'upi', 'card', 'split', 'other']" :key="method">
                                <label class="cursor-pointer rounded-[28px] border px-4 py-4 text-center text-sm font-semibold capitalize transition" :class="paymentMethod === method ? 'border-[#f4d27a] bg-[#d5a93b] text-black shadow-lg shadow-[#d5a93b]/20' : 'border-[#c8a24a]/25 bg-[#090806] text-[#f8efd8] hover:border-[#f4d27a] hover:text-[#f4d27a]'">
                                    <input type="radio" name="payment_method" class="sr-only" :value="method" x-model="paymentMethod">
                                    <span x-text="method === 'split' ? 'Split Payment' : method"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <input name="payment_note" x-model="paymentNote" class="mt-5 w-full rounded-3xl border border-[#c8a24a]/25 bg-[#090806] px-4 py-4 text-sm text-[#fff9ea] placeholder:text-[#8f7d5a] focus:border-[#f4d27a] focus:ring-2 focus:ring-[#f4d27a]/20 focus:outline-none" placeholder="Optional payment or billing note" :required="paymentMethod === 'other'">

                    <div class="mt-5 space-y-4" x-show="paymentMethod === 'split'">
                        <template x-for="(payment, index) in splitPayments" :key="index">
                            <div class="grid gap-3 sm:grid-cols-[130px_1fr]">
                                <select :name="paymentMethod === 'split' ? `split_payments[${index}][method]` : null" :disabled="paymentMethod !== 'split'" x-model="payment.method" class="rounded-[28px] border border-[#c8a24a]/25 bg-[#090806] px-4 py-4 text-sm text-[#fff9ea] focus:border-[#f4d27a] focus:ring-2 focus:ring-[#f4d27a]/20 focus:outline-none">
                                    <option value="cash">Cash</option>
                                    <option value="upi">UPI</option>
                                    <option value="card">Card</option>
                                    <option value="other">Other</option>
                                </select>
                                <input :name="paymentMethod === 'split' ? `split_payments[${index}][amount]` : null" :disabled="paymentMethod !== 'split'" x-model.number="payment.amount" type="number" step="0.01" min="0.01" class="rounded-[28px] border border-[#c8a24a]/25 bg-[#090806] px-4 py-4 text-sm text-[#fff9ea] placeholder:text-[#8f7d5a] focus:border-[#f4d27a] focus:ring-2 focus:ring-[#f4d27a]/20 focus:outline-none" placeholder="Amount">
                            </div>
                        </template>
                        <div class="flex flex-wrap gap-3">
                            <button type="button" @click="addSplitPayment" class="rounded-full border border-[#c8a24a]/25 bg-[#090806] px-4 py-3 text-sm font-semibold text-[#f4d27a] transition hover:border-[#f4d27a]">Add split row</button>
                            <button type="button" @click="fillSplitBalance" class="rounded-full border border-[#c8a24a]/25 bg-[#090806] px-4 py-3 text-sm font-semibold text-[#f4d27a] transition hover:border-[#f4d27a]">Fill balance</button>
                        </div>
                        <x-input-error :messages="$errors->get('split_payments')" class="mt-2" />
                    </div>
                </section>

                <button type="submit" :disabled="submitting || (items.length === 0 && selectedServices.length === 0)" class="sticky bottom-3 z-30 w-full rounded-full bg-[#d5a93b] px-5 py-4 text-sm font-bold uppercase tracking-[0.14em] text-black shadow-xl shadow-[#d5a93b]/25 transition disabled:cursor-not-allowed disabled:opacity-60 hover:bg-[#f0c75e]">
                    <span x-show="!submitting">Complete Billing</span>
                    <span x-show="submitting" class="inline-flex items-center justify-center gap-2">
                        <span class="h-4 w-4 animate-spin rounded-full border-2 border-black/30 border-t-black"></span>
                        Creating Invoice…
                    </span>
                </button>
            </form>

            <aside class="sticky bottom-3 z-20 self-start lg:top-6">
                <div class="space-y-4">
                    <div class="rounded-[32px] border border-[#c8a24a]/15 bg-[#0c0a08]/95 p-5 shadow-[0_24px_80px_rgba(0,0,0,0.35)]">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-[#a89567]">Quick Total</p>
                                <p class="mt-1 text-sm text-[#d8c8a3]">Review the final amount before checkout.</p>
                            </div>
                        </div>
                        <dl class="mt-5 space-y-3 text-sm">
                            <div class="flex justify-between"><dt class="text-[#d8c8a3]">Items</dt><dd class="font-semibold text-[#fff9ea]" x-text="items.length"></dd></div>
                            <div class="flex justify-between"><dt class="text-[#d8c8a3]">Subtotal</dt><dd class="font-semibold text-[#fff9ea]" x-text="money(grandTotal())"></dd></div>
                            <div class="flex justify-between"><dt class="text-[#d8c8a3]">Grand Total</dt><dd class="font-bold text-[#f4d27a]" x-text="money(grandTotal())"></dd></div>
                        </dl>
                    </div>
                    <div class="rounded-[32px] border border-[#c8a24a]/15 bg-[#0c0a08]/95 p-5 shadow-[0_24px_80px_rgba(0,0,0,0.35)]">
                        <div class="flex items-center justify-between">
                            <h2 class="font-serif text-xl text-[#f4d27a]">Today’s Bills</h2>
                            <span class="text-xs text-[#a89567]">{{ $todayBills->count() }}</span>
                        </div>
                        <div class="mt-4 space-y-3">
                            @forelse ($todayBills as $todayBill)
                                <a href="{{ route($routeRoot.'.billing.show', $todayBill, false) }}" class="block rounded-[24px] border border-[#c8a24a]/15 bg-[#090806] p-4 transition hover:border-[#f4d27a]">
                                    <p class="text-sm font-semibold text-[#fff9ea]">{{ $todayBill->invoice_number }}</p>
                                    <p class="mt-1 text-xs text-[#d8c8a3]">{{ $todayBill->customer->name }} · {{ \App\Support\Money::inr($todayBill->grand_total) }}</p>
                                    <p class="mt-2 text-xs text-[#a89567]">{{ $todayBill->billed_at->timezone('Asia/Kolkata')->format('h:i A') }} · {{ Str::title($todayBill->payments->pluck('payment_method')->join(' + ')) }}</p>
                                </a>
                            @empty
                                <p class="rounded-[24px] border border-[#c8a24a]/15 bg-[#090806] p-4 text-sm text-[#d8c8a3]">No bills created today.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    </div>

    <script>
        function billingDesk(config) {
            return {
                services: config.services,
                categories: config.categories || [],
                lookupUrl: config.lookupUrl,
                mobile: String(config.initialMobile || '').replace(/\D/g, '').slice(-10),
                customerName: config.initialCustomerName || '',
                customerId: config.initialCustomerId || '',
                customerFound: false,
                customerStatus: 'New Customer',
                lastVisit: '',
                lookupLoading: false,
                lookupController: null,
                serviceQuery: '',
                activeCategory: config.initialActiveCategory ?? null,
                selectedServices: [],
                items: config.initialItems || [],
                undoItem: null,
                paymentMethod: config.initialPaymentMethod || 'cash',
                paymentNote: config.initialPaymentNote || '',
                splitPayments: Array.isArray(config.initialSplitPayments) && config.initialSplitPayments.length
                    ? config.initialSplitPayments
                    : [{method: 'cash', amount: 0}, {method: 'upi', amount: 0}],
                submitting: false,
                submitError: '',
                get filteredServices() {
                    const q = this.serviceQuery.trim().toLowerCase();
                    return this.services
                        .filter(service => q === '' || service.search.includes(q))
                        .filter(service => this.activeCategory === null || Number(service.category_id) === Number(this.activeCategory));
                },
                get visibleServices() {
                    return this.filteredServices;
                },
                get favouriteServices() {
                    return this.services.filter(service => service.is_favourite).slice(0, 8);
                },
                get canToggleFavorites() {
                    return this.isAdmin;
                },
                async toggleFavorite(service) {
                    if (! this.canToggleFavorites) {
                        return;
                    }

                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    if (! token) {
                        return;
                    }

                    const response = await fetch(`${this.favoriteToggleBase}/${service.id}/favorite`, {
                        method: 'PATCH',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                        body: JSON.stringify({}),
                    });

                    if (! response.ok) {
                        return;
                    }

                    const payload = await response.json();
                    service.is_favourite = payload.is_favourite;
                },
                quickAdd(service) {
                    this.addService(service);
                },
                addService(service) {
                    const existing = this.items.find(item => item.id === service.id);

                    if (existing) {
                        existing.quantity = Math.min(20, Number(existing.quantity || 1) + 1);
                        return;
                    }

                    this.addServiceToItems(service);
                },
                sanitizeCustomerName() {
                    this.customerName = String(this.customerName || '')
                        .replace(/[^A-Za-z ]/g, '')
                        .replace(/\s+/g, ' ')
                        .replace(/^\s+/, '')
                        .slice(0, 50);
                },
                sanitizeMobile() {
                    this.mobile = String(this.mobile || '').replace(/\D/g, '').slice(-10);
                },
                lookupCustomer() {
                    this.sanitizeMobile();
                    const digits = this.mobile;
                    if (this.lookupController) this.lookupController.abort();
                    if (digits.length !== 10) {
                        this.lookupLoading = false;
                        this.customerFound = false;
                        this.customerId = '';
                        this.lastVisit = '';
                        this.customerStatus = 'New Customer';
                        return;
                    }
                    this.lookupController = new AbortController();
                    this.lookupLoading = true;
                    fetch(`${this.lookupUrl}?mobile=${encodeURIComponent(digits)}`, {
                        headers: {'Accept': 'application/json'},
                        signal: this.lookupController.signal,
                    })
                        .then(response => response.ok ? response.json() : Promise.reject())
                        .then(data => {
                            this.customerFound = data.found;
                            this.customerStatus = data.found ? 'Existing Customer' : 'New Customer';
                            this.customerId = data.customer?.id || '';
                            this.lastVisit = data.customer?.last_visit_at || '';
                            if (data.customer) this.customerName = data.customer.name;
                        })
                        .catch(error => {
                            if (error.name !== 'AbortError') {
                                this.customerFound = false;
                                this.customerId = '';
                                this.lastVisit = '';
                                this.customerStatus = 'New Customer';
                            }
                        })
                        .finally(() => {
                            this.lookupLoading = false;
                        });
                },
                async submitBilling(event) {
                    const form = event.target;
                    if (this.submitting || (this.items.length === 0 && this.selectedServices.length === 0)) return;

                    this.submitting = true;
                    this.submitError = '';

                    try {
                        if (this.selectedServices.length > 0) {
                            this.addSelectedServices();
                            await new Promise(resolve => requestAnimationFrame(resolve));
                        }

                        const response = await fetch(form.action, {
                            method: form.method || 'POST',
                            body: new FormData(form),
                            headers: {'Accept': 'text/html,application/xhtml+xml'},
                            credentials: 'same-origin',
                            redirect: 'follow',
                        });

                        if (response.url && ! response.url.includes('/billing/create')) {
                            window.location.assign(response.url);
                            return;
                        }

                        const html = await response.text();
                        const doc = new DOMParser().parseFromString(html, 'text/html');
                        const errors = Array.from(doc.querySelectorAll('.text-red-100 li, .text-red-100 [x-text], .text-red-100 p'))
                            .map(element => element.textContent.trim())
                            .filter(Boolean)
                            .filter(text => text !== 'Billing could not be completed.');

                        this.submitError = errors[0] || 'The invoice was not saved. Please check the required fields and try again.';
                        this.submitting = false;
                    } catch (error) {
                        this.submitError = 'Network error while saving the invoice. Please try again.';
                        this.submitting = false;
                    }
                },
                isSelected(service) {
                    return this.selectedServices.some(selected => selected.id === service.id);
                },
                isAdded(service) {
                    return this.items.some(item => item.id === service.id);
                },
                toggleSelected(service) {
                    if (this.isAdded(service)) return;
                    if (this.isSelected(service)) {
                        this.selectedServices = this.selectedServices.filter(selected => selected.id !== service.id);
                    } else {
                        this.selectedServices.push(service);
                    }
                },
                addSelectedServices() {
                    this.selectedServices.forEach(service => {
                        if (! this.isAdded(service)) this.addServiceToItems(service);
                    });
                    this.selectedServices = [];
                    this.serviceQuery = '';
                },
                addServiceToItems(service) {
                    this.items.push(Object.assign({}, service, {
                        key: `service-${service.id}-${Date.now()}`,
                        quantity: 1,
                        confirmed_price: service.estimated ? '' : service.price,
                    }));
                },
                removeItem(index) {
                    this.items.splice(index, 1);
                },
                increase(item) {
                    item.quantity = Math.min(20, Number(item.quantity || 1) + 1);
                },
                decrease(item, index) {
                    if (Number(item.quantity || 1) <= 1) {
                        this.undoItem = item;
                        this.items.splice(index, 1);
                        return;
                    }
                    item.quantity = Number(item.quantity || 1) - 1;
                },
                restoreItem() {
                    if (!this.undoItem || this.items.some(item => item.id === this.undoItem.id)) return;
                    this.items.push(this.undoItem);
                    this.undoItem = null;
                },
                unitPrice(item) {
                    return Number(item.estimated ? item.confirmed_price : item.price) || 0;
                },
                lineTotal(item) {
                    return this.unitPrice(item) * (Number(item.quantity) || 1);
                },
                grandTotal() {
                    return this.items.reduce((total, item) => total + this.lineTotal(item), 0);
                },
                fillSplitBalance() {
                    const used = this.splitPayments.slice(0, -1).reduce((total, payment) => total + (Number(payment.amount) || 0), 0);
                    this.splitPayments[this.splitPayments.length - 1].amount = Math.max(0, this.grandTotal() - used).toFixed(2);
                },
                addSplitPayment() {
                    this.splitPayments.push({method: 'cash', amount: 0});
                },
                money(value) {
                    return new Intl.NumberFormat('en-IN', {style: 'currency', currency: 'INR', maximumFractionDigits: 2}).format(Number(value) || 0);
                },
            }
        }
    </script>
    </div>
</x-app-layout>
