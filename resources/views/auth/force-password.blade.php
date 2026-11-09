<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="font-serif text-2xl font-semibold text-[var(--app-primary)]">Change Your Password</h1>
        <p class="mt-2 text-sm text-[var(--app-muted)]">Set a new password before accessing salon management.</p>
    </div>
    <form method="POST" action="{{ route('password.force.update') }}" class="space-y-4">
        @csrf
        @method('PUT')
        <div>
            <x-input-label for="current_password" :value="__('Current password')" class="text-[var(--app-text)]" />
            <x-text-input id="current_password" name="current_password" type="password" class="mt-1 block w-full border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-text)]" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="password" :value="__('New password')" class="text-[var(--app-text)]" />
            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-text)]" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm new password')" class="text-[var(--app-text)]" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-text)]" required autocomplete="new-password" />
        </div>
        <x-primary-button class="w-full justify-center bg-[var(--app-primary)] text-[var(--on-accent)] hover:bg-[var(--app-primary)]">
            Update Password
        </x-primary-button>
    </form>
</x-guest-layout>
