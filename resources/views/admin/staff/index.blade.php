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
            <x-admin.card class="mb-6 p-4" x-data="{openDate: null}">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-serif text-xl text-[#f4d27a]">Attendance Calendar</h2>
                        <p class="mt-1 text-sm text-[#d8c8a3]">{{ $attendanceMonth->format('F Y') }} · Staff1 / Staff2 login selfies</p>
                    </div>
                    <div class="flex flex-wrap gap-2 text-[11px] text-[#d8c8a3]">
                        @foreach ($attendanceCalendar->first()['details'] ?? [] as $index => $legend)
                            <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded-sm {{ $index === 0 ? 'bg-emerald-500' : ($index === 1 ? 'bg-indigo-500' : 'bg-sky-500') }}"></span>{{ $legend['name'] }} present</span>
                        @endforeach
                        <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded-sm bg-red-500"></span>Absent</span>
                        <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded-sm bg-black/40 ring-1 ring-[#c8a24a]/30"></span>Not yet recorded</span>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-7 gap-1.5 text-center text-xs text-[#a89567]">
                    @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $weekday)
                        <div>{{ $weekday }}</div>
                    @endforeach
                    @for ($blank = 0; $blank < $attendanceMonth->dayOfWeek; $blank++)
                        <div></div>
                    @endfor
                    @foreach ($attendanceCalendar as $day)
                        <button type="button" @click="openDate = openDate === '{{ $day['date'] }}' ? null : '{{ $day['date'] }}'" class="relative min-h-20 overflow-hidden rounded-md border border-[#c8a24a]/15 text-left text-[#fff9ea] transition hover:border-[#f4d27a]/60">
                            <span class="absolute left-1.5 top-1 z-10 font-bold">{{ $day['day'] }}</span>
                            <span class="grid h-full pt-6" style="grid-template-rows: repeat({{ max(1, count($day['details'])) }}, minmax(0, 1fr));">
                                @foreach ($day['details'] as $index => $detail)
                                    <span class="flex items-end px-1.5 pb-1 text-[9px] font-semibold {{ $detail['state'] === 'present' ? ($index === 0 ? 'bg-emerald-600/80' : ($index === 1 ? 'bg-indigo-600/80' : 'bg-sky-600/80')) : ($detail['state'] === 'absent' ? 'bg-red-700/80' : 'bg-black/40 text-[#a89567]') }}">{{ Str::before($detail['name'], ' ') }} · {{ $detail['state'] === 'pending' ? '—' : ($detail['state'] === 'present' ? 'P' : 'A') }}</span>
                                @endforeach
                            </span>
                        </button>
                    @endforeach
                </div>

                <div class="mt-4 space-y-3">
                    @foreach ($attendanceCalendar as $day)
                        <div x-show="openDate === '{{ $day['date'] }}'" x-cloak class="rounded-lg border border-[#c8a24a]/20 bg-black/40 p-3">
                            <h3 class="font-semibold text-[#f4d27a]">{{ \Illuminate\Support\Carbon::parse($day['date'])->format('d M Y') }}</h3>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                @foreach ($day['details'] as $detail)
                                    <div class="rounded-md border border-[#c8a24a]/15 bg-[#11100d] p-3">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="font-semibold text-[#fff9ea]">{{ $detail['name'] }}</p>
                                                <p class="mt-1 text-sm {{ $detail['state'] === 'present' ? 'text-emerald-300' : ($detail['state'] === 'absent' ? 'text-red-300' : 'text-[#a89567]') }}">{{ $detail['status'] }}</p>
                                                <p class="mt-1 text-xs text-[#a89567]">{{ $detail['time'] ? 'Login: '.$detail['time'] : 'No login time' }}</p>
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
