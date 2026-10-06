@php
    use App\Support\Catalog;
    $activeVehicle = $this->activeVehicle;
    $vehicleIcon = Catalog::VEHICLE_ICON[$activeVehicle] ?? 'bike';
    $currentRole = $this->currentRole;
    $myProfile = auth()->user()?->profile;
    $myInitial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($myProfile?->name ?: 'U', 0, 1));
    // Fetched once here (not per card) to avoid N+1 taxonomy queries in the list below.
    $venueTypeLabels = Catalog::allVenueTypeLabels();
    $expectedVolumeLabels = Catalog::allExpectedVolumeLabels();
    $benefitMeta = Catalog::allBenefitMeta();
    $benefits = Catalog::benefits();
    $banners = $this->banners;
@endphp

<div class="px-4 pt-6"
    x-data="{
        filtersOpen: false,
        vehicleOpen: false,
        roleOpen: false,
        menuOpen: false,
        draft: @js($filters),
        initial: { vehicles: [], dailyMin: '', feeMin: '', startTime: '', benefits: [], ownBag: 'any', date: '', onlyInterested: false, onlyMine: false },
        openFilters() { this.filtersOpen = true; },
        toggleVehicle(v) { this.draft.vehicles = this.draft.vehicles.includes(v) ? this.draft.vehicles.filter(x => x !== v) : [...this.draft.vehicles, v]; },
        toggleBenefit(b) { this.draft.benefits = this.draft.benefits.includes(b) ? this.draft.benefits.filter(x => x !== b) : [...this.draft.benefits, b]; },
        apply() { $wire.applyFilters(this.draft); this.filtersOpen = false; },
        clear() { this.draft = JSON.parse(JSON.stringify(this.initial)); $wire.clearFilters(); },
    }" x-init="window.mrRequestGeo && window.mrRequestGeo()"
    @filters-synced.window="draft = JSON.parse(JSON.stringify($event.detail.filters ?? $event.detail[0]?.filters ?? draft))">
    {{-- Header --}}
    <header class="flex items-center justify-between gap-2 lg:justify-end">
        {{-- lg: a sidebar já mostra a logo (ver <x-sidebar-nav />) --}}
        <x-logo class="lg:hidden" />
        <div class="relative flex items-center gap-2">
            {{-- Active vehicle (it filters the list below); tap to change it. Couriers only. --}}
            @if ($currentRole !== 'business')
                <button type="button" @click="vehicleOpen = true" aria-label="Veículo ativo: {{ Catalog::VEHICLE_LABEL_SHORT[$activeVehicle] ?? 'Moto' }}. Trocar veículo"
                    class="tap flex h-10 min-w-0 items-center gap-1.5 rounded-full border border-primary/40 bg-primary/10 px-3 text-[11px] font-semibold text-primary transition hover:bg-primary/20">
                    <x-ui.icon :name="$vehicleIcon" class="h-3.5 w-3.5 shrink-0" />
                    <span class="sm:hidden">{{ Catalog::VEHICLE_LABEL_TINY[$activeVehicle] ?? 'Moto' }}</span>
                    <span class="hidden max-w-[130px] truncate sm:inline">{{ Catalog::VEHICLE_LABEL_SHORT[$activeVehicle] ?? 'Moto' }}</span>
                </button>
            @endif
            <a href="{{ route('notifications') }}" wire:navigate aria-label="Notificações"
                class="tap relative grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-border bg-surface text-muted-foreground transition hover:text-foreground">
                <x-ui.icon name="bell" class="h-4 w-4" />
                @if ($this->unreadCount > 0)
                    <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-primary ring-2 ring-background"></span>
                @endif
            </a>
            {{-- Account menu: edit profile, switch active profile / vehicle, sign out. --}}
            <button type="button" @click="menuOpen = true" aria-label="Menu da conta" aria-haspopup="dialog"
                class="tap grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-full border border-border bg-secondary text-sm font-bold text-muted-foreground transition hover:border-primary/60">
                @if ($myProfile?->photo_url)
                    <img src="{{ $myProfile->photo_url }}" alt="" class="h-full w-full object-cover" />
                @else
                    {{ $myInitial }}
                @endif
            </button>

            {{-- Mobile: bottom sheet. lg: small dropdown under the photo. --}}
            <div x-show="menuOpen" x-cloak class="fixed inset-0 z-50 lg:absolute lg:inset-auto lg:right-0 lg:top-12 lg:z-40 lg:w-80"
                @keydown.escape.window="menuOpen = false">
                <div x-show="menuOpen" x-transition.opacity class="absolute inset-0 bg-black/60 lg:fixed lg:bg-transparent" @click="menuOpen = false"></div>
                <div x-show="menuOpen" role="dialog" aria-label="Menu da conta"
                    x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-full opacity-0 lg:translate-y-0" x-transition:enter-end="translate-y-0 opacity-100"
                    x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="translate-y-full opacity-0 lg:translate-y-0"
                    class="absolute bottom-0 left-1/2 w-full max-w-md -translate-x-1/2 rounded-t-3xl border border-border bg-surface p-4 shadow-2xl lg:static lg:left-auto lg:max-w-none lg:translate-x-0 lg:rounded-2xl">
                    <div class="flex items-center gap-3 px-1 pb-3">
                        <span class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-full border border-border bg-secondary text-base font-bold text-muted-foreground">
                            @if ($myProfile?->photo_url)
                                <img src="{{ $myProfile->photo_url }}" alt="" class="h-full w-full object-cover" />
                            @else
                                {{ $myInitial }}
                            @endif
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-bold">{{ $myProfile?->name ?: 'Usuário' }}</span>
                            <span class="block truncate text-[11px] text-muted-foreground">{{ auth()->user()?->email }}</span>
                        </span>
                    </div>

                    <div class="space-y-1 border-t border-border pt-2">
                        <a href="{{ route('profile') }}" wire:navigate @click="menuOpen = false"
                            class="tap flex items-center gap-3 rounded-xl px-2 py-3 text-sm font-medium transition hover:bg-surface-elevated">
                            <x-ui.icon name="user" class="h-5 w-5 shrink-0 text-muted-foreground" />
                            <span class="flex-1">Editar perfil</span>
                            <x-ui.icon name="chevron-right" class="h-4 w-4 text-muted-foreground" />
                        </a>
                        <button type="button" @click="menuOpen = false; roleOpen = true"
                            class="tap flex w-full items-center gap-3 rounded-xl px-2 py-3 text-left text-sm font-medium transition hover:bg-surface-elevated">
                            <x-ui.icon :name="$currentRole === 'business' ? 'store' : 'bike'" class="h-5 w-5 shrink-0 text-muted-foreground" />
                            <span class="flex-1">Trocar perfil ativo</span>
                            <span class="rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-semibold text-primary">{{ $currentRole === 'business' ? 'Estabelecimento' : 'Motoboy' }}</span>
                        </button>
                        @if ($currentRole !== 'business')
                            <button type="button" @click="menuOpen = false; vehicleOpen = true"
                                class="tap flex w-full items-center gap-3 rounded-xl px-2 py-3 text-left text-sm font-medium transition hover:bg-surface-elevated">
                                <x-ui.icon :name="$vehicleIcon" class="h-5 w-5 shrink-0 text-muted-foreground" />
                                <span class="flex-1">Trocar veículo ativo</span>
                                <span class="rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-semibold text-primary">{{ Catalog::VEHICLE_LABEL_SHORT[$activeVehicle] ?? 'Moto' }}</span>
                            </button>
                        @endif
                    </div>

                    <div class="mt-2 border-t border-border pt-2">
                        <button type="button" @click="window.mrLogout()"
                            class="tap flex w-full items-center gap-3 rounded-xl px-2 py-3 text-left text-sm font-medium text-destructive transition hover:bg-surface-elevated">
                            <x-ui.icon name="log-out" class="h-5 w-5 shrink-0" />
                            Sair
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- Hero / banner carousel — admin-managed under Filament > Vagas > Banners.
    Swiper is loaded on demand (only when there's at least one banner), the
    same lazy-CDN pattern used for Leaflet on the map page. wire:ignore keeps
    Livewire from touching this subtree on unrelated re-renders (typing in
    the search box, etc.) so the carousel instance survives untouched. --}}
    @if ($banners->isNotEmpty())
        <div class="relative mt-6 overflow-hidden rounded-3xl" wire:ignore
            x-data="{
                swiper: null,
                async init() {
                    await this.ensureSwiper();
                    this.swiper = new Swiper(this.$refs.swiperEl, {
                        loop: {{ $banners->count() > 1 ? 'true' : 'false' }},
                        autoplay: { delay: 4000, disableOnInteraction: false },
                        pagination: { el: this.$refs.pagination, clickable: true },
                    });
                },
                ensureSwiper() {
                    return new Promise((resolve) => {
                        if (window.Swiper) return resolve();
                        const css = document.createElement('link');
                        css.rel = 'stylesheet';
                        css.href = 'https://unpkg.com/swiper@11/swiper-bundle.min.css';
                        document.head.appendChild(css);
                        const s = document.createElement('script');
                        s.src = 'https://unpkg.com/swiper@11/swiper-bundle.min.js';
                        s.onload = () => resolve();
                        document.body.appendChild(s);
                    });
                },
            }">
            <div class="swiper" x-ref="swiperEl">
                <div class="swiper-wrapper">
                    @foreach ($banners as $banner)
                        <div class="swiper-slide">
                            @if ($banner->link_url)
                                <a href="{{ $banner->link_url }}" @if ($banner->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif>
                                    <x-banner-image :banner="$banner" />
                                </a>
                            @else
                                <x-banner-image :banner="$banner" />
                            @endif
                        </div>
                    @endforeach
                </div>
                @if ($banners->count() > 1)
                    <div class="swiper-pagination" x-ref="pagination"></div>
                @endif
            </div>
        </div>
        <style>
            .swiper-pagination-bullet { background: var(--color-muted-foreground); opacity: 0.5; }
            .swiper-pagination-bullet-active { background: var(--color-primary); opacity: 1; }
        </style>
    @else
        <div class="relative mt-6">
            <div aria-hidden class="pointer-events-none absolute -left-10 -top-10 h-32 w-32 rounded-full bg-primary/10 blur-3xl"></div>
            <h1 class="font-display text-[28px] font-bold leading-[1.1] tracking-tight">
                Sua <span class="bg-gradient-to-r from-primary to-[oklch(0.82_0.17_65)] bg-clip-text text-transparent">parceria</span><br />começa aqui
            </h1>
            <p class="mt-2 text-sm font-light text-muted-foreground">Vagas disponíveis na sua região hoje.</p>
        </div>
    @endif

    {{-- Search + filter button --}}
    <div class="mt-5 flex gap-2">
        <div class="relative flex-1">
            <x-ui.icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <input type="text" wire:model.live.debounce.300ms="q" placeholder="Buscar restaurante…"
                class="h-12 w-full rounded-2xl border border-border/70 bg-surface/60 pl-11 pr-3 text-sm placeholder:text-muted-foreground focus-visible:border-primary/50 focus-visible:outline-none" />
        </div>
        <button type="button" @click="openFilters()" aria-label="Filtros avançados"
            class="relative flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-border/70 bg-surface text-muted-foreground transition hover:border-primary/40 hover:text-primary">
            <x-ui.icon name="filter" class="h-[18px] w-[18px]" />
            @if ($this->activeFilterCount > 0)
                <span class="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[9px] font-bold text-primary-foreground ring-2 ring-background">
                    {{ $this->activeFilterCount }}
                </span>
            @endif
        </button>
    </div>

    {{-- Region chips. "Minhas vagas (n)" is the same filter as the one in the sheet, one tap away; shown only to whoever has open shifts. --}}
    <div class="mt-4 flex gap-2 overflow-x-auto pb-1 scrollbar-hide">
        @if ($this->myOpenCount > 0 || ! empty($filters['onlyMine']))
            <button wire:click="toggleMine" aria-pressed="{{ ! empty($filters['onlyMine']) ? 'true' : 'false' }}"
                class="flex shrink-0 items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-xs font-semibold transition {{ ! empty($filters['onlyMine']) ? 'border-primary bg-primary text-primary-foreground' : 'border-primary/50 bg-primary/10 text-primary hover:bg-primary/20' }}">
                <x-ui.icon name="store" class="h-3.5 w-3.5" />
                Minhas vagas ({{ $this->myOpenCount }})
            </button>
        @endif
        <button wire:click="setRegion(null)"
            class="shrink-0 rounded-full px-3.5 py-1.5 text-xs font-semibold transition {{ $region === null ? 'bg-primary text-primary-foreground' : 'bg-secondary text-secondary-foreground hover:bg-surface-elevated' }}">
            Todas
        </button>
        @foreach ($this->regions as $r)
            <button wire:click="setRegion(@js($r))"
                class="shrink-0 rounded-full px-3.5 py-1.5 text-xs font-semibold transition {{ $region === $r ? 'bg-primary text-primary-foreground' : 'bg-secondary text-secondary-foreground hover:bg-surface-elevated' }}">
                {{ $r }}
            </button>
        @endforeach
    </div>

    {{-- List --}}
    <div class="mt-4 space-y-3">
        @forelse ($this->shifts as $shift)
            <x-shift-card :shift="$shift" :interested="$this->myInterestIds->contains($shift->id)" :accepted="$this->myAcceptedIds->contains($shift->id)"
                :venueTypeLabels="$venueTypeLabels" :expectedVolumeLabels="$expectedVolumeLabels" :benefitMeta="$benefitMeta"
                wire:key="shift-{{ $shift->id }}" />
        @empty
            <div class="rounded-2xl border border-dashed border-border p-10 text-center text-sm text-muted-foreground">
                Nenhuma vaga encontrada.
            </div>
        @endforelse
    </div>

    {{-- ── Filters sheet ─────────────────────────────────────────── --}}
    <div x-show="filtersOpen" x-cloak wire:ignore class="fixed inset-0 z-50">
        <div x-show="filtersOpen" x-transition.opacity class="absolute inset-0 bg-black/60" @click="filtersOpen = false"></div>
        <div x-show="filtersOpen"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
            class="absolute bottom-0 left-1/2 max-h-[90dvh] w-full max-w-md -translate-x-1/2 overflow-y-auto rounded-t-3xl border border-border bg-surface p-5">
            <h2 class="font-display text-lg font-bold">Filtros avançados</h2>
            <p class="text-sm text-muted-foreground">Refine sua lista de vagas.</p>

            <div class="mt-4 space-y-5">
                {{-- My interest --}}
                <div>
                    <label class="text-[11px] uppercase tracking-wider text-muted-foreground">Meu interesse</label>
                    <button type="button" @click="draft.onlyInterested = !draft.onlyInterested; if (draft.onlyInterested) draft.onlyMine = false"
                        class="mt-2 flex w-full items-center justify-between rounded-xl border p-3 text-sm font-semibold transition"
                        :class="draft.onlyInterested ? 'border-primary bg-primary/10 text-primary' : 'border-border/60 bg-input text-muted-foreground'">
                        <span class="flex items-center gap-2"><x-ui.icon name="check" class="h-4 w-4" /> Apenas vagas com meu interesse</span>
                        <span class="flex h-5 w-9 items-center rounded-full p-0.5 transition" :class="draft.onlyInterested ? 'bg-primary' : 'bg-muted'">
                            <span class="h-4 w-4 rounded-full bg-background transition" :class="draft.onlyInterested ? 'translate-x-4' : ''"></span>
                        </span>
                    </button>
                </div>

                {{-- Published by me --}}
                <div>
                    <label class="text-[11px] uppercase tracking-wider text-muted-foreground">Minhas vagas</label>
                    <button type="button" @click="draft.onlyMine = !draft.onlyMine; if (draft.onlyMine) draft.onlyInterested = false"
                        class="mt-2 flex w-full items-center justify-between rounded-xl border p-3 text-sm font-semibold transition"
                        :class="draft.onlyMine ? 'border-primary bg-primary/10 text-primary' : 'border-border/60 bg-input text-muted-foreground'">
                        <span class="flex items-center gap-2"><x-ui.icon name="store" class="h-4 w-4" /> Vagas publicadas por mim</span>
                        <span class="flex h-5 w-9 items-center rounded-full p-0.5 transition" :class="draft.onlyMine ? 'bg-primary' : 'bg-muted'">
                            <span class="h-4 w-4 rounded-full bg-background transition" :class="draft.onlyMine ? 'translate-x-4' : ''"></span>
                        </span>
                    </button>
                    <p class="mt-1 text-[10px] text-muted-foreground">Mostra só as suas vagas que ainda estão abertas.</p>
                </div>
                {{-- Date --}}
                <div>
                    <label class="text-[11px] uppercase tracking-wider text-muted-foreground">Data específica (opcional)</label>
                    <div class="mt-2 flex gap-2">
                        <x-ui.input type="date" x-model="draft.date" class="flex-1" />
                        <button type="button" x-show="draft.date" @click="draft.date = ''"
                            class="rounded-xl border border-border/60 bg-surface px-3 text-xs font-semibold text-muted-foreground hover:text-foreground">Limpar</button>
                    </div>
                    <p class="mt-1 text-[10px] text-muted-foreground">Sem data selecionada exibe todas as vagas.</p>
                </div>

                {{-- Vehicles --}}
                <div>
                    <label class="text-[11px] uppercase tracking-wider text-muted-foreground">Veículos aceitos</label>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach (Catalog::VEHICLE_OPTIONS as $v)
                            <button type="button" @click="toggleVehicle('{{ $v }}')"
                                class="flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-semibold transition"
                                :class="draft.vehicles.includes('{{ $v }}') ? 'border-primary bg-primary/15 text-primary' : 'border-border/60 bg-input text-muted-foreground'">
                                <x-ui.icon :name="Catalog::VEHICLE_ICON[$v]" class="h-3.5 w-3.5" />
                                {{ Catalog::VEHICLE_LABEL[$v] }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Money --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-[11px] uppercase tracking-wider text-muted-foreground">Diária mínima (R$)</label>
                        <x-ui.input type="number" inputmode="numeric" x-model="draft.dailyMin" placeholder="40" />
                    </div>
                    <div>
                        <label class="text-[11px] uppercase tracking-wider text-muted-foreground">Taxa de entrega mínima</label>
                        <x-ui.input type="number" inputmode="numeric" x-model="draft.feeMin" placeholder="7" />
                    </div>
                </div>

                {{-- Start time --}}
                <div>
                    <label class="text-[11px] uppercase tracking-wider text-muted-foreground">Início a partir de</label>
                    <x-ui.input type="time" x-model="draft.startTime" />
                </div>

                {{-- Benefits --}}
                <div>
                    <label class="text-[11px] uppercase tracking-wider text-muted-foreground">Benefícios desejados</label>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        @foreach ($benefits as $benefit)
                            <label class="flex cursor-pointer items-center gap-2 rounded-xl border p-2.5 text-xs"
                                :class="draft.benefits.includes('{{ $benefit['slug'] }}') ? 'border-primary bg-primary/10 text-primary' : 'border-border/60 bg-input'">
                                <input type="checkbox" class="h-4 w-4 accent-[var(--color-primary)]"
                                    :checked="draft.benefits.includes('{{ $benefit['slug'] }}')" @change="toggleBenefit('{{ $benefit['slug'] }}')" />
                                {{ $benefit['name'] }}
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Own bag --}}
                <div>
                    <label class="text-[11px] uppercase tracking-wider text-muted-foreground">Exige Bag própria?</label>
                    <div class="mt-2 grid grid-cols-3 gap-2">
                        @foreach (['any' => 'Qualquer', 'yes' => 'Sim', 'no' => 'Não'] as $opt => $optLabel)
                            <button type="button" @click="draft.ownBag = '{{ $opt }}'"
                                class="rounded-xl border px-3 py-2 text-xs font-semibold transition"
                                :class="draft.ownBag === '{{ $opt }}' ? 'border-primary bg-primary/15 text-primary' : 'border-border/60 bg-input text-muted-foreground'">
                                {{ $optLabel }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="mt-6 flex flex-col gap-2">
                <x-ui.button size="lg" class="w-full" @click="apply()">Aplicar Filtros</x-ui.button>
                <button type="button" @click="clear()"
                    class="w-full rounded-xl border border-border/60 bg-surface py-3 text-xs font-semibold text-muted-foreground hover:text-foreground">Limpar filtros</button>
                <button type="button" @click="filtersOpen = false"
                    class="w-full rounded-xl py-2 text-xs font-medium text-muted-foreground hover:text-foreground">Cancelar</button>
            </div>
        </div>
    </div>

    {{-- ── Vehicle sheet ─────────────────────────────────────────── --}}
    <div x-show="vehicleOpen" x-cloak class="fixed inset-0 z-50">
        <div x-show="vehicleOpen" x-transition.opacity class="absolute inset-0 bg-black/60" @click="vehicleOpen = false"></div>
        <div x-show="vehicleOpen"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
            class="absolute bottom-0 left-1/2 w-full max-w-md -translate-x-1/2 rounded-t-3xl border border-border bg-surface p-5">
            <h2 class="font-display text-lg font-bold">Trocar veículo ativo</h2>
            <p class="text-sm text-muted-foreground">Selecione o veículo que você está utilizando agora.</p>

            <div class="mt-4 space-y-2">
                @foreach (Catalog::VEHICLE_OPTIONS as $v)
                    @php $isActive = $activeVehicle === $v; @endphp
                    <button type="button" @click="$wire.setVehicle('{{ $v }}'); vehicleOpen = false"
                        class="tap flex w-full items-center gap-3 rounded-xl border p-3 text-left transition {{ $isActive ? 'border-primary bg-primary/10' : 'border-border/60 bg-card hover:border-primary/40' }}">
                        <span class="grid h-10 w-10 place-items-center rounded-lg {{ $isActive ? 'bg-primary text-primary-foreground' : 'bg-surface-elevated text-primary' }}">
                            <x-ui.icon :name="Catalog::VEHICLE_ICON[$v]" class="h-5 w-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-foreground">{{ Catalog::VEHICLE_LABEL[$v] }}</span>
                            <span class="block truncate text-[11px] text-muted-foreground">{{ Catalog::VEHICLE_HINT[$v] }}</span>
                        </span>
                        @if ($isActive)
                            <x-ui.icon name="check" class="h-5 w-5 shrink-0 text-primary" />
                        @endif
                    </button>
                @endforeach
            </div>

            <a href="{{ route('vehicle') }}" wire:navigate @click="vehicleOpen = false"
                class="mt-4 block w-full rounded-xl border border-border/60 bg-surface py-3 text-center text-xs font-semibold text-muted-foreground hover:text-foreground">
                Gerenciar documentos do veículo
            </a>
        </div>
    </div>

    {{-- ── Switch profile confirm ───────────────────────────────── --}}
    <div x-show="roleOpen" x-cloak x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" @click.self="roleOpen = false">
        <div class="w-full max-w-sm rounded-2xl border border-border bg-card p-5">
            <h2 class="font-display text-lg font-bold">Trocar perfil</h2>
            <p class="mt-1.5 text-sm text-muted-foreground">
                @if ($currentRole === 'business')
                    Você está como Estabelecimento. Quer virar Motoboy?
                @else
                    Você está como Motoboy. Quer virar Estabelecimento?
                @endif
            </p>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" @click="roleOpen = false"
                    class="rounded-xl px-4 py-2 text-xs font-medium text-muted-foreground hover:text-foreground">Cancelar</button>
                <button type="button"
                    @click="$wire.switchRole('{{ $currentRole === 'business' ? 'courier' : 'business' }}'); roleOpen = false"
                    class="rounded-xl bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground">Confirmar</button>
            </div>
        </div>
    </div>
</div>
