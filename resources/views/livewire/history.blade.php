@php
    use Illuminate\Support\Carbon;
@endphp

<div class="pb-6">
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-primary/25 via-primary/5 to-transparent"></div>
        <div class="relative flex items-center gap-3 px-4 pb-4 pt-6">
            <button type="button" x-on:click="window.history.back()"
                class="grid h-10 w-10 place-items-center rounded-full border border-border/60 bg-surface">
                <x-ui.icon name="arrow-left" class="h-5 w-5" />
            </button>
            <div>
                <h1 class="font-display text-xl font-bold leading-tight">Histórico de Turnos</h1>
                <p class="text-xs text-muted-foreground">Toque em uma vaga para ver detalhes</p>
            </div>
        </div>

        <div class="relative mx-4 mb-4 grid grid-cols-2 gap-1 rounded-xl border border-border bg-surface p-1">
            @foreach (['published' => 'Publiquei', 'worked' => 'Trabalhei'] as $value => $label)
                <button wire:click="setTab('{{ $value }}')"
                    class="rounded-lg px-3 py-2 text-xs font-semibold transition {{ $tab === $value ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="mt-2 space-y-2 px-4">
        @forelse ($shifts as $v)
            @php
                $myApp = $myApplications[$v->id] ?? null;
                $completed = $tab === 'worked' ? (bool) $myApp?->confirmed : $v->status === 'filled';
                $expired = Carbon::parse($v->date->toDateString().' '.$v->end_time)->isPast();
                $statusLabel = $completed ? 'Concluída' : ($expired ? 'Expirada' : 'Ativa');
                $statusClass = $completed ? 'text-sky-400' : ($expired ? 'text-muted-foreground' : 'text-success');
            @endphp
            <a href="{{ route('shifts.show', $v->id) }}" wire:navigate wire:key="hist-{{ $v->id }}"
                class="flex items-center gap-3 rounded-2xl border border-border/60 bg-card p-4 transition hover:border-primary/40 active:scale-[.99]">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold">{{ $v->venue }}</p>
                    <p class="mt-0.5 flex items-center gap-1 text-[11px] text-muted-foreground">
                        <x-ui.icon name="clock" class="h-3 w-3" />
                        {{ $v->date->isoFormat('DD [de] MMM') }} · {{ $v->start_time }}–{{ $v->end_time }}
                    </p>
                </div>
                <div class="text-right">
                    <div class="font-display text-base font-bold text-primary">R$ {{ $v->daily_rate + 0 }}</div>
                    <span class="text-[10px] font-semibold uppercase {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>
                <x-ui.icon name="chevron-right" class="h-4 w-4 text-muted-foreground" />
            </a>
        @empty
            <div class="rounded-2xl border border-dashed border-border/60 p-10 text-center text-sm text-muted-foreground">
                <x-ui.icon name="calendar" class="mx-auto mb-2 h-6 w-6 opacity-60" />
                {{ $tab === 'published' ? 'Você ainda não publicou nenhuma vaga.' : 'Você ainda não trabalhou em nenhuma vaga.' }}
            </div>
        @endforelse
    </div>
</div>
