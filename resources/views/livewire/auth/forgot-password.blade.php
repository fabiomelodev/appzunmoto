<div class="flex min-h-dvh flex-col px-6 py-10 lg:mx-auto lg:max-w-[40rem]">
    <div class="flex flex-1 flex-col justify-center">
        {{-- Branding --}}
        <div class="mb-8 flex flex-col items-center text-center">
            <x-logo :size="80" :withText="false" />
            <h1 class="mt-4 font-display text-3xl font-bold tracking-tight">Recuperar senha</h1>
            <p class="mt-2 max-w-xs text-sm text-muted-foreground">
                Informe o e-mail da sua conta e enviaremos um link para você criar uma nova senha.
            </p>
        </div>

        @if ($sent)
            <div class="rounded-2xl border border-success/40 bg-success/10 p-5 text-sm text-success">
                <p class="flex items-center gap-2 font-semibold">
                    <x-ui.icon name="mail" class="h-4 w-4" /> Confira seu e-mail
                </p>
                <p class="mt-2 text-xs font-medium">
                    Se existir uma conta com o e-mail informado, enviamos um link para redefinir a senha. Ele vale por
                    {{ config('auth.passwords.'.config('auth.defaults.passwords').'.expire') }} minutos. Não recebeu? Verifique a caixa de spam ou tente novamente em instantes.
                </p>
            </div>
        @else
            <form wire:submit="submit" class="space-y-3 rounded-2xl border border-border bg-card p-5">
                <x-ui.field label="E-mail">
                    <x-ui.input type="email" wire:model="email" placeholder="voce@email.com" autocomplete="email" />
                    @error('email') <p class="mt-1 text-[11px] font-medium text-destructive">{{ $message }}</p> @enderror
                </x-ui.field>

                <x-ui.button type="submit" size="lg" class="w-full glow-orange" wire:loading.attr="disabled" wire:target="submit">
                    <span wire:loading.remove wire:target="submit" class="inline-flex items-center gap-2">
                        <x-ui.icon name="mail" class="h-4 w-4" /> Enviar link de recuperação
                    </span>
                    <span wire:loading wire:target="submit" class="inline-flex items-center gap-2">Enviando…</span>
                </x-ui.button>
            </form>
        @endif

        <a href="{{ route('login') }}" wire:navigate
            class="mt-5 flex items-center justify-center gap-1.5 text-sm text-muted-foreground hover:text-foreground">
            <x-ui.icon name="arrow-left" class="h-4 w-4" /> Voltar para o login
        </a>
    </div>
</div>
