import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// ── Theme (dark/light/urbano) ─────────────────────────────────────
// The <html class="dark"> in both layouts is hardcoded server-side; the
// inline <script> in <head> only overrides it with the saved theme on a
// real page load (before paint, to avoid a flash). wire:navigate swaps
// the page without a full reload, so that inline script never re-runs —
// the class silently fell back to the server-rendered "dark" on every
// SPA-style navigation until the user did a full reload. Re-apply it here
// on Livewire's own "navigation finished" hook so it survives wire:navigate.
function applyStoredTheme() {
    try {
        document.documentElement.className = localStorage.getItem('mr-theme') || 'dark';
    } catch (e) {
        document.documentElement.className = 'dark';
    }
}
document.addEventListener('livewire:navigated', applyStoredTheme);

// ── PWA installability ────────────────────────────────────────────
// Registers the service worker on every visit, not just when push gets
// activated — an active registration is part of what makes the browser
// consider the site installable ("Adicionar à Tela de Início").
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(() => {});
}

// ── Realtime (Reverb or Pusher) ──────────────────────────────────
// Connection params come from the server at runtime (window.__BROADCAST__,
// injected by <x-broadcast-config>) so the pre-built bundle connects to the
// right service — Reverb (self-hosted) or Pusher (cloud) — driven only by the
// server's .env, no rebuild on deploy. Falls back to build-time VITE_* for
// local dev. Guarded: no key → no Echo, so the rest of the app keeps working.
{
    const cfg = window.__BROADCAST__ || {};
    const key = cfg.key || import.meta.env.VITE_REVERB_APP_KEY;
    if (key) {
        window.Pusher = Pusher;
        if (cfg.broadcaster === 'pusher') {
            // Pusher cloud: wss on 443, no host/port/proxy needed.
            window.Echo = new Echo({
                broadcaster: 'pusher',
                key,
                cluster: cfg.cluster,
                forceTLS: (cfg.scheme || 'https') === 'https',
            });
        } else {
            // Reverb (self-hosted) — local dev default.
            const scheme = cfg.scheme || import.meta.env.VITE_REVERB_SCHEME || 'http';
            const port = Number(cfg.port || import.meta.env.VITE_REVERB_PORT || 8080);
            window.Echo = new Echo({
                broadcaster: 'reverb',
                key,
                wsHost: cfg.host || import.meta.env.VITE_REVERB_HOST,
                wsPort: port,
                wssPort: port,
                forceTLS: scheme === 'https',
                enabledTransports: ['ws', 'wss'],
            });
        }
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

// ── Browser push notifications (shifts + chat) ────────────────────
// VAPID public key comes from the server at runtime (window.__VAPID_PUBLIC_KEY__,
// injected by <x-webpush-config>), same pattern as broadcasting config above.
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(base64);
    return Uint8Array.from([...raw].map((c) => c.charCodeAt(0)));
}

window.webPushSupported = function () {
    return 'serviceWorker' in navigator && 'PushManager' in window && !!window.__VAPID_PUBLIC_KEY__;
};

// Whether an existing subscription's key still matches the server's current
// VAPID public key. A mismatch (e.g. keys were regenerated, or the
// subscription is left over from an earlier test) makes the push service
// silently reject every send — the browser has to unsubscribe and
// resubscribe with the current key before it'll work again.
function subscriptionKeyMatches(subscription) {
    const existing = subscription && subscription.options && subscription.options.applicationServerKey;
    if (!existing) return false;

    const current = urlBase64ToUint8Array(window.__VAPID_PUBLIC_KEY__);
    const existingBytes = new Uint8Array(existing);
    if (existingBytes.length !== current.length) return false;

    return existingBytes.every((byte, i) => byte === current[i]);
}

// Subscribes this browser to push and returns {subscription, replacedEndpoint}.
// subscription is a plain object ({endpoint, keys: {p256dh, auth}}) ready to
// send to the server; replacedEndpoint is the old endpoint to also tell the
// server to forget, or null when there wasn't a stale one.
window.webPushSubscribe = async function () {
    if (!window.webPushSupported()) throw new Error('Push não suportado neste navegador.');

    const permission = await Notification.requestPermission();
    if (permission !== 'granted') throw new Error('Permissão de notificação negada.');

    const registration = await navigator.serviceWorker.register('/sw.js');
    await navigator.serviceWorker.ready;

    let subscription = await registration.pushManager.getSubscription();
    let replacedEndpoint = null;
    if (subscription && !subscriptionKeyMatches(subscription)) {
        replacedEndpoint = subscription.endpoint;
        await subscription.unsubscribe();
        subscription = null;
    }
    if (!subscription) {
        subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(window.__VAPID_PUBLIC_KEY__),
        });
    }

    return { subscription: JSON.parse(JSON.stringify(subscription)), replacedEndpoint };
};

// Unsubscribes this browser and returns the endpoint that was removed (or
// null when there was nothing to unsubscribe from).
window.webPushUnsubscribe = async function () {
    if (!('serviceWorker' in navigator)) return null;

    const registration = await navigator.serviceWorker.getRegistration('/sw.js');
    const subscription = registration && (await registration.pushManager.getSubscription());
    if (!subscription) return null;

    const endpoint = subscription.endpoint;
    await subscription.unsubscribe();

    return endpoint;
};

// Current status for the UI: 'unsupported' | 'denied' | 'subscribed' | 'unsubscribed'.
window.webPushStatus = async function () {
    if (!window.webPushSupported()) return 'unsupported';
    if (Notification.permission === 'denied') return 'denied';

    const registration = await navigator.serviceWorker.getRegistration('/sw.js');
    const subscription = registration && (await registration.pushManager.getSubscription());
    if (!subscription) return 'unsubscribed';

    if (!subscriptionKeyMatches(subscription)) {
        // Looks active to the browser, but it's tied to an old/different
        // VAPID key — the push service will silently reject anything sent
        // to it. Clear it so the UI reports the true state (off) instead of
        // a misleading "on" that a page reload can't self-heal, since a
        // mismatched key is never re-synced to the server on purpose.
        await subscription.unsubscribe();
        return 'unsubscribed';
    }

    return 'subscribed';
};

// If this browser already has an active, current-key subscription, returns it
// (as a plain object) so the caller can re-sync it to the server. The browser
// remembering "I'm subscribed" doesn't mean the server still has that row —
// it can get lost (e.g. wiped, or the original save silently failed) while
// the browser-side subscription keeps existing, leaving the toggle stuck
// showing "on" with nothing actually saved. Called on every Settings page
// load so that mismatch heals itself instead of requiring an off/on toggle.
window.webPushCurrentSubscription = async function () {
    if (!window.webPushSupported() || Notification.permission !== 'granted') return null;

    const registration = await navigator.serviceWorker.getRegistration('/sw.js');
    const subscription = registration && (await registration.pushManager.getSubscription());
    if (!subscription || !subscriptionKeyMatches(subscription)) return null;

    return JSON.parse(JSON.stringify(subscription));
};
