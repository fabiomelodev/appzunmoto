@props(['icon', 'value', 'label'])
<div class="rounded-2xl border border-border/60 bg-card p-3 text-center transition hover:-translate-y-0.5 hover:border-primary/40">
    <div class="mx-auto mb-1.5 grid h-8 w-8 place-items-center rounded-lg bg-primary/15 text-primary">
        <x-ui.icon :name="$icon" class="h-4 w-4" />
    </div>
    <div class="font-display text-xl font-bold leading-none">{{ $value }}</div>
    <div class="mt-1 text-[10px] uppercase tracking-wide text-muted-foreground">{{ $label }}</div>
</div>
