@props([])

@if (session('status') || session('success') || session('error') || $errors->any())
    <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
        <div class="space-y-2">
            @if (session('status'))
                <div class="rounded-md border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-3 text-sm text-[var(--app-text)]">{{ session('status') }}</div>
            @endif
            @if (session('success'))
                <div class="rounded-md border border-emerald-400/30 bg-emerald-500/10 p-3 text-sm text-emerald-200">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-md border border-red-700 bg-[#2b0b0b] p-3 text-sm text-red-300">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-3 text-sm text-[var(--app-text)]">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
@endif
