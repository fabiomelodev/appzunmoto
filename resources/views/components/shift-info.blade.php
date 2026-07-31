@props([
    'label',
    'value',
    'icon' => null,
    'highlight' => false,
])
<div class="rounded-xl border border-border bg-card p-3">
    <div class="text-[10px] uppercase tracking-wide text-muted-foreground">{{ $label }}</div>
    <div class="mt-0.5 flex items-center gap-1.5 font-display text-lg font-bold {{ $highlight ? 'text-primary' : '' }}">
        @if ($icon)
            <x-ui.icon :name="$icon" class="h-3.5 w-3.5" />
        @endif
        {{ $value }}
    </div>
</div>
