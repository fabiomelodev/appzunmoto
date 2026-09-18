@php
    $items = [
        ['route' => 'shifts.index', 'pattern' => 'shifts.index', 'icon' => 'bike', 'label' => 'Vagas'],
        ['route' => 'map', 'pattern' => 'map', 'icon' => 'map', 'label' => 'Mapa'],
        ['route' => 'chats.index', 'pattern' => 'chats.*', 'icon' => 'handshake', 'label' => 'Parcerias'],
        ['route' => 'menu', 'pattern' => 'menu', 'icon' => 'layout-grid', 'label' => 'Menu'],
    ];
    // O perfil já define quem está publicando — não precisa mais perguntar (mesma regra do bottom-nav).
    $currentRole = auth()->user()->profile?->role === 'business' ? 'business' : 'courier';
@endphp

<aside class="fixed inset-y-0 left-0 z-40 hidden w-64 shrink-0 flex-col border-r border-border bg-surface/95 px-4 py-6 backdrop-blur-xl lg:flex">
    <a href="{{ route('shifts.index') }}" wire:navigate class="mb-6 inline-flex px-1">
        <x-logo :size="32" />
    </a>

    <a href="{{ route('shifts.create', ['as' => $currentRole]) }}" wire:navigate>
        <x-ui.button size="lg" class="w-full glow-orange">
            <x-ui.icon name="plus" :stroke="2.8" class="h-4 w-4" /> Criar vagas
        </x-ui.button>
    </a>

    <nav class="mt-6 flex flex-1 flex-col gap-1">
        @foreach ($items as $item)
            @php $active = request()->routeIs($item['pattern']); @endphp
            <a href="{{ route($item['route']) }}" wire:navigate
                class="tap flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ $active ? 'bg-accent text-primary' : 'text-muted-foreground hover:bg-surface-elevated hover:text-foreground' }}">
                <x-ui.icon :name="$item['icon']" :stroke="$active ? 2.4 : 1.8" class="h-5 w-5" />
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="mt-4 flex flex-col gap-1 border-t border-border pt-4">
        @php $settingsActive = request()->routeIs('settings'); @endphp
        <a href="{{ route('settings') }}" wire:navigate
            class="tap flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ $settingsActive ? 'bg-accent text-primary' : 'text-muted-foreground hover:bg-surface-elevated hover:text-foreground' }}">
            <x-ui.icon name="settings" :stroke="$settingsActive ? 2.4 : 1.8" class="h-5 w-5" />
            Configurações
        </a>
        <button type="button" x-on:click="window.mrLogout()"
            class="tap flex items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-destructive transition hover:bg-surface-elevated">
            <x-ui.icon name="log-out" class="h-5 w-5" />
            Sair
        </button>
    </div>
</aside>
