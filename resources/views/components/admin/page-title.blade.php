@props([])

@if (trim($slot->toHtml()))
    <header class="border-b border-[var(--app-border)] bg-[var(--app-surface)]/85 shadow-sm backdrop-blur-sm">
        <div class="mx-auto max-w-screen-2xl px-3 py-3 sm:px-6 sm:py-4 lg:px-8">
            {{ $slot }}
        </div>
    </header>
@endif
