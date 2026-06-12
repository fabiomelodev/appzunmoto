{{-- resources/views/components/bottom-nav.blade.php --}}
@php
    $route = request()->route()->getName() ?? '';
@endphp
<nav class="fixed bottom-0 left-0 right-0 z-50 border-t border-[#2a2a2a] bg-[#0d0d0d]/90 backdrop-blur-md">
    <div class="mx-auto flex max-w-md items-center justify-around px-2 py-2">

        {{-- Vagas --}}
        <a href="{{ route('vagas.index') }}"
           class="tap flex flex-col items-center gap-0.5 rounded-xl px-3 py-2 text-[10px] font-semibold transition
                  {{ str_starts_with($route, 'vagas') ? 'text-[#f97316]' : 'text-[#737373]' }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
            </svg>
            Vagas
        </a>

        {{-- Chats --}}
        <a href="{{ route('chats.index') }}"
           class="tap flex flex-col items-center gap-0.5 rounded-xl px-3 py-2 text-[10px] font-semibold transition
                  {{ str_starts_with($route, 'chats') ? 'text-[#f97316]' : 'text-[#737373]' }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
            </svg>
            Chats
        </a>

        {{-- Nova Vaga (destaque central) --}}
        <a href="{{ route('vagas.nova') }}"
           class="tap flex flex-col items-center gap-0.5 rounded-xl px-3 py-2 text-[10px] font-semibold transition
                  {{ $route === 'vagas.nova' ? 'text-[#f97316]' : 'text-[#737373]' }}">
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[#f97316] text-white shadow-lg glow-orange">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
            </span>
            Nova
        </a>

        {{-- Mapa --}}
        <a href="{{ route('mapa') }}"
           class="tap flex flex-col items-center gap-0.5 rounded-xl px-3 py-2 text-[10px] font-semibold transition
                  {{ $route === 'mapa' ? 'text-[#f97316]' : 'text-[#737373]' }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z" />
            </svg>
            Mapa
        </a>

        {{-- Menu --}}
        <a href="{{ route('menu') }}"
           class="tap flex flex-col items-center gap-0.5 rounded-xl px-3 py-2 text-[10px] font-semibold transition
                  {{ $route === 'menu' ? 'text-[#f97316]' : 'text-[#737373]' }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
            Menu
        </a>

    </div>
</nav>
