@props(['settings' => []])
@php
    $schedule = \App\Support\WorkingHours::schedule($settings);
    $today = now('Asia/Kolkata')->format('l');
    $status = \App\Support\WorkingHours::status(settings: $settings);
    $configured = collect($schedule)->contains(fn ($row) => $row['open']);
    $now = now('Asia/Kolkata');
    $previousDay = $now->copy()->subDay()->format('l');
    $previousHours = $schedule[$previousDay];
    $todayHours = $schedule[$today];
    $todayTime = $now->format('H:i');
    $withinTodayHours = $todayHours['open'] && ($todayHours['opens'] <= $todayHours['closes']
        ? $todayTime >= $todayHours['opens'] && $todayTime < $todayHours['closes']
        : $todayTime >= $todayHours['opens'] || $todayTime < $todayHours['closes']);
    $openFromPreviousDay = $status && ! $withinTodayHours && $previousHours['open'] && $previousHours['opens'] > $previousHours['closes'] && $todayTime < $previousHours['closes'];
@endphp
<section class="rounded-xl border border-[#c8a24a]/20 bg-[#11100d] p-5" aria-labelledby="opening-hours-title">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#a89567]">Visit us</p><h2 id="opening-hours-title" class="mt-1 font-serif text-2xl text-[#f4d27a]">Opening Hours</h2></div>
        @if ($configured)
            <span class="rounded-full px-3 py-1 text-sm font-semibold {{ $status ? 'bg-emerald-500/15 text-emerald-300' : 'bg-rose-500/15 text-rose-300' }}">{{ $status ? 'Open now' : 'Closed now' }}</span>
        @endif
    </div>
    <p class="mt-3 text-sm text-[#d8c8a3]">{{ $configured ? ($openFromPreviousDay ? 'Open from '.$previousDay.'’s late hours until '.\App\Support\WorkingHours::label($previousHours['closes']) : ($schedule[$today]['open'] ? 'Today · '.\App\Support\WorkingHours::label($schedule[$today]['opens']).' – '.\App\Support\WorkingHours::label($schedule[$today]['closes']) : 'Today · Closed')) : 'Hours have not been configured yet.' }}</p>
    <dl class="mt-4 divide-y divide-[#c8a24a]/10">
        @foreach ($schedule as $day => $row)
            <div class="flex justify-between gap-4 py-2 text-sm {{ $today === $day ? 'font-semibold text-[#f4d27a]' : 'text-[#d8c8a3]' }}" @if ($today === $day) aria-current="date" @endif>
                <dt>{{ $day }}{{ $today === $day ? ' · Today' : '' }}</dt>
                <dd>{{ $row['open'] ? \App\Support\WorkingHours::label($row['opens']).' – '.\App\Support\WorkingHours::label($row['closes']) : 'Closed' }}</dd>
            </div>
        @endforeach
    </dl>
</section>
