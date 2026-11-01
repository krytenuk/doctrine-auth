<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\TwoFactor;

interface TwoFactorPolicyInterface
{
    /**
     * Whether this user must pass a second factor before they are logged in.
     */
    public function requiresTwoFactor(object $user): bool;
}
