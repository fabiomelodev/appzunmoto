@props(['label', 'description', 'model'])
<div class="flex items-center justify-between gap-3 border-b border-border/60 py-3 last:border-0">
    <div class="min-w-0">
        <div class="text-sm font-medium text-foreground">{{ $label }}</div>
        <div class="text-xs text-muted-foreground">{{ $description }}</div>
    </div>
    <x-ui.switch wire:model.live="{{ $model }}" />
</div>
