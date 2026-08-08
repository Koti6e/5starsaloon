@props(['class' => ''])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface-elevated)] p-4 shadow-sm shadow-black/10 sm:p-5 ' . $class]) }}>
    {{ $slot }}
</div>
