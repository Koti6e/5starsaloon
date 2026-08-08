<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <h1 class="font-serif text-2xl text-[#f4d27a]">Staff Management</h1>
            <a href="{{ route('admin.staff.create') }}" class="rounded-md bg-[#d5a93b] px-4 py-2 text-sm font-semibold text-[#111]">Add Staff</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <p class="mb-5 rounded-md border border-[#c8a24a]/30 bg-[#11100d] p-3 text-sm text-[#f4d27a]">{{ session('status') }}</p>
            @endif
            @if (session('temporary_password'))
                <div class="mb-5 rounded-md border border-[#c8a24a]/30 bg-black p-4 text-sm text-[#f8efd8]">
                    <div class="font-semibold text-[#f4d27a]">Temporary credentials shown once</div>
                    <p class="mt-2">Username: {{ session('temporary_username') }}</p>
                    <p>Password: {{ session('temporary_password') }}</p>
                </div>
            @endif
            <x-admin.card class="overflow-hidden">
                <table class="min-w-full divide-y divide-[#c8a24a]/15 text-sm">
                    <thead class="bg-black text-left text-[#f4d27a]"><tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Username</th><th class="px-4 py-3">Mobile</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Password Change</th><th class="px-4 py-3">Actions</th></tr></thead>
                    <tbody class="divide-y divide-[#c8a24a]/10 text-[#f8efd8]">
                        @forelse ($staff as $member)
                            <tr>
                                <td class="px-4 py-3">{{ $member->name }}</td>
                                <td class="px-4 py-3">{{ $member->username }}</td>
                                <td class="px-4 py-3">{{ $member->mobile }}</td>
                                <td class="px-4 py-3">{{ ucfirst($member->status) }}</td>
                                <td class="px-4 py-3">{{ $member->must_change_password ? 'Required' : 'Completed' }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.staff.edit-password', $member) }}" class="inline-flex rounded-full bg-[#d5a93b] px-3 py-2 text-xs font-semibold text-[#111] transition hover:bg-[#f0c75e]">Reset Password</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-[#d8c8a3]">No staff accounts found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-admin.card>
            <div class="mt-6">{{ $staff->links() }}</div>
        </div>
    </div>
</x-app-layout>
