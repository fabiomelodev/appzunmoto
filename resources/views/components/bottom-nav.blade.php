@php
    $items = [
        ['route' => 'shifts.index', 'pattern' => 'shifts.index', 'icon' => 'bike', 'label' => 'Vagas'],
        ['route' => 'map', 'pattern' => 'map', 'icon' => 'map', 'label' => 'Mapa'],
        ['route' => 'chats.index', 'pattern' => 'chats.*', 'icon' => 'handshake', 'label' => 'Parcerias', 'class' => 'col-start-4'],
        ['route' => 'menu', 'pattern' => 'menu', 'icon' => 'layout-grid', 'label' => 'Menu'],
    ];
    // O perfil já define quem está publicando — não precisa mais perguntar.
    $currentRole = auth()->user()->profile?->role === 'business' ? 'business' : 'courier';
@endphp

{{-- lg: replaced by the fixed left sidebar (see <x-sidebar-nav />) --}}
<div class="lg:hidden">
    <nav
        class="app-shell fixed bottom-0 left-1/2 z-40 -translate-x-1/2 border-t border-border bg-surface/95 px-2 pb-[env(safe-area-inset-bottom)] backdrop-blur-xl">
        <div class="relative grid grid-cols-5">
            @foreach ($items as $item)
                @php $active = request()->routeIs($item['pattern']); @endphp
                <a href="{{ route($item['route']) }}" wire:navigate
                    class="tap flex flex-col items-center justify-center gap-0.5 py-3 text-[10px] transition {{ $active ? 'text-primary' : 'text-muted-foreground hover:text-foreground' }} {{ $item['class'] ?? '' }}">
                    <x-ui.icon :name="$item['icon']" :stroke="$active ? 2.4 : 1.8" class="h-5 w-5" />
                    <span class="font-medium">{{ $item['label'] }}</span>
                </a>
            @endforeach

            <a href="{{ route('shifts.create', ['as' => $currentRole]) }}" wire:navigate
                class="tap glow-orange absolute left-1/2 top-0 grid h-14 w-14 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-primary text-primary-foreground"
                aria-label="Publicar vaga">
                <x-ui.icon name="plus" :stroke="2.8" class="h-7 w-7" />
            </a>
        </div>
    </nav>
    <div class="h-20"></div>
</div>
