<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\TwoFactor;

enum TwoFactorStatus
{
    case Success;
    case InvalidCode;
    case Throttled;
    /** There is no login waiting for a code, or it has expired. Send the user back to the login page */
    case NoPendingLogin;
}
