<?php

namespace App\Livewire;

use App\Models\Application;
use App\Models\Review;
use App\Models\Shift;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Perfil — GiroMoto')]
class ProfilePage extends Component
{
    use WithFileUploads;

    public string $tab = 'info';

    public string $name = '';

    public string $cpf = '';

    public string $birthDate = '';

    public string $phone = '';

    public string $street = '';

    public string $streetNumber = '';

    public string $district = '';

    public string $city = '';

    public string $bio = '';

    public bool $hasBag = false;

    public $photo;

    public function mount(): void
    {
        $p = Auth::user()->profile;
        $this->name = $p?->name ?: Auth::user()->name;
        $this->cpf = $p?->cpf ?? '';
        $this->birthDate = $p?->birth_date?->format('d/m/Y') ?? '';
        $this->phone = $p?->phone ?? '';
        $this->street = $p?->street ?? '';
        $this->streetNumber = $p?->street_number ?? '';
        $this->district = $p?->district ?? '';
        $this->city = $p?->city ?? '';
        $this->bio = $p?->bio ?? '';
        $this->hasBag = (bool) $p?->has_bag;
    }

    public function updatedPhoto(): void
    {
        $this->validate(['photo' => ['image', 'max:4096']]);

        $path = $this->photo->storePubliclyAs('avatars', Auth::id().'.'.$this->photo->getClientOriginalExtension(), 'public');
        Auth::user()->profile?->update(['photo_url' => Storage::disk('public')->url($path)]);
        $this->photo = null;
        $this->dispatch('toast', message: 'Foto atualizada.');
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'min:2'],
            'city' => ['nullable', 'string'],
            'bio' => ['nullable', 'string', 'max:500'],
        ]);

        $birth = null;
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', trim($this->birthDate), $m)) {
            $birth = "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        Auth::user()->profile?->update([
            'name' => trim($this->name),
            'cpf' => preg_replace('/\D/', '', $this->cpf) ?: null,
            'birth_date' => $birth,
            'phone' => preg_replace('/\D/', '', $this->phone) ?: null,
            'street' => trim($this->street),
            'street_number' => trim($this->streetNumber),
            'district' => trim($this->district),
            'city' => trim($this->city),
            'bio' => trim($this->bio),
            'has_bag' => $this->hasBag,
        ]);

        // Keep the auth display name in sync.
        Auth::user()->update(['name' => trim($this->name)]);

        $this->dispatch('toast', message: 'Perfil atualizado com sucesso!');
    }

    #[Computed]
    public function stats(): array
    {
        $id = Auth::id();
        $published = Shift::where('creator_id', $id)->count();
        $completed = Shift::where('creator_id', $id)->where('status', 'filled')->count()
            + Application::where('user_id', $id)->where('confirmed', true)->count();

        return [
            'published' => $published,
            'completed' => $completed,
            'rating' => (float) (Auth::user()->profile?->avg_rating ?? 0),
            'totalReviews' => (int) (Auth::user()->profile?->total_reviews ?? 0),
        ];
    }

    #[Computed]
    public function reviews()
    {
        return Review::with('author.profile')
            ->where('target_id', Auth::id())
            ->latest('created_at')
            ->get();
    }

    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('login');
    }

    public function render()
    {
        return view('livewire.profile-page', [
            'user' => Auth::user(),
            'profile' => Auth::user()->profile,
        ]);
    }
}
