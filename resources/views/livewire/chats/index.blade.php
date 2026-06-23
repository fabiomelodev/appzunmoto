@php
    $conversations = $this->conversations;
    $myShifts = $this->myShifts;
@endphp

<div class="px-4 pb-6 pt-6">
    {{-- Header --}}
    <div class="flex items-center gap-2">
        <span class="grid h-9 w-9 place-items-center rounded-xl bg-primary/15 text-primary">
            <x-ui.icon name="handshake" class="h-4 w-4" />
        </span>
        <div>
            <h1 class="font-display text-2xl font-bold leading-tight">Parcerias</h1>
            <p class="text-xs text-muted-foreground">Negocie e confirme suas vagas.</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="mt-5 grid h-11 w-full grid-cols-2 gap-1 rounded-xl bg-surface p-1">
        <button wire:click="setTab('conversas')"
            class="rounded-lg text-sm font-semibold transition {{ $tab === 'conversas' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground' }}">Conversas</button>
        <button wire:click="setTab('candidaturas')"
            class="rounded-lg text-sm font-semibold transition {{ $tab === 'candidaturas' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground' }}">Candidaturas</button>
    </div>

    @if ($tab === 'conversas')
        <div class="mt-4 space-y-4">
            <section>
                <x-section-title :count="$conversations['active']->count()">Conversas ativas</x-section-title>
                <div class="mt-2 space-y-2">
                    @forelse ($conversations['active'] as $item)
                        @include('livewire.chats.partials.conversation-row', ['item' => $item])
                    @empty
                        <x-empty-state icon="message-circle" text="Nenhuma conversa ativa." />
                    @endforelse
                </div>
            </section>

            @if ($conversations['expired']->isNotEmpty())
                <section>
                    <x-section-title :count="$conversations['expired']->count()">Histórico / Encerradas</x-section-title>
                    <div class="mt-2 space-y-2">
                        @foreach ($conversations['expired'] as $item)
                            @include('livewire.chats.partials.conversation-row', ['item' => $item])
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    @else
        <div class="mt-4 space-y-4">
            <section>
                <x-section-title :count="$myShifts['active']->count()">Vagas ativas</x-section-title>
                <div class="mt-2 space-y-2">
                    @forelse ($myShifts['active'] as $shift)
                        @include('livewire.chats.partials.shift-row', ['shift' => $shift, 'expired' => false, 'openShift' => $openShift])
                    @empty
                        <x-empty-state icon="users" text="Sem vagas ativas no momento." />
                    @endforelse
                </div>
            </section>

            @if ($myShifts['expired']->isNotEmpty())
                <section>
                    <x-section-title :count="$myShifts['expired']->count()">Expiradas / Concluídas</x-section-title>
                    <div class="mt-2 space-y-2">
                        @foreach ($myShifts['expired'] as $shift)
                            @include('livewire.chats.partials.shift-row', ['shift' => $shift, 'expired' => true, 'openShift' => $openShift])
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    @endif

    {{-- Decline confirmation --}}
    @if ($declineTarget)
        <x-ui.modal wire:click.self="$set('declineTarget', null)">
            <h2 class="font-display text-lg font-bold">Recusar candidato</h2>
            <p class="mt-2 text-sm text-muted-foreground">Tem certeza que quer recusar {{ $declineTarget['name'] }}?</p>
            <div class="mt-4 flex justify-end gap-2">
                <x-ui.button variant="outline" wire:click="$set('declineTarget', null)">Cancelar</x-ui.button>
                <x-ui.button variant="destructive" wire:click="confirmDecline">Recusar</x-ui.button>
            </div>
        </x-ui.modal>
    @endif

    <livewire:profile-modal />
</div>
