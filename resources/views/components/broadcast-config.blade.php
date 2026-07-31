{{--
    Exposes the broadcasting client params at runtime (window.__BROADCAST__) so
    the pre-built JS bundle connects to the right service in every environment —
    driven only by the server's .env, no `npm run build` on deploy. Supports
    Reverb (self-hosted) and Pusher (cloud). Echo (resources/js/app.js) reads
    this and falls back to the build-time VITE_* values for local dev.
--}}
@php
    $driver = config('broadcasting.default');
    $conn = config('broadcasting.connections.'.$driver, []);
@endphp
@if (in_array($driver, ['reverb', 'pusher'], true) && ! empty($conn['key']))
    <script>
        window.__BROADCAST__ = {
            broadcaster: @json($driver),
            key: @json($conn['key']),
            cluster: @json($conn['options']['cluster'] ?? null),
            host: @json($conn['options']['host'] ?? request()->getHost()),
            port: {{ (int) ($conn['options']['port'] ?? 443) }},
            scheme: @json($conn['options']['scheme'] ?? 'https'),
        };
    </script>
@endif
