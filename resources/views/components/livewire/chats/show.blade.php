{{-- resources/views/livewire/chats/show.blade.php --}}
{{-- Polling a cada 3s para simular tempo real (substituir por Echo/Pusher em produção) --}}
<div class="flex flex-col h-dvh" wire:poll.3s>

    {{-- Header --}}
    <header class="flex items-center gap-3 border-b border-[#2a2a2a] bg-[#0d0d0d]/90 px-4 py-3 backdrop-blur shrink-0">
        <a href="{{ route('chats.index') }}"
           class="grid h-9 w-9 place-items-center rounded-xl border border-[#2a2a2a] bg-[#161616]">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
        </a>
        <img src="{{ $outro?->foto_url ?? 'https://ui-avatars.com/api/?name='.urlencode($outro?->nome ?? '?').'&background=f97316&color=fff' }}"
             alt="" class="h-9 w-9 rounded-full bg-[#1f1f1f] object-cover" />
        <div class="min-w-0 flex-1">
            <p class="truncate font-semibold text-sm">{{ $outro?->nome ?? '—' }}</p>
            <p class="truncate text-[10px] text-[#737373]">{{ $chat->vaga?->local ?? '' }}</p>
        </div>
    </header>

    {{-- Mensagens --}}
    <div class="flex-1 overflow-y-auto px-4 py-4 space-y-2" id="msgs">
        @forelse($mensagens as $msg)
            @php $minha = $msg->autor_id === $userId; @endphp
            <div class="flex {{ $minha ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[78%] rounded-2xl px-4 py-2.5 text-sm
                            {{ $minha ? 'rounded-br-sm bg-[#f97316] text-white' : 'rounded-bl-sm bg-[#1e1e1e] text-[#f5f5f5]' }}">
                    <p>{{ $msg->texto }}</p>
                    <p class="mt-1 text-[10px] {{ $minha ? 'text-white/60' : 'text-[#737373]' }} text-right">
                        {{ $msg->created_at?->format('H:i') }}
                    </p>
                </div>
            </div>
        @empty
            <p class="text-center text-sm text-[#737373] py-8">Comece a conversa!</p>
        @endforelse
    </div>

    {{-- Input de mensagem --}}
    <div class="shrink-0 border-t border-[#2a2a2a] bg-[#0d0d0d] px-4 py-3 pb-safe">
        <div class="flex items-end gap-2">
            <textarea wire:model="texto"
                      wire:keydown.enter.prevent="enviar"
                      placeholder="Digite uma mensagem…"
                      rows="1"
                      class="flex-1 resize-none rounded-2xl bg-[#1e1e1e] border border-[#2a2a2a] px-4 py-2.5 text-sm focus:border-[#f97316]/60 focus:ring-0"
                      style="max-height:120px"></textarea>
            <button wire:click="enviar"
                    wire:loading.attr="disabled"
                    class="tap grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[#f97316] text-white disabled:opacity-50 glow-orange">
                <svg class="h-4 w-4 rotate-90" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                </svg>
            </button>
        </div>
    </div>

</div>

@push('scripts')
<script>
    // Auto-scroll para o final das mensagens
    function scrollToBottom() {
        const el = document.getElementById('msgs');
        if (el) el.scrollTop = el.scrollHeight;
    }
    document.addEventListener('livewire:navigated', scrollToBottom);
    document.addEventListener('livewire:update', scrollToBottom);
    scrollToBottom();
</script>
@endpush
