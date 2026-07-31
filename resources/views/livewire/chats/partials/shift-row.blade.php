@php
    use Illuminate\Support\Str;
    $apps = $shift->applications;
    $accepted = $apps->where('status', 'accepted')->values();
    $interested = $apps->where('status', 'interested')->values();
    $needed = $shift->couriers_needed ?? 1;
    $acceptedCount = $accepted->count();
    $full = $acceptedCount >= $needed;
    $confirmedCount = $accepted->where('confirmed', true)->count();
    $ordered = $accepted->concat($interested);
    $isOpen = $openShift === $shift->id;
    $badge = $needed > 1 ? $acceptedCount.'/'.$needed : $interested->count();
@endphp
<div wire:key="shiftrow-{{ $shift->id }}" class="overflow-hidden rounded-2xl border border-border bg-card {{ $expired ? 'opacity-70' : '' }}">
    <button type="button" wire:click="toggleShift('{{ $shift->id }}')"
        class="flex w-full items-center gap-3 p-3 text-left transition hover:bg-surface-elevated">
        <div class="grid h-10 w-10 place-items-center rounded-xl bg-primary/10 text-primary"><x-ui.icon name="bike" class="h-4 w-4" /></div>
        <div class="min-w-0 flex-1">
            <div class="truncate text-sm font-semibold">{{ $shift->venue }}</div>
            <div class="text-[11px] text-muted-foreground">{{ $shift->region }} · {{ $shift->start_time }}–{{ $shift->end_time }}@if ($expired) · Encerrada @endif</div>
        </div>
        <span class="rounded-full bg-primary/15 px-2 py-0.5 text-[10px] font-bold text-primary">{{ $badge }}</span>
        <x-ui.icon name="chevron-right" class="h-4 w-4 text-muted-foreground transition {{ $isOpen ? 'rotate-90' : '' }}" />
    </button>

    @if ($isOpen)
        <div class="space-y-2 border-t border-border bg-surface/50 p-2">
            @if ($needed > 1)
                <div class="rounded-lg bg-primary/10 px-3 py-2 text-[11px] font-semibold text-primary">
                    {{ $confirmedCount }}/{{ $needed }} motoboys confirmados
                    <span class="ml-2 font-normal text-muted-foreground">· {{ $acceptedCount }} aceitos</span>
                </div>
            @endif

            @forelse ($ordered as $app)
                @php
                    $p = $app->user?->profile;
                    $cn = $p?->name ?: 'Usuário';
                    $first = Str::of($cn)->explode(' ')->first();
                    $isAcc = $app->status === 'accepted';
                @endphp
                <div class="space-y-2.5 rounded-xl border border-border/60 bg-card p-3">
                    <div class="flex items-start gap-3">
                        <button type="button" wire:click="$dispatch('open-profile', { userId: '{{ $app->user_id }}' })" class="shrink-0 rounded-full transition active:scale-95">
                            @if ($p?->photo_url)
                                <img src="{{ $p->photo_url }}" alt="" class="h-12 w-12 rounded-full border border-border bg-secondary object-cover" />
                            @else
                                <span class="grid h-12 w-12 place-items-center rounded-full border border-border bg-secondary text-sm font-bold text-muted-foreground">{{ Str::upper(Str::substr($cn, 0, 1)) }}</span>
                            @endif
                        </button>
                        <div class="min-w-0 flex-1">
                            <button type="button" wire:click="$dispatch('open-profile', { userId: '{{ $app->user_id }}' })" class="text-left">
                                <p class="truncate text-sm font-bold hover:text-primary">{{ $cn }}</p>
                            </button>
                            <div class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] text-muted-foreground">
                                <span class="inline-flex items-center gap-0.5 font-semibold text-primary">
                                    <x-ui.icon name="star" class="h-3 w-3 fill-current" />
                                    {{ $p && $p->total_reviews > 0 ? number_format($p->avg_rating, 1, ',', '') : '—' }}
                                    @if ($p && $p->total_reviews > 0)<span class="font-normal text-muted-foreground">({{ $p->total_reviews }})</span>@endif
                                </span>
                                @if ($p && ($p->city || $p->district))
                                    <span class="inline-flex items-center gap-0.5"><x-ui.icon name="map-pin" class="h-3 w-3" />{{ collect([$p->district, $p->city])->filter()->join(' — ') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if ($p?->bio)
                        <p class="line-clamp-3 rounded-lg bg-surface/60 px-2.5 py-1.5 text-[11px] text-muted-foreground">{{ $p->bio }}</p>
                    @endif

                    @if (! $expired && $isAcc)
                        <div class="flex items-center justify-center gap-1 rounded-lg bg-success/15 px-3 py-2 text-xs font-semibold text-success">
                            <x-ui.icon name="check" class="h-3.5 w-3.5" /> Aceito ✓
                        </div>
                    @elseif (! $expired && ! $full)
                        <div class="grid grid-cols-[1fr_auto] gap-2">
                            <button type="button" wire:click="acceptCandidate('{{ $shift->id }}', '{{ $app->user_id }}')"
                                class="flex items-center justify-center gap-1.5 rounded-lg bg-primary px-3 py-2 text-xs font-semibold text-primary-foreground transition hover:bg-primary/90">
                                ✅ Aceitar {{ $first }}
                            </button>
                            <button type="button" wire:click="requestDecline('{{ $shift->id }}', '{{ $app->user_id }}')"
                                class="rounded-lg border border-border bg-transparent px-3 py-2 text-xs font-semibold text-muted-foreground transition hover:border-destructive/40 hover:text-destructive">
                                Recusar
                            </button>
                        </div>
                    @elseif (! $expired && $full && ! $isAcc)
                        <p class="rounded-lg bg-muted/40 px-3 py-2 text-center text-[11px] font-medium text-muted-foreground">Essa vaga já está completa</p>
                    @endif

                    @if (! $expired && ($isAcc || ! $full))
                        <button type="button" wire:click="openChatWith('{{ $shift->id }}', '{{ $app->user_id }}')"
                            class="flex w-full items-center justify-center gap-1.5 rounded-lg bg-primary/10 px-3 py-2 text-xs font-semibold text-primary transition hover:bg-primary/20">
                            <x-ui.icon name="message-circle" class="h-3.5 w-3.5" /> Conversar com {{ $first }}
                        </button>
                    @endif
                </div>
            @empty
                <p class="py-3 text-center text-xs text-muted-foreground">Ainda ninguém se candidatou.</p>
            @endforelse
        </div>
    @endif
</div>
