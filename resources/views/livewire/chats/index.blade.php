{{-- resources/views/livewire/chats/index.blade.php --}}
<div class="pb-nav">
    <header class="sticky top-0 z-10 flex items-center gap-3 border-b border-[#2a2a2a] bg-[#0d0d0d]/90 px-4 py-4 backdrop-blur">
        <h1 class="text-xl font-bold">Chats</h1>
    </header>

    <div class="px-4 pt-4 space-y-2">
        @forelse($chats as $chat)
            @php
                $outroId  = $chat->outroParticipante($userId);
                $outro    = $profiles[$outroId] ?? null;
                $ultima   = $chat->mensagens->first();
                $nomeOutro = $outro?->nome ?? '—';
                $fotoOutro = $outro?->foto_url ?? 'https://ui-avatars.com/api/?name='.urlencode($nomeOutro).'&background=f97316&color=fff';
            @endphp
            <a href="{{ route('chats.show', $chat->id) }}"
               class="tap flex items-center gap-3 rounded-2xl border border-[#2a2a2a] bg-[#1a1a1a] p-4 transition hover:border-[#f97316]/30">
                <img src="{{ $fotoOutro }}" alt="" class="h-12 w-12 shrink-0 rounded-full bg-[#1f1f1f] object-cover" />
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between">
                        <p class="truncate font-semibold text-sm">{{ $nomeOutro }}</p>
                        @if($ultima)
                            <span class="shrink-0 text-[10px] text-[#737373]">
                                {{ $ultima->created_at?->diffForHumans(null, true) }}
                            </span>
                        @endif
                    </div>
                    <p class="mt-0.5 truncate text-xs text-[#737373]">
                        {{ $ultima?->texto ?? 'Vaga: '.$chat->vaga?->local }}
                    </p>
                </div>
            </a>
        @empty
            <div class="rounded-2xl border border-dashed border-[#2a2a2a] p-10 text-center text-sm text-[#737373]">
                <svg class="mx-auto mb-2 h-6 w-6 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                </svg>
                Nenhuma conversa ainda.
            </div>
        @endforelse
    </div>
</div>
