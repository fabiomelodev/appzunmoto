{{--
    Exposes the Carto API key at runtime (window.__CARTO_API_KEY__) so the map
    tiles work without a rebuild when the key is added/rotated. Carto now
    requires a (free-tier) key on basemaps.cartocdn.com — without it the
    tiles render an "API KEY REQUIRED" watermark instead of the map.
--}}
@if ($key = config('services.carto.key'))
    <script>
        window.__CARTO_API_KEY__ = @json($key);
    </script>
@endif
