<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <h1 class="font-serif text-2xl text-[var(--app-primary)]">Staff Management</h1>
            <a href="{{ route('admin.staff.create') }}" class="rounded-md bg-[var(--app-primary)] px-4 py-2 text-sm font-semibold text-[var(--on-accent)]">Add Staff</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <p class="mb-5 rounded-md border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-3 text-sm text-[var(--app-primary)]">{{ session('status') }}</p>
            @endif
            @if (session('temporary_password'))
                <div class="mb-5 rounded-md border border-[var(--app-border)] bg-[var(--app-bg)] p-4 text-sm text-[var(--app-text)]">
                    <div class="font-semibold text-[var(--app-primary)]">Temporary credentials shown once</div>
                    <p class="mt-2">Username: {{ session('temporary_username') }}</p>
                    <p>Password: {{ session('temporary_password') }}</p>
                </div>
            @endif
            <x-admin.card class="mb-6 p-4" x-data="{openDate: null}">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-serif text-xl text-[var(--app-primary)]">Attendance Calendar</h2>
                        <p class="mt-1 text-sm text-[var(--app-muted)]">{{ $attendanceMonth->format('F Y') }} · Staff1 / Staff2 login selfies</p>
                    </div>
                    <div class="hidden gap-3 text-xs text-[var(--app-muted)] sm:flex">
                        <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded-full bg-emerald-500"></span> All present</span>
                        <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded-full bg-gradient-to-r from-emerald-500 from-50% to-red-500 to-50%"></span> Mixed</span>
                        <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded-full bg-red-500"></span> Absent</span>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-7 gap-1.5 text-center text-xs text-[var(--app-subtle)]">
                    @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $weekday)
                        <div>{{ $weekday }}</div>
                    @endforeach
                    @for ($blank = 0; $blank < $attendanceMonth->dayOfWeek; $blank++)
                        <div></div>
                    @endfor
                    @foreach ($attendanceCalendar as $day)
                        <button type="button" @click="openDate = openDate === '{{ $day['date'] }}' ? null : '{{ $day['date'] }}'" class="relative min-h-14 rounded-md border border-[var(--app-border)] p-2 text-left text-[var(--app-text)] transition hover:border-[var(--accent-border)] {{ $day['state'] === 'present' ? 'bg-emerald-600/80' : ($day['state'] === 'absent' ? 'bg-red-700/80' : ($day['state'] === 'mixed' ? 'bg-gradient-to-r from-emerald-600 from-50% to-red-700 to-50%' : 'bg-[var(--app-bg)]/40 text-[var(--app-subtle)]')) }}">
                            <span class="font-bold">{{ $day['day'] }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="mt-4 space-y-3">
                    @foreach ($attendanceCalendar as $day)
                        <div x-show="openDate === '{{ $day['date'] }}'" x-cloak class="rounded-lg border border-[var(--app-border)] bg-[var(--app-bg)]/40 p-3">
                            <h3 class="font-semibold text-[var(--app-primary)]">{{ \Illuminate\Support\Carbon::parse($day['date'])->format('d M Y') }}</h3>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                @foreach ($day['details'] as $detail)
                                    <div class="rounded-md border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-3">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="font-semibold text-[var(--app-text)]">{{ $detail['name'] }}</p>
                                                <p class="mt-1 text-sm {{ $detail['present'] ? 'text-emerald-300' : 'text-red-300' }}">{{ $detail['status'] }}</p>
                                                <p class="mt-1 text-xs text-[var(--app-subtle)]">{{ $detail['time'] ? 'Login: '.$detail['time'] : 'No login time' }}</p>
                                            </div>
                                            @if ($detail['selfie_url'])
                                                <img src="{{ $detail['selfie_url'] }}" alt="{{ $detail['name'] }} login selfie" class="h-20 w-16 rounded-md object-cover">
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-admin.card>
            <x-admin.card class="overflow-hidden">
                <table class="min-w-full divide-y divide-[var(--app-border)] text-sm">
                    <thead class="bg-[var(--app-bg)] text-left text-[var(--app-primary)]"><tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Username</th><th class="px-4 py-3">Mobile</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Password Change</th><th class="px-4 py-3">Actions</th></tr></thead>
                    <tbody class="divide-y divide-[var(--app-border)] text-[var(--app-text)]">
                        @forelse ($staff as $member)
                            <tr>
                                <td class="px-4 py-3">{{ $member->name }}</td>
                                <td class="px-4 py-3">{{ $member->username }}</td>
                                <td class="px-4 py-3">{{ $member->mobile }}</td>
                                <td class="px-4 py-3">{{ ucfirst($member->status) }}</td>
                                <td class="px-4 py-3">{{ $member->must_change_password ? 'Required' : 'Completed' }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.staff.show', $member) }}" class="mr-2 inline-flex rounded-full border border-[var(--app-border)] px-3 py-2 text-xs font-semibold text-[var(--app-primary)] transition hover:bg-[var(--app-primary-soft)]">Profile</a>
                                    <a href="{{ route('admin.staff.edit-password', $member) }}" class="inline-flex rounded-full bg-[var(--app-primary)] px-3 py-2 text-xs font-semibold text-[var(--on-accent)] transition hover:bg-[var(--app-primary)]">Reset Password</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-[var(--app-muted)]">No staff accounts found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-admin.card>
            <div class="mt-6">{{ $staff->links() }}</div>
        </div>
    </div>
</x-app-layout>
