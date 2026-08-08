@props(['prefix' => null, 'suffix' => null])

<div {{ $attributes->merge(['class' => 'elite-input-group']) }}>
    @if($prefix)
        <div class="elite-prefix">{{ $prefix }}</div>
    @endif

    {{ $slot }}

    @if($suffix)
        <div class="elite-suffix">{{ $suffix }}</div>
    @endif
</div>
