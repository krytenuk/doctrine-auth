<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\TwoFactor;

/**
 * Asks for a second factor from users who have set one up. With no methods installed, nobody is asked.
 */
final class EnabledMethodsPolicy implements TwoFactorPolicyInterface
{
    public function __construct(private readonly TwoFactorMethods $methods)
    {
    }

    public function requiresTwoFactor(object $user): bool
    {
        return $this->methods->enabledFor($user) !== [];
    }
}
