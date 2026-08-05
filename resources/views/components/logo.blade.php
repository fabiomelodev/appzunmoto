@props([
    'size' => 36,
    'withText' => true,
])
<div {{ $attributes->class('flex items-center gap-2') }}>
    <img src="{{ asset('assets/logo.png') }}" alt="GiroMoto" width="{{ $size }}" height="{{ $size }}"
        class="object-contain" />
    @if ($withText)
        <span class="font-display text-lg font-bold tracking-tight">Giro<span class="text-primary">Moto</span></span>
    @endif
</div>
