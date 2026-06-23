@props(['icon', 'label'])
<span class="flex items-center gap-1 rounded-full border border-border/60 bg-surface px-2.5 py-1 text-[10px] font-semibold text-foreground">
    <x-ui.icon :name="$icon" class="h-3 w-3 text-primary" />{{ $label }}
</span>
