@props(['value' => 15, 'model' => null, 'change' => null])
{{-- Courier's search radius (5–20 km). `model`: a Livewire property kept in sync; `change`: a component
method called with the new km when the thumb is released (used where it saves immediately). --}}
<div x-data="{ km: {{ (int) $value }} }">
    <div class="flex items-baseline justify-between">
        <span class="text-xs font-medium text-muted-foreground">Raio de busca</span>
        <span class="font-display text-lg font-bold text-primary"><span x-text="km"></span> km</span>
    </div>
    <input type="range" min="{{ \App\Support\Radius::MIN_KM }}" max="{{ \App\Support\Radius::MAX_KM }}" step="1" x-model.number="km"
        @if ($model) wire:model="{{ $model }}" @endif
        @if ($change) x-on:change="$wire.{{ $change }}(km)" @endif
        aria-label="Raio de busca em quilômetros"
        class="mt-2 h-2 w-full cursor-pointer accent-primary" />
    <div class="mt-1 flex justify-between text-[10px] text-muted-foreground">
        <span>{{ \App\Support\Radius::MIN_KM }} km</span>
        <span>{{ \App\Support\Radius::MAX_KM }} km</span>
    </div>
    <p class="mt-2 text-[11px] leading-relaxed text-muted-foreground">
        Você só vê vagas até essa distância do seu bairro, em linha reta (o trajeto real pode ser um pouco maior). Vagas em que você já se candidatou continuam aparecendo.
    </p>
</div>
