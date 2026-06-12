<?php
// app/Livewire/Vagas/Index.php

namespace App\Livewire\Vagas;

use App\Models\Vaga;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Index extends Component
{
    public string $q = '';
    public ?string $regiao = null;

    // Filtros avançados (draft = o que está no modal, filtros = o que está aplicado)
    public bool $filtersOpen = false;
    public bool $vehicleOpen = false;

    public array $filtros = [
        'veiculos' => [],
        'diariaMin' => '',
        'entregaMin' => '',
        'horaInicio' => '',
        'beneficios' => [],
        'bagPropria' => 'qualquer',
        'data' => '',
    ];

    public array $draft = [
        'veiculos' => [],
        'diariaMin' => '',
        'entregaMin' => '',
        'horaInicio' => '',
        'beneficios' => [],
        'bagPropria' => 'qualquer',
        'data' => '',
    ];

    public function openFilters(): void
    {
        $this->draft = $this->filtros;
        $this->filtersOpen = true;
    }

    public function applyFilters(): void
    {
        $this->filtros = $this->draft;
        $this->filtersOpen = false;
    }

    public function clearFilters(): void
    {
        $this->draft = [
            'veiculos' => [],
            'diariaMin' => '',
            'entregaMin' => '',
            'horaInicio' => '',
            'beneficios' => [],
            'bagPropria' => 'qualquer',
            'data' => '',
        ];
        $this->filtros = $this->draft;
    }

    public function toggleVeicDraft(string $v): void
    {
        if (in_array($v, $this->draft['veiculos'])) {
            $this->draft['veiculos'] = array_values(array_filter($this->draft['veiculos'], fn($x) => $x !== $v));
        } else {
            $this->draft['veiculos'][] = $v;
        }
    }

    public function toggleBenDraft(string $b): void
    {
        if (in_array($b, $this->draft['beneficios'])) {
            $this->draft['beneficios'] = array_values(array_filter($this->draft['beneficios'], fn($x) => $x !== $b));
        } else {
            $this->draft['beneficios'][] = $b;
        }
    }

    public function atualizarVeiculo(string $veiculo): void
    {
        $profile = Profile::where('id', Auth::id())->first();
        if ($profile) {
            $profile->update(['veiculo' => $veiculo]);
        }
        $this->vehicleOpen = false;
    }

    public function getVagasFiltradas()
    {
        $agora = now();
        $user = Auth::user();

        $query = Vaga::where('status', '!=', 'preenchida')
            ->whereRaw("(data || 'T' || hora_fim || ':00')::timestamp >= ?", [$agora]);

        // Busca por texto
        if ($this->q) {
            $term = '%' . $this->q . '%';
            $query->where(function ($q) use ($term) {
                $q->where('local', 'ilike', $term)
                    ->orWhere('regiao', 'ilike', $term)
                    ->orWhere('endereco', 'ilike', $term);
            });
        }

        // Filtro de região (chip)
        if ($this->regiao) {
            $query->where('regiao', $this->regiao);
        }

        // Veículos aceitos
        if (!empty($this->filtros['veiculos'])) {
            $query->where(function ($q) {
                foreach ($this->filtros['veiculos'] as $v) {
                    $q->orWhereRaw('? = ANY(veiculos_aceitos)', [$v]);
                }
            });
        }

        if ($this->filtros['diariaMin'] !== '') {
            $query->where('valor_diaria', '>=', (float) $this->filtros['diariaMin']);
        }

        if ($this->filtros['entregaMin'] !== '') {
            $query->where('valor_entrega', '>=', (float) $this->filtros['entregaMin']);
        }

        if ($this->filtros['horaInicio'] !== '') {
            $query->where('hora_inicio', '>=', $this->filtros['horaInicio']);
        }

        if ($this->filtros['data'] !== '') {
            $query->where('data', $this->filtros['data']);
        }

        if (!empty($this->filtros['beneficios'])) {
            foreach ($this->filtros['beneficios'] as $b) {
                $query->whereRaw('? = ANY(beneficios)', [$b]);
            }
        }

        if ($this->filtros['bagPropria'] === 'sim') {
            $query->where('exige_bag_propria', true);
        } elseif ($this->filtros['bagPropria'] === 'nao') {
            $query->where('exige_bag_propria', false);
        }

        return $query->orderByRaw("CASE WHEN status = 'disponivel' THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getRegioes(): array
    {
        return Vaga::distinct()->pluck('regiao')->toArray();
    }

    public function getFiltrosAtivos(): int
    {
        return (count($this->filtros['veiculos']) > 0 ? 1 : 0)
            + ($this->filtros['diariaMin'] !== '' ? 1 : 0)
            + ($this->filtros['entregaMin'] !== '' ? 1 : 0)
            + ($this->filtros['horaInicio'] !== '' ? 1 : 0)
            + (count($this->filtros['beneficios']) > 0 ? 1 : 0)
            + ($this->filtros['bagPropria'] !== 'qualquer' ? 1 : 0)
            + ($this->filtros['data'] !== '' ? 1 : 0);
    }

    public function render()
    {
        $profile = Profile::query()->first();

        return view('livewire.vagas.index', [
            'vagas' => $this->getVagasFiltradas(),
            'regioes' => $this->getRegioes(),
            'filtrosAtivos' => $this->getFiltrosAtivos(),
            'profile' => $profile,
        ])->layout('layouts.app')->title('Vagas — MotoReserva');
    }
}
