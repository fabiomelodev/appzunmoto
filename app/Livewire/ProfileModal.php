<?php

namespace App\Livewire;

use App\Models\Profile;
use App\Models\Review;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Reusable courier profile dialog. Any page can open it with:
 *   $dispatch('open-profile', { userId: '...' })
 */
class ProfileModal extends Component
{
    public ?string $userId = null;

    #[On('open-profile')]
    public function open(string $userId): void
    {
        $this->userId = $userId;
    }

    public function close(): void
    {
        $this->userId = null;
    }

    #[Computed]
    public function data(): ?array
    {
        if (! $this->userId) {
            return null;
        }

        return [
            'profile' => Profile::publicColumns()->find($this->userId),
            'reviews' => Review::where('target_id', $this->userId)
                ->latest('created_at')
                ->limit(5)
                ->get(['rating', 'comment', 'created_at']),
        ];
    }

    public function render()
    {
        return view('livewire.profile-modal');
    }
}
