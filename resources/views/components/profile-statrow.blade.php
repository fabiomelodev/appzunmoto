@props(['label', 'value', 'highlight' => false])
<div class="flex items-center justify-between rounded-xl border border-border/60 bg-surface px-4 py-3">
    <span class="text-xs text-muted-foreground">{{ $label }}</span>
    <span class="font-display text-base font-bold {{ $highlight ? 'text-primary' : 'text-foreground' }}">{{ $value }}</span>
</div>
