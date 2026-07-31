@props(['label', 'value'])
<div class="flex items-center justify-between border-b border-border/60 py-2.5 last:border-0">
    <span class="text-xs text-muted-foreground">{{ $label }}</span>
    <div class="flex items-center gap-3">
        <span class="text-sm font-medium text-foreground">{{ $value }}</span>
        {{ $slot }}
    </div>
</div>
