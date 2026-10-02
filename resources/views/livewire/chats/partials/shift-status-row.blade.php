@php
    // $mode: 'published' | 'worked' (Histórico de turnos) | 'interested' (Vagas interessadas).
    // $chat (interested only): ['id' => ..., 'unread' => int] when the creator already wrote to this courier.
    $myApp = ($myApplications ?? collect())[$v->id] ?? null;
    $expired = $v->hasEnded();
    $chat = $chat ?? null;
    $unread = $chat['unread'] ?? 0;

    if ($mode === 'interested') {
        $statusLabel = $expired ? 'Expirada' : 'Em análise';
        $statusClass = $expired ? 'text-muted-foreground' : 'text-primary';
    } else {
        $completed = $mode === 'worked' ? (bool) $myApp?->confirmed : $v->status === 'filled';
        $statusLabel = $completed ? 'Concluída' : ($expired ? 'Expirada' : 'Ativa');
        $statusClass = $completed ? 'text-sky-400' : ($expired ? 'text-muted-foreground' : 'text-success');
    }
@endphp
{{-- Two sibling links (not nested): the row opens the shift, the side button opens the chat. --}}
<div class="flex items-stretch gap-2" wire:key="{{ $mode }}-{{ $v->id }}">
    <a href="{{ route('shifts.show', $v->id) }}" wire:navigate
        class="flex min-w-0 flex-1 items-center gap-3 rounded-2xl border p-4 transition hover:border-primary/40 active:scale-[.99] {{ $unread ? 'border-primary/50 bg-primary/5' : 'border-border/60 bg-card' }}">
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm {{ $unread ? 'font-bold' : 'font-semibold' }}">{{ $v->venue }}</p>
            <p class="mt-0.5 flex items-center gap-1 text-[11px] text-muted-foreground">
                <x-ui.icon name="clock" class="h-3 w-3" />
                {{ $v->date->isoFormat('DD [de] MMM') }} · {{ $v->timeRange() }}
            </p>
        </div>
        <div class="text-right">
            <div class="font-display text-base font-bold text-primary">R$ {{ $v->daily_rate + 0 }}</div>
            <span class="text-[10px] font-semibold uppercase {{ $statusClass }}">{{ $statusLabel }}</span>
        </div>
        <x-ui.icon name="chevron-right" class="h-4 w-4 text-muted-foreground" />
    </a>

    @if ($chat)
        <a href="{{ route('chats.show', $chat['id']) }}" wire:navigate aria-label="Abrir conversa"
            class="relative grid w-12 shrink-0 place-items-center rounded-2xl border border-primary/40 bg-primary/10 text-primary transition hover:bg-primary/20 active:scale-[.97]">
            <x-ui.icon name="message-circle" class="h-5 w-5" />
            <x-count-badge :count="$unread" class="absolute -right-1 -top-1" />
        </a>
    @endif
</div>
