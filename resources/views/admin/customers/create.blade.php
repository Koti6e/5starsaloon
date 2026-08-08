<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="p-2 rounded-xl bg-gradient-to-br from-[#d5a93b] to-[#f4d27a] shadow-lg shadow-[#d5a93b]/20 flex-shrink-0">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-[#111]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <h1 class="font-serif text-xl sm:text-2xl font-bold text-[#f4d27a] truncate">Add New Customer</h1>
                <p class="text-xs sm:text-sm text-[#d8c8a3] truncate">Create a new customer profile in the system</p>
            </div>
        </div>
    </x-slot>

    <div class="py-4 sm:py-8">
        <div class="mx-auto max-w-4xl px-3 sm:px-6 lg:px-8">
            <form 
                method="POST" 
                action="{{ route('admin.customers.store') }}" 
                x-data="{ 
                    mobile: @js(old('mobile', '')), 
                    name: @js(old('name', '')), 
                    email: @js(old('email', '')),
                    area: @js(old('area', '')),
                    city: @js(old('city', '')),
                    notes: @js(old('notes', '')),
                    gender: @js(old('gender', '')),
                    
                    cleanMobile() { 
                        this.mobile = String(this.mobile || '').replace(/\D/g, '').slice(-10); 
                    },
                    
                    cleanName() { 
                        this.name = String(this.name || '')
                            .replace(/[^A-Za-z\s]/g, '')
                            .replace(/\s+/g, ' ')
                            .replace(/^\s+/, '')
                            .slice(0, 50); 
                    },
                    
                    cleanEmail() {
                        this.email = String(this.email || '').trim().toLowerCase();
                    }
                }" 
                class="space-y-4 sm:space-y-6"
            >
                @csrf

                <!-- Main Card -->
                <div class="overflow-hidden rounded-xl sm:rounded-2xl border border-[#c8a24a]/20 bg-gradient-to-br from-[#0d0b08] to-[#0a0806] shadow-xl shadow-black/30">
                    
                    <!-- Card Header -->
                    <div class="border-b border-[#c8a24a]/15 px-4 sm:px-6 py-3 sm:py-4">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <div class="p-1.5 rounded-lg bg-[#d5a93b]/10 flex-shrink-0">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-[#f4d27a]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-xs sm:text-sm font-semibold text-[#f8efd8] truncate">Customer Information</h3>
                                <p class="text-[10px] sm:text-xs text-[#d8c8a3] truncate">Enter the customer's personal details</p>
                            </div>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-4 sm:p-6">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <!-- Name Field - Full Width -->
                            <div class="sm:col-span-2">
                                <label class="block">
                                    <span class="text-xs sm:text-sm font-semibold text-[#f8efd8]">
                                        Full Name <span class="text-[#d5a93b]">*</span>
                                    </span>
                                    <div class="relative mt-1.5">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-4 w-4 sm:h-5 sm:w-5 text-[#c8a24a]/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                        </div>
                                        <input 
                                            name="name" 
                                            x-model="name" 
                                            @input="cleanName" 
                                            maxlength="50" 
                                            pattern="[A-Za-z]+( [A-Za-z]+)*" 
                                            required 
                                            placeholder="Enter customer's full name"
                                            class="block w-full pl-8 sm:pl-10 pr-3 sm:pr-4 py-2.5 sm:py-3 text-sm sm:text-base rounded-lg sm:rounded-xl border-2 bg-[#0d0b08]/80 text-[#fff9ea] placeholder:text-[#6a5f4a] focus:border-[#f4d27a] focus:ring-2 focus:ring-[#f4d27a]/30 transition-all duration-200"
                                            style="border-color: var(--border-clr, #c8a24a/30);"
                                        />
                                    </div>
                                    <x-input-error :messages="$errors->get('name')" class="mt-1 sm:mt-2 text-xs sm:text-sm" />
                                </label>
                            </div>

                            <!-- Mobile Field -->
                            <div>
                                <label class="block">
                                    <span class="text-xs sm:text-sm font-semibold text-[#f8efd8]">
                                        Mobile <span class="text-[#d5a93b]">*</span>
                                    </span>
                                    <div class="mt-1.5 flex overflow-hidden rounded-lg sm:rounded-xl border-2 bg-[#0d0b08]/80 focus-within:border-[#f4d27a] focus-within:ring-2 focus-within:ring-[#f4d27a]/30 transition-all duration-200"
                                         style="border-color: var(--border-clr, #c8a24a/30);">
                                        <span class="flex items-center border-r px-2 sm:px-3 text-xs sm:text-sm font-bold text-[#f4d27a] bg-[#0d0b08] flex-shrink-0"
                                              style="border-color: var(--border-clr, #c8a24a/20);">
                                            +91
                                        </span>
                                        <input 
                                            name="mobile" 
                                            x-model="mobile" 
                                            @input="cleanMobile" 
                                            type="tel" 
                                            inputmode="numeric" 
                                            pattern="[6-9][0-9]{9}" 
                                            maxlength="10" 
                                            minlength="10" 
                                            required 
                                            placeholder="9876543210"
                                            class="w-full min-w-0 border-0 bg-transparent px-2 sm:px-3 py-2.5 sm:py-3 text-sm sm:text-base text-[#fff9ea] placeholder:text-[#6a5f4a] focus:ring-0"
                                        />
                                    </div>
                                    <x-input-error :messages="$errors->get('mobile')" class="mt-1 sm:mt-2 text-xs sm:text-sm" />
                                </label>
                            </div>

                            <!-- Email Field -->
                            <div>
                                <label class="block">
                                    <span class="text-xs sm:text-sm font-semibold text-[#f8efd8]">Email</span>
                                    <div class="relative mt-1.5">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-4 w-4 sm:h-5 sm:w-5 text-[#c8a24a]/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                        <input 
                                            name="email" 
                                            x-model="email" 
                                            @input="cleanEmail" 
                                            type="email" 
                                            placeholder="customer@example.com"
                                            class="block w-full pl-8 sm:pl-10 pr-3 sm:pr-4 py-2.5 sm:py-3 text-sm sm:text-base rounded-lg sm:rounded-xl border-2 bg-[#0d0b08]/80 text-[#fff9ea] placeholder:text-[#6a5f4a] focus:border-[#f4d27a] focus:ring-2 focus:ring-[#f4d27a]/30 transition-all duration-200"
                                            style="border-color: var(--border-clr, #c8a24a/30);"
                                        />
                                    </div>
                                    <x-input-error :messages="$errors->get('email')" class="mt-1 sm:mt-2 text-xs sm:text-sm" />
                                </label>
                            </div>

                            <!-- Gender Field -->
                            <div>
                                <label class="block">
                                    <span class="text-xs sm:text-sm font-semibold text-[#f8efd8]">Gender</span>
                                    <div class="relative mt-1.5">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-4 w-4 sm:h-5 sm:w-5 text-[#c8a24a]/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                        </div>
                                        <select 
                                            name="gender" 
                                            x-model="gender"
                                            class="block w-full pl-8 sm:pl-10 pr-8 sm:pr-10 py-2.5 sm:py-3 text-sm sm:text-base rounded-lg sm:rounded-xl border-2 bg-[#0d0b08]/80 text-[#fff9ea] appearance-none focus:border-[#f4d27a] focus:ring-2 focus:ring-[#f4d27a]/30 transition-all duration-200"
                                            style="border-color: var(--border-clr, #c8a24a/30);"
                                        >
                                            <option value="">Select</option>
                                            <option value="female">Female</option>
                                            <option value="male">Male</option>
                                            <option value="other">Other</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <svg class="h-4 w-4 sm:h-5 sm:w-5 text-[#c8a24a]/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <x-input-error :messages="$errors->get('gender')" class="mt-1 sm:mt-2 text-xs sm:text-sm" />
                                </label>
                            </div>

                            <!-- Area Field -->
                            <div>
                                <label class="block">
                                    <span class="text-xs sm:text-sm font-semibold text-[#f8efd8]">Area</span>
                                    <div class="relative mt-1.5">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-4 w-4 sm:h-5 sm:w-5 text-[#c8a24a]/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                        </div>
                                        <input 
                                            name="area" 
                                            x-model="area" 
                                            placeholder="e.g., Andheri East"
                                            class="block w-full pl-8 sm:pl-10 pr-3 sm:pr-4 py-2.5 sm:py-3 text-sm sm:text-base rounded-lg sm:rounded-xl border-2 bg-[#0d0b08]/80 text-[#fff9ea] placeholder:text-[#6a5f4a] focus:border-[#f4d27a] focus:ring-2 focus:ring-[#f4d27a]/30 transition-all duration-200"
                                            style="border-color: var(--border-clr, #c8a24a/30);"
                                        />
                                    </div>
                                    <x-input-error :messages="$errors->get('area')" class="mt-1 sm:mt-2 text-xs sm:text-sm" />
                                </label>
                            </div>

                            <!-- City Field -->
                            <div>
                                <label class="block">
                                    <span class="text-xs sm:text-sm font-semibold text-[#f8efd8]">City</span>
                                    <div class="relative mt-1.5">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-4 w-4 sm:h-5 sm:w-5 text-[#c8a24a]/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                            </svg>
                                        </div>
                                        <input 
                                            name="city" 
                                            x-model="city" 
                                            placeholder="e.g., Mumbai"
                                            class="block w-full pl-8 sm:pl-10 pr-3 sm:pr-4 py-2.5 sm:py-3 text-sm sm:text-base rounded-lg sm:rounded-xl border-2 bg-[#0d0b08]/80 text-[#fff9ea] placeholder:text-[#6a5f4a] focus:border-[#f4d27a] focus:ring-2 focus:ring-[#f4d27a]/30 transition-all duration-200"
                                            style="border-color: var(--border-clr, #c8a24a/30);"
                                        />
                                    </div>
                                    <x-input-error :messages="$errors->get('city')" class="mt-1 sm:mt-2 text-xs sm:text-sm" />
                                </label>
                            </div>
                        </div>

                        <!-- Notes Field - Full Width -->
                        <div class="mt-4 sm:mt-5">
                            <label class="block">
                                <span class="text-xs sm:text-sm font-semibold text-[#f8efd8]">Additional Notes</span>
                                <div class="relative mt-1.5">
                                    <div class="absolute top-3 left-3 pointer-events-none">
                                        <svg class="h-4 w-4 sm:h-5 sm:w-5 text-[#c8a24a]/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </div>
                                    <textarea 
                                        name="notes" 
                                        x-model="notes" 
                                        rows="3" 
                                        placeholder="Any special notes about the customer..."
                                        class="block w-full pl-8 sm:pl-10 pr-3 sm:pr-4 py-2.5 sm:py-3 text-sm sm:text-base rounded-lg sm:rounded-xl border-2 bg-[#0d0b08]/80 text-[#fff9ea] placeholder:text-[#6a5f4a] focus:border-[#f4d27a] focus:ring-2 focus:ring-[#f4d27a]/30 transition-all duration-200 resize-none"
                                        style="border-color: var(--border-clr, #c8a24a/30);"
                                    ></textarea>
                                </div>
                                <x-input-error :messages="$errors->get('notes')" class="mt-1 sm:mt-2 text-xs sm:text-sm" />
                            </label>
                        </div>
                    </div>

                    <!-- Card Footer -->
                    <div class="border-t border-[#c8a24a]/15 px-4 sm:px-6 py-3 sm:py-4 bg-[#0d0b08]/50">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                            <div class="flex items-center gap-2 text-[10px] sm:text-xs text-[#d8c8a3] order-2 sm:order-1">
                                <svg class="w-3 h-3 sm:w-4 sm:h-4 text-[#d5a93b] flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="truncate">Fields with <span class="text-[#d5a93b]">*</span> are required</span>
                            </div>
                            <div class="flex flex-wrap gap-2 sm:gap-3 order-1 sm:order-2">
                                <a href="{{ route('admin.customers.index') }}" 
                                   class="inline-flex items-center justify-center gap-1 sm:gap-2 rounded-lg sm:rounded-xl border-2 px-3 sm:px-5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold transition-all duration-200 hover:bg-white/5 flex-1 sm:flex-none"
                                   style="border-color: var(--border-clr, #c8a24a/30); color: var(--text-secondary, #d8c8a3);">
                                    <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                    </svg>
                                    <span class="hidden xs:inline">Cancel</span>
                                </a>
                                <button 
                                    type="submit" 
                                    class="inline-flex items-center justify-center gap-1 sm:gap-2 rounded-lg sm:rounded-xl px-4 sm:px-6 py-2 sm:py-2.5 text-xs sm:text-sm font-bold tracking-wide text-[#111] shadow-lg shadow-[#d5a93b]/20 hover:shadow-xl hover:shadow-[#d5a93b]/40 hover:scale-[1.02] transition-all duration-200 active:scale-[0.98] flex-1 sm:flex-none"
                                    style="background: linear-gradient(135deg, #d5a93b, #f4d27a);">
                                    <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Save
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions - Mobile Responsive Grid -->
                <div class="grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4">
                    <a href="{{ route('admin.customers.index') }}" 
                       class="flex items-center gap-2 sm:gap-3 rounded-xl border border-[#c8a24a]/15 bg-[#0d0b08]/50 p-3 sm:p-4 transition-all duration-200 hover:border-[#c8a24a]/30 hover:bg-[#0d0b08] group">
                        <div class="p-1.5 sm:p-2 rounded-lg bg-[#d5a93b]/10 group-hover:bg-[#d5a93b]/20 transition-colors flex-shrink-0">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-[#f4d27a]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs sm:text-sm font-medium text-[#f8efd8] truncate">All Customers</p>
                            <p class="text-[10px] sm:text-xs text-[#d8c8a3] truncate">Manage existing</p>
                        </div>
                    </a>
                    
                    <a href="{{ route('appointments.book') }}" 
                       class="flex items-center gap-2 sm:gap-3 rounded-xl border border-[#c8a24a]/15 bg-[#0d0b08]/50 p-3 sm:p-4 transition-all duration-200 hover:border-[#c8a24a]/30 hover:bg-[#0d0b08] group">
                        <div class="p-1.5 sm:p-2 rounded-lg bg-[#d5a93b]/10 group-hover:bg-[#d5a93b]/20 transition-colors flex-shrink-0">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-[#f4d27a]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs sm:text-sm font-medium text-[#f8efd8] truncate">Book Appointment</p>
                            <p class="text-[10px] sm:text-xs text-[#d8c8a3] truncate">Schedule new</p>
                        </div>
                    </a>
                    
                    <a href="{{ route('admin.dashboard') }}" 
                       class="flex items-center gap-2 sm:gap-3 rounded-xl border border-[#c8a24a]/15 bg-[#0d0b08]/50 p-3 sm:p-4 transition-all duration-200 hover:border-[#c8a24a]/30 hover:bg-[#0d0b08] group col-span-1 xs:col-span-2 sm:col-span-1">
                        <div class="p-1.5 sm:p-2 rounded-lg bg-[#d5a93b]/10 group-hover:bg-[#d5a93b]/20 transition-colors flex-shrink-0">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-[#f4d27a]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs sm:text-sm font-medium text-[#f8efd8] truncate">Dashboard</p>
                            <p class="text-[10px] sm:text-xs text-[#d8c3a3] truncate">Return to overview</p>
                        </div>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Mobile Responsive CSS Overrides -->
    <style>
        /* Extra small devices (phones, 480px and down) */
        @media (max-width: 480px) {
            .xs\:inline { display: inline !important; }
            .xs\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
        }
        
        /* Small devices (phones, 640px and down) */
        @media (max-width: 640px) {
            .container-padding {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }
            
            /* Ensure inputs don't overflow on mobile */
            input, select, textarea {
                font-size: 16px !important; /* Prevents iOS zoom */
            }
            
            /* Better touch targets on mobile */
            button, a, input, select {
                min-height: 44px;
            }
            
            /* Adjust spacing for mobile */
            .gap-4 {
                gap: 0.75rem;
            }
        }
        
        /* Prevent horizontal scroll on mobile */
        .overflow-x-hidden {
            overflow-x: hidden;
        }
        
        /* Ensure text doesn't overflow */
        .truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
</x-app-layout>
