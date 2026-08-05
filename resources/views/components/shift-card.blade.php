@props(['shift', 'interested' => false, 'accepted' => false, 'venueTypeLabels' => null, 'expectedVolumeLabels' => null, 'benefitMeta' => null])
@php
    use App\Support\Catalog;

    // Pass these down from a parent that renders many cards (e.g. Shifts\Index) to
    // avoid one taxonomy query per card; falls back to a self-fetch otherwise.
    $venueTypeLabels ??= Catalog::allVenueTypeLabels();
    $expectedVolumeLabels ??= Catalog::allExpectedVolumeLabels();
    $benefitMeta ??= Catalog::allBenefitMeta();

    $profile = $shift->creator?->profile;
    $creatorName = $profile?->name ?: ($shift->creator?->name ?? 'Usuário');
    $creatorPhoto = $profile?->photo_url;

    $isCoverage = $shift->creator_role === 'courier';
    $unavailable = $shift->status !== 'available';
    $filled = $shift->status === 'filled';
    $statusLabel = $filled ? 'Preenchida' : ($unavailable ? 'Reservada' : 'Disponível');

    $photo = $shift->address_photo_url;
    $hasPhoto = $photo && \Illuminate\Support\Str::startsWith($photo, ['http://', 'https://']);

    $mine = auth()->id() === $shift->creator_id;
@endphp

