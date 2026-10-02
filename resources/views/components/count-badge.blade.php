@props(['count' => 0])
{{-- WhatsApp-style unread counter; renders nothing at 0. --}}
@if ($count > 0)
    <span {{ $attributes->class('inline-grid h-5 min-w-5 place-items-center rounded-full bg-primary px-1.5 text-[10px] font-bold leading-none text-primary-foreground ring-2 ring-background') }}>{{ $count > 99 ? '99+' : $count }}</span>
@endif
