@php
    use App\Support\Catalog;
    use Illuminate\Support\Str;
@endphp
<div>
    @if ($userId && $this->data)
        @php
            $pp = $this->data['profile'];
            $reviews = $this->data['reviews'];
        @endphp
        <x-ui.modal wire:click.self="close">
            <h2 class="font-display text-lg font-bold">Perfil do entregador</h2>
            @if (! $pp)
                <div class="py-8 text-center text-sm text-muted-foreground">Carregando…</div>
            @else
                <div class="mt-3 space-y-4">
                    <div class="flex items-center gap-3">
                        @if ($pp->photo_url)
                            <img src="{{ $pp->photo_url }}" alt="" class="h-16 w-16 rounded-full border-2 border-primary/40 bg-secondary object-cover" />
                        @else
                            <span class="grid h-16 w-16 place-items-center rounded-full border-2 border-primary/40 bg-secondary text-xl font-bold text-muted-foreground">{{ Str::upper(Str::substr($pp->name ?: 'U', 0, 1)) }}</span>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-base font-bold">{{ $pp->name ?: 'Usuário' }}</p>
                            <div class="mt-1 flex items-center gap-1.5">
                                <div class="flex">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <x-ui.icon name="star" class="h-3.5 w-3.5 {{ $i <= round($pp->avg_rating) ? 'text-primary fill-current' : 'text-muted-foreground/30' }}" />
                                    @endfor
                                </div>
                                <span class="text-[11px] text-muted-foreground">
                                    {{ $pp->total_reviews > 0 ? number_format($pp->avg_rating, 1, ',', '') . ' · ' . $pp->total_reviews . ' ' . ($pp->total_reviews === 1 ? 'avaliação' : 'avaliações') : 'Sem avaliações' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    @if ($pp->city || $pp->district)
                        <div class="flex items-center gap-2 rounded-xl border border-border/60 bg-surface px-3 py-2 text-xs">
                            <x-ui.icon name="map-pin" class="h-3.5 w-3.5 text-primary" />
                            <span class="truncate">{{ collect([$pp->district, $pp->city])->filter()->join(' — ') }}</span>
                        </div>
                    @endif

                    @if ($pp->bio)
                        <div>
                            <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Sobre</p>
                            <p class="rounded-xl border border-border/60 bg-surface p-3 text-sm">{{ $pp->bio }}</p>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-2">
                        <div class="rounded-xl border border-border/60 bg-surface p-2.5">
                            <div class="flex items-center gap-1.5 text-[10px] uppercase tracking-wide text-muted-foreground"><x-ui.icon name="bike" class="h-3 w-3" /> Veículo</div>
                            <div class="mt-0.5 truncate text-xs font-semibold">{{ $pp->vehicle ? (Catalog::VEHICLE_LABEL[$pp->vehicle] ?? $pp->vehicle) : 'Não informado' }}</div>
                        </div>
                        <div class="rounded-xl border border-border/60 bg-surface p-2.5">
                            <div class="flex items-center gap-1.5 text-[10px] uppercase tracking-wide text-muted-foreground"><x-ui.icon name="package" class="h-3 w-3" /> Bag própria</div>
                            <div class="mt-0.5 text-xs font-semibold">{{ $pp->has_bag ? 'Sim' : 'Não' }}</div>
                        </div>
                    </div>

                    <div>
                        <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Histórico de notas</p>
                        @if ($reviews->isEmpty())
                            <p class="rounded-xl border border-dashed border-border/60 px-3 py-4 text-center text-xs text-muted-foreground">Sem avaliações ainda.</p>
                        @else
                            <div class="space-y-1.5">
                                @foreach ($reviews as $r)
                                    <div class="rounded-xl border border-border/60 bg-surface p-2.5">
                                        <div class="flex items-center justify-between">
                                            <div class="flex">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    <x-ui.icon name="star" class="h-3 w-3 {{ $i <= $r->rating ? 'text-primary fill-current' : 'text-muted-foreground/30' }}" />
                                                @endfor
                                            </div>
                                            <span class="text-[10px] text-muted-foreground">{{ \Illuminate\Support\Carbon::parse($r->created_at)->format('d/m/Y') }}</span>
                                        </div>
                                        @if ($r->comment)
                                            <p class="mt-1 flex items-start gap-1.5 text-xs text-muted-foreground">
                                                <x-ui.icon name="message-square" class="mt-0.5 h-3 w-3 shrink-0" />
                                                <span>{{ $r->comment }}</span>
                                            </p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </x-ui.modal>
    @endif
</div>
