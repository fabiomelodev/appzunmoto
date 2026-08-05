<?php

namespace App\Livewire;

use App\Models\Document;
use App\Support\Catalog;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Veículo e Documentação — GiroMoto')]
class Vehicle extends Component
{
    use WithFileUploads;

    public string $vehicle = 'moto';

    public $identityFile;

    public $vehicleFile;

    public function mount(): void
    {
        $this->vehicle = Auth::user()->profile?->vehicle ?? 'moto';
    }

    public function setVehicle(string $vehicle): void
    {
        if (in_array($vehicle, Catalog::VEHICLE_OPTIONS, true)) {
            $this->vehicle = $vehicle;
        }
    }

    public function save(): void
    {
        Auth::user()->profile?->update(['vehicle' => $this->vehicle]);
        $this->dispatch('toast', message: 'Veículo e documentação salvos.');
    }

    public function updatedIdentityFile(): void
    {
        $this->validate(['identityFile' => ['file', 'max:8192', 'mimes:jpg,jpeg,png,webp,pdf']]);
        $this->storeDocument($this->identityFile, Document::TYPE_IDENTITY);
        $this->identityFile = null;
    }

    public function updatedVehicleFile(): void
    {
        $this->validate(['vehicleFile' => ['file', 'max:8192', 'mimes:jpg,jpeg,png,webp,pdf']]);
        $this->storeDocument($this->vehicleFile, Document::TYPE_VEHICLE);
        $this->vehicleFile = null;
    }

    protected function storeDocument($file, string $type): void
    {
        $ext = $file->getClientOriginalExtension() ?: 'dat';
        // Identity/vehicle docs are PII → stored on the PRIVATE disk and served
        // only to the owner via the authorized `documents.file` route.
        $path = $file->storeAs('documents/'.Auth::id(), "{$type}.{$ext}", 'local');

        Document::updateOrCreate(
            ['user_id' => Auth::id(), 'type' => $type],
            [
                'status' => 'review',
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'submitted_at' => now(),
            ],
        );

        $this->dispatch('toast', message: 'Documento enviado para análise.');
    }

    #[Computed]
    public function docs(): array
    {
        $all = Document::where('user_id', Auth::id())->get()->keyBy('type');

        return [
            'identity' => $all->get('identity'),
            'vehicle' => $all->get('vehicle'),
        ];
    }

    public function render()
    {
        return view('livewire.vehicle');
    }
}
