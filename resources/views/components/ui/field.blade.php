@props(['label' => null])
{{-- Mirrors the React <Row> helper: a label stacked above its input. --}}
<div {{ $attributes->class('space-y-1.5') }}>
    @if ($label)
        <x-ui.label class="text-xs">{{ $label }}</x-ui.label>
    @endif
    {{ $slot }}
</div>
