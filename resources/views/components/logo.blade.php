@props([
    'size' => 36,
    'withText' => true,
    'image' => 'assets/brand.png',
])
<div {{ $attributes->class('flex items-center gap-2') }}>
    <img src="{{ asset($image) }}" alt="ZunMoto" width="{{ $size }}" height="{{ $size }}"
        class="object-contain" />
    @if ($withText)
        <span class="font-display text-lg font-bold tracking-tight">Zun<span class="text-primary">Moto</span></span>
    @endif
</div>
