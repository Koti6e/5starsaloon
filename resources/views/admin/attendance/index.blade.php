<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h1 class="font-serif text-2xl text-[#f4d27a]">Staff Attendance</h1>
            <p class="text-sm text-[#a89567]">Manage daily attendance, check-in/out times and captured staff photos.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">

            {{-- Flash message --}}
            @if (session('status'))
                <div class="mb-6 flex items-center gap-3 rounded-xl border border-[#c8a24a]/25 bg-[#11100d] px-4 py-3 shadow-lg shadow-black/10">
                    <span class="flex h-2.5 w-2.5 shrink-0 rounded-full bg-[#d5a93b]"></span>
                    <p class="text-sm text-[#f4d27a]">{{ session('status') }}</p>
                </div>
            @endif

            {{-- Date selector --}}
            <form method="GET" class="mb-6">
                <x-admin.card class="p-5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <label for="attendance-date" class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-[#a89567]">
                                Attendance Date
                            </label>

                            <input
                                id="attendance-date"
                                type="date"
                                name="date"
                                value="{{ $date }}"
                                class="w-full rounded-xl border-[#c8a24a]/30 bg-black px-4 py-3 text-sm font-medium text-[#fff9ea] outline-none transition focus:border-[#d5a93b] focus:ring-1 focus:ring-[#d5a93b] sm:w-56"
                            >
                        </div>

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-[#d5a93b] px-6 py-3 text-sm font-semibold text-[#111] transition hover:bg-[#e4bd55] focus:outline-none focus:ring-2 focus:ring-[#d5a93b]/50"
                        >
                            Open Date
                        </button>
                    </div>
                </x-admin.card>
            </form>

            {{-- Daily summary --}}
            @php
                $presentCount = 0;
                $absentCount = 0;
                $notMarkedCount = 0;
                $otherCount = 0;

                foreach ($staff as $member) {
                    $summaryRow = $attendance->get($member->id);
                    $summaryStatus = $summaryRow?->status ?? 'not_marked';

                    if ($summaryStatus === 'present') {
                        $presentCount++;
                    } elseif ($summaryStatus === 'absent') {
                        $absentCount++;
                    } elseif ($summaryStatus === 'not_marked') {
                        $notMarkedCount++;
                    } else {
                        $otherCount++;
                    }
                }
            @endphp

            <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div class="rounded-xl border border-emerald-400/15 bg-[#11100d] p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#a89567]">Present</p>
                    <p class="mt-1 text-2xl font-semibold text-emerald-300">{{ $presentCount }}</p>
                </div>

                <div class="rounded-xl border border-red-400/15 bg-[#11100d] p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#a89567]">Absent</p>
                    <p class="mt-1 text-2xl font-semibold text-red-300">{{ $absentCount }}</p>
                </div>

                <div class="rounded-xl border border-[#c8a24a]/15 bg-[#11100d] p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#a89567]">Not Marked</p>
                    <p class="mt-1 text-2xl font-semibold text-[#f4d27a]">{{ $notMarkedCount }}</p>
                </div>

                <div class="rounded-xl border border-[#c8a24a]/15 bg-[#11100d] p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#a89567]">Other</p>
                    <p class="mt-1 text-2xl font-semibold text-[#d8c8a3]">{{ $otherCount }}</p>
                </div>
            </div>

            {{-- Attendance cards --}}
            <div class="space-y-5">
                @foreach ($staff as $member)
                    @php
                        $row = $attendance->get($member->id);
                        $status = $row?->status ?? 'not_marked';

                        $statusLabel = match ($status) {
                            'present' => 'Present',
                            'absent' => 'Absent',
                            'late' => 'Late',
                            'leave' => 'Leave',
                            'weekly_off' => 'Weekly Off',
                            default => 'Not Marked',
                        };

                        $statusClasses = match ($status) {
                            'present' => 'border-emerald-400/20 bg-emerald-400/10 text-emerald-300',
                            'absent' => 'border-red-400/20 bg-red-400/10 text-red-300',
                            'late' => 'border-amber-400/20 bg-amber-400/10 text-amber-300',
                            'leave' => 'border-sky-400/20 bg-sky-400/10 text-sky-300',
                            'weekly_off' => 'border-slate-400/20 bg-slate-400/10 text-slate-300',
                            default => 'border-[#c8a24a]/20 bg-[#c8a24a]/5 text-[#d8c8a3]',
                        };

                        $sourceLabel = in_array($row?->source, ['automatic_login', 'automatic_logout'], true)
                            ? 'Auto Marked'
                            : ($row ? 'Updated by Admin' : 'Not marked');
                    @endphp

                    <form
                        method="POST"
                        action="{{ route('admin.attendance.update') }}"
                        class="overflow-hidden rounded-2xl border border-[#c8a24a]/15 bg-[#11100d] shadow-xl shadow-black/10"
                    >
                        @csrf

                        <input type="hidden" name="attendance_date" value="{{ $date }}">
                        <input type="hidden" name="staff_id" value="{{ $member->id }}">

                        {{-- Card header --}}
                        <div class="flex flex-col gap-4 border-b border-[#c8a24a]/10 px-5 py-5 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-center gap-4">
                                {{-- Staff avatar --}}
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-[#c8a24a]/30 bg-black text-lg font-semibold text-[#f4d27a]">
                                    {{ strtoupper(substr($member->name, 0, 1)) }}
                                </div>

                                <div class="min-w-0">
                                    <h2 class="truncate text-lg font-semibold text-[#fff9ea]">
                                        {{ $member->name }}
                                    </h2>

                                    <p class="mt-1 text-xs text-[#a89567]">
                                        Shift:
                                        {{ $member->shift_start ?: 'Not set' }}
                                        –
                                        {{ $member->shift_end ?: 'Not set' }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold {{ $statusClasses }}">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                    {{ $statusLabel }}
                                </span>

                                <span class="hidden rounded-full border border-[#c8a24a]/15 bg-black px-3 py-1.5 text-[11px] font-medium text-[#a89567] sm:inline-flex">
                                    {{ $sourceLabel }}
                                </span>
                            </div>
                        </div>

                        {{-- Main content --}}
                        <div class="grid gap-6 p-5 lg:grid-cols-[180px_minmax(0,1fr)]">

                            {{-- Photo --}}
                            <div>
                                <p class="mb-3 text-xs font-semibold uppercase tracking-[0.16em] text-[#a89567]">
                                    Login Photo
                                </p>

                                @if ($row?->selfie_path)
                                    <a
                                        href="{{ route('admin.attendance.selfie', $row) }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="group block overflow-hidden rounded-2xl border border-[#c8a24a]/25 bg-black"
                                        title="View captured attendance photo"
                                    >
                                        <div class="aspect-[4/5] w-full overflow-hidden">
                                            <img
                                                src="{{ route('admin.attendance.selfie', $row) }}"
                                                alt="Attendance photo for {{ $member->name }}"
                                                class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                                            >
                                        </div>

                                        <div class="border-t border-[#c8a24a]/15 px-3 py-2.5 text-center text-xs font-semibold text-[#f4d27a]">
                                            View Captured Photo
                                        </div>
                                    </a>
                                @else
                                    <div class="flex aspect-[4/5] w-full flex-col items-center justify-center rounded-2xl border border-dashed border-[#c8a24a]/20 bg-black/50 px-4 text-center">
                                        <svg class="mb-3 h-8 w-8 text-[#a89567]/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h3l1.5-2h7L17 7h3v12H4V7Z"/>
                                            <circle cx="12" cy="13" r="3.5"/>
                                        </svg>

                                        <p class="text-xs font-medium text-[#a89567]">
                                            No attendance photo
                                        </p>
                                    </div>
                                @endif
                            </div>

                            {{-- Attendance controls --}}
                            <div class="min-w-0">
                                <div class="grid gap-4 sm:grid-cols-2">

                                    {{-- Status --}}
                                    <div>
                                        <label
                                            for="status-{{ $member->id }}"
                                            class="mb-2 block text-xs font-semibold uppercase tracking-wider text-[#a89567]"
                                        >
                                            Attendance Status
                                        </label>

                                        <select
                                            id="status-{{ $member->id }}"
                                            name="status"
                                            class="w-full rounded-xl border-[#c8a24a]/30 bg-black px-3 py-3 text-sm text-[#fff9ea] outline-none focus:border-[#d5a93b] focus:ring-1 focus:ring-[#d5a93b]"
                                        >
                                            @foreach (['not_marked', 'present', 'absent', 'late', 'leave', 'weekly_off'] as $statusOption)
                                                <option
                                                    value="{{ $statusOption }}"
                                                    @selected($status === $statusOption)
                                                >
                                                    {{ str_replace('_', ' ', ucfirst($statusOption)) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Source --}}
                                    <div>
                                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wider text-[#a89567]">
                                            Attendance Source
                                        </label>

                                        <div class="flex min-h-[46px] items-center rounded-xl border border-[#c8a24a]/15 bg-black px-3 text-sm text-[#d8c8a3]">
                                            <span class="mr-2 h-2 w-2 rounded-full {{ $row ? 'bg-[#d5a93b]' : 'bg-[#5d5749]' }}"></span>
                                            {{ $sourceLabel }}
                                        </div>
                                    </div>

                                    {{-- Check in --}}
                                    <div>
                                        <label
                                            for="check-in-{{ $member->id }}"
                                            class="mb-2 block text-xs font-semibold uppercase tracking-wider text-[#a89567]"
                                        >
                                            Check In
                                        </label>

                                        <input
                                            id="check-in-{{ $member->id }}"
                                            type="time"
                                            name="check_in_time"
                                            value="{{ $row?->check_in_time }}"
                                            class="w-full rounded-xl border-[#c8a24a]/30 bg-black px-3 py-3 text-sm text-[#fff9ea] outline-none focus:border-[#d5a93b] focus:ring-1 focus:ring-[#d5a93b]"
                                        >
                                    </div>

                                    {{-- Check out --}}
                                    <div>
                                        <label
                                            for="check-out-{{ $member->id }}"
                                            class="mb-2 block text-xs font-semibold uppercase tracking-wider text-[#a89567]"
                                        >
                                            Check Out
                                        </label>

                                        <input
                                            id="check-out-{{ $member->id }}"
                                            type="time"
                                            name="check_out_time"
                                            value="{{ $row?->check_out_time }}"
                                            class="w-full rounded-xl border-[#c8a24a]/30 bg-black px-3 py-3 text-sm text-[#fff9ea] outline-none focus:border-[#d5a93b] focus:ring-1 focus:ring-[#d5a93b]"
                                        >
                                    </div>

                                    {{-- Correction reason --}}
                                    <div class="sm:col-span-2">
                                        <label
                                            for="reason-{{ $member->id }}"
                                            class="mb-2 block text-xs font-semibold uppercase tracking-wider text-[#a89567]"
                                        >
                                            Correction Reason
                                        </label>

                                        <input
                                            id="reason-{{ $member->id }}"
                                            name="correction_reason"
                                            required
                                            value="{{ old('correction_reason', $row?->correction_reason) }}"
                                            placeholder="Enter reason when correcting attendance"
                                            class="w-full rounded-xl border-[#c8a24a]/30 bg-black px-3 py-3 text-sm text-[#fff9ea] placeholder:text-[#655d4c] outline-none focus:border-[#d5a93b] focus:ring-1 focus:ring-[#d5a93b]"
                                        >
                                    </div>

                                    {{-- Notes --}}
                                    <div class="sm:col-span-2">
                                        <label
                                            for="notes-{{ $member->id }}"
                                            class="mb-2 block text-xs font-semibold uppercase tracking-wider text-[#a89567]"
                                        >
                                            Notes
                                        </label>

                                        <textarea
                                            id="notes-{{ $member->id }}"
                                            name="notes"
                                            rows="2"
                                            placeholder="Optional attendance notes"
                                            class="w-full resize-none rounded-xl border-[#c8a24a]/30 bg-black px-3 py-3 text-sm text-[#fff9ea] placeholder:text-[#655d4c] outline-none focus:border-[#d5a93b] focus:ring-1 focus:ring-[#d5a93b]"
                                        >{{ $row?->notes }}</textarea>
                                    </div>
                                </div>

                                {{-- Action footer --}}
                                <div class="mt-5 flex flex-col gap-3 border-t border-[#c8a24a]/10 pt-5 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="text-xs text-[#756b58]">
                                        Attendance for {{ \Illuminate\Support\Carbon::parse($date)->format('d M Y') }}
                                    </div>

                                    <button
                                        type="submit"
                                        class="inline-flex w-full items-center justify-center rounded-xl bg-[#d5a93b] px-6 py-3 text-sm font-semibold text-[#111] transition hover:bg-[#e4bd55] focus:outline-none focus:ring-2 focus:ring-[#d5a93b]/50 sm:w-auto"
                                    >
                                        Save Attendance
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                @endforeach
            </div>

            @if ($staff->isEmpty())
                <x-admin.card class="p-10 text-center">
                    <p class="font-serif text-xl text-[#f4d27a]">No staff found</p>
                    <p class="mt-2 text-sm text-[#a89567]">There are no staff members available for this date.</p>
                </x-admin.card>
            @endif
        </div>
    </div>
</x-app-layout>