<?php

namespace Modules\IdentityAccess\Enums;

enum TwoFactorType: string
{
    case Totp = 'totp';
    case Email = 'email';
}