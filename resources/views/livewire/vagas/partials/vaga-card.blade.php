{{-- resources/views/livewire/vagas/partials/vaga-card.blade.php --}}
@php
    $expirada = now()->gt(\Carbon\Carbon::parse($vaga->data . 'T' . $vaga->horaFim . ':00'));
    $disponivel = $vaga->status === 'disponivel';
    $benefLabels = ['lanche'=>'Lanche','almoco'=>'Almoço','janta'=>'Janta','combustivel'=>'Combustível'];
@endphp
<a href="{{ route('vagas.show', $vaga->id) }}"
   class="tap block rounded-2xl border border-[#2a2a2a] bg-[#1a1a1a] p-4 transition hover:border-[#f97316]/30 active:scale-[.99]">

    {{-- Status + local --}}
    <div class="flex items-start justify-between gap-2">
        <div class="min-w-0 flex-1">
            <span class="inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase
                         {{ $disponivel ? 'bg-green-500/15 text-green-400' : 'bg-[#262626] text-[#737373]' }}">
                {{ $disponivel ? 'Disponível' : ($vaga->status === 'reservada' ? 'Reservada' : 'Preenchida') }}
            </span>
            <p class="mt-1.5 truncate font-semibold text-[#f5f5f5]">{{ $vaga->local }}</p>
            <p class="mt-0.5 flex items-center gap-1 text-xs text-[#737373]">
                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                </svg>
                {{ $vaga->regiao }}
            </p>
        </div>

        {{-- Valor diária --}}
        <div class="shrink-0 text-right">
            <span class="font-bold text-[#f97316]">R$ {{ number_format($vaga->valor_diaria, 0, ',', '.') }}</span>
            <p class="text-[10px] text-[#737373]">diária</p>
        </div>
    </div>

    {{-- Horário e taxa --}}
    <div class="mt-3 flex items-center gap-3 text-xs text-[#737373]">
        <span class="flex items-center gap-1">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ $vaga->hora_inicio }} – {{ $vaga->hora_fim }}
        </span>
        <span>·</span>
        <span>R$ {{ number_format($vaga->valor_entrega, 2, ',', '.') }}/entrega</span>
        <span>·</span>
        <span>{{ \Carbon\Carbon::parse($vaga->data)->locale('pt_BR')->isoFormat('D [de] MMM') }}</span>
    </div>

    {{-- Benefícios --}}
    @if(count($vaga->beneficios ?? []) > 0)
        <div class="mt-2.5 flex flex-wrap gap-1.5">
            @foreach($vaga->beneficios as $b)
                <span class="rounded-full border border-[#f97316]/30 bg-[#f97316]/10 px-2 py-0.5 text-[10px] font-semibold text-[#f97316]">
                    {{ $benefLabels[$b] ?? $b }}
                </span>
            @endforeach
        </div>
    @endif

</a>