<a href="{{ route('shifts.show', $shift->id) }}" wire:navigate
    class="tap group block rounded-2xl border p-5 transition {{ $mine ? 'border-primary/60 bg-primary/[0.06] ring-1 ring-inset ring-primary/25 hover:bg-primary/10' : 'border-border bg-card hover:border-primary/40 hover:bg-surface-elevated' }}">
    {{-- Header: thumbnail + title / price --}}
    <div class="flex items-start gap-3">
        @if ($hasPhoto)
            <img src="{{ $photo }}" alt="{{ $shift->venue }}"
                class="h-14 w-14 shrink-0 rounded-2xl object-cover ring-1 ring-border/60" />
        @else
            <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-secondary text-primary ring-1 ring-border/60">
                <x-ui.icon :name="$isCoverage ? 'bike' : 'store'" class="h-6 w-6" />
            </span>
        @endif

        <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <h3 class="truncate text-[15px] font-semibold leading-snug text-foreground">{{ $shift->venue }}</h3>
                    @if ($shift->venue_type)
                        <p class="text-[11px] font-medium text-muted-foreground">{{ $venueTypeLabels[$shift->venue_type] ?? $shift->venue_type }}</p>
                    @endif
                </div>
                <div class="shrink-0 text-right">
                    <div class="font-display text-xl font-bold leading-none text-primary">R$ {{ $shift->daily_rate + 0 }}</div>
                    <div class="mt-0.5 text-[10px] font-medium tracking-wide text-muted-foreground/70">DIÁRIA</div>
                    @if ($interested)
                        <span class="glow-orange mt-1.5 inline-flex items-center gap-1 rounded-full bg-primary px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-primary-foreground">
                            <x-ui.icon name="check" class="h-3 w-3" :stroke="3" /> Tenho interesse
                        </span>
                    @endif
                </div>
            </div>

            <p class="mt-1.5 flex items-center gap-1 text-xs text-muted-foreground">
                <x-ui.icon name="map-pin" class="h-3 w-3" />
                <span class="truncate"><span x-data x-text="(() => { const d = window.mrDistance($store.geo, {{ $shift->lat }}, {{ $shift->lng }}); return d ? d + ' · ' : ''; })()"></span>{{ $shift->region }}</span>
            </p>
        </div>
    </div>

    {{-- Status pill + source --}}
    <div class="mt-4 flex flex-wrap items-center gap-2">
        @if ($mine)
            <span class="inline-flex items-center gap-1 rounded-full bg-primary px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-primary-foreground">
                <x-ui.icon name="user" class="h-3 w-3" :stroke="2.6" /> Sua vaga
            </span>
        @endif
        @if (! $shift->active)
            <span class="inline-flex items-center gap-1 rounded-full bg-amber-500/15 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-500">
                <x-ui.icon name="pause" class="h-3 w-3" :stroke="2.6" /> Pausada
            </span>
        @else
            <span class="rounded-full px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $unavailable ? 'bg-muted text-muted-foreground' : 'bg-success/15 text-success' }}">
                {{ $statusLabel }}
            </span>
        @endif

        @if ($shift->couriers_needed > 1)
            <span class="inline-flex items-center gap-1.5 rounded-full border border-primary/40 bg-primary/15 px-2.5 py-0.5 text-[10px] font-semibold text-primary">
                <x-ui.icon name="users" class="h-3 w-3" />
                Vaga para {{ $shift->couriers_needed }} motoboys
            </span>
        @endif

        @if ($isCoverage)
            <span class="inline-flex items-center gap-1.5 rounded-full border border-primary/30 bg-primary/10 px-2.5 py-0.5 text-[10px] font-semibold text-primary">
                <x-ui.icon name="refresh-cw" class="h-3 w-3" />
                Cobertura de turno
            </span>
        @else
            <span class="inline-flex items-center gap-1.5 rounded-full border border-cyan-400/40 bg-cyan-400/10 px-2.5 py-0.5 text-[10px] font-semibold text-cyan-300">
                <x-ui.icon name="store" class="h-3 w-3" />
                Estabelecimento
            </span>
        @endif
    </div>

    {{-- Schedule --}}
    <div class="mt-4 flex items-center justify-between gap-3">
        <span class="flex items-center gap-1.5 text-xs text-foreground/80">
            <x-ui.icon name="clock" class="h-3.5 w-3.5 text-muted-foreground/70" />
            {{ \Illuminate\Support\Carbon::parse($shift->date)->isoFormat('DD [de] MMM') }} · {{ $shift->start_time }}–{{ $shift->end_time }}
        </span>
    </div>

    <p class="mt-2 text-xs text-muted-foreground">
        Taxa:
        <span class="font-semibold text-foreground/85">
            @if ($shift->delivery_fee_min == $shift->delivery_fee_max)
                R$ {{ number_format($shift->delivery_fee_min, 2, ',', '.') }}
            @else
                R$ {{ number_format($shift->delivery_fee_min, 2, ',', '.') }} – R$ {{ number_format($shift->delivery_fee_max, 2, ',', '.') }}
            @endif
        </span>
        por entrega
    </p>

    {{-- Soft tags --}}
    <div class="mt-3 flex flex-wrap items-center gap-1.5">
        <span class="inline-flex items-center gap-1 rounded-full bg-secondary/40 px-2 py-0.5 text-[10px] font-medium text-muted-foreground">
            <x-ui.icon name="bike" class="h-2.5 w-2.5" />
            {{ Catalog::vehiclesLabel($shift->accepted_vehicles ?? []) }}
        </span>
        @if ($shift->requires_own_bag)
            <span class="inline-flex items-center gap-1 rounded-full bg-secondary/40 px-2 py-0.5 text-[10px] font-medium text-muted-foreground">
                <x-ui.icon name="package" class="h-2.5 w-2.5" />
                Bag própria
            </span>
        @endif
        @foreach ($shift->benefits ?? [] as $benefit)
            <span class="inline-flex items-center gap-1 rounded-full bg-secondary/40 px-2 py-0.5 text-[10px] font-medium text-muted-foreground">
                <x-ui.icon :name="$benefitMeta[$benefit]['icon'] ?? 'check'" class="h-2.5 w-2.5" />
                {{ $benefitMeta[$benefit]['name'] ?? $benefit }}
            </span>
        @endforeach
    </div>

    @if ($shift->expected_volume)
        <p class="mt-2 text-[11px] text-muted-foreground">Movimento: {{ $expectedVolumeLabels[$shift->expected_volume] ?? $shift->expected_volume }}</p>
    @endif

    {{-- Footer: creator + last edited --}}
    <div class="mt-4 flex items-end justify-between gap-2 border-t border-border/50 pt-3">
        <div class="flex min-w-0 items-center gap-2">
            @if ($creatorPhoto)
                <img src="{{ $creatorPhoto }}" alt="" class="h-6 w-6 rounded-full bg-secondary object-cover" />
            @else
                <span class="grid h-6 w-6 place-items-center rounded-full bg-secondary text-[10px] font-bold text-muted-foreground">
                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($creatorName, 0, 1)) }}
                </span>
            @endif
            <span class="truncate text-[11px] text-muted-foreground">
                Publicado por <span class="font-semibold text-foreground/90">{{ $creatorName }}</span>
            </span>
        </div>
        @if ($shift->edited_at)
            <span class="shrink-0 text-right text-[10px] text-muted-foreground/80">Atualizado em {{ $shift->edited_at->format('d/m/Y H:i') }}</span>
        @endif
    </div>

    @if ($accepted)
        <div class="mt-3 flex items-start gap-2 rounded-xl border border-success/40 bg-success/10 px-3 py-2.5 text-xs font-semibold text-success">
            <x-ui.icon name="check-circle" class="mt-0.5 h-4 w-4 shrink-0" :stroke="2.4" />
            <span>Interesse aceito com sucesso. Esteja no local no horário especificado! Bom trabalho 🎉</span>
        </div>
    @endif
</a>
