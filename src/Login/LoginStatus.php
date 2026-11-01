<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Login;

enum LoginStatus
{
    case Success;
    case TwoFactorRequired;
    case InvalidCredentials;
    case Throttled;
}
