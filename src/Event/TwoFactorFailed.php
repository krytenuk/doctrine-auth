<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Event;

/**
 * A wrong code was entered for a user whose password was right.
 */
final class TwoFactorFailed
{
    public function __construct(
        public readonly object $user,
        public readonly string $userId,
        public readonly string $method,
        public readonly string $ipAddress,
    ) {
    }
}
