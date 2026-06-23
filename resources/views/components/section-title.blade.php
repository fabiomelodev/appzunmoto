@props(['count' => null])
<div class="flex items-center gap-2">
    <h2 class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">{{ $slot }}</h2>
    @if (! is_null($count))
        <span class="rounded-full bg-surface px-1.5 py-0.5 text-[10px] font-bold text-muted-foreground">{{ $count }}</span>
    @endif
    <div class="h-px flex-1 bg-border/60"></div>
</div>
