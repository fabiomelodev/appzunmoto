<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'MotoReserva')</title>

    {{-- Tailwind CSS CDN (em produção: npm install + vite) --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: 'oklch(0.75 0.18 55)',
                        background: '#0d0d0d',
                        surface: '#161616',
                        'surface-elevated': '#1e1e1e',
                        card: '#1a1a1a',
                        border: '#2a2a2a',
                        foreground: '#f5f5f5',
                        'muted-foreground': '#737373',
                        success: '#22c55e',
                    },
                    fontFamily: {
                        display: ['Inter', 'system-ui', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style>
        :root { color-scheme: dark; }
        body { background: #0d0d0d; color: #f5f5f5; font-family: Inter, system-ui, sans-serif; }
        .tap { transition: transform 0.1s; }
        .tap:active { transform: scale(0.97); }
        .glow-orange { box-shadow: 0 0 16px 0 rgba(251,146,60,0.35); }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        /* Cores semânticas usadas no React original */
        .text-primary { color: #f97316; }
        .bg-primary { background-color: #f97316; }
        .border-primary { border-color: #f97316; }
        .bg-primary\/10 { background-color: rgba(249,115,22,0.10); }
        .bg-primary\/15 { background-color: rgba(249,115,22,0.15); }
        .bg-primary\/25 { background-color: rgba(249,115,22,0.25); }
        .border-primary\/40 { border-color: rgba(249,115,22,0.40); }
        .text-primary-foreground { color: #fff; }
        .bg-muted { background-color: #262626; }
        .bg-secondary { background-color: #1f1f1f; }
        .text-secondary-foreground { color: #a3a3a3; }
        .bg-card { background-color: #1a1a1a; }
        .bg-surface { background-color: #161616; }
        .bg-surface-elevated { background-color: #1e1e1e; }
        .border-border { border-color: #2a2a2a; }
        .border-border\/60 { border-color: rgba(42,42,42,0.60); }
        .text-foreground { color: #f5f5f5; }
        .text-muted-foreground { color: #737373; }
        .text-destructive { color: #ef4444; }
        .bg-destructive\/10 { background-color: rgba(239,68,68,0.10); }
        .border-destructive\/40 { border-color: rgba(239,68,68,0.40); }
        .text-\[oklch\(0\.78_0\.16_155\)\] { color: #4ade80; }
        .bg-success\/15 { background-color: rgba(34,197,94,0.15); }
        /* Input padrão */
        input, textarea, select {
            background: #1e1e1e;
            border: 1px solid #2a2a2a;
            color: #f5f5f5;
            border-radius: 0.75rem;
            padding: 0.5rem 0.75rem;
            width: 100%;
            outline: none;
            font-size: 0.875rem;
        }
        input:focus, textarea:focus, select:focus {
            border-color: rgba(249,115,22,0.6);
            box-shadow: 0 0 0 2px rgba(249,115,22,0.15);
        }
        /* Bottom nav safe area */
        .pb-nav { padding-bottom: 5.5rem; }
    </style>

    @livewireStyles
    @stack('styles')
</head>
<body class="min-h-screen bg-[#0d0d0d]">

    @yield('content')

    {{-- Bottom Navigation (aparece em todas as páginas autenticadas) --}}
    @auth
    @include('components.bottom-nav')
    @endauth

    @livewireScripts
    @stack('scripts')
</body>
</html>
