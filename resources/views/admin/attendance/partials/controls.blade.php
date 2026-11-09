<div class="mt-4 grid gap-3">
    <select name="status" class="rounded-md border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-text)]">
        @foreach (['not_marked', 'present', 'absent', 'late', 'leave', 'weekly_off'] as $status)
            <option value="{{ $status }}" @selected(($row?->status ?? 'not_marked') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
        @endforeach
    </select>
    <div class="text-xs text-[var(--app-subtle)]">{{ in_array($row?->source, ['automatic_login', 'automatic_logout'], true) ? 'Auto Marked' : ($row ? 'Updated by Admin' : 'Not marked') }}</div>
    @if ($row?->selfie_path)
        <a href="{{ route('admin.attendance.selfie', $row) }}" target="_blank" rel="noopener" class="rounded-md border border-[var(--app-border)] px-3 py-2 text-center text-xs font-semibold text-[var(--app-primary)]">View captured photo</a>
    @endif
    <div class="grid grid-cols-2 gap-3">
        <input type="time" name="check_in_time" value="{{ $row?->check_in_time }}" class="rounded-md border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-text)]">
        <input type="time" name="check_out_time" value="{{ $row?->check_out_time }}" class="rounded-md border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-text)]">
    </div>
    <input name="correction_reason" required value="{{ $row?->correction_reason }}" placeholder="Correction reason" class="rounded-md border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-text)]">
    <input name="notes" value="{{ $row?->notes }}" placeholder="Notes" class="rounded-md border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-text)]">
    <button class="rounded-md bg-[var(--app-primary)] px-3 py-2 text-sm font-semibold text-[var(--on-accent)]">Save Attendance</button>
</div>
