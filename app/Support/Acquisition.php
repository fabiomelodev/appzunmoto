<?php

namespace App\Support;

use App\Models\AcquisitionChannel;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * "De onde vêm os usuários": first-touch attribution from ?utm_source=… (QR codes on TV and
 * flyers, ads…). The value is remembered in a first-party cookie by CaptureAcquisitionSource
 * (the query string is lost on the redirect to /login) and attached to the account when it signs up.
 */
class Acquisition
{
    public const COOKIE = 'zm_utm_source';

    /** Cookie lifetime, in minutes (60 days). */
    public const COOKIE_MINUTES = 60 * 24 * 60;

    /** What the visitor can pick in "Como você conheceu o ZunMoto?" (onboarding, optional). */
    public const HEARD_FROM = [
        'tv' => 'TV',
        'panfleto' => 'Panfleto',
        'indicacao' => 'Indicação de amigo ou conhecido',
        'instagram' => 'Instagram',
        'google' => 'Google',
        'youtube' => 'YouTube',
        'outro' => 'Outro',
    ];

    /**
     * utm_source comes straight from the address bar, so it is untrusted: lower-case it and keep
     * only [a-z0-9_-] (max 64) — "TV", "tv " and "Tv" are the same channel, and nothing odd gets stored.
     */
    public static function normalize(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9_-]+/', '', $value) ?? '';

        return substr($value, 0, 64);
    }

    /** Records on a brand-new account where it came from (no-op without a remembered source). */
    public static function attach(User $user, ?Request $request = null): void
    {
        $source = self::normalize(($request ?? request())->cookie(self::COOKIE));
        if ($source === '') {
            return;
        }

        $channel = AcquisitionChannel::where('slug', $source)->where('active', true)->first();

        $user->forceFill(['utm_source' => $source, 'acquisition_channel_id' => $channel?->id])->save();
    }

    /** Label shown in the panel for a user's tracked source. */
    public static function label(?string $utmSource, ?string $channelName): string
    {
        if ($channelName) {
            return $channelName;
        }

        return $utmSource ? 'Outro: '.$utmSource : 'Direto / não informado';
    }
}
