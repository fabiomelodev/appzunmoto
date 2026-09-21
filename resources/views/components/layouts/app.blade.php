<!DOCTYPE html>
<html lang="pt-BR" class="dark">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <title>{{ $title ?? 'ZunMoto' }}</title>
    <link rel="icon" href="{{ asset('assets/favicon.png') }}" type="image/png" />
    <x-pwa-meta />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        (function () {
            try { document.documentElement.className = localStorage.getItem('mr-theme') || 'dark'; }
            catch (e) { document.documentElement.className = 'dark'; }
        })();
    </script>
    <x-broadcast-config />
    <x-webpush-config />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="antialiased lg:pl-64">
    <x-sidebar-nav />

    <div class="app-shell min-h-dvh bg-background">
        {{ $slot }}
        <x-bottom-nav />
    </div>

    {{-- Keeps the private echo channel subscribed on every authenticated page
    (not just Vagas/Notificações, which already listen for their own reasons)
    so the floating toast below can fire regardless of where the user is. --}}
    <livewire:notification-listener />

    {{-- Toasts (replaces sonner). Livewire: $this->dispatch('toast', message: '…', type: 'success'). --}}
    {{-- lg: the app-shell centering below assumes the full viewport width; at
    lg+ a fixed sidebar (w-64) eats the left 16rem, so the anchor point shifts
    right by half that (8rem) to stay centered in the remaining space instead
    of drifting under the sidebar. --}}
    <div x-data="{ toasts: [] }"
        @toast.window="
            const id = (window.__toastId = (window.__toastId || 0) + 1);
            toasts.push({ id, message: $event.detail.message, type: $event.detail.type || 'success' });
            setTimeout(() => { toasts = toasts.filter(t => t.id !== id); }, 3000);
        "
        class="app-shell pointer-events-none fixed left-1/2 top-4 z-[60] flex -translate-x-1/2 flex-col items-center gap-2 px-4 lg:left-[calc(50%+8rem)]">
        <template x-for="t in toasts" :key="t.id">
            <div x-transition
                class="pointer-events-auto rounded-xl border border-border bg-surface-elevated px-4 py-2.5 text-sm font-medium text-foreground shadow-lg"
                x-text="t.message"></div>
        </template>
    </div>

    {{-- Floating notification toast (bottom-right). Fired by
    NotificationListener::onNotification() — a realtime `notification.received`
    event on the user's private channel (see app/Events/NotificationReceived.php)
    — separate from the bell badge, which updates on its own in the background.
    bottom-24: clears the mobile bottom-nav (h-20 + margin); lg:bottom-6
    matches the floating action bar on the shift-detail page since there's no
    bottom-nav to clear on desktop (sidebar instead). --}}
    <div x-data="{ toasts: [] }"
        @notification-toast.window="
            const id = (window.__notifToastId = (window.__notifToastId || 0) + 1);
            toasts.push({ id, title: $event.detail.title, description: $event.detail.description, url: $event.detail.url });
            setTimeout(() => { toasts = toasts.filter(t => t.id !== id); }, 8000);
        "
        class="pointer-events-none fixed bottom-24 right-4 z-[60] flex w-[calc(100%-2rem)] max-w-sm flex-col gap-2 lg:bottom-6">
        <template x-for="t in toasts" :key="t.id">
            <div x-transition
                class="pointer-events-auto overflow-hidden rounded-2xl border border-border bg-surface-elevated shadow-xl">
                <div class="flex items-start gap-3 p-4">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-primary/15 text-primary">
                        <x-ui.icon name="bell" class="h-4 w-4" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-foreground" x-text="t.title"></p>
                        <p class="mt-0.5 line-clamp-2 text-xs text-muted-foreground" x-text="t.description"></p>
                    </div>
                    <button type="button" x-on:click="toasts = toasts.filter(x => x.id !== t.id)"
                        aria-label="Fechar" class="tap shrink-0 text-muted-foreground hover:text-foreground">
                        <x-ui.icon name="x" class="h-4 w-4" />
                    </button>
                </div>
                <template x-if="t.url">
                    <a :href="t.url" wire:navigate x-on:click="toasts = toasts.filter(x => x.id !== t.id)"
                        class="tap block border-t border-border px-4 py-2.5 text-center text-sm font-semibold text-primary transition hover:bg-surface">
                        Ver agora
                    </a>
                </template>
            </div>
        </template>
    </div>

    @livewireScripts
</body>

</html>
