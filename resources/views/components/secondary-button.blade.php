<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex min-h-10 items-center justify-center rounded-lg border border-[var(--app-border)] bg-[var(--app-surface-elevated)] px-4 py-2 text-sm font-semibold text-[var(--app-text)] shadow-sm transition hover:border-[var(--accent-border)] hover:bg-[var(--app-primary-soft)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--app-focus)] disabled:cursor-not-allowed disabled:opacity-50']) }}>
    {{ $slot }}
</button>
