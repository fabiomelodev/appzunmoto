@php
    use Illuminate\Support\Str;
    $name = $profile?->name ?: auth()->user()->name;
    $items = [
        ['route' => 'profile', 'icon' => 'user', 'label' => 'Meu Perfil', 'hint' => 'Dados pessoais, avaliações e histórico'],
        ['route' => 'vehicle', 'icon' => 'bike', 'label' => 'Veículo e Documentação', 'hint' => $profile?->vehicle ? (App\Support\Catalog::VEHICLE_LABEL[$profile->vehicle] ?? $profile->vehicle) : 'Defina seu veículo e envie documentos'],
        ['route' => 'addresses', 'icon' => 'map-pin', 'label' => 'Meus Endereços', 'hint' => 'Salve endereços para acelerar suas vagas'],
        ['route' => 'history', 'icon' => 'history', 'label' => 'Histórico de Turnos', 'hint' => 'Vagas realizadas e substituições'],
        ['route' => 'settings', 'icon' => 'settings', 'label' => 'Configurações', 'hint' => 'Tema, notificações e preferências'],
        ['route' => 'help', 'icon' => 'help-circle', 'label' => 'Ajuda e Suporte', 'hint' => 'FAQ e contato com o suporte'],
    ];
@endphp

<div class="pb-6">
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-primary/25 via-primary/5 to-transparent"></div>
        <div class="absolute -right-16 -top-16 h-56 w-56 rounded-full bg-primary/20 blur-3xl"></div>
        <div class="relative px-4 pb-6 pt-8">
            <div class="flex items-center gap-4">
                @if ($profile?->photo_url)
                    <img src="{{ $profile->photo_url }}" alt="" class="h-16 w-16 rounded-full border-2 border-primary/60 bg-surface object-cover" />
                @else
                    <span class="grid h-16 w-16 place-items-center rounded-full border-2 border-primary/60 bg-surface text-xl font-bold text-primary">{{ Str::upper(Str::substr($name, 0, 1)) }}</span>
                @endif
                <div class="min-w-0">
                    <h1 class="truncate font-display text-xl font-bold leading-tight">{{ $name }}</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-2 space-y-2 px-4">
        @foreach ($items as $it)
            <a href="{{ route($it['route']) }}" wire:navigate
                class="flex items-center gap-4 rounded-2xl border border-border/60 bg-card p-4 transition hover:border-primary/40 hover:bg-surface-elevated">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-primary/15 text-primary">
                    <x-ui.icon :name="$it['icon']" class="h-5 w-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-foreground">{{ $it['label'] }}</p>
                    <p class="truncate text-[11px] text-muted-foreground">{{ $it['hint'] }}</p>
                </div>
                <x-ui.icon name="chevron-right" class="h-4 w-4 shrink-0 text-primary" />
            </a>
        @endforeach

        <button wire:click="logout"
            class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl border border-border/60 bg-surface py-3 text-sm font-medium text-destructive transition hover:bg-surface-elevated">
            <x-ui.icon name="log-out" class="h-4 w-4" /> Sair da conta
        </button>
    </div>
</div>
