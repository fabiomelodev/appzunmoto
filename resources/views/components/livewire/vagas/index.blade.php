{{-- resources/views/livewire/vagas/index.blade.php --}}
<div class="px-4 pt-6 pb-nav">

    {{-- Header --}}
    <header class="flex items-center justify-between gap-2">
        {{-- Logo --}}
        <div class="flex items-center gap-2">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#f97316]/15">
                <svg class="h-5 w-5 text-[#f97316]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                </svg>
            </div>
            <span class="font-bold tracking-tight">Moto<span class="text-[#f97316]">Reserva</span></span>
        </div>

        {{-- Veículo ativo --}}
        <div class="flex items-center gap-2">
            <button wire:click="$set('vehicleOpen', true)"
                    class="tap flex min-w-0 items-center gap-1.5 rounded-full border border-[#f97316]/40 bg-[#f97316]/10 px-3 py-2 text-[11px] font-semibold text-[#f97316] transition hover:bg-[#f97316]/20">
                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375..." />
                </svg>
                <span class="max-w-[120px] truncate">
                    {{ $profile?->veiculo === 'bike-eletrica' ? 'M. Elétrica' : ucfirst($profile?->veiculo ?? 'Moto') }}
                </span>
            </button>

            <a href="{{ route('notificacoes') }}"
               class="tap relative grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-[#2a2a2a] bg-[#161616] text-[#737373] transition hover:text-[#f5f5f5]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                </svg>
                <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-[#f97316] ring-2 ring-[#0d0d0d]"></span>
            </a>
        </div>
    </header>

    {{-- Título --}}
    <div class="mt-5">
        <h1 class="text-3xl font-bold leading-tight tracking-tight">
            Sua <span class="bg-gradient-to-r from-[#f97316] to-[#fbbf24] bg-clip-text text-transparent">parceria</span> começa aqui
        </h1>
        <p class="mt-1 text-sm text-[#737373]">Vagas disponíveis na sua região hoje.</p>
    </div>

    {{-- Busca --}}
    <div class="mt-4 relative">
        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#737373]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
        </svg>
        <input wire:model.live.debounce.300ms="q"
               type="text"
               placeholder="Buscar restaurante, região…"
               class="pl-9" />
    </div>

    {{-- Botão filtros --}}
    <button wire:click="openFilters"
            class="mt-3 flex w-full items-center justify-center gap-2 rounded-xl border border-[#2a2a2a] bg-[#161616] px-3 py-2.5 text-xs font-semibold text-[#f5f5f5] transition hover:border-[#f97316]/40 hover:text-[#f97316]">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
        </svg>
        Filtros Avançados
        @if($filtrosAtivos > 0)
            <span class="ml-1 rounded-full bg-[#f97316] px-2 py-0.5 text-[10px] font-bold text-white">{{ $filtrosAtivos }}</span>
        @endif
    </button>

    {{-- Chips de região --}}
    <div class="mt-3 flex gap-2 overflow-x-auto pb-1 scrollbar-hide">
        <button wire:click="$set('regiao', null)"
                class="shrink-0 rounded-full px-3.5 py-1.5 text-xs font-semibold transition
                       {{ $regiao === null ? 'bg-[#f97316] text-white' : 'bg-[#1f1f1f] text-[#a3a3a3] hover:bg-[#1e1e1e]' }}">
            Todas
        </button>
        @foreach($regioes as $r)
            <button wire:click="$set('regiao', '{{ $r }}')"
                    class="shrink-0 rounded-full px-3.5 py-1.5 text-xs font-semibold transition
                           {{ $regiao === $r ? 'bg-[#f97316] text-white' : 'bg-[#1f1f1f] text-[#a3a3a3] hover:bg-[#1e1e1e]' }}">
                {{ $r }}
            </button>
        @endforeach
    </div>

    {{-- Lista de vagas --}}
    <div class="mt-4 space-y-3">
        @forelse($vagas as $vaga)
            @include('livewire.vagas.partials.vaga-card', ['vaga' => $vaga])
        @empty
            <div class="rounded-2xl border border-dashed border-[#2a2a2a] p-10 text-center text-sm text-[#737373]">
                Nenhuma vaga encontrada.
            </div>
        @endforelse
    </div>

    {{-- ===================== MODAL: Filtros Avançados ===================== --}}
    @if($filtersOpen)
    <div class="fixed inset-0 z-50 flex items-end justify-center bg-black/60 backdrop-blur-sm"
         wire:click.self="$set('filtersOpen', false)">
        <div class="w-full max-w-md max-h-[90dvh] overflow-y-auto rounded-t-3xl border border-[#2a2a2a] bg-[#161616] p-5"
             @click.stop>

            <div class="mb-4">
                <h2 class="text-lg font-bold">Filtros avançados</h2>
                <p class="text-sm text-[#737373]">Refine sua lista de vagas.</p>
            </div>

            <div class="space-y-5">
                {{-- Data --}}
                <div>
                    <label class="text-[11px] uppercase tracking-wider text-[#737373]">Data específica (opcional)</label>
                    <div class="mt-2 flex gap-2">
                        <input type="date" wire:model="draft.data" class="flex-1" />
                        @if($draft['data'])
                            <button wire:click="$set('draft.data', '')"
                                    class="rounded-xl border border-[#2a2a2a]/60 bg-[#161616] px-3 text-xs font-semibold text-[#737373] hover:text-[#f5f5f5]">
                                Limpar
                            </button>
                        @endif
                    </div>
                    <p class="mt-1 text-[10px] text-[#737373]">Sem data selecionada exibe todas as vagas.</p>
                </div>

                {{-- Veículos --}}
                <div>
                    <label class="text-[11px] uppercase tracking-wider text-[#737373]">Veículos aceitos</label>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach([['moto','Moto'],['bike-eletrica','Moto Elétrica'],['bike','Bicicleta']] as [$val,$lbl])
                            <button wire:click="toggleVeicDraft('{{ $val }}')"
                                    class="flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold transition border
                                           {{ in_array($val, $draft['veiculos']) ? 'border-[#f97316] bg-[#f97316]/15 text-[#f97316]' : 'border-[#2a2a2a]/60 bg-[#161616] text-[#737373]' }}">
                                {{ $lbl }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Valores --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-[11px] uppercase tracking-wider text-[#737373]">Diária mínima (R$)</label>
                        <input type="number" wire:model="draft.diariaMin" placeholder="40" />
                    </div>
                    <div>
                        <label class="text-[11px] uppercase tracking-wider text-[#737373]">Taxa média mín. (R$)</label>
                        <input type="number" wire:model="draft.entregaMin" step="0.5" placeholder="7" />
                    </div>
                </div>

                {{-- Hora início --}}
                <div>
                    <label class="text-[11px] uppercase tracking-wider text-[#737373]">Início a partir de</label>
                    <input type="time" wire:model="draft.horaInicio" />
                </div>

                {{-- Benefícios --}}
                <div>
                    <label class="text-[11px] uppercase tracking-wider text-[#737373]">Benefícios desejados</label>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        @foreach([['lanche','Lanche'],['almoco','Almoço'],['janta','Janta'],['combustivel','Combustível']] as [$val,$lbl])
                            <label class="flex cursor-pointer items-center gap-2 rounded-xl border p-2.5 text-xs
                                          {{ in_array($val, $draft['beneficios']) ? 'border-[#f97316] bg-[#f97316]/10 text-[#f97316]' : 'border-[#2a2a2a]/60 bg-[#161616]' }}">
                                <input type="checkbox"
                                       wire:click="toggleBenDraft('{{ $val }}')"
                                       {{ in_array($val, $draft['beneficios']) ? 'checked' : '' }}
                                       class="h-4 w-4 accent-[#f97316]" style="width:1rem;border-radius:3px;padding:0" />
                                {{ $lbl }}
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Bag própria --}}
                <div>
                    <label class="text-[11px] uppercase tracking-wider text-[#737373]">Exige Bag própria?</label>
                    <div class="mt-2 grid grid-cols-3 gap-2">
                        @foreach([['qualquer','Qualquer'],['sim','Sim'],['nao','Não']] as [$val,$lbl])
                            <button wire:click="$set('draft.bagPropria', '{{ $val }}')"
                                    class="rounded-xl border px-3 py-2 text-xs font-semibold transition
                                           {{ $draft['bagPropria'] === $val ? 'border-[#f97316] bg-[#f97316]/15 text-[#f97316]' : 'border-[#2a2a2a]/60 bg-[#161616] text-[#737373]' }}">
                                {{ $lbl }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Ações --}}
            <div class="mt-6 space-y-2">
                <button wire:click="applyFilters"
                        class="tap w-full rounded-xl bg-[#f97316] py-3 font-semibold text-white glow-orange">
                    Aplicar Filtros
                </button>
                <button wire:click="clearFilters"
                        class="w-full rounded-xl border border-[#2a2a2a]/60 bg-[#161616] py-3 text-xs font-semibold text-[#737373] hover:text-[#f5f5f5]">
                    Limpar filtros
                </button>
                <button wire:click="$set('filtersOpen', false)"
                        class="w-full rounded-xl py-2 text-xs font-medium text-[#737373] hover:text-[#f5f5f5]">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ===================== MODAL: Trocar veículo ===================== --}}
    @if($vehicleOpen)
    <div class="fixed inset-0 z-50 flex items-end justify-center bg-black/60 backdrop-blur-sm"
         wire:click.self="$set('vehicleOpen', false)">
        <div class="w-full max-w-md rounded-t-3xl border border-[#2a2a2a] bg-[#161616] p-5" @click.stop>
            <h2 class="text-lg font-bold">Trocar veículo ativo</h2>
            <p class="mb-4 text-sm text-[#737373]">Selecione o veículo que você está utilizando agora.</p>

            <div class="space-y-2">
                @foreach([['moto','Moto','Combustão · maior alcance'],['bike-eletrica','Moto Elétrica / Bike M.','Bateria/Combustão · médio alcance'],['bike','Bicicleta Convencional','Sem motor · entregas próximas']] as [$val,$lbl,$hint])
                    <button wire:click="atualizarVeiculo('{{ $val }}')"
                            class="tap flex w-full items-center gap-3 rounded-xl border p-3 text-left transition
                                   {{ $profile?->veiculo === $val ? 'border-[#f97316] bg-[#f97316]/10' : 'border-[#2a2a2a]/60 bg-[#1a1a1a] hover:border-[#f97316]/40' }}">
                        <span class="grid h-10 w-10 place-items-center rounded-lg {{ $profile?->veiculo === $val ? 'bg-[#f97316] text-white' : 'bg-[#1e1e1e] text-[#f97316]' }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125..." />
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold">{{ $lbl }}</span>
                            <span class="block truncate text-[11px] text-[#737373]">{{ $hint }}</span>
                        </span>
                        @if($profile?->veiculo === $val)
                            <svg class="h-5 w-5 shrink-0 text-[#f97316]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        @endif
                    </button>
                @endforeach
            </div>

            <div class="mt-4">
                <a href="{{ route('veiculo') }}"
                   class="block w-full rounded-xl border border-[#2a2a2a]/60 bg-[#161616] py-3 text-center text-xs font-semibold text-[#737373] hover:text-[#f5f5f5]">
                    Gerenciar documentos do veículo
                </a>
            </div>
        </div>
    </div>
    @endif

</div>
