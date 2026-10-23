// ── "Instale o app" (PWA) ─────────────────────────────────────────
// Our own install invitation + tutorial, so we don't depend on the browser deciding to suggest it.
//  • Chrome/Edge/Android fire `beforeinstallprompt`: we keep the event and offer a real "Instalar agora".
//  • iPhone (Safari) and browsers without that event only get the step-by-step tutorial.
//  • Once the app is installed (running standalone, or the `appinstalled` event, or this browser was
//    already flagged as installed) the invitation and the menu entry disappear for good.

const INSTALLED_KEY = 'mr-pwa-installed';
const SNOOZE_KEY = 'mr-install-snooze';
const SNOOZE_DAYS = 7; // after "Agora não" / closing the invitation
const AUTO_OPEN_DELAY_MS = 6000; // let the page settle before inviting

const read = (key) => {
    try {
        return localStorage.getItem(key);
    } catch (e) {
        return null;
    }
};
const write = (key, value) => {
    try {
        value === null ? localStorage.removeItem(key) : localStorage.setItem(key, value);
    } catch (e) {}
};

function isStandalone() {
    return (
        window.matchMedia('(display-mode: standalone)').matches ||
        window.matchMedia('(display-mode: minimal-ui)').matches ||
        window.matchMedia('(display-mode: window-controls-overlay)').matches ||
        window.navigator.standalone === true // iOS home-screen app
    );
}

function detectPlatform() {
    const ua = navigator.userAgent || '';
    // iPadOS reports itself as a Mac with touch points.
    if (/iPhone|iPad|iPod/i.test(ua) || (/Macintosh/i.test(ua) && navigator.maxTouchPoints > 1)) return 'ios';
    if (/Android/i.test(ua)) return 'android';
    return 'desktop';
}

// The browser may fire this before Alpine boots: capture it at module load and hand it to the store.
let deferredPrompt = null;
window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault(); // we show our own invitation instead of the mini-infobar
    deferredPrompt = event;
    // The browser only offers installation when the app is NOT installed: forget a stale "installed" flag
    // (e.g. the app was uninstalled since).
    write(INSTALLED_KEY, null);
    if (window.Alpine && window.Alpine.store('install')) {
        const store = window.Alpine.store('install');
        store.deferred = event;
        store.installed = false;
    }
});

window.addEventListener('appinstalled', () => {
    deferredPrompt = null;
    write(INSTALLED_KEY, '1');
    if (window.Alpine && window.Alpine.store('install')) {
        const store = window.Alpine.store('install');
        store.installed = true;
        store.deferred = null;
        store.open = false;
    }
});

document.addEventListener('alpine:init', () => {
    window.Alpine.store('install', {
        open: false,
        platform: detectPlatform(),
        tab: detectPlatform(), // 'android' | 'ios' | 'desktop'
        deferred: deferredPrompt,
        installed: isStandalone() || read(INSTALLED_KEY) === '1',

        get canPrompt() {
            return !!this.deferred;
        },

        show() {
            this.tab = this.platform;
            this.open = true;
        },

        /** "Agora não" / X: don't invite again for a while (the menu entry stays). */
        dismiss() {
            this.open = false;
            write(SNOOZE_KEY, String(Date.now() + SNOOZE_DAYS * 86400000));
        },

        /** Native install dialog (Chrome/Edge/Android). Falls back to the tutorial elsewhere. */
        async install() {
            if (!this.deferred) {
                this.dismiss();
                return;
            }
            const event = this.deferred;
            this.deferred = null; // the event can only be used once
            event.prompt();
            const choice = await event.userChoice.catch(() => null);
            if (choice && choice.outcome === 'accepted') {
                this.installed = true;
                write(INSTALLED_KEY, '1');
            }
            this.open = false;
        },

        /** Invites once per page load, never when installed, and not again until the snooze runs out. */
        maybeAutoOpen() {
            if (this.installed || this.open) return;
            if (Number(read(SNOOZE_KEY) || 0) > Date.now()) return;
            if (window.sessionStorage && sessionStorage.getItem('mr-install-shown')) return;

            setTimeout(() => {
                if (this.installed || this.open) return;
                try {
                    sessionStorage.setItem('mr-install-shown', '1');
                } catch (e) {}
                this.show();
            }, AUTO_OPEN_DELAY_MS);
        },
    });
});
