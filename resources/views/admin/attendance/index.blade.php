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

                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('admin.attendance.index', ['date' => \Illuminate\Support\Carbon::parse($date)->subDay()->toDateString(), 'month' => $month->format('Y-m')]) }}" class="rounded-xl border border-[var(--app-border)] px-4 py-3 text-sm text-[var(--app-text)]" aria-label="Previous attendance day">← Day</a>
                            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[var(--app-primary)] px-6 py-3 text-sm font-semibold text-[var(--on-accent)] transition focus:outline-none focus:ring-2 focus:ring-[var(--app-focus)]">Open Date</button>
                            @if ($date < now('Asia/Kolkata')->toDateString())
                                <a href="{{ route('admin.attendance.index', ['date' => \Illuminate\Support\Carbon::parse($date)->addDay()->toDateString(), 'month' => $month->format('Y-m')]) }}" class="rounded-xl border border-[var(--app-border)] px-4 py-3 text-sm text-[var(--app-text)]" aria-label="Next attendance day">Day →</a>
                            @endif
                        </div>
                    </div>
                </x-admin.card>
            </form>

            <section class="mb-6 rounded-xl border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-semibold text-[var(--app-text)]">Weekly Attendance</h2>
                        <p class="mt-1 text-sm text-[var(--app-text-muted)]">{{ $weekStart->format('d M') }} – {{ $weekEnd->format('d M Y') }}</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.attendance.index', ['date' => $weekStart->copy()->subWeek()->toDateString(), 'month' => $month->format('Y-m')]) }}" class="rounded-lg border border-[var(--app-border)] px-3 py-2 text-sm text-[var(--app-text)]">← Previous week</a>
                        @if ($weekEnd->toDateString() < now('Asia/Kolkata')->toDateString())
                            <a href="{{ route('admin.attendance.index', ['date' => $weekStart->copy()->addWeek()->toDateString(), 'month' => $month->format('Y-m')]) }}" class="rounded-lg border border-[var(--app-border)] px-3 py-2 text-sm text-[var(--app-text)]">Next week →</a>
                        @endif
                    </div>
                </div>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[900px] text-left text-sm">
                        <thead class="text-xs uppercase text-[var(--app-text-muted)]"><tr><th class="p-2">Staff</th>@foreach ($weekStart->copy()->daysUntil($weekEnd->copy()->addDay()) as $day)<th class="p-2 text-center">{{ $day->format('D d') }}</th>@endforeach<th class="p-2 text-right">Present</th><th class="p-2 text-right">Attendance</th></tr></thead>
                        <tbody class="divide-y divide-[var(--app-border)]">
                            @foreach ($staff as $member)
                                @php
                                    $summary = $weeklySummary[$member->id];
                                @endphp
                                <tr>
                                    <th class="p-2 font-medium text-[var(--app-text)]"><a class="underline" href="{{ route('admin.attendance.index', ['date' => $date, 'month' => $month->format('Y-m'), 'staff' => $member->id]) }}">{{ $member->name }}</a></th>
                                    @foreach ($summary['days'] as $day)
                                        @php
                                            $tone = in_array($day['status'], ['present', 'late'], true) ? 'bg-emerald-500' : (in_array($day['status'], ['absent', 'leave', 'half_day'], true) ? 'bg-amber-500' : 'bg-[var(--app-border)]');
                                        @endphp
                                        <td class="p-2 text-center"><a href="{{ route('admin.attendance.index', ['date' => $day['date'], 'month' => $month->format('Y-m')]) }}" class="inline-flex min-h-8 min-w-8 items-center justify-center rounded-lg hover:bg-[var(--app-bg)]" title="{{ $day['date'] }} · {{ str_replace('_', ' ', $day['status']) }}"><span class="h-2.5 w-2.5 rounded-full {{ $tone }}" aria-hidden="true"></span><span class="sr-only">{{ str_replace('_', ' ', $day['status']) }}</span></a></td>
                                    @endforeach
                                    <td class="p-2 text-right">{{ $summary['present'] }}/{{ $summary['working_days'] }}</td><td class="p-2 text-right font-semibold">{{ $summary['attendance'] }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-xs text-[var(--app-text-muted)]">Activity dots show recorded attendance. Gray indicates no record or a weekly day off.</p>
            </section>

            <section class="mb-6 rounded-xl border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-semibold text-[var(--app-text)]">Staff 1 and Staff 2 Login History</h2>
                        <p class="mt-1 text-sm text-[var(--app-text-muted)]">GPS result and submitted location for recent login attempts. Device-reported GPS can be spoofed.</p>
                    </div>
                    <span class="rounded-full border border-[var(--app-border)] px-3 py-1 text-xs font-semibold text-[var(--app-muted)]">{{ $loginAttempts->count() }} recent</span>
                </div>
                <div class="mt-4 space-y-2">
                    @forelse ($loginAttempts as $attempt)
                        <article class="rounded-lg border border-[var(--app-border)] p-4">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <p class="font-semibold text-[var(--app-text)]">{{ $attempt->staff?->name ?? 'Former staff' }} · {{ $attempt->attempted_at?->timezone('Asia/Kolkata')->format('d M Y, h:i:s A') }}</p>
                                    <p class="mt-1 text-sm text-[var(--app-muted)]">{{ $attempt->reason }}</p>
                                    <p class="mt-1 text-xs text-[var(--app-subtle)]">GPS: {{ $attempt->latitude ?? 'not provided' }}, {{ $attempt->longitude ?? 'not provided' }} · accuracy {{ $attempt->gps_accuracy_meters !== null ? number_format((float) $attempt->gps_accuracy_meters).' m' : 'unavailable' }} · distance {{ $attempt->distance_from_salon_meters !== null ? number_format((float) $attempt->distance_from_salon_meters).' m' : 'not calculated' }}</p>
                                </div>
                                <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $attempt->decision === 'verified' ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700' : 'border-red-500/30 bg-red-500/10 text-red-700' }}">{{ ucfirst($attempt->decision) }}</span>
                            </div>
                        </article>
                    @empty
                        <p class="rounded-lg border border-dashed border-[var(--app-border)] p-5 text-center text-sm text-[var(--app-muted)]">No GPS login attempts recorded yet.</p>
                    @endforelse
                </div>
                @if ($loginAttempts->hasPages())
                    <div class="mt-4">{{ $loginAttempts->links() }}</div>
                @endif
            </section>

            <section class="mb-6 rounded-xl border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-5">
                <div class="flex items-center justify-between gap-3"><div><h2 class="text-xl font-semibold text-[var(--app-text)]">Attendance Verification Review</h2><p class="mt-1 text-sm text-[var(--app-text-muted)]">GPS failures and unverified selfie/device submissions. Review decisions require an explanation.</p></div><span class="rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-700">{{ $verificationAttempts->count() }} pending</span></div>
                <div class="mt-4 space-y-3">
                    @forelse ($verificationAttempts as $attempt)
                        <article class="rounded-lg border border-[var(--app-border)] p-4">
                            <div class="flex flex-wrap items-start justify-between gap-2"><div><p class="font-semibold text-[var(--app-text)]">{{ $attempt->staff?->name ?? 'Former staff' }} · {{ $attempt->attempted_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</p><p class="mt-1 text-sm text-[var(--app-muted)]">{{ $attempt->reason }}</p><p class="mt-1 text-xs text-[var(--app-subtle)]">GPS: {{ $attempt->latitude ?? 'not provided' }}, {{ $attempt->longitude ?? 'not provided' }} · accuracy {{ $attempt->gps_accuracy_meters ? number_format($attempt->gps_accuracy_meters).' m' : 'unavailable' }} · distance {{ $attempt->distance_from_salon_meters ? number_format($attempt->distance_from_salon_meters).' m' : 'not calculated' }}</p></div>@if ($attempt->attendance_id)<a class="text-sm font-semibold text-[var(--app-primary)] underline" href="{{ route('admin.attendance.selfie', $attempt->attendance_id) }}">Review selfie</a>@endif</div>
                            <form method="POST" action="{{ route('admin.attendance.verification.review', $attempt) }}" class="mt-3 grid gap-2 sm:grid-cols-[1fr_auto_auto]">@csrf<label class="sr-only" for="review-reason-{{ $attempt->id }}">Review reason</label><input id="review-reason-{{ $attempt->id }}" name="review_reason" required maxlength="1000" placeholder="Required review reason" class="min-h-10 rounded-md border border-[var(--app-border)] bg-[var(--app-bg)] px-3 text-sm text-[var(--app-text)]"><button name="decision" value="approved" class="rounded-md bg-[var(--app-primary)] px-4 py-2 text-sm font-semibold text-[var(--on-accent)]">Approve exception</button><button name="decision" value="rejected" class="rounded-md border border-red-500/40 px-4 py-2 text-sm font-semibold text-red-700">Reject</button></form>
                        </article>
                    @empty
                        <p class="rounded-lg border border-dashed border-[var(--app-border)] p-5 text-center text-sm text-[var(--app-muted)]">No attendance verification reviews are waiting.</p>
                    @endforelse
                </div>
            </section>

            <section class="mb-6 rounded-xl border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-5">
                <h2 class="text-xl font-semibold text-[var(--app-text)]">Registered Staff Devices</h2><p class="mt-1 text-sm text-[var(--app-text-muted)]">Browser installation IDs assist review and do not establish physical device identity.</p>
                <div class="mt-4 space-y-2">@forelse ($attendanceDevices as $device)<div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-[var(--app-border)] p-3"><div><p class="font-semibold text-[var(--app-text)]">{{ $device->staff?->name ?? 'Former staff' }} · {{ ucfirst($device->status) }}</p><p class="text-xs text-[var(--app-muted)]">{{ $device->label ?: 'Attendance browser' }} · first seen {{ $device->first_seen_at?->timezone('Asia/Kolkata')->format('d M Y') }} · last seen {{ $device->last_seen_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</p></div><div class="flex gap-2">@if ($device->status !== 'approved')<form method="POST" action="{{ route('admin.attendance.devices.update', $device) }}" class="flex gap-2">@csrf @method('PATCH')<input type="hidden" name="action" value="approve"><input name="reason" required maxlength="1000" placeholder="Approval reason" aria-label="Device approval reason" class="w-36 rounded-md border border-[var(--app-border)] bg-[var(--app-bg)] px-2 text-xs"><button class="rounded-md bg-[var(--app-primary)] px-3 py-2 text-xs font-semibold text-[var(--on-accent)]">Approve</button></form>@endif @if ($device->status !== 'revoked')<form method="POST" action="{{ route('admin.attendance.devices.update', $device) }}" class="flex gap-2">@csrf @method('PATCH')<input type="hidden" name="action" value="revoke"><input name="reason" required maxlength="1000" placeholder="Revocation reason" aria-label="Device revocation reason" class="w-36 rounded-md border border-[var(--app-border)] bg-[var(--app-bg)] px-2 text-xs"><button class="rounded-md border border-red-500/40 px-3 py-2 text-xs font-semibold text-red-700">Revoke</button></form>@endif</div></div>@empty<p class="text-sm text-[var(--app-muted)]">No device registrations yet.</p>@endforelse</div>
            </section>

            <section class="mb-6 rounded-xl border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-5">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div><h2 class="text-xl font-semibold text-[var(--app-text)]">Monthly Summary</h2><p class="mt-1 text-sm text-[var(--app-text-muted)]">{{ $month->format('F Y') }} · totals include days elapsed in the current month.</p></div>
                    <form method="GET" class="flex flex-wrap items-end gap-2">
                        <input type="hidden" name="date" value="{{ $date }}">
                        <label class="text-sm text-[var(--app-text)]">Month<input type="month" name="month" value="{{ $month->format('Y-m') }}" class="mt-1 block rounded-md border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-text)]"></label>
                        <a href="{{ route('admin.attendance.index', ['month' => $month->copy()->subMonth()->format('Y-m'), 'date' => $date]) }}" class="rounded-md border border-[var(--app-border)] px-3 py-2 text-sm text-[var(--app-text)]">Previous</a>
                        <a href="{{ route('admin.attendance.index', ['month' => $month->copy()->addMonth()->format('Y-m'), 'date' => $date]) }}" class="rounded-md border border-[var(--app-border)] px-3 py-2 text-sm text-[var(--app-text)]">Next</a>
                        <button class="rounded-md bg-[var(--app-primary-strong)] px-4 py-2 text-sm font-semibold text-black">View</button>
                    </form>
                </div>
                <div class="mt-5 overflow-x-auto"><table class="w-full min-w-[760px] text-left text-sm"><thead class="text-xs uppercase text-[var(--app-text-muted)]"><tr><th class="p-2">Staff</th><th class="p-2">Working Days</th><th class="p-2">Present</th><th class="p-2">Absent</th><th class="p-2">Leave</th><th class="p-2">Half Day</th><th class="p-2">Attendance</th></tr></thead><tbody class="divide-y divide-[var(--app-border)]">
                    @foreach ($staff as $member)
                        @php
                            $summary = $monthlySummary[$member->id];
                        @endphp
                        <tr><th class="p-2 font-medium text-[var(--app-text)]"><a class="underline" href="{{ route('admin.attendance.index', ['month' => $month->format('Y-m'), 'date' => $date, 'staff' => $member->id]) }}">{{ $member->name }}</a></th><td class="p-2">{{ $summary['working_days'] }}</td><td class="p-2">{{ $summary['present'] }}</td><td class="p-2">{{ $summary['absent'] }}</td><td class="p-2">{{ $summary['leave'] }}</td><td class="p-2">{{ $summary['half_day'] }}</td><td class="p-2 font-semibold">{{ $summary['attendance'] }}%</td></tr>
                    @endforeach
                </tbody></table></div>
            </section>

            @if ($selectedStaff && isset($monthlySummary[$selectedStaff]))
                @php
                    $detailMember = $staff->firstWhere('id', $selectedStaff);
                @endphp
                <section class="mb-6 rounded-xl border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-5"><div class="flex justify-between gap-3"><h2 class="text-lg font-semibold text-[var(--app-text)]">{{ $detailMember->name }} · {{ $month->format('F Y') }}</h2><a class="text-sm underline" href="{{ route('admin.attendance.index', ['month' => $month->format('Y-m'), 'date' => $date]) }}">Close details</a></div><div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-7">@foreach ($monthlySummary[$selectedStaff]['daily'] as $day)<div class="rounded-md border border-[var(--app-border)] p-3 text-sm"><p class="font-semibold text-[var(--app-text)]">{{ \Illuminate\Support\Carbon::parse($day['date'])->format('D, M j') }}</p><p class="mt-1 capitalize text-[var(--app-text-muted)]">{{ str_replace('_', ' ', $day['status']) }}</p>@if ($day['row']?->check_in_time)<p class="mt-1 text-xs">{{ substr($day['row']->check_in_time, 0, 5) }}@if($day['row']->check_out_time) – {{ substr($day['row']->check_out_time, 0, 5) }}@endif</p>@endif</div>@endforeach</div></section>
            @endif

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
                            'half_day' => 'Half Day',
                            'weekly_off' => 'Weekly Off',
                            default => 'Not Marked',
                        };

                        $statusClasses = match ($status) {
                            'present' => 'border-emerald-400/20 bg-emerald-400/10 text-emerald-300',
                            'absent' => 'border-red-400/20 bg-red-400/10 text-red-300',
                            'late' => 'border-amber-400/20 bg-amber-400/10 text-amber-300',
                            'leave' => 'border-sky-400/20 bg-sky-400/10 text-sky-300',
                            'half_day' => 'border-violet-400/20 bg-violet-400/10 text-violet-300',
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
                        class="overflow-hidden rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface-elevated)] shadow-xl"
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
                                <p class="mb-3 text-xs font-semibold uppercase tracking-[0.16em] text-[var(--app-muted)]">
                                    Attendance Photo
                                </p>

                                @if ($row?->selfie_path)
                                    <a
                                        href="{{ route('admin.attendance.selfie', $row) }}"
                                        target="_blank"
                                        rel="noopener"
                                            class="group block overflow-hidden rounded-2xl border border-[var(--app-border)] bg-[var(--app-bg)]"
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

                                        <p class="text-xs font-medium text-[var(--app-muted)]">
                                            No attendance photo
                                        </p>
                                    </div>
                                @endif
                                @if ($row?->latitude !== null)
                                    <p class="mt-2 text-xs leading-5 text-[var(--app-muted)]">GPS {{ number_format($row->distance_from_salon_meters) }} m from salon · ±{{ number_format($row->gps_accuracy_meters) }} m</p>
                                @endif
                                <p class="mt-2 rounded-md border border-amber-500/30 bg-amber-500/10 px-2 py-1.5 text-xs font-semibold text-amber-800">{{ str_replace('_', ' ', ucfirst($row?->verification_status ?? 'not verified')) }}{{ $row?->verification_reason ? ' · '.$row->verification_reason : '' }}</p>
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
                                            @foreach (['not_marked', 'present', 'absent', 'late', 'leave', 'half_day', 'weekly_off'] as $statusOption)
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

                                    <div class="sm:col-span-2">
                                        <label for="verification-status-{{ $member->id }}" class="mb-2 block text-xs font-semibold uppercase tracking-wider text-[var(--app-muted)]">Verification Result</label>
                                        <select id="verification-status-{{ $member->id }}" name="verification_status" class="w-full rounded-xl border border-[var(--app-border)] bg-[var(--app-bg)] px-3 py-3 text-sm text-[var(--app-text)]">
                                            @foreach (['review_required', 'verified', 'rejected'] as $verificationOption)
                                                <option value="{{ $verificationOption }}" @selected(($row?->verification_status ?? 'review_required') === $verificationOption)>{{ str_replace('_', ' ', ucfirst($verificationOption)) }}</option>
                                            @endforeach
                                        </select>
                                        <p class="mt-1 text-xs text-[var(--app-muted)]">Choosing Verified is an administrator override. Enter the reason below; the original result is retained in the audit history.</p>
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
