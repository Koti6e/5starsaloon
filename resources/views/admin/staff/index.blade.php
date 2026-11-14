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
                    <div class="flex flex-wrap gap-x-3 gap-y-1 text-xs text-[var(--app-muted)]">
                        <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded-full bg-blue-600"></span> Staff1 – Present</span>
                        <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded-full bg-yellow-400"></span> Staff2 – Present</span>
                        <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded-full bg-red-600"></span> Absent</span>
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
                        <button type="button" @click="openDate = openDate === '{{ $day['date'] }}' ? null : '{{ $day['date'] }}'" aria-label="{{ $day['date'] }} attendance details" class="relative isolate min-h-14 overflow-hidden rounded-md border border-[var(--app-border)] p-2 text-left text-white transition hover:border-[var(--accent-border)] {{ $day['future'] ? 'bg-[var(--app-bg)]/40 text-[var(--app-subtle)]' : '' }}">
                            @unless ($day['future'])
                                <span aria-hidden="true" class="absolute inset-0 flex">
                                    @foreach ($day['details'] as $detail)
                                        <span class="h-full w-1/2 {{ $detail['present'] ? ($detail['color'] === 'blue' ? 'bg-blue-600' : 'bg-yellow-400') : 'bg-red-600' }}"></span>
                                    @endforeach
                                </span>
                            @endunless
                            <span class="relative z-10 font-bold {{ $day['future'] ? 'text-[var(--app-subtle)]' : 'drop-shadow' }}">{{ $day['day'] }}</span>
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
                                                <p class="mt-1 text-sm {{ $detail['present'] ? ($detail['color'] === 'blue' ? 'text-blue-600' : 'text-yellow-600') : 'text-red-600' }}">{{ $detail['status'] }}</p>
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
                                    @unless (\App\Support\StaffCredentialPolicy::usesFixedPassword($member))
                                        <a href="{{ route('admin.staff.edit-password', $member) }}" class="inline-flex rounded-full bg-[var(--app-primary)] px-3 py-2 text-xs font-semibold text-[var(--on-accent)] transition hover:bg-[var(--app-primary)]">Reset Password</a>
                                    @endunless
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
