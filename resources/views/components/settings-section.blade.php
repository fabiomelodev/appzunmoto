@props(['icon', 'title'])
<section>
    <div class="mb-2 flex items-center gap-2 px-1">
        <span class="grid h-7 w-7 place-items-center rounded-lg bg-accent text-accent-foreground">
            <x-ui.icon :name="$icon" class="h-4 w-4" />
        </span>
        <h2 class="text-sm font-semibold tracking-tight">{{ $title }}</h2>
    </div>
    <div class="rounded-2xl border border-border bg-card p-4 shadow-sm">
        {{ $slot }}
    </div>
</section>
