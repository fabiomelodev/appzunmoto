<x-layouts.app :title="$title">
    <header class="sticky top-0 z-30 flex items-center gap-3 border-b border-border bg-surface/80 px-4 py-3 backdrop-blur-xl">
        <x-logo :size="28" />
        <span class="font-display text-base font-semibold">{{ $title }}</span>
    </header>

    <div class="flex flex-col items-center gap-3 px-6 py-16 text-center">
        <p class="font-display text-lg font-bold">{{ $title }}</p>
        <p class="max-w-xs text-sm text-muted-foreground">Esta tela será reconstruída nas próximas fases.</p>

        <form method="POST" action="{{ route('logout') }}" class="mt-6">
            @csrf
            <x-ui.button type="submit" variant="outline" size="sm">
                <x-ui.icon name="log-out" class="mr-1.5 h-4 w-4" /> Sair
            </x-ui.button>
        </form>
    </div>
</x-layouts.app>
