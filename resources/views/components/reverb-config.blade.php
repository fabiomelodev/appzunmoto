{{--
    Exposes the Reverb client connection params at runtime so the (pre-built,
    committed) JS bundle connects to the RIGHT server in every environment —
    no `npm run build` needed on deploy. Echo reads window.__REVERB__ first and
    falls back to the build-time VITE_REVERB_* values.
--}}
@php
    $reverbKey = config('broadcasting.connections.reverb.key');
    $reverbHost = config('broadcasting.connections.reverb.options.host') ?: request()->getHost();
    $reverbPort = (int) config('broadcasting.connections.reverb.options.port', 443);
    $reverbScheme = config('broadcasting.connections.reverb.options.scheme', 'https');
@endphp
@if ($reverbKey)
    <script>
        window.__REVERB__ = {
            key: @json($reverbKey),
            host: @json($reverbHost),
            port: {{ $reverbPort }},
            scheme: @json($reverbScheme),
        };
    </script>
@endif
