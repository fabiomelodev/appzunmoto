@props([
    'size' => 36,
    'withText' => true,
])
<div {{ $attributes->class('flex items-center gap-2') }}>
    <img src="{{ asset('assets/logo.png') }}" alt="MotoReserva" width="{{ $size }}" height="{{ $size }}"
        class="object-contain" />
    @if ($withText)
        <span class="font-display text-lg font-bold tracking-tight">Moto<span class="text-primary">Reserva</span></span>
    @endif
</div>
