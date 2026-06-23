@php
    use Illuminate\Support\Str;
    use Illuminate\Support\Carbon;
    $chat = $item['chat'];
    $other = $item['other'];
    $last = $item['last'];
    $shift = $item['shift'];
    $expired = $item['expired'];
    $name = $other?->name ?: 'Usuário';
    $otherId = $chat->otherParticipant(auth()->id());
@endphp
<a href="{{ route('chats.show', $chat->id) }}" wire:navigate wire:key="conv-{{ $chat->id }}"
    class="flex items-center gap-3 rounded-2xl border border-border bg-card p-3 transition hover:border-primary/40 {{ $expired ? 'opacity-70' : '' }}">
    <button type="button" @click.stop.prevent="$dispatch('open-profile', { userId: '{{ $otherId }}' })"
        class="shrink-0 rounded-full transition active:scale-95">
        @if ($other?->photo_url)
            <img src="{{ $other->photo_url }}" alt="" class="h-12 w-12 rounded-full bg-secondary object-cover" />
        @else
            <span class="grid h-12 w-12 place-items-center rounded-full bg-secondary text-sm font-bold text-muted-foreground">{{ Str::upper(Str::substr($name, 0, 1)) }}</span>
        @endif
    </button>
    <div class="min-w-0 flex-1">
        <div class="flex items-baseline justify-between gap-2">
            <span class="truncate text-sm font-semibold">{{ $name }}</span>
            @if ($last)
                <span class="shrink-0 text-[10px] text-muted-foreground">{{ Carbon::parse($last->created_at)->format('H:i') }}</span>
            @endif
        </div>
        <div class="truncate text-xs text-muted-foreground">
            {{ $last ? $last->body : ($shift ? 'Sobre: '.$shift->venue : 'Nova conversa') }}
        </div>
        @if ($expired && $shift)
            <div class="mt-0.5 inline-flex items-center gap-1 text-[10px] font-semibold text-muted-foreground">
                <x-ui.icon name="clock" class="h-3 w-3" /> Encerrada · {{ $shift->end_time }}
            </div>
        @endif
    </div>
</a>
