@props(['icon' => 'bell', 'text' => ''])
<div class="rounded-2xl border border-dashed border-border p-8 text-center">
    <x-ui.icon :name="$icon" class="mx-auto h-7 w-7 text-muted-foreground" />
    <p class="mt-2 text-xs text-muted-foreground">{{ $text }}</p>
</div>
