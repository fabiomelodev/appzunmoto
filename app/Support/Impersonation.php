<?php

namespace App\Support;

use App\Models\ImpersonationLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Admin "acessar como usuário": the session simply switches to the target account (no password, no
 * OAuth, so Google-only accounts work the same), remembering the admin so they can go back.
 *
 * While impersonating, actions that would leak into the user's real life are suppressed (marking
 * their messages/notifications as read, saving the admin's browser as a push device for them,
 * changing their e-mail/password/Google link): see isActive() at those places.
 */
class Impersonation
{
    public const SESSION_ADMIN = 'impersonator_id';

    public const SESSION_LOG = 'impersonation_log_id';

    public static function isActive(): bool
    {
        return session()->has(self::SESSION_ADMIN);
    }

    public static function admin(): ?User
    {
        $id = session(self::SESSION_ADMIN);

        return $id ? User::find($id) : null;
    }

    /** Only admins may start, never on themselves, another admin, or while already impersonating. */
    public static function canImpersonate(?User $admin, User $target): bool
    {
        return (bool) $admin?->is_admin
            && ! self::isActive()
            && $admin->getKey() !== $target->getKey()
            && ! $target->is_admin;
    }

    public static function start(User $admin, User $target): bool
    {
        if (! self::canImpersonate($admin, $target)) {
            return false;
        }

        $log = ImpersonationLog::create([
            'admin_id' => $admin->getKey(),
            'user_id' => $target->getKey(),
            'ip' => request()->ip(),
            'started_at' => now(),
        ]);

        Auth::login($target); // not "remember": the access dies with the session
        session()->regenerate();
        session()->put(self::SESSION_ADMIN, $admin->getKey());
        session()->put(self::SESSION_LOG, $log->getKey());

        return true;
    }

    /** Back to the admin's own account. Returns false when there is nothing to leave. */
    public static function leave(): bool
    {
        $admin = self::admin();
        if (! $admin) {
            return false;
        }

        if ($logId = session(self::SESSION_LOG)) {
            ImpersonationLog::whereKey($logId)->whereNull('ended_at')->update(['ended_at' => now()]);
        }

        session()->forget([self::SESSION_ADMIN, self::SESSION_LOG]);
        Auth::login($admin);
        session()->regenerate();

        return true;
    }
}
