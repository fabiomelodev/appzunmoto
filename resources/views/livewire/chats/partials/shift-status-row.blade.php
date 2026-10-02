@php
    // $mode: 'published' | 'worked' (Histórico de turnos) | 'interested' (Vagas interessadas).
    $myApp = ($myApplications ?? collect())[$v->id] ?? null;
    $expired = $v->hasEnded();

    if ($mode === 'interested') {
        $statusLabel = $expired ? 'Expirada' : 'Em análise';
        $statusClass = $expired ? 'text-muted-foreground' : 'text-primary';
    } else {
        $completed = $mode === 'worked' ? (bool) $myApp?->confirmed : $v->status === 'filled';
        $statusLabel = $completed ? 'Concluída' : ($expired ? 'Expirada' : 'Ativa');
        $statusClass = $completed ? 'text-sky-400' : ($expired ? 'text-muted-foreground' : 'text-success');
    }
@endphp
<a href="{{ route('shifts.show', $v->id) }}" wire:navigate wire:key="{{ $mode }}-{{ $v->id }}"
    class="flex items-center gap-3 rounded-2xl border border-border/60 bg-card p-4 transition hover:border-primary/40 active:scale-[.99]">
    <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-semibold">{{ $v->venue }}</p>
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
