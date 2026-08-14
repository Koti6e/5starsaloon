<button
    type="button"
    x-data="{
        themes: ['emerald', 'sapphire', 'crimson', 'gold', 'pearl', 'obsidian'],
        theme: document.documentElement.dataset.theme || 'emerald',
        apply(value) {
            this.theme = this.themes.includes(value) ? value : 'emerald';
            localStorage.setItem('salon-theme', this.theme);
            document.documentElement.dataset.theme = this.theme;
            document.documentElement.classList.toggle('theme-dark', this.theme !== 'pearl');
            document.documentElement.classList.toggle('theme-light', this.theme === 'pearl');
        },
        next() {
            this.apply(this.themes[(this.themes.indexOf(this.theme) + 1) % this.themes.length]);
        },
    }"
    x-init="apply(theme)"
    @click="next()"
    :aria-label="`Theme: ${theme}. Switch theme`"
    :title="`Theme: ${theme}. Switch theme`"
    class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-[var(--app-border)] text-[var(--app-primary)] transition hover:border-[var(--app-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--app-primary)]"
>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <circle cx="12" cy="12" r="3"/>
        <path d="M12 2v3M12 19v3M4.9 4.9 7 7M17 17l2.1 2.1M2 12h3M19 12h3M4.9 19.1 7 17M17 7l2.1-2.1"/>
    </svg>
</button>
