<x-app-layout>
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
            initialMobile: @js(old('customer_mobile', $appointment?->customer?->mobile ?? '')),
            initialCustomerName: @js(old('customer_name', $appointment?->customer?->name ?? '')),
            initialCustomerId: @js(old('customer_id', $appointment?->customer_id ?? '')),
            initialActiveCategory: @js(request()->query('category')),
            initialPaymentMethod: @js(old('payment_method', 'cash')),
            initialPaymentNote: @js(old('payment_note', '')),
            initialSplitPayments: @js(old('split_payments', [['method' => 'cash', 'amount' => 0], ['method' => 'upi', 'amount' => 0]])),
            staff: @js(collect([auth()->user()])->merge($staff)->unique('id')->map(fn ($staffMember) => ['id' => $staffMember->id, 'name' => $staffMember->name])->values()),
            lookupUrl: '{{ route($routeRoot.'.billing.customer-lookup', [], false) }}',
            favoriteToggleBase: '{{ auth()->user()->isAdmin() ? url('admin/services') : '' }}',
            isAdmin: @js(auth()->user()->isAdmin()),
        })" class="mx-auto max-w-4xl px-0 sm:px-3 lg:px-6">
            <form method="POST" action="{{ route($routeRoot.'.billing.store', [], false) }}" class="space-y-2.5 pb-28 lg:pb-24" @submit.prevent="submitBilling($event)">
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
                @if ($appointment)
                    <input type="hidden" name="appointment_id" value="{{ $appointment->id }}">
                @endif

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

                <section class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface)]/95 px-3 py-2 shadow-[0_10px_26px_rgba(0,0,0,0.2)] sm:px-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h1 class="text-lg font-bold leading-tight text-[var(--app-text)]">Quick Billing</h1>
                            <p class="text-xs font-semibold text-[var(--app-primary)]" x-text="billClock()"></p>
                        </div>
                        <span class="text-right text-[11px] font-semibold text-[var(--app-muted)]">{{ $biller->name }}</span>
                    </div>
                </section>

                <section class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface)]/95 p-2.5 shadow-[0_10px_26px_rgba(0,0,0,0.2)] sm:p-3">
                    <label class="block">
                        <span class="sr-only">Search customer by name or mobile number</span>
                        <div class="relative">
                            <input
                                x-model="customerQuery"
                                @input.debounce.250ms="lookupCustomer()"
                                @focus="customerSuggestionsOpen = customerSuggestions.length > 0"
                                type="search"
                                autocomplete="off"
                                class="w-full rounded-2xl border border-[var(--app-border)] bg-[var(--app-bg)] px-3 py-3 pr-10 text-base text-[var(--app-text)] placeholder:text-[var(--app-muted)] focus:border-[var(--app-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--app-focus)]"
                                placeholder="Search customer by name/mobile"
                            >
                            <span x-show="lookupLoading" class="absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 animate-spin rounded-full border-2 border-[var(--app-border)] border-t-[var(--app-primary)]"></span>
                            <div x-show="customerSuggestionsOpen" x-cloak @click.outside="customerSuggestionsOpen = false" class="absolute left-0 right-0 top-full z-30 mt-1 max-h-64 overflow-y-auto rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-1.5 shadow-2xl shadow-black/40">
                                <template x-for="customer in customerSuggestions" :key="customer.id">
                                    <button type="button" @click="selectCustomer(customer)" class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-left transition hover:bg-[var(--app-primary-soft)]">
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-semibold text-[var(--app-text)]" x-text="customer.name"></span>
                                            <span class="mt-1 block text-xs text-[var(--app-muted)]" x-text="`+91 ${maskMobile(customer.mobile)}`"></span>
                                        </span>
                                        <span class="shrink-0 text-xs font-bold text-[var(--app-primary)]">Select</span>
                                    </button>
                                </template>
                                <button type="button" x-show="customerQuery.trim().length >= 2 && customerSuggestions.length === 0 && !lookupLoading" @click="startNewCustomer()" class="w-full rounded-xl border border-[var(--app-border)] bg-[var(--app-primary-soft)] px-3 py-2.5 text-left text-sm font-semibold text-[var(--app-primary)]">+ New Customer</button>
                            </div>
                        </div>
                    </label>

                    <input type="hidden" name="customer_mobile" :value="mobile">
                    <input type="hidden" name="customer_name" :value="customerName">
                    <input type="hidden" name="customer_id" :value="customerId">

                    <div x-show="customerFound" x-cloak class="mt-2 flex items-center justify-between gap-2 rounded-xl border border-[var(--app-border)] bg-[var(--app-bg)] px-3 py-2">
                        <p class="min-w-0 truncate text-sm font-semibold text-[var(--app-text)]">
                            <span x-text="customerName"></span>
                            <span class="text-[var(--app-muted)]"> · </span>
                            <span class="text-[var(--app-muted)]" x-text="maskMobile(mobile)"></span>
                        </p>
                        <button type="button" @click="clearCustomer()" class="shrink-0 text-xs font-bold text-[var(--app-primary)]">Change</button>
                    </div>

                    <div x-show="newCustomerOpen" x-cloak class="mt-2 grid gap-2 sm:grid-cols-2">
                        <label class="block min-w-0">
                            <span class="text-xs font-semibold uppercase tracking-[0.14em] text-[var(--app-subtle)]">Mobile Number</span>
                            <x-input-group prefix="+91" class="mt-1 w-full border border-[var(--app-border)] bg-[var(--app-bg)]">
                                <input x-model="mobile" @input.debounce.250ms="sanitizeMobile(); syncCustomerQueryFromFields(); lookupCustomer()" type="tel" inputmode="numeric" pattern="[6-9][0-9]{9}" maxlength="10" minlength="10" class="elite-input min-w-0 flex-1 border-0 bg-transparent" placeholder="9876543210">
                            </x-input-group>
                            <x-input-error :messages="$errors->get('customer_mobile')" class="mt-2" />
                        </label>
                        <label class="block min-w-0">
                            <span class="text-xs font-semibold uppercase tracking-[0.14em] text-[var(--app-subtle)]">Customer Name</span>
                            <input x-model="customerName" @input="sanitizeCustomerName(); syncCustomerQueryFromFields()" type="text" maxlength="50" pattern="[A-Za-z]+( [A-Za-z]+)*" class="mt-1 w-full rounded-2xl border border-[var(--app-border)] bg-[var(--app-bg)] px-3 py-3 text-base text-[var(--app-text)] placeholder:text-[var(--app-muted)] focus:border-[var(--app-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--app-focus)]" placeholder="Customer name">
                            <x-input-error :messages="$errors->get('customer_name')" class="mt-2" />
                        </label>
                    </div>
                </section>

                <section class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface)]/95 p-2.5 shadow-[0_10px_26px_rgba(0,0,0,0.2)] sm:p-3">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-bold text-[var(--app-text)]">Favourites</p>
                        <span class="text-xs text-[var(--app-primary)]">Quick tap</span>
                    </div>
                    <div class="mt-2 grid grid-cols-2 gap-1.5 sm:grid-cols-3">
                        <template x-for="service in favouriteServices" :key="service.id">
                            <button type="button" @click="quickAdd(service)" class="min-h-12 rounded-xl border px-2.5 py-1.5 text-left transition active:scale-[0.98]" :class="[isAdded(service) ? 'border-[var(--app-primary)] bg-[var(--app-primary-soft)] shadow-[0_0_16px_var(--app-glow)]' : 'border-[var(--app-border)] bg-[var(--app-bg)] hover:border-[var(--app-primary)]', isHairCut(service) ? 'ring-1 ring-[var(--app-primary)]/45' : '']">
                                <span class="block truncate text-sm font-bold text-[var(--app-text)]" x-text="isHairCut(service) ? `✂ ${service.name.toUpperCase()}` : service.name"></span>
                                <span class="block text-xs font-semibold" :class="isAdded(service) ? 'text-emerald-300' : 'text-[var(--app-primary)]'" x-text="isAdded(service) ? 'Added' : service.display_price"></span>
                            </button>
                        </template>
                    </div>
                    <div x-show="!favouriteServices.length" class="mt-2 rounded-xl border border-[var(--app-border)] bg-[var(--app-bg)] p-3 text-sm text-[var(--app-muted)]">No favourite services yet.</div>

                    <div class="mt-2.5">
                        <label class="block">
                            <span class="text-sm font-semibold text-[var(--app-text)]">Other Service</span>
                            <div class="relative mt-1.5">
                                <input x-model="serviceQuery" @focus="servicePickerOpen = true" @input="servicePickerOpen = true" type="search" class="w-full rounded-2xl border border-[var(--app-border)] bg-[var(--app-bg)] px-3 py-3 text-base text-[var(--app-text)] placeholder:text-[var(--app-muted)] focus:border-[var(--app-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--app-focus)]" placeholder="Search / Select Service">
                                <div x-show="servicePickerOpen" x-cloak @click.outside="servicePickerOpen = false" class="absolute left-0 right-0 top-full z-20 mt-1 max-h-72 overflow-y-auto rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-1.5 shadow-2xl shadow-black/40">
                                    <template x-for="service in visibleServices" :key="service.id">
                                        <button type="button" @click="addService(service); servicePickerOpen = false; serviceQuery = ''" class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-left transition hover:bg-[var(--app-primary-soft)]">
                                            <span class="min-w-0">
                                                <span class="block truncate text-sm font-semibold text-[var(--app-text)]" x-text="service.name"></span>
                                                <span class="mt-1 block truncate text-xs text-[var(--app-muted)]" x-text="service.category"></span>
                                            </span>
                                            <span class="shrink-0 text-sm font-bold text-[var(--app-primary)]" x-text="service.display_price"></span>
                                        </button>
                                    </template>
                                    <p x-show="visibleServices.length === 0" class="rounded-2xl px-3 py-4 text-sm text-[var(--app-muted)]">No matching service.</p>
                                </div>
                            </div>
                        </label>
                        <div x-show="isAdmin" class="mt-3 flex flex-wrap gap-2">
                            <template x-for="service in visibleServices.slice(0, 6)" :key="`fav-${service.id}`">
                                <button type="button" @click="toggleFavorite(service)" class="rounded-full border border-[var(--app-border)] bg-[var(--app-bg)] px-3 py-2 text-xs font-semibold text-[var(--app-primary)]">
                                    <span x-text="service.is_favourite ? 'Unstar' : 'Star'"></span>
                                    <span x-text="service.name"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                    <x-input-error :messages="$errors->get('items')" class="mt-3" />
                </section>

                <section class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface)]/95 p-2.5 shadow-[0_10px_26px_rgba(0,0,0,0.2)] sm:p-3">
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-[var(--app-text)]">Selected</p>
                        </div>
                        <p class="text-xs font-semibold text-[var(--app-muted)]" x-text="`${items.length} item${items.length === 1 ? '' : 's'}`"></p>
                    </div>

                    <div class="space-y-2">
                        <div x-show="undoItem" class="rounded-xl border border-[var(--app-border)] bg-[var(--app-bg)] p-3 text-sm text-[var(--app-text)] shadow-inner shadow-black/20">
                            Removed <span x-text="undoItem?.name"></span>.
                            <button type="button" @click="restoreItem" class="ml-2 font-semibold text-[var(--app-primary)]">Undo</button>
                        </div>

                        <template x-for="(item, index) in items" :key="item.key">
                            <div class="rounded-xl border border-[var(--app-border)] bg-[var(--app-bg)] px-3 py-2 shadow-[0_8px_18px_rgba(0,0,0,0.14)]">
                                <input type="hidden" :name="`items[${index}][service_id]`" :value="item.id">
                                <input type="hidden" :name="`items[${index}][quantity]`" :value="item.quantity">
                                @if (! auth()->user()->isAdmin())
                                    <input type="hidden" :name="`items[${index}][service_performed_by]`" value="{{ auth()->id() }}">
                                @endif

                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-[var(--app-text)]" x-text="item.name"></p>
                                        <p class="text-xs font-semibold text-[var(--app-primary)]" x-text="money(lineTotal(item))"></p>
                                    </div>
                                    <button type="button" @click="removeItem(index)" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-red-300/40 text-lg font-bold text-red-200 transition hover:bg-red-500/10" :aria-label="`Remove ${item.name}`">×</button>
                                </div>

                                <div class="mt-2 grid grid-cols-[minmax(0,1fr)_auto_auto] items-center gap-2">
                                    @if (auth()->user()->isAdmin())
                                        <select :name="`items[${index}][service_performed_by]`" x-model="item.service_performed_by" class="min-w-0 rounded-xl border border-[var(--app-border)] bg-[var(--app-surface-elevated)] px-3 py-2.5 text-sm text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-2 focus:ring-[var(--app-focus)] focus:outline-none">
                                            <option value="{{ auth()->id() }}">{{ auth()->user()->name }}</option>
                                            @foreach ($staff as $staffMember)
                                                <option value="{{ $staffMember->id }}">{{ $staffMember->name }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <p class="min-w-0 truncate rounded-xl border border-[var(--app-border)] bg-[var(--app-surface-elevated)] px-3 py-2.5 text-sm font-semibold text-[var(--app-text)]">{{ auth()->user()->name }}</p>
                                    @endif
                                    <div class="grid grid-cols-[2.25rem_2rem_2.25rem] items-center rounded-full border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-1">
                                        <button type="button" @click="decrease(item, index)" :aria-label="`Decrease quantity for ${item.name}`" class="h-8 w-8 rounded-full text-xl font-semibold text-[var(--app-primary)]">−</button>
                                        <p class="text-center text-sm font-bold text-[var(--app-text)]" x-text="item.quantity"></p>
                                        <button type="button" @click="increase(item)" :aria-label="`Increase quantity for ${item.name}`" class="h-8 w-8 rounded-full bg-[var(--app-primary-strong)] text-xl font-semibold text-black">+</button>
                                    </div>
                                </div>

                                <label class="mt-2 block" x-show="item.estimated">
                                    <span class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--app-subtle)]">Confirmed Price</span>
                                    <input type="number" min="0" step="0.01" x-model.number="item.confirmed_price" :name="`items[${index}][confirmed_price]`" class="mt-1 w-full rounded-xl border border-[var(--app-border)] bg-[var(--app-surface-elevated)] px-3 py-2.5 text-sm text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-2 focus:ring-[var(--app-focus)] focus:outline-none">
                                </label>
                            </div>
                        </template>

                        <p x-show="items.length === 0" class="rounded-xl border border-[var(--app-border)] bg-[var(--app-bg)] p-3 text-sm text-[var(--app-muted)]">No services added yet.</p>
                    </div>
                </section>

                <details class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface)]/95 p-2.5 text-sm text-[var(--app-text)]">
                    <summary class="cursor-pointer font-semibold text-[var(--app-primary)]">Today’s Bills ({{ $todayBills->count() }})</summary>
                    <div class="mt-2 space-y-1.5">
                        @forelse ($todayBills as $todayBill)
                            <a href="{{ route($routeRoot.'.billing.show', $todayBill, false) }}" class="flex items-center justify-between gap-3 rounded-xl border border-[var(--app-border)] bg-[var(--app-bg)] px-3 py-2">
                                <span class="min-w-0">
                                    <span class="block truncate text-xs font-semibold">{{ $todayBill->invoice_number }}</span>
                                    <span class="block truncate text-xs text-[var(--app-muted)]">{{ $todayBill->customer->name }}</span>
                                </span>
                                <span class="shrink-0 text-xs font-bold text-[var(--app-primary)]">{{ \App\Support\Money::inr($todayBill->grand_total) }}</span>
                            </a>
                        @empty
                            <p class="rounded-xl border border-[var(--app-border)] bg-[var(--app-bg)] px-3 py-2 text-[var(--app-muted)]">No bills created today.</p>
                        @endforelse
                    </div>
                </details>

                <div class="fixed inset-x-0 bottom-[82px] z-30 px-3 lg:bottom-5 lg:left-auto lg:right-6 lg:w-[360px]">
                    <div class="mx-auto flex max-w-2xl items-center gap-3 rounded-full border border-[var(--app-border)] bg-[var(--app-surface)]/95 p-2 shadow-2xl shadow-black/40 backdrop-blur-xl">
                        <div class="min-w-0 flex-1 pl-3">
                            <p class="truncate text-base font-bold text-[var(--app-primary)]" x-text="`${items.length} ${items.length === 1 ? 'Service' : 'Services'} • ${money(grandTotal())}`"></p>
                        </div>
                        <button type="button" @click="openPayment()" :disabled="items.length === 0" class="rounded-full bg-[var(--app-primary-strong)] px-5 py-3 text-sm font-bold uppercase tracking-[0.12em] text-black shadow-[0_0_24px_var(--app-glow)] transition disabled:cursor-not-allowed disabled:opacity-50">Continue</button>
                    </div>
                </div>

                <div x-show="paymentOpen" x-cloak class="fixed inset-0 z-50" aria-modal="true" role="dialog">
                    <div class="absolute inset-0 bg-black/70" @click="paymentOpen = false"></div>
                    <section class="absolute inset-x-0 bottom-0 max-h-[86vh] overflow-y-auto rounded-t-[32px] border-t border-[var(--app-border)] bg-[var(--app-surface)] p-4 pb-[calc(env(safe-area-inset-bottom)+1rem)] shadow-[0_-24px_80px_rgba(0,0,0,0.55)] sm:left-1/2 sm:max-w-xl sm:-translate-x-1/2 sm:rounded-[32px] sm:border">
                        <div class="mx-auto mb-4 h-1.5 w-12 rounded-full bg-[var(--app-border)]"></div>
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm text-[var(--app-muted)]">Total</p>
                                <p class="mt-1 text-4xl font-bold text-[var(--app-primary)]" x-text="money(grandTotal())"></p>
                            </div>
                            <button type="button" @click="paymentOpen = false" class="h-11 w-11 rounded-full border border-[var(--app-border)] text-xl text-[var(--app-text)]">×</button>
                        </div>

                        <div class="mt-6 grid grid-cols-3 gap-2">
                            <template x-for="method in ['cash', 'upi', 'card']" :key="method">
                                <label class="cursor-pointer rounded-3xl border px-3 py-4 text-center text-sm font-bold uppercase transition" :class="paymentMethod === method ? 'border-[var(--app-primary)] bg-[var(--app-primary-strong)] text-black shadow-[0_0_24px_var(--app-glow)]' : 'border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-text)]'">
                                    <input type="radio" name="payment_method" class="sr-only" :value="method" x-model="paymentMethod">
                                    <span x-text="method"></span>
                                </label>
                            </template>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <template x-for="method in ['split', 'other']" :key="method">
                                <label class="cursor-pointer rounded-3xl border px-3 py-3 text-center text-sm font-semibold capitalize transition" :class="paymentMethod === method ? 'border-[var(--app-primary)] bg-[var(--app-primary-soft)] text-[var(--app-primary)]' : 'border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-muted)]'">
                                    <input type="radio" name="payment_method" class="sr-only" :value="method" x-model="paymentMethod">
                                    <span x-text="method === 'split' ? 'Split Payment' : method"></span>
                                </label>
                            </template>
                        </div>

                        <input name="payment_note" x-model="paymentNote" class="mt-5 w-full rounded-3xl border border-[var(--app-border)] bg-[var(--app-bg)] px-4 py-4 text-sm text-[var(--app-text)] placeholder:text-[var(--app-subtle)] focus:border-[var(--app-primary)] focus:ring-2 focus:ring-[var(--app-focus)] focus:outline-none" placeholder="Optional payment note" :required="paymentMethod === 'other'">

                        <div class="mt-5 space-y-4" x-show="paymentMethod === 'split'">
                            <template x-for="(payment, index) in splitPayments" :key="index">
                                <div class="grid grid-cols-[120px_1fr] gap-3">
                                    <select :name="paymentMethod === 'split' ? `split_payments[${index}][method]` : null" :disabled="paymentMethod !== 'split'" x-model="payment.method" class="rounded-[22px] border border-[var(--app-border)] bg-[var(--app-bg)] px-3 py-4 text-sm text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-2 focus:ring-[var(--app-focus)] focus:outline-none">
                                        <option value="cash">Cash</option>
                                        <option value="upi">UPI</option>
                                        <option value="card">Card</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <input :name="paymentMethod === 'split' ? `split_payments[${index}][amount]` : null" :disabled="paymentMethod !== 'split'" x-model.number="payment.amount" type="number" step="0.01" min="0.01" class="rounded-[22px] border border-[var(--app-border)] bg-[var(--app-bg)] px-3 py-4 text-sm text-[var(--app-text)] placeholder:text-[var(--app-subtle)] focus:border-[var(--app-primary)] focus:ring-2 focus:ring-[var(--app-focus)] focus:outline-none" placeholder="Amount">
                                </div>
                            </template>
                            <div class="flex flex-wrap gap-3">
                                <button type="button" @click="addSplitPayment" class="rounded-full border border-[var(--app-border)] bg-[var(--app-bg)] px-4 py-3 text-sm font-semibold text-[var(--app-primary)]">Add split row</button>
                                <button type="button" @click="fillSplitBalance" class="rounded-full border border-[var(--app-border)] bg-[var(--app-bg)] px-4 py-3 text-sm font-semibold text-[var(--app-primary)]">Fill balance</button>
                            </div>
                            <x-input-error :messages="$errors->get('split_payments')" class="mt-2" />
                        </div>

                        <button type="submit" :disabled="submitting || items.length === 0" class="mt-6 w-full rounded-full bg-[var(--app-primary-strong)] px-5 py-4 text-sm font-bold uppercase tracking-[0.14em] text-black shadow-xl shadow-[var(--app-glow)] transition disabled:cursor-not-allowed disabled:opacity-60 hover:brightness-110">
                            <span x-show="!submitting">Generate Bill</span>
                            <span x-show="submitting" class="inline-flex items-center justify-center gap-2">
                                <span class="h-4 w-4 animate-spin rounded-full border-2 border-black/30 border-t-black"></span>
                                Creating Invoice...
                            </span>
                        </button>
                    </section>
                </div>
            </form>
        </div>
    </div>

    </div>

    <script>
        function billingDesk(config) {
            return {
                services: config.services,
                categories: config.categories || [],
                staff: config.staff || [],
                lookupUrl: config.lookupUrl,
                isAdmin: Boolean(config.isAdmin),
                now: new Date(),
                customerQuery: [config.initialCustomerName, config.initialMobile].filter(Boolean).join(' · '),
                mobile: String(config.initialMobile || '').replace(/\D/g, '').slice(-10),
                customerName: config.initialCustomerName || '',
                customerId: config.initialCustomerId || '',
                customerFound: Boolean(config.initialCustomerId),
                customerStatus: config.initialCustomerId ? 'Existing Customer' : 'New Customer',
                lastVisit: '',
                lookupLoading: false,
                lookupController: null,
                customerSuggestions: [],
                customerSuggestionsOpen: false,
                newCustomerOpen: false,
                serviceQuery: '',
                servicePickerOpen: false,
                activeCategory: config.initialActiveCategory ?? null,
                selectedServices: [],
                items: config.initialItems || [],
                undoItem: null,
                paymentMethod: config.initialPaymentMethod || 'cash',
                paymentNote: config.initialPaymentNote || '',
                paymentOpen: false,
                splitPayments: Array.isArray(config.initialSplitPayments) && config.initialSplitPayments.length
                    ? config.initialSplitPayments
                    : [{method: 'cash', amount: 0}, {method: 'upi', amount: 0}],
                submitting: false,
                submitError: '',
                init() {
                    setInterval(() => this.now = new Date(), 1000);
                },
                get filteredServices() {
                    const q = this.serviceQuery.trim().toLowerCase();
                    return this.services
                        .filter(service => q === '' || service.search.includes(q))
                        .filter(service => this.activeCategory === null || Number(service.category_id) === Number(this.activeCategory));
                },
                get visibleServices() {
                    return this.filteredServices
                        .filter(service => ! this.favouriteServices.some(favourite => favourite.id === service.id))
                        .slice(0, 12);
                },
                get favouriteServices() {
                    return this.services
                        .filter(service => service.is_favourite || this.favouriteRank(service) < 99)
                        .sort((a, b) => this.favouriteRank(a) - this.favouriteRank(b) || a.name.localeCompare(b.name))
                        .slice(0, 8);
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
                isHairCut(service) {
                    const normalized = String(service.name || '').toLowerCase().replace(/\s+/g, '');
                    return normalized === 'haircut';
                },
                favouriteRank(service) {
                    const normalized = String(service.name || '').toLowerCase().replace(/[&+]/g, 'and').replace(/\s+/g, ' ').trim();
                    const preferred = [
                        'hair cut',
                        'haircut',
                        'hair cut and shaving',
                        'haircut and shaving',
                        'shaving',
                        'beard trim',
                        'hair wash',
                        'facial',
                    ];
                    const index = preferred.indexOf(normalized);
                    if (index >= 0) return index;

                    return service.is_favourite ? 50 : 99;
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
                syncCustomerQueryFromFields() {
                    if (this.mobile.length > 0) {
                        this.customerQuery = this.mobile;
                    } else if (this.customerName.length > 0) {
                        this.customerQuery = this.customerName;
                    }
                },
                lookupCustomer() {
                    const rawQuery = String(this.customerQuery || '').trim();
                    const digits = rawQuery.replace(/\D/g, '').slice(-10);
                    if (this.lookupController) this.lookupController.abort();
                    if (rawQuery.length < 2 && digits.length < 2) {
                        this.lookupLoading = false;
                        this.customerFound = false;
                        this.customerId = '';
                        this.lastVisit = '';
                        this.customerSuggestions = [];
                        this.customerSuggestionsOpen = false;
                        this.customerStatus = 'New Customer';
                        return;
                    }
                    this.lookupController = new AbortController();
                    this.lookupLoading = true;
                    fetch(`${this.lookupUrl}?q=${encodeURIComponent(rawQuery || digits)}`, {
                        headers: {'Accept': 'application/json'},
                        signal: this.lookupController.signal,
                    })
                        .then(response => response.ok ? response.json() : Promise.reject())
                        .then(data => {
                            this.customerSuggestions = data.customers || [];
                            this.customerSuggestionsOpen = true;
                            const exact = digits.length === 10 ? this.customerSuggestions.find(customer => customer.mobile === digits) : null;
                            if (exact) {
                                this.selectCustomer(exact);
                                return;
                            }
                            this.customerFound = false;
                            this.customerStatus = this.customerSuggestions.length ? 'Select Customer' : 'New Customer';
                            this.customerId = '';
                            this.lastVisit = '';
                            this.newCustomerOpen = false;
                        })
                        .catch(error => {
                            if (error.name !== 'AbortError') {
                                this.customerFound = false;
                                this.customerId = '';
                                this.lastVisit = '';
                                this.customerSuggestions = [];
                                this.customerSuggestionsOpen = false;
                                this.customerStatus = 'New Customer';
                            }
                        })
                        .finally(() => {
                            this.lookupLoading = false;
                        });
                },
                selectCustomer(customer) {
                    this.customerFound = true;
                    this.customerStatus = 'Existing Customer';
                    this.customerId = customer.id || '';
                    this.customerName = customer.name || '';
                    this.mobile = String(customer.mobile || '').replace(/\D/g, '').slice(-10);
                    this.customerQuery = `${this.customerName} · ${this.mobile}`;
                    this.lastVisit = customer.last_visit_at || '';
                    this.customerSuggestionsOpen = false;
                    this.newCustomerOpen = false;
                },
                startNewCustomer() {
                    const digits = String(this.customerQuery || '').replace(/\D/g, '').slice(-10);
                    if (digits.length > 0) {
                        this.mobile = digits;
                    } else {
                        this.customerName = String(this.customerQuery || '').replace(/[^A-Za-z ]/g, '').trim().slice(0, 50);
                    }
                    this.customerFound = false;
                    this.customerId = '';
                    this.lastVisit = '';
                    this.customerStatus = 'New Customer';
                    this.customerSuggestionsOpen = false;
                    this.newCustomerOpen = true;
                },
                clearCustomer() {
                    this.customerFound = false;
                    this.customerId = '';
                    this.customerName = '';
                    this.mobile = '';
                    this.customerQuery = '';
                    this.lastVisit = '';
                    this.customerStatus = 'New Customer';
                    this.customerSuggestions = [];
                    this.customerSuggestionsOpen = false;
                    this.newCustomerOpen = false;
                },
                maskMobile(mobile) {
                    const digits = String(mobile || '').replace(/\D/g, '');
                    if (digits.length < 10) return digits;
                    return `${digits.slice(0, 4)}X XXXXX`;
                },
                billClock() {
                    return this.now.toLocaleDateString('en-IN', {day: '2-digit', month: 'short', year: 'numeric', timeZone: 'Asia/Kolkata'})
                        + ' • '
                        + this.now.toLocaleTimeString('en-IN', {hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Kolkata'});
                },
                openPayment() {
                    if (this.items.length === 0) return;
                    this.fillSplitBalance();
                    this.paymentOpen = true;
                },
                async submitBilling(event) {
                    const form = event.target;
                    if (this.submitting || this.items.length === 0) return;

                    this.submitting = true;
                    this.submitError = '';

                    try {
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
                        service_performed_by: this.staff[0]?.id || '',
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
                performerName(id) {
                    return this.staff.find(staff => Number(staff.id) === Number(id))?.name || @js(auth()->user()->name);
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
