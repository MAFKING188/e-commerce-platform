<?php

namespace Modules\IdentityAccess\Services;

use Illuminate\Session\Store as SessionStore;
use Modules\IdentityAccess\Models\User;

class StepUpService
{
    private const VERIFIED_TTL = 900;

    public static function begin(User $user, SessionStore $session): void
    {
        OtpService::send($user);
        $session->put('stepup.pending', true);
    }

    public static function isVerified(SessionStore $session): bool
    {
        $verifiedAt = $session->get('stepup.verified');
        return is_int($verifiedAt) && ($verifiedAt + self::VERIFIED_TTL) > time();
    }

    public static function complete(SessionStore $session): void
    {
        $session->put('stepup.verified', time());
        $session->forget('stepup.pending');
    }

    public static function invalidate(SessionStore $session): void
    {
        $session->forget(['stepup.pending', 'stepup.verified']);
    }
}