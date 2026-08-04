<div class="flex min-h-dvh flex-col px-6 py-10">
    <div class="flex flex-1 flex-col justify-center">
        {{-- Branding --}}
        <div class="mb-8 flex flex-col items-center text-center">
            <x-logo :size="80" :withText="false" />
            <h1 class="mt-4 font-display text-3xl font-bold tracking-tight">Moto<span class="text-primary">Reserva</span></h1>
            <p class="mt-2 max-w-xs text-sm text-muted-foreground">
                Conectando motoboys e comércios para nenhuma entrega ficar para trás.
            </p>
        </div>

        {{-- Mode toggle --}}
        <div class="mb-4 flex rounded-full bg-muted p-1 text-sm">
            <button type="button" wire:click="setMode('signin')"
                class="flex-1 rounded-full py-2 font-medium tap {{ $mode === 'signin' ? 'bg-background shadow' : 'text-muted-foreground' }}">
                Entrar
            </button>
            <button type="button" wire:click="setMode('signup')"
                class="flex-1 rounded-full py-2 font-medium tap {{ $mode === 'signup' ? 'bg-background shadow' : 'text-muted-foreground' }}">
                Criar conta
            </button>
        </div>

        @if ($notice)
            <div class="mb-4 rounded-xl border border-success/40 bg-success/10 p-3 text-xs font-medium text-success">
                {{ $notice }}
            </div>
        @endif

        {{-- Form --}}
        <form wire:submit="submit" class="space-y-3 rounded-2xl border border-border bg-card p-5">
            @if ($mode === 'signup')
                <x-ui.field label="Nome completo">
                    <x-ui.input wire:model="name" placeholder="João da Silva" />
                    @error('name') <p class="mt-1 text-[11px] font-medium text-destructive">{{ $message }}</p> @enderror
                </x-ui.field>

                <x-ui.field label="Data de nascimento">
                    <x-ui.input wire:model="birthDate" inputmode="numeric" placeholder="DD/MM/AAAA"
                        x-on:input="$el.value = window.maskDate($el.value)" />
                    @error('birthDate') <p class="mt-1 text-[11px] font-medium text-destructive">{{ $message }}</p> @enderror
                </x-ui.field>

                <x-ui.field label="Telefone / WhatsApp">
                    <x-ui.input wire:model="phone" inputmode="tel" placeholder="(11) 9 9999-0000"
                        x-on:input="$el.value = window.maskPhone($el.value)" />
                    @error('phone') <p class="mt-1 text-[11px] font-medium text-destructive">{{ $message }}</p> @enderror
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
                        <x-ui.input wire:model="street" placeholder="Av. Paulista" />
                    </x-ui.field>
                    <x-ui.field label="Número">
                        <x-ui.input wire:model="number" placeholder="100" />
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
            @endif

            <x-ui.field label="E-mail">
                <x-ui.input type="email" wire:model="email" placeholder="voce@email.com" />
                @error('email') <p class="mt-1 text-[11px] font-medium text-destructive">{{ $message }}</p> @enderror
            </x-ui.field>

            <x-ui.field label="Senha">
                <div class="relative" x-data="{ show: false }">
                    <x-ui.input x-bind:type="show ? 'text' : 'password'" wire:model="password" placeholder="••••••" class="pr-10" />
                    <button type="button" x-on:click="show = !show"
                        class="absolute right-2 top-1/2 grid h-7 w-7 -translate-y-1/2 place-items-center rounded-md text-muted-foreground hover:text-foreground"
                        :aria-label="show ? 'Ocultar senha' : 'Mostrar senha'">
                        <x-ui.icon x-show="!show" name="eye" class="h-4 w-4" />
                        <x-ui.icon x-show="show" x-cloak name="eye-off" class="h-4 w-4" />
                    </button>
                </div>
                @error('password') <p class="mt-1 text-[11px] font-medium text-destructive">{{ $message }}</p> @enderror
            </x-ui.field>

            @if ($mode === 'signup')
                <x-ui.field label="Confirmar senha">
                    <div class="relative" x-data="{ show: false }">
                        <x-ui.input x-bind:type="show ? 'text' : 'password'" wire:model="passwordConfirmation"
                            placeholder="••••••" class="pr-10" />
                        <button type="button" x-on:click="show = !show"
                            class="absolute right-2 top-1/2 grid h-7 w-7 -translate-y-1/2 place-items-center rounded-md text-muted-foreground hover:text-foreground"
                            :aria-label="show ? 'Ocultar senha' : 'Mostrar senha'">
                            <x-ui.icon x-show="!show" name="eye" class="h-4 w-4" />
                            <x-ui.icon x-show="show" x-cloak name="eye-off" class="h-4 w-4" />
                        </button>
                    </div>
                    @error('passwordConfirmation') <p class="mt-1 text-[11px] font-medium text-destructive">{{ $message }}</p> @enderror
                </x-ui.field>
            @endif

            <x-ui.button type="submit" size="lg" class="w-full glow-orange" wire:loading.attr="disabled" wire:target="submit">
                <span wire:loading.remove wire:target="submit" class="inline-flex items-center gap-2">
                    <x-ui.icon name="mail" class="h-4 w-4" />
                    {{ $mode === 'signup' ? 'Criar conta' : 'Entrar' }}
                </span>
                <span wire:loading wire:target="submit" class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="3" />
                        <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                    </svg>
                    Aguarde…
                </span>
            </x-ui.button>
        </form>

        {{-- Divider --}}
        <div class="my-5 flex items-center gap-3 text-xs text-muted-foreground">
            <div class="h-px flex-1 bg-border"></div>
            ou
            <div class="h-px flex-1 bg-border"></div>
        </div>

        {{-- Google --}}
        <x-ui.button :href="route('google.redirect')" variant="outline" size="lg" class="w-full">
            <svg class="mr-2 h-4 w-4" viewBox="0 0 48 48" aria-hidden="true">
                <path fill="#FFC107"
                    d="M43.6 20.5H42V20H24v8h11.3C33.7 32.4 29.3 35.5 24 35.5c-6.4 0-11.5-5.1-11.5-11.5S17.6 12.5 24 12.5c2.9 0 5.5 1.1 7.5 2.9l5.7-5.7C33.9 6.4 29.2 4.5 24 4.5 13.2 4.5 4.5 13.2 4.5 24S13.2 43.5 24 43.5 43.5 34.8 43.5 24c0-1.2-.1-2.3-.4-3.5z" />
                <path fill="#FF3D00"
                    d="M6.3 14.7l6.6 4.8C14.8 16 19 12.5 24 12.5c2.9 0 5.5 1.1 7.5 2.9l5.7-5.7C33.9 6.4 29.2 4.5 24 4.5 16.3 4.5 9.7 8.9 6.3 14.7z" />
                <path fill="#4CAF50"
                    d="M24 43.5c5.2 0 9.8-1.9 13.3-5l-6.1-5c-2 1.4-4.5 2.3-7.2 2.3-5.3 0-9.7-3.1-11.3-7.5l-6.5 5C9.6 39 16.2 43.5 24 43.5z" />
                <path fill="#1976D2"
                    d="M43.6 20.5H42V20H24v8h11.3c-.7 2-2 3.8-3.8 5.1l6.1 5c4.3-3.9 7-9.7 7-16.1 0-1.2-.1-2.3-.4-3.5z" />
            </svg>
            Continuar com Google
        </x-ui.button>

        {{-- Test mode (hidden in production) --}}
        @unless (app()->environment('production'))
            <div class="mt-6 rounded-2xl border border-dashed border-primary/40 bg-primary/5 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-primary">Modo teste</p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Entre instantaneamente sem e-mail nem senha. Use apenas para testar o app.
                </p>
                <div class="mt-3 flex gap-2">
                    <x-ui.input wire:model="testName" placeholder="Nome de usuário" maxlength="40" />
                    <x-ui.button type="button" wire:click="testLogin" class="shrink-0" wire:loading.attr="disabled"
                        wire:target="testLogin">
                        <x-ui.icon name="zap" class="mr-1.5 h-4 w-4" />
                        Entrar
                    </x-ui.button>
                </div>
            </div>
        @endunless

        @if ($mode === 'signup')
            <p class="mt-4 text-center text-[11px] text-muted-foreground">
                Ao criar conta você concorda com os Termos e a Política de Privacidade.
            </p>
        @endif
    </div>
</div>
