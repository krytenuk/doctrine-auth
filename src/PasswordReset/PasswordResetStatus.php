<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\PasswordReset;

enum PasswordResetStatus
{
    case Success;

    /** The token is wrong, used or expired. There is deliberately no way to tell which */
    case InvalidToken;

    /** The token still works, so the user can try again with another password */
    case UnacceptablePassword;
}
