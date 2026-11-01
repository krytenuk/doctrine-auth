<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\TwoFactor;

final class NoTwoFactorPolicy implements TwoFactorPolicyInterface
{
    public function requiresTwoFactor(object $user): bool
    {
        return false;
    }
}
