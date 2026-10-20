{{-- Admin "acessar como": always visible while inside a user's account, one tap back to the panel
     (POST /logout leaves the impersonation instead of ending the session). Used by every layout. --}}
@if (\App\Support\Impersonation::isActive())
    <div class="sticky top-0 z-[70] flex items-center justify-between gap-3 bg-amber-500 px-4 py-2 text-xs font-semibold text-black">
        <span class="min-w-0 truncate">Acessando como <strong>{{ auth()->user()?->name }}</strong></span>
        <form method="POST" action="{{ route('logout') }}" class="shrink-0">
            @csrf
            <button type="submit" class="rounded-lg bg-black/85 px-3 py-1 text-[11px] font-bold text-amber-400">Voltar ao painel</button>
        </form>
    </div>
@endif
