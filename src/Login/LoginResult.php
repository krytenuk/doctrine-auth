<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Login;

/**
 * The outcome of a login attempt. There is deliberately no way to tell an unknown user from a wrong password or a
 * disabled account, so that a login page cannot reveal which accounts exist.
 */
final class LoginResult
{
    private function __construct(
        public readonly LoginStatus $status,
        public readonly ?object $user = null,
        public readonly ?int $retryAfter = null,
    ) {
    }

    public static function success(object $user): self
    {
        return new self(LoginStatus::Success, $user);
    }

    public static function twoFactorRequired(object $user): self
    {
        return new self(LoginStatus::TwoFactorRequired, $user);
    }

    public static function invalidCredentials(): self
    {
        return new self(LoginStatus::InvalidCredentials);
    }

    public static function throttled(?int $retryAfter): self
    {
        return new self(LoginStatus::Throttled, retryAfter: $retryAfter);
    }

    public function isSuccess(): bool
    {
        return $this->status === LoginStatus::Success;
    }

    public function requiresTwoFactor(): bool
    {
        return $this->status === LoginStatus::TwoFactorRequired;
    }

    public function isThrottled(): bool
    {
        return $this->status === LoginStatus::Throttled;
    }
}
