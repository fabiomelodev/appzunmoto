<?php

namespace App\Observers;

use App\Models\Profile;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Support\Str;

/**
 * Provisions a base profile and default settings for every new user.
 * Mirrors the Supabase `handle_new_user` trigger.
 */
class UserObserver
{
    public function created(User $user): void
    {
        Profile::firstOrCreate(
            ['id' => $user->id],
            [
                'role' => 'courier',
                'name' => $user->name ?: Str::before($user->email ?? '', '@') ?: 'Usuário',
            ],
        );

        UserSetting::firstOrCreate(['user_id' => $user->id]);
    }
}
