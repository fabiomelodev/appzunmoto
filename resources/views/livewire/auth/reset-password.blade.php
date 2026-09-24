<div class="flex min-h-dvh flex-col px-6 py-10 lg:mx-auto lg:max-w-[40rem]">
    <div class="flex flex-1 flex-col justify-center">
        {{-- Branding --}}
        <div class="mb-8 flex flex-col items-center text-center">
            <x-logo :size="80" :withText="false" />
            <h1 class="mt-4 font-display text-3xl font-bold tracking-tight">Nova senha</h1>
            <p class="mt-2 max-w-xs text-sm text-muted-foreground">Escolha uma nova senha para acessar sua conta.</p>
        </div>

        <form wire:submit="submit" class="space-y-3 rounded-2xl border border-border bg-card p-5">
            <x-ui.field label="E-mail">
                <x-ui.input type="email" wire:model="email" placeholder="voce@email.com" autocomplete="email" />
                @error('email') <p class="mt-1 text-[11px] font-medium text-destructive">{{ $message }}</p> @enderror
            </x-ui.field>

            <x-ui.field label="Nova senha">
                <div class="relative" x-data="{ show: false }">
                    <x-ui.input x-bind:type="show ? 'text' : 'password'" wire:model="password" placeholder="••••••"
                        autocomplete="new-password" class="pr-10" />
                    <button type="button" x-on:click="show = !show"
                        class="absolute right-2 top-1/2 grid h-7 w-7 -translate-y-1/2 place-items-center rounded-md text-muted-foreground hover:text-foreground"
                        :aria-label="show ? 'Ocultar senha' : 'Mostrar senha'">
                        <x-ui.icon x-show="!show" name="eye" class="h-4 w-4" />
                        <x-ui.icon x-show="show" x-cloak name="eye-off" class="h-4 w-4" />
                    </button>
                </div>
                @error('password') <p class="mt-1 text-[11px] font-medium text-destructive">{{ $message }}</p> @enderror
            </x-ui.field>

            <x-ui.field label="Confirmar nova senha">
                <x-ui.input type="password" wire:model="passwordConfirmation" placeholder="••••••" autocomplete="new-password" />
                @error('passwordConfirmation') <p class="mt-1 text-[11px] font-medium text-destructive">{{ $message }}</p> @enderror
            </x-ui.field>

            <x-ui.button type="submit" size="lg" class="w-full glow-orange" wire:loading.attr="disabled" wire:target="submit">
                <span wire:loading.remove wire:target="submit" class="inline-flex items-center gap-2">
                    <x-ui.icon name="check" class="h-4 w-4" /> Redefinir senha
                </span>
                <span wire:loading wire:target="submit" class="inline-flex items-center gap-2">Aguarde…</span>
            </x-ui.button>
        </form>

        @error('email')
            <a href="{{ route('password.request', ['email' => $email]) }}" wire:navigate
                class="mt-4 block text-center text-sm font-medium text-primary hover:underline">
                Pedir um novo link de recuperação
            </a>
        @enderror

        <a href="{{ route('login') }}" wire:navigate
            class="mt-5 flex items-center justify-center gap-1.5 text-sm text-muted-foreground hover:text-foreground">
            <x-ui.icon name="arrow-left" class="h-4 w-4" /> Voltar para o login
        </a>
    </div>
</div>
