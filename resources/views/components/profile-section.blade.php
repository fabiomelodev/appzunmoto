@props(['title'])
<div class="space-y-3">
    <h2 class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">{{ $title }}</h2>
    <div class="space-y-3">
        {{ $slot }}
    </div>
</div>
