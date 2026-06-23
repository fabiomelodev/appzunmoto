@php
    $maskCep = fn ($c) => strlen((string) $c) > 5 ? substr($c, 0, 5).'-'.substr($c, 5) : (string) $c;
@endphp

<div class="pb-10">
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-primary/25 via-primary/5 to-transparent"></div>
        <div class="relative flex items-center gap-3 px-4 pb-4 pt-6">
            <button type="button" x-on:click="window.history.back()"
                class="grid h-10 w-10 place-items-center rounded-full border border-border/60 bg-surface">
                <x-ui.icon name="arrow-left" class="h-5 w-5" />
            </button>
            <div class="flex-1">
                <h1 class="font-display text-xl font-bold leading-tight">Meus Endereços</h1>
                <p class="text-xs text-muted-foreground">Acelere a publicação das suas vagas</p>
            </div>
        </div>
    </div>

    <div class="mt-2 space-y-2 px-4">
        <x-ui.button wire:click="openNew" size="lg" class="w-full">
            <x-ui.icon name="plus" class="mr-2 h-4 w-4" /> Adicionar novo endereço
        </x-ui.button>

        @forelse ($this->addresses as $a)
            <div class="rounded-2xl border border-border/60 bg-card p-4" wire:key="addr-{{ $a->id }}">
                <div class="flex items-start gap-3">
                    @if ($a->photo_url)
                        <img src="{{ $a->photo_url }}" alt="{{ $a->label }}" class="h-12 w-12 shrink-0 rounded-xl object-cover" />
                    @else
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-primary/15 text-primary">
                            <x-ui.icon name="map-pin" class="h-4 w-4" />
                        </span>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold">{{ $a->label }}</p>
                        <p class="truncate text-[11px] text-muted-foreground">{{ $a->street }}@if ($a->number), {{ $a->number }}@endif — {{ $a->district }}</p>
                        <p class="truncate text-[11px] text-muted-foreground">{{ $a->city }} @if ($a->postal_code) · {{ $maskCep($a->postal_code) }} @endif</p>
                        @if ($a->reference)
                            <p class="mt-1 truncate text-[11px] text-muted-foreground">📍 {{ $a->reference }}</p>
                        @endif
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <button wire:click="openEdit('{{ $a->id }}')" class="grid h-8 w-8 place-items-center rounded-lg bg-surface text-muted-foreground hover:text-primary" aria-label="Editar">
                            <x-ui.icon name="pencil" class="h-3.5 w-3.5" />
                        </button>
                        <button x-on:click="if (confirm('Excluir endereço "{{ $a->label }}"?')) $wire.delete('{{ $a->id }}')"
                            class="grid h-8 w-8 place-items-center rounded-lg bg-surface text-muted-foreground hover:text-destructive" aria-label="Excluir">
                            <x-ui.icon name="trash" class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-border/60 p-10 text-center text-sm text-muted-foreground">
                <x-ui.icon name="map-pin" class="mx-auto mb-2 h-6 w-6 opacity-60" />
                Nenhum endereço salvo ainda.
            </div>
        @endforelse
    </div>

    {{-- Form dialog --}}
    @if ($open)
        <x-ui.modal wire:click.self="$set('open', false)">
            <h2 class="font-display text-lg font-bold">{{ $editingId ? 'Editar endereço' : 'Novo endereço' }}</h2>
            <form wire:submit="save" class="mt-3 space-y-3">
                <x-ui.field label="Apelido do local">
                    <x-ui.input wire:model="label" placeholder="Hamburgueria, Depósito, Casa…" />
                    @error('label') <p class="mt-1 text-xs font-medium text-destructive">{{ $message }}</p> @enderror
                </x-ui.field>
                <x-ui.field label="CEP">
                    <div class="relative">
                        <x-ui.input wire:model="cep" inputmode="numeric" maxlength="9" placeholder="00000-000"
                            x-on:input="$el.value = window.maskCep($el.value)"
                            x-on:blur="$wire.set('cep', $el.value).then(() => $wire.lookupCep())" />
                        <span wire:loading wire:target="lookupCep" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground">
                            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="3" /><path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round" /></svg>
                        </span>
                    </div>
                </x-ui.field>
                <div class="grid grid-cols-[1fr_90px] gap-2">
                    <x-ui.field label="Rua">
                        <x-ui.input wire:model="street" />
                        @error('street') <p class="mt-1 text-xs font-medium text-destructive">{{ $message }}</p> @enderror
                    </x-ui.field>
                    <x-ui.field label="Número">
                        <x-ui.input wire:model="number" />
                        @error('number') <p class="mt-1 text-xs font-medium text-destructive">{{ $message }}</p> @enderror
                    </x-ui.field>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <x-ui.field label="Bairro"><x-ui.input wire:model="district" /></x-ui.field>
                    <x-ui.field label="Cidade"><x-ui.input wire:model="city" /></x-ui.field>
                </div>
                <x-ui.field label="Ponto de referência">
                    <x-ui.textarea wire:model="reference" rows="2" placeholder="Ao lado da padaria…"></x-ui.textarea>
                </x-ui.field>
                <div class="flex justify-end gap-2 pt-1">
                    <x-ui.button type="button" variant="outline" wire:click="$set('open', false)">Cancelar</x-ui.button>
                    <x-ui.button type="submit">{{ $editingId ? 'Salvar alterações' : 'Adicionar endereço' }}</x-ui.button>
                </div>
            </form>
        </x-ui.modal>
    @endif
</div>
