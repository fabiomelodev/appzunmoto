{{-- resources/views/livewire/menu.blade.php --}}
@extends('layouts.app')
@section('title', 'Menu — MotoReserva')

@section('content')
<div class="pb-nav">
    <header class="sticky top-0 z-10 flex items-center gap-3 border-b border-[#2a2a2a] bg-[#0d0d0d]/90 px-4 py-4 backdrop-blur">
        <h1 class="text-xl font-bold">Menu</h1>
    </header>

    @auth
    <div class="px-4 pt-5">
        {{-- Perfil resumido --}}
        <a href="{{ route('perfil') }}"
           class="tap flex items-center gap-4 rounded-2xl border border-[#2a2a2a] bg-[#1a1a1a] p-4 transition hover:border-[#f97316]/30">
            <img src="{{ auth()->user()->profile?->foto_url ?? 'https://ui-avatars.com/api/?name='.urlencode(auth()->user()->name).'&background=f97316&color=fff&size=80' }}"
                 alt="" class="h-14 w-14 rounded-full bg-[#1f1f1f] object-cover" />
            <div class="min-w-0 flex-1">
                <p class="truncate font-semibold">{{ auth()->user()->name }}</p>
                <p class="truncate text-sm text-[#737373]">{{ auth()->user()->email }}</p>
            </div>
            <svg class="h-4 w-4 shrink-0 text-[#737373]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>
        </a>

        {{-- Links --}}
        <nav class="mt-4 space-y-1">
            @foreach([
                ['route'=>'historico','icon'=>'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z','label'=>'Histórico de Turnos'],
                ['route'=>'favoritos','icon'=>'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z','label'=>'Vagas Favoritas'],
                ['route'=>'notificacoes','icon'=>'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0','label'=>'Notificações'],
                ['route'=>'documentos','icon'=>'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z','label'=>'Documentos'],
                ['route'=>'enderecos','icon'=>'M15 10.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z','label'=>'Meus Endereços'],
                ['route'=>'configuracoes','icon'=>'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z M15 12a3 3 0 11-6 0 3 3 0 016 0z','label'=>'Configurações'],
                ['route'=>'ajuda','icon'=>'M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z','label'=>'Ajuda e Suporte'],
            ] as $item)
                <a href="{{ route($item['route']) }}"
                   class="tap flex items-center gap-3 rounded-xl border border-[#2a2a2a]/60 bg-[#1a1a1a] px-4 py-3.5 transition hover:border-[#f97316]/30">
                    <svg class="h-5 w-5 shrink-0 text-[#f97316]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                    </svg>
                    <span class="flex-1 text-sm font-medium">{{ $item['label'] }}</span>
                    <svg class="h-4 w-4 text-[#737373]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            @endforeach
        </nav>

        <p class="mt-6 text-center text-[10px] text-[#737373]">MotoReserva v1.0 · Feito para motoboys</p>
    </div>
    @endauth
</div>
@endsection
