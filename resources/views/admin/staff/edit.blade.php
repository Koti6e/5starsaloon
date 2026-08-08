<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 class="font-serif text-2xl text-[#f4d27a]">Reset Staff Password</h1>
                <p class="mt-2 text-sm text-[#d8c8a3]">Admin override for staff password reset and forced password change.</p>
            </div>
            <a href="{{ route('admin.staff.index') }}" class="rounded-md bg-[#11100d] px-4 py-2 text-sm font-semibold text-[#f4d27a] transition hover:bg-[#1b1711]">Back to staff</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <x-admin.card>
                <form method="POST" action="{{ route('admin.staff.update', $staff) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="text-sm font-semibold text-[#f8efd8]" for="password">New password</label>
                        <input id="password" name="password" type="password" required class="mt-2 w-full rounded-md border border-[#c8a24a]/30 bg-black px-4 py-3 text-[#fff9ea] placeholder:text-[#8a6616] focus:border-[#f4d27a] focus:ring-[#f4d27a]/20" autocomplete="new-password">
                        @error('password')
                            <p class="mt-2 text-sm text-[#f4d27a]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-[#f8efd8]" for="password_confirmation">Confirm password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required class="mt-2 w-full rounded-md border border-[#c8a24a]/30 bg-black px-4 py-3 text-[#fff9ea] placeholder:text-[#8a6616] focus:border-[#f4d27a] focus:ring-[#f4d27a]/20" autocomplete="new-password">
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <button type="submit" class="rounded-full bg-[#d5a93b] px-5 py-3 text-sm font-semibold text-[#111] transition hover:bg-[#f0c75e]">Reset password</button>
                    </div>
                </form>
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
