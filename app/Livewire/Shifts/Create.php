<?php

namespace App\Livewire\Shifts;

use App\Models\Shift;
use App\Models\ShiftContact;
use App\Models\UserAddress;
use App\Support\Catalog;
use App\Support\Geocoder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Nova vaga — MotoReserva')]
class Create extends Component
{
    /** 'business' | 'courier' */
    public string $as = 'business';

    public ?string $addressId = null;

    public ?string $cloneId = null;

    /** When set, the form edits an existing shift instead of creating one. */
    public ?string $editId = null;

    // Resolved location (from the chosen address or the existing/cloned shift).
    public string $venue = '';
    public string $addressLine = '';
    public string $region = '';
    public ?string $cep = null;
    public float $lat = 0;
    public float $lng = 0;
    public ?string $addressPhotoUrl = null;

    /** Initial form values handed to Alpine. */
    public array $initial = [];

    public function mount(?string $id = null)
    {
        if ($id !== null) {
            return $this->mountEdit($id);
        }

        $this->as = request('as') === 'courier' ? 'courier' : 'business';
        $this->cloneId = request('clone');
        $this->addressId = request('address');

        $clone = $this->cloneId ? Shift::find($this->cloneId) : null;

        if ($clone) {
            $this->as = $clone->creator_role;
            $this->fillLocationFromShift($clone);
        } else {
            $address = $this->addressId ? UserAddress::where('user_id', Auth::id())->find($this->addressId) : null;
            if (! $address) {
                return $this->redirect(route('addresses.choose', ['as' => $this->as]), navigate: true);
            }
            $this->venue = $address->label;
            $this->addressLine = collect([
                $address->street.($address->number ? ', '.$address->number : ''),
                $address->district,
                $address->city,
            ])->filter()->join(' — ');
            $this->region = $address->district ?: $address->city ?: '';
            $this->cep = $address->postal_code;
            $this->lat = (float) ($address->lat ?? 0);
            $this->lng = (float) ($address->lng ?? 0);
            $this->addressPhotoUrl = $address->photo_url;
        }

        $this->initial = $this->initialFrom($clone);

        return null;
    }

    protected function mountEdit(string $id)
    {
        $shift = Shift::with('contact')->find($id);
        if (! $shift || $shift->creator_id !== Auth::id()) {
            return $this->redirect(route('shifts.index'), navigate: true);
        }

        $this->editId = $shift->id;
        $this->as = $shift->creator_role;
        $this->fillLocationFromShift($shift);
        $this->initial = $this->initialFrom($shift, withContact: true);

        return null;
    }

    protected function fillLocationFromShift(Shift $shift): void
    {
        $this->venue = $shift->venue;
        $this->addressLine = $shift->address;
        $this->region = $shift->region;
        $this->cep = $shift->postal_code;
        $this->lat = (float) $shift->lat;
        $this->lng = (float) $shift->lng;
        $this->addressPhotoUrl = $shift->address_photo_url;
    }

    protected function initialFrom(?Shift $source, bool $withContact = false): array
    {
        return [
            'date' => $source && $withContact ? $source->date->toDateString() : now()->toDateString(),
            'startTime' => $source->start_time ?? '18:00',
            'endTime' => $source->end_time ?? '23:00',
            'dailyRate' => $source ? (string) ($source->daily_rate + 0) : '',
            'fee' => $source ? (string) ($source->delivery_fee_min + 0) : '',
            'contactName' => $withContact ? ($source->contact?->contact_name ?? '') : '',
            'contactPhone' => $withContact ? ($source->contact?->whatsapp_phone ?? '') : '',
            'notes' => $source->notes ?? '',
            'venueType' => $source->venue_type ?? null,
            'expectedVolume' => $source->expected_volume ?? null,
            'couriersNeeded' => $source->couriers_needed ?? 1,
            'benefits' => $source->benefits ?? [],
            'vehicles' => ($source && ! empty($source->accepted_vehicles)) ? $source->accepted_vehicles : ['moto'],
            'requiresOwnBag' => $source ? (bool) $source->requires_own_bag : true,
        ];
    }

