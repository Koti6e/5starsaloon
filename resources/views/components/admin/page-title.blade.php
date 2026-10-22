@props([])

@if (trim($slot->toHtml()))
    <header class="border-b border-[var(--app-border)] bg-[var(--app-surface)]/80 shadow-sm backdrop-blur-sm">
        <div class="mx-auto max-w-7xl px-3 py-2 sm:px-6 sm:py-3 lg:px-8">
            {{ $slot }}
        </div>
    </header>
@endif
