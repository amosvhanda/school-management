<?php

namespace App\Enums;

enum LicenseKeyStatus: string
{
    case Unused = 'unused';
    case Active = 'active';
    case Expired = 'expired';
    case Revoked = 'revoked';
}
