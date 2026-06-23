@props([])
{{-- A toggle switch backed by a checkbox so wire:model / x-model work directly. --}}
<label class="relative inline-flex cursor-pointer items-center">
    <input type="checkbox" {{ $attributes->class('peer sr-only') }} />
    <div class="h-6 w-11 rounded-full bg-input transition-colors peer-checked:bg-primary peer-focus-visible:ring-2 peer-focus-visible:ring-ring"></div>
    <div class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-background shadow transition-transform peer-checked:translate-x-5"></div>
</label>
