<?php

namespace App\Livewire;

use App\Models\Profile;
use App\Support\Reviews;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * [TEST BRANCH: feature/avaliacao-obrigatoria] Makes reviewing mandatory:
 * a non-dismissible modal, mounted once in components/layouts/app.blade.php,
 * blocks the rest of the app until every review the signed-in account owes
 * (as creator or as a confirmed courier, on any shift that already ended)
 * has been submitted. One at a time, oldest shift first.
 *
 * Never mounted on the guest/onboarding layout, so it can't block login or
 * onboarding — only the main app screens use components.layouts.app.
 */
class ReviewGate extends Component
{
    public int $rating = 0;

    public string $comment = '';

    #[Computed]
    public function pending()
    {
        if (! Auth::check()) {
            return collect();
        }

        return Reviews::pendingForUser(Auth::id())->sortBy(fn ($p) => $p['shift']->date);
    }

    #[Computed]
    public function current(): ?array
    {
        $first = $this->pending->first();
        if (! $first) {
            return null;
        }

        $profile = Profile::publicColumns()->find($first['targetId']);

        return [
            'shift' => $first['shift'],
            'targetId' => $first['targetId'],
            'name' => $profile?->name ?: 'Usuário',
            'remaining' => $this->pending->count(),
        ];
    }

    public function setRating(int $value): void
    {
        $this->rating = max(0, min(5, $value));
    }

    public function submit(): void
    {
        $current = $this->current;
        if (! $current || $this->rating < 1) {
            return;
        }

        Reviews::submit($current['shift'], Auth::id(), $current['targetId'], $this->rating, $this->comment);

        $this->reset('rating', 'comment');
        unset($this->pending, $this->current);
    }

    public function render()
    {
        return view('livewire.review-gate');
    }
}
