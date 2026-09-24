<?php

namespace App\Livewire;

use App\Models\Application;
use App\Models\Profile;
use App\Models\Review;
use App\Models\Shift;
use App\Support\Cpf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Perfil — ZunMoto')]
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

        // Same rule as the registration wizard: only a real calendar date is accepted.
        $birth = null;
        $birthDate = trim($this->birthDate);
        if ($birthDate !== '') {
            if (! preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $birthDate, $m) || ! checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
                $this->addError('birthDate', 'Data de nascimento inválida (use DD/MM/AAAA).');

                return;
            }
            $birth = "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        $cpfDigits = preg_replace('/\D/', '', $this->cpf);
        if ($cpfDigits !== '') {
            if (! Cpf::isValid($cpfDigits)) {
                $this->addError('cpf', 'CPF inválido.');

                return;
            }

            if (Profile::where('cpf', $cpfDigits)->where('id', '!=', Auth::id())->exists()) {
                $this->addError('cpf', 'Esse CPF já está cadastrado em outra conta.');

                return;
            }
        }

        Auth::user()->profile?->update([
            'name' => trim($this->name),
            'cpf' => $cpfDigits ?: null,
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

        // Ratings are kept per role: what was received as a courier and as an
        // establishment don't mix (see ReviewObserver).
        $profile = Auth::user()->profile;
        $business = $profile?->isBusiness();

        return [
            'published' => $published,
            'completed' => $completed,
            'rating' => (float) ($business ? $profile->business_avg_rating : $profile?->avg_rating),
            'totalReviews' => (int) ($business ? $profile->business_total_reviews : $profile?->total_reviews),
        ];
    }

    #[Computed]
    public function reviews()
    {
        // The author is never loaded: reviews are shown as anonymous.
        return Review::published()
            ->where('target_id', Auth::id())
            ->where('target_role', Auth::user()->profile?->isBusiness() ? 'business' : 'courier')
            ->latest('published_at')
            ->get();
    }

    /**
     * $pushEndpoint: this browser's push subscription, unsubscribed
     * client-side right before this call (see profile-page.blade.php) —
     * deleted here too so the next account logged in on this device doesn't
     * silently inherit it (push subscriptions are per-browser, not per-account).
     */
    public function logout(?string $pushEndpoint = null)
    {
        if ($pushEndpoint) {
            Auth::user()?->deletePushSubscription($pushEndpoint);
        }

        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('login');
    }

    public function render()
    {
        $profile = Auth::user()->profile;

        return view('livewire.profile-page', [
            'user' => Auth::user(),
            'profile' => $profile,
            'isBusiness' => $profile?->role === 'business',
        ]);
    }
}
