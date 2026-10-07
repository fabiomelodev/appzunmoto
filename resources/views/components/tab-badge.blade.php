@props(['count' => 0, 'active' => false])
{{-- Small counter inside a tab button; inverts on the (primary-coloured) active tab so it stays visible. --}}
@if ($count > 0)
    <span {{ $attributes->class(['ml-1 inline-grid h-4 min-w-4 place-items-center rounded-full px-1 align-middle text-[10px] font-bold leading-none', $active ? 'bg-primary-foreground text-primary' : 'bg-primary text-primary-foreground']) }}>{{ $count > 99 ? '99+' : $count }}</span>
@endif
