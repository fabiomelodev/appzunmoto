@props(['options' => [], 'selected' => '', 'action', 'label' => null, 'id' => null])
{{--
    Custom dropdown that replaces the browser's native <select> (which can't be styled).
    `options`: key => label. `selected`: the current key. `action`: the Livewire method called with
    the chosen key. Keyboard: ↑/↓ move, Enter/Space choose, Esc or Tab closes; click outside closes.
--}}
@php
    $keys = array_map('strval', array_keys($options));
    $labels = array_values($options);
    $selectedKey = (string) $selected;
    $selectedIndex = array_search($selectedKey, $keys, true);
    $selectedLabel = $selectedIndex !== false ? $labels[$selectedIndex] : ($labels[0] ?? '');
@endphp
{{-- Re-created when the selection changes server-side, so the local state never drifts from it. --}}
<div class="relative min-w-0" wire:key="select-menu-{{ $id ?? $action }}-{{ md5($selectedKey) }}"
    x-data="{
        open: false,
        active: -1,
        keys: @js($keys),
        selected: @js($selectedKey),
        toggle() {
            this.open = ! this.open;
            if (this.open) this.active = Math.max(0, this.keys.indexOf(this.selected));
        },
        close() { this.open = false; },
        move(step) {
            if (! this.open) { this.toggle(); return; }
            this.active = (this.active + step + this.keys.length) % this.keys.length;
            this.$nextTick(() => this.$refs.list.children[this.active]?.scrollIntoView({ block: 'nearest' }));
        },
        choose(index) {
            const key = this.keys[index];
            this.close();
            if (key !== undefined && key !== this.selected) { this.selected = key; $wire.{{ $action }}(key); }
        },
    }"
    x-on:click.outside="close()"
    x-on:keydown.escape.window="close()">
    <button type="button" @if ($id) id="{{ $id }}" @endif
        aria-haspopup="listbox" :aria-expanded="open.toString()" @if ($label) aria-label="{{ $label }}" @endif
        x-on:click="toggle()"
        x-on:keydown.arrow-down.prevent="move(1)"
        x-on:keydown.arrow-up.prevent="move(-1)"
        x-on:keydown.enter.prevent="open ? choose(active) : toggle()"
        x-on:keydown.space.prevent="open ? choose(active) : toggle()"
        x-on:keydown.tab="close()"
        {{ $attributes->class('tap flex min-w-0 max-w-full items-center gap-1.5 rounded-lg border border-border bg-surface py-1 pl-2.5 pr-2 text-left text-[11px] font-semibold text-foreground outline-none transition hover:border-primary/50 focus-visible:border-primary') }}
        :class="open ? 'border-primary' : ''">
        <span class="truncate">{{ $selectedLabel }}</span>
        <span class="shrink-0 text-muted-foreground transition-transform duration-150" :class="open ? 'rotate-180' : ''">
            <x-ui.icon name="chevron-down" class="h-3.5 w-3.5" />
        </span>
    </button>

    <ul x-show="open" x-cloak x-ref="list" role="listbox" tabindex="-1"
        x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="absolute left-0 z-30 mt-1 max-h-60 w-max min-w-full max-w-[16rem] overflow-auto rounded-xl border border-border bg-surface-elevated p-1 shadow-xl">
        @foreach ($keys as $i => $key)
            @php $isSelected = $key === $selectedKey; @endphp
            <li role="option" aria-selected="{{ $isSelected ? 'true' : 'false' }}"
                x-on:click="choose({{ $i }})" x-on:mouseenter="active = {{ $i }}"
                :class="active === {{ $i }} ? 'bg-primary/10' : ''"
                class="flex cursor-pointer items-center justify-between gap-3 rounded-lg px-2.5 py-2 text-xs {{ $isSelected ? 'font-semibold text-primary' : 'font-medium text-foreground' }}">
                <span class="truncate">{{ $labels[$i] }}</span>
                @if ($isSelected)
                    <x-ui.icon name="check" class="h-3.5 w-3.5 shrink-0" :stroke="2.6" />
                @endif
            </li>
        @endforeach
    </ul>
</div>
