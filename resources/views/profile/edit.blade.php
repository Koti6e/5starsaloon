<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl text-[#f4d27a]">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-lg border border-[#c8a24a]/20 bg-[#11100d] p-4 shadow sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="rounded-lg border border-[#c8a24a]/20 bg-[#11100d] p-4 shadow sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
