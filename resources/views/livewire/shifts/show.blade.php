@php
    use App\Support\Catalog;
    use Illuminate\Support\Str;
    use Illuminate\Support\Js;
    $statusLabel =$shift->status === 'filled' ? 'Preenchida' : ($shift->status === 'reserved' ? 'Reservada' : 'Disponível');
    $requiredTypeLabel = count($acceptedVehicles) === 1
        ? (Catalog::VEHICLE_LABEL[$acceptedVehicles[0]] ?? $acceptedVehicles[0])
        : implode(' ou ', array_map(fn ($v) => Catalog::VEHICLE_LABEL[$v] ?? $v, $acceptedVehicles));
    $userVehicleLabel = $userVehicle ? (Catalog::VEHICLE_LABEL[$userVehicle] ?? $userVehicle) : 'não definido';
    $creatorProfile = $shift->creator?->profile;
    $creatorName = $creatorProfile?->name ?: ($shift->creator?->name ?? 'Usuário');
@endphp

<div class="px-4 pb-6 pt-4" x-data x-init="window.mrRequestGeo && window.mrRequestGeo()">
    {{-- Top bar --}}
    <div class="flex items-center justify-between">
        <button type="button" x-on:click="window.history.back()"
            class="grid h-10 w-10 place-items-center rounded-xl border border-border bg-surface">
            <x-ui.icon name="arrow-left" class="h-4 w-4" />
        </button>
        <button type="button" aria-label="Compartilhar no WhatsApp"
            x-on:click="window.open('https://wa.me/?text=' + encodeURIComponent({{ Illuminate\Support\Js::from("Olha essa vaga no MotoReserva!\n\n📍 {$shift->venue} — {$shift->region}\n📅 ".$shift->date->isoFormat('DD/MM/YYYY')." · {$shift->start_time}–{$shift->end_time}\n💰 R$ ".($shift->daily_rate + 0)." diária\n\n".route('shifts.show', $shift->id)) }}), '_blank')"
            class="grid h-10 w-10 place-items-center rounded-xl border border-border bg-surface text-muted-foreground">
            <x-ui.icon name="share-2" class="h-4 w-4" />
        </button>
    </div>

    {{-- Title --}}
    <div class="mt-5">
        <span class="inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase {{ $shift->status !== 'available' ? 'bg-muted text-muted-foreground' : 'bg-success/15 text-success' }}">
            {{ $statusLabel }}
        </span>
        @unless ($shift->active)
            <span class="ml-1 inline-block rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] font-semibold uppercase text-amber-400">Pausada</span>
        @endunless
        <h1 class="mt-2 font-display text-2xl font-bold">{{ $shift->venue }}</h1>
        @if ($shift->venue_type)
            <p class="text-xs font-semibold text-muted-foreground">{{ Catalog::VENUE_TYPE_LABEL[$shift->venue_type] ?? $shift->venue_type }}</p>
        @endif
        <p class="flex items-center gap-1 text-sm text-muted-foreground">
            <x-ui.icon name="map-pin" class="h-3.5 w-3.5" />
            {{ $shift->region }}@if ($shift->address) · {{ $shift->address }}@endif
        </p>
        <p class="mt-1 text-xs font-semibold text-primary" x-cloak
            x-show="window.mrDistance($store.geo, {{ $shift->lat }}, {{ $shift->lng }})">
            📍 ~<span x-text="window.mrDistance($store.geo, {{ $shift->lat }}, {{ $shift->lng }})"></span> de você
        </p>
        @if ($needed > 1)
            <div class="mt-3 inline-flex items-center gap-2 rounded-full border border-primary/40 bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                <x-ui.icon name="users" class="h-3.5 w-3.5" />
                Vaga para {{ $needed }} motoboys
                @if ($isCreator)
                    <span class="font-normal text-foreground/70">· {{ $totalAccepted }} aceitos · {{ $totalConfirmed }}/{{ $needed }} confirmados</span>
                @endif
            </div>
        @endif
    </div>

    {{-- Source banner --}}
    @if ($isCoverage)
        <div class="mt-4 flex items-center gap-2 rounded-xl border border-primary/30 bg-primary/10 p-3 text-xs font-semibold text-primary">
            <x-ui.icon name="refresh-cw" class="h-4 w-4" /> Vaga de cobertura criada por um colega motoboy
        </div>
    @else
        <div class="mt-4 flex items-center gap-2 rounded-xl border border-cyan-400/40 bg-cyan-400/10 p-3 text-xs font-semibold text-cyan-300">
            <x-ui.icon name="store" class="h-4 w-4" /> Vaga publicada por um estabelecimento
        </div>
    @endif

    {{-- Creator: manage shift --}}
    @if ($isCreator)
        <div class="mt-4 rounded-2xl border border-border bg-card p-3">
            <h3 class="mb-2 text-sm font-semibold">Gerenciar vaga</h3>
            <div class="grid grid-cols-3 gap-2">
                <a href="{{ route('shifts.edit', $shift->id) }}" wire:navigate>
                    <x-ui.button variant="outline" size="sm" class="w-full"><x-ui.icon name="pencil" class="mr-1 h-3.5 w-3.5" /> Editar</x-ui.button>
                </a>
                <x-ui.button variant="outline" size="sm" class="w-full" wire:click="toggleActive">
                    <x-ui.icon :name="$shift->active ? 'pause' : 'play'" class="mr-1 h-3.5 w-3.5" /> {{ $shift->active ? 'Pausar' : 'Reativar' }}
                </x-ui.button>
                <x-ui.button variant="outline" size="sm" class="w-full !text-destructive" wire:click="$set('confirmDeleteOpen', true)">
                    <x-ui.icon name="trash" class="mr-1 h-3.5 w-3.5" /> Excluir
                </x-ui.button>
            </div>
            @unless ($shift->active)
                <p class="mt-2 rounded-lg bg-muted/40 px-3 py-2 text-center text-[11px] font-medium text-muted-foreground">Esta vaga está pausada e não aparece na listagem.</p>
            @endunless
        </div>
    @endif

    @if ($shift->expected_volume)
        <div class="mt-4 rounded-xl border border-border bg-card p-3 text-sm">
            <span class="text-xs text-muted-foreground">Movimento esperado</span>
            <p class="font-semibold">{{ Catalog::VOLUME_LABEL[$shift->expected_volume] ?? $shift->expected_volume }}</p>
        </div>
    @endif

    {{-- Vehicles --}}
    <div class="mt-5">
        <h3 class="mb-2 text-sm font-semibold">Veículos aceitos</h3>
        <div class="flex flex-wrap gap-2">
            @if ($noRestriction)
                <span class="flex items-center gap-1.5 rounded-full border border-border bg-surface px-3 py-1 text-xs font-semibold">
                    <x-ui.icon name="bike" class="h-3 w-3 text-primary" /> Todos os veículos
                </span>
            @else
                @foreach ($acceptedVehicles as $v)
                    <span class="flex items-center gap-1.5 rounded-full border border-border bg-surface px-3 py-1 text-xs font-semibold">
                        <x-ui.icon name="bike" class="h-3 w-3 text-primary" /> {{ Catalog::VEHICLE_LABEL[$v] ?? $v }}
                    </span>
                @endforeach
            @endif
        </div>
    </div>

    {{-- Bag --}}
    <div class="mt-5">
        <h3 class="mb-2 text-sm font-semibold">Exigência de Bag</h3>
        <div class="flex items-center gap-2 rounded-xl border p-3 text-xs font-semibold {{ $requiresBag ? 'border-primary/40 bg-primary/10 text-primary' : 'border-border/60 bg-surface text-foreground/80' }}">
            <x-ui.icon :name="$requiresBag ? 'package' : 'package-x'" class="h-4 w-4" />
            {{ $requiresBag ? 'Exigência: Precisa de Bag própria' : 'Exigência: Não precisa de Bag' }}
        </div>
    </div>

    {{-- Key info --}}
    <div class="mt-5 grid grid-cols-2 gap-3">
        <x-shift-info label="Diária" :value="'R$ ' . ($shift->daily_rate + 0)" highlight />
        <x-shift-info label="Taxa por entrega" :value="'R$ ' . number_format($shift->delivery_fee_min, 2, ',', '.') . ' a R$ ' . number_format($shift->delivery_fee_max, 2, ',', '.')" />
        <x-shift-info label="Data" :value="$shift->date->isoFormat('DD [de] MMM')" />
        <x-shift-info label="Horário" :value="$shift->start_time . ' – ' . $shift->end_time" icon="clock" />
    </div>

    {{-- Benefits --}}
    @if (count($shift->benefits ?? []) > 0)
        <div class="mt-5">
            <h3 class="mb-2 text-sm font-semibold">Benefícios</h3>
            <div class="flex flex-wrap gap-2">
                @foreach ($shift->benefits as $b)
                    <span class="flex items-center gap-1.5 rounded-xl bg-secondary px-3 py-1.5 text-xs font-medium">
                        <x-ui.icon :name="Catalog::BENEFIT_ICON[$b] ?? 'check'" class="h-4 w-4 text-primary" />
                        {{ Catalog::BENEFIT_LABEL[$b] ?? $b }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Notes --}}
    @if ($shift->notes)
        <div class="mt-5">
            <h3 class="mb-1 text-sm font-semibold">Observações</h3>
            <p class="rounded-xl border border-border bg-card p-3 text-sm text-muted-foreground">{{ $shift->notes }}</p>
        </div>
    @endif

    {{-- Contact (accepted only) --}}
    @if ($contact && ($contact->contact_name || $contact->whatsapp_phone))
        <div class="mt-5 rounded-xl border border-border bg-card p-3">
            <h3 class="mb-2 text-sm font-semibold">Contato no local</h3>
            @if ($contact->contact_name)
                <p class="flex items-center gap-2 text-sm"><x-ui.icon name="user-round" class="h-4 w-4 text-primary" />{{ $contact->contact_name }}</p>
            @endif
            @if ($contact->whatsapp_phone)
                <p class="mt-1 flex items-center gap-2 text-sm"><x-ui.icon name="phone" class="h-4 w-4 text-primary" />{{ $contact->whatsapp_phone }}</p>
            @endif
        </div>
    @endif

    {{-- Creator --}}
    <div class="mt-5 flex items-center gap-3 rounded-2xl border border-border bg-card p-3">
        @if ($creatorProfile?->photo_url)
            <img src="{{ $creatorProfile->photo_url }}" alt="" class="h-12 w-12 rounded-full bg-secondary object-cover" />
        @else
            <span class="grid h-12 w-12 place-items-center rounded-full bg-secondary text-sm font-bold text-muted-foreground">{{ Str::upper(Str::substr($creatorName, 0, 1)) }}</span>
        @endif
        <div class="min-w-0 flex-1">
            <div class="text-xs text-muted-foreground">Publicado por</div>
            <div class="truncate text-sm font-semibold">{{ $creatorName }}</div>
            @if ($creatorProfile?->city)
                <div class="truncate text-xs text-muted-foreground">{{ $creatorProfile->city }}</div>
            @endif
        </div>
    </div>

    {{-- Creator: interested couriers --}}
    @if ($isCreator)
        <div class="mt-6">
            <div class="mb-2 flex items-center justify-between">
                <h3 class="text-sm font-semibold">Motoboys interessados <span class="text-muted-foreground">({{ $interested->count() }})</span></h3>
                @if ($interested->count() > 0)
                    <a href="{{ route('chats.index') }}" wire:navigate class="flex items-center gap-1 rounded-full bg-primary/15 px-2.5 py-1 text-[11px] font-semibold text-primary transition hover:bg-primary/25">
                        <x-ui.icon name="users" class="h-3 w-3" /> Gerenciar
                    </a>
                @endif
            </div>
            @if ($interested->count() === 0)
                <p class="text-sm text-muted-foreground">Ainda ninguém demonstrou interesse.</p>
            @else
                <div class="space-y-2">
                    @foreach ($interested as $cand)
                        @php $cp = $cand['profile']; $cn = $cp?->name ?: 'Usuário'; @endphp
                        <button type="button" wire:key="cand-{{ $cand['id'] }}" wire:click="$dispatch('open-profile', { userId: '{{ $cand['id'] }}' })"
                            class="flex w-full items-center gap-3 rounded-xl border border-border bg-card p-3 text-left transition hover:border-primary/40 active:scale-[.99]">
                            @if ($cp?->photo_url)
                                <img src="{{ $cp->photo_url }}" alt="" class="h-10 w-10 rounded-full bg-secondary object-cover" />
                            @else
                                <span class="grid h-10 w-10 place-items-center rounded-full bg-secondary text-xs font-bold text-muted-foreground">{{ Str::upper(Str::substr($cn, 0, 1)) }}</span>
                            @endif
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-semibold">{{ $cn }}</div>
                                <div class="flex flex-wrap items-center gap-x-2 text-[11px] text-muted-foreground">
                                    <span class="inline-flex items-center gap-0.5 font-semibold text-primary">
                                        <x-ui.icon name="star" class="h-3 w-3 fill-current" />
                                        {{ $cp && $cp->total_reviews > 0 ? number_format($cp->avg_rating, 1, ',', '') : '—' }}
                                    </span>
                                    @if ($cp && ($cp->city || $cp->district))
                                        <span class="inline-flex items-center gap-0.5">
                                            <x-ui.icon name="map-pin" class="h-3 w-3" />
                                            {{ collect([$cp->district, $cp->city])->filter()->join(' — ') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            @if ($cand['accepted'])
                                <span class="rounded-full bg-success/15 px-2 py-0.5 text-[10px] font-semibold text-success">Aceito</span>
                            @endif
                            <x-ui.icon name="chevron-right" class="h-4 w-4 text-muted-foreground" />
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- Creator: shift actions (expired) --}}
    @if ($isCreator && $expired)
        <div class="mt-6 space-y-2">
            <h3 class="text-sm font-semibold">Ações da vaga</h3>
            <a href="{{ route('shifts.create', ['clone' => $shift->id]) }}" wire:navigate>
                <x-ui.button variant="outline" size="lg" class="w-full"><x-ui.icon name="copy" class="mr-2 h-4 w-4" /> Anunciar Novamente</x-ui.button>
            </a>
            @if ($shift->reserved_by && $needed === 1)
                <x-ui.button size="lg" class="w-full" wire:click="$set('reviewOpen', true)"><x-ui.icon name="star" class="mr-2 h-4 w-4" /> Avaliar entregador</x-ui.button>
            @endif
        </div>
    @endif

    {{-- Sticky bottom action --}}
    <div class="app-shell fixed bottom-20 left-1/2 z-30 -translate-x-1/2 px-4">
        @if ($isCreator)
            <a href="{{ route('chats.index') }}" wire:navigate>
                <x-ui.button variant="outline" size="lg" class="w-full"><x-ui.icon name="message-circle" class="mr-2 h-4 w-4" /> Ver conversas</x-ui.button>
            </a>
        @elseif ($wasAccepted)
            <div class="space-y-2">
                <div class="rounded-xl border border-success/30 bg-success/15 p-3 text-center text-sm font-bold text-success">🎉 Você foi aceito nessa vaga!</div>
                <x-ui.button size="lg" class="w-full glow-orange" wire:click="openChat">💬 Abrir conversa</x-ui.button>
            </div>
        @elseif ($expired)
            <x-ui.button size="lg" variant="secondary" class="w-full" disabled><x-ui.icon name="clock" class="mr-2 h-4 w-4" /> Esse turno já passou</x-ui.button>
        @elseif (! $shift->active)
            <x-ui.button size="lg" variant="secondary" class="w-full" disabled><x-ui.icon name="pause" class="mr-2 h-4 w-4" /> Vaga pausada</x-ui.button>
        @elseif ($shift->status === 'filled' || $full)
            <x-ui.button size="lg" class="w-full" disabled>{{ $shift->status === 'filled' ? 'Vaga preenchida' : 'Vagas esgotadas' }}</x-ui.button>
        @elseif ($alreadyInterested)
            <x-ui.button size="lg" variant="secondary" class="w-full" disabled><x-ui.icon name="check" class="mr-2 h-4 w-4" /> Interesse registrado</x-ui.button>
        @elseif (! $compatible)
            <x-ui.button size="lg" variant="secondary" class="w-full" disabled><x-ui.icon name="lock" class="mr-2 h-4 w-4" /> Vaga exclusiva para {{ $requiredTypeLabel }}</x-ui.button>
        @elseif ($blockedByBag)
            <div class="space-y-2">
                <x-ui.button size="lg" variant="secondary" class="w-full" disabled><x-ui.icon name="lock" class="mr-2 h-4 w-4" /> Vaga indisponível</x-ui.button>
                <div class="flex items-start gap-2 rounded-xl border border-destructive/40 bg-destructive/10 p-3 text-[11px] font-medium">
                    <x-ui.icon name="alert-triangle" class="mt-0.5 h-4 w-4 shrink-0 text-destructive" />
                    <p class="text-foreground/90">Este estabelecimento exige que você leve sua própria Mochila Térmica (Bag). Para aceitar, acesse "Meu Perfil" e ative a opção de que possui o equipamento.</p>
                </div>
            </div>
        @else
            <x-ui.button size="lg" class="w-full glow-orange" wire:click="$set('confirmOpen', true)">Aceitar Vaga</x-ui.button>
        @endif
    </div>
    <div class="h-16"></div>

    {{-- Confirm dialog --}}
    @if ($confirmOpen)
        <x-ui.modal wire:click.self="$set('confirmOpen', false)">
            <h2 class="font-display text-lg font-bold">Confirmar aceitação</h2>
            <div class="mt-3 space-y-2">
                <p class="flex gap-2 rounded-xl border border-primary/30 bg-primary/10 p-3 text-xs font-medium text-primary">
                    <x-ui.icon name="alert-triangle" class="mt-0.5 h-4 w-4 shrink-0" />
                    <span>Confirme que você realmente irá realizar as entregas utilizando o veículo: <strong class="font-semibold">{{ $userVehicleLabel }}</strong>.</span>
                </p>
                @if ($requiresBag)
                    <p class="flex gap-2 rounded-xl border border-primary/30 bg-primary/10 p-3 text-xs font-medium text-primary">
                        <x-ui.icon name="package" class="mt-0.5 h-4 w-4 shrink-0" />
                        <span>E confirme que você possui e está levando sua Mochila Térmica (Bag) própria.</span>
                    </p>
                @endif
            </div>
            <div class="mt-4 flex flex-col gap-2">
                <x-ui.button size="lg" class="w-full glow-orange" wire:click="registerInterest">Confirmar e aceitar vaga</x-ui.button>
                <a href="{{ route('vehicle') }}" wire:navigate class="py-2 text-center text-sm text-muted-foreground hover:text-foreground">Alterar veículo</a>
            </div>
        </x-ui.modal>
    @endif

    {{-- Review dialog --}}
    @if ($reviewOpen)
        <x-ui.modal wire:click.self="$set('reviewOpen', false)">
            <h2 class="font-display text-lg font-bold">Avaliar entregador</h2>
            <p class="text-sm text-muted-foreground">Dê uma nota de 1 a 5 estrelas para essa parceria.</p>
            <div class="flex justify-center gap-1 py-3">
                @for ($i = 1; $i <= 5; $i++)
                    <button type="button" wire:click="setRating({{ $i }})">
                        <x-ui.icon name="star" class="h-8 w-8 {{ $i <= $rating ? 'text-primary fill-current' : 'text-muted-foreground/40' }}" />
                    </button>
                @endfor
            </div>
            <x-ui.textarea wire:model="comment" placeholder="Comentário (opcional)" rows="3" />
            <div class="mt-4">
                <x-ui.button size="lg" class="w-full" wire:click="submitReview" :disabled="$rating === 0">Enviar avaliação</x-ui.button>
            </div>
        </x-ui.modal>
    @endif

    {{-- Delete confirmation --}}
    @if ($confirmDeleteOpen)
        <x-ui.modal wire:click.self="$set('confirmDeleteOpen', false)">
            <h2 class="font-display text-lg font-bold">Excluir vaga</h2>
            <p class="mt-2 text-sm text-muted-foreground">Tem certeza? Esta ação não pode ser desfeita e remove as candidaturas e conversas vinculadas.</p>
            <div class="mt-4 flex justify-end gap-2">
                <x-ui.button variant="outline" wire:click="$set('confirmDeleteOpen', false)">Cancelar</x-ui.button>
                <x-ui.button variant="destructive" wire:click="deleteShift">Excluir</x-ui.button>
            </div>
        </x-ui.modal>
    @endif

    <livewire:profile-modal />
</div>
