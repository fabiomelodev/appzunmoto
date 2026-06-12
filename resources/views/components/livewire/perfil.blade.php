{{-- resources/views/livewire/perfil.blade.php --}}
<div class="pb-nav">

    {{-- Header com foto --}}
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-[#f97316]/25 via-[#f97316]/5 to-transparent"></div>
        <div class="relative px-4 pt-8 pb-6 text-center">
            <div class="relative inline-block">
                <img src="{{ $profile?->foto_url ?? 'https://ui-avatars.com/api/?name='.urlencode($profile?->nome ?? auth()->user()->name ?? '?').'&background=f97316&color=fff&size=128' }}"
                     alt="" class="h-24 w-24 rounded-full bg-[#1f1f1f] object-cover ring-4 ring-[#f97316]/30" />
            </div>
            <h1 class="mt-3 text-xl font-bold">{{ $profile?->nome ?? auth()->user()->name }}</h1>
            @if($profile?->cidade)
                <p class="mt-0.5 text-sm text-[#737373]">{{ $profile->cidade }}</p>
            @endif
            @if($profile && $profile->total_reviews > 0)
                <div class="mt-2 inline-flex items-center gap-1.5 rounded-full border border-[#f97316]/30 bg-[#f97316]/10 px-3 py-1 text-sm font-semibold text-[#f97316]">
                    <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24"><path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" /></svg>
                    {{ number_format($profile->avg_rating, 1) }}
                    <span class="text-[#737373] font-normal">({{ $profile->total_reviews }})</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Tabs --}}
    <div class="px-4">
        <div class="flex gap-1 rounded-2xl bg-[#161616] p-1 text-xs font-semibold overflow-x-auto scrollbar-hide">
            @foreach([['info','Dados'],['stats','Estatísticas'],['avaliacoes','Avaliações']] as [$t,$l])
                <button wire:click="$set('tab', '{{ $t }}')"
                        class="shrink-0 flex-1 rounded-xl py-2 transition
                               {{ $tab === $t ? 'bg-[#1e1e1e] text-[#f5f5f5] shadow' : 'text-[#737373]' }}">
                    {{ $l }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="mx-4 mt-4 rounded-xl border border-green-500/40 bg-green-500/10 px-4 py-3 text-sm text-green-400">
            {{ session('success') }}
        </div>
    @endif

    {{-- ===== TAB: DADOS ===== --}}
    @if($tab === 'info')
    <form wire:submit="salvar" class="mt-4 px-4 space-y-4">

        <div class="space-y-1.5">
            <label class="text-xs text-[#737373]">Nome completo</label>
            <input wire:model="nome" type="text" placeholder="Seu nome" />
            @error('nome') <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p> @enderror
        </div>

        <div class="space-y-1.5">
            <label class="text-xs text-[#737373]">CPF</label>
            <input wire:model="cpf" type="text" inputmode="numeric" placeholder="000.000.000-00" maxlength="14" />
        </div>

        <div class="space-y-1.5">
            <label class="text-xs text-[#737373]">Data de nascimento</label>
            <input wire:model="dataNascimento" type="text" inputmode="numeric" placeholder="DD/MM/AAAA" maxlength="10" />
        </div>

        <div class="space-y-1.5">
            <label class="text-xs text-[#737373]">Telefone / WhatsApp</label>
            <input wire:model="telefone" type="tel" placeholder="(11) 9 9999-0000" />
        </div>

        <div class="grid grid-cols-[1fr_90px] gap-2">
            <div class="space-y-1.5">
                <label class="text-xs text-[#737373]">Rua</label>
                <input wire:model="enderecoRua" type="text" placeholder="Av. Paulista" />
            </div>
            <div class="space-y-1.5">
                <label class="text-xs text-[#737373]">Número</label>
                <input wire:model="enderecoNumero" type="text" placeholder="100" />
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2">
            <div class="space-y-1.5">
                <label class="text-xs text-[#737373]">Bairro</label>
                <input wire:model="enderecoBairro" type="text" placeholder="Centro" />
            </div>
            <div class="space-y-1.5">
                <label class="text-xs text-[#737373]">Cidade</label>
                <input wire:model="cidade" type="text" placeholder="São Paulo - SP" />
            </div>
        </div>

        <div class="space-y-1.5">
            <label class="text-xs text-[#737373]">Bio (opcional)</label>
            <textarea wire:model="bio" rows="3" placeholder="Conte um pouco sobre você…" class="rounded-xl resize-none"></textarea>
        </div>

        {{-- Possui Bag --}}
        <button type="button" wire:click="$toggle('possuiBag')"
                class="flex w-full items-center gap-3 rounded-xl border p-3 text-left transition
                       {{ $possuiBag ? 'border-[#f97316] bg-[#f97316]/10' : 'border-[#2a2a2a]/60 bg-[#1a1a1a]' }}">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg {{ $possuiBag ? 'bg-[#f97316] text-white' : 'bg-[#1e1e1e] text-[#737373]' }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                </svg>
            </span>
            <span class="flex-1">
                <span class="block text-sm font-semibold {{ $possuiBag ? 'text-[#f97316]' : '' }}">Possuo Bag (Mochila Térmica)</span>
                <span class="text-[11px] text-[#737373]">Ative para aparecer em vagas que exigem Bag própria</span>
            </span>
            {{-- Toggle visual --}}
            <span class="shrink-0 h-6 w-10 rounded-full transition {{ $possuiBag ? 'bg-[#f97316]' : 'bg-[#2a2a2a]' }} relative">
                <span class="absolute top-1 h-4 w-4 rounded-full bg-white transition-all {{ $possuiBag ? 'left-5' : 'left-1' }}"></span>
            </span>
        </button>

        <button type="submit"
                wire:loading.attr="disabled"
                class="tap flex w-full items-center justify-center gap-2 rounded-xl bg-[#f97316] py-3 font-semibold text-white disabled:opacity-50 glow-orange">
            <span wire:loading wire:target="salvar" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
            <span wire:loading.remove wire:target="salvar">Salvar alterações</span>
        </button>

        {{-- Logout --}}
        <button type="button" wire:click="logout"
                class="flex w-full items-center justify-center gap-2 rounded-xl border border-red-500/30 bg-red-500/10 py-3 text-sm font-semibold text-red-400 transition hover:bg-red-500/20">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
            </svg>
            Sair da conta
        </button>

    </form>
    @endif

    {{-- ===== TAB: ESTATÍSTICAS ===== --}}
    @if($tab === 'stats')
    <div class="mt-4 px-4 space-y-3">
        <div class="grid grid-cols-2 gap-3">
            <div class="rounded-xl border border-[#2a2a2a] bg-[#1a1a1a] p-4 text-center">
                <div class="text-2xl font-bold text-[#f97316]">{{ $profile?->total_reviews ?? 0 }}</div>
                <div class="mt-1 text-xs text-[#737373]">Avaliações recebidas</div>
            </div>
            <div class="rounded-xl border border-[#2a2a2a] bg-[#1a1a1a] p-4 text-center">
                <div class="text-2xl font-bold text-[#f97316]">
                    {{ $profile?->total_reviews > 0 ? number_format($profile->avg_rating, 1) : '—' }}
                </div>
                <div class="mt-1 text-xs text-[#737373]">Nota média</div>
            </div>
        </div>
        <div class="rounded-xl border border-[#2a2a2a] bg-[#1a1a1a] p-4 text-center">
            <div class="text-[#737373] text-sm">Mais estatísticas em breve.</div>
        </div>
    </div>
    @endif

    {{-- ===== TAB: AVALIAÇÕES ===== --}}
    @if($tab === 'avaliacoes')
    <div class="mt-4 px-4 space-y-3">
        @forelse($reviews as $review)
            <div class="rounded-2xl border border-[#2a2a2a] bg-[#1a1a1a] p-4">
                <div class="flex items-center gap-2">
                    <div class="flex gap-0.5">
                        @for($i = 1; $i <= 5; $i++)
                            <svg class="h-4 w-4 {{ $i <= $review->nota ? 'fill-[#f97316] text-[#f97316]' : 'fill-none text-[#737373]/40' }}"
                                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                            </svg>
                        @endfor
                    </div>
                    <span class="ml-auto text-[10px] text-[#737373]">
                        {{ $review->created_at->locale('pt_BR')->diffForHumans() }}
                    </span>
                </div>
                @if($review->comentario)
                    <p class="mt-2 text-sm text-[#737373]">{{ $review->comentario }}</p>
                @endif
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-[#2a2a2a] p-10 text-center text-sm text-[#737373]">
                Nenhuma avaliação ainda.
            </div>
        @endforelse
    </div>
    @endif

</div>
