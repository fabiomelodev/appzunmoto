{{--
    Exposes the VAPID public key at runtime (window.__VAPID_PUBLIC_KEY__) so the
    browser can subscribe to push without a rebuild when the key changes.
--}}
@if ($key = config('webpush.vapid.public_key'))
    <script>
        window.__VAPID_PUBLIC_KEY__ = @json($key);
    </script>
@endif
