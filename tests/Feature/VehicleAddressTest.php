<?php

namespace Tests\Feature;

use App\Livewire\Addresses\Index as AddressesIndex;
use App\Livewire\Vehicle;
use App\Models\UserAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class VehicleAddressTest extends TestCase
{
    use RefreshDatabase;

    protected function user(): User
    {
        return User::create([
            'name' => 'Carlos',
            'email' => 'carlos'.uniqid().'@test.dev',
            'password' => 'secret123',
        ]);
    }

    public function test_vehicle_save_updates_profile(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        Livewire::test(Vehicle::class)
            ->set('vehicle', 'bike-eletrica')
            ->call('save')
            ->assertDispatched('toast');

        $this->assertSame('bike-eletrica', $user->fresh()->profile->vehicle);
    }

    public function test_document_upload_creates_record(): void
    {
        Storage::fake('public');
        $user = $this->user();
        $this->actingAs($user);

        Livewire::test(Vehicle::class)
            ->set('identityFile', UploadedFile::fake()->image('cnh.jpg'))
            ->assertHasNoErrors();

        $this->assertDatabaseHas('documents', [
            'user_id' => $user->id, 'type' => 'identity', 'status' => 'review',
        ]);
    }

    public function test_address_create_edit_delete(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        $component = Livewire::test(AddressesIndex::class)
            ->call('openNew')
            ->set('label', 'Casa')
            ->set('street', 'Rua A')
            ->set('number', '10')
            ->set('district', 'Centro')
            ->set('city', 'SP')
            ->set('cep', '01310-100')
            ->call('save');

        $address = UserAddress::where('user_id', $user->id)->first();
        $this->assertNotNull($address);
        $this->assertSame('Casa', $address->label);
        $this->assertSame('01310100', $address->postal_code);

        $component->call('openEdit', $address->id)
            ->set('label', 'Trabalho')
            ->call('save');
        $this->assertSame('Trabalho', $address->fresh()->label);

        $component->call('delete', $address->id);
        $this->assertDatabaseMissing('user_addresses', ['id' => $address->id]);
    }

    public function test_pages_render(): void
    {
        $this->actingAs($this->user());

        $this->get(route('vehicle'))->assertOk()->assertSee('Veículo utilizado');
        $this->get(route('documents'))->assertOk()->assertSee('Documentação');
        $this->get(route('addresses'))->assertOk()->assertSee('Meus Endereços');
    }
}
