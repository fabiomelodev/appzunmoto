<div class="min-h-dvh pb-6"
    x-data="{
        theme: (() => { try { return localStorage.getItem('mr-theme') || 'dark'; } catch (e) { return 'dark'; } })(),
        setTheme(t) {
            this.theme = t;
            document.documentElement.className = t;
            try { localStorage.setItem('mr-theme', t); } catch (e) {}
            $wire.persistTheme(t);
        },
    }">
    <header class="sticky top-0 z-10 flex items-center gap-3 border-b border-border bg-background/85 px-4 py-4 backdrop-blur">
        <button type="button" x-on:click="window.history.back()" class="grid h-9 w-9 place-items-center rounded-xl border border-border bg-surface" aria-label="Voltar">
            <x-ui.icon name="arrow-left" class="h-4 w-4" />
        </button>
        <div>
            <h1 class="font-display text-xl font-bold">Configurações</h1>
            <p class="text-xs text-muted-foreground">Personalize sua experiência</p>
        </div>
    </header>

    <div class="space-y-6 px-4 pt-5">
        {{-- Appearance --}}
        <x-settings-section icon="palette" title="Aparência">
            <div class="grid grid-cols-3 gap-2">
                @foreach (['dark' => ['moon', 'Escuro', 'Premium'], 'light' => ['sun', 'Claro', 'Elegante'], 'urbano' => ['zap', 'Urbano', 'Speed']] as $value => $meta)
                    <button type="button" @click="setTheme('{{ $value }}')"
                        class="flex flex-col items-start gap-2 rounded-xl border p-3 text-left transition"
                        :class="theme === '{{ $value }}' ? 'border-primary bg-accent text-foreground shadow-sm glow-orange' : 'border-border bg-surface text-muted-foreground hover:text-foreground'">
                        <span class="grid h-9 w-9 place-items-center rounded-lg"
                            :class="theme === '{{ $value }}' ? 'bg-primary text-primary-foreground' : 'bg-surface-elevated'">
                            <x-ui.icon :name="$meta[0]" class="h-5 w-5" />
                        </span>
                        <div>
                            <div class="text-sm font-semibold text-foreground">{{ $meta[1] }}</div>
                            <div class="text-[10px] uppercase tracking-wide text-muted-foreground">{{ $meta[2] }}</div>
                        </div>
                    </button>
                @endforeach
            </div>
            <p class="mt-3 text-xs text-muted-foreground">A preferência é sincronizada com a sua conta.</p>
        </x-settings-section>

        {{-- User preferences --}}
        <x-settings-section icon="user" title="Preferências do usuário">
            <x-settings-row label="Nome" :value="$profile?->name ?: $user->name">
                <a href="{{ route('profile') }}" wire:navigate class="text-xs font-semibold text-primary">Editar</a>
            </x-settings-row>
            <x-settings-row label="Telefone" :value="$profile?->phone ?: 'Não informado'" />
        </x-settings-section>

        {{-- Active profile --}}
        <x-settings-section icon="refresh-cw" title="Perfil">
            <p class="mb-3 text-xs text-muted-foreground">Sua conta pode ser as duas coisas — alterne quando quiser.</p>
            <div class="grid grid-cols-2 gap-2">
                <button type="button" wire:click="switchRole('courier')"
                    class="flex flex-col items-start gap-2 rounded-xl border p-3 text-left transition {{ $profile?->role === 'courier' ? 'border-primary bg-accent text-foreground shadow-sm glow-orange' : 'border-border bg-surface text-muted-foreground hover:text-foreground' }}">
                    <span class="grid h-9 w-9 place-items-center rounded-lg {{ $profile?->role === 'courier' ? 'bg-primary text-primary-foreground' : 'bg-surface-elevated' }}">
                        <x-ui.icon name="bike" class="h-5 w-5" />
                    </span>
                    <div class="text-sm font-semibold text-foreground">Motoboy</div>
                </button>
                <button type="button" wire:click="switchRole('business')"
                    class="flex flex-col items-start gap-2 rounded-xl border p-3 text-left transition {{ $profile?->role === 'business' ? 'border-primary bg-accent text-foreground shadow-sm glow-orange' : 'border-border bg-surface text-muted-foreground hover:text-foreground' }}">
                    <span class="grid h-9 w-9 place-items-center rounded-lg {{ $profile?->role === 'business' ? 'bg-primary text-primary-foreground' : 'bg-surface-elevated' }}">
                        <x-ui.icon name="store" class="h-5 w-5" />
                    </span>
                    <div class="text-sm font-semibold text-foreground">Estabelecimento</div>
                </button>
            </div>
        </x-settings-section>

        {{-- Account & security --}}
        <x-settings-section icon="shield-check" title="Conta e segurança">
            <x-settings-row label="E-mail" :value="$user->email">
                <button wire:click="$set('emailOpen', true)" class="text-xs font-semibold text-primary">Alterar</button>
            </x-settings-row>
            <x-settings-row label="Senha" :value="$hasPassword ? '••••••••' : 'Não definida'">
                <button wire:click="$set('passwordOpen', true)" class="text-xs font-semibold text-primary">{{ $hasPassword ? 'Alterar' : 'Definir' }}</button>
            </x-settings-row>
            <x-settings-row label="Conta Google" :value="$linkedGoogle ? 'Vinculada' : 'Não vinculada'">
                @if ($linkedGoogle)
                    <button wire:click="unlinkGoogle"
                        wire:confirm="Desvincular sua conta do Google? Você poderá entrar novamente com e-mail e senha."
                        class="text-xs font-semibold text-destructive">Desvincular</button>
                @else
                    <span class="text-[11px] text-muted-foreground">Use “Continuar com Google” para vincular</span>
                @endif
            </x-settings-row>
        </x-settings-section>

        {{-- Region --}}
        <x-settings-section icon="globe" title="Região">
            <label class="block text-xs font-medium text-muted-foreground">Cidade base</label>
            <input wire:model="city" x-on:blur="$wire.saveCity()" placeholder="Ex: São Paulo, SP"
                class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm outline-none focus:border-primary" />
            <p class="mt-2 text-xs text-muted-foreground">Usado para filtrar vagas próximas a você.</p>
        </x-settings-section>

        {{-- Notifications --}}
        <x-settings-section icon="bell" title="Notificações">
            <x-settings-toggle label="Novas vagas próximas" description="Avise quando surgir uma vaga compatível" model="notifyShifts" />
            <x-settings-toggle label="Mensagens do chat" description="Notificar respostas de contratantes" model="notifyChat" />
            <x-settings-toggle label="Resumo por e-mail" description="Receber resumo semanal" model="notifyEmail" />

            <div class="border-b border-border/60 py-3 last:border-0"
                x-data="{
                    status: 'unsubscribed',
                    loading: false,
                    async refresh() {
                        this.status = await window.webPushStatus();
                        // Browser-side "I'm subscribed" doesn't guarantee the
                        // server still has the row — re-sync it silently so a
                        // lost/failed save heals itself instead of leaving the
                        // toggle stuck "on" with nothing actually saved.
                        if (this.status === 'subscribed') {
                            const sub = await window.webPushCurrentSubscription();
                            if (sub) $wire.subscribeToPush(sub, true);
                        }
                    },
                    async toggle() {
                        if (this.loading) return;
                        this.loading = true;
                        try {
                            if (this.status === 'subscribed') {
                                const endpoint = await window.webPushUnsubscribe();
                                if (endpoint) $wire.unsubscribeFromPush(endpoint);
                            } else {
                                const { subscription, replacedEndpoint } = await window.webPushSubscribe();
                                if (replacedEndpoint) $wire.unsubscribeFromPush(replacedEndpoint);
                                $wire.subscribeToPush(subscription);
                            }
                        } catch (e) {
                            $dispatch('toast', { message: e.message || 'Não foi possível alterar as notificações.', type: 'error' });
                        } finally {
                            await this.refresh();
                            this.loading = false;
                        }
                    },
                }"
                x-init="refresh()">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-foreground">Notificações do navegador</div>
                        <template x-if="status === 'unsupported'"><div class="text-xs text-muted-foreground">Não suportado neste navegador.</div></template>
                        <template x-if="status === 'denied'"><div class="text-xs text-muted-foreground">Bloqueadas nas permissões do navegador.</div></template>
                        <template x-if="status === 'subscribed' || status === 'unsubscribed'"><div class="text-xs text-muted-foreground">Alertas de vagas e chat mesmo com o app em segundo plano.</div></template>
                    </div>
                    <template x-if="status === 'subscribed' || status === 'unsubscribed'">
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" class="peer sr-only" :checked="status === 'subscribed'" :disabled="loading" x-on:change="toggle()" />
                            <div class="h-6 w-11 rounded-full bg-input transition-colors peer-checked:bg-primary peer-focus-visible:ring-2 peer-focus-visible:ring-ring"></div>
                            <div class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-background shadow transition-transform peer-checked:translate-x-5"></div>
                        </label>
                    </template>
                </div>
            </div>
        </x-settings-section>

        {{-- About --}}
        <x-settings-section icon="info" title="Sobre o aplicativo">
            <x-settings-row label="Versão" value="1.0.0" />
            <x-settings-row label="Build" value="ZunMoto · Stable" />
            <div class="mt-3 rounded-xl border border-border bg-surface p-3 text-xs text-muted-foreground">
                ZunMoto conecta motoboys a turnos disponíveis em restaurantes parceiros, com agilidade, transparência e foco em rentabilidade.
            </div>
        </x-settings-section>
    </div>

    {{-- Change email dialog --}}
    @if ($emailOpen)
        <x-ui.modal wire:click.self="$set('emailOpen', false)">
            <h2 class="flex items-center gap-2 font-display text-lg font-bold"><x-ui.icon name="mail" class="h-4 w-4" /> Alterar e-mail</h2>
            <form wire:submit="updateEmail" class="mt-3 space-y-3">
                <x-ui.field label="E-mail atual"><x-ui.input :value="$user->email" disabled /></x-ui.field>
                <x-ui.field label="Novo e-mail">
                    <x-ui.input type="email" wire:model="newEmail" placeholder="novo@email.com" />
                    @error('newEmail') <p class="mt-1 text-xs font-medium text-destructive">{{ $message }}</p> @enderror
                </x-ui.field>
                <x-ui.field label="Senha atual">
                    <x-ui.input type="password" wire:model="currentPassword" placeholder="••••••" />
                    @error('currentPassword') <p class="mt-1 text-xs font-medium text-destructive">{{ $message }}</p> @enderror
                </x-ui.field>
                <div class="flex justify-end gap-2">
                    <x-ui.button type="button" variant="outline" wire:click="$set('emailOpen', false)">Cancelar</x-ui.button>
                    <x-ui.button type="submit">Salvar</x-ui.button>
                </div>
            </form>
        </x-ui.modal>
    @endif

    {{-- Change / set password dialog --}}
    @if ($passwordOpen)
        <x-ui.modal wire:click.self="$set('passwordOpen', false)">
            <h2 class="flex items-center gap-2 font-display text-lg font-bold"><x-ui.icon name="key-round" class="h-4 w-4" /> {{ $hasPassword ? 'Alterar senha' : 'Definir senha' }}</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ $hasPassword ? 'Escolha uma nova senha com no mínimo 6 caracteres.' : 'Crie uma senha para também poder entrar com e-mail e senha.' }}
            </p>
            <form wire:submit="updatePassword" class="mt-3 space-y-3">
                <x-ui.field label="Nova senha">
                    <x-ui.input type="password" wire:model="newPassword" placeholder="••••••" />
                    @error('newPassword') <p class="mt-1 text-xs font-medium text-destructive">{{ $message }}</p> @enderror
                </x-ui.field>
                <x-ui.field label="Confirmar nova senha">
                    <x-ui.input type="password" wire:model="passwordConfirmation" placeholder="••••••" />
                </x-ui.field>
                @if ($hasPassword)
                    <x-ui.field label="Senha atual">
                        <x-ui.input type="password" wire:model="currentPassword" placeholder="••••••" />
                        @error('currentPassword') <p class="mt-1 text-xs font-medium text-destructive">{{ $message }}</p> @enderror
                    </x-ui.field>
                @endif
                <div class="flex justify-end gap-2">
                    <x-ui.button type="button" variant="outline" wire:click="$set('passwordOpen', false)">Cancelar</x-ui.button>
                    <x-ui.button type="submit">{{ $hasPassword ? 'Salvar nova senha' : 'Definir senha' }}</x-ui.button>
                </div>
            </form>
        </x-ui.modal>
    @endif
</div>
