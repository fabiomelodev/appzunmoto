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

    @livewireScripts
</body>

</html>
