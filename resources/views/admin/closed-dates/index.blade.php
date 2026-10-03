<x-app-layout>
    <div class="mx-auto max-w-4xl space-y-6 px-4 py-6 sm:px-6">
        <div>
            <h1 class="text-2xl font-bold text-[var(--app-text)]">Salon Closed Dates</h1>
            <p class="mt-1 text-sm text-[var(--app-muted)]">Active dates are unavailable for online appointment booking.</p>
        </div>
        @if (session('status'))<p class="rounded-lg bg-emerald-500/10 p-3 text-sm text-emerald-300">{{ session('status') }}</p>@endif
        @if ($errors->any())<ul class="rounded-lg bg-red-500/10 p-3 text-sm text-red-200">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
        <form method="POST" action="{{ route('admin.closed-dates.store') }}" class="grid gap-3 rounded-xl border border-[var(--app-border)] bg-[var(--app-surface)] p-4 sm:grid-cols-[1fr_2fr_auto]">
            @csrf
            <label class="text-sm">Date<input required type="date" name="date" class="mt-1 w-full rounded-lg border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-text)]"></label>
            <label class="text-sm">Name or reason (optional)<input type="text" name="name" maxlength="120" placeholder="Diwali Holiday" class="mt-1 w-full rounded-lg border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-text)]"></label>
            <button class="self-end rounded-lg bg-[var(--app-primary-strong)] px-4 py-2 font-semibold text-black">Add closure</button>
        </form>
        <div class="overflow-hidden rounded-xl border border-[var(--app-border)] bg-[var(--app-surface)]">
            @forelse ($closedDates as $closedDate)
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[var(--app-border)] p-4 last:border-0">
                    <div><p class="font-semibold text-[var(--app-text)]">{{ $closedDate->date->format('d M Y') }} · {{ $closedDate->name ?: 'Salon closed' }}</p><p class="text-xs text-[var(--app-muted)]">{{ $closedDate->is_active ? 'Active closure' : 'Inactive' }}</p></div>
                    <div class="flex gap-2">
                        <form method="POST" action="{{ route('admin.closed-dates.update', $closedDate) }}">@csrf @method('PATCH')<input type="hidden" name="is_active" value="{{ $closedDate->is_active ? 0 : 1 }}"><button class="rounded-lg border border-[var(--app-border)] px-3 py-2 text-sm text-[var(--app-text)]">{{ $closedDate->is_active ? 'Deactivate' : 'Activate' }}</button></form>
                        <form method="POST" action="{{ route('admin.closed-dates.destroy', $closedDate) }}" onsubmit="return confirm('Remove this closure date?')">@csrf @method('DELETE')<button class="rounded-lg border border-red-400/30 px-3 py-2 text-sm text-red-300">Remove</button></form>
                    </div>
                </div>
            @empty
                <p class="p-4 text-sm text-[var(--app-muted)]">No date-specific closures configured.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
