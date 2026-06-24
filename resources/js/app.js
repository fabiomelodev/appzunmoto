import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// ── Realtime (Laravel Reverb) ────────────────────────────────────
// Connection params come from the server at runtime (window.__REVERB__,
// injected by <x-reverb-config>) so the pre-built bundle connects to the right
// server in every environment — no rebuild on deploy. Falls back to the
// build-time VITE_REVERB_* values for local dev. Guarded: no key → no Echo, so
// the rest of the app keeps working (Livewire round-trips still update the UI).
{
    const rt = window.__REVERB__ || {};
    const key = rt.key || import.meta.env.VITE_REVERB_APP_KEY;
    if (key) {
        const scheme = rt.scheme || import.meta.env.VITE_REVERB_SCHEME || 'http';
        const port = Number(rt.port || import.meta.env.VITE_REVERB_PORT || 8080);
        window.Pusher = Pusher;
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key,
            wsHost: rt.host || import.meta.env.VITE_REVERB_HOST,
            wsPort: port,
            wssPort: port,
            forceTLS: scheme === 'https',
            enabledTransports: ['ws', 'wss'],
        });
    }
}

// Input masks (ported from the React app). Exposed globally for Alpine x-on:input.
window.maskCPF = function (v) {
    const d = String(v).replace(/\D/g, '').slice(0, 11);
    return d
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
};

window.maskDate = function (v) {
    const d = String(v).replace(/\D/g, '').slice(0, 8);
    return [d.slice(0, 2), d.slice(2, 4), d.slice(4, 8)].filter(Boolean).join('/');
};

window.maskPhone = function (v) {
    const d = String(v).replace(/\D/g, '').slice(0, 11);
    if (d.length <= 10) return d.replace(/(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3').replace(/-$/, '');
    return d.replace(/(\d{2})(\d{5})(\d{0,4}).*/, '($1) $2-$3').replace(/-$/, '');
};

window.maskCep = function (v) {
    const d = String(v).replace(/\D/g, '').slice(0, 8);
    return d.length > 5 ? `${d.slice(0, 5)}-${d.slice(5)}` : d;
};

// ── Geolocation + distance (for shift cards) ─────────────────────
// Reactive Alpine store holding the user's coords (filled on demand).
document.addEventListener('alpine:init', () => {
    window.Alpine.store('geo', { coords: null });
});

// Requests the browser location once and writes it to the store.
window.mrRequestGeo = function () {
    if (!navigator.geolocation || !window.Alpine) return;
    navigator.geolocation.getCurrentPosition(
        (p) => {
            window.Alpine.store('geo').coords = { lat: p.coords.latitude, lng: p.coords.longitude };
        },
        () => {},
        { enableHighAccuracy: false, timeout: 8000 },
    );
};

// Haversine + formatting (ported from the React lib/geo.ts). Returns '' when
// the user location is unknown or the shift has no coordinates.
window.mrDistance = function (geo, lat, lng) {
    const c = geo && geo.coords;
    if (!c || !lat || !lng) return '';

    const toRad = (v) => (v * Math.PI) / 180;
    const dLat = toRad(lat - c.lat);
    const dLng = toRad(lng - c.lng);
    const a =
        Math.sin(dLat / 2) ** 2 +
        Math.cos(toRad(c.lat)) * Math.cos(toRad(lat)) * Math.sin(dLng / 2) ** 2;
    const km = 6371 * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    if (km < 1) return `${Math.round(km * 1000)} m`;
    return `${km.toFixed(km < 10 ? 1 : 0).replace('.', ',')} km`;
};
