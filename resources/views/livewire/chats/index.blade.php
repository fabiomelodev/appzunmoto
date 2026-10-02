@php
    $myShifts = $this->myShifts;
    $interestedShifts = $this->interestedShifts;
    $historyShifts = $this->historyShifts;
    $myApplications = $this->myApplicationsByShift;
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
    <div class="mt-5 grid h-auto w-full grid-cols-3 gap-1 rounded-xl bg-surface p-1">
        <button wire:click="setTab('publicadas')"
            class="rounded-lg px-1 py-2 text-[11px] font-semibold leading-tight transition {{ $tab === 'publicadas' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground' }}">Vagas publicadas</button>
        <button wire:click="setTab('interessadas')"
            class="rounded-lg px-1 py-2 text-[11px] font-semibold leading-tight transition {{ $tab === 'interessadas' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground' }}">Vagas interessadas</button>
        <button wire:click="setTab('historico')"
            class="rounded-lg px-1 py-2 text-[11px] font-semibold leading-tight transition {{ $tab === 'historico' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground' }}">Histórico de turnos</button>
    </div>

    @if ($tab === 'publicadas')
        <div class="mt-4 space-y-4">
            <section>
                <x-section-title :count="$myShifts['active']->count()">Vagas abertas</x-section-title>
                <div class="mt-2 space-y-2">
                    @forelse ($myShifts['active'] as $shift)
                        @include('livewire.chats.partials.shift-row', ['shift' => $shift, 'expired' => false, 'openShift' => $openShift])
                    @empty
                        <x-empty-state icon="users" text="Você ainda não publicou nenhuma vaga aberta." />
                    @endforelse
                </div>
            </section>

            @if ($myShifts['expired']->isNotEmpty())
                <section>
                    <x-section-title :count="$myShifts['expired']->count()">Vagas encerradas</x-section-title>
                    <div class="mt-2 space-y-2">
                        @foreach ($myShifts['expired'] as $shift)
                            @include('livewire.chats.partials.shift-row', ['shift' => $shift, 'expired' => true, 'openShift' => $openShift])
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    @elseif ($tab === 'interessadas')
        <div class="mt-4 space-y-2">
            @forelse ($interestedShifts as $v)
                @include('livewire.chats.partials.shift-status-row', ['v' => $v, 'mode' => 'interested', 'myApplications' => $myApplications])
            @empty
                <x-empty-state icon="bike" text="Você ainda não demonstrou interesse em nenhuma vaga." />
            @endforelse
        </div>
    @else
        <div class="mt-4">
            <div class="grid grid-cols-2 gap-1 rounded-xl border border-border bg-surface p-1">
                @foreach (['published' => 'Publiquei', 'worked' => 'Trabalhei'] as $value => $label)
                    <button wire:click="setHistoryTab('{{ $value }}')"
                        class="rounded-lg px-3 py-2 text-xs font-semibold transition {{ $historyTab === $value ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="mt-3 space-y-2">
                @forelse ($historyShifts as $v)
                    @include('livewire.chats.partials.shift-status-row', ['v' => $v, 'mode' => $historyTab, 'myApplications' => $myApplications])
                @empty
                    <x-empty-state icon="calendar" :text="$historyTab === 'published' ? 'Você ainda não publicou nenhuma vaga.' : 'Você ainda não trabalhou em nenhuma vaga.'" />
                @endforelse
            </div>
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
