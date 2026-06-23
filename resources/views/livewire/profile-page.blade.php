@php
    use Illuminate\Support\Str;
    $stats = $this->stats;
    $rating = $stats['rating'];
    $totalReviews = $stats['totalReviews'];
    $displayName = $profile?->name ?: $user->name;
@endphp

<div class="pb-6">
    {{-- Header --}}
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-primary/25 via-primary/5 to-transparent"></div>
        <div class="absolute -right-16 -top-16 h-56 w-56 rounded-full bg-primary/20 blur-3xl"></div>

        <div class="relative px-4 pb-6 pt-6">
            <button type="button" x-on:click="window.history.back()" aria-label="Voltar"
                class="mb-4 grid h-10 w-10 place-items-center rounded-full border border-border/60 bg-surface text-foreground transition hover:bg-surface-elevated active:scale-95">
                <x-ui.icon name="arrow-left" class="h-5 w-5" />
            </button>

            <div class="flex items-start gap-4">
                <div class="relative">
                    @if ($profile?->photo_url)
                        <img src="{{ $profile->photo_url }}" alt="" class="relative h-24 w-24 rounded-full border-2 border-primary/60 bg-surface object-cover" />
                    @else
                        <span class="grid h-24 w-24 place-items-center rounded-full border-2 border-primary/60 bg-surface text-3xl font-bold text-primary">{{ Str::upper(Str::substr($displayName, 0, 1)) }}</span>
                    @endif
                    <label class="absolute -bottom-0.5 -right-0.5 grid h-8 w-8 cursor-pointer place-items-center rounded-full border-2 border-background bg-primary text-primary-foreground shadow-md transition active:scale-95" aria-label="Alterar foto">
                        <x-ui.icon name="camera" class="h-4 w-4" />
                        <input type="file" accept="image/*" class="hidden" wire:model="photo" />
                    </label>
                </div>

                <div class="min-w-0 flex-1 pt-1">
                    <div class="flex items-center gap-1.5">
                        <h1 class="truncate font-display text-2xl font-bold leading-tight">{{ $displayName }}</h1>
                        <x-ui.icon name="badge-check" class="h-5 w-5 shrink-0 text-primary" />
                    </div>
                    @if ($profile?->city)
                        <p class="mt-0.5 flex items-center gap-1 text-xs text-muted-foreground"><x-ui.icon name="map-pin" class="h-3 w-3" />{{ $profile->city }}</p>
                    @endif
                    <div wire:loading wire:target="photo" class="mt-1 text-[11px] text-muted-foreground">Enviando foto…</div>
                </div>
            </div>

            <div class="mt-4 flex items-center gap-2">
                <div class="flex items-center gap-0.5">
                    @for ($i = 1; $i <= 5; $i++)
                        <x-ui.icon name="star" class="h-4 w-4 {{ $i <= round($rating) ? 'text-primary fill-current' : 'text-muted-foreground/30' }}" />
                    @endfor
                </div>
                <span class="text-xs text-muted-foreground">
                    @if ($totalReviews > 0)
                        <span class="font-semibold text-foreground">{{ number_format($rating, 1, ',', '') }}</span> · {{ $totalReviews }} {{ $totalReviews === 1 ? 'avaliação' : 'avaliações' }}
                    @else
                        Sem avaliações ainda
                    @endif
                </span>
            </div>

            <div class="mt-4 flex flex-wrap gap-1.5">
                <x-profile-badge icon="badge-check" label="Verificado" />
                <x-profile-badge icon="sparkles" label="Entregador ativo" />
                @if ($totalReviews === 0 && $stats['completed'] === 0)
                    <x-profile-badge icon="shield" label="Conta nova" />
                @else
                    <x-profile-badge icon="trending-up" label="Entregador experiente" />
                @endif
            </div>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="-mt-2 px-4">
        <div class="grid grid-cols-3 gap-2">
            <x-profile-stat icon="bike" :value="$stats['published']" label="Vagas" />
            <x-profile-stat icon="calendar-check" :value="$stats['completed']" label="Escalas" />
            <x-profile-stat icon="trending-up" :value="$totalReviews" label="Reviews" />
        </div>
    </div>

    {{-- Tabs --}}
    <div class="mt-6 px-4">
        <div class="flex gap-1.5 rounded-2xl border border-border bg-surface p-1">
            @foreach (['info' => 'Pessoal', 'stats' => 'Estatísticas', 'reviews' => 'Avaliações'] as $id => $label)
                <button wire:click="$set('tab', '{{ $id }}')"
                    class="flex-1 whitespace-nowrap rounded-xl px-3 py-2 text-xs font-semibold transition {{ $tab === $id ? 'bg-primary text-primary-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="mt-5 px-4">
        @if ($tab === 'info')
            <form wire:submit="save" class="space-y-5">
                <x-profile-section title="Dados pessoais">
                    <x-ui.field label="Nome completo"><x-ui.input wire:model="name" class="h-12 rounded-xl" /></x-ui.field>
                    @error('name') <p class="text-xs font-medium text-destructive">{{ $message }}</p> @enderror
                    <x-ui.field label="E-mail"><x-ui.input :value="$user->email" disabled class="h-12 rounded-xl opacity-70" /></x-ui.field>
                    <x-ui.field label="CPF"><x-ui.input wire:model="cpf" inputmode="numeric" placeholder="000.000.000-00" class="h-12 rounded-xl" x-on:input="$el.value = window.maskCPF($el.value)" /></x-ui.field>
                    <x-ui.field label="Data de nascimento"><x-ui.input wire:model="birthDate" inputmode="numeric" placeholder="DD/MM/AAAA" class="h-12 rounded-xl" x-on:input="$el.value = window.maskDate($el.value)" /></x-ui.field>
                    <x-ui.field label="Telefone / WhatsApp"><x-ui.input wire:model="phone" inputmode="tel" placeholder="(11) 9 9999-0000" class="h-12 rounded-xl" x-on:input="$el.value = window.maskPhone($el.value)" /></x-ui.field>
                </x-profile-section>

                <x-profile-section title="Endereço">
                    <div class="grid grid-cols-[1fr_90px] gap-2">
                        <x-ui.field label="Rua"><x-ui.input wire:model="street" placeholder="Av. Paulista" class="h-12 rounded-xl" /></x-ui.field>
                        <x-ui.field label="Número"><x-ui.input wire:model="streetNumber" placeholder="100" class="h-12 rounded-xl" /></x-ui.field>
                    </div>
                    <x-ui.field label="Bairro"><x-ui.input wire:model="district" placeholder="Centro" class="h-12 rounded-xl" /></x-ui.field>
                    <x-ui.field label="Cidade"><x-ui.input wire:model="city" placeholder="São Paulo - SP" class="h-12 rounded-xl" /></x-ui.field>
                </x-profile-section>

                <x-profile-section title="Sobre você">
                    <x-ui.field label="Bio"><x-ui.textarea wire:model="bio" rows="3" placeholder="Conte um pouco sobre você…" class="rounded-xl"></x-ui.textarea></x-ui.field>
                </x-profile-section>

                <x-profile-section title="Equipamentos disponíveis">
                    <label class="flex cursor-pointer items-center justify-between rounded-xl border border-border/60 bg-surface px-4 py-3">
                        <span class="flex items-center gap-3">
                            <span class="grid h-9 w-9 place-items-center rounded-lg bg-primary/15 text-primary"><x-ui.icon name="package" class="h-4 w-4" /></span>
                            <span>
                                <span class="block text-sm font-semibold text-foreground">Possuo Mochila Térmica (Bag)</span>
                                <span class="block text-[11px] text-muted-foreground">Alguns restaurantes exigem que você tenha uma bag.</span>
                            </span>
                        </span>
                        <x-ui.switch wire:model="hasBag" />
                    </label>
                </x-profile-section>

                <x-ui.button type="submit" size="lg" class="h-12 w-full rounded-xl" wire:loading.attr="disabled" wire:target="save">
                    <x-ui.icon name="save" class="mr-2 h-4 w-4" /> Salvar alterações
                </x-ui.button>
            </form>
        @elseif ($tab === 'stats')
            <x-profile-section title="Estatísticas">
                <x-profile-statrow label="Vagas publicadas" :value="$stats['published']" />
                <x-profile-statrow label="Escalas concluídas" :value="$stats['completed']" />
                <x-profile-statrow label="Avaliações recebidas" :value="$totalReviews" />
                <x-profile-statrow label="Nota média" :value="$totalReviews > 0 ? number_format($rating, 1, ',', '') : '—'" highlight />
            </x-profile-section>
        @else
            <x-profile-section title="Avaliações recebidas">
                @forelse ($this->reviews as $r)
                    @php $author = $r->author?->profile?->name ?: 'Usuário'; @endphp
                    <div class="rounded-xl border border-border/60 bg-surface p-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="grid h-8 w-8 place-items-center rounded-full bg-primary/15 text-primary"><x-ui.icon name="message-square" class="h-3.5 w-3.5" /></div>
                                <div>
                                    <p class="text-xs font-semibold">{{ $author }}</p>
                                    <p class="text-[10px] text-muted-foreground">{{ \Illuminate\Support\Carbon::parse($r->created_at)->format('d/m/Y') }}</p>
                                </div>
                            </div>
                            <div class="flex gap-0.5">
                                @for ($i = 1; $i <= 5; $i++)
                                    <x-ui.icon name="star" class="h-3 w-3 {{ $i <= $r->rating ? 'text-primary fill-current' : 'text-muted-foreground/30' }}" />
                                @endfor
                            </div>
                        </div>
                        @if ($r->comment)
                            <p class="mt-2 text-xs text-muted-foreground">{{ $r->comment }}</p>
                        @endif
                    </div>
                @empty
                    <x-empty-state text="Nenhuma avaliação ainda. Conclua vagas para receber avaliações." />
                @endforelse
            </x-profile-section>
        @endif

        <button wire:click="logout"
            class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl border border-border/60 bg-surface py-3 text-sm font-medium text-destructive transition hover:bg-surface-elevated">
            <x-ui.icon name="log-out" class="h-4 w-4" /> Sair da conta
        </button>
    </div>
</div>
