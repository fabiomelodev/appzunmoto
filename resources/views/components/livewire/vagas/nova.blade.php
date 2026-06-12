{{-- resources/views/livewire/vagas/nova.blade.php --}}
<div class="px-4 pb-nav pt-4">

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('vagas.index') }}"
           class="grid h-10 w-10 place-items-center rounded-xl border border-[#2a2a2a] bg-[#161616]">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold">Nova Vaga</h1>
            <p class="text-xs text-[#737373]">Preencha os dados para publicar</p>
        </div>
    </div>

    {{-- Tipo de criador --}}
    <div class="mt-5">
        <label class="mb-2 block text-xs uppercase tracking-wider text-[#737373]">Você está criando como</label>
        <div class="grid grid-cols-2 gap-2">
            <button wire:click="$set('criadorTipo', 'restaurante')"
                    class="flex items-center gap-2 rounded-xl border p-3 text-sm font-semibold transition
                           {{ $criadorTipo === 'restaurante' ? 'border-[#f97316] bg-[#f97316]/10 text-[#f97316]' : 'border-[#2a2a2a]/60 bg-[#1a1a1a] text-[#737373]' }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" /></svg>
                Restaurante
            </button>
            <button wire:click="$set('criadorTipo', 'motoboy')"
                    class="flex items-center gap-2 rounded-xl border p-3 text-sm font-semibold transition
                           {{ $criadorTipo === 'motoboy' ? 'border-[#f97316] bg-[#f97316]/10 text-[#f97316]' : 'border-[#2a2a2a]/60 bg-[#1a1a1a] text-[#737373]' }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375..." /></svg>
                Motoboy (cobertura)
            </button>
        </div>
    </div>

    {{-- Formulário --}}
    <form wire:submit="salvar" class="mt-5 space-y-4">

        <div class="space-y-1.5">
            <label class="text-xs text-[#737373]">Nome do local / estabelecimento</label>
            <input wire:model="local" type="text" placeholder="Ex: Restaurante Sabor da Casa" />
            @error('local') <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1.5">
                <label class="text-xs text-[#737373]">Região</label>
                <input wire:model="regiao" type="text" placeholder="Ex: Centro, Zona Sul" />
                @error('regiao') <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p> @enderror
            </div>
            <div class="space-y-1.5">
                <label class="text-xs text-[#737373]">CEP (opcional)</label>
                <input wire:model="cep" type="text" inputmode="numeric" placeholder="00000-000" maxlength="9" />
            </div>
        </div>

        <div class="space-y-1.5">
            <label class="text-xs text-[#737373]">Endereço completo</label>
            <input wire:model="endereco" type="text" placeholder="Rua, número, bairro" />
            @error('endereco') <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p> @enderror
        </div>

        <div class="space-y-1.5">
            <label class="text-xs text-[#737373]">Data</label>
            <input wire:model="data" type="date" min="{{ now()->toDateString() }}" />
            @error('data') <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1.5">
                <label class="text-xs text-[#737373]">Início</label>
                <input wire:model="horaInicio" type="time" />
            </div>
            <div class="space-y-1.5">
                <label class="text-xs text-[#737373]">Fim</label>
                <input wire:model="horaFim" type="time" />
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1.5">
                <label class="text-xs text-[#737373]">Diária (R$)</label>
                <input wire:model="valorDiaria" type="number" min="0" step="5" placeholder="150" />
                @error('valorDiaria') <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p> @enderror
            </div>
            <div class="space-y-1.5">
                <label class="text-xs text-[#737373]">Valor médio/entrega (R$)</label>
                <input wire:model="valorEntrega" type="number" min="0" step="0.5" placeholder="8" />
            </div>
        </div>

        {{-- Veículos aceitos --}}
        <div>
            <label class="text-xs text-[#737373]">Veículos aceitos</label>
            <div class="mt-2 space-y-2">
                @foreach([['moto','Moto','Combustão · maior alcance'],['bike-eletrica','Moto Elétrica / Bike M.','Bateria/Combustão · médio alcance'],['bike','Bicicleta Convencional','Sem motor · entregas próximas']] as [$val,$lbl,$hint])
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-3 transition
                                  {{ in_array($val, $veiculos) ? 'border-[#f97316] bg-[#f97316]/10' : 'border-[#2a2a2a]/60 bg-[#1a1a1a]' }}">
                        <input type="checkbox"
                               wire:click="toggleVeiculo('{{ $val }}')"
                               {{ in_array($val, $veiculos) ? 'checked' : '' }}
                               class="h-4 w-4 accent-[#f97316]" style="width:1rem;padding:0;border-radius:3px" />
                        <span class="flex-1">
                            <span class="block text-sm font-semibold">{{ $lbl }}</span>
                            <span class="text-[11px] text-[#737373]">{{ $hint }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Benefícios --}}
        <div>
            <label class="text-xs text-[#737373]">Benefícios oferecidos</label>
            <div class="mt-2 grid grid-cols-2 gap-2">
                @foreach([['lanche','Lanche'],['almoco','Almoço'],['janta','Janta'],['combustivel','Combustível']] as [$val,$lbl])
                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border p-2.5 text-xs
                                  {{ in_array($val, $beneficios) ? 'border-[#f97316] bg-[#f97316]/10 text-[#f97316]' : 'border-[#2a2a2a]/60 bg-[#1a1a1a]' }}">
                        <input type="checkbox"
                               wire:click="toggleBeneficio('{{ $val }}')"
                               {{ in_array($val, $beneficios) ? 'checked' : '' }}
                               class="h-4 w-4 accent-[#f97316]" style="width:1rem;padding:0;border-radius:3px" />
                        {{ $lbl }}
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Exige bag --}}
        <div>
            <label class="text-xs text-[#737373]">Exige Bag (Mochila Térmica) própria?</label>
            <div class="mt-2 grid grid-cols-2 gap-2">
                <button type="button" wire:click="$set('exigeBagPropria', true)"
                        class="rounded-xl border px-3 py-2.5 text-xs font-semibold transition
                               {{ $exigeBagPropria ? 'border-[#f97316] bg-[#f97316]/15 text-[#f97316]' : 'border-[#2a2a2a]/60 bg-[#1a1a1a] text-[#737373]' }}">
                    Sim, exige Bag
                </button>
                <button type="button" wire:click="$set('exigeBagPropria', false)"
                        class="rounded-xl border px-3 py-2.5 text-xs font-semibold transition
                               {{ !$exigeBagPropria ? 'border-[#f97316] bg-[#f97316]/15 text-[#f97316]' : 'border-[#2a2a2a]/60 bg-[#1a1a1a] text-[#737373]' }}">
                    Não precisa de Bag
                </button>
            </div>
        </div>

        {{-- Observações --}}
        <div class="space-y-1.5">
            <label class="text-xs text-[#737373]">Observações (opcional)</label>
            <textarea wire:model="observacoes" rows="3" placeholder="Informações adicionais para o motoboy…" class="rounded-xl resize-none"></textarea>
        </div>

        {{-- Botão publicar --}}
        <button type="submit"
                wire:loading.attr="disabled"
                class="tap flex w-full items-center justify-center gap-2 rounded-xl bg-[#f97316] py-3.5 font-semibold text-white transition hover:bg-[#ea6c0a] disabled:opacity-50 glow-orange">
            <span wire:loading wire:target="salvar" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
            <span wire:loading.remove wire:target="salvar">Publicar Vaga</span>
        </button>

    </form>
</div>
