<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 class="font-serif text-2xl text-[var(--app-primary)]">Reset Staff Password</h1>
                <p class="mt-2 text-sm text-[var(--app-muted)]">Admin override for staff password reset and forced password change.</p>
            </div>
            <a href="{{ route('admin.staff.index') }}" class="rounded-md bg-[var(--app-surface-elevated)] px-4 py-2 text-sm font-semibold text-[var(--app-primary)] transition hover:bg-[var(--app-surface-elevated)]">Back to staff</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <x-admin.card>
                <form method="POST" action="{{ route('admin.staff.update-password', $staff) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="text-sm font-semibold text-[var(--app-text)]" for="password">New password</label>
                        <input id="password" name="password" type="password" required class="mt-2 w-full rounded-md border border-[var(--app-border)] bg-[var(--app-bg)] px-4 py-3 text-[var(--app-text)] placeholder:text-[var(--app-subtle)] focus:border-[var(--accent-border)] focus:ring-[var(--app-focus)]" autocomplete="new-password">
                        @error('password')
                            <p class="mt-2 text-sm text-[var(--app-primary)]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-[var(--app-text)]" for="password_confirmation">Confirm password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required class="mt-2 w-full rounded-md border border-[var(--app-border)] bg-[var(--app-bg)] px-4 py-3 text-[var(--app-text)] placeholder:text-[var(--app-subtle)] focus:border-[var(--accent-border)] focus:ring-[var(--app-focus)]" autocomplete="new-password">
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <button type="submit" class="rounded-full bg-[var(--app-primary)] px-5 py-3 text-sm font-semibold text-[var(--on-accent)] transition hover:bg-[var(--app-primary)]">Reset password</button>
                    </div>
                </form>
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
