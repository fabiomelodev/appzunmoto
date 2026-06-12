<!DOCTYPE html>
<html lang="pt-BR" class="dark">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'MotoReserva')</title>

    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <style type="tailwindcss">
        /* Ativa o Dark Mode baseado na classe 'dark' na tag <html> */
        @variant dark (&:where(.dark, .dark *));

        /* Injeta suas classes customizadas dentro do motor do Tailwind v4 */
        @theme {
            --color-primary: #f97316;
            --color-muted: #262626;
            --color-card: #1a1a1a;
            --color-surface: #161616;
            --color-border: #2a2a2a;
            --color-muted-foreground: #737373;
            --color-destructive: #ef4444;
        }
    </style>

    <style>
        :root {
            color-scheme: dark;
        }

        body {
            background: #0d0d0d;
            color: #f5f5f5;
            font-family: Inter, system-ui, sans-serif;
        }

        .tap {
            transition: transform 0.1s;
        }

        .tap:active {
            transform: scale(0.97);
        }

        .glow-orange {
            box-shadow: 0 0 16px 0 rgba(251, 146, 60, 0.35);
        }

        input,
        textarea,
        select {
            background: #1e1e1e;
            border: 1px solid #2a2a2a;
            color: #f5f5f5;
            border-radius: 0.75rem;
            padding: 0.5rem 0.75rem;
            width: 100%;
            outline: none;
            font-size: 0.875rem;
        }

        input:focus,
        textarea:focus {
            border-color: rgba(249, 115, 22, 0.6);
            box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.15);
        }
    </style>
    @livewireStyles
</head>

<body class="min-h-screen bg-[#0d0d0d]">
    @yield('content')
    @livewireScripts
</body>

</html>