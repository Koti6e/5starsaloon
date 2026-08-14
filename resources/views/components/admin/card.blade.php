@props(['class' => ''])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-4 shadow-[var(--shadow-card)] sm:p-5 ' . $class]) }}>
    {{ $slot }}
</div>
