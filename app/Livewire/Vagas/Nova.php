<?php
// app/Livewire/Vagas/Nova.php

namespace App\Livewire\Vagas;

use App\Models\Vaga;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Nova extends Component
{
    public string $criadorTipo = 'restaurante';

    // Campos do formulário
    public string $local      = '';
    public string $regiao     = '';
    public string $endereco   = '';
    public string $cep        = '';
    public string $data       = '';
    public string $horaInicio = '18:00';
    public string $horaFim    = '23:00';
    public string $valorDiaria    = '150';
    public string $valorEntrega   = '8';
    public string $observacoes    = '';
    public bool $exigeBagPropria  = true;

    public array $beneficios     = [];
    public array $veiculos       = ['moto'];

    public bool $busy = false;

    protected function rules(): array
    {
        return [
            'local'        => 'required|min:2',
            'regiao'       => 'required|min:2',
            'endereco'     => 'required|min:2',
            'data'         => 'required|date',
            'horaInicio'   => 'required',
            'horaFim'      => 'required',
            'valorDiaria'  => 'required|numeric|min:0',
            'valorEntrega' => 'required|numeric|min:0',
        ];
    }

    public function mount(?string $clone = null): void
    {
        $this->data = now()->toDateString();

        if ($clone) {
            $vaga = Vaga::find($clone);
            if ($vaga) {
                $this->local          = $vaga->local;
                $this->regiao         = $vaga->regiao;
                $this->endereco       = $vaga->endereco;
                $this->horaInicio     = $vaga->hora_inicio;
                $this->horaFim        = $vaga->hora_fim;
                $this->valorDiaria    = (string)$vaga->valor_diaria;
                $this->valorEntrega   = (string)$vaga->valor_entrega;
                $this->beneficios     = $vaga->beneficios ?? [];
                $this->veiculos       = $vaga->veiculos_aceitos ?? ['moto'];
                $this->exigeBagPropria = (bool)$vaga->exige_bag_propria;
                $this->observacoes    = $vaga->observacoes ?? '';
                $this->criadorTipo    = $vaga->criador_tipo;
            }
        }
    }

    public function toggleBeneficio(string $b): void
    {
        if (in_array($b, $this->beneficios)) {
            $this->beneficios = array_values(array_filter($this->beneficios, fn($x) => $x !== $b));
        } else {
            $this->beneficios[] = $b;
        }
    }

    public function toggleVeiculo(string $v): void
    {
        if (in_array($v, $this->veiculos)) {
            $this->veiculos = array_values(array_filter($this->veiculos, fn($x) => $x !== $v));
        } else {
            $this->veiculos[] = $v;
        }
    }

    public function salvar(): void
    {
        $this->validate();
        $this->busy = true;

        Vaga::create([
            'criador_id'       => Auth::id(),
            'criador_tipo'     => $this->criadorTipo,
            'local'            => trim($this->local),
            'regiao'           => trim($this->regiao),
            'endereco'         => trim($this->endereco),
            'cep'              => $this->cep ?: null,
            'data'             => $this->data,
            'hora_inicio'      => $this->horaInicio,
            'hora_fim'         => $this->horaFim,
            'valor_diaria'     => (float)$this->valorDiaria,
            'valor_entrega'    => (float)$this->valorEntrega,
            'beneficios'       => $this->beneficios,
            'veiculos_aceitos' => $this->veiculos,
            'exige_bag_propria'=> $this->exigeBagPropria,
            'observacoes'      => trim($this->observacoes) ?: null,
            'status'           => 'disponivel',
            'lat'              => 0,
            'lng'              => 0,
        ]);

        $this->busy = false;
        session()->flash('success', 'Vaga publicada com sucesso!');
        $this->redirect(route('vagas.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.vagas.nova')
            ->layout('layouts.app')
            ->title('Nova vaga — MotoReserva');
    }
}
