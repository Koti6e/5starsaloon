@props([
    'period',
    'action',
    'preserve' => [],
    'showNavigation' => true,
])

<div class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface)] p-3 shadow-[var(--shadow-card)] sm:p-4"
     x-data="{ mode: @js($period->mode) }">
    <form method="GET" action="{{ $action }}" class="flex flex-wrap items-end gap-3">
        @foreach ($preserve as $key => $value)
            @if (is_scalar($value) && $value !== '')
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach

        <div class="min-w-36">
            <label for="reporting-period" class="mb-1 block text-xs font-semibold text-[var(--app-muted)]">Period</label>
            <select id="reporting-period" name="period" x-model="mode" class="min-h-11 w-full rounded-xl border border-[var(--app-border)] bg-[var(--app-bg)] px-3 text-sm text-[var(--app-text)] focus:border-[var(--app-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--app-focus)]">
                <option value="daily">Day</option>
                <option value="weekly">Week</option>
                <option value="monthly">Month</option>
                <option value="custom">Custom range</option>
            </select>
        </div>

        <div x-show="mode === 'daily'" x-cloak class="min-w-40">
            <label for="reporting-day" class="mb-1 block text-xs font-semibold text-[var(--app-muted)]">Business date</label>
            <input id="reporting-day" name="date" type="date" value="{{ $period->start->toDateString() }}" :disabled="mode !== 'daily'" class="min-h-11 w-full rounded-xl border border-[var(--app-border)] bg-[var(--app-bg)] px-3 text-sm text-[var(--app-text)]">
        </div>
        <div x-show="mode === 'weekly'" x-cloak class="min-w-40">
            <label for="reporting-week" class="mb-1 block text-xs font-semibold text-[var(--app-muted)]">Date in week</label>
            <input id="reporting-week" name="week" type="date" value="{{ $period->start->toDateString() }}" :disabled="mode !== 'weekly'" class="min-h-11 w-full rounded-xl border border-[var(--app-border)] bg-[var(--app-bg)] px-3 text-sm text-[var(--app-text)]">
        </div>
        <div x-show="mode === 'monthly'" x-cloak class="min-w-40">
            <label for="reporting-month" class="mb-1 block text-xs font-semibold text-[var(--app-muted)]">Month</label>
            <input id="reporting-month" name="month" type="month" value="{{ $period->start->format('Y-m') }}" :disabled="mode !== 'monthly'" class="min-h-11 w-full rounded-xl border border-[var(--app-border)] bg-[var(--app-bg)] px-3 text-sm text-[var(--app-text)]">
        </div>
        <div x-show="mode === 'custom'" x-cloak class="flex flex-wrap gap-3">
            <div>
                <label for="reporting-start" class="mb-1 block text-xs font-semibold text-[var(--app-muted)]">From</label>
                <input id="reporting-start" name="start_date" type="date" value="{{ $period->start->toDateString() }}" :disabled="mode !== 'custom'" :required="mode === 'custom'" class="min-h-11 rounded-xl border border-[var(--app-border)] bg-[var(--app-bg)] px-3 text-sm text-[var(--app-text)]">
            </div>
            <div>
                <label for="reporting-end" class="mb-1 block text-xs font-semibold text-[var(--app-muted)]">To</label>
                <input id="reporting-end" name="end_date" type="date" value="{{ $period->endExclusive->subDay()->toDateString() }}" :disabled="mode !== 'custom'" :required="mode === 'custom'" class="min-h-11 rounded-xl border border-[var(--app-border)] bg-[var(--app-bg)] px-3 text-sm text-[var(--app-text)]">
            </div>
        </div>

        <button type="submit" class="min-h-11 rounded-xl bg-[var(--app-primary)] px-4 text-sm font-semibold text-[var(--on-accent)] transition hover:opacity-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--app-primary)]">Apply</button>
        <span class="ml-auto text-sm font-medium text-[var(--app-muted)]">{{ $period->label() }}</span>
    </form>

    @if ($showNavigation)
        <nav class="mt-3 flex items-center justify-between border-t border-[var(--app-border)] pt-3" aria-label="Reporting period navigation">
            <a href="{{ $action.'?'.http_build_query([...$preserve, ...$period->previous()->toQuery()]) }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-[var(--app-primary)] hover:bg-[var(--app-primary-soft)]">← Previous</a>
            <a href="{{ $action.'?'.http_build_query([...$preserve, ...$period->next()->toQuery()]) }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-[var(--app-primary)] hover:bg-[var(--app-primary-soft)]">Next →</a>
        </nav>
    @endif
</div>
