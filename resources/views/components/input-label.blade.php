@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-[var(--app-text)]']) }}>
    {{ $value ?? $slot }}
</label>
