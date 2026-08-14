<button
    type="button"
    x-data="{ theme: localStorage.getItem('salon-theme') || document.documentElement.dataset.defaultTheme || 'light' }"
    x-init="$watch('theme', value => { localStorage.setItem('salon-theme', value); document.documentElement.classList.toggle('theme-dark', value === 'dark'); document.documentElement.classList.toggle('theme-light', value !== 'dark'); })"
    @click="theme = theme === 'dark' ? 'light' : 'dark'"
    :aria-label="theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
    :title="theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
    class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-[var(--app-border)] text-[var(--app-primary)] transition hover:border-[var(--app-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--app-primary)]"
>
    <svg x-show="theme !== 'dark'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path d="M21 12.8A8.5 8.5 0 1 1 11.2 3 7 7 0 0 0 21 12.8Z"/>
    </svg>
    <svg x-show="theme === 'dark'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <circle cx="12" cy="12" r="4"/>
        <path d="M12 2v2m0 16v2m10-10h-2M4 12H2m17.07-7.07-1.41 1.41M6.34 17.66l-1.41 1.41m14.14 0-1.41-1.41M6.34 6.34 4.93 4.93"/>
    </svg>
</button>
