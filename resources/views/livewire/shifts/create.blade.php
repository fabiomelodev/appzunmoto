@php
    use App\Support\Catalog;
    $isBusiness = $as === 'business';
    $editing = $editId !== null;
    // Persist a draft only when starting a brand-new shift (not editing/cloning).
    $persistDraft = $editId === null && empty($cloneId);
    $venueTypes = Catalog::venueTypes();
    $expectedVolumes = Catalog::expectedVolumes();
    $benefits = Catalog::benefits();
@endphp

<div class="px-4 pb-10 pt-4"
    @if ($persistDraft)
        x-init="loadDraft()"
        x-on:clear-shift-draft.window="clearDraft()"
    @endif
    x-data="{
    f: @js($initial),
    minCouriers: @js($minCouriers),
    get retroactive() {
        if (!this.f.date || !this.f.startTime) return false;
        return new Date(this.f.date + 'T' + this.f.startTime + ':00').getTime() < Date.now();
    },
    get allVehicles() { return this.f.vehicles.length === 3; },
    toggleAll() { this.f.vehicles = this.allVehicles ? ['moto'] : ['moto', 'bike-eletrica', 'bike']; },
    toggleVehicle(v) { this.f.vehicles = this.f.vehicles.includes(v) ? this.f.vehicles.filter(x => x !== v) : [...this.f.vehicles, v]; },
    toggleBenefit(b) { this.f.benefits = this.f.benefits.includes(b) ? this.f.benefits.filter(x => x !== b) : [...this.f.benefits, b]; },
    inc() { this.f.couriersNeeded = Math.min(10, this.f.couriersNeeded + 1); },
    dec() { this.f.couriersNeeded = Math.max(this.minCouriers, this.f.couriersNeeded - 1); },
    loadDraft() {
        try { const d = JSON.parse(localStorage.getItem('mr-new-shift-draft') || 'null'); if (d && typeof d === 'object') Object.assign(this.f, d); } catch (e) {}
        this.$watch('JSON.stringify(f)', (json) => { try { localStorage.setItem('mr-new-shift-draft', json); } catch (e) {} });
    },
    clearDraft() { try { localStorage.removeItem('mr-new-shift-draft'); } catch (e) {} },
}">
    {{-- Header --}}
    <div class="flex items-center gap-3">
        <button type="button" x-on:click="window.history.back()"
            class="grid h-10 w-10 place-items-center rounded-xl border border-border bg-surface">
            <x-ui.icon name="arrow-left" class="h-4 w-4" />
        </button>
        <div>
            <p class="flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wider text-primary">
                <x-ui.icon :name="$isBusiness ? 'store' : 'user'" class="h-3 w-3" /> {{ $editing ? 'Editar vaga' : 'Etapa 3 de 3' }}
            </p>
            <h1 class="font-display text-lg font-bold leading-tight">{{ $editing ? 'Editar detalhes' : ($isBusiness ? 'Detalhes do turno' : 'Cobertura de turno') }}</h1>
        </div>
    </div>

    {{-- Address summary --}}
    <div class="mt-4 rounded-2xl border border-primary/30 bg-gradient-to-br from-primary/10 to-transparent p-4">
        <div class="flex items-start gap-3">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-primary/20 text-primary">
                <x-ui.icon name="map-pin" class="h-5 w-5" />
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-primary">Local do turno</p>
                <p class="truncate text-sm font-semibold">{{ $venue ?: '—' }}</p>
                <p class="truncate text-[11px] text-muted-foreground">{{ $addressLine }}</p>
            </div>
            @unless ($editing)
                <a href="{{ route('addresses.choose', ['as' => $as]) }}" wire:navigate aria-label="Trocar endereço"
                    class="grid h-8 w-8 place-items-center rounded-lg bg-surface text-muted-foreground hover:text-primary">
                    <x-ui.icon name="pencil" class="h-3.5 w-3.5" />
                </a>
            @endunless
        </div>
    </div>

    <form @submit.prevent="$wire.save(f)" class="mt-5 space-y-4">
        <div x-show="retroactive" x-cloak class="rounded-xl border border-destructive/50 bg-destructive/10 p-3 text-xs font-semibold text-destructive">
            Atenção: esta data ou horário já passou. Ajuste o agendamento para um momento futuro.
        </div>

        <div class="grid grid-cols-2 gap-3">
            <x-ui.field label="Data"><x-ui.input type="date" x-model="f.date" /></x-ui.field>
            <x-ui.field label="Início"><x-ui.input type="time" x-model="f.startTime" /></x-ui.field>
        </div>
        <x-ui.field label="Fim"><x-ui.input type="time" x-model="f.endTime" /></x-ui.field>

        <x-ui.field label="Quantos motoboys você precisa?">
            <div class="flex items-center gap-3">
                <button type="button" @click="dec()" x-bind:disabled="f.couriersNeeded <= minCouriers"
                    class="grid h-10 w-10 place-items-center rounded-xl border border-border bg-surface text-lg font-bold transition hover:border-primary/40 hover:text-primary disabled:opacity-40">−</button>
                <div class="min-w-[3rem] text-center font-display text-2xl font-bold text-primary" x-text="f.couriersNeeded"></div>
                <button type="button" @click="inc()" x-bind:disabled="f.couriersNeeded >= 10"
                    class="grid h-10 w-10 place-items-center rounded-xl border border-border bg-surface text-lg font-bold transition hover:border-primary/40 hover:text-primary disabled:opacity-40">+</button>
                <span class="text-[11px] text-muted-foreground" x-text="f.couriersNeeded === 1 ? '1 motoboy' : f.couriersNeeded + ' motoboys'"></span>
            </div>
            @if ($minCouriers > 1)
                <p class="mt-2 flex items-start gap-1.5 text-[11px] leading-relaxed text-primary">
                    <x-ui.icon name="info" class="mt-0.5 h-3 w-3 shrink-0" />
                    Esta vaga já tem motoboys interessados — só é possível aumentar a quantidade.
                </p>
            @endif
        </x-ui.field>

        <x-ui.field label="Valor da diária (R$)">
            <x-ui.input type="number" inputmode="decimal" min="0" x-model="f.dailyRate" />
        </x-ui.field>

        <div>
            <x-ui.field label="Taxa por entrega (R$)"><x-ui.input type="number" inputmode="decimal" min="0" step="0.5" x-model="f.fee" /></x-ui.field>
            <p class="mt-1.5 text-[11px] leading-relaxed text-muted-foreground">Valor médio pago por entrega no local (ex.: R$ 8).</p>
        </div>

        <div>
            <x-ui.label class="mb-2 block">Tipo do local</x-ui.label>
            <div class="grid grid-cols-3 gap-2">
                @foreach ($venueTypes as $val => $lbl)
                    <button type="button" @click="f.venueType = '{{ $val }}'"
                        class="h-auto min-h-10 rounded-md border px-2 py-2 text-xs font-medium transition"
                        :class="f.venueType === '{{ $val }}' ? 'border-primary bg-primary text-primary-foreground' : 'border-input bg-background hover:bg-accent'">{{ $lbl }}</button>
                @endforeach
            </div>
        </div>

        <div>
            <x-ui.label class="mb-2 block">Movimento esperado</x-ui.label>
            <div class="grid grid-cols-3 gap-2">
                @foreach ($expectedVolumes as $val => $lbl)
                    <button type="button" @click="f.expectedVolume = '{{ $val }}'"
                        class="min-h-16 whitespace-pre-line rounded-md border px-1 py-2 text-[10px] font-medium leading-tight transition"
                        :class="f.expectedVolume === '{{ $val }}' ? 'border-primary bg-primary text-primary-foreground' : 'border-input bg-background hover:bg-accent'">{{ $lbl }}</button>
                @endforeach
            </div>
        </div>

        <div>
            <x-ui.label class="mb-2 block">Contato no local (opcional)</x-ui.label>
            <div class="grid grid-cols-2 gap-3">
                <x-ui.input maxlength="100" x-model="f.contactName" placeholder="Nome do responsável" />
                <x-ui.input type="tel" inputmode="tel" maxlength="16" x-model="f.contactPhone"
                    x-on:input="f.contactPhone = window.maskPhone($event.target.value)" placeholder="(11) 99999-9999" />
            </div>
            <p class="mt-1.5 text-[11px] text-muted-foreground">Visível apenas para o motoboy aceito na vaga.</p>
        </div>

        <div>
            <x-ui.label class="mb-2 block">Veículos aceitos</x-ui.label>
            <div class="space-y-2">
                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-border/60 bg-surface p-3">
                    <input type="checkbox" class="h-4 w-4 accent-[var(--color-primary)]" :checked="allVehicles" @change="toggleAll()" />
                    <span class="text-sm font-semibold">Todos os veículos</span>
                </label>
                @foreach (Catalog::VEHICLE_OPTIONS as $v)
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-3 transition"
                        :class="f.vehicles.includes('{{ $v }}') ? 'border-primary bg-primary/10' : 'border-border/60 bg-surface'">
                        <input type="checkbox" class="h-4 w-4 accent-[var(--color-primary)]" :checked="f.vehicles.includes('{{ $v }}')" @change="toggleVehicle('{{ $v }}')" />
                        <x-ui.icon :name="Catalog::VEHICLE_ICON[$v]" class="h-4 w-4 text-primary" />
                        <span class="text-sm">{{ Catalog::VEHICLE_LABEL[$v] }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <x-ui.label class="mb-2 block">Entregador precisa levar Bag própria?</x-ui.label>
            <div class="grid grid-cols-2 gap-2">
                <button type="button" @click="f.requiresOwnBag = true"
                    class="rounded-xl border px-3 py-2.5 text-sm font-semibold transition"
                    :class="f.requiresOwnBag ? 'border-primary bg-primary/15 text-primary' : 'border-border/60 bg-surface text-muted-foreground'">Sim</button>
                <button type="button" @click="f.requiresOwnBag = false"
                    class="rounded-xl border px-3 py-2.5 text-sm font-semibold transition"
                    :class="!f.requiresOwnBag ? 'border-primary bg-primary/15 text-primary' : 'border-border/60 bg-surface text-muted-foreground'">Não</button>
            </div>
        </div>

        <div>
            <x-ui.label class="mb-2 block">Benefícios</x-ui.label>
            <div class="flex flex-wrap gap-2">
                @foreach ($benefits as $benefit)
                    <button type="button" @click="toggleBenefit('{{ $benefit['slug'] }}')"
                        class="rounded-full px-3 py-1.5 text-xs font-semibold transition"
                        :class="f.benefits.includes('{{ $benefit['slug'] }}') ? 'bg-primary text-primary-foreground' : 'bg-secondary text-secondary-foreground'">{{ $benefit['name'] }}</button>
                @endforeach
            </div>
        </div>

        <x-ui.field label="Observações (opcional)">
            <x-ui.textarea x-model="f.notes" rows="3"
                :placeholder="$isBusiness ? 'Detalhes importantes para o motoboy…' : 'Conte o motivo da troca, horários flexíveis, etc.'"></x-ui.textarea>
        </x-ui.field>

        <x-ui.button type="submit" size="lg" class="w-full glow-orange" x-bind:disabled="retroactive"
            wire:loading.attr="disabled" wire:target="save">
            {{ $editing ? 'Salvar alterações' : ($isBusiness ? 'Publicar vaga' : 'Publicar pedido de cobertura') }}
        </x-ui.button>
    </form>
</div>
