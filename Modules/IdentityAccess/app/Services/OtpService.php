<?php

namespace Modules\IdentityAccess\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Modules\IdentityAccess\Mail\OtpMail;
use Modules\IdentityAccess\Models\User;

class OtpService
{
    private const OTP_TTL = 600;

    public static function issue(User $user): string
    {
        $code = (string) random_int(100000, 999999);
        Cache::put('2fa:otp:' . $user->id, Hash::make($code), self::OTP_TTL);
        return $code;
    }

    public static function send(User $user): void
    {
        Mail::to($user)->queue(new OtpMail($user, self::issue($user)));
    }

    public static function check(User $user, string $code): bool
    {
        $hashed = Cache::get('2fa:otp:' . $user->id);
        if (! $hashed || ! Hash::check($code, $hashed)) {
            return false;
        }
        Cache::forget('2fa:otp:' . $user->id);
        return true;
    }
}