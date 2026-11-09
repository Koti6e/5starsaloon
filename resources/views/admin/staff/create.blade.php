<x-app-layout>
    <x-slot name="header">
        <h1 class="font-serif text-2xl text-[var(--app-primary)]">Add Staff</h1>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-5 rounded-md border border-red-400/40 bg-red-950/40 p-3 text-sm text-red-100">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('admin.staff.store') }}" class="grid gap-4 md:grid-cols-2">
                <x-admin.card class="md:col-span-2">
                @csrf
                <label class="text-sm text-[var(--app-text)]">Name<input name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-md border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-text)]"></label>
                <label class="text-sm text-[var(--app-text)]">Username<input name="username" value="{{ old('username') }}" required class="mt-1 w-full rounded-md border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-text)]"></label>
                <label class="text-sm text-[var(--app-text)]">Mobile<input name="mobile" value="{{ old('mobile') }}" required class="mt-1 w-full rounded-md border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-text)]"></label>
                <label class="text-sm text-[var(--app-text)]">Email<input name="email" value="{{ old('email') }}" class="mt-1 w-full rounded-md border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-text)]"></label>
                <label class="text-sm text-[var(--app-text)]">Employee code<input name="employee_code" value="{{ old('employee_code') }}" class="mt-1 w-full rounded-md border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-text)]"></label>
                <label class="text-sm text-[var(--app-text)]">Specialization<input name="specialization" value="{{ old('specialization') }}" class="mt-1 w-full rounded-md border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-text)]"></label>
                <label class="text-sm text-[var(--app-text)]">Joining date<input type="date" name="joining_date" value="{{ old('joining_date') }}" class="mt-1 w-full rounded-md border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-text)]"></label>
                <label class="text-sm text-[var(--app-text)]">Employment type<input name="employment_type" value="{{ old('employment_type') }}" class="mt-1 w-full rounded-md border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-text)]"></label>
                <label class="text-sm text-[var(--app-text)]">Temporary password<input name="password" value="{{ old('password', $temporaryPassword) }}" required class="mt-1 w-full rounded-md border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-text)]"></label>
                <label class="text-sm text-[var(--app-text)]">Status<select name="status" class="mt-1 w-full rounded-md border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-text)]"><option value="active">Active</option><option value="inactive">Inactive</option></select></label>
                <label class="inline-flex items-center gap-2 text-sm text-[var(--app-muted)] md:col-span-2"><input type="checkbox" name="is_home_service_eligible" value="1" class="rounded border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-primary)]"> Eligible for Elite Home Service</label>
                <div class="flex gap-3 md:col-span-2">
                    <button class="rounded-md bg-[var(--app-primary)] px-5 py-3 font-semibold text-[var(--on-accent)]">Create Staff</button>
                    <a href="{{ route('admin.staff.index') }}" class="rounded-md border border-[var(--app-border)] px-5 py-3 font-semibold text-[var(--app-text)]">Cancel</a>
                </div>
                </x-admin.card>
            </form>
        </div>
    </div>
</x-app-layout>
