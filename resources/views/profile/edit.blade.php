<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl text-[var(--app-primary)]">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-lg border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-4 shadow sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="rounded-lg border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-4 shadow sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
