@php
    use Illuminate\Support\Carbon;
    $icons = [
        'turno' => 'calendar-check',
        'mensagem' => 'message-square',
        'documento' => 'file-check',
        'sistema' => 'shield-alert',
        'vaga' => 'calendar-check',
    ];
@endphp

<div class="min-h-dvh">
    {{-- Header --}}
    <header class="sticky top-0 z-10 flex items-center gap-3 border-b border-border bg-background/85 px-4 py-4 backdrop-blur">
        <button type="button" x-on:click="window.history.back()" aria-label="Voltar"
            class="grid h-9 w-9 place-items-center rounded-xl border border-border bg-surface text-foreground">
            <x-ui.icon name="arrow-left" class="h-4 w-4" />
        </button>
        <div class="flex-1">
            <h1 class="flex items-center gap-2 font-display text-xl font-bold"><x-ui.icon name="bell" class="h-5 w-5 text-primary" /> Notificações</h1>
            <p class="text-xs text-muted-foreground">Avisos, turnos e atualizações recentes</p>
        </div>
        <button type="button" wire:click="markAllRead"
            class="flex items-center gap-1 rounded-lg border border-border bg-surface px-2.5 py-1.5 text-[11px] font-semibold text-muted-foreground transition hover:text-foreground">
            <x-ui.icon name="check-circle" class="h-3.5 w-3.5" /> Marcar lidas
        </button>
    </header>

    <div class="space-y-2 px-4 pt-4">
        @forelse ($this->notifications as $n)
            <button type="button" wire:click="open('{{ $n->id }}')" wire:key="notif-{{ $n->id }}"
                class="flex w-full gap-3 rounded-2xl border p-4 text-left transition active:scale-[.99] hover:border-primary/40 {{ $n->read ? 'border-border/60 bg-card/60' : 'border-primary/30 bg-card shadow-sm' }}">
                <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl {{ $n->read ? 'bg-surface text-muted-foreground' : 'bg-primary/15 text-primary' }}">
                    <x-ui.icon :name="$icons[$n->type] ?? 'shield-alert'" class="h-5 w-5" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-sm font-semibold text-foreground">{{ $n->title }}</p>
                        @unless ($n->read)
                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-primary"></span>
                        @endunless
                    </div>
                    <p class="mt-0.5 text-xs text-muted-foreground">{{ $n->description }}</p>
                    <p class="mt-1.5 text-[10px] uppercase tracking-wide text-muted-foreground/70">
                        {{ Carbon::parse($n->created_at)->diffForHumans() }}
                    </p>
                </div>
            </button>
        @empty
            <div class="rounded-2xl border border-dashed border-border p-10 text-center text-sm text-muted-foreground">
                Nenhuma notificação ainda.
            </div>
        @endforelse
    </div>
</div>
