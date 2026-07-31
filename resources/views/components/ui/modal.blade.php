@props([])
{{-- Centered modal. The caller controls visibility (e.g. with @if) and passes
     wire:click.self / @click.self to the root to close on backdrop click. --}}
<div {{ $attributes->class('fixed inset-0 z-50 flex items-end justify-center bg-black/60 p-4 sm:items-center') }}>
    <div class="max-h-[85vh] w-full max-w-sm overflow-y-auto rounded-2xl border border-border bg-card p-5">
        {{ $slot }}
    </div>
</div>
