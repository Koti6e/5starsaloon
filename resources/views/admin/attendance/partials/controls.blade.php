<div class="mt-4 grid gap-3">
    <select name="status" class="rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]">
        @foreach (['not_marked', 'present', 'absent', 'late', 'leave', 'weekly_off'] as $status)
            <option value="{{ $status }}" @selected(($row?->status ?? 'not_marked') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
        @endforeach
    </select>
    <div class="text-xs text-[#a89567]">{{ in_array($row?->source, ['automatic_login', 'automatic_logout'], true) ? 'Auto Marked' : ($row ? 'Updated by Admin' : 'Not marked') }}</div>
    <div class="grid grid-cols-2 gap-3">
        <input type="time" name="check_in_time" value="{{ $row?->check_in_time }}" class="rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]">
        <input type="time" name="check_out_time" value="{{ $row?->check_out_time }}" class="rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]">
    </div>
    <input name="correction_reason" required value="{{ $row?->correction_reason }}" placeholder="Correction reason" class="rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]">
    <input name="notes" value="{{ $row?->notes }}" placeholder="Notes" class="rounded-md border-[#c8a24a]/30 bg-black text-[#fff9ea]">
    <button class="rounded-md bg-[#d5a93b] px-3 py-2 text-sm font-semibold text-[#111]">Save Attendance</button>
</div>
