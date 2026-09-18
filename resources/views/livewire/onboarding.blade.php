@php use App\Support\Catalog; @endphp
<div class="flex min-h-dvh flex-col px-6 py-10 lg:mx-auto lg:max-w-[40rem]">
    <div class="flex flex-1 flex-col justify-center">
        <div class="mb-14 flex flex-col items-center text-center">
            <x-logo :size="56" :withText="false" />
        </div>

        {{-- Step indicator --}}
        <div class="mb-14 flex items-start justify-center">
            @foreach ($this->steps as $num => $label)
                @if (! $loop->first)
                    <div class="mt-6 h-0.5 w-10 shrink-0 {{ $step > $num - 1 ? 'bg-primary' : 'bg-border' }}"></div>
                @endif
                <div class="flex w-20 flex-col items-center gap-2 text-center">
                    <div class="grid h-12 w-12 shrink-0 place-items-center rounded-full text-base font-bold {{ $step >= $num ? 'bg-primary text-primary-foreground' : 'border border-border bg-surface text-muted-foreground' }}">
                        {{ $num }}
                    </div>
                    <span class="text-xs font-semibold leading-tight {{ $step === $num ? 'text-foreground' : 'text-muted-foreground' }}">{{ $label }}</span>
                </div>
            @endforeach
        </div>

        <div class="mb-14 text-center">
            <h1 class="font-display text-2xl font-bold tracking-tight">
                @if ($step === 1)
                    Complete seu cadastro
                @elseif ($step === 2)
                    Perfil e endereço
                @else
                    Qual seu veículo?
                @endif
            </h1>
            <p class="mt-4 text-sm text-muted-foreground">
                @if ($step === 1)
                    Conte um pouco sobre você.
                @elseif ($step === 2)
                    Escolha seu perfil e complete os dados abaixo.
                @else
                    Último passo — isso ajuda a mostrar vagas compatíveis com você.
                @endif
            </p>
        </div>

        @if ($step === 1)
            <form wire:submit="submitPersonalData" class="space-y-3 rounded-2xl border border-border bg-card p-5">
                <x-ui.field label="Nome completo">
                    <x-ui.input wire:model="name" placeholder="João da Silva" />
                    @error('name') <p class="mt-1 text-[11px] font-medium text-destructive">{{ $message }}</p> @enderror
                </x-ui.field>

                <x-ui.field label="Telefone / WhatsApp">
                    <x-ui.input wire:model="phone" inputmode="tel" placeholder="(11) 9 9999-0000"
                        x-on:input="$el.value = window.maskPhone($el.value)" />
                    @error('phone') <p class="mt-1 text-[11px] font-medium text-destructive">{{ $message }}</p> @enderror
                </x-ui.field>

                <x-ui.field label="Data de nascimento">
                    <x-ui.input wire:model.blur="birthDate" inputmode="numeric" placeholder="DD/MM/AAAA"
                        x-on:input="$el.value = window.maskDate($el.value)" />
                    @error('birthDate') <p class="mt-1 text-[11px] font-medium text-destructive">{{ $message }}</p> @enderror
                </x-ui.field>

                <x-ui.button type="submit" size="lg" class="w-full glow-orange" wire:loading.attr="disabled" wire:target="submitPersonalData">
                    <span wire:loading.remove wire:target="submitPersonalData">Continuar</span>
                    <span wire:loading wire:target="submitPersonalData" class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="3" />
                            <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                        </svg>
                        Aguarde…
                    </span>
                </x-ui.button>
            </form>
        @elseif ($step === 2)
            <form wire:submit="submitAddress" class="space-y-3 rounded-2xl border border-border bg-card p-5">
                <div>
                    <label class="mb-2 block text-xs font-medium text-muted-foreground">Você é...</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" wire:click="setRole('courier')"
                            class="flex min-w-0 flex-col items-center gap-2 rounded-xl border p-3 text-center transition {{ $role === 'courier' ? 'border-primary bg-accent text-foreground shadow-sm glow-orange' : 'border-border bg-surface text-muted-foreground hover:text-foreground' }}">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg {{ $role === 'courier' ? 'bg-primary text-primary-foreground' : 'bg-surface-elevated' }}">
                                <x-ui.icon name="bike" class="h-5 w-5" />
                            </span>
                            <span class="min-w-0 break-words text-sm font-semibold leading-tight">Motoboy</span>
                        </button>
                        <button type="button" wire:click="setRole('business')" @disabled(! $this->isAdult)
                            class="flex min-w-0 flex-col items-center gap-2 rounded-xl border p-3 text-center transition {{ ! $this->isAdult ? 'cursor-not-allowed border-border/60 bg-surface/50 text-muted-foreground opacity-60' : ($role === 'business' ? 'border-primary bg-accent text-foreground shadow-sm glow-orange' : 'border-border bg-surface text-muted-foreground hover:text-foreground') }}">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg {{ $role === 'business' && $this->isAdult ? 'bg-primary text-primary-foreground' : 'bg-surface-elevated' }}">
                                <x-ui.icon name="store" class="h-5 w-5" />
                            </span>
                            <span class="min-w-0 break-words text-sm font-semibold leading-tight">{{ $this->isAdult ? 'Estabelecimento' : 'Precisa ter 18 anos' }}</span>
                        </button>
                    </div>
                    <p class="mt-1.5 text-[11px] text-muted-foreground">Depois você pode alternar entre os dois perfis em Configurações.</p>
                </div>

                @if ($role === 'courier')
                    <x-ui.field label="CEP">
                        <div class="relative">
                            <x-ui.input wire:model="cep" inputmode="numeric" maxlength="9" placeholder="00000-000"
                                x-on:input="$el.value = window.maskCep($el.value)"
                                x-on:blur="$wire.set('cep', $el.value).then(() => $wire.lookupCep())" />
                            <span wire:loading wire:target="lookupCep" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground">
                                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="3" /><path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round" /></svg>
                            </span>
                        </div>
                        @error('cep') <p class="mt-1 text-[11px] font-medium text-destructive">{{ $message }}</p> @enderror
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
                @else
                    <x-ui.field label="Apelido do local">
                        <x-ui.input wire:model="label" placeholder="Restaurante da Ana" />
                        @error('label') <p class="mt-1 text-[11px] font-medium text-destructive">{{ $message }}</p> @enderror
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
                        @error('cep') <p class="mt-1 text-[11px] font-medium text-destructive">{{ $message }}</p> @enderror
                    </x-ui.field>

                    <div class="grid grid-cols-[1fr_90px] gap-2">
                        <x-ui.field label="Rua">
                            <x-ui.input wire:model="street" placeholder="Av. Paulista" />
                            @error('street') <p class="mt-1 text-[11px] font-medium text-destructive">{{ $message }}</p> @enderror
                        </x-ui.field>
                        <x-ui.field label="Número">
                            <x-ui.input wire:model="number" placeholder="100" />
                            @error('number') <p class="mt-1 text-[11px] font-medium text-destructive">{{ $message }}</p> @enderror
                        </x-ui.field>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <x-ui.field label="Bairro">
                            <x-ui.input wire:model="district" placeholder="Centro" />
                        </x-ui.field>
                        <x-ui.field label="Cidade">
                            <x-ui.input wire:model="city" placeholder="São Paulo - SP" />
                        </x-ui.field>
                    </div>

                    <x-ui.field label="Ponto de referência">
                        <x-ui.textarea wire:model="reference" rows="2" placeholder="Ao lado da padaria…"></x-ui.textarea>
                    </x-ui.field>

                    <x-photo-picker :photo="$photo" />
                @endif

                <x-ui.button type="submit" size="lg" class="w-full glow-orange" wire:loading.attr="disabled" wire:target="submitAddress">
                    <span wire:loading.remove wire:target="submitAddress">{{ $role === 'courier' ? 'Continuar' : 'Concluir cadastro' }}</span>
                    <span wire:loading wire:target="submitAddress" class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="3" />
                            <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                        </svg>
                        Aguarde…
                    </span>
                </x-ui.button>
                <button type="button" wire:click="previousStep" class="w-full rounded-xl py-2 text-xs font-medium text-muted-foreground hover:text-foreground">
                    Voltar
                </button>
            </form>
        @else
            <form wire:submit="finish" class="space-y-3 rounded-2xl border border-border bg-card p-5">
                <div class="space-y-2">
                    @foreach (Catalog::VEHICLE_OPTIONS as $v)
                        @php
                            $active = $vehicle === $v;
                            $blocked = $v === 'moto' && ! $this->isAdult;
                        @endphp
                        <button type="button" wire:click="setVehicle('{{ $v }}')" @disabled($blocked)
                            class="flex w-full items-center gap-3 rounded-xl border p-4 text-left transition {{ $blocked ? 'cursor-not-allowed border-border/60 bg-surface/50 text-muted-foreground opacity-60' : ($active ? 'border-primary bg-primary/10 text-foreground' : 'border-border/60 bg-surface text-muted-foreground hover:text-foreground') }}">
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
                @error('vehicle') <p class="text-[11px] font-medium text-destructive">{{ $message }}</p> @enderror

                <x-ui.button type="submit" size="lg" class="w-full glow-orange" wire:loading.attr="disabled" wire:target="finish">
                    <span wire:loading.remove wire:target="finish">Concluir cadastro</span>
                    <span wire:loading wire:target="finish" class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="3" />
                            <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                        </svg>
                        Aguarde…
                    </span>
                </x-ui.button>
                <button type="button" wire:click="previousStep" class="w-full rounded-xl py-2 text-xs font-medium text-muted-foreground hover:text-foreground">
                    Voltar
                </button>
            </form>
        @endif
    </div>
</div>
