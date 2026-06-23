<!DOCTYPE html>
<html lang="pt-BR" class="dark">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <title>{{ $title ?? 'MotoReserva' }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any" />
    {{-- Apply the saved theme before paint to avoid a flash --}}
    <script>
        (function () {
            try { document.documentElement.className = localStorage.getItem('mr-theme') || 'dark'; }
            catch (e) { document.documentElement.className = 'dark'; }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="antialiased">
    <div class="app-shell min-h-dvh">
        {{ $slot }}
    </div>
</body>

</html>
