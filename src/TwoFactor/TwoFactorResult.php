<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\TwoFactor;

use Mezzio\Session\SessionInterface;

final class TwoFactorResult
{
    /**
     * @param SessionInterface|null $session On success, the session to use from now on, as the login changes it
     * @param int|null $retryAfter When throttled, seconds until logins are allowed again, or null if blocked with no end
     */
    private function __construct(
        public readonly TwoFactorStatus $status,
        public readonly ?object $user = null,
        public readonly ?SessionInterface $session = null,
        public readonly ?int $retryAfter = null,
    ) {
    }

    public static function success(object $user, SessionInterface $session): self
    {
        return new self(TwoFactorStatus::Success, $user, $session);
    }

    public static function invalidCode(): self
    {
        return new self(TwoFactorStatus::InvalidCode);
    }

    public static function throttled(?int $retryAfter): self
    {
        return new self(TwoFactorStatus::Throttled, retryAfter: $retryAfter);
    }

    public static function noPendingLogin(): self
    {
        return new self(TwoFactorStatus::NoPendingLogin);
    }

    public function isSuccess(): bool
    {
        return $this->status === TwoFactorStatus::Success;
    }
}
