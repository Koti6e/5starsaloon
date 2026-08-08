<x-app-layout>
    <x-slot name="header">
        <h1 class="font-serif text-2xl text-[#f4d27a]">Staff Attendance</h1>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <p class="mb-5 rounded-md border border-[#c8a24a]/30 bg-[#11100d] p-3 text-sm text-[#f4d27a]">{{ session('status') }}</p>
            @endif
            <form method="GET" class="mb-6">
                <x-admin.card class="p-4">
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <input type="date" name="date" value="{{ $date }}" class="rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea] px-4 py-3">
                        <button class="rounded-md bg-[#d5a93b] px-4 py-3 font-semibold text-[#111]">Open Date</button>
                    </div>
                </x-admin.card>
            </form>

            <div class="grid gap-4 md:hidden">
                @foreach ($staff as $member)
                    @php($row = $attendance->get($member->id))
                    <form method="POST" action="{{ route('admin.attendance.update') }}" class="rounded-lg border border-[#c8a24a]/20 bg-[#11100d] p-4">
                        @csrf
                        <input type="hidden" name="attendance_date" value="{{ $date }}">
                        <input type="hidden" name="staff_id" value="{{ $member->id }}">
                        <h2 class="font-semibold text-[#fff9ea]">{{ $member->name }}</h2>
                        <p class="mt-1 text-xs text-[#a89567]">{{ $member->shift_start ?: 'Shift not set' }} - {{ $member->shift_end ?: 'Shift not set' }}</p>
                        @include('admin.attendance.partials.controls', ['row' => $row])
                    </form>
                @endforeach
            </div>

            <x-admin.card class="hidden overflow-hidden md:block">
                <table class="min-w-full divide-y divide-[#c8a24a]/15 text-sm">
                    <thead class="bg-black text-left text-[#f4d27a]"><tr><th class="px-4 py-3">Staff</th><th>Shift</th><th>Source</th><th>Status</th><th>Check in</th><th>Check out</th><th>Reason</th><th>Notes</th><th></th></tr></thead>
                    <tbody class="divide-y divide-[#c8a24a]/10 text-[#f8efd8]">
                        @foreach ($staff as $member)
                            @php($row = $attendance->get($member->id))
                            <tr>
                                    <td class="px-4 py-3">{{ $member->name }}</td>
                                    <td>{{ $member->shift_start ?: 'Not set' }} - {{ $member->shift_end ?: 'Not set' }}</td>
                                    <td><span class="rounded-sm border border-[#c8a24a]/30 px-2 py-1 text-xs">{{ in_array($row?->source, ['automatic_login', 'automatic_logout'], true) ? 'Auto Marked' : ($row ? 'Updated by Admin' : 'Not marked') }}</span></td>
                                    <td><select name="status" form="attendance-row-{{ $member->id }}" class="rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]">@foreach (['not_marked', 'present', 'absent', 'late', 'leave', 'weekly_off'] as $status)<option value="{{ $status }}" @selected(($row?->status ?? 'not_marked') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>@endforeach</select></td>
                                    <td><input type="time" name="check_in_time" form="attendance-row-{{ $member->id }}" value="{{ $row?->check_in_time }}" class="rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]"></td>
                                    <td><input type="time" name="check_out_time" form="attendance-row-{{ $member->id }}" value="{{ $row?->check_out_time }}" class="rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]"></td>
                                    <td><input name="correction_reason" form="attendance-row-{{ $member->id }}" required value="{{ old('correction_reason', $row?->correction_reason) }}" placeholder="Required" class="rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]"></td>
                                    <td><input name="notes" form="attendance-row-{{ $member->id }}" value="{{ $row?->notes }}" class="rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]"></td>
                                    <td class="pr-4"><button type="submit" form="attendance-row-{{ $member->id }}" class="rounded-md bg-[#d5a93b] px-3 py-2 text-xs font-semibold text-[#111]">Save</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
