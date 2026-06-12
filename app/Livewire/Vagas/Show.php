<?php
// app/Livewire/Vagas/Show.php

namespace App\Livewire\Vagas;

use App\Models\Vaga;
use App\Models\Candidatura;
use App\Models\Profile;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Show extends Component
{
    public string $vagaId;
    public bool $confirmOpen = false;
    public bool $reviewOpen  = false;
    public int $nota = 0;
    public string $comentario = '';
    public bool $enviandoReview = false;

    public function mount(string $id): void
    {
        $this->vagaId = $id;
    }

    public function registrarInteresse(): void
    {
        $user = Auth::user();
        Candidatura::firstOrCreate([
            'vaga_id' => $this->vagaId,
            'user_id' => $user->id,
        ], ['status' => 'interessado']);

        $this->confirmOpen = false;
        session()->flash('success', 'Interesse enviado!');
    }

    public function toggleFavorito(): void
    {
        // Implementar com tabela de favoritos se necessário
        session()->flash('info', 'Favorito atualizado.');
    }

    public function enviarReview(): void
    {
        $vaga = Vaga::find($this->vagaId);
        if (!$vaga || !$vaga->reservado_por || $this->nota === 0) return;

        $this->enviandoReview = true;

        Review::updateOrCreate(
            ['vaga_id' => $this->vagaId, 'autor_id' => Auth::id()],
            [
                'alvo_id'    => $vaga->reservado_por,
                'nota'       => $this->nota,
                'comentario' => trim($this->comentario),
            ]
        );

        $this->reviewOpen  = false;
        $this->nota        = 0;
        $this->comentario  = '';
        $this->enviandoReview = false;

        session()->flash('success', 'Avaliação enviada!');
    }

    public function render()
    {
        $vaga    = Vaga::findOrFail($this->vagaId);
        $user    = Auth::user();
        $profile = Profile::find($user->id);
        $criador = Profile::find($vaga->criador_id);

        $candidaturas = Candidatura::where('vaga_id', $this->vagaId)->get();
        $interessados = $candidaturas->pluck('user_id')->toArray();
        $profilesInteressados = Profile::whereIn('id', $interessados)->get()->keyBy('id');

        $jaInteressado = in_array($user->id, $interessados);
        $ehCriador     = $vaga->criador_id === $user->id;
        $fav           = false; // implementar com tabela favoritos

        $veiculosAceitos = $vaga->veiculos_aceitos ?? [];
        $semRestricao    = count($veiculosAceitos) === 0 || count($veiculosAceitos) === 3;
        $compativel      = $semRestricao || ($profile?->veiculo && in_array($profile->veiculo, $veiculosAceitos));
        $exigeBag        = (bool)$vaga->exige_bag_propria;
        $bloqueadoPorBag = $exigeBag && !$profile?->possui_bag;

        $expirada = now()->gt(\Carbon\Carbon::parse($vaga->data . 'T' . $vaga->hora_fim . ':00'));

        $veiculoLabels = ['moto'=>'Moto','bike-eletrica'=>'Moto Elétrica / Bike M.','bike'=>'Bicicleta Convencional'];
        $benefLabels   = ['lanche'=>'Lanche','almoco'=>'Almoço','janta'=>'Janta','combustivel'=>'Combustível'];

        return view('livewire.vagas.show', compact(
            'vaga','user','profile','criador','jaInteressado','ehCriador','fav',
            'veiculosAceitos','semRestricao','compativel','exigeBag','bloqueadoPorBag',
            'expirada','veiculoLabels','benefLabels','profilesInteressados'
        ))->layout('layouts.app')->title('Detalhes da vaga — MotoReserva');
    }
}