    public function save(array $form)
    {
        $existing = $this->editId ? Shift::find($this->editId) : null;
        if ($this->editId && (! $existing || $existing->creator_id !== Auth::id())) {
            return null;
        }

        $toast = fn (string $m) => $this->dispatch('toast', message: $m);

        $dailyRate = trim((string) ($form['dailyRate'] ?? ''));
        $fee = trim((string) ($form['fee'] ?? ''));
        $date = (string) ($form['date'] ?? '');
        $startTime = (string) ($form['startTime'] ?? '');
        $endTime = (string) ($form['endTime'] ?? '');
        $venueType = $form['venueType'] ?? null;
        $expectedVolume = $form['expectedVolume'] ?? null;
        $vehicles = array_values(array_intersect((array) ($form['vehicles'] ?? []), Catalog::VEHICLE_OPTIONS));
        $benefits = array_values(array_intersect((array) ($form['benefits'] ?? []), Catalog::BENEFIT_OPTIONS));
        $couriers = max(1, min(10, (int) ($form['couriersNeeded'] ?? 1)));

        if ($dailyRate === '') {
            return $toast('O valor da diária é obrigatório.');
        }
        if ($fee === '') {
            return $toast('Informe a taxa por entrega.');
        }
        if (! array_key_exists($venueType, Catalog::VENUE_TYPE_LABEL)) {
            return $toast('Selecione o tipo do local.');
        }
        if (! array_key_exists($expectedVolume, Catalog::VOLUME_LABEL)) {
            return $toast('Selecione o movimento esperado.');
        }
        if (empty($vehicles)) {
            return $toast('Selecione ao menos um veículo aceito.');
        }
        if (! $date || ! $startTime || ! $endTime) {
            return $toast('Preencha data e horários.');
        }
        if (Carbon::parse("{$date} {$startTime}")->isPast()) {
            return $toast('A data/horário já passou. Ajuste para um momento futuro.');
        }

        $conflict = Shift::where('creator_id', Auth::id())
            ->where('venue', $this->venue)
            ->whereDate('date', $date)
            ->where('status', '!=', Shift::STATUS_FILLED)
            ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))
            ->get()
            ->first(fn ($v) => $startTime < $v->end_time && $v->start_time < $endTime);

        if ($conflict) {
            return $toast('Você já tem uma vaga neste local nesse mesmo horário.');
        }

        $data = [
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'daily_rate' => (float) $dailyRate,
            'delivery_fee' => (float) $fee,
            'delivery_fee_min' => (float) $fee,
            'delivery_fee_max' => (float) $fee,
            'venue_type' => $venueType,
            'expected_volume' => $expectedVolume,
            'benefits' => $benefits,
            'accepted_vehicles' => $vehicles,
            'requires_own_bag' => (bool) ($form['requiresOwnBag'] ?? false),
            'notes' => trim((string) ($form['notes'] ?? '')),
            'couriers_needed' => $couriers,
        ];

        $contactName = trim((string) ($form['contactName'] ?? '')) ?: null;
        $contactPhone = trim((string) ($form['contactPhone'] ?? '')) ?: null;

        if ($existing) {
            $existing->update($data + ['edited_at' => now()]);
            ShiftContact::updateOrCreate(
                ['shift_id' => $existing->id],
                ['contact_name' => $contactName, 'whatsapp_phone' => $contactPhone],
            );
            $this->dispatch('toast', message: 'Vaga atualizada!');

            return $this->redirect(route('shifts.show', $existing->id), navigate: true);
        }

        // Last-resort geocode if the chosen address/clone carried no coordinates,
        // so the shift still lands on the map.
        if ((! $this->lat || ! $this->lng) && ! app()->runningUnitTests()) {
            $coords = Geocoder::forShift($this->addressLine, $this->region, null, $this->cep);
            if ($coords) {
                $this->lat = $coords['lat'];
                $this->lng = $coords['lng'];
            }
        }

        $shift = Shift::create($data + [
            'creator_id' => Auth::id(),
            'creator_role' => $this->as,
            'venue' => $this->venue,
            'region' => $this->region,
            'address' => $this->addressLine,
            'postal_code' => $this->cep,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'address_photo_url' => $this->addressPhotoUrl,
        ]);

        if ($contactName || $contactPhone) {
            ShiftContact::create([
                'shift_id' => $shift->id,
                'contact_name' => $contactName,
                'whatsapp_phone' => $contactPhone,
            ]);
        }

        $located = (float) $shift->lat !== 0.0 && (float) $shift->lng !== 0.0;
        $this->dispatch('toast',
            message: $located
                ? 'Vaga publicada com sucesso!'
                : 'Vaga publicada! Não conseguimos localizar o endereço no mapa agora — ela aparecerá no mapa assim que a localização for resolvida.',
            type: $located ? 'success' : 'error',
        );

        // The shift was created — drop the saved form draft (see create.blade.php).
        $this->dispatch('clear-shift-draft');

        return $this->redirect(route('shifts.show', $shift->id), navigate: true);
    }

    public function render()
    {
        return view('livewire.shifts.create');
    }
}
