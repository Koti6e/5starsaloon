@props(['tone' => 'neutral'])

@php
    $toneClasses = match ($tone) {
        'success' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
        'warning' => 'border-amber-500/30 bg-amber-500/10 text-amber-800 dark:text-amber-200',
        'danger' => 'border-rose-500/30 bg-rose-500/10 text-rose-700 dark:text-rose-300',
        'info' => 'border-sky-500/30 bg-sky-500/10 text-sky-700 dark:text-sky-300',
        default => 'border-[var(--app-border)] bg-[var(--app-surface)] text-[var(--app-text)]',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold leading-4', $toneClasses]) }}>
    {{ $slot }}
</span>
