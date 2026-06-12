{{-- resources/views/livewire/vagas/show.blade.php --}}
<div class="px-4 pb-32 pt-4">

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-green-500/40 bg-green-500/10 px-4 py-3 text-sm text-green-400">
            {{ session('success') }}
        </div>
    @endif

    {{-- Header com voltar e favorito --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('vagas.index') }}"
           class="grid h-10 w-10 place-items-center rounded-xl border border-[#2a2a2a] bg-[#161616]">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
        </a>
        <button wire:click="toggleFavorito"
                class="grid h-10 w-10 place-items-center rounded-xl border border-[#2a2a2a] bg-[#161616] transition
                       {{ $fav ? 'text-[#f97316]' : 'text-[#737373]' }}">
            <svg class="h-4 w-4" fill="{{ $fav ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
            </svg>
        </button>
    </div>

    {{-- Status + título --}}
    <div class="mt-5">
        <span class="inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase
                     {{ $vaga->status === 'disponivel' ? 'bg-green-500/15 text-green-400' : 'bg-[#262626] text-[#737373]' }}">
            {{ $vaga->status === 'preenchida' ? 'Preenchida' : ($vaga->status === 'reservada' ? 'Reservada' : 'Disponível') }}
        </span>
        <h1 class="mt-2 text-2xl font-bold">{{ $vaga->local }}</h1>
        <p class="flex items-center gap-1 text-sm text-[#737373]">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
            </svg>
            {{ $vaga->regiao }} · {{ $vaga->endereco }}
        </p>
    </div>

    {{-- Badge cobertura --}}
    @if($vaga->criador_tipo === 'motoboy')
        <div class="mt-4 flex items-center gap-2 rounded-xl border border-[#f97316]/30 bg-[#f97316]/10 p-3 text-xs font-semibold text-[#f97316]">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
            Vaga de cobertura criada por um colega motoboy
        </div>
    @endif

    {{-- Veículos aceitos --}}
    <div class="mt-5">
        <h3 class="mb-2 text-sm font-semibold">Veículos aceitos</h3>
        <div class="flex flex-wrap gap-2">
            @if($semRestricao)
                <span class="flex items-center gap-1.5 rounded-full border border-[#2a2a2a] bg-[#161616] px-3 py-1 text-xs font-semibold">
                    Todos os veículos
                </span>
            @else
                @foreach($veiculosAceitos as $v)
                    <span class="flex items-center gap-1.5 rounded-full border border-[#2a2a2a] bg-[#161616] px-3 py-1 text-xs font-semibold">
                        {{ $veiculoLabels[$v] ?? $v }}
                    </span>
                @endforeach
            @endif
        </div>
    </div>

    {{-- Bag --}}
    <div class="mt-5">
        <h3 class="mb-2 text-sm font-semibold">Exigência de Bag</h3>
        <div class="flex items-center gap-2 rounded-xl border p-3 text-xs font-semibold
                    {{ $exigeBag ? 'border-[#f97316]/40 bg-[#f97316]/10 text-[#f97316]' : 'border-[#2a2a2a]/60 bg-[#161616] text-[#f5f5f5]/80' }}">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                @if($exigeBag)
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                @else
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                @endif
            </svg>
            {{ $exigeBag ? 'Exigência: Precisa de Bag própria' : 'Exigência: Não precisa de Bag' }}
        </div>
    </div>

    {{-- Infos principais --}}
    <div class="mt-5 grid grid-cols-2 gap-3">
        <div class="rounded-xl border border-[#2a2a2a] bg-[#1a1a1a] p-3">
            <div class="text-[10px] uppercase tracking-wide text-[#737373]">Diária</div>
            <div class="mt-0.5 text-lg font-bold text-[#f97316]">R$ {{ number_format($vaga->valor_diaria, 0, ',', '.') }}</div>
        </div>
        <div class="rounded-xl border border-[#2a2a2a] bg-[#1a1a1a] p-3">
            <div class="text-[10px] uppercase tracking-wide text-[#737373]">Valor Médio por Entrega</div>
            <div class="mt-0.5 text-lg font-bold">R$ {{ number_format($vaga->valor_entrega, 2, ',', '.') }}</div>
        </div>
        <div class="rounded-xl border border-[#2a2a2a] bg-[#1a1a1a] p-3">
            <div class="text-[10px] uppercase tracking-wide text-[#737373]">Data</div>
            <div class="mt-0.5 text-lg font-bold">{{ \Carbon\Carbon::parse($vaga->data)->locale('pt_BR')->isoFormat('D [de] MMM') }}</div>
        </div>
        <div class="rounded-xl border border-[#2a2a2a] bg-[#1a1a1a] p-3">
            <div class="text-[10px] uppercase tracking-wide text-[#737373]">Horário</div>
            <div class="mt-0.5 flex items-center gap-1.5 text-lg font-bold">
                <svg class="h-3.5 w-3.5 text-[#737373]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                {{ $vaga->hora_inicio }} – {{ $vaga->hora_fim }}
            </div>
        </div>
    </div>

    {{-- Benefícios --}}
    @if(count($vaga->beneficios ?? []) > 0)
        <div class="mt-5">
            <h3 class="mb-2 text-sm font-semibold">Benefícios</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($vaga->beneficios as $b)
                    <span class="rounded-full border border-[#f97316]/30 bg-[#f97316]/10 px-3 py-1 text-xs font-semibold text-[#f97316]">
                        {{ $benefLabels[$b] ?? $b }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Observações --}}
    @if($vaga->observacoes)
        <div class="mt-5">
            <h3 class="mb-1 text-sm font-semibold">Observações</h3>
            <p class="rounded-xl border border-[#2a2a2a] bg-[#1a1a1a] p-3 text-sm text-[#737373]">
                {{ $vaga->observacoes }}
            </p>
        </div>
    @endif

    {{-- Criador --}}
    <div class="mt-5 flex items-center gap-3 rounded-2xl border border-[#2a2a2a] bg-[#1a1a1a] p-3">
        <img src="{{ $criador?->foto_url ?? 'https://ui-avatars.com/api/?name='.urlencode($criador?->nome ?? '?').'&background=f97316&color=fff' }}"
             alt="" class="h-12 w-12 rounded-full bg-[#1f1f1f] object-cover" />
        <div class="min-w-0 flex-1">
            <div class="text-xs text-[#737373]">Publicado por</div>
            <div class="truncate text-sm font-semibold">{{ $criador?->nome ?? '—' }}</div>
            @if($criador?->cidade)
                <div class="truncate text-xs text-[#737373]">{{ $criador->cidade }}</div>
            @endif
        </div>
    </div>

    {{-- Interessados (só criador vê) --}}
    @if($ehCriador)
        <div class="mt-6">
            <div class="mb-2 flex items-center justify-between">
                <h3 class="text-sm font-semibold">
                    Motoboys interessados
                    <span class="text-[#737373]">({{ count($profilesInteressados) }})</span>
                </h3>
                @if(count($profilesInteressados) > 0)
                    <a href="{{ route('chats.index') }}"
                       class="flex items-center gap-1 rounded-full bg-[#f97316]/15 px-2.5 py-1 text-[11px] font-semibold text-[#f97316] transition hover:bg-[#f97316]/25">
                        Gerenciar
                    </a>
                @endif
            </div>

            @forelse($profilesInteressados as $mid => $m)
                <div class="mb-2 flex items-center gap-3 rounded-xl border border-[#2a2a2a] bg-[#1a1a1a] p-3">
                    <img src="{{ $m->foto_url ?? 'https://ui-avatars.com/api/?name='.urlencode($m->nome ?? '?').'&background=f97316&color=fff' }}"
                         alt="" class="h-10 w-10 rounded-full bg-[#1f1f1f] object-cover" />
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-semibold">{{ $m->nome }}</div>
                        <div class="flex items-center gap-1 text-[11px] text-[#737373]">
                            <svg class="h-3 w-3 fill-[#f97316] text-[#f97316]" viewBox="0 0 24 24"><path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" /></svg>
                            {{ $m->total_reviews > 0 ? number_format($m->avg_rating, 1) : '—' }}
                            @if($m->cidade || $m->endereco_bairro)
                                · {{ implode(' — ', array_filter([$m->endereco_bairro, $m->cidade])) }}
                            @endif
                        </div>
                    </div>
                    @if($vaga->reservado_por === $mid)
                        <span class="rounded-full bg-green-500/15 px-2 py-0.5 text-[10px] font-semibold text-green-400">Aceito</span>
                    @endif
                </div>
            @empty
                <p class="text-sm text-[#737373]">Ainda ninguém demonstrou interesse.</p>
            @endforelse
        </div>

        {{-- Ações do criador em vaga expirada --}}
        @if($expirada)
            <div class="mt-6 space-y-2">
                <h3 class="text-sm font-semibold">Ações da vaga</h3>
                <a href="{{ route('vagas.nova', ['clone' => $vaga->id]) }}"
                   class="tap flex w-full items-center justify-center gap-2 rounded-xl border border-[#2a2a2a] bg-[#161616] py-3 font-semibold transition hover:border-[#f97316]/40">
                    Anunciar Novamente
                </a>
                @if($vaga->reservado_por)
                    <button wire:click="$set('reviewOpen', true)"
                            class="tap w-full rounded-xl bg-[#f97316] py-3 font-semibold text-white glow-orange">
                        Avaliar entregador
                    </button>
                @endif
            </div>
        @endif
    @endif

    {{-- ===== Botão fixo no bottom ===== --}}
    <div class="fixed bottom-20 left-1/2 z-30 w-full max-w-md -translate-x-1/2 px-4">
        @if($ehCriador)
            <a href="{{ route('chats.index') }}"
               class="tap flex w-full items-center justify-center gap-2 rounded-xl border border-[#2a2a2a] bg-[#161616] py-3.5 font-semibold transition hover:border-[#f97316]/40">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337..." /></svg>
                Ver conversas
            </a>
        @elseif($vaga->status !== 'disponivel')
            <button disabled class="w-full rounded-xl bg-[#262626] py-3.5 font-semibold text-[#737373] cursor-not-allowed">
                Vaga já reservada
            </button>
        @elseif($jaInteressado)
            <button disabled class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#1f1f1f] py-3.5 font-semibold text-[#737373] cursor-not-allowed">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                Interesse registrado
            </button>
        @elseif(!$compativel)
            <button disabled class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#1f1f1f] py-3.5 font-semibold text-[#737373] cursor-not-allowed">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                Vaga exclusiva para outro veículo
            </button>
        @elseif($bloqueadoPorBag)
            <div class="space-y-2">
                <button disabled class="w-full rounded-xl bg-[#1f1f1f] py-3.5 font-semibold text-[#737373] cursor-not-allowed">Vaga indisponível</button>
                <div class="flex items-start gap-2 rounded-xl border border-red-500/40 bg-red-500/10 p-3 text-[11px] font-medium text-[#f5f5f5]/90">
                    Este estabelecimento exige Bag própria. Acesse "Meu Perfil" para ativar.
                </div>
            </div>
        @else
            <button wire:click="$set('confirmOpen', true)"
                    class="tap w-full rounded-xl bg-[#f97316] py-3.5 font-semibold text-white glow-orange">
                Aceitar Vaga
            </button>
        @endif
    </div>

    {{-- ===== Modal: Confirmar interesse ===== --}}
    @if($confirmOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm px-4"
             wire:click.self="$set('confirmOpen', false)">
            <div class="w-full max-w-sm rounded-2xl border border-[#2a2a2a] bg-[#1a1a1a] p-5" @click.stop>
                <h2 class="text-lg font-bold">Confirmar aceitação</h2>
                <div class="mt-3 space-y-2">
                    <div class="flex gap-2 rounded-xl border border-[#f97316]/30 bg-[#f97316]/10 p-3 text-[#f97316] text-xs font-medium">
                        Confirme que você irá usar o veículo:
                        <strong>{{ $veiculoLabels[$profile?->veiculo ?? 'moto'] ?? 'não definido' }}</strong>
                    </div>
                    @if($exigeBag)
                        <div class="flex gap-2 rounded-xl border border-[#f97316]/30 bg-[#f97316]/10 p-3 text-[#f97316] text-xs font-medium">
                            Confirme que você está levando sua Bag (Mochila Térmica) própria.
                        </div>
                    @endif
                </div>
                <div class="mt-5 space-y-2">
                    <button wire:click="registrarInteresse"
                            class="tap w-full rounded-xl bg-[#f97316] py-3 font-semibold text-white glow-orange">
                        Confirmar e aceitar vaga
                    </button>
                    <button wire:click="$set('confirmOpen', false)"
                            class="w-full py-2 text-sm text-[#737373] hover:text-[#f5f5f5]">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== Modal: Avaliar entregador ===== --}}
    @if($reviewOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm px-4"
             wire:click.self="$set('reviewOpen', false)">
            <div class="w-full max-w-sm rounded-2xl border border-[#2a2a2a] bg-[#1a1a1a] p-5" @click.stop>
                <h2 class="text-lg font-bold">Avaliar entregador</h2>
                <p class="mt-1 text-sm text-[#737373]">Dê uma nota de 1 a 5 estrelas para essa parceria.</p>

                <div class="flex justify-center gap-1 py-4">
                    @for($i = 1; $i <= 5; $i++)
                        <button wire:click="$set('nota', {{ $i }})">
                            <svg class="h-8 w-8 {{ $i <= $nota ? 'fill-[#f97316] text-[#f97316]' : 'fill-none text-[#737373]/40' }}"
                                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                            </svg>
                        </button>
                    @endfor
                </div>

                <textarea wire:model="comentario"
                          placeholder="Comentário (opcional)"
                          rows="3"
                          class="rounded-xl"></textarea>

                <div class="mt-4 space-y-2">
                    <button wire:click="enviarReview"
                            wire:loading.attr="disabled"
                            @disabled($nota === 0)
                            class="tap w-full rounded-xl bg-[#f97316] py-3 font-semibold text-white glow-orange disabled:opacity-50">
                        <span wire:loading wire:target="enviarReview">Enviando…</span>
                        <span wire:loading.remove wire:target="enviarReview">Enviar avaliação</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
