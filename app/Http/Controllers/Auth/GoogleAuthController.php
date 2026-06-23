<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /** Send the user off to Google's consent screen. */
    public function redirect(): RedirectResponse
    {
        try {
            return Socialite::driver('google')->redirect();
        } catch (\Throwable $e) {
            report($e);

            // Most likely the credentials aren't configured in this environment.
            return redirect()->route('login')
                ->with('notice', 'Login com Google indisponível no momento.');
        }
    }

    /** Handle the callback: find or create the user, then sign them in. */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('login')
                ->with('notice', 'Não foi possível entrar com o Google. Tente novamente.');
        }

        // Match by google_id first, then fall back to email to link an
        // account that was originally created with e-mail/password.
        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            if ($user->google_id !== $googleUser->getId()) {
                $user->forceFill(['google_id' => $googleUser->getId()])->save();
            }
        } else {
            // password stays null (migration); UserObserver provisions the
            // base profile (role=courier) + settings automatically.
            $user = User::create([
                'name' => $googleUser->getName() ?: Str::before((string) $googleUser->getEmail(), '@'),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
            ]);
        }

        // Carry the Google avatar over, but never overwrite a chosen photo.
        if ($googleUser->getAvatar() && blank($user->profile?->photo_url)) {
            $user->profile()->update(['photo_url' => $googleUser->getAvatar()]);
        }

        Auth::login($user, remember: true);
        session()->regenerate();

        return redirect()->intended(route('shifts.index'));
    }
}
