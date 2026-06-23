<div class="pb-10">
    {{-- Header --}}
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-primary/30 via-primary/5 to-transparent"></div>
        <div class="relative flex items-start gap-3 px-4 pb-5 pt-6">
            <button type="button" x-on:click="window.history.back()"
                class="grid h-10 w-10 place-items-center rounded-full border border-border/60 bg-surface">
                <x-ui.icon name="arrow-left" class="h-5 w-5" />
            </button>
            <div class="flex-1">
                <p class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wider text-primary">
                    <x-ui.icon :name="$as === 'business' ? 'store' : 'user'" class="h-3 w-3" /> Etapa 2 de 3
                </p>
                <h1 class="font-display text-2xl font-bold leading-tight">Onde será o turno?</h1>
                <p class="mt-0.5 text-xs text-muted-foreground">Escolha um endereço salvo ou cadastre um novo.</p>
            </div>
        </div>
    </div>

    <div class="px-4">
        {{-- Tabs --}}
        <div class="mt-2 grid grid-cols-2 gap-2 rounded-full bg-surface p-1">
            <button wire:click="$set('mode', 'list')"
                class="rounded-full py-2 text-xs font-semibold transition {{ $mode === 'list' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground' }}">
                Meus endereços
            </button>
            <button wire:click="$set('mode', 'new')"
                class="rounded-full py-2 text-xs font-semibold transition {{ $mode === 'new' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground' }}">
                Cadastrar novo
            </button>
        </div>

        @if ($mode === 'list')
            <div class="mt-4 space-y-2">
                @forelse ($this->addresses as $a)
                    <button wire:click="select('{{ $a->id }}')"
                        class="tap flex w-full items-center gap-3 rounded-2xl border border-border/60 bg-card p-4 text-left transition hover:border-primary">
                        <span class="grid h-11 w-11 place-items-center rounded-xl bg-primary/15 text-primary">
                            <x-ui.icon name="map-pin" class="h-5 w-5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold">{{ $a->label }}</p>
                            <p class="truncate text-[11px] text-muted-foreground">{{ $a->street }}@if ($a->number), {{ $a->number }}@endif — {{ $a->district }}</p>
                            <p class="truncate text-[11px] text-muted-foreground">{{ $a->city }}</p>
                        </div>
                        <x-ui.icon name="chevron-right" class="h-5 w-5 shrink-0 text-muted-foreground" />
                    </button>
                @empty
                    <div class="rounded-2xl border border-dashed border-border/60 p-8 text-center">
                        <x-ui.icon name="map-pin" class="mx-auto mb-2 h-6 w-6 text-muted-foreground" />
                        <p class="text-sm font-semibold">Nenhum endereço salvo</p>
                        <p class="mt-1 text-xs text-muted-foreground">Cadastre o primeiro para continuar.</p>
                        <x-ui.button class="mt-4" wire:click="$set('mode', 'new')"><x-ui.icon name="plus" class="mr-1 h-4 w-4" /> Cadastrar endereço</x-ui.button>
                    </div>
                @endforelse
            </div>
        @else
            <form wire:submit="saveNew" class="mt-4 space-y-3">
                <x-ui.field label="Apelido do local">
                    <x-ui.input wire:model="label" placeholder="Hamburgueria, Depósito, Casa…" />
                    @error('label') <p class="mt-1 text-xs font-medium text-destructive">{{ $message }}</p> @enderror
                </x-ui.field>

                <x-ui.field label="CEP *">
                    <div class="relative">
                        <x-ui.input wire:model="cep" inputmode="numeric" maxlength="9" placeholder="00000-000"
                            x-on:input="$el.value = window.maskCep($el.value)"
                            x-on:blur="$wire.set('cep', $el.value).then(() => $wire.lookupCep())" />
                        <span wire:loading wire:target="lookupCep" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground">
                            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="3" /><path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round" /></svg>
                        </span>
                    </div>
                    @error('cep') <p class="mt-1 text-xs font-medium text-destructive">{{ $message }}</p> @enderror
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
                    <x-ui.field label="Bairro">
                        <x-ui.input wire:model="district" />
                        @error('district') <p class="mt-1 text-xs font-medium text-destructive">{{ $message }}</p> @enderror
                    </x-ui.field>
                    <x-ui.field label="Cidade">
                        <x-ui.input wire:model="city" />
                        @error('city') <p class="mt-1 text-xs font-medium text-destructive">{{ $message }}</p> @enderror
                    </x-ui.field>
                </div>

                <x-ui.button type="submit" size="lg" class="w-full" wire:loading.attr="disabled" wire:target="saveNew">
                    Salvar e continuar
                </x-ui.button>
            </form>
        @endif
    </div>
</div>
