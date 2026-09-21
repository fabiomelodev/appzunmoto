@php
    use App\Support\Catalog;
    $isAdult = auth()->user()->profile?->isAdult();
@endphp
<div class="flex min-h-dvh flex-col px-6 py-10">
    <div class="flex flex-1 flex-col justify-center">
        <div class="mb-8 flex flex-col items-center text-center">
            <x-logo :size="64" :withText="false" />
            <h1 class="mt-4 font-display text-2xl font-bold tracking-tight">Complete seu perfil de motoboy</h1>
            <p class="mt-2 max-w-xs text-sm text-muted-foreground">
                Só mais um passo para virar Motoboy.
            </p>
        </div>

        <form wire:submit="finish" class="space-y-3 rounded-2xl border border-border bg-card p-5">
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

            <div class="grid grid-cols-2 gap-2">
                <x-ui.field label="Bairro">
                    <x-ui.input wire:model="district" placeholder="Centro" />
                    @error('district') <p class="mt-1 text-[11px] font-medium text-destructive">{{ $message }}</p> @enderror
                </x-ui.field>
                <x-ui.field label="Cidade">
                    <x-ui.input wire:model="city" placeholder="São Paulo - SP" />
                    @error('city') <p class="mt-1 text-[11px] font-medium text-destructive">{{ $message }}</p> @enderror
                </x-ui.field>
            </div>

            <div>
                <label class="mb-2 block text-xs font-medium text-muted-foreground">Veículo</label>
                <div class="space-y-2">
                    @foreach (Catalog::VEHICLE_OPTIONS as $v)
                        @php
                            $active = $vehicle === $v;
                            $blocked = $v === 'moto' && ! $isAdult;
                        @endphp
                        <button type="button" wire:click="setVehicle('{{ $v }}')" @disabled($blocked)
                            class="flex w-full items-center gap-3 rounded-xl border p-4 text-left transition {{ $blocked ? 'cursor-not-allowed border-border/60 bg-surface/50 text-muted-foreground opacity-60' : ($active ? 'border-primary bg-primary/10 text-foreground' : 'border-border/60 bg-input text-muted-foreground hover:text-foreground') }}">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg {{ $active && ! $blocked ? 'bg-primary text-primary-foreground' : 'bg-surface-elevated' }}">
                                <x-ui.icon :name="Catalog::VEHICLE_ICON[$v]" class="h-5 w-5" />
                            </span>
                            <div class="flex-1">
                                <div class="text-sm font-semibold text-foreground">{{ Catalog::VEHICLE_LABEL[$v] }}</div>
                                <div class="text-[11px] text-muted-foreground">{{ $blocked ? 'Precisa ter 18 anos' : Catalog::VEHICLE_HINT[$v] }}</div>
                            </div>
                            @if ($active && ! $blocked)
                                <x-ui.icon name="check-circle" class="h-5 w-5 text-primary" />
                            @endif
                        </button>
                    @endforeach
                </div>
                @error('vehicle') <p class="mt-1 text-[11px] font-medium text-destructive">{{ $message }}</p> @enderror
            </div>

            <x-ui.button type="submit" size="lg" class="w-full glow-orange" wire:loading.attr="disabled" wire:target="finish">
                <span wire:loading.remove wire:target="finish">Concluir</span>
                <span wire:loading wire:target="finish" class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="3" />
                        <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                    </svg>
                    Aguarde…
                </span>
            </x-ui.button>
        </form>
    </div>
</div>
