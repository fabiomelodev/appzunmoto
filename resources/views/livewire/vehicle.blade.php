@php
    use App\Support\Catalog;
    $docs = $this->docs;
@endphp

<div class="pb-6">
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-primary/25 via-primary/5 to-transparent"></div>
        <div class="relative flex items-center gap-3 px-4 pb-4 pt-6">
            <button type="button" x-on:click="window.history.back()"
                class="grid h-10 w-10 place-items-center rounded-full border border-border/60 bg-surface">
                <x-ui.icon name="arrow-left" class="h-5 w-5" />
            </button>
            <div>
                <h1 class="font-display text-xl font-bold leading-tight">Veículo e Documentação</h1>
                <p class="text-xs text-muted-foreground">Defina seu veículo e envie os documentos</p>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="mt-2 space-y-6 px-4">
        {{-- Vehicle --}}
        <x-profile-section title="Veículo utilizado">
            <div class="space-y-2">
                @foreach (Catalog::VEHICLE_OPTIONS as $v)
                    @php $active = $vehicle === $v; @endphp
                    <button type="button" wire:click="setVehicle('{{ $v }}')"
                        class="flex w-full items-center gap-3 rounded-xl border p-4 text-left transition {{ $active ? 'border-primary bg-primary/10 text-foreground' : 'border-border/60 bg-surface text-muted-foreground hover:text-foreground' }}">
                        <span class="grid h-10 w-10 place-items-center rounded-lg {{ $active ? 'bg-primary text-primary-foreground' : 'bg-surface-elevated' }}">
                            <x-ui.icon :name="Catalog::VEHICLE_ICON[$v]" class="h-5 w-5" />
                        </span>
                        <div class="flex-1">
                            <div class="text-sm font-semibold text-foreground">{{ Catalog::VEHICLE_LABEL[$v] }}</div>
                            <div class="text-[11px] text-muted-foreground">{{ Catalog::VEHICLE_HINT[$v] }}</div>
                        </div>
                        @if ($active)
                            <x-ui.icon name="check-circle" class="h-5 w-5 text-primary" />
                        @endif
                    </button>
                @endforeach
            </div>
        </x-profile-section>

        {{-- Documents --}}
        <x-profile-section title="Documentação">
            <div class="mb-1 flex items-center gap-2 rounded-xl border border-border/60 bg-surface px-3 py-2">
                <x-ui.icon name="lock" class="h-3.5 w-3.5 text-primary" />
                <p class="text-[11px] text-muted-foreground">Seus documentos ficam armazenados de forma segura e visíveis somente para validação.</p>
            </div>
            <x-doc-uploader label="Documento de identidade / CNH" hint="Frente e verso, foto nítida" :doc="$docs['identity']" model="identityFile" />
            @if ($vehicle === 'moto')
                <x-doc-uploader label="Documento do veículo (CRLV)" hint="CRLV atualizado da moto" :doc="$docs['vehicle']" model="vehicleFile" />
            @endif
        </x-profile-section>

        <x-ui.button type="submit" size="lg" class="h-12 w-full rounded-xl" wire:loading.attr="disabled" wire:target="save">
            <x-ui.icon name="save" class="mr-2 h-4 w-4" /> Salvar
        </x-ui.button>
    </form>
</div>
