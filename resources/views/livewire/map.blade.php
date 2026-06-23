<div class="fixed inset-0 isolate"
    x-data="{
        shifts: @js($this->shifts),
        map: null,
        userMarker: null,
        async init() {
            await this.ensureLeaflet();
            this.build();
            this.locate(true);
        },
        ensureLeaflet() {
            return new Promise((resolve) => {
                if (window.L) return resolve();
                const css = document.createElement('link');
                css.rel = 'stylesheet';
                css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                document.head.appendChild(css);
                const s = document.createElement('script');
                s.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                s.onload = () => resolve();
                document.body.appendChild(s);
            });
        },
        build() {
            const dark = (localStorage.getItem('mr-theme') || 'dark') !== 'light';
            this.map = L.map(this.$refs.map, { zoomControl: false, worldCopyJump: true }).setView([-23.561, -46.656], 12);
            L.tileLayer(
                dark
                    ? 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png'
                    : 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
                { attribution: '&copy; CARTO &copy; OpenStreetMap', maxZoom: 19 },
            ).addTo(this.map);
            L.control.zoom({ position: 'bottomright' }).addTo(this.map);

            const groups = {};
            for (const v of this.shifts) {
                const k = v.lat.toFixed(5) + ':' + v.lng.toFixed(5);
                (groups[k] = groups[k] || []).push(v);
            }
            for (const k in groups) {
                const list = groups[k];
                const c = list[0];
                const icon = list.length > 1 ? this.clusterIcon(list.length) : this.dotIcon();
                L.marker([c.lat, c.lng], { icon }).addTo(this.map).bindPopup(this.popupHtml(list));
            }
        },
        dotIcon() {
            return L.divIcon({ className: '', iconSize: [22, 22], iconAnchor: [11, 11],
                html: '<div style=\'width:22px;height:22px;border-radius:9999px;background:#f97316;border:2px solid #fff;box-shadow:0 0 0 2px rgba(249,115,22,.35),0 2px 6px rgba(0,0,0,.45)\'></div>' });
        },
        clusterIcon(n) {
            return L.divIcon({ className: '', iconSize: [36, 36], iconAnchor: [18, 18],
                html: '<div style=\'width:36px;height:36px;border-radius:9999px;background:#f97316;border:2px solid #fff;box-shadow:0 0 0 3px rgba(249,115,22,.30),0 2px 8px rgba(0,0,0,.5);display:grid;place-items:center;color:#fff;font-weight:700;font-size:13px\'>' + n + '</div>' });
        },
        userDot() {
            return L.divIcon({ className: '', iconSize: [16, 16], iconAnchor: [8, 8],
                html: '<div style=\'width:16px;height:16px;border-radius:9999px;background:#22c55e;border:2px solid #fff;box-shadow:0 0 0 4px rgba(34,197,94,.25)\'></div>' });
        },
        popupHtml(list) {
            if (list.length > 1) {
                return '<p style=\'font-weight:700;margin-bottom:6px\'>' + list.length + ' vagas neste local</p>' +
                    list.map((v) => '<a href=\'' + v.url + '\' style=\'display:block;font-weight:600\'>' + v.venue + ' — R$ ' + v.rate + '</a>').join('');
            }
            const v = list[0];
            return '<p style=\'font-weight:700\'>' + v.venue + '</p><p>' + v.region + ' · R$ ' + v.rate + '</p>' +
                '<a href=\'' + v.url + '\' style=\'font-weight:600;color:#f97316\'>Ver detalhes →</a>';
        },
        locate(initial) {
            if (!navigator.geolocation) return;
            navigator.geolocation.getCurrentPosition((p) => {
                const ll = [p.coords.latitude, p.coords.longitude];
                if (initial) this.map.setView(ll, 14); else this.map.flyTo(ll, 15);
                if (this.userMarker) this.userMarker.remove();
                this.userMarker = L.marker(ll, { icon: this.userDot() }).addTo(this.map);
            });
        },
    }"
    x-init="init()">
    <style>
        .leaflet-bottom.leaflet-right, .leaflet-bottom.leaflet-left { margin-bottom: 96px; }
        .leaflet-control-attribution { font-size: 9px; }
    </style>

    <header class="pointer-events-none absolute left-0 right-0 top-0 z-[1100] flex items-center justify-between px-4 pt-4">
        <div class="rounded-2xl bg-surface/90 px-3 py-2 backdrop-blur"><x-logo :size="28" /></div>
        <div class="rounded-full bg-surface/90 px-3 py-1.5 text-xs font-medium backdrop-blur">{{ count($this->shifts) }} vagas no mapa</div>
    </header>

    <div x-ref="map" class="absolute inset-0 h-full w-full"></div>

    <button type="button" @click="locate(false)" aria-label="Ir para minha localização"
        class="absolute bottom-[200px] right-2.5 z-[1000] grid h-11 w-11 place-items-center rounded-full border border-border bg-surface text-primary shadow-lg">
        <x-ui.icon name="locate" class="h-5 w-5" />
    </button>
</div>
