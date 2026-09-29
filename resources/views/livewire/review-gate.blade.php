@php $current = $this->current; @endphp

{{-- Livewire always needs one root element, even with nothing to show. --}}
<div>
    @if ($current)
        {{-- z-[80]: above the notification toast (z-[60]) and every other modal
        (z-50) — intentionally non-dismissible: no backdrop click, no X button. --}}
        <div class="fixed inset-0 z-[80] flex items-end justify-center bg-black/70 p-4 sm:items-center">
            <div class="max-h-[85vh] w-full max-w-sm overflow-y-auto rounded-2xl border border-border bg-card p-5">
                <h2 class="font-display text-lg font-bold">Avalie para continuar</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    A vaga <strong class="text-foreground">{{ $current['shift']->venue }}</strong> foi concluída.
                    Avalie <strong class="text-foreground">{{ $current['name'] }}</strong> para voltar a usar o ZunMoto.
                </p>
                <p class="mt-1 text-[11px] text-muted-foreground">Por segurança, sua avaliação é anônima e só fica pública {{ \App\Models\Review::PUBLISH_DELAY_DAYS }} dias depois de enviada.</p>

                <div class="mt-4 flex justify-center gap-1">
                    @for ($i = 1; $i <= 5; $i++)
                        <button type="button" wire:click="setRating({{ $i }})">
                            <x-ui.icon name="star" class="h-8 w-8 {{ $i <= $rating ? 'text-primary fill-current' : 'text-muted-foreground/40' }}" />
                        </button>
                    @endfor
                </div>
                <x-ui.textarea wire:model="comment" placeholder="Comentário (opcional)" rows="3" class="mt-3" />

                <x-ui.button size="lg" class="mt-4 w-full" wire:click="submit" wire:loading.attr="disabled" wire:target="submit" :disabled="$rating === 0">
                    Enviar avaliação
                </x-ui.button>

                @if ($current['remaining'] > 1)
                    <p class="mt-2 text-center text-[11px] text-muted-foreground">
                        Mais {{ $current['remaining'] - 1 }} {{ $current['remaining'] - 1 === 1 ? 'avaliação pendente' : 'avaliações pendentes' }} depois desta.
                    </p>
                @endif
            </div>
        </div>
    @endif
</div>
