@php
    $items = [
        ['route' => 'shifts.index', 'pattern' => 'shifts.index', 'icon' => 'bike', 'label' => 'Vagas'],
        ['route' => 'map', 'pattern' => 'map', 'icon' => 'map', 'label' => 'Mapa'],
        ['route' => 'chats.index', 'pattern' => 'chats.*', 'icon' => 'handshake', 'label' => 'Parcerias', 'class' => 'col-start-4'],
        ['route' => 'menu', 'pattern' => 'menu', 'icon' => 'layout-grid', 'label' => 'Menu'],
    ];
@endphp

<div x-data="{ open: false }">
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

            <button type="button" @click="open = true"
                class="tap glow-orange absolute left-1/2 top-0 grid h-14 w-14 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-primary text-primary-foreground"
                aria-label="Publicar vaga">
                <x-ui.icon name="plus" :stroke="2.8" class="h-7 w-7" />
            </button>
        </div>
    </nav>
    <div class="h-20"></div>

    {{-- "Who is creating this shift?" dialog --}}
    <div x-show="open" x-cloak x-transition.opacity
        class="fixed inset-0 z-50 flex items-end justify-center bg-black/60 p-4 sm:items-center" @click.self="open = false">
        <div x-show="open" x-transition
            class="w-full max-w-[340px] rounded-2xl border border-border/60 bg-surface p-6">
            <h2 class="font-display text-xl font-bold">Quem está criando esta vaga?</h2>
            <p class="mt-1.5 text-xs text-muted-foreground">Escolha o seu perfil para adaptarmos o formulário.</p>

            <div class="mt-4 space-y-2">
                <a href="{{ route('shifts.create', ['as' => 'business']) }}" wire:navigate
                    class="flex w-full items-center gap-3 rounded-xl border border-border/60 bg-card p-4 text-left transition hover:border-primary">
                    <span class="grid h-11 w-11 place-items-center rounded-lg bg-primary/15 text-primary">
                        <x-ui.icon name="store" class="h-5 w-5" />
                    </span>
                    <div class="flex-1">
                        <p class="text-sm font-semibold">Sou um Restaurante</p>
                        <p class="text-[11px] text-muted-foreground">Publicar vaga para meu estabelecimento</p>
                    </div>
                </a>

                <a href="{{ route('shifts.create', ['as' => 'courier']) }}" wire:navigate
                    class="flex w-full items-center gap-3 rounded-xl border border-border/60 bg-card p-4 text-left transition hover:border-primary">
                    <span class="grid h-11 w-11 place-items-center rounded-lg bg-primary/15 text-primary">
                        <x-ui.icon name="user" class="h-5 w-5" />
                    </span>
                    <div class="flex-1">
                        <p class="text-sm font-semibold">Sou um Motoboy</p>
                        <p class="text-[11px] text-muted-foreground">Pedir substituição de turno / folga</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>
