@php
    use Illuminate\Support\Str;
    use Illuminate\Support\Carbon;
    $otherName = $other?->name ?: 'Usuário';
    $firstName = Str::of($otherName)->explode(' ')->first();
@endphp

<div class="flex min-h-[calc(100dvh-5rem)] flex-col" wire:poll.6s>
    {{-- Header --}}
    <header class="sticky top-0 z-10 flex items-center gap-3 border-b border-border bg-surface/95 px-4 py-3 backdrop-blur">
        <button type="button" x-on:click="window.history.back()" class="grid h-9 w-9 place-items-center rounded-lg">
            <x-ui.icon name="arrow-left" class="h-4 w-4" />
        </button>
        <button type="button" wire:click="$dispatch('open-profile', { userId: '{{ $otherId }}' })" class="shrink-0 rounded-full transition active:scale-95">
            @if ($other?->photo_url)
                <img src="{{ $other->photo_url }}" alt="" class="h-10 w-10 rounded-full bg-secondary object-cover" />
            @else
                <span class="grid h-10 w-10 place-items-center rounded-full bg-secondary text-sm font-bold text-muted-foreground">{{ Str::upper(Str::substr($otherName, 0, 1)) }}</span>
            @endif
        </button>
        <button type="button" wire:click="$dispatch('open-profile', { userId: '{{ $otherId }}' })" class="min-w-0 flex-1 text-left">
            <div class="truncate text-sm font-semibold hover:text-primary">{{ $otherName }}</div>
        </button>
    </header>

    {{-- Messages --}}
    <div class="flex-1 space-y-2 overflow-y-auto px-4 py-4"
        x-data="{ stick() { this.$el.scrollTop = this.$el.scrollHeight } }"
        x-init="$nextTick(() => stick()); new MutationObserver(() => stick()).observe($el, { childList: true, subtree: true })">
        @if ($messages->isEmpty())
            <div class="mx-auto mt-10 max-w-xs rounded-2xl bg-surface px-4 py-3 text-center text-xs text-muted-foreground">
                Diga olá para {{ $firstName }} 👋
            </div>
        @endif
        @foreach ($messages as $m)
            @php $mine = $m->author_id === $me; @endphp
            <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[78%] rounded-2xl px-3.5 py-2 text-sm {{ $mine ? 'rounded-br-md bg-primary text-primary-foreground' : 'rounded-bl-md bg-surface text-foreground' }}">
                    <div class="whitespace-pre-wrap break-words">{{ $m->body }}</div>
                    <div class="mt-0.5 text-right text-[9px] {{ $mine ? 'text-primary-foreground/70' : 'text-muted-foreground' }}">{{ Carbon::parse($m->created_at)->format('H:i') }}</div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Review panel --}}
    @if ($canReview)
        <div class="space-y-2 border-t border-border bg-surface/80 px-4 py-3">
            <p class="text-xs font-semibold">Vaga concluída — avalie {{ $firstName }}:</p>
            <div class="flex gap-1">
                @for ($i = 1; $i <= 5; $i++)
                    <button type="button" wire:click="setRating({{ $i }})">
                        <x-ui.icon name="star" class="h-7 w-7 {{ $i <= $rating ? 'text-primary fill-current' : 'text-muted-foreground/40' }}" />
                    </button>
                @endfor
            </div>
            <x-ui.textarea wire:model="comment" placeholder="Comentário (opcional)" rows="2" />
            <x-ui.button size="sm" class="w-full" wire:click="submitReview" :disabled="$rating === 0">Enviar avaliação</x-ui.button>
        </div>
    @endif

    {{-- Partnership panel --}}
    @if ($shift)
        <div class="space-y-2 border-t border-border bg-surface/80 px-4 py-2.5">
            @if ($conflict && ! $confirmedHere)
                <div class="flex items-start gap-2 rounded-xl bg-amber-500/10 px-3 py-2 text-[11px] font-medium text-amber-300">
                    <x-ui.icon name="alert-triangle" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                    <span>
                        @if (! $isCreator)
                            Você já possui uma parceria confirmada neste mesmo horário com "{{ $conflict->venue }}".
                        @else
                            Este motoboy já confirmou parceria com outra vaga neste mesmo horário.
                        @endif
                    </span>
                </div>
            @endif

            @if ($confirmedHere)
                <div class="flex items-center justify-center gap-2 rounded-xl bg-success/15 px-3 py-2 text-xs font-semibold text-success">
                    <x-ui.icon name="check-circle" class="h-4 w-4" /> Parceria confirmada
                </div>
            @elseif (! $courierAccepted)
                <p class="text-center text-[11px] text-muted-foreground">Aguardando ser aceito na vaga: <span class="text-foreground">{{ $shift->venue }}</span></p>
            @else
                @php
                    $statusMsg = $alreadyConfirmed
                        ? ($otherConfirmed ? 'Aguardando processamento…' : ($isCreator ? 'Aguardando confirmação do motoboy' : 'Aguardando confirmação do contratante'))
                        : $confirmCount.'/2 confirmações';
                    $blocked = $conflict || $expired;
                @endphp
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate text-[11px] font-semibold">{{ $shift->venue }}</p>
                        <p class="text-[10px] text-muted-foreground">{{ $statusMsg }}</p>
                    </div>
                    <x-ui.button size="sm" class="shrink-0" wire:click="confirmPartnership" :disabled="$alreadyConfirmed || $blocked">
                        <x-ui.icon name="handshake" class="mr-1.5 h-3.5 w-3.5" />
                        {{ $alreadyConfirmed ? 'Aguardando' : 'Confirmar Parceria' }}
                    </x-ui.button>
                </div>
            @endif
        </div>
    @endif

    {{-- Composer --}}
    <form wire:submit="send" class="flex items-center gap-2 border-t border-border bg-surface/95 p-3">
        <input type="text" wire:model="body" @disabled($expired)
            placeholder="{{ $expired ? 'Conversa encerrada (somente leitura)' : 'Mensagem' }}"
            class="h-10 flex-1 rounded-md border border-input bg-transparent px-3 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:opacity-50" />
        <button type="submit" @disabled($expired)
            class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-primary text-primary-foreground disabled:opacity-50">
            <x-ui.icon name="send" class="h-4 w-4" />
        </button>
    </form>

    <livewire:profile-modal />
</div>
