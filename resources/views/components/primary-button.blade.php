<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex min-h-10 items-center justify-center rounded-lg border border-transparent bg-[var(--app-primary)] px-4 py-2 text-sm font-semibold text-[var(--on-accent)] shadow-sm transition hover:opacity-90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--app-focus)] focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--app-surface)] disabled:cursor-not-allowed disabled:opacity-50']) }}>
    {{ $slot }}
</button>
